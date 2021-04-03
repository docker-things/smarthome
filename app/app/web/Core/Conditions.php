<?php

class Core_Conditions {
  /**
   * Keep an instance of the currently running controller
   *
   * @var Core_Controller_Base
   */
  private $_app;

  /**
   * @var array
   */
  private $_replace = [];

  /**
   * @var array
   */
  private $_replaceThis = [];

  /**
   * @var array
   */
  private $_variables = [];

  /**
   * Set the instance of the running controller
   *
   * @param Core_Controller_Base $state State object
   */
  public function __construct(Core_Controller_Base $app) {
    $this->_app = $app;
  }

  /**
   * @param array $conditions
   */
  public function check($conditions) {
    // Core_Logger::info('Core_Conditions::check();');

    // Normalize conditions syntax
    $conditions = $this->_normalizeConditions($conditions);

    // Check each condition
    foreach ($conditions AS $condition) {
      if (!$this->_checkCondition($condition)) {
        return false;
      }
    }

    // All condition checks were valid
    return true;
  }

  /**
   * @param $name
   * @param $value
   */
  public function replace(string $name, string $value) {
    $this->_replace[' ' . $name . ' '] = ' ' . $value . ' ';
  }

  /**
   * @param string $thisName
   * @param string $objectName
   */
  public function replaceThisWithObjectName(string $thisName, string $objectName) {
    $this->_replaceThis = [
      'this'       => $thisName,
      'objectName' => $objectName,
    ];
  }

  /**
   * @param string $name
   * @param mixed  $value
   */
  public function set(string $name, $value) {
    $this->_variables[$name] = $value;
  }

  /**
   * @param string $condition
   */
  private function _checkCondition(string $condition) {
    // Core_Logger::debug('Checking if: ' . $condition);

    // Try each known operator
    foreach ([
      '==',
      '!=',
      '>=',
      '>',
      '<=',
      '<',
      'not in',
      'in',
    ] AS $operator) {

      // If the operator is not present check the next one
      if (strpos($condition, ' ' . $operator . ' ') === false) {
        continue;
      }

      // Split values to be compared
      $tmp = explode(' ' . $operator . ' ', $condition);

      // Check if we got exactly 2 values
      if (count($tmp) != 2) {
        Core_Logger::error("Core_Conditions::_checkCondition(): Invalid condition! Please compare only 2 values.\n" . yaml_emit($condition));
        return false;
      }

      // Process fields
      $tmp[0] = $this->_preprocessDataField($tmp[0]);
      $tmp[1] = $this->_preprocessDataField($tmp[1]);

      // Core_Logger::debug('Real check:  ' . $tmp[0] . ' ' . $operator . ' ' . $tmp[1]);

      // Perform the required comparison
      switch ($operator) {
        case '==':
          return $tmp[0] == $tmp[1];
        case '!=':
          return $tmp[0] != $tmp[1];
        case '>=':
          if (is_numeric($tmp[0]) && is_numeric($tmp[1])) {
            return floatval($tmp[0]) >= floatval($tmp[1]);
          }
          return $tmp[0] >= $tmp[1];
        case '>':
          if (is_numeric($tmp[0]) && is_numeric($tmp[1])) {
            return floatval($tmp[0]) > floatval($tmp[1]);
          }
          return $tmp[0] > $tmp[1];
        case '<=':
          if (is_numeric($tmp[0]) && is_numeric($tmp[1])) {
            return floatval($tmp[0]) <= floatval($tmp[1]);
          }
          return $tmp[0] <= $tmp[1];
        case '<':
          if (is_numeric($tmp[0]) && is_numeric($tmp[1])) {
            return floatval($tmp[0]) < floatval($tmp[1]);
          }
          return $tmp[0] < $tmp[1];
        case 'in':
          // Make an array out of the list
          $array = explode(',', substr($tmp[1], 1, -1));

          // Transform fields
          foreach ($array AS $key => $field) {
            $array[$key] = $this->_preprocessDataField($field);
          }

          // Check
          return in_array($tmp[0], $array);
        case 'not in':
          // Make an array out of the list
          $array = explode(',', substr($tmp[1], 1, -1));

          // Transform fields
          foreach ($array AS $key => $field) {
            $array[$key] = $this->_preprocessDataField($field);
          }

          // Check
          return !in_array($tmp[0], $array);
        default:
          Core_Logger::error('Core_Conditions::_checkCondition(): Unknown operator! Please treat all the cases.');
          return false;
      }
    }

    // Got unknown operator
    Core_Logger::error('Core_Conditions::_checkCondition(): Invalid condition! Got unknown operator.');
    return false;
  }

