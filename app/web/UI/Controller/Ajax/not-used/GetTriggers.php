<?php

class UI_Controller_GetTriggers extends UI_Controller_Base {
    public function run() {

        // Get payload
        $payload = $this->getPayload();

        $possibleTriggers  = $this->_getPossibleTriggers();
        $possibleOperators = $this->_getPossibleOperators();
        $possibleFunctions = $this->_getPossibleFunctions();
        $triggers          = $this->_getTriggers($possibleFunctions);

        // Return sccess message
        $this->_dumpSuccess([
            'triggers'          => array_values($triggers),
            'possibleTriggers'  => $possibleTriggers,
            'possibleOperators' => $possibleOperators,
            'possibleFunctions' => array_values($possibleFunctions),
        ]);
    }

    /**
     * @return mixed
     */
    protected function _getPossibleFunctions() {
        $possibleFunctions = [];

        $objectNames = $this->getConfig()->getObjectsWhichHave('Functions');

        foreach ($objectNames AS $objectName) {
            $functions = $this->getConfig()->getObjectByName($objectName)['Functions'];

            if (!isset($functions['functions'])) {
                continue;
            }

            foreach (array_keys($functions['functions']) AS $function) {
                $functionCall = $objectName . '.' . $function;

                $params = [];
                if (strpos($function, '()') === false) {
                    $tmp = explode('(', $function, 2)[1];
                    $tmp = substr($tmp, 0, -1);
                    $tmp = explode(',', $tmp);
                    foreach ($tmp AS $param) {
                        $params[] = [
                            'name'  => $param,
                            'value' => '',
                        ];
                    }
                }

                $possibleFunctions[$functionCall] = [
                    'function' => $functionCall,
                    'params'   => $params,
                ];
            }
        }

        return $possibleFunctions;
    }

    protected function _getPossibleOperators() {
        return [
            '==',
            '!=',
            '>=',
            '>',
            '<=',
            '<',
            'not in',
            'in',
        ];
    }

    /**
     * @param string $triggerName
     * @param array  $objects
     * @param array  $modules
     */
    protected function _getTriggerImage(string $triggerName, array $objects, array $modules) {

        $objectName = explode('.', $triggerName, 2)[0];

        if (!isset($objects[$objectName]['base'])) {
            return 'Unknown.png';
        }

        $moduleName = str_replace('!import Module.', '', $objects[$objectName]['base']);

        $module = $modules[$moduleName];

        if (isset($module['GUI']) && isset($module['GUI']['image'])) {
            return $module['GUI']['image'];
        }

        return 'Unknown.png';
    }

    /**
     * @param string $triggerName
     * @param array  $objects
     * @param array  $modules
     */
    protected function _getTriggerModuleName(string $triggerName, array $objects, array $modules) {

        $objectName = explode('.', $triggerName, 2)[0];

        if (!isset($objects[$objectName]['base'])) {
            return '';
        }

        return str_replace('!import Module.', '', $objects[$objectName]['base']);
    }

    /**
     * @return array
     */
    protected function _getTriggers(array $possibleFunctions) {

        // Get unprocessed triggers
        $tmp = $this->getConfig()->getRawConfig('Triggers');

        // Get objects & modules
        $rawObjects = $this->getConfig()->getRawConfig('Objects');
        $rawModules = $this->getConfig()->getRawConfig('Module');

        $triggers = [];

        // Split the conditions and normalize the operators
        foreach ($tmp AS $triggerName => $trigger) {
            $moduleName = $this->_getTriggerModuleName($triggerName, $rawObjects, $rawModules);

            $key = $moduleName . '.' . $triggerName;

            $triggers[$key]['trigger'] = $triggerName;
            $triggers[$key]['image']   = $this->_getTriggerImage($triggerName, $rawObjects, $rawModules);

            foreach ($trigger AS $groupName => $groups) {

                if (!isset($groups['if'])) {
                    $groups['if'] = [];
                }

                if (!isset($groups['run'])) {
                    continue;
                }

                $triggers[$key]['actions'][] = [
                    'name' => $groupName,
                    'if'   => $this->_processIFs($groups['if']),
                    'run'  => $this->_processRUNs($groups['run'], $possibleFunctions),
                ];
            }
        }

        ksort($triggers);

        return $triggers;
    }

