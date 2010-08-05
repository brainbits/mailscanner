<?php

class MailScanner_Module_Delete extends MailScanner_Module_Abstract
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
        $this->_deleteMessages($this->_deleteMsgs);
    }
}
