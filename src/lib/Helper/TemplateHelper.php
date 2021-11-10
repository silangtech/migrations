<?php
declare (strict_types=1);


namespace SilangPHP\Migrate\Helper;


/**
 *
 * FILE_NAME: TemplateHelper.php
 * User: OneXian
 * Date: 2021.11.09
 */
class TemplateHelper
{
    /**
     * Returns the requested template file as an array, each item in that array is a single line from the file.
     *
     * @param string $file the filename of the template being requested
     * @param array	 $vars an array of key value pairs that correspond to variables that should be replaced in the template file
     *
     * @return array
     */
    public static function getTemplateAsArrayOfLines($file, $vars = array())
    {
        $contents = TemplateHelper::getTemplate($file, $vars);
        $arr = explode("\n", $contents);
        return $arr;
    }

    /**
     * Returns the requested template file as a string
     *
     * @param string $file the filename of the template being requested
     * @param array	 $vars an array of key value pairs that correspond to variables that should be replaced in the template file
     *
     * @return string
     */
    public static function getTemplate($file, $vars = array())
    {
        if (isset($GLOBALS['db_config']))
        {
            $db_config = $GLOBALS['db_config'];
        }
        else
        {
            $db_config = new \stdClass();
            $db_config->db_path = M_PATH . '/lib/templates/';
        }

        // has the file been customized?
        if (file_exists($db_config->db_path . $file))
        {
            $contents = file_get_contents($db_config->db_path . $file);
        }
        else
        {
            $contents = file_get_contents(M_PATH . '/lib/templates/' . $file);
        }
        foreach ($vars as $key => $val)
        {
            $contents = str_replace('@@' . $key . '@@', $val, $contents);
        }
        return $contents;
    }

}