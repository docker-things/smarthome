<?php

class Core_Controller_Status extends Core_Controller_Base {
    /**
     * Watch the MQTT feed and simulate incoming requests
     */
    public function run() {

        // Get state
        $state = $this->getState()->getState();

        // Apply filters
        if (isset($_GET['filter'])) {
            $state = $this->_applyFilters($state, explode(',', $_GET['filter']));
        }

        // Fill temporary values waiting to be set
        foreach ($state AS $object => $variables) {
            foreach ($variables AS $variable => $values) {
                $tmp = $this->getState()->getVariable($object, $variable);
                if ('' !== $tmp['tmpValue']) {
                    $state[$object][$variable] .= ' (Detected "' . $tmp['tmpValue'] . '" ' . $tmp['tmpTimes'] . ' time' . (1 == $tmp['tmpTimes'] ? '' : 's') . ')';
                }
            }
        }

        // Sort
        ksort($state);

        // Show page
        echo $this->_drawHTML($state);
    }

    /**
     * @param  $state
     * @param  $filters
     * @return mixed
     */
    private function _applyFilters($state, $filters) {
        if (!empty($filters)) {
            $newState = [];
            foreach ($filters AS $filter) {
                foreach ($state AS $object => $values) {
                    if (stripos($object, $filter) !== false) {
                        $newState[$object] = $values;
                    }
                }
            }
            $state = $newState;
        }
        return $newState;
    }

    /**
     * @param $state
     */
    private function _drawHTML($state) {
        return '
            <html>
                <head>
                    <meta http-equiv="refresh" content="3" >
                    <title>World State: ' . implode(', ', array_keys($state)) . '</title>
                </head>
                <body>
                    <pre>' . preg_replace('/\n([a-zA-Z0-9\-\_]+):/', "\n\n<b>\\1</b>:", yaml_emit($state)) . '</pre>
                </body>
            </html>
        ';
    }
}