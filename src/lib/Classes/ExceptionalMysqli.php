<?php
declare (strict_types=1);


namespace SilangPHP\Migrate\Classes;

use SilangPHP\Migrate\Exception\{
    DatabaseConnectionException,
    MalformedQueryException
};


/**
 * The ExceptionalMysqli class wraps the mysqli object and throws exceptions instead of triggering errors when problems occur.
*
 * FILE_NAME: ExceptionalMysqli.php
 * User: OneXian
 * Date: 2021.11.09
 */
class ExceptionalMysqli extends \mysqli
{
    /**
     * Object constructor.
     *
     * You can pass all the same parameters to this constructor as you would when instantiating a mysqli object.
     *
     * @return ExceptionalMysqli
     */
    public function __construct()
    {
        $args = func_get_args();
        eval("parent::__construct(" . join(',', array_map('\SilangPHP\Migrate\Helper\StringHelper::addSingleQuotes', $args)) . ");");
        if ($this->connect_errno)
        {
            throw new DatabaseConnectionException($this->connect_error);
        }
    }

    /**
     * Wrapper for the mysqli::query method.
     *
     * @param string $query      the SQL query to send to MySQL
     * @param int    $resultMode Either the constant MYSQLI_USE_RESULT or MYSQLI_STORE_RESULT depending on the desired behavior
     *
     * @return mysqli_result
     */
    public function query($query, $resultMode = MYSQLI_STORE_RESULT)
    {
        $result = parent::query($query, $resultMode);
        if ($this->errno)
        {
            throw new MalformedQueryException($this->error);
        }
        return $result;
    }

    /**
     * Turns off auto commit.
     *
     * @return void
     */
    public function beginTransaction()
    {
        $this->autocommit(false);
        return;
    }

    /**
     * Same as mysqli::query
     *
     * @return mysqli_result
     */
    public function exec($sql)
    {
        return $this->query($sql);
    }
}