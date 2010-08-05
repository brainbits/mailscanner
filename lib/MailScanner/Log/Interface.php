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
 * Log interface
 * Concrete log classes have to implement this interface
 *
 * @category  MailScanner
 * @package   MailScanner_Log
 * @author    Stephan Wentz <swentz@brainbits.net>
 * @copyright Copyright (c) 2010 brainbits GmbH (http://www.brainbits.net)
 */
interface MailScanner_Log_Interface
{
    /**
     * Log given string with level debug
     *
     * @param string $s
     */
    public function debug($s);

    /**
     * Log given string with level info
     *
     * @param string $s
     */
    public function info($s);

    /**
     * Log given string with level notice
     *
     * @param string $s
     */
    public function notice($s);

    /**
     * Log given string with level warn
     *
     * @param string $s
     */
    public function warn($s);

    /**
     * Log given string with level error
     *
     * @param string $s
     */
    public function error($s);

    /**
     * Start dot output
     */
    public function startDots();

    /**
     * End dot output
     */
    public function endDots();

    /**
     * Log dot output
     *
     * @param string $dot
     */
    public function dot($dot);
}