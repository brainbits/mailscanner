<?php

// settings
error_reporting(-1);
date_default_timezone_set('Europe/Berlin');
set_include_path(dirname(__FILE__) . '/mailscanner/lib' . PATH_SEPARATOR . get_include_path());

// zend autoloader
require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

// create objects
$config      = new Zend_Config_Ini('config.ini');
$transport   = new Zend_Mail_Transport_Smtp($config->transport->hostname, $config->transport->toArray());
$log         = new MailScanner_Log_Console();
$report      = new MailScanner_Report_Mail($transport, $config->mail);
$scanner     = new MailScanner_Check($log, $report);
$imapStorage = new Zend_Mail_Storage_Imap($config->imap->toArray());

foreach ($config->module as $moduleConfig)
{
    if (!isset($moduleConfig->enabled) || !$moduleConfig->enabled)
    {
        continue;
    }

    $moduleClassname = $moduleConfig->classname;
    $module = new $moduleClassname($imapStorage, $log, $moduleConfig);

    $scanner->addModule($module);
}

try
{
    $scanner->run();
}
catch (Exception $e)
{
    echo $e->getMessage().PHP_EOL . '<pre>' . $e->getTraceAsString();
}
