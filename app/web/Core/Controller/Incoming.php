<?php

class Core_Controller_Incoming extends Core_Controller_Base {
    /**
     * Process the incoming request
     */
    public function run() {

        // Get the object recognizing the current incoming request
        $object = $this->_getRelevantObject();

        // If no object recognized the request
        if (false === $object) {
            Core_Logger::warn(get_class($this) . ': No object recognized the current incoming request!');
            return;
        }

        // Show the object
        Core_Logger::info("Matched Object: " . $object->getName());

        // Process the request
        $object->processRequest();
    }

    /**
     * Scan modules and return the first one that recognizes the incoming request
     *
     * @return Core_Object_Incoming Incoming Module Object
     */
    private function _getRelevantObject() {

        // Get object names having Incoming functionality
        $objectNames = $this->getConfig()->getObjectsWhichHave('Incoming');

        // For each of them
        foreach ($objectNames AS $objectName) {

            // Initialize the object
            $object = new Core_Object_Incoming($this, $objectName);

            // Check if it recognizes the current incoming request
            if ($object->recognizeRequest()) {

                // Return object
                return $object;
            }
        }

        // Return false if no module recognizes the current incoming request
        return false;
    }
}