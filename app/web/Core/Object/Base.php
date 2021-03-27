<?php

class Core_Object_Base {
  /**
   * Object containing the instance of the current running controller
   *
   * @var Core_Controller_Base
   */
  protected $_app;

  /**
   * Meta of the current instance
   *
   * @var array
   */
  protected $_meta = [
    'name' => 'Unknown',
    'type' => 'Unknown',
  ];

  /**
   * Source that will be set in DB
   *
   * @var string
   */
  protected $_source = 'Unknown';

  /**
   * Build meta on init
   */
  public function __construct(Core_Controller_Base $app) {

    // Set app
    $this->_app = $app;

    // Set meta
    $this->_getMetaFromClassName();
  }

  /**
   * Get the Core_Queue object
   *
   * @return Core_Queue Current queue
   */
  protected function queue() {
    return $this->_app->getQueue();
  }

  /**
   * Get the Core_State object
   *
   * @return Core_State Current state
   */
  protected function state() {
    return $this->_app->getState();
  }

  /**
   * Set meta from class name
   */
  private function _getMetaFromClassName() {
    $nameSplit = explode('_', get_class($this), 3);

    if (count($nameSplit) != 3) {
      Core_Logger::error(get_class($this) . ': Invalid class name format. Format: Module_[NAME]_[TYPE]');
      return;
    }

    $this->_meta['prefix'] = $nameSplit[0];
    $this->_meta['name']   = $nameSplit[1];
    $this->_meta['type']   = $nameSplit[2];

    $this->_source = $this->_meta['prefix'] . '_' . $this->_meta['name'] . '_' . $this->_meta['type'];
  }
}
