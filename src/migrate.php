#!/usr/bin/env php
<?php

// we want to see any errors
ini_set('display_errors', 1);
error_reporting(E_ALL);

// fix date issues
if (function_exists('date_default_timezone_set'))
{
    date_default_timezone_set("Asia/Shanghai");
}

/**
 * Define the full path to this file.
 */
define('M_PATH', dirname(__FILE__));

define('CMD_PATH', getcwd());
define('CONFIG_PATH', CMD_PATH . '/DbSchema');

require_once (CMD_PATH . '/vendor/autoload.php');

/**
 * Version Number - for reference
 */
define('M_VERSION', '1.0.0');

/**
 * Include the init script.
 */
require_once(M_PATH . '/lib/init.php');

// get the proper controller, do the action, and exit the script
$obj = \SilangPHP\Migrate\Classes\ControllerFactory::getInstance($argv);
$obj->doAction();
exit;