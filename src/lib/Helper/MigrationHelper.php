<?php
declare (strict_types=1);


namespace SilangPHP\Migrate\Helper;

use SilangPHP\Migrate\Classes\{
    CommandLineWriter,
    Migration
};

/**
 *
 * FILE_NAME: MigrationHelper.php
 * User: OneXian
 * Date: 2021.11.09
 */
class MigrationHelper
{
    /**
     * Sets the current active migration.
     *
     * @param int $id the ID of the migration to set as the current one
     *
     * @return void
     */
    public static function setCurrentMigration($id)
    {
        $db_config        = $GLOBALS['db_config'];
        $migrations_table = $db_config->migrations_table;
        $sql1             = "UPDATE `{$migrations_table}` SET `is_current` = '0'";
        $sql2             = "UPDATE `{$migrations_table}` SET `is_current` = '1' WHERE `id` = {$id}";
        $obj              = DbHelper::getDbObj();
        $obj->beginTransaction();
        try {
            $obj->exec($sql1);
            $obj->exec($sql2);
        } catch (\Exception $e) {
            $obj->rollback();
            echo "\n\tQuery failed!";
            echo "\n\t--- " . $e->getMessage();
            exit;
        }
        $obj->commit();
    }


    /**
     * Performs a single migration.
     *
     * @param object $obj a simple object with migration information (from a migration list)
     * @param int    &$total_migrations_run a running total of migrations run
     * @param bool $forced if true, exceptions will not cause the script to exit
     *
     * @return void
     */
    public static function runMigration(&$obj, $method = 'up', $forced = false)
    {
        $db_config        = $GLOBALS['db_config'];
        $migrations_table = $db_config->migrations_table;
        $filename         = StringHelper::getFilenameFromTimestamp($obj->timestamp);
        $classname        = 'Migration_' . str_replace('.php', '', $filename);

        // make sure the file exists; if it doesn't, skip it but display a message
        if (!file_exists(M_DB_PATH . $filename)) {
            echo "\n\tMigration " . $obj->timestamp . ' (ID ' . $obj->id . ') skipped - file missing.';
            return;
        }

        // file exists -- run the migration
        echo "\n\tPerforming " . strtoupper($method) . " migration " . $obj->timestamp . ' (ID ' . $obj->id . ')... ';
        require_once(M_DB_PATH . $filename);
        $migration = new $classname();
        if ($migration instanceof Migration) // need PDO object
        {
            $dbObj = DbHelper::getPdoObj();
        } else {
            $dbObj = DbHelper::getMysqliObj();
        }
        $dbObj->beginTransaction();
        if ($method == 'down') {
            $active = 0;
        } else {
            $active = 1;
        }
        try {
            $migration->$method($dbObj);
            $sql = "UPDATE `{$migrations_table}` SET `active` = '$active', `update_time` = NOW() WHERE `id` = {$obj->id}";
            $dbObj->exec($sql);

        } catch (\Exception $e) {

            $dbObj->rollback();
            echo "failed!";
            echo "\n";
            $clw = CommandLineWriter::getInstance();
            $clw->writeLine($e->getMessage(), 12);
            if (!$forced) {
                echo "\n\n";
                exit;
            } else {
                return;
            }
        }

        $dbObj->commit();
        echo "done.";
    }

    /**
     * Returns the timestamp of the migration currently rolled to.
     *
     * @return string
     */
    public static function getCurrentMigrationTimestamp()
    {
        $db_config        = $GLOBALS['db_config'];
        $migrations_table = $db_config->migrations_table;

        // Resolution to Issue #1 - PDO::rowCount is not reliable
        $sql1  = "SELECT COUNT(*) as total FROM `{$migrations_table}` WHERE `is_current` = 1";
        $sql2  = "SELECT `timestamp` FROM `{$migrations_table}` WHERE `is_current` = 1";
        $dbObj = DbHelper::getDbObj();
        switch (DbHelper::getMethod()) {
            case M_METHOD_PDO:
                $stmt = $dbObj->query($sql1);
                if ($stmt->fetchColumn() == 0) {
                    return false;
                }
                unset($stmt);
                $stmt   = $dbObj->query($sql2);
                $row    = $stmt->fetch(\PDO::FETCH_ASSOC);
                $latest = $row['timestamp'];
                break;
            case M_METHOD_MYSQLI:
                $result = $dbObj->query($sql1);
                $row    = $result->fetch_object();
                if ($row->total == 0) {
                    return false;
                }
                $result->close();
                unset($result);
                $result = $dbObj->query($sql2);
                $row    = $result->fetch_object();
                $latest = $row->timestamp;
                break;
        }
        return $latest;
    }

