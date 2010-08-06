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
 * @package   MailScanner_Log
 * @copyright Copyright (c) 2010 brainbits GmbH (http://www.brainbits.net)
 */

/**
 * Console log
 *
 * Writes all log messages to stdout, message with level error will be
 * written to stderr.
 *
 * @category  MailScanner
 * @package   MailScanner_Log
 * @author    Stephan Wentz <swentz@brainbits.net>
 * @copyright Copyright (c) 2010 brainbits GmbH (http://www.brainbits.net)
 * @see       MailScanner_Report_Interface
 */
class MailScanner_Log_Console implements MailScanner_Log_Interface
{
    /**
     * @var resource
     */
    protected $_stdout;

    /**
     * @var resource
     */
    protected $_stderr;

    /**
     * @var integer
     */
    protected $_dotCount = 0;

    /**
     * @var integer
     */
    protected $_dotLimit = 70;

    /**
     * Constructor
     */
    public function __construct($dotLimit = 70)
    {
        $this->_stdout = fopen('php://stdout', 'w');
        $this->_stderr = fopen('php://stderr', 'w');

        $this->_dotLimit = 79;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        fclose ($this->_stdout);
        fclose ($this->_stderr);
    }

    /**
     * Log given string with level debug
     *
     * @param string $s
     */
    public function debug($s)
    {
        fwrite($this->_stdout, $s);
    }

    /**
     * Log given string with level info
     *
     * @param string $s
     */
    public function info($s)
    {
        fwrite($this->_stdout, $s);
    }

    /**
     * Log given string with level notice
     *
     * @param string $s
     */
    public function notice($s)
    {
        fwrite($this->_stdout, $s);
    }

    /**
     * Log given string with level warn
     *
     * @param string $s
     */
    public function warn($s)
    {
        fwrite($this->_stderr, $s);
    }

    /**
     * Log given string with level error
     *
     * @param string $s
     */
    public function error($s)
    {
        fwrite($this->_stderr, $s);
    }

    /**
     * Start dot output
     */
    public function startDots()
    {
        $this->_dotCount = 0;
    }

    /**
     * End dot output
     */
    public function endDots()
    {
        $this->info(PHP_EOL);
    }

    /**
     * Log dot output
     *
     * @param string $dot
     */
    public function dot($dot)
    {
        if ($this->_dotCount >= $this->_dotLimit)
        {
            $this->endDots();
            $this->startDots();
        }

        $this->_dotCount++;

        $this->info($dot);
    }
}