<?php
declare (strict_types=1);


namespace SilangPHP\Migrate\Helper;

use SilangPHP\Migrate\Classes\{
    CommandLineWriter,
    ExceptionalMysqli
};

/**
 *
 * FILE_NAME: DbHelper.php
 * User: OneXian
 * Date: 2021.11.09
 */
class DbHelper
{

    /**
     * Returns the correct database object based on the database configuration file.
     *
     * @throws \Exception if database configuration file is missing or method is incorrectly defined
     *
     * @return object
     */
    public static function getDbObj()
    {
        switch (DbHelper::getMethod())
        {
            case M_METHOD_PDO:
                return DbHelper::getPdoObj();
            case M_METHOD_MYSQLI:
                return DbHelper::getMysqliObj();
            default:
                throw new \Exception('Unknown database connection method defined in database configuration.');
        }
    }

    /**
     * Returns a PDO object with connection in place.
     *
     * @throws DatabaseConnectionException if unable to connect to the database
     *
     * @return \PDO
     */
    public static function getPdoObj()
    {
        $pdo_settings = array
        (
            \PDO::ATTR_PERSISTENT => true,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        );
        $db_config = $GLOBALS['db_config'];
        return new \PDO("mysql:host={$db_config->host};port={$db_config->port};dbname={$db_config->name}", $db_config->user, $db_config->pass, $pdo_settings);

    }

    /**
     * Returns an ExceptionalMysqli object with connection in place.
     *
     * @throws DatabaseConnectionException if unable to connect to the database
     *
     * @return ExceptionalMysqli
     */
    public static function getMysqliObj()
    {

        $db_config = $GLOBALS['db_config'];
        return new ExceptionalMysqli($db_config->host, $db_config->user, $db_config->pass, $db_config->name, $db_config->port);
    }

    /**
     * Returns the correct database connection method as set in the database configuration file.
     *
     * @throws \Exception if database configuration file is missing
     *
     * @return int
     */
    public static function getMethod()
    {
        if (!isset($GLOBALS['db_config']))
        {
            throw new \Exception('Missing database configuration.');
        }
        $db_config = $GLOBALS['db_config'];
        return $db_config->method;
    }

    /**
     * Performs a query; $sql should be a SELECT query that returns exactly 1 row of data; returns an object that contains the row
     *
     * @param string $sql a SELECT query that returns exactly 1 row of data
     * @param object $db  a PDO or ExceptionalMysqli object that can be used to run the query
     *
     * @return obj
     */
    public static function doSingleRowSelect($sql, &$db = null)
    {
        try
        {
            if ($db == null)
            {
                $db = DbHelper::getDbObj();
            }
            switch (DbHelper::getMethod())
            {
                case M_METHOD_PDO:
                    $stmt = $db->query($sql);
                    $obj = $stmt->fetch(\PDO::FETCH_OBJ);
                    return $obj;
                case M_METHOD_MYSQLI:
                    $stmt = $db->query($sql);
                    $obj = $stmt->fetch_object();
                    return $obj;
                default:
                    throw new \Exception('Unknown method defined in database configuration.');
            }
        }
        catch (\Exception $e)
        {
            echo "\n\nError: ", $e->getMessage(), "\n\n";
            exit;
        }
    }

    /**
     * Performs a SELECT query
     *
     * @param string $sql a SELECT query
     *
     * @return array
     */
    public static function doMultiRowSelect($sql)
    {
        try
        {
            $db = DbHelper::getDbObj();
            $results = array();
            switch (DbHelper::getMethod())
            {
                case M_METHOD_PDO:
                    $stmt = $db->query($sql);
                    while ($obj = $stmt->fetch(\PDO::FETCH_OBJ))
                    {
                        $results[] = $obj;
                    }
                    return $results;
                case M_METHOD_MYSQLI:
                    $stmt = $db->query($sql);
                    while($obj = $stmt->fetch_object())
                    {
                        $results[] = $obj;
                    }
                    return $results;
                default:
                    throw new \Exception('Unknown method defined in database configuration.');
            }
        }
        catch (\Exception $e)
        {
            echo "\n\nError: ", $e->getMessage(), "\n\n";
            exit;
        }
    }

    /**
     * Checks to make sure everything is in place to be able to use the migrations tool.
     *
     * @return void
     */
    public static function test()
    {
        $problems = array();
        if (!file_exists(CONFIG_PATH . '/db_config.php'))
        {
            $problems[] = 'You have not yet run the init command.  You must run this command before you can use any other commands.';
        }
        else
        {
            switch (DbHelper::getMethod())
            {
                case M_METHOD_PDO:
                    if (!class_exists('\\PDO'))
                    {
                        $problems[] = 'It does not appear that the PDO extension is installed.';
                    }
                    $drivers = \PDO::getAvailableDrivers();
                    if (!in_array('mysql', $drivers))
                    {
                        $problems[] = 'It appears that the mysql driver for PDO is not installed.';
                    }
                    if (count($problems) == 0)
                    {
                        try
                        {
                            $pdo = DbHelper::getPdoObj();
                        }
                        catch (\Exception $e)
                        {
                            $problems[] = 'Unable to connect to the database: ' . $e->getMessage();
                        }
                    }
                    break;
                case M_METHOD_MYSQLI:
                    if (!class_exists('mysqli'))
                    {
                        $problems[] = "It does not appear that the mysqli extension is installed.";
                    }
                    if (count($problems) == 0)
                    {
                        try
                        {
                            $mysqli = DbHelper::getMysqliObj();
                        }
                        catch (\Exception $e)
                        {
                            $problems[] = "Unable to connect to the database: " . $e->getMessage();
                        }
                    }
                    break;
            }
            if (!DbHelper::checkForDbTable())
            {
                $problems[] = 'Migrations table not found in your database.  Re-run the init command.';
            }
            if (count($problems) > 0)
            {
                $obj = CommandLineWriter::getInstance();
                $obj->addText("It appears there are some problems:");
                $obj->addText("\n");
                foreach ($problems as $problem)
                {
                    $obj->addText($problem, 4);
                    $obj->addText("\n");
                }
                $obj->write();
                exit;
            }
        }
    }

    /**
     * Checks whether or not the migrations database table exists.
     *
     * @return bool
     */
    public static function checkForDbTable()
    {
        $db_config = $GLOBALS['db_config'];
        $migrations_table = $db_config->migrations_table;
        if (isset($db_config->migrations_table))
        {
            $migrations_table = $db_config->migrations_table;
        }
        $tables = DbHelper::getTables();
        if (count($tables) == 0 || !in_array($migrations_table, $tables))
        {
            return false;
        }
        return true;
    }

    /**
     * Returns an array of all the tables in the database.
     *
     * @return array
     */
    public static function getTables(&$dbObj = null)
    {
        if ($dbObj == null)
        {
            $dbObj = DbHelper::getDbObj();
        }
        $sql = "SHOW TABLES";
        $tables = array();
        switch (DbHelper::getMethod())
        {
            case M_METHOD_PDO:
                try
                {
                    foreach ($dbObj->query($sql) as $row)
                    {
                        $tables[] = $row[0];
                    }
                }
                catch (\Exception $e)
                {
                }
                break;
            case M_METHOD_MYSQLI:
                try
                {
                    $result = $dbObj->query($sql);
                    while ($row = $result->fetch_array())
                    {
                        $tables[] = $row[0];
                    }
                }
                catch (\Exception $e)
                {
                }
                break;
        }
        return $tables;
    }

}