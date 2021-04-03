<?php

class Core_Controller_RunFunctions extends Core_Controller_Base {
  /**
   * @param array $argv
   */
  public function __construct($argv) {
    $this->_argv = $argv;
    parent::__construct();
  }

  public function run() {
    for ($i = 1; $i < count($this->_argv); $i++) {
      $function = new Core_Function($this, urldecode($this->_argv[$i]));
      $function->process();
    }
  }
}