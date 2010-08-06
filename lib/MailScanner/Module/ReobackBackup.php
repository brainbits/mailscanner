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
        $readResults = $this->_readResults;
        $this->_readResults = array();

        foreach ($readResults as $result)
        {
            $host = $result['match']['host'];

            if (empty($this->_readResults[$host]))
            {
                $this->_readResults[$host] = array(
                    'occurances' => 0,
                    'sets'       => 0
                );
            }
            $this->_readResults[$host]['occurances']++;

            $body = $result['content'];
            if(preg_match_all('/tgz\.\.\.done\./', $body, $match))
            {
                $this->_readResults[$host]['sets'] = count($match[0]);
            }
        }
    }

    protected function _doAnalyze()
    {
        $this->_log->info(PHP_EOL . 'Analyzing results...' . PHP_EOL);

        foreach ($this->_options['check'] as $name => $expectedRow)
        {
            $expectedString = $expectedRow['expected'];
            $expectedNum    = $expectedRow['occurances'];
            $expectedSets   = $expectedRow['sets'];

            if (empty($this->_readResults[$expectedString]))
            {
                $this->_status = false;
                $this->_reportLines[] = $name." didn't run!";
                continue;
            }

            $resultNum = $this->_readResults[$expectedString]['occurances'];
            if ($expectedNum != $resultNum)
            {
                $this->_status = false;
                $this->_reportLines[] = $name." was supposed to run ".$expectedNum."x, but ran ".$resultNum."x!";
            }
            $resultSets = $this->_readResults[$expectedString]['sets'];
            if ($expectedSets != $resultSets)
            {
                $this->_status = false;
                $this->_reportLines[] = $name." was supposed to backup ".$expectedSets." sets, but did ".$resultSets." sets!";
            }

            unset($this->_readResults[$expectedString]);
        }

        if (!empty($this->_readResults))
        {
            array_push($this->_reportLines, 'Found candidates that are not configured:');
            $this->_log->notice(PHP_EOL . 'Found candidates that are not configured:' . PHP_EOL);
            foreach ($this->_readResults as $name => $readResult)
            {
                array_push($this->_reportLines, $name . ': ran ' . $readResult['occurances'] . 'x');
                $this->_log->notice($name . ': ran ' . $readResult['occurances'] . 'x' . PHP_EOL);
            }
        }

        if ($this->_status)
        {
            $this->_log->notice(PHP_EOL . 'Reoback Backup Results: All OK' . PHP_EOL);
            array_unshift($this->_reportLines, 'Reoback Backup Results: All OK');
        }
        else
        {
            $this->_log->notice(PHP_EOL . 'Reoback Backup Results: Errors' . PHP_EOL);
            array_unshift($this->_reportLines, 'Reoback Backup Results: Errors');
        }
    }
}
