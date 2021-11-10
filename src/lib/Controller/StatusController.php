<?php
declare (strict_types=1);


namespace SilangPHP\Migrate\Controller;

use SilangPHP\Migrate\Classes\CommandLineWriter;
use SilangPHP\Migrate\Helper\{
    DbHelper,
    ListHelper,
    MigrationHelper
};

/**
 *
 * FILE_NAME: StatusController.php
 * User: OneXian
 * Date: 2021.11.09
 */
class StatusController extends Controller
{
    /**
     * Determines what action should be performed and takes that action.
     *
     * @return void
     */
    public function doAction()
    {
        // make sure we're init'd
        DbHelper::test();

        // get latest timestamp
        $latest = MigrationHelper::getCurrentMigrationTimestamp();

        // get latest number
        $num = MigrationHelper::getCurrentMigrationNumber();

        // get list of migrations
        $list = ListHelper::getFullList();

        // get command line writer
        $clw = CommandLineWriter::getInstance();
        $clw->writeHeader();

        if (empty($latest))
        {
            echo "You have not performed any migrations yet.";
        }
        else
        {
            echo "You are currently on migration $num -- " . $latest . '.';
        }
        echo "\n";
        $clw->writeFooter();
    }

    /**
     * Displays the help page for this controller.
     *
     * @return void
     */
    public function displayHelp()
    {
        $obj = CommandLineWriter::getInstance();
        $obj->addText('./migrate.php status');
        $obj->addText(' ');
        $obj->addText('This command is used to display the current migration you are on and lists any pending migrations which would be performed if you migrated to the most recent version of the database.');
        $obj->addText(' ');
        $obj->addText('Valid Example:');
        $obj->addText('./migrate.php status', 4);
        $obj->write();
    }

}