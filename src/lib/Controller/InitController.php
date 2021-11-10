<?php
declare (strict_types=1);


namespace SilangPHP\Migrate\Controller;

use SilangPHP\Migrate\Classes\CommandLineWriter;
use SilangPHP\Migrate\Helper\{
    DbHelper
};

/**
 *
 * FILE_NAME: InitController.php
 * User: OneXian
 * Date: 2021.11.09
 */
class InitController extends Controller
{
    /**
     * Determines what action should be performed and takes that action.
     *
     * @return void
     */
    public function doAction()
    {
        $user = '';
        $dbname = '';
        $port = '';
        $db_path = '';
        $method = 0;

        $clw = CommandLineWriter::getInstance();
        $clw->writeHeader();
        echo "Defaults are in brackets ([]).  To accept the default, simply press ENTER.\n\n";

        if (file_exists(CONFIG_PATH . '/db_config.php'))
        {
            echo "\nWARNING:  IF YOU CONTINUE, YOUR EXISTING MIGRATION SETUP WILL BE ERASED!";
            echo "\nThis will not affect your existing migrations or database, but \ncould cause your future migrations to fail.";
            echo "\nDO YOU WANT TO CONTINUE? [y/N] ";
            $answer = fgets(STDIN);
            $answer = trim($answer);
            $answer = strtolower($answer);
            if (empty($answer) || substr($answer, 0, 1) == 'n')
            {
                echo "\nABORTED!\n\n";
                $clw->writeFooter();
                exit;
            }
            else
            {
                require(CONFIG_PATH . '/db_config.php');
            }
        }

        echo "\nEnter a name to use for the table that will hold your migration data [";
        if (isset($db_config) && isset($db_config->migrations_table))
        {
            echo $db_config->migrations_table;
        }
        else
        {
            echo 'm_migrations';
        }
        echo ']: ';

        // input in Enter
        $migrations_table = fgets(STDIN);
        $migrations_table = trim($migrations_table);
        if (empty($migrations_table))
        {
            if (isset($db_config) && isset($db_config->migrations_table))
            {
                $migrations_table = $db_config->migrations_table;
            }
            else
            {
                $migrations_table = 'm_migrations';
            }
        }

        do
        {
            echo "\nWhich method would you like to use to connect to\nthe database?  ".M_METHOD_PDO."=PDO or ".M_METHOD_MYSQLI."=MySQLi";
            if (isset($db_config))
            {
                echo " [" . $db_config->method . "]";
            }
            echo ": ";
            $method = fgets(STDIN);
            $method = trim($method);
            if (!is_numeric($method))
            {
                $method = 0;
            }
            if (empty($method) && isset($db_config))
            {
                $method = $db_config->method;
            }
        } while ($method < M_METHOD_PDO || $method > M_METHOD_MYSQLI || $method == 0);

        echo "\nEnter your MySQL database hostname or IP address [";
        if (isset($db_config))
        {
            echo $db_config->host;
        }
        else
        {
            echo 'localhost';
        }
        echo ']: ';
        $host = fgets(STDIN);
        $host = trim($host);
        if (empty($host))
        {
            if (isset($db_config))
            {
                $host = $db_config->host;
            }
            else
            {
                $host = 'localhost';
            }
        }

        while (empty($port))
        {
            echo "\nEnter your MySQL database port [";
            if (isset($db_config))
            {
                echo $db_config->port;
            }
            else
            {
                echo '3306';
            }
            echo ']: ';

            $port = fgets(STDIN);
            $port = trim($port);
            if (empty($port))
            {
                $port = 3306;
            }
            if (!is_numeric($port))
            {
                $port = '';
            }
        }

        while (empty($user))
        {
            echo "\nEnter your MySQL database username";
            if (isset($db_config))
            {
                echo ' [', $db_config->user, ']';
            }
            echo ': ';
            $user = fgets(STDIN);
            $user = trim($user);
            if (empty($user) && isset($db_config))
            {
                $user = $db_config->user;
            }
        }

        echo "\nEnter your MySQL database password (enter - for no password) [";
        if (isset($db_config))
        {
            echo $db_config->pass;
        }
        echo ']: ';
        $pass = fgets(STDIN);
        $pass = trim($pass);
        if (empty($pass) && isset($db_config))
        {
            $pass = $db_config->pass;
        }
        else if ($pass == '-')
        {
            $pass = '';
        }


        while (empty($dbname))
        {
            echo "\nEnter your MySQL database name";
            if (isset($db_config))
            {
                echo ' [', $db_config->name, ']';
            }
            echo ': ';
            $dbname = fgets(STDIN);
            $dbname = trim($dbname);
            if (empty($dbname) && isset($db_config))
            {
                $dbname = $db_config->name;
            }
        }

        echo "\nEnter the directory where you'd like to store your\nmigration files [";
        if (isset($db_config))
        {
            echo $db_config->db_path;
        }
        else
        {
            echo CONFIG_PATH . '/db/';
        }
        echo ']: ';
        $db_path = fgets(STDIN);
        $db_path = trim($db_path);
        if (empty($db_path) && isset($db_config))
        {
            $db_path = $db_config->db_path;
        }
        else if (empty($db_path) && !isset($db_config))
        {
            $db_path = CONFIG_PATH . '/db/';
        }
        if (substr($db_path, strlen($db_path) - 1, 1) != '/')
        {
            $db_path .= '/';
        }

        $method = (int) $method;

        $file = '<?php' . "\n\n";
        $file .= '$db_config = (object) array();' . "\n";
        $file .= '$db_config->host = ' . "'" . $host . "';" . "\n";
        $file .= '$db_config->port = ' . "'" . $port . "';" . "\n";
        $file .= '$db_config->user = ' . "'" . $user . "';" . "\n";
        $file .= '$db_config->pass = ' . "'" . $pass . "';" . "\n";
        $file .= '$db_config->name = ' . "'" . $dbname . "';" . "\n";
        $file .= '$db_config->db_path = ' . "'" . $db_path . "';" . "\n";
        $file .= '$db_config->method = ' . $method . ";" . "\n";
        $file .= '$db_config->migrations_table = ' . "'" . $migrations_table . "';" . "\n";
        $file .= "\n?>";

        !is_dir($db_path) ? mkdir($db_path, 0777, true) : '';

        if (file_exists(CONFIG_PATH . '/db_config.php'))
        {
            unlink(CONFIG_PATH . '/db_config.php');
        }

        $fp = fopen(CONFIG_PATH . '/db_config.php', "w");
        if ($fp == false)
        {
            echo "\nUnable to write to file.  Initialization failed!\n\n";
            exit;
        }
        $success = fwrite($fp, $file);
        if ($success == false)
        {
            echo "\nUnable to write to file.  Initialization failed!\n\n";
            exit;
        }
        fclose($fp);

        require(CONFIG_PATH . '/db_config.php');
        $GLOBALS['db_config'] = $db_config;

        echo "\nConfiguration saved... looking for existing migrations table... ";

        try
        {
            if (false === DbHelper::checkForDbTable())
            {
                echo "not found.\n";
                echo "Creating migrations table... ";
                $sql1 = "CREATE TABLE IF NOT EXISTS `{$migrations_table}` ( `id` INT(11) NOT NULL AUTO_INCREMENT, `timestamp` DATETIME NOT NULL, `active` TINYINT(1) NOT NULL DEFAULT 0, `is_current` TINYINT(1) NOT NULL DEFAULT 0, `create_time` datetime DEFAULT CURRENT_TIMESTAMP, `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY ( `id` ) ) ENGINE=InnoDB";
                $sql2 = "CREATE UNIQUE INDEX `TIMESTAMP_INDEX` ON `{$migrations_table}` ( `timestamp` )";

                if (DbHelper::getMethod() == M_METHOD_PDO)
                {
                    $pdo = DbHelper::getDbObj();
                    $pdo->beginTransaction();
                    try
                    {
                        $pdo->exec($sql1);
                        $pdo->exec($sql2);
                    }
                    catch (\Exception $e)
                    {
                        $pdo->rollback();
                        echo "failure!\n\n" . 'Unable to create required ' . $migrations_table . ' table:' . $e->getMessage();
                        echo "\n\n";
                        exit;
                    }
                    $pdo->commit();
                }
                else
                {
                    $mysqli = DbHelper::getDbObj();
                    $mysqli->query($sql1);
                    if ($mysqli->errno)
                    {
                        echo "failure!\n\n" . 'Unable to create required ' . $migrations_table . ' table:' . $mysqli->error;
                        echo "\n\n";
                        exit;
                    }
                    $mysqli->query($sql2);
                    if ($mysqli->errno)
                    {
                        echo "failure!\n\n" . 'Unable to create required ' . $migrations_table . ' table:' . $mysqli->error;
                        echo "\n\n";
                        exit;
                    }
                }
                echo "done.\n\n";
            }
            else
            {
                echo "found.\n\n";
            }

        }
        catch (\Exception $e)
        {
            echo "failure!\n\nUnable to complete initialization: " . $e->getMessage() . "\n\n";
            echo "Check your database settings and re-run init.\n\n";
            exit;
        }

        echo "Initalization complete!  Type 'php migrate.php help' for a list of commands.\n\n";
        $clw->writeFooter();
        exit;
    }

    /**
     * Displays the help page for this controller.
     *
     * @return void
     */
    public function displayHelp()
    {
        $obj = CommandLineWriter::getInstance();
        $obj->addText('./migrate.php init');
        $obj->addText(' ');
        $obj->addText('This command is used to initialize the migration system for use with your particular deployment.  After you have modified the /config/db.php configuration file appropriately, you should run this command to setup the initial tracking schema and add your username to the migraiton archive.');
        $obj->addText(' ');
        $obj->addText('Example:');
        $obj->addText('./migrate.php init jdoe', 4);
        $obj->write();
    }

}