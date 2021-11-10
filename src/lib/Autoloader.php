<?php
declare (strict_types=1);


namespace SilangPHP\Migrate;


/**
 *
 * FILE_NAME: Autoloader.php
 * User: OneXian
 * Date: 2021.11.09
 */
class Autoloader{

    public static function load($name)
    {
        $class_path = str_replace('\\', DIRECTORY_SEPARATOR, $name);
        if (strpos($name, 'SilangPHP\\Migrate\\') === 0) {
            $class_file = __DIR__ . substr($class_path, strlen('SilangPHP\\Migrate')) . '.php';
        }elseif(empty($class_file) || !is_file($class_file)){
            $class_file = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $class_path . ".php";
        }

        if (is_file($class_file)) {
            require_once ($class_file);
            if (class_exists($name, false)) {
                return true;
            }
        }
        return false;

    }

}