    /**
     * @param $ifs
     */
    protected function _processIFs($ifs) {
        $result = [];
        if (!is_array($ifs)) {
            $ifs = [$ifs];
        }

        $ifs = implode(' && ', $ifs);

        $replace = [
            '"'                     => '\'',
            ' and '                 => ' && ',
            ' is greater than '     => ' > ',
            ' is lower than '       => ' < ',
            ' is not greater than ' => ' <= ',
            ' is not lower than '   => ' >= ',
            ' is not '              => ' != ',
            ' is '                  => ' == ',
            ' greater than '        => ' > ',
            ' lower than '          => ' < ',
        ];
        $ifs = trim(str_replace(array_keys($replace), array_values($replace), $ifs));

        $ifs = explode(' && ', $ifs);

        foreach ($ifs AS $if) {

            // Try each known operator
            foreach ([
                '==',
                '!=',
                '>=',
                '>',
                '<=',
                '<',
                'not in',
                'in',
            ] AS $operator) {

                // If the operator is not present check the next one
                if (strpos($if, ' ' . $operator . ' ') === false) {
                    continue;
                }

                // Split values to be compared
                $tmp = explode(' ' . $operator . ' ', $if);

                $result[] = [
                    'arg1' => $tmp[0],
                    'cond' => $operator,
                    'arg2' => $tmp[1],
                ];

                break;
            }
        }
        return $result;
    }

    /**
     * @param  $runs
     * @return mixed
     */
    protected function _processRUNs($runs, $possibleFunctions) {
        if (!is_array($runs)) {
            $runs = [$runs];
        }

        $ret = [];

        foreach ($runs AS $function) {
            // echo "\n\$function = " . print_r($function, 1);
            if (preg_match('/^([^\(]+)\((.*)\)$/', $function, $matches)) {
                $name   = $matches[1];
                $params = [];
                if (preg_match_all('/\'([^\']+)\'/', $matches[2], $paramsMatches)) {
                    $params = $paramsMatches[1];
                    // echo "\n\$params = " . print_r($params, 1);
                    $paramsCount = count($params);
                } else {
                    $paramsCount = 0;
                }

                $assocParams = [];

                // Get params names from the possible functions
                foreach ($possibleFunctions AS $possible) {
                    $pFunction = $possible['function'];
                    $pParams   = $possible['params'];

                    // Get possible function name
                    $pFunctionName = explode('(', $pFunction, 2)[0];

                    // Check if it's the one we're searching for
                    if ($pFunctionName != $name) {
                        continue;
                    }

                    // Check if it has the same number of params
                    if (count($pParams) != $paramsCount) {
                        continue;
                    }

                    // Associate names and values
                    for ($i = 0; $i < $paramsCount; $i++) {
                        $assocParams[$pParams[$i]['name']] = [
                            'name'  => $pParams[$i]['name'],
                            'value' => $params[$i],
                        ];
                    }

                    $ret[] = [
                        'function' => $pFunction,
                        'params'   => array_values($assocParams),
                    ];

                    break;
                }
            } else {
                $this->_dumpError('Found invalid function!');
                return false;
            }
        }

        return $ret;
    }

    /**
     * @return mixed
     */
    private function _getPossibleTriggers() {
        $possibleTriggers = [];

        // Get from Incoming
        $objectNames = $this->getConfig()->getObjectsWhichHave('Incoming');
        foreach ($objectNames AS $objectName) {
            $incoming = $this->getConfig()->getObjectByName($objectName)['Incoming'];

            if (!isset($incoming['actions'])) {
                continue;
            }

            foreach ($incoming['actions'] AS $params) {
                foreach (array_keys($params) AS $param) {
                    $possibleTriggers[$objectName . '.' . $param] = true;
                }
            }
        }

        // Get from Functions
        $objectNames = $this->getConfig()->getObjectsWhichHave('Functions');
        foreach ($objectNames AS $objectName) {

            $functions = $this->getConfig()->getObjectByName($objectName)['Functions'];

            if (!isset($functions['functions'])) {
                continue;
            }

            foreach ($functions['functions'] AS $function) {
                if (!isset($function['set'])) {
                    continue;
                }
                foreach (array_keys($function['set']) AS $param) {
                    $possibleTriggers[$objectName . '.' . $param] = true;
                }
            }
        }

        return array_keys($possibleTriggers);
    }
}