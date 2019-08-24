<?php

class UI_Controller_DeleteObject extends UI_Controller_Base {
    public function run() {

        // Get payload
        $payload = $this->getPayload();

        // Check if we have the required params
        foreach (['name'] AS $field) {
            if (!isset($payload[$field]) || empty($payload[$field])) {
                $this->_dumpError('Please provide "' . $field . '"');
            }
        }

        // Set new definition in config
        $this->getConfig()->unsetRawObjectDefinition($payload['name']);

        // Save config to disk
        if (!$this->getConfig()->saveRaw('Objects')) {
            $this->_dumpError('Couldn\'t save the configuration!');
        }

        // Return sccess message
        $this->_dumpSuccess([]);
    }
}