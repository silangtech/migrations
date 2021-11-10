<?php
declare (strict_types=1);


namespace SilangPHP\Migrate\Classes;


/**
 * The MysqliMigration is an abstract template class used as the parent to all migration classes which use mysqli.
 *
 * FILE_NAME: MysqliMigration.php
 * User: OneXian
 * Date: 2021.11.09
 */
abstract class MysqliMigration
{
    /**
     * Migrates the database up.
     *
     * @param ExceptionalMysqli $mysqli an ExceptionalMysqli object
     *
     * @return void
     */
    abstract public function up(ExceptionalMysqli &$mysqli);

    /**
     * Migrates down (reverses changes made by the up method).
     *
     * @param ExceptionalMysqli $mysqli an ExceptionalMysqli object
     *
     * @return void
     */
    abstract public function down(ExceptionalMysqli &$mysqli);
}