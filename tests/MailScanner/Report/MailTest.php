<?php

require_once '../../../lib/MailScanner/Report/Interface.php';
require_once '../../../lib/MailScanner/Report/Mail.php';
require_once 'PHPUnit/Framework/TestCase.php';

// stupid, but needed since we don't bundle ZF
class Zend_Mail
{
    public function __call($key, $params) {}
}
class Zend_Config implements Iterator
{
    public function __isset($key) {}
    public function __get($key) {}
    public function rewind() {}
    public function current() {}
    public function key() {}
    public function next() {}
    public function valid() {}
}

/**
 * MailScanner_Report_Console test case.
 */
class MailScanner_Report_MailTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tests MailScanner_Report_Console->report()
     */
    public function testReportEmptyConfig ()
    {
        $transportMock = $this->getMock('Zend_Mail_Transport_Abstract');

        $report = new MailScanner_Report_Mail($transportMock);

        $this->assertAttributeEquals('MailScanner result', '_subject', $report);
        $this->assertAttributeEquals(null, '_fromEmail', $report);
        $this->assertAttributeEquals('MailScanner', '_fromName', $report);
        $this->assertAttributeEquals(array(), '_recipients', $report);
    }

    /**
     * Tests MailScanner_Report_Console->report()
     */
    public function testReportConfigNotSet ()
    {
        $transportMock = $this->getMock('Zend_Mail_Transport_Abstract');
        $configMock = $this->getMock('Zend_Config', array('__isset', '__get'), array(), '', false);

        $configMock->expects($this->at(0))
                   ->method('__isset')
                   ->with('subject')
                   ->will($this->returnValue(false));

        $configMock->expects($this->at(1))
                   ->method('__isset')
                   ->with('from')
                   ->will($this->returnValue(false));

        $configMock->expects($this->at(2))
                   ->method('__isset')
                   ->with('recipients')
                   ->will($this->returnValue(false));

        $report = new MailScanner_Report_Mail($transportMock, $configMock);

        $this->assertAttributeEquals('MailScanner result', '_subject', $report);
        $this->assertAttributeEquals(null, '_fromEmail', $report);
        $this->assertAttributeEquals('MailScanner', '_fromName', $report);
        $this->assertAttributeEquals(array(), '_recipients', $report);
    }

    /**
     * Tests MailScanner_Report_Console->report()
     */
    public function testReportConfigSubject ()
    {
        $transportMock = $this->getMock('Zend_Mail_Transport_Abstract');
        $configMock = $this->getMock('Zend_Config');

        $configMock->expects($this->at(0))
                   ->method('__isset')
                   ->with('subject')
                   ->will($this->returnValue(true));

        $configMock->expects($this->at(1))
                   ->method('__get')
                   ->with('subject')
                   ->will($this->returnValue('testSubject'));

        $report = new MailScanner_Report_Mail($transportMock, $configMock);

        $this->assertAttributeEquals('testSubject', '_subject', $report);
    }

    /**
     * Tests MailScanner_Report_Console->report()
     */
    public function testReportConfigFromWithoutName ()
    {
        $transportMock = $this->getMock('Zend_Mail_Transport_Abstract');
        $configMock = $this->getMock('Zend_Config');
        $fromConfigMock = $this->getMock('Zend_Config');

        $configMock->expects($this->at(0))
            ->method('__isset')
            ->with('subject')
            ->will($this->returnValue(false));

        $configMock->expects($this->at(1))
            ->method('__isset')
            ->with('from')
            ->will($this->returnValue(true));

        $configMock->expects($this->at(2))
            ->method('__get')
            ->with('from')
            ->will($this->returnValue($fromConfigMock));

        $fromConfigMock->expects($this->at(0))
            ->method('__isset')
            ->with('email')
            ->will($this->returnValue(true));

        $fromConfigMock->expects($this->at(1))
            ->method('__isset')
            ->with('name')
            ->will($this->returnValue(false));

        $fromConfigMock->expects($this->at(2))
            ->method('__get')
            ->with('email')
            ->will($this->returnValue('test@test.com'));

        $report = new MailScanner_Report_Mail($transportMock, $configMock);

        $this->assertAttributeEquals('test@test.com', '_fromEmail', $report);
    }

    /**
     * Tests MailScanner_Report_Console->report()
     */
    public function testReportConfigFromWithName ()
    {
        $transportMock = $this->getMock('Zend_Mail_Transport_Abstract');
        $configMock = $this->getMock('Zend_Config');
        $fromConfigMock = $this->getMock('Zend_Config');

        $configMock->expects($this->at(0))
            ->method('__isset')
            ->with('subject')
            ->will($this->returnValue(false));

        $configMock->expects($this->at(1))
            ->method('__isset')
            ->with('from')
            ->will($this->returnValue(true));

        $configMock->expects($this->at(2))
            ->method('__get')
            ->with('from')
            ->will($this->returnValue($fromConfigMock));

        $fromConfigMock->expects($this->at(0))
            ->method('__isset')
            ->with('email')
            ->will($this->returnValue(true));

        $fromConfigMock->expects($this->at(1))
            ->method('__isset')
            ->with('name')
            ->will($this->returnValue(true));

        $fromConfigMock->expects($this->at(2))
            ->method('__get')
            ->with('name')
            ->will($this->returnValue('testname'));

        $fromConfigMock->expects($this->at(3))
            ->method('__get')
            ->with('email')
            ->will($this->returnValue('test@test.com'));

        $report = new MailScanner_Report_Mail($transportMock, $configMock);

        $this->assertAttributeEquals('test@test.com', '_fromEmail', $report);
        $this->assertAttributeEquals('testname', '_fromName', $report);
    }

    /**
     * Tests MailScanner_Report_Console->report()
     */
    public function testReportConfigRecipients ()
    {
        $transportMock = $this->getMock('Zend_Mail_Transport_Abstract');
        $configMock = $this->getMock('Zend_Config');
        $recipientsConfigMock = $this->getMock('Zend_Config');

        $configMock->expects($this->at(0))
            ->method('__isset')
            ->with('subject')
            ->will($this->returnValue(false));

        $configMock->expects($this->at(1))
            ->method('__isset')
            ->with('from')
            ->will($this->returnValue(false));

        $configMock->expects($this->at(2))
            ->method('__isset')
            ->with('recipients')
            ->will($this->returnValue(true));

        $configMock->expects($this->at(3))
            ->method('__get')
            ->with('recipients')
            ->will($this->returnValue($recipientsConfigMock));

        $recipientsConfigMock->expects($this->at(0))
            ->method('rewind');

        $recipientsConfigMock->expects($this->at(1))
            ->method('valid')
            ->will($this->returnValue(true));

        $recipientsConfigMock->expects($this->at(2))
            ->method('current')
            ->will($this->returnValue('test@test.com'));

        $report = new MailScanner_Report_Mail($transportMock, $configMock);

        $this->assertAttributeEquals(array('test@test.com' => 'test@test.com'), '_recipients', $report);
    }

    /**
     * Tests MailScanner_Report_Console->report()
     *
     * @expectedException Exception
     */
    public function testReportWithoutRecipients ()
    {
        $transportMock = $this->getMock('Zend_Mail_Transport_Abstract');
        $configMock = $this->getMock('Zend_Config');

        $report = new MailScanner_Report_Mail($transportMock, $configMock);

        $report->report('testReport');
    }
    /**
     * Tests MailScanner_Report_Console->report()
     */
    public function testReport ()
    {
        $transportMock = $this->getMock('Zend_Mail_Transport_Abstract');
        $configMock = $this->getMock('Zend_Config');

        $report = new MailScanner_Report_Mail($transportMock, $configMock);
        $report->addRecipient('test@test.com');

        $report->report('testReport');
    }
}

