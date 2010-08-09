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
 * Module interface
 * Concrete module classes have to implement this interface
 *
 * @category  MailScanner
 * @package   MailScanner_Module
 * @author    Stephan Wentz <swentz@brainbits.net>
 * @copyright Copyright (c) 2010 brainbits GmbH (http://www.brainbits.net)
 */
interface MailScanner_Module_Interface
{
    /**
     * Set simulate flag
     *
     * @param boolean $simulate
     */
    public function setSimulate($simulate = true);

    /**
     * Run check
     *
     * @return boolean
     */
    public function check();

    /**
     * Return status
     *
     * @return boolean
     */
    public function getStatus();

    /**
     * Return report lines
     *
     * @return array
     */
    public function getReportLines();
}
