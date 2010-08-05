<?php

class MailScanner_Check
{
    /**
     * @var MailScanner_Log_Interface
     */
    protected $_log = null;

    /**
     * @var MailScanner_Report_Interface
     */
    protected $_report = null;

    /**
     * @var array
     */
    protected $_modules = array();

    /**
     * Constructor
     *
     * @param MailScanner_Log $log
     */
    public function __construct(MailScanner_Log_Interface $log,
                                MailScanner_Report_Interface $report)
    {
        $this->_log    = $log;
        $this->_report = $report;
    }

    /**
     * Add scan module
     *
     * @param MailScanner_Module_Interface $module
     */
    public function addModule(MailScanner_Module_Interface $module)
    {
        $this->_modules[] = $module;
    }

    /**
     * Run checks
     */
    public function run()
    {
        $this->_log->log('Starting tests');

        $body = 'Testing started: '.date('Y-m-d H:i:s')."\n\n";

        $errors = array();

        foreach ($this->_modules as $module)
        {
            /* @var $module MaiLScanner_Module_Interface */

            $result = $module->check();

            foreach ($result as $row)
            {
                $body .= $row . "\n";
            }
            $body .= "\n";
        }

        $this->_report->report($body);

        $this->_log->log('Sent mail to: ' . implode(', ', $this->_recipients) . PHP_EOL);
    }
}
