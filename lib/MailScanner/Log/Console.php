<?php

class MailScanner_Log_Console implements MailScanner_Log_Interface
{
    /**
     * @var resource
     */
    protected $_fd;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_fd = fopen('php://stdout', 'w+');
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        fclose ($this->_fd);
    }

    /**
     * Log given string
     *
     * @param string $s
     */
    public function log($s)
    {
        echo $s;
        //fwrite($this->_fd, $s);
    }
}