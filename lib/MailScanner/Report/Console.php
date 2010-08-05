<?php

class MailScanner_Report_Console implements MailScanner_Report_Interface
{
    /**
     * Generate report
     *
     * @param string $body
     */
    public function report($body)
    {
        echo 'Generated output:' . PHP_EOL;
        echo $body . PHP_EOL;
    }
}