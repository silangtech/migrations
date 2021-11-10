<?php

if (file_exists(CONFIG_PATH . '/db_config.php'))
{
    /**
     * Include the database connection info.
     */
    require_once(CONFIG_PATH . '/db_config.php');
}


if (!defined('M_DB_PATH'))
{
    if (isset($db_config->db_path) && strlen($db_config->db_path) > 0)
    {
        /**
         * Defines the M_DB_PATH if specified.  Allows this to be outside of the main migration script library.
         */
        define('M_DB_PATH', $db_config->db_path);
    }
    else
    {
        /**
         * @ignore
         */
        define('M_DB_PATH', M_PATH . '/db/');
    }
}

if (!defined('M_METHOD_PDO'))
{
    /**
     * Flag to use PDO to talk to the database.
     */
    define('M_METHOD_PDO', 1);
}

if (!defined('M_METHOD_MYSQLI'))
{
    /**
     * Flag to use MySQLi to talk to the database.
     */
    define('M_METHOD_MYSQLI', 2);
}

if (!defined('STDIN'))
{
    /**
     * In some cases STDIN built-in can be undefined
     */
    define('STDIN', fopen("php://stdin","r"));
}

/**
 * Include the AutoloadHelper class.
 */
require_once(M_PATH . '/lib/Autoloader.php');

// add default autoloader function to the autoload stack
if (function_exists('__autoload'))
{
    spl_autoload_register('__autoload');
}

// add custom library autoloader to the stack
spl_autoload_register('\SilangPHP\Migrate\Autoloader::load');