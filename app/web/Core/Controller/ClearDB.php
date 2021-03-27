<?php

class Core_Controller_ClearDB extends Core_Controller_Base {
  public function run() {
    $this->getState()->deleteAllData();
    echo 'DONE';
  }
}