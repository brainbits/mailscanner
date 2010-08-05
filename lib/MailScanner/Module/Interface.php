<?php

interface MailScanner_Module_Interface
{
    /**
     * Run check
     */
    public function check();

    /**
     * @return boolean
     */
    public function isOk();

    /**
     * @return array
     */
    public function getResult();
}
