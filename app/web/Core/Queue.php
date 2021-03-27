<?php

class Core_Queue {
  /**
   * Keep an instance of the currently running controller
   *
   * @var Core_Controller_Base
   */
  private $_app;

  /**
   * A queue of actions
   *
   * @var array
   */
  private $_queue = [];

  /**
   * Init State and Commands objects
   *
   * @param Core_Controller_Base $state State object
   */
  public function __construct(Core_Controller_Base $app) {
    $this->_app = $app;
  }

  /**
   * Add event to queue
   *
   * @param array $event Event array
   */
  public function add($event) {
    $this->_queue[] = $event;
  }

  /**
   * Process each event in the queue
   *
   * @return array Array of messages to be shown
   */
  public function process() {
    $messages = [];
    if (!empty($this->_queue)) {
      foreach ($this->_queue AS $key => $event) {
        $messages[] = $this->_processEvent($event);
        unset($this->_queue[$key]);
      }
    }
    return $messages;
  }

  /**
   * Process an event
   *
   * @param  array  $event Event to be processed
   * @return string Text message to be shown to the user
   */
  private function _processEvent($event) {
    return '[NOT-IMPLEMENTED]';
  }
}