  /**
   * @param mixed $conditions
   */
  private function _normalizeConditions($conditions) {

    // Make sure it's a string
    if (is_array($conditions)) {
      $conditions = implode(' && ', $conditions);
    }

    // Remove multiple consecutive blanks
    $conditions = ' ' . preg_replace('/[ \t\n\r]+/', ' ', $conditions) . ' ';

    // Normalize words
    $replace = array_merge($this->_replace, [
      // ' or '   => ' || ',
      '"'                     => '\'',
      ' and '                 => ' && ',
      ' is greater than '     => ' > ',
      ' is lower than '       => ' < ',
      ' is not greater than ' => ' <= ',
      ' is not lower than '   => ' >= ',
      ' is not '              => ' != ',
      ' is '                  => ' == ',
      ' greater than '        => ' > ',
      ' lower than '          => ' < ',
    ]);
    $conditions = trim(str_replace(array_keys($replace), array_values($replace), $conditions));

    // Replace 'this' with object name where needed
    if (!empty($this->_replaceThis)) {
      $thisName   = $this->_replaceThis['this'];
      $objectName = $this->_replaceThis['objectName'];
      $conditions = trim(preg_replace('/ ' . $thisName . '\.([a-zA-Z0-9]+)/', ' ' . $objectName . '.$1', ' ' . $conditions));
    }

    // Return an array of conditions
    return explode(' && ', $conditions);
  }

  /**
   * @param $field
   */
  private function _preprocessDataField($field) {

    // Initialize new value
    $newField = $field;

    // Remove quotes
    $newField = preg_replace('/^\'(.*)\'$/', '$1', $newField);

    // Check if it's a world state variable
    if (strpos($newField, '.') !== false) {

      // Split source & name
      $tmp = explode('.', $newField, 2);

      // .timeSince
      $fieldName = preg_replace('/\.timeSince$/', '', $tmp[1]);
      if ($fieldName != $tmp[1]) {
        $newField = $this->_app->getState()->getVariableTimeSince($tmp[0], $fieldName, $newField);
      }
      // .previousValue
      else {
        $fieldName = preg_replace('/\.previousValue$/', '', $tmp[1]);
        if ($fieldName != $tmp[1]) {
          $newField = $this->_app->getState()->getVariablePreviousValue($tmp[0], $fieldName, $newField);
        }
        // .objectName
        else {
          $fieldName = preg_replace('/\.objectName$/', '', $tmp[1]);
          if ($fieldName != $tmp[1]) {
            $newField = $tmp[0];
          }
          // Replace with value
          else {
            $newField = $this->_app->getState()->getVariableValue($tmp[0], $fieldName, $newField);
          }
        }
      }
    }

    // Check if we need to set variables
    if (!empty($this->_variables)) {
      foreach ($this->_variables AS $varName => $varValue) {
        if (preg_match_all('/\$\{' . $varName . '.(.*)\}/', $newField, $matches)) {

          // For each response variable needed
          for ($i = 0, $matchesCount = count($matches[1]); $i < $matchesCount; $i++) {

            // Build array path
            $outputPath = explode('.', $matches[1][$i]);

            // Get the value from the command output
            $newValue = $varValue;
            foreach ($outputPath AS $key) {

              // Stop if the key is not found
              if (!isset($newValue[$key])) {
                break;
              }

              // Set new value
              $newValue = $newValue[$key];
            }

            // If it's an array set the string 'array'
            if (is_array($newValue)) {
              $newValue = 'array';
            }

            // Replace response variable in the $newField string
            $newField = str_replace($matches[0][$i], $newValue, $newField);
          }
        }
      }
    }

    // Return new field value
    return $newField;
  }
}
