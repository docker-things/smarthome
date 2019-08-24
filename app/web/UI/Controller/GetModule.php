<?php

class UI_Controller_GetModule extends UI_Controller_Base {
    public function run() {

        // Get payload
        $payload = $this->getPayload();

        // Check if we have the required params
        foreach (['name'] AS $field) {
            if (!isset($payload[$field]) || empty($payload[$field])) {
                $this->_dumpError('Please provide "' . $field . '"');
            }
        }

        // Get only modules properties
        $module = $this->_getModule($payload['name']);

        // Check if we've got a valid module
        if (false === $module) {
            $this->_dumpError('The requested module doesn\'t exist!');
        }

        // Return success message
        $this->_dumpSuccess($module);
    }

    /**
     * @return array
     */
    private function _getModule($name) {

        // Get unprocessed modules
        $rawModules = $this->getConfig()->getRawConfig('Module');

        // Check if the required module exists
        if (!isset($rawModules[$name])) {
            return false;
        }

        // Isolate the module
        $rawModule = $rawModules[$name];
        unset($rawModules);

        // List of possible files
        $moduleFiles = [
            'Cron',
            'Functions',
            'GUI',
            'Incoming',
            'Properties',
        ];

        // Init module
        $module = [];

        // Grab raw files
        foreach ($moduleFiles AS $file) {

            // If the file is not defined set it empty
            if (!isset($rawModule[$file])) {
                $module[$file] = '';
                continue;
            }

            // Get the file contents
            $module[$file] = $this->getConfig()->getRawFile('Module.' . $name . '.' . $file);

            // If the file couln't be read
            if (false === $module[$file]) {
                $module[$file] = '';
            }
        }

        // Return
        return $module;
    }
}