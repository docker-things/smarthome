<?php

class Core_Trigger {
  /**
   * The main app controller
   *
   * @var Core_Controller_base
   */
  private $_app;

  /**
   * @var string
   */
  private $_name;

  /**
   * @var string
   */
  private $_source;

  /**
   * @var mixed
   */
  private $_value;

  /**
   * @param Core_Controller_Base $app
   */
  public function __construct(Core_Controller_Base $app, $source, $name, $value) {
    // Core_Logger::info('Core_Trigger("' . $source . '", "' . $name . '", "' . $value . '");');

    // Set app controller
    $this->_app = $app;

    // Set trigger source
    $this->_source = $source;

    // Set trigger name
    $this->_name = $name;

    // Set trigger value
    $this->_value = $value;
  }

  public function process() {
    // Core_Logger::info('Core_Trigger::process();');

    // Get triggers
    $triggers = $this->_app->getConfig()->getTriggersFor($this->_source, $this->_name);

    // Stop execution if no trigger found
    if (false === $triggers) {
      // Core_Logger::info('Core_Trigger::process(): No triggers found');
      return false;
    }

    // Keep triggers with valid conditions
    $triggers = $this->_keepTriggersWithValidConditions($triggers);

    // Stop execution if no trigger left
    if (empty($triggers)) {
      // Core_Logger::info('Core_Trigger::process(): No triggers met the conditions');
      return false;
    }

    // Set new states
    $this->_setNewStates($triggers);

    // Run functions
    $this->_runFunctions($triggers);

    // Inform that at least a trigger was executed
    return true;
  }

  /**
   * @param  array   $triggers
   * @return array
   */
  private function _keepTriggersWithValidConditions(array $triggers) {
    // Core_Logger::info('Core_Trigger::_keepTriggersWithValidConditions();');

    // Initialize conditions checking object
    $conditions = new Core_Conditions($this->_app);
    $conditions->replace('this', $this->_value);
    $conditions->replaceThisWithObjectName('this', $this->_source);

    // Will keep all the valid triggers
    $validTriggers = [];

    // Go through each trigger
    foreach ($triggers AS $trigger) {

      // If there's no condition assume we always trigger this
      $valid = false;
      if (!isset($trigger['if'])) {
        $valid = true;
      }
      // Otherwise check if the conditions are valid
      else {
        $conditionsToCheck = [
          $trigger['if'],
        ];
        if (isset($trigger['elseIf'])) {
          $conditionsToCheck[] = $trigger['elseIf'];
          for ($i = 2; $i < 10; $i++) {
            if (isset($trigger['elseIf-' . $i])) {
              $conditionsToCheck[] = $trigger['elseIf-' . $i];
            } else {
              break;
            }
          }
        }
        for ($i = 0; $i < count($conditionsToCheck); $i++) {
          if ($conditions->check($conditionsToCheck[$i])) {
            $valid = true;
            break;
          }
        }
      }

      // Append trigger if valid
      if ($valid) {
        $validTriggers[] = $trigger;
      }
    }

    // Return all the valid triggers
    return $validTriggers;
  }

  /**
   * @param $function
   */
  private function _runFunction($function) {
    $cmd = "mosquitto_pub -h mqtt -t 'core-function/run' -m '" . urlencode($function) . "'";
    ob_start();
    system($cmd . ' 2>&1 &', $retval);
    ob_end_clean();
    // $function = new Core_Function($this->_app, $function);
    // $function->process();
  }

  /**
   * @param $triggers
   */
  private function _runFunctions($triggers) {
    // Core_Logger::info('Core_Trigger::_runFunctions();');

    // Parse each trigger
    foreach ($triggers AS $trigger) {

      // Skip trigger if there's nothing to run
      if (!isset($trigger['run'])) {
        continue;
      }

      // Set functions variable
      $functions = $trigger['run'];

      // Make sure we have an array of functions
      if (!is_array($functions)) {
        $functions = [$functions];
      }

      // Replace this with object name (useful for module defined triggers)
      foreach ($functions AS $i => $function) {
        $functions[$i] = trim(preg_replace('/ this\.([a-zA-Z0-9]+)/', $this->_source . '.$1', ' ' . $function));
      }

      // Run each function
      if ($this->_app->shouldRunFunctionsAsync()) {
        $this->_runFunctionsAsync($functions);
      } else {
        foreach ($functions AS $function) {
          $this->_runFunction($function);
        }
      }
    }
  }

  /**
   * @param $functions
   */
  private function _runFunctionsAsync($functions) {
    Core_Logger::debug('Core_Trigger::_runFunctionsAsync(): ' . implode(', ', $functions));
    $args = [];
    foreach ($functions AS $function) {
      $args[] = urlencode($function);
    }

    // Core_Logger::info('Launching: ' . implode('; ', $functions));
    $cmd = "mosquitto_pub -h mqtt -t 'core-function/run' -m '" . implode(";;", $args) . "'";
    ob_start();
    system($cmd . ' 2>&1 &', $retval);
    ob_end_clean();
    // exec("php web/runFunctions.php '" . implode("' '", $args) . "' > /dev/null 2>&1 &");
  }

  /**
   * @param $where
   * @param $value
   */
  private function _setNewState($where, $value) {
    // Core_Logger::info('Core_Trigger::_setNewState("' . $where . '", "' . $value . '");');

    $where = explode('.', $where, 2);
    if (!isset($where[1])) {
      Core_Logger::error('Core_Trigger::_setNewState("' . $where . '", "' . $value . '"): INVALID $where PARAM!');
      return false;
    }

    // Set new var state
    $this->_app->getState()->set($where[0], $where[1], $value);
  }

  /**
   * @param $triggers
   */
  private function _setNewStates($triggers) {
    // Core_Logger::info('Core_Trigger::_setNewStates(): '.print_r($triggers,1));

    foreach ($triggers AS $trigger) {

      // Skip trigger if there's nothing to set
      if (!isset($trigger['set'])) {
        continue;
      }

      // Set each variable
      foreach ($trigger['set'] AS $where => $value) {
        $this->_setNewState($where, $value);
      }
    }
  }
}
