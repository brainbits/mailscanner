<?php

require_once '../../../lib/MailScanner/Log/Interface.php';
require_once '../../../lib/MailScanner/Log/Console.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * MailScanner_Log_Console test case.
 */
class MailScanner_Log_ConsoleTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var MailScanner_Log_Console
     */
    protected $_log;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();

        $this->_log = new MailScanner_Log_Console();
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->_log = null;

        parent::tearDown();
    }

    /**
     * Tests MailScanner_Log_Console->log()
     */
    public function testLog ()
    {
        ob_start();

        $this->_log->log('test');
        $this->_log = null;

        $output = ob_get_clean();

        $this->assertEquals('test', $output);
    }

    /**
     * Tests MailScanner_Log_Console->log()
     */
    public function testMultipleLogCalls ()
    {
        ob_start();

        $this->_log->log('test1');
        $this->_log->log('test2');
        $this->_log->log('test3');
        $this->_log = null;

        $output = ob_get_clean();

        $this->assertEquals('test1test2test3', $output);
    }

    /**
     * Tests MailScanner_Log_Console->log()
     */
    public function testMultipleLogCallsWithBreaks ()
    {
        ob_start();

        $this->_log->log('test1' . PHP_EOL);
        $this->_log->log('test2' . PHP_EOL);
        $this->_log->log('test3' . PHP_EOL);
        $this->_log = null;

        $output = ob_get_clean();

        $this->assertEquals('test1' . PHP_EOL . 'test2' . PHP_EOL . 'test3' . PHP_EOL, $output);
    }
}

