<?php

class Core_Controller_Base {
  /**
   * Keeps all the configs available
   *
   * @var Core_Config
   */
  private $_config;

  /**
   * Show debugging messages?
   *
   * @var boolean
   */
  private $_debug = false;

  /**
   * List of existing modules
   *
   * @var array
   */
  private $_modules;

  /**
   * Keeps the received payload
   * @var array
   */
  private $_payload;

  /**
   * Keeps the queue of stuff to be runned
   *
   * @var Core_Queue
   */
  private $_queue;

  /**
   * @var mixed
   */
  private $_shouldRunFunctionsAsync = false;

  /**
   * Keeps the state of the world
   *
   * @var Core_State
   */
  private $_state;

  /**
   * Initialize stuff
   */
  public function __construct(Core_Controller_Base $app = null, Core_Config $config = null) {
    if (null === $app) {
      $this->_payload = $this->_setPayload();
      $this->_config  = new Core_Config($this);
      $this->_state   = new Core_State($this);
      $this->_queue   = new Core_Queue($this);
    } else {
      $this->_payload = $app->getPayload();
      $this->_config  = null !== $config ? $config : $app->getConfig();
      $this->_state   = $app->getState();
      $this->_queue   = $app->getQueue();
    }
  }

  /**
   * Show debugging messages if debug activated
   *
   * @param string $message Message
   */
  public function debug($message) {
    if ($this->_debug) {
      echo "\n[" . date('Y-m-d H:i:s') . "] " . print_r($message, 1);
    }
  }

  /**
   * Return the built config object
   *
   * @return Core_Config
   */
  public function getConfig(): Core_Config {
    return $this->_config;
  }

  /**
   * Return the received params trough the known methods
   *
   * @return array Params
   */
  public function getPayload() {
    return $this->_payload;
  }

  /**
   * Get the queue
   *
   * @return Core_Queue The queue of stuff to be runned
   */
  public function getQueue() {
    return $this->_queue;
  }

  /**
   * Get the state
   *
   * @return Core_State The state of the world
   */
  public function getState() {
    return $this->_state;
  }

  /**
   * Method that should be overloaded to have a working controller
   */
  public function run() {
    Core_Logger::error(get_class($this) . '::run(): Method not implemented!');
    die;
  }

  /**
   * @param  $value
   * @return mixed
   */
  public function shouldRunFunctionsAsync($value = null) {
    if (null === $value) {
      return $this->_shouldRunFunctionsAsync;
    } else {
      $this->_shouldRunFunctionsAsync = $value;
    }
  }

  /**
   * @return mixed
   */
  protected function _processQueue() {
    return $this->_queue->process();
  }

  /**
   * Get the GET & POST params and merge them in a single array
   *
   * @return array The merged params
   */
  protected function _setPayload() {

    // Init empty payload
    $payload = [];

    // Append GET
    if (!empty($_GET)) {
      foreach ($_GET AS $key => $value) {
        $payload[$key] = $value;
      }
    }

    // Append POST
    if (!empty($_POST)) {
      foreach ($_POST AS $key => $value) {
        $payload[$key] = $value;
      }
    }

    // Append POST JSON
    $params = trim(file_get_contents("php://input"));
    if (!empty($params)) {
      $params = json_decode($params, true);
      if (false !== $params && null !== $params) {
        foreach ($params AS $key => $value) {
          $payload[$key] = $value;
        }
      }
    }

    // If we have only 'payload' and it's a decodable JSON
    if (isset($payload['payload']) && count($payload) == 1) {
      $tmp = json_decode($payload['payload'], true);
      if (false !== $tmp) {
        $payload = $tmp;
      }
    }

    // Transform boolean values to strings
    if ($payload) {
      $payload = $this->_arrayMapRecursive(function ($val) {
        if (true === $val) {
          return 'true';
        }

        if (false === $val) {
          return 'false';
        }

        return $val;
      }, $payload);
    }

    // Return
    return $payload;
  }

  /**
   * @param  $callback
   * @param  $arr
   * @return mixed
   */
  private function _arrayMapRecursive($callback, $arr) {
    $ret = [];
    foreach ($arr as $key => $val) {
      if (is_array($val)) {
        $ret[$key] = $this->_arrayMapRecursive($callback, $val);
      } else {
        $ret[$key] = $callback($val);
      }

    }
    return $ret;
  }
}