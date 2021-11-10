<?php
declare (strict_types=1);


namespace SilangPHP\Migrate\Controller;

use SilangPHP\Migrate\Classes\CommandLineWriter;
use SilangPHP\Migrate\Helper\{
    ListHelper
};

/**
 *
 * FILE_NAME: ListController.php
 * User: OneXian
 * Date: 2021.11.09
 */
class ListController extends Controller
{
    /**
     * Determines what action should be performed and takes that action.
     *
     * @return void
     */
    public function doAction()
    {
        $page = 1;
        $per_page = 30;

        if (isset($this->arguments[0]))
        {
            $page = $this->arguments[0];
        }
        if (isset($this->arguments[1]))
        {
            $per_page = $this->arguments[1];
        }

        if (!is_numeric($per_page))
        {
            $per_page = 30;
        }
        if (!is_numeric($page))
        {
            $page = 1;
        }
        $start_idx = ($page - 1) * $per_page;

        $list = ListHelper::getFullList($start_idx, $per_page);
        $total = ListHelper::getTotalMigrations();
        $total_pages = ceil($total / $per_page);
        $clw = CommandLineWriter::getInstance();

        if ($total == 0)
        {
            $clw->addText('No migrations exist.');
        }
        else
        {
            $clw->addText("WARNING: Migration numbers may not be in order due to interleaving.", 4);
            $clw->addText(" ");
            $clw->addText("#\t\tTimestamp", 6);
            $clw->addText("=========================================", 4);
            foreach ($list as $obj)
            {
                if (strlen($obj->id) > 1)
                {
                    $clw->addText($obj->id . "\t" . $obj->timestamp, 6);
                }
                else
                {
                    $clw->addText($obj->id . "\t\t" . $obj->timestamp, 6);
                }
            }
            $clw->addText(" ");
            $clw->addText("Page $page of $total_pages, $total migrations in all.", 4);
        }

        $clw->write();
    }

    /**
     * Displays the help page for this controller.
     *
     * @return void
     */
    public function displayHelp()
    {
        $obj = CommandLineWriter::getInstance();
        $obj->addText('./migrate.php list [page] [per page]');
        $obj->addText(' ');
        $obj->addText('This command is used to display a list of all the migrations available.  Each migration is listed by number and timestamp.  You will need the migration number in order to perform an up or down migration.');
        $obj->addText(' ');
        $obj->addText('Since a project may have a large number of migrations, this command is paginated.  The page number is required.  If you do not enter it, the command will assume you want to see page 1.');
        $obj->addText(' ');
        $obj->addText('If you do not provide a per page argument, this command will default to 30 migrations per page.');
        $obj->addText(' ');
        $obj->addText('Valid Examples:');
        $obj->addText('./migrate.php list', 4);
        $obj->addText('./migrate.php list 2', 4);
        $obj->addText('./migrate.php list 1 15', 4);
        $obj->write();
    }

}