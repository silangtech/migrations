<?php
declare (strict_types=1);

namespace SilangPHP\Migrate\Controller;

use SilangPHP\Migrate\Helper\{
    ListHelper,
    DbHelper
};
/**
 *
 * FILE_NAME: Controller.php
 * User: OneXian
 * Date: 2021.11.09
 */
abstract class Controller
{
    protected $arguments;

    /**
     * The current command being issued.
     *
     * @var string
     */
    protected $command;

    /**
     * Object constructor.
     *
     * @param array $arguments an array of command line arguments (minus the first two elements which should already be shifted off from the ControllerFactory)
     *
     * @return Controller
     */
    public function __construct($command = 'help', $arguments = array())
    {
        $this->arguments = $arguments;
        $this->command = $command;
        if ($command != 'help' && $command != 'init')
        {
            DbHelper::test();
            ListHelper::mergeFilesWithDb();
        }
    }

    /**
     * Determines what action should be performed and takes that action.
     *
     * @return void
     */
    abstract public function doAction();

    /**
     * Displays the help page for this controller.
     *
     * @return void
     */
    abstract public function displayHelp();
}