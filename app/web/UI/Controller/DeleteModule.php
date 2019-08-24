<?php

class UI_Controller_DeleteModule extends UI_Controller_Base {
    public function run() {

        // Get payload
        $payload = $this->getPayload();

        // Check if we have the required params nonempty
        foreach (['name'] AS $field) {
            if (!isset($payload[$field]) || empty($payload[$field])) {
                $this->_dumpError('Please provide "' . $field . '"');
            }
        }

        // Check for usage
        if ($this->_objectUsingModule($payload['name'])) {
            $this->_dumpError('A device uses this module!');
        }

        // Delete module an check for failure
        if (false === $this->getConfig()->deleteModule($payload['name'])) {
            $this->_dumpError('Couldn\'t delete the module!');
        }

        // Return success message
        $this->_dumpSuccess([]);
    }

    /**
     * @param $module
     */
    private function _objectUsingModule($module) {

        // Get objects
        $objects = $this->getConfig()->getRawConfig('Objects');

        // For each object
        foreach ($objects AS $object) {

            // Ignore object if doesn't have a module base
            if (!isset($object['base'])) {
                continue;
            }

            // Get module name
            $base = str_replace('!import Module.', '', $object['base']);

            // Compare
            if ($base == $module) {
                return true;
            }
        }

        return false;
    }
}