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
 * @package   MailScanner_Report
 * @copyright Copyright (c) 2010 brainbits GmbH (http://www.brainbits.net)
 */

/**
 * Console report
 *
 * Writes report to stdout
 *
 * @category  MailScanner
 * @package   MailScanner_Report
 * @author    Stephan Wentz <swentz@brainbits.net>
 * @copyright Copyright (c) 2010 brainbits GmbH (http://www.brainbits.net)
 * @see       MailScanner_Report_Interface
 */
class MailScanner_Report_Console implements MailScanner_Report_Interface
{
    /**
     * Generate report
     *
     * @param string $body
     */
    public function report($body)
    {
        echo PHP_EOL . 'Generated output:' . PHP_EOL;
        echo $body . PHP_EOL;
    }
}