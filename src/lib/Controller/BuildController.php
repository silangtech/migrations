<?php
declare (strict_types=1);


namespace SilangPHP\Migrate\Controller;

use SilangPHP\Migrate\Classes\CommandLineWriter;
use SilangPHP\Migrate\Helper\{
    TemplateHelper,
    MigrationHelper,
    DbHelper
};

/**
 *
 * FILE_NAME: BuildController.php
 * User: OneXian
 * Date: 2021.11.09
 */
class BuildController extends Controller
{
    /**
     * Determines what action should be performed and takes that action.
     *
     * @return void
     */
    public function doAction()
    {
        // make sure system is init'ed
        DbHelper::test();

        $clw = CommandLineWriter::getInstance();

        $forced = false;

        $with_data = false;

        // are we adding a schema file?
        if (isset($this->arguments[0]) && $this->arguments[0] == 'add')
        {
            // make sure the schema file doesn't exist
            if (file_exists(M_DB_PATH . 'schema.php') || file_exists(M_DB_PATH . 'test_data.php'))
            {
                $clw->addText('The schema and/or test data files already exist.  Delete them first if you want to use this option.');
                $clw->write();
                exit;
            }
            $file = TemplateHelper::getTemplate('schema.txt');
            $test_data_file = TemplateHelper::getTemplate('test_data.txt');

            $fp = fopen(M_DB_PATH . 'schema.php', "w");
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

            $fp = fopen(M_DB_PATH . 'test_data.php', "w");
            if ($fp == false)
            {
                echo "\nUnable to write to file.  Initialization failed!\n\n";
                exit;
            }
            $success = fwrite($fp, $test_data_file);
            if ($success == false)
            {
                echo "\nUnable to write to file.  Initialization failed!\n\n";
                exit;
            }
            fclose($fp);

            $clw->addText('File ' . M_DB_PATH . 'schema.php has been created.');
            $clw->addText('File ' . M_DB_PATH . 'test_data.php has been created.');
            $clw->write();
            exit;

        }
        else if (isset($this->arguments[0]) && $this->arguments[0] == 'with_data')
        {
            $with_data = true;
        }
        else if (isset($this->arguments[0]) && $this->arguments[0] == '--force')
        {
            $forced = true;
        }

        // make sure the schema file exists
        if (!file_exists(M_DB_PATH . 'schema.php'))
        {
            $clw->addText('The schema file does not exist.  Run this command with the "add" argument to create one (only a stub).');
            $clw->write();
            exit;
        }
        // make sure the test data file exists
        if ($with_data == true && !file_exists(M_DB_PATH . 'test_data.php'))
        {
            $clw->addText('The test data file does not exist.  Run this command with the "add" argument to create one (only a stub).');
            $clw->write();
            exit;
        }

        $clw->writeHeader();

        if (!$forced)
        {
            echo "\nWARNING:  IF YOU CONTINUE, ALL TABLES IN YOUR DATABASE WILL BE ERASED!";
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
        }

        echo "\n";
        $this->build($with_data);

        $clw->writeFooter();
        exit;

    }

    /**
     * Does the actual task of destroying and rebuilding the database from the ground up.
     *
     * @param bool $with_data whether or not to run the test_data.php file after build
     *
     * @return void
     */
    public function build($with_data)
    {
        require_once(M_DB_PATH . 'schema.php');
        $obj = new InitialSchema();
        $obj->destroy();
        echo "\n";
        $obj->reloadMigrations();
        echo "\n", 'Building initial database schema... ';
        $obj->build();
        echo 'done.', "\n\n", 'Applying migrations... ';
        try
        {
            $total_migrations = MigrationHelper::getMigrationCount();
            if ($total_migrations == 0)
            {
                echo "no migrations exist.";
            }
            else
            {
                $to_id = MigrationHelper::getLatestMigration();
                $obj = new UpController('up', array ( $to_id, $forced ));
                $obj->doAction($quiet);
            }
        }
        catch (\Exception $e)
        {
            echo "\n\nERROR: " . $e->getMessage() . "\n\n";
            exit;
        }
        if ($with_data)
        {
            require_once(M_DB_PATH . 'test_data.php');
            echo "\n\nInserting test data... ";
            $test_data_obj = new TestData();
            $test_data_obj->build();
            echo 'done.';
        }
        echo "\n\n", 'Database build complete.', "\n";
    }

    /**
     * Displays the help page for this controller.
     *
     * @return void
     */
    public function displayHelp()
    {
        $obj = CommandLineWriter::getInstance();
        $obj->addText('./migrate.php build [--force|add]');
        $obj->addText(' ');
        $obj->addText('This command is used to build the database.  If a schema.php file is found in the migrations directory, the Schema::Build() method will be called.  Then, all migrations will be run against the database.');
        $obj->addText(' ');
        $obj->addText('Use the "add" argument to create an empty stub for the schema.php file.  You can then add your own query statements.  This will also add a filed called test_data.php.  You can add queries to this file to insert test data after a build.');
        $obj->addText(' ');
        $obj->addText('Use the "with_data" argument to run the test_data.php file after the database has been rebuilt.  This allows you to automatically insert fresh new test data into the system.');
        $obj->addText(' ');
        $obj->addText('If you use the "--force" argument instead of the "add" argument, you will not be prompted to confirm the action (good for scripting a build process).');
        $obj->addText(' ');
        $obj->addText('WARNING: THIS IS A DESTRUCTIVE ACTION!!  BEFORE THE DATABASE IS BUILT, ALL TABLES CURRENTLY IN THE DATABASE ARE REMOVED!');
        $obj->addText(' ');
        $obj->addText('Valid Examples:');
        $obj->addText('./migrate.php build add', 4);
        $obj->addText('./migrate.php build with_data', 4);
        $obj->addText('./migrate.php build with_data --force', 4);
        $obj->write();
    }

}