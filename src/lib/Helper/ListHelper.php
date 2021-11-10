<?php
declare (strict_types=1);


namespace SilangPHP\Migrate\Helper;


/**
 *
 * FILE_NAME: ListHelper.php
 * User: OneXian
 * Date: 2021.11.09
 */
class ListHelper
{
    /**
     * Returns the total number of migrations available.
     *
     * @return int
     */
    public static function getTotalMigrations()
    {
        $db_config        = $GLOBALS['db_config'];
        $migrations_table = $db_config->migrations_table;
        $sql              = "SELECT COUNT(*) AS total FROM `{$migrations_table}`";
        $obj              = DbHelper::doSingleRowSelect($sql);
        return $obj->total;
    }

    /**
     * Returns a full list of all migrations.
     *
     * @param int $startIdx the start index number
     * @param int $total total number of records to return
     *
     * @return arrays
     */
    public static function getFullList($startIdx = 0, $total = 30)
    {
        $db_config        = $GLOBALS['db_config'];
        $migrations_table = $db_config->migrations_table;
        $list             = array();
        $sql              = "SELECT * FROM `{$migrations_table}` ORDER BY `timestamp`";
        if ($total > 0) {
            $sql .= " LIMIT $startIdx,$total";
        }
        $list = DbHelper::doMultiRowSelect($sql);
        return $list;
    }

    /**
     * Fetches a list of files and adds migrations to the database migrations table.
     *
     * @return void
     */
    public static function mergeFilesWithDb()
    {
        $db_config        = $GLOBALS['db_config'];
        $migrations_table = $db_config->migrations_table;
        $files            = ListHelper::getListOfFiles();
        $total_migrations = ListHelper::getTotalMigrations();
        $db_list          = ListHelper::getFullList(0, $total_migrations);
        $file_timestamps  = ListHelper::getTimestampArray($files);

        if (DbHelper::getMethod() == M_METHOD_PDO) {
            $pdo = DbHelper::getPdoObj();
            $pdo->beginTransaction();

            if (count($files) > 0) {
                try {
                    foreach ($files as $file) {
                        $sql = "INSERT IGNORE INTO `{$migrations_table}` ( `timestamp`, `active`, `is_current` ) VALUES ( '{$file->timestamp}', 0, 0 )";
                        $pdo->exec($sql);
                    }
                } catch (Exception $e) {
                    $pdo->rollback();
                    echo "\n\nError: " . $e->getMessage();
                    echo "\n\n";
                    exit;
                }
            }
            if (count($db_list)) {
                try {
                    foreach ($db_list as $obj) {
                        if (!in_array($obj->timestamp, $file_timestamps) && $obj->active == 0) {
                            $sql = "DELETE FROM `{$migrations_table}` WHERE `id` = '{$obj->id}'";
                            $pdo->exec($sql);
                        }
                    }
                } catch (\Exception $e) {
                    $pdo->rollback();
                    echo "\n\nError: " . $e->getMessage();
                    echo "\n\n";
                    exit;
                }
            }
            $pdo->commit();

        } else {
            $mysqli = DbHelper::getMysqliObj();
            $mysqli->autocommit(false);

            if (count($files) > 0) {
                try {
                    $stmt = $mysqli->prepare('INSERT IGNORE INTO `' . $migrations_table . '` ( `timestamp`, `active`, `is_current` ) VALUES ( ?, 0, 0 )');
                    foreach ($files as $file) {
                        $stmt->bind_param('s', $file->timestamp);
                        $result = $stmt->execute();
                        if ($result === false) {
                            throw new \Exception('Unable to execute query to update file list.');
                        }
                    }
                } catch (\Exception $e) {
                    $mysqli->rollback();
                    $mysqli->close();
                    echo "\n\nError:", $e->getMessage(), "\n\n";
                    exit;
                }
            }
            if (count($db_list)) {
                try {
                    $stmt = $mysqli->prepare('DELETE FROM `' . $migrations_table . '` WHERE `id` = ?');
                    foreach ($db_list as $obj) {
                        if (!in_array($obj->timestamp, $file_timestamps) && $obj->active == 0) {
                            $stmt->bind_param('i', $obj->id);
                            $result = $stmt->execute();
                            if ($result === false) {
                                throw new \Exception('Unable to execute query to remove stale files from the list.');
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $mysqli->rollback();
                    $mysqli->close();
                    echo "\n\nError: " . $e->getMessage();
                    echo "\n\n";
                    exit;
                }
            }
            $mysqli->commit();
            $mysqli->close();
        }
    }

    /**
     * Given an array of objects (from the getFullList() or getListOfFiles() methods), returns an array of timestamps.
     *
     * @return array
     */
    public static function getTimestampArray($obj_array)
    {
        $timestamp_array = array();
        foreach ($obj_array as $obj) {
            $timestamp_array[] = str_replace('T', ' ', $obj->timestamp);
        }
        return $timestamp_array;
    }

    /**
     * Returns an array of objects which hold data about a migration file (timestamp, file, etc.).
     *
     * @param string $sort should either be old or new; determines how the migrations are sorted in the array
     *
     * @return array
     */
    public static function getListOfFiles($sort = 'old')
    {
        $list = array();
        if ($sort == 'new') {
            $sort_order = 1;
        } else {
            $sort_order = 0;
        }
        $files = scandir(M_DB_PATH, $sort_order);
        foreach ($files as $file) {
            $full_file = M_DB_PATH . $file;
            if ($file != 'schema.php' && $file != '.' && $file != '..' && !is_dir($full_file) && stripos($full_file, '.php') !== false) {
                $timestamp = StringHelper::getTimestampFromFilename($file);
                if ($timestamp !== null) {
                    $obj            = (object)array();
                    $obj->timestamp = $timestamp;
                    $obj->filename  = $file;
                    $obj->full_file = $full_file;
                    $list[]         = $obj;
                }
            }
        }
        return $list;
    }

    /**
     * Returns an array of migration filenames.
     *
     * @return array
     */
    public static function getFiles()
    {
        $files = array();
        $list  = ListHelper::getListOfFiles();
        foreach ($list as $obj) {
            $files[] = $obj->filename;
        }
        return $files;
    }

    /**
     * Fetches a list of migrations which have already been run.
     *
     *
     * @param string $latestTimestamp the current timestamp of the migration run last
     * @param string $direction the way we are migrating; should either be up or down
     *
     * @return array
     */
    public static function getListFromDb($latestTimestamp, $direction = 'up')
    {
        $db_config        = $GLOBALS['db_config'];
        $migrations_table = $db_config->migrations_table;
        if ($direction == 'down') {
            $sql      = "SELECT * FROM `{$migrations_table}` WHERE `timestamp` <= '$latestTimestamp' AND `active` = 1";
            $countSql = "SELECT COUNT(*) as total FROM `{$migrations_table}` WHERE `timestamp` <= '$latestTimestamp' AND `active` = 1";
        } else {
            $sql      = "SELECT * FROM `{$migrations_table}` WHERE `timestamp` >= '$latestTimestamp' AND `active` = 1";
            $countSql = "SELECT COUNT(*) as total FROM `{$migrations_table}` WHERE `timestamp` >= '$latestTimestamp' AND `active` = 1";
        }
        $list     = array();
        $countObj = DbHelper::doSingleRowSelect($countSql);
        if ($countObj->total > 0) {
            $results = DbHelper::doMultiRowSelect($sql);
            foreach ($results as $obj) {
                $list[] = $obj->timestamp;
            }
        }
        return $list;
    }
}