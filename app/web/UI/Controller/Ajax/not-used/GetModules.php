<?php

class UI_Controller_GetModules extends UI_Controller_Base {
    public function run() {

        // Get payload
        $payload = $this->getPayload();

        // Get only modules properties
        $modules = $this->_getModules();

        // Return sccess message
        $this->_dumpSuccess([
            'modules' => array_values($modules),
        ]);
    }

    /**
     * @return array
     */
    private function _getModules() {

        // Get unprocessed modules
        $rawModules = $this->getConfig()->getRawConfig('Module');

        // If there are no modules return an empty list
        if (empty($rawModules)) {
            return [];
        }

        $modules = [];
        foreach ($rawModules AS $name => $raw) {
            $image = 'Unknown.png';

            if (isset($raw['GUI']) && isset($raw['GUI']['image'])) {
                $image = $raw['GUI']['image'];
            }

            $modules[$name] = [
                'name'  => $name,
                'image' => $image,
            ];
        }

        // Sort the modules
        ksort($modules);

        // Return
        return $modules;
    }
}