<?php

error_reporting(E_ALL ^ E_STRICT);
date_default_timezone_set('Europe/Berlin');

set_include_path(
    dirname(dirname(__FILE__)) . '/lib' . PATH_SEPARATOR .
    get_include_path()
);

require_once 'Zend/Loader/Autoloader.php';
Zend_Loader_Autoloader::getInstance()
    ->registerNamespace('MailScanner')
    ->suppressNotFoundWarnings(true);

