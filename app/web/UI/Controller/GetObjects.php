<?php

class UI_Controller_GetObjects extends UI_Controller_Base {
    public function run() {

        // Get payload
        $payload = $this->getPayload();

        // Get only modules properties
        $modules = $this->_getModules();

        // Get objects, prettify 'base', fill missing properties from $modules
        $objects = $this->_getObjects($modules);

        // Return sccess message
        $this->_dumpSuccess([
            'modules' => array_values($modules),
            'objects' => array_values($objects),
        ]);
    }

    /**
     * @return array
     */
    private function _getModules() {

        $modules = [];

        // Get unprocessed modules
        $tmp = $this->getConfig()->getRawConfig('Module');

        // Kepp only their properties
        foreach ($tmp AS $moduleName => $module) {
            if (!isset($module['Properties'])) {
                $module['Properties'] = [];
            }

            $properties = [];
            foreach ($module['Properties'] AS $name => $value) {
                $properties[] = [
                    'name'  => $name,
                    'value' => '!required' == $value ? '' : $value,
                ];
            }

            $moduleImage = 'Unknown.png';
            if (isset($module['GUI']) && isset($module['GUI']['image'])) {
                $moduleImage = $module['GUI']['image'];
            }

            $modules[$moduleName] = [
                'name'       => $moduleName,
                'image'      => $moduleImage,
                'properties' => $properties,
            ];
        }

        return $modules;
    }

    /**
     * @return mixed
     */
    private function _getObjects($modules) {
        $configObjects = $this->getConfig()->getRawConfig('Objects');

        $objects = [];

        foreach ($configObjects AS $objectName => $object) {

            $outputObject = [];

            // Base module name
            $moduleName = str_replace('!import Module.', '', $object['base']);

            // Properties list
            $properties = [];
            if (isset($object['with'])) {
                foreach ($object['with'] AS $name => $value) {
                    $properties[$name] = [
                        'name'  => $name,
                        'value' => $value,
                    ];
                }
            }

            // Fill properties from module if missing in object
            $module = $modules[$moduleName];
            foreach ($module['properties'] AS $property) {
                if (!isset($properties[$property['name']])) {
                    $properties[$property['name']] = [
                        'name'  => $property['name'],
                        'value' => $property['value'],
                    ];
                }
            }

            $objects[$moduleName.'.'.$objectName] = [
                'name'       => $objectName,
                'module'     => $moduleName,
                'image'      => $module['image'],
                'properties' => array_values($properties),
            ];
        }

        ksort($objects);

        return $objects;
    }
}