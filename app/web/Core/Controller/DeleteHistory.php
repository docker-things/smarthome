<?php

class Core_Controller_DeleteHistory extends Core_Controller_Base {
  public function run() {
    $this->getState()->deleteHistory();
    echo 'DONE';
  }
}