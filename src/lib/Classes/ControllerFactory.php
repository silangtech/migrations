<?php
declare (strict_types=1);


namespace SilangPHP\Migrate\Classes;

/**
 *
 * FILE_NAME: ControllerFactory.php
 * User: OneXian
 * Date: 2021.11.09
 */
class ControllerFactory
{

    public static function getInstance($argv)
    {
        $script_name = array_shift($argv);
        $controller_name = array_shift($argv);
        if ($controller_name == null)
        {
            $controller_name = 'help';
        }
        $class_name = ucwords(strtolower($controller_name)) . 'Controller';

        $class_name = '\\SilangPHP\\Migrate\\Controller\\' . $class_name;

        $obj = new $class_name($controller_name, $argv);
        return $obj;
    }

}