    /**
     * Returns an array of migrations which need to be run (in order).
     *
     * @param int $toId the ID of the migration to stop on
     * @param string $direction the direction of the migration; should be 'up' or 'down'
     *
     * @return array
     */
    public static function getListOfMigrations($toId, $direction = 'up')
    {
        $db_config        = $GLOBALS['db_config'];
        $migrations_table = $db_config->migrations_table;
        $list             = array();
        $timestamp        = MigrationHelper::getTimestampFromId($toId);
        if ($direction == 'up') {
            $sql = "SELECT `id`, `timestamp` FROM `{$migrations_table}` WHERE `active` = 0 AND `timestamp` <= '$timestamp' ORDER BY `timestamp`";
        } else {
            $sql = "SELECT `id`, `timestamp` FROM `{$migrations_table}` WHERE `active` = 1 AND `timestamp` > '$timestamp' ORDER BY `timestamp` DESC";
        }
        switch (DbHelper::getMethod()) {
            case M_METHOD_PDO:
                try {
                    $pdo  = DbHelper::getPdoObj();
                    $stmt = $pdo->query($sql);
                    while ($obj = $stmt->fetch(\PDO::FETCH_OBJ)) {
                        $list[$obj->id] = $obj;
                    }
                } catch (\Exception $e) {
                    echo "\n\nError: " . $e->getMessage() . "\n\n";
                    exit;
                }
                break;
            case M_METHOD_MYSQLI:
                try {
                    $mysqli  = DbHelper::getMysqliObj();
                    $results = $mysqli->query($sql);
                    while ($row = $results->fetch_object()) {
                        $list[$row->id] = $row;
                    }
                } catch (\Exception $e) {
                    echo "\n\nError: " . $e->getMessage() . "\n\n";
                    exit;
                }
                break;

        }
        return $list;
    }

    /**
     * Returns a timestamp when given a migration ID number.
     *
     * @param int $id the ID number of the migration
     *
     * @return string
     */
    public static function getTimestampFromId($id)
    {
        $db_config        = $GLOBALS['db_config'];
        $migrations_table = $db_config->migrations_table;
        try {
            switch (DbHelper::getMethod()) {
                case M_METHOD_PDO:
                    // Resolution to Issue #1 - PDO::rowCount is not reliable
                    $pdo  = DbHelper::getPdoObj();
                    $sql  = "SELECT COUNT(*) FROM `{$migrations_table}` WHERE `id` = '$id'";
                    $stmt = $pdo->query($sql);
                    if ($stmt->fetchColumn() == 1) {
                        unset($stmt);
                        $sql       = "SELECT `timestamp` FROM `{$migrations_table}` WHERE `id` = '$id'";
                        $stmt      = $pdo->query($sql);
                        $result    = $stmt->fetch(\PDO::FETCH_OBJ);
                        $timestamp = $result->timestamp;
                    } else {
                        $timestamp = false;
                    }
                    break;
                case M_METHOD_MYSQLI:
                    $mysqli = DbHelper::getMysqliObj();
                    $sql    = "SELECT COUNT(*) as total FROM `{$migrations_table}` WHERE `id` = '$id'";
                    $stmt   = $mysqli->query($sql);
                    $row    = $stmt->fetch_object();
                    if ($row->total == 1) {
                        $stmt->close();
                        unset($stmt);
                        $sql       = "SELECT `timestamp` FROM `{$migrations_table}` WHERE `id` = '$id'";
                        $stmt      = $mysqli->query($sql);
                        $result    = $stmt->fetch_object();
                        $timestamp = $result->timestamp;
                        $stmt->close();
                        $mysqli->close();
                    } else {
                        $timestamp = false;
                    }
                    break;
            }
        } catch (\Exception $e) {
            echo "\n\nERROR: " . $e->getMessage() . "\n\n";
            exit;
        }
        return $timestamp;
    }

    /**
     * Returns the number of the migration currently rolled to.
     *
     * @return string
     */
    public static function getCurrentMigrationNumber()
    {
        $db_config        = $GLOBALS['db_config'];
        $migrations_table = $db_config->migrations_table;
        try {
            switch (DbHelper::getMethod()) {
                case M_METHOD_PDO:
                    $pdo = DbHelper::getDbObj();
                    // Resolution to Issue #1 - PDO::rowCount is not reliable
                    $sql  = "SELECT COUNT(*) FROM `{$migrations_table}` WHERE `is_current` = 1";
                    $stmt = $pdo->query($sql);
                    if ($stmt->fetchColumn() == 0) {
                        return false;
                    }
                    $sql = "SELECT `id` FROM `{$migrations_table}` WHERE `is_current` = 1";
                    unset($stmt);
                    $stmt   = $pdo->query($sql);
                    $row    = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $latest = $row['id'];
                    break;
                case M_METHOD_MYSQLI:
                    $mysqli = DbHelper::getDbObj();
                    $sql    = "SELECT COUNT(*) as total FROM `{$migrations_table}` WHERE `is_current` = 1";
                    $stmt   = $mysqli->query($sql);
                    $row    = $stmt->fetch_object();
                    if ($row->total == 0) {
                        return false;
                    }
                    $stmt->close();
                    unset($stmt);
                    $sql    = "SELECT `id` FROM `{$migrations_table}` WHERE `is_current` = 1";
                    $stmt   = $mysqli->query($sql);
                    $row    = $stmt->fetch_object();
                    $latest = $row->id;
                    $stmt->close();
                    $mysqli->close();
                    break;
            }
        } catch (\Exception $e) {
            echo "\n\nERROR: " . $e->getMessage() . "\n\n";
            exit;
        }
        return $latest;
    }

