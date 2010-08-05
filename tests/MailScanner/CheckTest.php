<?php

require_once '../../lib/MailScanner/Check.php';
require_once '../../lib/MailScanner/Report/Interface.php';
require_once '../../lib/MailScanner/Log/Interface.php';
require_once '../../lib/MailScanner/Module/Interface.php';
require_once 'PHPUnit/Framework/TestCase.php';

date_default_timezone_set('Europe/Berlin');

/**
 * MailScanner_Check test case.
 */
class MailScanner_CheckTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var MailScanner_Check
     */
    protected $_check;

    /**
     * @var MailScanner_Log_Interface
     */
    protected $_logMock = null;

    /**
     * @var MailScanner_Report_Interface
     */
    protected $_reportMock = null;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();

        $this->_logMock = $this->getMock('MailScanner_Log_Interface');
        $this->_reportMock = $this->getMock('MailScanner_Report_Interface');

        $this->_check = new MailScanner_Check($this->_logMock, $this->_reportMock);
    }
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->_check = null;

        $this->_logMock = null;
        $this->_reportMock = null;

        parent::tearDown();
    }

    /**
     * Tests MailScanner_Check->addModule()
     */
    public function testAddModule()
    {
        $moduleMock = $this->getMock('MailScanner_Module_Interface');

        $this->_check->addModule($moduleMock);
    }

    /**
     * Tests MailScanner_Check->run()
     */
    public function testRun()
    {
        $module1Mock = $this->getMock('MailScanner_Module_Interface');
        $module2Mock = $this->getMock('MailScanner_Module_Interface');

        $this->_check->addModule($module1Mock);
        $this->_check->addModule($module2Mock);

        $this->_logMock->expects($this->exactly(2))
                       ->method('log')
                       ->with($this->isType('string'));

        $this->_reportMock->expects($this->once())
                          ->method('report')
                          ->with($this->isType('string'));

        $module1Mock->expects($this->once())
                    ->method('check')
                    ->will($this->returnValue(array('mock1')));

        $module2Mock->expects($this->once())
                    ->method('check')
                    ->will($this->returnValue(array('mock2')));

        $this->_check->run();
    }
}

