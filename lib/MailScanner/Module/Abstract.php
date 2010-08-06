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
        'action_delete'          => 0,       // don't delete mails
        'action_mark_seen'       => 0,       // don't mark mails seen
        'action_examine'         => 0,       // don't gather mails for further examiniation
        'treshold_examine'       => 86400,   // 1 day
        'treshold_keep'          => 2592000, // 30 days
    );

    /**
     * Simulate flag
     * Don't change any data
     *
     * @var boolean
     */
    protected $_simulate = false;

    /**
     * Result status of this module
     *
     * @var boolean
     */
    protected $_status = true;

    /**
     * Report lines
     *
     * @var array
     */
    protected $_reportLines = array();

    /**
     * Read results buffer
     *
     * @var array
     */
    protected $_readResults = array();

    /**
     * Delete items buffer
     *
     * @var array
     */
    protected $_deleteMsgs = array();

    /**
     * Mark seen items buffer
     *
     * @var array
     */
    protected $_markSeenMsgs = array();

    /**
     * Set simulate flag
     *
     * @param boolean $simulate
     */
    public function setSimulate($simulate = true)
    {
        $this->_simulate = $simulate;
    }

    /**
     * Run check
     *
     * @return boolean
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

        return $this->_status;
    }

    /**
     * Return status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return !!$this->_status;
    }

    /**
     * @return array
     */
    public function getReportLines()
    {
        return $this->_reportLines;
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
        if (isset($config->action->examine))
        {
            $this->_options['action_examine'] = $config->action->examine;
        }
        if (isset($config->tresholds->examine))
        {
            $examineTreshold = (string)$config->tresholds->examine;
            if (!preg_match('/^([0-9]+)([smhd]{0,1})$/', $examineTreshold, $match))
            {
                throw new Exception('Treshold config invalid.');
            }
            if (!empty($match[2]))
            {
                switch ($match[2])
                {
                    case 'm':
                        $examineTreshold *= 60;
                        break;

                    case 'h':
                        $examineTreshold *= 3600;
                        break;

                    case 'd':
                        $examineTreshold *= 86400;
                        break;

                }
            }
            $this->_options['treshold_examine'] = $examineTreshold;
        }
        if (isset($config->tresholds->keep))
        {
            $keepTreshold = (string)$config->tresholds->keep;
            if (!preg_match('/^([0-9]+)([smhd]{0,1})$/', $keepTreshold, $match))
            {
                throw new Exception('Treshold config invalid.');
            }
            if (!empty($match[2]))
            {
                switch ($match[2])
                {
                    case 'm':
                        $keepTreshold *= 60;
                        break;

                    case 'h':
                        $keepTreshold *= 3600;
                        break;

                    case 'd':
                        $keepTreshold *= 86400;
                        break;

                }
            }
            $this->_options['treshold_keep'] = $keepTreshold;
        }

        if ($this->_options['treshold_examine'] < 1)
        {
            //throw new Exception('Treshold for consider days can\'t be smaller then 1');
        }
        if ($this->_options['treshold_keep'] < 1)
        {
            //throw new Exception('Treshold for keep days can\'t be smaller then 1');
        }
    }

    /**
     * Basic init
     */
    protected function _init()
    {
        $this->_log->notice(PHP_EOL . PHP_EOL . $this->_options['title'] . ':' . PHP_EOL);

        $this->_mail->selectFolder($this->_options['folder']);
        $this->_log->notice(PHP_EOL . 'Folder: ' . $this->_options['folder'] . ' (' . $this->_mail->countMessages() . ' mails)' . PHP_EOL);

        $this->_log->info('Actions:  ' . ($this->_options['action_delete'] ? 'delete   ' : '') .
                                         ($this->_options['action_mark_seen'] ? 'mark seen   ' : '') .
                                         ($this->_options['action_examine'] ? 'examine   ' : '') .
                                         PHP_EOL);

        $this->_log->info('Legend:   ' . self::DOT_MISS . ' miss   ' .
                                         self::DOT_SKIP . ' skip   ' .
                                         self::DOT_DELETE . ' delete   ' .
                                         self::DOT_HIT . ' hit   ' .
                                         PHP_EOL);
    }

    /**
     * Read messages
     */
    protected function _readMessages()
    {
        $timeExamine = time() - $this->_options['treshold_examine'];
        $timeKeep    = time() - $this->_options['treshold_keep'];

        $startDate  = date('YmdHis', $timeExamine);
        $deleteDate = date('YmdHis', $timeKeep);

        $this->_log->info('Examining mails after ' . date('Y-m-d H:i:s', $timeExamine) . PHP_EOL);
        $this->_log->info('Deleting mails before ' . date('Y-m-d H:i:s', $timeKeep) . PHP_EOL);
        $this->_log->info(PHP_EOL . 'Reading:' . PHP_EOL);
        $this->_log->startDots();

        foreach ($this->_mail as $msgID => $msg)
        {
            /* @var $msg Zend_Mail_Message */

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

            $patternMatches = array();

            if (isset($this->_config->read->match->all) && $this->_config->read->match->all)
            {
                $patternMatches[] = array(
                    'type'    => 'all',
                    'pattern' => 'all',
                    'subject' => $subject,
                    'content' => $content,
                    'match'   => array(),
                    'status'  => null,
                    'count'   => 1,
                );
            }
            else
            {
                $hasPatterns = false;

                if (isset($this->_config->read->match->subject->pattern))
                {
                    $hasPatterns = true;

                    foreach ($this->_config->read->match->subject->pattern as $patternName => $pattern)
                    {
                        $regex = $pattern->regex;
                        $status = null;
                        if (isset($pattern->status))
                        {
                            $status = $pattern->status;
                        }

                        if (preg_match($regex, $subject, $match))
                        {
                            unset($match[0]);
                            foreach ($match as $key => $value)
                            {
                                if (is_integer($key))
                                {
                                    unset ($match[$key]);
                                }
                            }

                            $patternMatch = array(
                                'type'    => 'subject',
                                'pattern' => $patternName,
                                'subject' => $subject,
                                'content' => $content,
                                'match'   => $match,
                                'status'  => null,
                                'count'   => 1,
                            );

                            if ($status)
                            {
                                $patternMatch['status'] = $status;
                            }

                            if (isset($this->_config->read->match->subject->condition))
                            {
                                foreach ($this->_config->read->match->subject->condition as $condition)
                                {
                                    $conditionKey    = $condition->key;
                                    $conditionString = $condition->string;
                                    $conditionStatus = $condition->status;

                                    if ($conditionString === $match[$conditionKey])
                                    {
                                        $patternMatch['status'] = $conditionStatus;
                                    }
                                }
                            }

                            $patternMatches[] = $patternMatch;

                            break;
                        }
                    }
                }

                if (isset($this->_config->read->match->content->pattern))
                {
                    $hasPatterns = true;

                    foreach ($this->_config->read->match->content->pattern as $patternName => $pattern)
                    {
                        $regex = (string)$pattern->regex;
                        $status = null;
                        if (isset($pattern->status))
                        {
                            $status = $pattern->status;
                        }

                        $patternMatchCount = preg_match_all($regex, $content, $match);
                        if ($patternMatchCount)
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

                            $patternMatch = array(
                                'type'    => 'body',
                                'pattern' => $patternName,
                                'subject' => $subject,
                                'content' => $content,
                                'match'   => $match,
                                'status'  => null,
                                'count'   => $patternMatchCount,
                            );

                            if ($status)
                            {
                                $patternMatch['status'] = $status;
                            }

                            if (isset($this->_config->read->match->subject->condition))
                            {
                                foreach ($this->_config->read->match->subject->condition as $condition)
                                {
                                    $conditionKey    = $condition->key;
                                    $conditionString = $condition->string;
                                    $conditionStatus = $condition->status;

                                    if ($conditionString === $match[$conditionKey])
                                    {
                                        $patternMatch['status'] = $conditionStatus;
                                    }
                                }
                            }

                            $patternMatches[] = $patternMatch;

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
            if (!count($patternMatches))
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

            array_unshift($this->_markSeenMsgs, $msgID);

            // skip messages that are older than startDate
            if (!$this->_options['action_examine'] || $date < $startDate)
            {
                $this->_log->dot(self::DOT_SKIP);
                continue;
            }

            // get fresh copy of message
            //$msg = $this->_mail->getMessage($msgID);

            $this->_log->dot(self::DOT_HIT);

            $readResult = array(
                'date'    => $dummyDate,
                'subject' => $subject,
                'content' => $content,
                'status'  => self::STATUS_SUCCESS,
                'count'   => array(),
                'matches' => array(),
            );

            foreach ($patternMatches as $patternMatch)
            {
                if ($patternMatch['status'] === self::STATUS_ERROR)
                {
                    $readResult['status'] = self::STATUS_ERROR;
                }

                $readResult['count'][$patternMatch['pattern']] = $patternMatch['count'];
                $readResult['matches'] = array_merge($readResult['matches'], $patternMatch['match']);
            }

            $this->_readResults[] = $readResult;
        }

        $this->_log->endDots();

        $this->_log->info(PHP_EOL);
        $this->_log->info(count($this->_deleteMsgs) . ' mail(s) in delete list' . PHP_EOL);
        $this->_log->info(count($this->_markSeenMsgs) . ' mail(s) in mark seen list' . PHP_EOL);
        if ($this->_options['action_examine'])
        {
            $this->_log->info(count($this->_readResults) . ' candidate mail(s)' . PHP_EOL);
        }
        foreach ($this->_readResults as $candidate)
        {
            $this->_log->debug('(' . $candidate['date'] . ') ' . $candidate['subject'] . PHP_EOL);
        }
        $this->_log->info(PHP_EOL);
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
                if (!$this->_simulate)
                {
                    $this->_mail->removeMessage($msgId);
                }
            }

            $this->_log->endDots();
            $this->_log->info(PHP_EOL);
        }
    }
    /**
     * Mark messages seen
     */
    protected function _markMessagesSeen()
    {
        if (!$this->_options['action_mark_seen'])
        {
            return;
        }

        if (sizeof($this->_markSeenMsgs))
        {
            $this->_log->notice(PHP_EOL . 'Marking ' . count($this->_markSeenMsgs) . ' mails seen:' . PHP_EOL);
            $this->_log->startDots();

            foreach ($this->_markSeenMsgs as $msgId)
            {
                $this->_log->dot(self::DOT_HIT);
                if (!$this->_simulate)
                {
                    $msg = $this->_mail->getMail($msgId);

                    // mark seen
                    $flags = $msg->getFlags();
                    if (!in_array(Zend_Mail_Storage::FLAG_SEEN, $flags))
                    {
                        $flags[Zend_Mail_Storage::FLAG_SEEN] = Zend_Mail_Storage::FLAG_SEEN;
                        $this->_mail->setFlags($msgId, $flags);
                    }
                }
            }

            $this->_log->endDots();
            $this->_log->info(PHP_EOL);
        }
    }
}