    /**
     * Returns the total number of migrations.
     *
     * @return int
     */
    public static function getMigrationCount()
    {
        $db_config        = $GLOBALS['db_config'];
        $migrations_table = $db_config->migrations_table;
        try {
            switch (DbHelper::getMethod()) {
                case M_METHOD_PDO:
                    $pdo = DbHelper::getDbObj();
                    // Resolution to Issue #1 - PDO::rowCount is not reliable
                    $sql   = "SELECT COUNT(id) FROM `{$migrations_table}`";
                    $stmt  = $pdo->query($sql);
                    $count = $stmt->fetchColumn();
                    break;
                case M_METHOD_MYSQLI:
                    $mysqli = DbHelper::getDbObj();
                    $sql    = "SELECT COUNT(id) AS total FROM `{$migrations_table}`";
                    $stmt   = $mysqli->query($sql);
                    $row    = $stmt->fetch_object();
                    $count  = $row->total;
                    break;
            }
        } catch (\Exception $e) {
            echo "\n\nERROR: " . $e->getMessage() . "\n\n";
            exit;
        }
        return $count;
    }

    /**
     * Returns the ID of the latest migration.
     *
     * @return int
     */
    public static function getLatestMigration()
    {
        $db_config        = $GLOBALS['db_config'];
        $migrations_table = $db_config->migrations_table;
        $sql              = "SELECT `id` FROM `{$migrations_table}` ORDER BY `timestamp` DESC LIMIT 0,1";
        try {
            switch (DbHelper::getMethod()) {
                case M_METHOD_PDO:
                    $pdo    = DbHelper::getDbObj();
                    $stmt   = $pdo->query($sql);
                    $result = $stmt->fetch(\PDO::FETCH_OBJ);
                    $to_id  = $result->id;
                    break;
                case M_METHOD_MYSQLI:
                    $mysqli = DbHelper::getDbObj();
                    $stmt   = $mysqli->query($sql);
                    $result = $stmt->fetch_object();
                    $to_id  = $result->id;
                    break;
            }
        } catch (\Exception $e) {
            echo "\n\nERROR: " . $e->getMessage() . "\n\n";
            exit;
        }
        return $to_id;
    }

    /**
     * Checks to see if a migration with the given ID actually exists.
     *
     * @param int $id the ID of the migration
     *
     * @return int
     */
    public static function doesMigrationExist($id)
    {
        $db_config        = $GLOBALS['db_config'];
        $migrations_table = $db_config->migrations_table;
        $sql              = "SELECT COUNT(*) as total FROM `{$migrations_table}` WHERE `id` = '$id'";
        $return           = false;
        try {
            switch (DbHelper::getMethod()) {
                case M_METHOD_PDO:
                    $pdo    = DbHelper::getDbObj();
                    $stmt   = $pdo->query($sql);
                    $result = $stmt->fetch(\PDO::FETCH_OBJ);
                    if ($result->total > 0) {
                        $return = true;
                    }
                    break;
                case M_METHOD_MYSQLI:
                    $mysqli = DbHelper::getDbObj();
                    $stmt   = $mysqli->query($sql);
                    $result = $stmt->fetch_object();
                    if ($result->total > 0) {
                        $return = true;
                    }
                    break;
            }
        } catch (\Exception $e) {
            echo "\n\nERROR: " . $e->getMessage() . "\n\n";
            exit;
        }
        return $return;
    }

    /**
     * Returns a migration object; this object contains all data stored in the DB for the particular migration ID.
     *
     * @param int $id the ID of the migration
     *
     * @return object
     */
    public static function getMigrationObject($id)
    {
        $db_config        = $GLOBALS['db_config'];
        $migrations_table = $db_config->migrations_table;
        $sql              = "SELECT * FROM `{$migrations_table}` WHERE `id` = '$id'";
        $obj              = null;
        try {
            switch (DbHelper::getMethod()) {
                case M_METHOD_PDO:
                    $pdo  = DbHelper::getDbObj();
                    $stmt = $pdo->query($sql);
                    $obj  = $stmt->fetch(\PDO::FETCH_OBJ);
                    break;
                case M_METHOD_MYSQLI:
                    $mysqli = DbHelper::getDbObj();
                    $stmt   = $mysqli->query($sql);
                    $obj    = $stmt->fetch_object();
                    break;
            }
        } catch (\Exception $e) {
            echo "\n\nERROR: " . $e->getMessage() . "\n\n";
            exit;
        }
        return $obj;
    }
}