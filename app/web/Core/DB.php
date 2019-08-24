<?php

/**
 * SQLite3 wrapper
 */
class MariaDB {
    /**
     * @var mixed
     */
    private $_connection;

    /**
     * @var string
     */
    private $_db = 'smarthome';

    /**
     * @var string
     */
    private $_host = '127.0.0.1';

    /**
     * @var string
     */
    private $_pass = '';

    /**
     * @var string
     */
    private $_user = 'root';

    /**
     * Initialize the proper file
     */
    public function __construct() {

        do {
            // Connect
            $this->_connection = mysqli_connect($this->_host, $this->_user, $this->_pass, $this->_db);

            // Check connection
            if (mysqli_connect_errno()) {
                Core_Logger::error('MySQL connection failed: ' . mysqli_connect_error());
                Core_Logger::error('Will wait for 5 seconds');
                sleep(5);
            }
        } while (mysqli_connect_errno());

    }

    public function close() {
        if ($this->_connection) {
            $this->_connection->close();
        }
    }

    /**
     * @param string $string
     */
    public function escapeString($string) {
        return $this->_connection->real_escape_string($string);
    }

    /**
     * @param  $sql
     * @return mixed
     */
    public function exec($sql) {
        $this->_connection->query($sql);
        if (mysqli_errno($this->_connection)) {
            Core_Logger::error(mysqli_error($this->_connection));
            die;
        }
    }

    /**
     * @return string
     */
    public function getDatabaseName() {
        return $this->_db;
    }

    // public function lastErrorMsg() {}

    /**
     * @param $sql
     */
    public function query($sql) {
        try {
            $data = [];
            if ($result = $this->_connection->query($sql)) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $result->close();
            } elseif (mysqli_errno($this->_connection)) {
                Core_Logger::error(mysqli_error($this->_connection));
                die;
            }
            return $data;
        } catch (Exception $e) {
            Core_Logger::error($e);
            return [];
        }
    }

    /**
     * @param $sql
     */
    public function queryReturnsSomething($sql) {
        try {
            if ($result = $this->_connection->query($sql)) {
                while ($row = $result->fetch_row()) {
                    $result->close();
                    return true;
                }
            } elseif (mysqli_errno($this->_connection)) {
                Core_Logger::error(mysqli_error($this->_connection));
                die;
            }
            return false;
        } catch (Exception $e) {
            Core_Logger::error($e);
            return false;
        }
    }
}

/**
 * The actual DB class used through the app
 */
class Core_DB {
                /**
     * @var mixed
     */
    public $db = null;

    /**
     * Keep the number of runs of exec()
     *
     * @var integer
     */
    private $_execCounter = 0;

    /**
     * Keep the number of runs of query()
     *
     * @var integer
     */
    private $_queryCounter = 0;

    /**
     * Connect and create tables
     */
    public function __construct() {

        // Connect
        $this->db = new MariaDB();

        // Create tables if needed
        $this->_createTables();
    }

    /**
     * Disconnect
     */
    public function __destruct() {
        if ($this->db) {
            $this->db->close();
        }
    }

    /**
     * Escape string to be included in SQL Query
     *
     * @param  string $value  String value
     * @return string Escaped string value
     */
    public function escape($value) {
        return $this->db->escapeString($value);
    }

    /**
     * Run query without expecting a result
     *
     * @param string $sql SQL Query
     */
    public function exec($sql) {
        // Run query
        $this->db->exec($sql);

        // Increment the number of runs
        $this->_queryCounter++;
    }

    /**
     * @param  $username
     * @param  $password
     * @return mixed
     */
    public function isUserValid($username, $password) {
        return $this->db->queryReturnsSomething("
            SELECT 1
            FROM   `users`
            WHERE  `username` = '" . $this->escape($username) . "' AND
                   `password` = '" . md5($password) . "'
            LIMIT  1
            ");
    }

    /**
     * Return the number of runs of exec()
     *
     * @return int Number of runs
     */
    public function numberOfChanges() {
        return $this->_execCounter;
    }

    /**
     * Run query expecting data from the DB
     *
     * @param  string        $sql   SQL Query
     * @return SQLite3Result Data
     */
    public function query($sql) {
        // Run query
        $result = $this->db->query($sql);

        // Increment the number of runs
        $this->_queryCounter++;

        // Return the query result
        return $result;
    }

    /**
     * @param $username
     * @param $password
     */
    public function setPassword($username, $password) {
        $this->exec("
            UPDATE `users` SET
                `password` = '" . md5($password) . "'
            WHERE
                `username` = '" . $this->escape($username) . "'
            ");
    }

    /**
     * Check if $field exists with $value in $table
     *
     * @param  string $field Column name
     * @param  string $value Column value
     * @param  string $table Table name
     * @return bool   True if exists
     */
    public function valueExists($field, $value, $table) {
        return $this->db->queryReturnsSomething("
            SELECT 1
            FROM   `" . $table . "`
            WHERE  `" . $field . "` = '" . $this->escape($value) . "'
            LIMIT  1
            ");
    }

    /**
     * Create the necessary tables
     */
    private function _createTables() {

        // $this->exec("DROP TABLE `history`");
        // $this->exec("DROP TABLE `current`");
        // $this->exec("DROP TABLE `users`");

        if (!$this->_tableExists('current')) {
            $this->exec("
                CREATE TABLE `current` (
                    `source`       VARCHAR(255) NOT NULL,
                    `name`         VARCHAR(255) NOT NULL,
                    `value`        VARCHAR(255) NOT NULL,
                    `prevValue`    VARCHAR(255) NOT NULL,
                    `timestamp`    INT          NOT NULL,
                    `tmpValue`     VARCHAR(255) NOT NULL,
                    `tmpTimes`     INT          NOT NULL,
                    `tmpTimestamp` INT          NOT NULL
                )
                ");
            $this->exec("
                CREATE UNIQUE INDEX `source_name_unique` ON `current` (`source` ASC, `name` ASC)
                ");
        }
        if (!$this->_tableExists('history')) {
            $this->exec("
                CREATE TABLE `history` (
                    `source`    VARCHAR(255) NOT NULL,
                    `name`      VARCHAR(255) NOT NULL,
                    `value`     VARCHAR(255) NOT NULL,
                    `timestamp` INT          NOT NULL
                )
                ");
            $this->exec("
                CREATE INDEX `source_name_time_unique` ON `history` (`source` ASC, `name` ASC, `timestamp` DESC)
                ");
        }
        if (!$this->_tableExists('users')) {
            $this->exec("
                CREATE TABLE `users` (
                    `username` VARCHAR(255) NOT NULL,
                    `password` VARCHAR(255) NOT NULL
                )
                ");
            $this->exec("
                CREATE INDEX `username_unique` ON `users` (`username` ASC)
                ");
            $this->exec("
                INSERT INTO `users` (`username`,`password`)
                VALUES ('admin','" . md5('smarthome') . "')
                ");
        }
    }

    /**
     * Check if $table exists in the DB
     *
     * @param  string $table Table name
     * @return bool   True if exists
     */
    private function _tableExists($table) {
        return $this->db->queryReturnsSomething("
            SELECT table_name
            FROM   information_schema.tables
            WHERE  table_schema = '" . $this->db->getDatabaseName() . "' AND table_name = '" . $table . "'
            LIMIT  1
            ");
    }
}
