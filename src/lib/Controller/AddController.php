<?php
declare (strict_types=1);


namespace SilangPHP\Migrate\Controller;

use SilangPHP\Migrate\Classes\CommandLineWriter;
use SilangPHP\Migrate\Helper\{
    TemplateHelper,
    ListHelper,
    DbHelper
};

/**
 *
 * FILE_NAME: AddController.php
 * User: OneXian
 * Date: 2021.11.09
 */
class AddController extends Controller
{
    public function doAction()
    {

        // get date stamp for use in generating filename
        $date_stamp = date('Y_m_d_H_i_s');
        $filename = $date_stamp . '.php';
        $vars = array ('timestamp' => $date_stamp);
        //$classname = 'Migration_' . $date_stamp;

        // get list of files
        $files = ListHelper::getFiles();

        // if filename is taken, throw error
        if (in_array($filename, $files))
        {
            $obj = CommandLineWriter::getInstance();
            $obj->addText('Unable to obtain a unique filename for your migration.  Please try again in a few seconds.');
            $obj->write();
        }

        // create file
        if (DbHelper::getMethod() == M_METHOD_PDO)
        {
            $file = TemplateHelper::getTemplate('pdo_migration.txt', $vars);
        }
        else
        {
            $file = TemplateHelper::getTemplate('mysqli_migration.txt', $vars);
        }

        // write the file
        !is_dir(M_DB_PATH) ? mkdir(M_DB_PATH, 0755, true) :'';
        $fp = fopen(M_DB_PATH . $filename, "w");
        if ($fp == false)
        {
            $obj = CommandLineWriter::getInstance();
            $obj->addText('Unable to write new migration file.');
            $obj->write();
        }
        $success = fwrite($fp, $file);
        if ($success == false)
        {
            $obj = CommandLineWriter::getInstance();
            $obj->addText('Unable to write new migration file.');
            $obj->write();
        }
        fclose($fp);

        // display success message
        $obj = CommandLineWriter::getInstance();
        $obj->addText("New migration created: file " . CONFIG_PATH . "/db/" . $filename);
        $obj->write();
    }


    public function displayHelp()
    {
        $obj = CommandLineWriter::getInstance();
        $obj->addText('./migrate.php add');
        $obj->addText(' ');
        $obj->addText('This command is used to create a new migration script.  The script will be created and prepopulated with the up() and down() methods which you can then modify for the migration.');
        $obj->addText(' ');
        $obj->addText('Valid Example:');
        $obj->addText('./migrate.php add', 4);
        $obj->write();
    }

}