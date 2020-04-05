<?php

class UI_Controller_SetObject extends UI_Controller_Base {
    public function run() {

        // Get payload
        $payload = $this->getPayload();

        // Check if we have the required params
        foreach (['name', 'module'] AS $field) {
            if (!isset($payload[$field]) || empty($payload[$field])) {
                $this->_dumpError('Please provide "' . $field . '"');
            }
        }

        // Check if we have the required params
        foreach (['properties'] AS $field) {
            if (!isset($payload[$field])) {
                $this->_dumpError('Please provide "' . $field . '"');
            }
        }

        // Change properties structure
        $properties = [];
        foreach ($payload['properties'] AS $property) {
            $properties[$property['name']] = $property['value'];
        }

        // Set new definition in config
        $this->getConfig()->setRawObjectDefinition(
            $payload['name'],
            '!import Module.' . $payload['module'],
            $properties
        );

        // Save config to disk
        if (!$this->getConfig()->saveRaw('Objects')) {
            $this->_dumpError('Couldn\'t save the configuration!');
        }

        // Return success message
        $this->_dumpSuccess([]);
    }
}