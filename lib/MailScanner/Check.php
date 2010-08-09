<?php
/**
 * MailScanner
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to tsmckelvey@gmail.com so I can send you a copy immediately.
 *
 * @category  MailScanner
 * @package   MailScanner_Module
 * @copyright Copyright (c) 2010 brainbits GmbH (http://www.brainbits.net)
 */

/**
 * Main check class
 * Uses modules, report and log
 *
 * @category  MailScanner
 * @package   MailScanner_Module
 * @author    Stephan Wentz <swentz@brainbits.net>
 * @copyright Copyright (c) 2010 brainbits GmbH (http://www.brainbits.net)
 */
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
     *
     * @param boolean $simulate
     */
    public function run($simulate = false)
    {
        $this->_log->info('Testing started: '.date('Y-m-d H:i:s') . PHP_EOL);
        $body = 'Testing started: '.date('Y-m-d H:i:s')."\n\n";

        $errors = array();

        foreach ($this->_modules as $module)
        {
            /* @var $module MaiLScanner_Module_Interface */

            $module->setSimulate($simulate);

            $status = $module->check();

            $reportLines = $module->getReportLines();
            $body .= implode("\n", $reportLines) . "\n";
        }

        $this->_report->report($body);
    }
}
