<?php

class Core_Controller_DeleteObjectData extends Core_Controller_Base {
    public function run() {
        $this->getState()->deleteObjectData($_GET['object']);
        echo 'DONE';
    }
}