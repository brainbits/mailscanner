<?php

interface MailScanner_Report_Interface
{
    /**
     * Generate report
     *
     * @param string $body
     */
    public function report($body);
}