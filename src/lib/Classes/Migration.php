<?php
declare (strict_types=1);


namespace SilangPHP\Migrate\Classes;


/**
 * The Migration is an abstract template class used as the parent to all migration classes.
 *
 * FILE_NAME: Migration.php
 * User: OneXian
 * Date: 2021.11.09
 */
abstract class Migration
{
    /**
     * Migrates the database up.
     *
     * @param PDO $pdo a PDO object
     *
     * @return void
     */
    abstract public function up(\PDO &$pdo);

    /**
     * Migrates down (reverses changes made by the up method).
     *
     * @param PDO $pdo a PDO object
     *
     * @return void
     */
    abstract public function down(\PDO &$pdo);
}