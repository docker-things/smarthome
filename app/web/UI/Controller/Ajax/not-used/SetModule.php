<?php

class UI_Controller_SetModule extends UI_Controller_Base {
    public function run() {

        // Get payload
        $payload = $this->getPayload();

        // List of required files
        $moduleFiles = [
            'Cron',
            'Functions',
            'GUI',
            'Incoming',
            'Properties',
        ];

        // Check if we have the required params nonempty
        foreach (['name'] AS $field) {
            if (!isset($payload[$field]) || empty($payload[$field])) {
                $this->_dumpError('Please provide "' . $field . '"');
            }
        }

        // Check if we have the required params which can be empty
        foreach ($moduleFiles AS $field) {
            if (!isset($payload[$field])) {
                $this->_dumpError('Please provide "' . $field . '"');
            }
        }

        // Make sure all the files are YAM parsable
        foreach ($moduleFiles AS $file) {
            if (!$this->getConfig()->isYamlValid($payload[$file])) {
                $this->_dumpError('The file "' . $file . '" doesn\'t have a proper YAML structure!');
            }
        }

        // Save the files
        foreach ($moduleFiles AS $file) {
            $result = $this->getConfig()->saveRawFile('Module.' . $payload['name'] . '.' . $file, $payload[$file]);
            if (!empty($result)) {
                $this->_dumpError($result);
            }
        }

        // Return success message
        $this->_dumpSuccess([]);
    }
}