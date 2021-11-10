<?php
declare (strict_types=1);


namespace SilangPHP\Migrate\Controller;

use SilangPHP\Migrate\Classes\CommandLineWriter;

use SilangPHP\Migrate\Helper\{
    DbHelper,
    MigrationHelper
};

/**
 *
 * FILE_NAME: LatestController.php
 * User: OneXian
 * Date: 2021.11.09
 */
class LatestController extends Controller
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
        // are we forcing this?
        $forced = '';
        if (isset($this->arguments[0]) && strcasecmp($this->arguments[0], '--force') == 0)
        {
            $forced = '--force';
        }

        try
        {
            $total_migrations = MigrationHelper::getMigrationCount();
            if ($total_migrations == 0)
            {
                $clw = CommandLineWriter::getInstance();
                $clw->addText('No migrations exist.');
                $clw->write();
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
        $obj->addText('./migrate.php latest [--force]');
        $obj->addText(' ');
        $obj->addText('This command is used to migrate up to the most recent version.  No arguments are required.');
        $obj->addText(' ');
        $obj->addText('If the --force option is provided, then the script will automatically skip over any migrations which cause errors and continue migrating forward.');
        $obj->addText(' ');
        $obj->addText('Valid Examples:');
        $obj->addText('./migrate.php latest', 4);
        $obj->addText('./migrate.php latest --force', 4);
        $obj->write();
    }

}