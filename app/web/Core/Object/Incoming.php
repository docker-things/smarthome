<?php

class Core_Object_Incoming extends Core_Object_Base {
  /**
   * Keeps the param to be used for the incoming action
   * @var string
   */
  private $_actionParam = '';

  /**
   * Array of actions and which states should be set by them
   *
   * Plex example: [
   *     'media.play'   => ['plex' => 'play'],
   *     'media.resume' => ['plex' => 'resume'],
   *     'media.stop'   => ['plex' => 'stop'],
   *     'media.pause'  => ['plex' => 'pause'],
   *     ]
   *
   * In this case, when 'media.play' action is received the variable 'plex' is set to the value 'play' in the DB
   *
   * @var array
   */
  private $_actions = [];

  /**
   * Provide normalization of certain params
   *
   * Ex: [
   * 'charging_mode': [
   *     3 => 'ac',
   *     4 => 'wireless',
   *     ],
   * ]
   *
   * @var array
   */
  private $_normalizeParams = [];

  /**
   * Overwrite certain param keys after getting them
   *
   * Ex: [
   *     'device' => 'plex',
   * ]
   *
   * @var array
   */
  private $_overwriteParams = [];

  /**
   * This will hold the received params
   *
   * @var array
   */
  private $_params = [];

  /**
   * Array of rules which will help us recognize or not the incoming request
   * It will usually depend on some properties of the module which are particular for each object instance
   *
   * Ex: [
   *   '${params.device}' => '${Properties.device}',
   * ]
   *
   * @var array
   */
  private $_recognizeByComparing = [];

  /**
   * @param Core_Controller_Base $app Current instance of the app
   */
  public function __construct(Core_Controller_Base $app, string $objectName) {

    // Call parent constructor
    parent::__construct($app);

    // Set name
    $this->_name = $objectName;

    // Get params
    $this->_params = $this->_app->getPayload();

    // Load object config
    $this->_loadConfig();
  }

  /**
   * @return mixed
   */
  public function getName() {
    return $this->_name;
  }

  /**
   * Run method returned by _getActionMethodName
   */
  public function processRequest() {

    // Normalize params
    $this->_normalizeParams();

    // Overwrite params
    $this->_overwriteParams();

    // Normalize actions
    $this->_normalizeActions();

    // Set states
    $this->_setStatesByActions();
  }

  /**
   * Check if the current object recognizes the incoming request
   *
   * @return bool True if recognized
   */
  public function recognizeRequest() {

    // If got no params it's clearly not recognized
    if (empty($this->_params)) {
      return false;
    }

    // Take the action from a certain param
    if (!empty($this->_actionParam)) {
      $this->_params['action'] = $this->_actionParam;
    }

    // Check if params has action key
    if (!isset($this->_params['action'])) {
      return false;
    }

    // For each set of rules
    foreach ($this->_recognizeByComparing AS $rules) {

      // Assume recognized
      $recognized = true;

      // For each rule in the list
      foreach ($rules AS $value1 => $value2) {

        // If things are not equal mark as not recognized
        if ($value1 != $value2) {
          $recognized = false;
          break;
        }
      }

      // If recognized don't continue to the other rules
      if ($recognized) {
        break;
      }
    }

    // If not recognized stop
    if (!$recognized) {
      return false;
    }

    // Action is known?
    if (!isset($this->_actions[$this->_params['action']]) && !isset($this->_actions['all'])) {
      Core_Logger::warn('Core_Object_Incoming::recognizeRequest(): Request belongs to "' . $this->_name . '" but got unknown action: "' . $this->_params['action'] . '"');
      return false;
    }

    // Recognized
    return true;
  }

  private function _loadConfig() {

    // Get object config to be used
    $config = $this->_app->getConfig()->getObjectByName($this->_name)['Incoming'];

    // Set: actionParam
    if (isset($config['actionParam'])) {
      $this->_actionParam = $config['actionParam'];
    }
    // Set: actions
    if (isset($config['actions'])) {
      $this->_actions = $config['actions'];
    }
    if (empty($this->_actions)) {
      Core_Logger::error('Core_Object_Incoming::_loadConfig(): Object "' . $this->_name . '" has no action rule! It will never work without populating "actions".');
      die;
    }

    // Set: normalize-params
    if (isset($config['normalize-params'])) {
      $this->_normalizeParams = $config['normalize-params'];
    }

    // Set: overwrite-params
    if (isset($config['overwrite-params'])) {
      $this->_overwriteParams = $config['overwrite-params'];
    }

    // Set: recognize-by-comparing
    if (isset($config['recognize-by-comparing'])) {
      $this->_recognizeByComparing = $config['recognize-by-comparing'];
    }
    if (empty($this->_recognizeByComparing)) {
      Core_Logger::error('Core_Object_Incoming::_loadConfig(): Object "' . $this->_name . '" has no recognition rule! It will never work without populating "recognize-by-comparing".');
      die;
    }
  }

  /**
   * @return null
   */
  private function _normalizeActions() {

    // Do nothing if empty params
    if (empty($this->_actions)) {
      return;
    }

    // Normalize params
    if (!empty($this->_normalizeParams)) {

      // For each action
      foreach ($this->_normalizeParams AS $action => $params) {

        // For each param
        foreach ($params AS $param => $dictionary) {

          // If the param exists
          if (!isset($this->_actions[$action][$param])) {
            continue;
          }

          // If the received value should be normalized
          if (isset($dictionary[$this->_actions[$action][$param]])) {

            // Replace it with the new value
            $this->_actions[$action][$param] = $dictionary[$this->_actions[$action][$param]];
          }
        }
      }
    }
  }

  /**
   * Normalize params values according to the $_normalizeParams array
   */
  private function _normalizeParams() {

    // Do nothing if empty params
    if (empty($this->_params)) {
      return;
    }

    // Normalize params
    if (!empty($this->_normalizeParams)) {

      // For each param
      foreach ($this->_normalizeParams AS $param => $dictionary) {

        // If the param exists
        if (!isset($this->_params[$param])) {
          continue;
        }

        // If the received value should be normalized
        if (isset($dictionary[$this->_params[$param]])) {

          // Replace it with the new value
          $this->_params[$param] = $dictionary[$this->_params[$param]];
        }
      }
    }
  }

  /**
   * Overwrite params according to the $_overwriteParams array
   */
  private function _overwriteParams() {

    // Do nothing if empty params
    if (empty($this->_params)) {
      return;
    }

    // Overwrite params
    if (!empty($this->_overwriteParams)) {

      // For each param
      foreach (array_keys($this->_overwriteParams) AS $key) {

        // Set the fixed value
        $params[$key] = $this->_overwriteParams[$key];
      }
    }

  }

  /**
   * Set var states in DB according to the $this->_actions array and the received action param
   *
   * @param string $action From which action to take the rules
   */
  private function _setStatesByAction($action) {

    // Check if the action exists
    if (isset($this->_actions[$action])) {

      // For each rule
      foreach ($this->_actions[$action] AS $name => $value) {

        // Set the value
        $this->state()->set($this->_name, $name, $value);
      }
    }
  }

  /**
   * Set var states in DB according to the $this->_actions array
   */
  private function _setStatesByActions() {

    // Generic rules for all actions
    $this->_setStatesByAction('all');
    $this->_setStatesByAction('any');

    // Specific rules for current action
    $this->_setStatesByAction($this->_params['action']);
  }
}