<?php
declare (strict_types=1);


namespace SilangPHP\Migrate\Controller;

use SilangPHP\Migrate\Classes\CommandLineWriter;

/**
 *
 * FILE_NAME: HelpController.php
 * User: OneXian
 * Date: 2021.11.09
 */
class HelpController extends Controller
{
    /**
     * Determines what action should be performed and takes that action.
     *
     * @return void
     */
    public function doAction()
    {
        if (count($this->arguments) == 0)
        {
            return $this->displayHelp();
        }
        else
        {
            $controller_name = $this->arguments[0];

            $class_name = ucwords(strtolower($controller_name)) . 'Controller';
            $class_name = '\\SilangPHP\\Migrate\\Controller\\' . $class_name;

            $obj = new $class_name();
            return $obj->displayHelp();
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
        $obj->addText('The Following Commands Are Available:');
        $obj->addText('add    - add a new migration', 4);
        $obj->addText('build  - builds the database', 4);
        $obj->addText('down   - roll down to a previous migration', 4);
        $obj->addText('help   - get more specific help about individual commands', 4);
        $obj->addText('init   - initialize the migrations', 4);
        $obj->addText('latest - roll up to the latest migration', 4);
        $obj->addText('list   - list all migrations', 4);
        $obj->addText('run    - runs a single migration', 4);
        $obj->addText('status - show the current migration', 4);
        $obj->addText('up     - roll up to a future migration', 4);
        $obj->addText(' ');

        $obj->addText('For specific help for an individual command, type:');
        $obj->addText('./migrate.php help [command]', 4);
        $obj->write();
    }
}