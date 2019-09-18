<?php

class Core_State {
    /**
     * The main app controller
     *
     * @var Core_Controller_base
     */
    private $_app;

    /**
     * Array containing the current world state
     *
     * @var array
     */
    private $_currentState;

    /**
     * DB Object
     *
     * @var Core_DB
     */
    private $_db;

    /**
     * Init DB and get current state
     */
    public function __construct(Core_Controller_Base $app) {

        // Set app controller
        $this->_app = $app;

        // Init DB
        $this->_db = new Core_DB;

        // Load current state
        $this->_loadCurrentState();
    }

    public function deleteAllData() {
        $this->_db->exec("DELETE FROM `current`");
        $this->_db->exec("DELETE FROM `history`");
        $this->optimizeDB();
    }

    public function deleteHistory() {
        $this->_db->exec("DELETE FROM `history`");
        $this->optimizeDB();
    }

    /**
     * @param $object
     */
    public function deleteObjectData($object) {
        $this->_db->exec("DELETE FROM `current` WHERE source = '" . $this->_db->escape($object) . "'");
        $this->_db->exec("DELETE FROM `history` WHERE source = '" . $this->_db->escape($object) . "'");
        $this->optimizeDB();
    }

    /**
     * Check if a certain variable exists in the current world state
     *
     * @param  string $source Object name
     * @param  string $name   Variable name
     * @return int    Number of occurences
     */
    public function exists($source, $name) {
        return isset($this->_currentState[$source]) && isset($this->_currentState[$source][$name]);
    }

