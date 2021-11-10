<?php
declare (strict_types=1);


namespace SilangPHP\Migrate\Controller;

use SilangPHP\Migrate\Classes\CommandLineWriter;

use SilangPHP\Migrate\Helper\{
    MigrationHelper
};

/**
 *
 * FILE_NAME: UpController.php
 * User: OneXian
 * Date: 2021.11.09
 */
class UpController extends Controller
{
    /**
     * Determines what action should be performed and takes that action.
     *
     * @param bool $quiet supresses certain text when true
     *
     * @return void
     */
    public function doAction($quiet = false)
    {
        $clw = CommandLineWriter::getInstance();

        if (!$quiet)
        {
            $clw->writeHeader();
        }

        if (count($this->arguments) == 0)
        {
            return $this->displayHelp();
        }

        $up_to = $this->arguments[0];

        if (!is_numeric($up_to))
        {
            return $this->displayHelp();
        }

        // are we forcing this?
        $forced = false;
        if (isset($this->arguments[1]) && strcasecmp($this->arguments[1], '--force') == 0)
        {
            $forced = true;
        }

        // what migrations need to be done?
        $list = MigrationHelper::getListOfMigrations($up_to);

        if (count($list) == 0)
        {
            if (!$quiet)
            {
                echo 'All needed migrations have already been run or no migrations exist.';
                $clw->writeFooter();
                exit;
            }
            else
            {
                return;
            }
        }

        $to = MigrationHelper::getTimestampFromId($up_to);

        if (!$quiet)
        {
            echo "Migrating to " . $to . ' (ID '.$up_to.')... ';
        }

        foreach ($list as $id => $obj)
        {
            MigrationHelper::runMigration($obj, 'up', $forced);
        }

        MigrationHelper::setCurrentMigration($up_to);

        if (!$quiet)
        {
            echo "\n";
            $clw->writeFooter();
        }
    }

    /**
     * Displays the help page for this controller.
     * 
     * @return void
     */
    public function displayHelp()
    {
        $obj = CommandLineWriter::getInstance();
        $obj->addText('./migrate.php up [migration #] [--force]');
        $obj->addText(' ');
        $obj->addText('This command is used to migrate up to a newer version.  You can get a list of all of the migrations available by using the list command.');
        $obj->addText(' ');
        $obj->addText('You must specify a migration # (as provided by the list command)');
        $obj->addText(' ');
        $obj->addText('If the --force option is provided, then the script will automatically skip over any migrations which cause errors and continue migrating forward.');
        $obj->addText(' ');
        $obj->addText('Valid Examples:');
        $obj->addText('./migrate.php up 14', 4);
        $obj->addText('./migrate.php up 12 --force', 4);
        $obj->write();
    }

}