<?php

class Core_Function {
    /**
     * The main app controller
     *
     * @var Core_Controller_base
     */
    private $_app;

    /**
     * @var string
     */
    private $_function;

    /**
     * @var string
     */
    private $_object;

    /**
     * @param Core_Controller_Base $app
     */
    public function __construct(Core_Controller_Base $app, $objectFunction) {
        // Core_Logger::info('Core_Function("' . $objectFunction . '");');

        // Set app controller
        $this->_app = $app;

        // Split call into object & function
        $objectFunction = explode('.', $objectFunction, 2);

        if (!isset($objectFunction[1])) {
            Core_Logger::error('Core_Function(): Invalid method call!');
            return;
        }

        // Set object name
        $this->_object = $objectFunction[0];

        // Set object function name
        $this->_function = $objectFunction[1];
    }

    public function process() {
        // Core_Logger::info('Core_Function::process();');

        // Get function and params
        $config = $this->_app->getConfig()->getObjectFunction($this->_object, $this->_function);

        // Don't continue if the function couldn't be retrieved
        if (false === $config) {
            return false;
        }

        // Set the received params into the function definition
        $function = $this->_setFunctionParams($config);

        // Check if we should run something
        if ($this->_checkRunConditions($function, '')) {

            // Execute YAML function
            $this->_runYamlFunctions($function);

            // Set state variables
            $function = $this->_setFunctionStateVariables($function);

            // Execute function
            $output = $this->_runFunction($function);

            // Decode output
            $output = $this->_decodeOutput($output);

            // Set new states if we have valid conditions
            if ($this->_checkConditions($function, $output)) {
                $this->_setNewStates($function, $output, 'set');
            } else {
                $this->_setNewStates($function, $output, 'elseSet');
            }
        }

        return true;
    }

