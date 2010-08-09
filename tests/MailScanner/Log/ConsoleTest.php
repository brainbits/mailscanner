<?php

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

        $this->_log->info('test');
        $this->_log->close();

        $output = ob_get_clean();

        $this->assertEquals('test', $output);
    }

    /**
     * Tests MailScanner_Log_Console->log()
     */
    public function testMultipleLogCalls ()
    {
        ob_start();

        $this->_log->debug('test1');
        $this->_log->info('test2');
        $this->_log->notice('test3');
        $this->_log->close();

        $output = ob_get_clean();

        $this->assertEquals('test1test2test3', $output);
    }

    /**
     * Tests MailScanner_Log_Console->log()
     */
    public function testMultipleLogCallsWithBreaks ()
    {
        ob_start();

        $this->_log->debug('test1' . PHP_EOL);
        $this->_log->info('test2' . PHP_EOL);
        $this->_log->info('test3' . PHP_EOL);
        $this->_log->close();

        $output = ob_get_clean();

        $this->assertEquals('test1' . PHP_EOL . 'test2' . PHP_EOL . 'test3' . PHP_EOL, $output);
    }
}

