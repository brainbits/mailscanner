<?php

/**
 * MailScanner_Report_Console test case.
 */
class MailScanner_Report_ConsoleTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var MailScanner_Report_Console
     */
    protected $_report;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();

        $this->_report = new MailScanner_Report_Console();
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->_report = null;

        parent::tearDown();
    }

    /**
     * Tests MailScanner_Report_Console->report()
     */
    public function testReport ()
    {
        ob_start();

        $this->_report->report('testreport');

        $output = ob_get_clean();

        $expected = 'Generated output:' . PHP_EOL . 'testreport' . PHP_EOL;
        $this->assertEquals($expected, $output);
    }
}