    /**
     * @param  $callback
     * @param  $arr
     * @return mixed
     */
    private function _arrayMapRecursive($callback, $arr) {
        $ret = [];
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $ret[$key] = $this->_arrayMapRecursive($callback, $val);
            } else {
                $ret[$key] = $callback($val);
            }

        }
        return $ret;
    }

    /**
     * @param  $function
     * @param  $output
     * @return mixed
     */
    private function _checkConditions($function, $output) {

        // Assume we always set states when no conditions are set
        if (!isset($function['if'])) {
            return true;
        }

        // Initialize conditions checking object
        $conditions = new Core_Conditions($this->_app);

        // Set function output
        $conditions->set('RESPONSE', $output);

        // Check conditions
        return $conditions->check($function['if']);
    }

    /**
     * @param  $function
     * @param  $output
     * @return mixed
     */
    private function _checkRunConditions($function, $output) {

        // Assume we always set states when no conditions are set
        if (!isset($function['runIf'])) {
            return true;
        }

        // Initialize conditions checking object
        $conditions = new Core_Conditions($this->_app);

        // Set function output
        $conditions->set('RESPONSE', $output);

        // Check conditions
        return $conditions->check($function['runIf']);
    }

    /**
     * @param $output
     */
    private function _decodeOutput($output) {

        // Trim the output
        $output = trim($output);

        // Core_Logger::debug('Core_Function::_decodeOutput(): $output: ' . print_r($output, 1));

        // Decode JSON
        $decoded = json_decode($output, true);
        // Core_Logger::debug('Core_Function::_decodeOutput(): $decoded: ' . print_r($decoded, 1));

        // If decoding failed try getting the last line of the string
        if (!$decoded) {
            if (strpos($output, "\n") !== false) {
                $lines   = explode("\n", $output);
                $decoded = json_decode(array_pop($lines), true);
                // Core_Logger::debug('Core_Function::_decodeOutput(): $output: ' . print_r($output, 1));
            }
        }

        // Replace boolean with strings
        if ($decoded) {
            if (is_array($decoded)) {
                $decoded = $this->_arrayMapRecursive(function ($val) {
                    if (true === $val) {
                        return 'true';
                    }

                    if (false === $val) {
                        return 'false';
                    }

                    return $val;
                }, $decoded);
            } else {
                $decoded = [
                    'output' => $output,
                ];
            }
        }
        // Generating a valid JSON with the output as a string
        else {
            $decoded = [
                'output' => $output,
            ];
        }

        // Return values
        return $decoded;
    }

    /**
     * @param $function
     */
    private function _runFunction($function) {
        Core_Logger::info('Core_Function::_runFunction("' . json_encode($function) . '");');

        if (!isset($function['run'])) {
            if (!isset($function['runFunctions']) && !isset($function['runFunctionsAsync'])) {
                Core_Logger::error('Core_Function::_runFunction(): Function doensn\'t have any command to run!');
            }
            return false;
        }

        Core_Logger::info('Core_Function::_runFunction(): Running: ' . $function['run']);

        ob_start();
        system($function['run'] . ' 2>&1', $retval);
        $output = ob_get_clean();

        Core_Logger::info('Core_Function::_runFunction(): Output: ' . $output);

        return $output;
    }

    /**
     * @param $function
     */
    private function _runYamlFunctions($functions) {
        Core_Logger::info('Core_Function::_runYamlFunctions("' . json_encode($functions) . '");');

        if (!isset($functions['runFunctions']) && !isset($functions['runFunctionsAsync'])) {
            if (!isset($functions['run'])) {
                Core_Logger::error('Core_Function::_runYamlFunctions(): Function doesn\'t have any command to run!');
            }
            return false;
        }

        // Run async functions
        if (isset($functions['runFunctionsAsync'])) {
            foreach ($functions['runFunctionsAsync'] as $function) {
                Core_Logger::info('Core_Function::_runYamlFunctions(): Running async: ' . $function);

                // Run function in another process
                exec("php web/runFunctions.php '" . urlencode($function) . "' > /dev/null 2>&1 &");

                // ob_start();
                // system("php web/runFunctions.php '" . urlencode($function) . "' > /dev/null 2>&1", $retval);
                // $output = ob_get_clean();
                // Core_Logger::info('Core_Function::_runYamlFunctions(): Output: ' . $output);
            }
        }

        // Run ordered functions
        if (isset($functions['runFunctions'])) {
            foreach ($functions['runFunctions'] as $function) {
                Core_Logger::info('Core_Function::_runYamlFunctions(): Running: ' . $function);

                // Init function object
                $subfunction = new Core_Function($this->_app, $function);

                // Run function
                $subfunction->process();
            }
        }
    }

    /**
     * @param  $config
     * @return mixed
     */
    private function _setFunctionParams($config) {
        $function = $config['function'];
        if (!empty($config['params'])) {
            foreach ($config['params'] AS $from => $to) {
                $function = $this->_str_replace_recursive('${ARGS.' . $from . '}', $to, $function);
            }
        }
        return $function;
    }

    /**
     * @param  $function
     * @return mixed
     */
    private function _setFunctionStateVariables($function) {
        return $this->_arrayMapRecursive(function ($val) {
            if (preg_match_all('/([a-zA-Z0-9\-\_]+)\.([a-zA-Z0-9\-\_\.]+)/', $val, $matches)) {
                foreach ($matches[1] AS $key => $object) {
                    if (in_array($object, ['RESPONSE'])) {
                        continue;
                    }
                    $value = $this->_app->getState()->getVariableValue($object, $matches[2][$key]);
                    if (false === $value) {
                        continue;
                    }

                    // Core_Logger::debug("Core_Function::_setFunctionStateVariables(): REPLACE " . $matches[0][$key] . " WITH " . $value . "");
                    $val = str_replace($matches[0][$key], $value, $val);
                }
            }
            return $val;
        }, $function);
    }

    /**
     * @param $variable
     * @param $value
     * @param $output
     */
    private function _setNewState($variable, $value, $output, $nthTime = 0) {
        // Core_Logger::info('Core_Function::_setNewState("' . $variable . '", "' . $value . '");');

        // Check if we need to set variables from the output
        if (preg_match_all('/\$\{RESPONSE.(.*)\}/', $value, $matches)) {

            // For each response variable needed
            for ($i = 0, $matchesCount = count($matches[1]); $i < $matchesCount; $i++) {

                // Build array path
                $outputPath = explode('.', $matches[1][$i]);

                // Get the value from the command output
                $newValue = $output;
                foreach ($outputPath AS $key) {

                    // Stop if the key is not found
                    if (!isset($newValue[$key])) {
                        break;
                    }

                    // Set new value
                    $newValue = $newValue[$key];
                }

                // If it's an array set the string 'array'
                if (is_array($newValue)) {
                    $newValue = 'array';
                }

                // Replace response variable in the $value string
                $value = str_replace($matches[0][$i], $newValue, $value);
            }
        }

        // Set variable
        if (0 != $nthTime) {
            $this->_app->getState()->setNthTime($this->_object, $variable, $value, $nthTime);
        } else {
            $this->_app->getState()->set($this->_object, $variable, $value);
        }
    }

    /**
     * @param $trigger
     * @param $output
     */
    private function _setNewStates($trigger, $output, $key = 'set') {
        // Core_Logger::info('Core_Function::_setNewStates(' . $key . ');');

        // Set for the simple key (ex: 'set')
        if (isset($trigger[$key])) {
            foreach ($trigger[$key] AS $where => $value) {
                $this->_setNewState($where, $value, $output);
            }
            return;
        }

        // Set for the custom keys (ex: 'set 3rd time')
        foreach (array_keys($trigger) AS $customKey) {

            // Make sure the string starts with 'set'
            if (strpos($customKey, $key . ' ') !== 0) {
                continue;
            }

            // Make sure there's a number in the string
            if (!preg_match('/[0-9]+/', $customKey, $numbers)) {
                continue;
            }

            // Set all the new states
            foreach ($trigger[$customKey] AS $where => $value) {
                $this->_setNewState($where, $value, $output, $numbers[0]);
            }
        }
    }

    /**
     * @param $from
     * @param $to
     * @param $into
     */
    private function _str_replace_recursive($from, $to, $into) {
        if (is_array($into)) {
            $newArray = [];
            foreach ($into AS $key => $value) {
                $newArray[$key] = $this->_str_replace_recursive($from, $to, $value);
            }
            return $newArray;
        }
        return str_replace($from, $to, $into);
    }
}
