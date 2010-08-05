<?php

class MailScanner_Module_Analyze extends MailScanner_Module_Abstract
{
    /**
     * @var Zend_Mail_Storage_Imap
     */
    protected $_mail = null;

    /**
     * @var MailScanner_Log_Interface
     */
    protected $_log = null;

    /**
     * @var Zend_Config
     */
    protected $_config = null;

    /**
     * Constructor
     *
     * @param Zend_Mail_Storage_Imap    $mail
     * @param MailScanner_Log_Interface $log
     * @param Zend_Config               $config
     */
    public function __construct(Zend_Mail_Storage_Imap $mail, MailScanner_Log_Interface $log, Zend_Config $config)
    {
        $this->_parseConfig($config);

        $this->_mail = $mail;
        $this->_config = $config;
        $this->_log = $log;
    }

    protected function _doInit()
    {
        $this->_init();
    }

    protected function _doRead()
    {
        $this->_readMessages();
    }

    protected function _doDelete()
    {
        $this->_deleteMessages();
    }

    protected function _doPrepare()
    {
        $results = $this->_rawResult;
        $this->_rawResult = array();

        $key = $this->_config->match->key;

        foreach ($results as $row)
        {
            $matchString = $row['match'][$key];

            if (empty($this->_rawResult[$matchString]))
            {
                $this->_rawResult[$matchString] = array(
                    'occurances' => 0,
                    'errors'     => 0
                );
            }

            $this->_rawResult[$matchString]['occurances']++;

            if ($row['status'] !== 'success')
            {
                $this->_rawResult[$matchString]['errors']++;
            }
        }
    }

    protected function _doAnalyze()
    {
        $this->_log->log(PHP_EOL . 'Analyzing results...' . PHP_EOL);

        foreach ($this->_options['check'] as $name => $expectedRow)
        {
            $expectedString = $expectedRow['expected'];
            $expectedNum = !empty($expectedRow['occurances']) ? $expectedRow['occurances'] : 1;

            // if there are no occurances for this check, it didn't run
            if (empty($this->_rawResult[$expectedString]['occurances']))
            {
                $this->_ok = false;
                $this->_result[] = $name." didn't run!";
                continue;
            }

            // check expected occurances
            $resultNum = $this->_rawResult[$expectedString]['occurances'];
            if ($expectedNum != $resultNum)
            {
                $this->_ok = false;
                $this->_result[] = $name." was supposed to occure ".$expectedNum."x, but occured ".$resultNum."x!";
            }

            // check error condition
            if (!empty($this->_rawResult[$expectedString]['errors']))
            {
                $this->_ok = false;
                $this->_result[] = $name." had an error!";
            }

            unset($this->_rawResult[$expectedString]);
        }

        if (!empty($this->_rawResult))
        {
            array_push($this->_result, 'Found candidates that are not configured:');
            $this->_log->log(PHP_EOL . 'Found candidates that are not configured:' . PHP_EOL);
            foreach ($this->_rawResult as $name => $rawResult)
            {
                array_push($this->_result, $name . ': ran ' . $rawResult['occurances'] . 'x');
                $this->_log->log($name . ': ran ' . $rawResult['occurances'] . 'x' . PHP_EOL);
            }
        }

        if ($this->_ok)
        {
            $this->_log->log(PHP_EOL . $this->_options['title'] .' results: All OK' . PHP_EOL);
            array_unshift($this->_result, $this->_options['title'] .' results: All OK');
        }
        else
        {
            $this->_log->log(PHP_EOL . $this->_options['title'] .' results: Errors' . PHP_EOL);
            array_unshift($this->_result, $this->_options['title'] .' results: Errors');
        }
    }
}
