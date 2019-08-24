<?php

class UI_Controller_GetDashboard extends UI_Controller_Base {
    public function run() {

        // Get payload
        $payload = $this->getPayload();

        $data = [];

        $objectNames = $this->getConfig()->getObjectsWhichHave('GUI');

        // Get objects
        $objects = [];
        $guis    = [];
        foreach ($objectNames AS $objectName) {
            $gui               = $this->getConfig()->getObjectByName($objectName)['GUI'];
            $guis[$objectName] = $gui;

            $object = [];

            // Set status
            $object['status'] = false;
            if (isset($gui['mainStatusFrom'])) {
                $statusVar = $this->getState()->getVariableValue($objectName, $gui['mainStatusFrom']);
                if (isset($gui['positiveValue']) && $statusVar == $gui['positiveValue']) {
                    $object['status'] = true;
                } elseif (isset($gui['positiveValues']) && in_array($statusVar, $gui['positiveValues'])) {
                    $object['status'] = true;
                }
            }

            // Set function
            if (isset($gui['dashboardFunction'])) {
                $object['function'] = $objectName . '.' . $gui['dashboardFunction'];
            }

            // Set type
            if (isset($gui['type'])) {
                $object['type'] = $gui['type'];
            } else {
                $object['type'] = 'sensor';
            }

            // Set type
            if (isset($gui['image'])) {
                $object['image'] = $gui['image'];
            } else {
                $object['image'] = 'Unknown.png';
            }

            // Which params to show
            $params = [];
            if (isset($gui['showInDashboard'])) {
                $params = $gui['showInDashboard'];
            } elseif (isset($gui['params'])) {
                $params = array_keys($gui['params']);
            }

            $object['params'] = [];

            // Append params to show with their values
            if (!empty($params)) {
                foreach ($params AS $param) {
                    $value = $this->getState()->getVariableValue($objectName, $param, 'Unknown');
                    if ('true' == $value) {
                        $value = 'yes';
                    } elseif ('false' == $value) {
                        $value = 'no';
                    }

                    if ('battery' == $param) {
                        if (isset($object['batteryImage'])) {
                            continue;
                        }
                        if ($value > 75) {
                            $object['batteryImage'] = '100.png';
                        } elseif ($value > 50) {
                            $object['batteryImage'] = '75.png';
                        } elseif ($value > 25) {
                            $object['batteryImage'] = '50.png';
                        } elseif ($value > 5) {
                            $object['batteryImage'] = '25.png';
                        } else {
                            $object['batteryImage'] = '0.png';
                        }
                        continue;
                    } elseif ('charging' == $param) {
                        if (in_array($value, ['ac', 'wireless', 'yes', 'on'])) {
                            $object['batteryImage'] = 'charging.png';
                        }
                        continue;
                    } elseif ('status' == $param && 'Charging' == $value) {
                        $object['batteryImage'] = 'charging.png';
                        continue;
                    } elseif ('temperature' == $param) {
                        $value .= '°C';
                    } elseif ('illuminance' == $param) {
                        $value .= ' lux';
                    } elseif (in_array($param, ['angle_x', 'angle_y', 'angle_z'])) {
                        $value .= '°';
                    }

                    $param = preg_replace('/[\_\-\. ]+/', ' ', $param);
                    $param = ucwords($param);
                    $param = preg_replace('/([A-Z])/', ' $1', $param);
                    $param = trim($param);

                    $object['params'][$param] = $value;

                    // if (count($object['params']) == 3) {
                    //     break;
                    // }
                }
            }

            // Set brightness value for slider types
            if ('slider' == $object['type']) {
                if ('on' == $object['params']['Status']) {
                    $object['status'] = $object['params']['Brightness'];
                } else {
                    $object['status'] = '0';
                }
            }

            // Enable interaction by default
            $object['disabled'] = false;

            // Disable interaction if device is offline
            if (isset($object['params']['Status']) && 'offline' == $object['params']['Status']) {
                $object['disabled'] = true;
            }

            // Disable interaction if specified in config
            elseif (isset($gui['disabled']) && true == $gui['disabled']) {
                $object['disabled'] = true;
            }

            $object['name'] = $objectName;

            $objectName = str_replace('-', ' ', $objectName);
            $objectName = str_replace('_', ' ', $objectName);

            // Append object
            $objects[$objectName] = $object;
        }

        // Sort objects
        $objects = $this->_sortObjects($objects);

        // Return sccess message
        $this->_dumpSuccess($objects);
    }

    /**
     * @param  $objects
     * @return mixed
     */
    private function _sortObjects($objects) {
        $tmp = [];

        ksort($objects);

        $rawObjects = $this->getConfig()->getRawConfig('Objects');

        $tmp = [
            'slider-1-enabled'  => [],
            'slider-2-disabled' => [],
            'toggle-1-enabled'  => [],
            'toggle-2-disabled' => [],
        ];

        foreach ($objects AS $name => $object) {
            if ('sensor' == $object['type']) {
                $key = empty($object['params']) ? 'z' : 'x';
            } else {
                $key = '';
            }
            $key .= $object['type'] . '-';
            if ($object['disabled']) {
                $key .= '2-disabled';
            } else {
                $key .= '1-enabled';
            }
            $key .= $rawObjects[$object['name']]['base'];
            $tmp[$key][$name] = $object;
        }

        ksort($tmp);

        $sorted = [];
        foreach ($tmp AS $objects) {
            foreach ($objects AS $name => $object) {
                $sorted[$name] = $object;
            }
        }

        return $sorted;
    }
}