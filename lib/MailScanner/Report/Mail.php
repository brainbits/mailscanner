<?php

class MailScanner_Report_Mail implements MailScanner_Report_Interface
{
    /**
     * @var Zend_Mail_Transport_Abstract
     */
    protected $_transport = null;

    /**
     * @var string
     */
    protected $_subject = 'MailScanner result';

    /**
     * @var string
     */
    protected $_fromName = 'MailScanner';

    /**
     * @var string
     */
    protected $_fromEmail = null;

    /**
     * @var array
     */
    protected $_recipients = array();

    /**
     * Constructor
     *
     * @param Zend_Mail_Transport_Abstract $transport
     * @param Zend_Config                  $config
     */
    public function __construct(Zend_Mail_Transport_Abstract $transport,
                                Zend_Config $config = null)
    {
        $this->_transport = $transport;

        if ($config !== null)
        {
            if (isset($config->subject))
            {
                $this->setSubject($config->subject);
            }

            if (isset($config->from))
            {
                $from = $config->from;
                if (isset($from->email))
                {
                    $fromName = null;
                    if (isset($from->name))
                    {
                        $fromName = $from->name;
                    }
                    $this->setFrom($from->email, $fromName);
                }
            }

            if (isset($config->recipients))
            {
                $recipients = $config->recipients;
                foreach ($recipients as $recipient)
                {
                    $this->addRecipient($recipient);
                }
            }
        }
    }

    /**
     * Set subject
     *
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->_subject = $subject;
    }

    /**
     * Set from email and name
     *
     * @param string $fromEmail
     * @param string $fromName  (optional)
     */
    public function setFrom($fromEmail, $fromName = null)
    {
        $this->_fromEmail = $fromEmail;
        $this->_fromName  = $fromName;
    }

    /**
     * Add recipient
     *
     * @param string $recipient
     */
    public function addRecipient($recipient)
    {
        $this->_recipients[$recipient] = $recipient;
    }

    /**
     * Generate report
     *
     * @param string $body
     */
    public function report($body)
    {
        if (!count($this->_recipients))
        {
            throw new Exception('No recipients set.');
        }

        $mail = new Zend_Mail();
        $mail->setFrom($this->_fromEmail, $this->_fromName);
        foreach ($this->_recipients as $recipient)
        {
            $mail->addTo($recipient);
        }
        $mail->setSubject($this->_subject);
        $mail->setBodyText($body);
        $mail->send($this->_transport);
    }
}