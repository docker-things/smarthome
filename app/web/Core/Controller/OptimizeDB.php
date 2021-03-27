<?php

class Core_Controller_OptimizeDB extends Core_Controller_Base {
  public function run() {
    $this->getState()->optimizeDB();
    echo 'DONE';
  }
}