    /**
     * @param  array   $where
     * @param  int     $limit
     * @return mixed
     */
    public function getAllHistory(array $where = ['1'], int $limit = 100) {
        return $this->_db->query("
            SELECT
                `timestamp`,
                `source`,
                `name`,
                `value`
            FROM `history`
            WHERE " . implode(' AND ', $where) . "
            ORDER BY
                `timestamp` DESC,
                `source` ASC,
                `name` ASC,
                `value` ASC
            LIMIT
                " . $limit . "
            ");
    }

    /**
     * Get current world state with all the details
     *
     * @return array World state
     */
    public function getFullState() {
        return $this->_currentState;
    }

    /**
     * @param  array   $where
     * @param  int     $limit
     * @return mixed
     */
    public function getLogs(int $limit = 100, array $where = ['1']) {
        return $this->_db->query("
            SELECT
                FROM_UNIXTIME(`timestamp`) AS `Time`,
                `source`                   AS `Object`,
                `name`                     AS `Variable`,
                `value`                    AS `Value`
            FROM `history`
            WHERE " . implode(' AND ', $where) . "
            ORDER BY
                `timestamp` DESC,
                `source` ASC,
                `name` ASC,
                `value` ASC
            LIMIT
                " . $limit . "
            ");
    }

    /**
     * @param  source  $source
     * @param  int     $limit
     * @return mixed
     */
    public function getSourceHistory(source $source, int $limit = 100) {
        return $this->getAllHistory([
            "`source` = '" . $this->_db->escape($source) . "'",
        ], $limit);
    }

    /**
     * Get current world state in a simplified version
     *
     * @return array World state
     */
    public function getState() {
        $state = [];
        if (!empty($this->_currentState)) {
            foreach ($this->_currentState AS $object => $variables) {
                foreach ($variables AS $variable => $values) {
                    $state[$object][$variable] = $this->_currentState[$object][$variable]['value'];
                }
            }
        }
        return $state;
    }

    /**
     * Get a specific variable
     *
     * @param  string     $source  Object name
     * @param  string     $name    Variable name
     * @param  mixed      $default What to return if the variable doesn't exist
     * @return array/bool False if not set or array if set
     */
    public function getVariable($source, $name, $default = false) {
        if ($this->exists($source, $name)) {
            return $this->_currentState[$source][$name];
        }
        return $default;
    }

    /**
     * Get the history of a variable
     *
     * @param  string $source   Object name
     * @param  string $name     Variable name
     * @param  string $where    SQL conditions
     * @param  int    $limit    Number of rows
     * @return array  History
     */
    public function getVariableHistory(source $source, source $name, int $limit = 100) {
        return $this->getAllHistory([
            "`source` = '" . $this->_db->escape($source) . "'",
            "`name`   = '" . $this->_db->escape($name) . "'",
        ], $limit);
    }

    /**
     * @param  $source
     * @param  $name
     * @param  $default
     * @return mixed
     */
    public function getVariablePreviousValue($source, $name, $default = false) {
        if ($this->exists($source, $name)) {
            return $this->_currentState[$source][$name]['prevValue'];
        }
        return $default;
    }

    /**
     * @param  $source
     * @param  $name
     * @param  $default
     * @return mixed
     */
    public function getVariableTimeSince($source, $name, $default = 0) {
        if ($this->exists($source, $name)) {
            return time() - $this->_currentState[$source][$name]['timestamp'];
        }
        return $default;
    }

    /**
     * @param  $source
     * @param  $name
     * @param  $default
     * @return mixed
     */
    public function getVariableValue($source, $name, $default = false) {
        if ($this->exists($source, $name)) {
            return $this->_currentState[$source][$name]['value'];
        }
        return $default;
    }

    /**
     * @param  $username
     * @param  $password
     * @return mixed
     */
    public function isUserValid($username, $password) {
        return $this->_db->isUserValid($username, $password);
    }

    public function optimizeDB() {
        $this->_db->exec("OPTIMIZE TABLE `current`");
        $this->_db->exec("OPTIMIZE TABLE `history`");
        $this->_db->exec("PURGE BINARY LOGS BEFORE DATE(NOW() - INTERVAL 1 DAY) + INTERVAL 0 SECOND");
    }

    public function reload() {

        // Reset existing states
        $this->_currentState = [];

        // Load current state
        $this->_loadCurrentState();
    }

    /**
     * Set a certain variable in current and history
     *
     * @param string $source Variable value source
     * @param string $name   Variable name
     * @param mixed  $value  Variable value
     */
    public function set($source, $name, $value) {
        // Core_Logger::info(get_class($this) . '::set("' . $source . '", "' . $name . '", "' . $value . '");');

        if (!$this->_shouldSet($source, $name, $value)) {
            return;
        }

        // Current timestamp
        $timestamp = time();

        // Keep the previous value
        $prevValue = $this->getVariableValue($source, $name, '');

        // Set locally
        $this->_currentState[$source][$name] = [
            'value'        => $value,
            'prevValue'    => $prevValue,
            'timestamp'    => $timestamp,
            'tmpValue'     => '',
            'tmpTimes'     => 0,
            'tmpTimestamp' => 0,
        ];

        // Set current state
        $this->_db->exec("
            REPLACE INTO `current` (
                `source`,
                `name`,
                `value`,
                `prevValue`,
                `timestamp`,
                `tmpValue`,
                `tmpTimes`,
                `tmpTimestamp`
            )
            VALUES (
                '" . $this->_db->escape($source) . "',
                '" . $this->_db->escape($name) . "',
                '" . $this->_db->escape($value) . "',
                '" . $this->_db->escape($prevValue) . "',
                " . $this->_db->escape($timestamp) . ",
                '',
                0,
                0
            )
            ");

        // Add history entry
        $this->_db->exec("
            INSERT INTO `history` (
                `source`,
                `name`,
                `value`,
                `timestamp`
            )
            VALUES (
                '" . $this->_db->escape($source) . "',
                '" . $this->_db->escape($name) . "',
                '" . $this->_db->escape($value) . "',
                " . $this->_db->escape($timestamp) . "
            )
            ");

        // Initialize trigger
        $triggers = new Core_Trigger($this->_app, $source, $name, $value);

        // Process it
        $triggers->process();
    }

    /**
     * @param $source
     * @param $name
     * @param $value
     * @param $tries
     */
    public function setNthTime($source, $name, $value, $nthTime) {
        // Core_Logger::info(get_class($this) . '::setNthTime("' . $source . '", "' . $name . '", "' . $value . '", "' . $nthTime . '");');

        if (!$this->_shouldSet($source, $name, $value)) {
            return;
        }

        // Current timestamp
        $timestamp = time();

        // Get variable with a default value if not found
        $variable = $this->getVariable($source, $name, [
            'value'        => '',
            'timestamp'    => 0,
            'tmpValue'     => '',
            'tmpTimes'     => 0,
            'tmpTimestamp' => 0,
        ]);

        // If the tmp value has changed reset counter
        if ($variable['tmpValue'] != $value) {
            // Core_Logger::debug(get_class($this) . '::setNthTime(): If the tmp value has changed reset counter');
            $variable['tmpValue']     = $value;
            $variable['tmpTimes']     = 1;
            $variable['tmpTimestamp'] = $timestamp;
        }

        // If it's the same value but we didn't reach the number of times
        elseif (++$variable['tmpTimes'] < $nthTime) {
            // Core_Logger::debug(get_class($this) . '::setNthTime(): If it\'s the same value but we didn\'t reach the number of times');
            $variable['tmpValue']     = $value;
            $variable['tmpTimestamp'] = $timestamp;
        }

        // If the value should be set now
        elseif ($variable['tmpTimes'] >= $nthTime) {
            // Core_Logger::debug(get_class($this) . '::setNthTime(): If the value should be set now');
            $variable['tmpValue']     = '';
            $variable['tmpTimes']     = 0;
            $variable['tmpTimestamp'] = 0;
        }

        // Something went very wrong
        else {
            Core_Logger::error(get_class($this) . '::setNthTime("' . $source . '", "' . $name . '", "' . $value . '", "' . $nthTime . '");');
            Core_Logger::error(get_class($this) . '::setNthTime(): This should never happen!');
            return;
        }

        // Set locally
        $this->_currentState[$source][$name] = $variable;

        // Set current state
        $this->_db->exec("
            REPLACE INTO `current` (
                `source`,
                `name`,
                `value`,
                `timestamp`,
                `tmpValue`,
                `tmpTimes`,
                `tmpTimestamp`
            )
            VALUES (
                '" . $this->_db->escape($source) . "',
                '" . $this->_db->escape($name) . "',
                '" . $this->_db->escape($variable['value']) . "',
                " . $this->_db->escape($variable['timestamp']) . ",
                '" . $this->_db->escape($variable['tmpValue']) . "',
                " . $this->_db->escape($variable['tmpTimes']) . ",
                " . $this->_db->escape($variable['tmpTimestamp']) . "
            )
            ");

        // Actually set the value and launch the triggers
        if (0 == $variable['tmpTimes']) {
            $this->set($source, $name, $value);
        }
    }

    /**
     * @param  $username
     * @param  $password
     * @return mixed
     */
    public function setPassword($username, $password) {
        return $this->_db->setPassword($username, $password);
    }

    /**
     * Load current world state
     */
    private function _loadCurrentState() {
        $result = $this->_db->query("SELECT *  FROM `current` ORDER BY `source`, `name`");
        if (!empty($result)) {
            foreach ($result AS $row) {
                $this->_currentState[$row['source']][$row['name']] = [
                    'value'        => $row['value'],
                    'prevValue'    => $row['prevValue'],
                    'timestamp'    => $row['timestamp'],
                    'tmpValue'     => $row['tmpValue'],
                    'tmpTimes'     => $row['tmpTimes'],
                    'tmpTimestamp' => $row['tmpTimestamp'],
                ];
            }
        }
    }

    /**
     * @param $source
     * @param $name
     * @param $value
     */
    private function _shouldSet($source, $name, $value) {

        // Don't set value if any of the params is empty
        if (empty($source) || empty($name) || '' === $value) {
            return false;
        }

        // Got same value as in the DB
        $currentValue = $this->getVariableValue($source, $name);
        if (false !== $currentValue && $currentValue == $value) {

            // Get the object involved
            $object = $this->_app->getConfig()->getObjectByName($source);

            // If the variable should be set every time regardless of the value
            $alwaysSetWhenReceived = false !== $object &&
            isset($object['Incoming']['alwaysSetWhenReceived']) &&
                $object['Incoming']['alwaysSetWhenReceived'];

            // Stop the method if needed
            if (!$alwaysSetWhenReceived) {
                return false;
            }
        }

        return true;
    }
}
