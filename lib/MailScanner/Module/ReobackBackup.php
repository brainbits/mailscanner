<?php

class MailScanner_Module_ReobackBackup extends MailScanner_Module_Abstract
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

        $this->_mail   = $mail;
        $this->_config = $config;
        $this->_log    = $log;
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
        $this->_deleteMessages($this->_deleteMsgs);
    }

    protected function _doPrepare()
    {
        $results = $this->_rawResult;
        $this->_rawResult = array();

        foreach ($results as $row)
        {
            $host   = $row['match']['host'];

            if (empty($this->_rawResult[$host]))
            {
                $this->_rawResult[$host] = array(
                    'occurances' => 0,
                    'sets'       => 0
                );
            }
            $this->_rawResult[$host]['occurances']++;

            $body = $row['content'];
            if(preg_match_all('/tgz\.\.\.done\./', $body, $match))
            {
                $this->_rawResult[$host]['sets'] = count($match[0]);
            }
        }
    }

    protected function _doAnalyze()
    {
        $this->_log->log(PHP_EOL . 'Analyzing results...' . PHP_EOL);

        foreach ($this->_options['check'] as $name => $expectedRow)
        {
            $expectedString = $expectedRow['expected'];
            $expectedNum    = $expectedRow['occurances'];
            $expectedSets   = $expectedRow['sets'];

            if (empty($this->_rawResult[$expectedString]))
            {
                $this->_ok = false;
                $this->_result[] = $name." didn't run!";
                $this->_errors[] = '(reo) '.$name." norun";
                continue;
            }

            $resultNum = $this->_rawResult[$expectedString]['occurances'];
            if ($expectedNum != $resultNum)
            {
                $this->_ok = false;
                $this->_result[] = $name." was supposed to run ".$expectedNum."x, but ran ".$resultNum."x!";
            }
            $resultSets = $this->_rawResult[$expectedString]['sets'];
            if ($expectedSets != $resultSets)
            {
                $this->_ok = false;
                $this->_result[] = $name." was supposed to backup ".$expectedSets." sets, but did ".$resultSets." sets!";
            }

            if ($expectedNum != $resultNum || $expectedSets != $resultSets)
            {
                $this->_errors[] = '(reo) '.$name." error";
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
            $this->_log->log(PHP_EOL . 'Reoback Backup Results: All OK' . PHP_EOL);
            array_unshift($this->_result, 'Reoback Backup Results: All OK');
        }
        else
        {
            $this->_log->log(PHP_EOL . 'Reoback Backup Results: Errors' . PHP_EOL);
            array_unshift($this->_result, 'Reoback Backup Results: Errors');
        }
    }
}
