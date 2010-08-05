<?php

class MailScanner_Module_SpamCounter extends MailScanner_Module_Abstract
{
    protected $_mail = null;
    protected $_check = array();

    protected $_startSpan  = 1;
    protected $_deleteSpan = 30;

    /**
     * Constructor
     *
     * @param Zend_Mail_Storage_Imap $mail
     * @param array                  $expected
     */
    public function __construct(Zend_Mail_Storage_Imap $mail, array $check)
    {
        $this->_mail = $mail;
        $this->_check = $check;
    }

    protected function _read($verbose = false)
    {
        $this->_mail->selectFolder('INBOX/spamcounter');

        if ($verbose) echo "\n\nSpam Counter:\n";
        if ($verbose) echo "\nConsidering ".$this->_mail->countMessages()." mails:\n";

        $deleteDate = date('YmdHis', time() - ($this->_deleteSpan * 60 * 60 * 24));
        $startDate = date('YmdHis', time() - ($this->_startSpan * 60 * 60 * 24));
        if ($verbose > 1) echo "Starting from ".$startDate."\n";

        foreach ($this->_mail as $msgID => $msg)
        {
            $dummyDate = $msg->date;
            if (substr($dummyDate, -2)=='UT')
            {
                $dummyDate = str_replace('UT', '+0000', $dummyDate);
            }

            $date = date('YmdHis', strtotime($dummyDate));

            // check for old messages, delete if necessary
            if ($date < $deleteDate)
            {
                array_unshift($this->_deleteMsgs, $msgID);
                if($verbose) echo 'X';
                continue;
            }

            // check for messages that need to be checked
            if ($date < $startDate)
            {
                if($verbose) echo '.';
                continue;
            }

            // get fresh copy of message
            $msg = $this->_mail->getMessage($msgID);
            if ($verbose) echo '+';

            $subject = $msg->subject;
            if (preg_match('/^(.*) Spamcounter$/', $subject, $match))
            {
                $host = $match[1];
            }
            else
            {
                continue;
            }

            if (empty($this->_rawResult[$host]))
            {
                $this->_rawResult[$host] = array(
                );
            }

            if ($msg->isMultiPart())
            {
                $msg = $msg->getPart(1);
            }

            $body = $msg->getContent();

            if (preg_match_all('/(\w+):\n\s*(\d+)\n/', $body, $match))
            {
                foreach ($match[1] as $key => $type)
                {
                    $this->_rawResult[$host][$type] = $match[2][$key];
                }
            }

            if($verbose > 1) echo "\n".$host.": (".$dummyDate.")";
        }

        if ($verbose) echo "\n";
    }

    protected function _delete($verbose = false)
    {
        if (sizeof($this->_deleteMsgs))
        {
            if ($verbose) echo "\nDeleting (".count($this->_deleteMsgs).") old mails:\n";

            foreach ($this->_deleteMsgs as $msgID)
            {
                if($verbose) echo 'X';
                $this->_mail->removeMessage($msgID);
            }

            if ($verbose) echo "\n";
        }
    }

    protected function _analyze($verbose = false)
    {
        if ($verbose) echo "\nAnalyzing results...\n";

        foreach ($this->_check as $host => $expectedArray)
        {
            if (empty($this->_rawResult[$host]))
            {
                $this->_ok = false;
                $this->_result[] = $host." didn't run!";
                $this->_errors[] = '(spam) '.$host." norun";
                continue;
            }

            $line = '';
            foreach ($this->_rawResult[$host] as $type => $num)
            {
                $line .= ($line ? ', ' : '') . $type . ': ' . $num;
            }

            $this->_result[] = '[' . $host . '] ' . $line;
        }

        array_unshift($this->_result, 'SpamCounter:');
    }
}
