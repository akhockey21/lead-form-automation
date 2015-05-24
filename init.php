<?php

/**
 * Load Clases
 */ 

require 'settingsclass.php';

/**
 * Load Config Options and Settings
 * 
 * If a etting is enabled, then load the API class related
 */ 
require_once 'config.php';

/**
 * Load Database Class
 * Get Connection Settigns From Config Class
 */ 
require_once 'dbclass.php';
$dbInfo = $settings->getSettings();
$db = new MysqliDb ($dbInfo['host'], $dbInfo['username'], $dbInfo['password'], $dbInfo['databaseName']);

//If statement to load API.
require_once 'Services/Twilio.php';
require_once 'Services/Email/Mandrill.php';
require_once 'email.php';
require_once 'functions.php';
/**
 * Include Main Script
 */ 
require_once 'main.php';



?>