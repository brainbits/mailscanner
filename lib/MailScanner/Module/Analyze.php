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
 * Analyze module class
 * Analyzes mails by rule sets, creates a success/fail report
 *
 * @category  MailScanner
 * @package   MailScanner_Module
 * @author    Stephan Wentz <swentz@brainbits.net>
 * @copyright Copyright (c) 2010 brainbits GmbH (http://www.brainbits.net)
 * @see       MailScanner_Module_Abstract
 */
class MailScanner_Module_Analyze extends MailScanner_Module_Abstract
{
    /**
     * @var Zend_Mail_Storage_Imap
     */
    protected $_mail = null;

    /**
     * @var MailScanner_Log_Interface
     */
    protected $_log = null;

    /**
     * @var Zend_Config
     */
    protected $_config = null;

    /**
     * @var array
     */
    protected $_mappedResults = array();

    /**
     * Constructor
     *
     * @param Zend_Mail_Storage_Imap    $mail
     * @param MailScanner_Log_Interface $log
     * @param Zend_Config               $config
     */
    public function __construct(Zend_Mail_Storage_Imap $mail, MailScanner_Log_Interface $log, Zend_Config $config)
    {
        $this->_parseConfig($config);

        $this->_mail = $mail;
        $this->_config = $config;
        $this->_log = $log;
    }

    /**
     * Init phase
     */
    protected function _doInit()
    {
        $this->_init();
    }

    /**
     * Read phase
     */
    protected function _doRead()
    {
        $this->_readMessages();
    }

    /**
     * Delete phase
     */
    protected function _doDelete()
    {
        $this->_deleteMessages();
    }

    /**
     * Map phase
     */
    protected function _doMap()
    {
        $readResults   = $this->_readResults;
        $mappedResults = array();

        if (!isset($this->_config->map->key))
        {
            throw new Exception('No map key defined.');
        }

        $key = $this->_config->map->key;

        foreach ($readResults as $row)
        {
            if (!isset($row['matches'][$key]))
            {
                throw new Exception('Key "'.$key.'" not found in read result row.');
            }

            $matchString = $row['matches'][$key];

            if (empty($mappedResults[$matchString]))
            {
                $mappedResults[$matchString] = array(
                    'occurances' => 0,
                    'errors'     => 0,
                    'count'      => array(),
                );
            }

            $mappedResults[$matchString]['occurances']++;

            if ($row['status'] !== self::STATUS_SUCCESS)
            {
                $mappedResults[$matchString]['errors']++;
            }

            foreach ($row['count'] as $countKey => $countValue)
            {
                $mappedResults[$matchString]['count'][$countKey][] = $countValue;
            }
        }

        $this->_mappedResults = $mappedResults;
    }

    /**
     * Analyze phase
     */
    protected function _doAnalyze()
    {
        $this->_log->info(PHP_EOL . 'Analyzing results...' . PHP_EOL);

        foreach ($this->_config->analyze as $name => $analyzeRow)
        {
            $expectedString = $analyzeRow->expected;
            $expectedNum    = $analyzeRow->get('occurances', 1);

            // if there are no occurances for this check, it didn't run
            if (empty($this->_mappedResults[$expectedString]['occurances']))
            {
                $this->_status = false;
                $this->_log->warn($name . ' didn\'t run!' . PHP_EOL);
                $this->_reportLines[] = $name . ' didn\'t run!';
                continue;
            }

            // check expected occurances
            $resultNum = $this->_mappedResults[$expectedString]['occurances'];
            if ($expectedNum != $resultNum)
            {
                $this->_status = false;
                $this->_log->warn($name . ' was supposed to occure ' . $expectedNum . 'x, but occured ' . $resultNum . 'x!' . PHP_EOL);
                $this->_reportLines[] = $name . ' was supposed to occure ' . $expectedNum . 'x, but occured ' . $resultNum . 'x!';
            }

            // check error condition
            if (!empty($this->_mappedResults[$expectedString]['errors']))
            {
                $this->_status = false;
                $this->_log->warn($name . ' had an error!' . PHP_EOL);
                $this->_reportLines[] = $name . ' had an error!';
            }

            if (isset($analyzeRow->count))
            {
                foreach ($analyzeRow->count as $countKey => $countExpectedNum)
                {
                    if (empty($this->_mappedResults[$expectedString]['count'][$countKey]))
                    {
                        $this->_status = false;
                        $this->_log->warn('Count key ' . $countKey.' was expected, but not found!' . PHP_EOL);
                        $this->_reportLines[] = 'Count key ' . $countKey.' was expected, but not found!';
                        continue;
                    }

                    foreach ($this->_mappedResults[$expectedString]['count'][$countKey] as $countNum)
                    {
                        if ($countExpectedNum != $countNum)
                        {
                            $this->_status = false;
                            $this->_log->warn('Count key ' . $countKey.' was supposed to be found '.$countExpectedNum.'x, but was found '.$countNum.'x!' . PHP_EOL);
                            $this->_reportLines[] = 'Count key ' . $countKey.' was supposed to be found '.$countExpectedNum.'x, but was found '.$countNum.'x!';
                            continue;
                        }
                    }
                }
            }

            unset($this->_mappedResults[$expectedString]);
        }

        if (!empty($this->_mappedResults))
        {
            array_push($this->_reportLines, 'Found candidates that are not configured:');
            $this->_log->notice(PHP_EOL . 'Found candidates that are not configured:' . PHP_EOL);
            foreach ($this->_readResults as $name => $readResult)
            {
                array_push($this->_reportLines, $name . ': ran ' . $readResult['occurances'] . 'x');
                $this->_log->notice($name . ': ran ' . $readResult['occurances'] . 'x' . PHP_EOL);
            }
        }

        if ($this->_status)
        {
            $this->_log->notice(PHP_EOL . $this->_options['title'] .' results: All OK' . PHP_EOL);
            array_unshift($this->_reportLines, $this->_options['title'] .' results: All OK');
        }
        else
        {
            $this->_log->notice(PHP_EOL . $this->_options['title'] .' results: Errors' . PHP_EOL);
            array_unshift($this->_reportLines, $this->_options['title'] .' results: Errors');
        }
    }
}
