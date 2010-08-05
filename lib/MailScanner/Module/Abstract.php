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
 * Abstract module class
 * Provides basic functions needed to read and delete mails
 *
 * @category  MailScanner
 * @package   MailScanner_Module
 * @author    Stephan Wentz <swentz@brainbits.net>
 * @copyright Copyright (c) 2010 brainbits GmbH (http://www.brainbits.net)
 * @see       MailScanner_Module_Interface
 */
abstract class MailScanner_Module_Abstract implements MailScanner_Module_Interface
{
    const DOT_MISS   = '#';
    const DOT_HIT    = '+';
    const DOT_DELETE = 'X';
    const DOT_SKIP   = '.';

    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR   = 'error';

    /**
     * Well known config options
     *
     * @var array
     */
    protected $_options = array(
        'title'                  => 'Untitled module',
        'folder'                 => 'INBOX',
        'action_delete'          => 0,
        'action_mark_seen'       => 0,
        'treshold_consider_days' => 1,
        'treshold_keep_days'     => 30,
        'expected'               => array(),
    );

    protected $_ok = true;
    protected $_result = array();
    protected $_rawResult = array();
    protected $_deleteMsgs = array();

    /**
     * Run check
     */
    public function check()
    {
        $methods = get_class_methods($this);

        foreach ($methods as $method)
        {
            if (!preg_match('/^_do[A-Z][a-z]+/', $method))
            {
                continue;
            }

            $this->$method();
        }

        return $this->_result;
    }

    /**
     * @return boolean
     */
    public function isOk()
    {
        return $this->_ok;
    }

    /**
     * @return array
     */
    public function getResult()
    {
        return $this->_result;
    }

    /**
     * Parse well known config options
     *
     * @param Zend_Config $config
     */
    protected function _parseConfig(Zend_Config $config)
    {
        if (!isset($config->folder))
        {
            throw new Exception('folder config not set');
        }

        $this->_options['folder'] = $config->folder;

        if (isset($config->title))
        {
            $this->_options['title'] = $config->title;
        }
        if (isset($config->action->delete))
        {
            $this->_options['action_delete'] = $config->action->delete;
        }
        if (isset($config->action->mark_seen))
        {
            $this->_options['action_mark_seen'] = $config->action->mark_seen;
        }
        if (isset($config->tresholds->consider))
        {
            $this->_options['treshold_consider_days'] = $config->tresholds->consider_days;
        }
        if (isset($config->tresholds->keep))
        {
            $this->_options['treshold_keep_days'] = $config->tresholds->keep_days;
        }
        if (isset($config->check))
        {
            $this->_options['check'] = $config->check->toArray();
        }

        if ($this->_options['treshold_consider_days'] < 1)
        {
            throw new Exception('Treshold for consider days can\'t be smaller then 1');
        }
        if ($this->_options['treshold_keep_days'] < 1)
        {
            throw new Exception('Treshold for keep days can\'t be smaller then 1');
        }
    }

    /**
     * Basic init
     */
    protected function _init()
    {
        $this->_log->notice(PHP_EOL . PHP_EOL . $this->_options['title'] . ':' . PHP_EOL);
        $this->_log->notice(PHP_EOL . 'Folder: ' . $this->_options['folder'] . PHP_EOL);

        $this->_mail->selectFolder($this->_options['folder']);

        if ($this->_options['action_delete'])
        {
            $this->_log->info('Delete enabled' . PHP_EOL);
        }

        if ($this->_options['action_mark_seen'])
        {
            $this->_log->info('Mark seen enabled' . PHP_EOL);
        }

        $this->_log->info('Examining ' . $this->_mail->countMessages() . ' mails' . PHP_EOL);
        $this->_log->info('Legend:   ' . self::DOT_MISS . ' miss   ' .
                                         self::DOT_SKIP . ' skip   ' .
                                         self::DOT_DELETE . ' delete   ' .
                                         self::DOT_HIT . ' hit' .
                                         PHP_EOL);
    }

    /**
     * Read messages
     */
    protected function _readMessages()
    {
        $tresholdConsider = $this->_options['treshold_consider_days'] * 24 * 60 * 60;
        $tresholdKeep     = $this->_options['treshold_keep_days'] * 24 * 60 * 60;

        $timeConsider = time() - $tresholdConsider;
        $timeKeep     = time() - $tresholdKeep;

        $startDate  = date('YmdHis', $timeConsider);
        $deleteDate = date('YmdHis', $timeKeep);

        $this->_log->info('Considering mails after ' . date('Y-m-d H:i:s', $timeConsider) . PHP_EOL);
        $this->_log->info('Deleting mails before ' . date('Y-m-d H:i:s', $timeKeep) . PHP_EOL);

        $candidates = array();

        $this->_log->info(PHP_EOL . 'Reading:' . PHP_EOL);

        $this->_log->startDots();

        foreach ($this->_mail as $msgID => $msg)
        {
            /* @var $msg Zend_Mail_Message */

            try
            {
                $dummyDate = $msg->date;
                if (substr($dummyDate, -2) === 'UT')
                {
                    $dummyDate = str_replace('UT', '+0000', $dummyDate);
                }

                $date = date('YmdHis', strtotime($dummyDate));

                $subject = $msg->subject;
                if ($msg->isMultiPart())
                {
                    $content = $msg->getPart(1)->getContent();
                }
                else
                {
                    $content = $msg->getContent();
                }

                $hit = false;

                if (isset($this->_config->match->all) && $this->_config->match->all)
                {
                    $hit = true;

                    $result = array(
                        'pattern' => '_all_',
                        'subject' => $subject,
                        'content' => $content,
                        'match'   => array(),
                    );
                }
                else
                {
                    $hasPatterns = false;

                    if (isset($this->_config->match->subject->pattern))
                    {
                        $hasPatterns = true;

                        foreach ($this->_config->match->subject->pattern as $patternName => $pattern)
                        {
                            $regex = $pattern->regex;
                            $status = null;
                            if (isset($pattern->status))
                            {
                                $status = $pattern->status;
                            }

                            if (preg_match($regex, $subject, $match))
                            {
                                $hit = true;

                                unset($match[0]);
                                foreach ($match as $key => $value)
                                {
                                    if (is_integer($key))
                                    {
                                        unset ($match[$key]);
                                    }
                                }

                                $result = array(
                                    'pattern' => $patternName,
                                    'subject' => $subject,
                                    'content' => $content,
                                    'match'   => $match,
                                    'status'  => null,
                                );

                                if ($status)
                                {
                                    $result['status'] = $status;
                                }

                                if (isset($this->_config->match->subject->condition))
                                {
                                    foreach ($this->_config->match->subject->condition as $conditionKey => $condition)
                                    {
                                        if ($condition->string === $match[$conditionKey])
                                        {
                                            $result['status'] = $condition->status;
                                        }
                                    }
                                }

                                break;
                            }
                        }
                    }

                    if (!$hasPatterns)
                    {
                        throw new Exception('No patterns found.');
                    }
                }

                // if no pattern has matched, skip
                if (!$hit)
                {
                    $this->_log->dot(self::DOT_MISS);
                    continue;
                }

                // check for old messages, delete if necessary
                if ($date < $deleteDate)
                {
                    array_unshift($this->_deleteMsgs, $msgID);
                    $this->_log->dot(self::DOT_DELETE);
                    continue;
                }

                if ($this->_options['action_mark_seen'])
                {
                    // mark seen
                    $flags = $msg->getFlags();
                    if (!in_array(Zend_Mail_Storage::FLAG_SEEN, $flags))
                    {
                        $flags[Zend_Mail_Storage::FLAG_SEEN] = Zend_Mail_Storage::FLAG_SEEN;
                        $this->_mail->setFlags($msgID, $flags);
                    }
                }

                // skip messages that are older than startDate
                if ($date < $startDate)
                {
                    $this->_log->dot(self::DOT_SKIP);
                    continue;
                }

                // get fresh copy of message
                //$msg = $this->_mail->getMessage($msgID);

                $this->_log->dot(self::DOT_HIT);
                $this->_rawResult[] = $result;

                $candidates[] = $dummyDate;
            }
            catch (Exception $e)
            {
            }
        }

        $this->_log->endDots(PHP_EOL);

        $this->_log->debug(PHP_EOL . 'Candidates:' . PHP_EOL);
        foreach ($candidates as $candidate)
        {
            $this->_log->debug($candidate . PHP_EOL);
        }
    }

    /**
     * Delete messages
     */
    protected function _deleteMessages()
    {
        if (!$this->_options['action_delete'])
        {
            return;
        }

        if (sizeof($this->_deleteMsgs))
        {
            $this->_log->notice(PHP_EOL . 'Deleting ' . count($this->_deleteMsgs) . ' old mails:' . PHP_EOL);
            $this->_log->startDots();

            foreach ($this->_deleteMsgs as $msgId)
            {
                $this->_log->dot(self::DOT_DELETE);
                $this->_mail->removeMessage($msgId);
            }

            $this->_log->endDots();
        }
    }
}
