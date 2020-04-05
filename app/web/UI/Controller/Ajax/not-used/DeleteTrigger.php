<?php

class UI_Controller_DeleteTrigger extends UI_Controller_Base {
    public function run() {

        // Get payload
        $payload = $this->getPayload();

        // Check if we have the required params
        foreach (['trigger'] AS $field) {
            if (!isset($payload[$field]) || empty($payload[$field])) {
                $this->_dumpError('Please provide "' . $field . '"');
            }
        }

        // Set new definition in config
        $this->getConfig()->unsetRawTriggerDefinition($payload['trigger']);

        // Save config to disk
        if (!$this->getConfig()->saveRaw('Triggers')) {
            $this->_dumpError('Couldn\'t save the configuration!');
        }

        // Return sccess message
        $this->_dumpSuccess([]);
    }
}