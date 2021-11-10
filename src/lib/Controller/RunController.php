<?php
declare (strict_types=1);


namespace SilangPHP\Migrate\Controller;

use SilangPHP\Migrate\Classes\CommandLineWriter;
use SilangPHP\Migrate\Helper\{
    MigrationHelper,
    DbHelper
};

/**
 *
 * FILE_NAME: RunController.php
 * User: OneXian
 * Date: 2021.11.09
 */
class RunController extends Controller
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

        if (count($this->arguments) != 2)
        {
            $obj = CommandLineWriter::getInstance();
            $obj->addText('ERROR: You must provide two arguments with this command.');
            $obj->addText(' ');
            $this->displayHelp();
            return;
        }

        // are we running the up or the down?
        $type = strtolower($this->arguments[0]);

        // what number do we want to run?
        $num = $this->arguments[1];

        if (!is_numeric($num))
        {
            $obj = CommandLineWriter::getInstance();
            $obj->addText('ERROR: Migration number must be numeric.');
            $obj->addText(' ');
            $this->displayHelp();
            return;
        }

        if ($type != 'up' && $type != 'down')
        {
            $obj = CommandLineWriter::getInstance();
            $obj->addText('ERROR: Method must be either up or down.');
            $obj->addText(' ');
            $this->displayHelp();
            return;
        }

        // does this migration number exist?
        if (!MigrationHelper::doesMigrationExist($num))
        {
            $obj = CommandLineWriter::getInstance();
            $obj->addText('ERROR: Migration ' . $num . ' does not exist.');
            $obj->write();
            return;
        }

        $row = MigrationHelper::getMigrationObject($num);
        $obj = CommandLineWriter::getInstance();
        $obj->writeHeader();
        MigrationHelper::runMigration($row, $type);
        echo "\n";
        $obj->writeFooter();

    }

    /**
     * Displays the help page for this controller.
     *
     * @uses CommandLineWriter::getInstance()
     * @uses CommandLineWriter::addText()
     * @uses CommandLineWriter::write()
     *
     * @return void
     */
    public function displayHelp()
    {
        $obj = CommandLineWriter::getInstance();
        $obj->addText('./migrate.php run [method] [migration #]');
        $obj->addText(' ');
        $obj->addText('This command is used to run a single migration.');
        $obj->addText(' ');
        $obj->addText('You must specify a method to run (either up or down) and a migration # (as provided by the list command)');
        $obj->addText(' ');
        $obj->addText('Valid Examples:');
        $obj->addText('./migrate.php run up 13', 4);
        $obj->addText('./migrate.php run down 12', 4);
        $obj->write();
    }
}