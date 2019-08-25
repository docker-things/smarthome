<?php

class Core_Config {
    /**
     * @var array
     */
    protected static $_yamlCallbacks = [
        '!import'      => 'Core_Config::rawprepend',
        '!json-decode' => 'Core_Config::rawprepend',
        '!json-encode' => 'Core_Config::rawprepend',
        '!required'    => 'Core_Config::rawprepend',
    ];

    /**
     * @var Core_Controller_Base
     */
    private $_app;

    /**
     * Keeps the big config array
     *
     * @var array
     */
    private $_config = [];

    /**
     * Keeps the big config array without any processing
     *
     * @var array
     */
    private $_rawConfig = [];

    /**
     * Make sure the import method doesn't get into an infinite loop
     *
     * @var integer
     */
    private $_safetyCounter = 0;

    /**
     * Load & import everything on init
     */
    public function __construct(Core_Controller_Base $app) {
        $this->_app = $app;
        $this->_loadAll();
        $this->_processImports();
        $this->_setRuntimeConstants();
        $this->_processObjects();
    }

    /**
     * @param $name
     */
    public function deleteModule($name) {

        // Build full module path
        $modulePath = CONFIG_DIR . '/Module/' . $name;

        // If it's a yaml file
        if (file_exists($modulePath . '.yaml')) {
            return unlink($modulePath);
        }

        // If it's a dir
        elseif (is_dir($modulePath)) {
            return $this->_deleteDirRecursively($modulePath);
        }

        // If it doesn't exist
        return true;
    }

    /**
     * @param  string  $key
     * @return mixed
     */
    public function getConfig($key = false) {
        if (false !== $key) {
            return $this->_config[$key];
        }
        return $this->_config;
    }

    /**
     * @param  string  $name
     * @return mixed
     */
    public function getObjectByName(string $name) {
        // Core_Logger::info(get_class($this) . '::getObjectByName("' . $name . '");');

        // Die if the object doesn't exist
        if (!isset($this->_config['Objects'][$name])) {
            Core_Logger::error(get_class($this) . '::_loadConfig(): The object "' . $name . '" doesn\'t exist!');
            return false;
        }

        // Set object locally
        $object = $this->_config['Objects'][$name];

        // Replace stuff allover it
        $this->_resolveVariablesInPath(['Objects', $name]);

        // Return the proper object
        return $object;
    }

    /**
     * @param $objectName
     */
    public function getObjectCronJobs($objectName) {

        // Init jobs array
        $jobs = [];

        // Get object
        $object = $this->getObjectByName($objectName);

        // Make sure we have cron jobs
        if (!isset($object['Cron']) || !isset($object['Cron']['jobs']) || empty($object['Cron']['jobs'])) {
            return $jobs;
        }

        // For each job
        foreach ($object['Cron']['jobs'] AS $job) {

            // If there's nothing to run/set skip
            if (!isset($job['run']) && !isset($job['set']) && !isset($job['elseSet'])) {
                Core_Logger::error('Job in ' . $objectName . ' doesn\'t have functions to run or variables to set!');
                continue;
            }

            // If there's no interval skip
            if (!isset($job['interval'])) {
                Core_Logger::error('Job in ' . $objectName . ' doesn\'t have an interval!');
                continue;
            }

            if (isset($job['run'])) {
                // Make sure it's an array
                if (!is_array($job['run'])) {
                    $job['run'] = [$job['run']];
                }

                // Prepend the object name for all the function calls
                foreach ($job['run'] AS $key => $function) {
                    if (strpos($function, '.') === false) {
                        $function = $objectName . '.' . $function;
                    }
                    $job['run'][$key] = $function;
                }
            }

            // Add object name in job
            $job['objectName'] = $objectName;

            // Apppend job
            $jobs[] = $job;
        }

        // Return jobs
        return $jobs;
    }

    /**
     * @param  $object
     * @param  $function
     * @return mixed
     */
    public function getObjectFunction($objectName, $function) {
        // Core_Logger::info(get_class($this) . '::getObjectFunction("' . $objectName . '", "' . $function . '");');

        // Get required object
        $object = $this->getObjectByName($objectName);

        // Check if the object has functions
        if (!isset($object['Functions']) || !isset($object['Functions']['functions'])) {
            Core_Logger::error(get_class($this) . '::getObjectFunction("' . $objectName . '", "' . $function . '"): The object doesn\'t have functions!');
            return false;
        }

        // Check if the object has the exact function
        if (isset($object['Functions']['functions'][$function])) {
            return [
                'function' => $object['Functions']['functions'][$function],
                'params'   => [],
            ];
        }

        // Check the number of parameters required
        if (preg_match('/^([^\(]+)\((.*)\)$/', $function, $matches)) {
            $name = $matches[1];
            if (preg_match_all('/\'([^\']+)\'/', $matches[2], $paramsMatches)) {
                $params = $paramsMatches[1];
            }
        } else {
            Core_Logger::error(get_class($this) . '::getObjectFunction("' . $objectName . '", "' . $function . '"): The object doesn\'t have the required function!');
            return false;
        }

        // Iterate through functions and check for matches
        foreach ($object['Functions']['functions'] AS $definition => $func) {

            // Split name and params
            $tmp = explode('(', $definition, 2);

            // Check if this function has the required name
            $definitionName = $tmp[0];
            if ($definitionName != $name) {
                continue;
            }

            // Get the param names
            if (!preg_match('/^.*\((.*)\)$/', $definition, $matches)) {
                continue;
            }
            $definitionParams = explode(',', $matches[1]);

            // Check if the params count is the same
            if (count($params) != count($definitionParams)) {
                continue;
            }

            $paramsMatch = [];
            foreach ($definitionParams AS $key => $name) {
                $paramsMatch[$name] = $params[$key];
            }

            return [
                'function' => $func,
                'params'   => $paramsMatch,
            ];
        }

        Core_Logger::error(get_class($this) . '::getObjectFunction("' . $objectName . '", "' . $function . '"): The object doesn\'t have the requested function!');

        return false;
    }

    /**
     * @param  $objectName
     * @return mixed
     */
    public function getObjectTrigger($objectName, $variableName) {
        // Core_Logger::info(get_class($this) . '::getObjectTrigger("' . $objectName . '");');

        $allTriggers = [];

        foreach ($this->getObjectsWhichHave('Triggers') as $currentObjectName) {

            // Get required object
            $object = $this->getObjectByName($currentObjectName);

            // Figure if there are triggers we're searching for
            $triggers = [];
            if (isset($object['Triggers'][$variableName])) {
                $triggers = $object['Triggers'][$variableName];
            } elseif (isset($object['Triggers'][$objectName . '.' . $variableName])) {
                $triggers = $object['Triggers'][$objectName . '.' . $variableName];
            } else {
                continue;
            }

            // Set object name
            foreach ($triggers AS $name => $trigger) {
                foreach (['set', 'elseSet'] AS $key) {
                    if (!isset($trigger[$key])) {
                        continue;
                    }
                    $tmp = [];
                    foreach ($trigger[$key] AS $where => $what) {
                        if (strpos($where, '.') === false) {
                            $where = $objectName . '.' . $where;
                        }
                        $tmp[$where] = preg_replace('/^this\.([a-zA-Z])/', $objectName . '.$1', $what);
                    }
                    $trigger[$key] = $tmp;
                }
                foreach (['run'] AS $key) {
                    if (!isset($trigger[$key])) {
                        continue;
                    }
                    if (!is_array($trigger[$key])) {
                        $trigger[$key] = [$trigger[$key]];
                    }
                    foreach ($trigger[$key] AS $i => $function) {
                        $trigger[$key][$i] = preg_replace('/([^a-z])this\.([a-zA-Z])/', '$1' . $objectName . '.$2', $function);
                    }
                }
                $allTriggers[$name] = $trigger;
            }
        }

        // Return triggers
        return empty($allTriggers) ? false : $allTriggers;
    }

    /**
     * @param string $behavior
     */
    public function getObjectsWhichHave(string $behavior) {
        // Core_Logger::info(get_class($this) . '::getObjectsWhichHave("' . $behavior . '");');

        $objects = [];
        if (!isset($this->_config['Objects']) || empty($this->_config['Objects'])) {
            Core_Logger::warn(get_class($this) . '::getObjectsWhichHave("' . $behavior . '"): There are no objects in the config');
        }
        foreach ($this->_config['Objects'] AS $name => $object) {
            if (isset($object[$behavior])) {
                $objects[] = $name;
            }
        }
        return $objects;
    }

    /**
     * @param  string  $key
     * @return mixed
     */
    public function getRawConfig($key = false) {
        if (false !== $key) {
            return $this->_rawConfig[$key];
        }
        return $this->_rawConfig;
    }

    /**
     * @param $path
     */
    public function getRawFile($path) {

        // Build full path to the file
        $filepath = CONFIG_DIR . '/' . str_replace('.', '/', $path) . '.yaml';

        // Check if the file exists
        if (!file_exists($filepath)) {
            return false;
        }

        // Get the contents
        $contents = file_get_contents($filepath);

        // Return
        return $contents;
    }

    /**
     * @param $name
     * @param $value
     * @param $source
     */
    public function getTriggersFor($source, $name) {
        // Core_Logger::info(get_class($this) . '::getTriggersFor("' . $source . '", "' . $name . '");');

        $triggersKey = $source . '.' . $name;

        $triggers = [];

        // Get the object triggers defined by the module
        $objectTriggers = $this->getObjectTrigger($source, $name);

        // Append if not empty
        if (!empty($objectTriggers)) {
            $triggers = array_merge($triggers, $objectTriggers);
        }

        // Get user defined triggers
        if (isset($this->_config['Triggers'][$triggersKey])) {
            $triggers = array_merge($triggers, $this->_config['Triggers'][$triggersKey]);
        }

        // If none found return false
        if (empty($triggers)) {
            return false;
        }

        // Otherwise return triggers
        return $triggers;
    }

    /**
     * @return mixed
     */
    public function getUserCronJobs() {

        // Init jobs array
        $jobs = [];

        // Make sure we have cron jobs
        if (!isset($this->_config['Cron']) || !isset($this->_config['Cron']['jobs']) || empty($this->_config['Cron']['jobs'])) {
            return $jobs;
        }

        // For each job
        foreach ($this->_config['Cron']['jobs'] AS $job) {

            // If there's nothing to run skip
            if (!isset($job['run']) && !isset($job['set']) && !isset($job['elseSet'])) {
                Core_Logger::error('Job in Cron doesn\'t have functions to run or variables to set!');
                continue;
            }

            // If there's no interval skip
            if (!isset($job['interval'])) {
                Core_Logger::error('Job in Cron an doesn\'t have interval!');
                continue;
            }

            // Make sure 'run' is an array
            if (isset($job['run']) && !is_array($job['run'])) {
                $job['run'] = [$job['run']];
            }

            // Apppend job
            $jobs[] = $job;
        }

        // Return jobs
        return $jobs;
    }

    /**
     * @param $path
     */
    public function isYamlValid($content) {

        // Trim content
        $content = trim($content);

        // If empty mark as valid
        if (empty($content)) {
            return true;
        }

        // If parsing fails mark invalid
        if (false === yaml_parse($content)) {
            return false;
        }

        // Otherwise all good
        return true;
    }

    /**
     * @param $configPart
     */
    public function saveRaw($configPart) {

        // Check if the section exists
        if (!isset($this->_rawConfig[$configPart])) {
            return false;
        }

        // Generate file path
        $filepath = CONFIG_DIR . '/' . $configPart . '.yaml';

        // Check if file exists
        if (!file_exists($filepath)) {
            return false;
        }

        // Save file
        return yaml_emit_file($filepath, $this->_rawConfig[$configPart]);
    }

    /**
     * @param $path
     */
    public function saveRawFile($path, $content) {

        // Check if the yaml content is valid
        if (!$this->isYamlValid($content)) {
            return 'Can\'t save "' . $path . '". Content is not a valid YAML format!';
        }

        // Build full path to the file
        $filepath = CONFIG_DIR . '/' . str_replace('.', '/', $path) . '.yaml';

        // Trim content
        $content = trim($content);

        // If we've got empty contents remove the file
        if (empty($content)) {
            if (file_exists($filepath)) {
                unlink($filepath);
                return '';
            }
        }

        // Add new line at EOF
        $content .= "\n";

        // Build full dir path
        $dirpath = str_replace('/' . basename($filepath), '', $filepath);

        // Make sure dir exists
        if (!is_dir($dirpath) && !mkdir($dirpath, 0777, true)) {
            return 'Can\'t create dir ' . $dirpath . ' for "' . $path . '"!';
        }

        // Save file
        if (false === file_put_contents($filepath, $content)) {
            return 'Can\'t write contents to ' . $filepath . '"!';
        }

        // Return success
        return '';
    }

    /**
     * @param string $objectName
     * @param string $base
     * @param array  $with
     */
    public function setRawCronDefinition(array $jobs) {
        $this->_rawConfig['Cron']['jobs'] = $jobs;
    }

    /**
     * @param string $objectName
     * @param string $base
     * @param array  $with
     */
    public function setRawObjectDefinition(string $objectName, string $base, array $with) {
        $this->_rawConfig['Objects'][$objectName] = [
            'base' => $base,
            'with' => $with,
        ];
    }

    /**
     * @param string $triggerName
     * @param string $trigger
     */
    public function setRawTriggerDefinition(string $triggerName, array $trigger) {
        $this->_rawConfig['Triggers'][$triggerName] = $trigger;
    }

    /**
     * @param string $objectName
     */
    public function unsetRawObjectDefinition(string $objectName) {
        unset($this->_rawConfig['Objects'][$objectName]);
    }

    /**
     * @param string $triggerName
     */
    public function unsetRawTriggerDefinition(string $triggerName) {
        unset($this->_rawConfig['Triggers'][$triggerName]);
    }

    /**
     * Callback for generic tags
     *
     * @param  mixed  $value Data from yaml file
     * @param  string $tag   Tag that triggered callback
     * @param  int    $flags Scalar entity style (see YAML_*_SCALAR_STYLE)
     * @return mixed  Value that YAML parser should emit for the given value
     */
    protected static function rawPrepend($value, $tag, $flags) {
        if (is_string($value)) {
            return trim($tag . ' ' . $value);
        }

        return $value;
    }

    /**
     * Get the reference to the content of a given path so we can change the contents in-place
     *
     * @param  array $path      The path to get
     * @param  bool  $buildPath If the path should be built if it doesn't exist
     * @return mixed Reference to the content of the path
     */
    private function &_getContentReference($path, $buildPath = false) {

        $here = &$this->_config;
        foreach ($path AS $key) {
            if (isset($here[$key])) {
                $here = &$here[$key];
            } elseif ($buildPath) {
                $here[$key] = null;
                $here       = &$here[$key];
            } else {
                Core_Logger::error(get_class($this) . '::&_getContentReference("' . implode('.', $path) . '"): Undefined key: "' . $key . '"');
                return null;
            }
        }

        return $here;
    }

    /**
     * @param $dir
     */
    private function _deleteDirRecursively($dir) {

        // Scan for contents
        $files = array_diff(scandir($dir), ['.', '..']);

        // For each entry
        foreach ($files as $file) {

            // If dir go recursively
            if (is_dir($dir . '/' . $file)) {
                $this->_deleteDirRecursively($dir . '/' . $file);
            }
            // If file remove it now
            else {
                unlink($dir . '/' . $file);
            }
        }

        // Remove current dir
        return rmdir($dir);
    }

    /**
     * Check if a given path exists
     *
     * @param  array $path Path to search
     * @param  array $root Where to search for the path
     * @return bool  True if exists / False if not
     */
    private function _existsPath($path, $root = []) {
        $here = empty($root) ? $this->_config : $root;
        foreach ($path AS $key) {
            if (!isset($here[$key])) {
                return false;
            }
            $here = $here[$key];
        }

        return true;
    }

    /**
     * Search a path in the parents of the given tree
     *
     * @param  array      $path Path to be searched
     * @param  array      $tree Tree to be parsed
     * @return array/bool Array with the absolute path found / False if not found
     */
    private function _existsPathInParents($path, $tree) {

        // For each parent in the tree (reverse order)
        for ($i = count($tree) - 2; $i >= -1; $i--) {

            // Get to the actual location
            $herePath = [];
            for ($k = 0; $k <= $i; $k++) {
                $herePath[] = $tree[$k];
            }

            // Get the content of the current location
            $here = $this->_getContent($herePath);

            // Check if the searched path can be found here
            if ($this->_existsPath($path, $here) !== false) {

                // Return the absolute path to the searched one
                return array_merge($herePath, $path);
            }
        }

        // Nothing found

        return false;
    }

    /**
     * Get the content of a given path
     *
     * @param  array $path   The path to get
     * @param  array $root   Array from which to get the path
     * @return mixed Content of the path
     */
    private function _getContent($path, $root = [], $die = true) {
        $here = empty($root) ? $this->_config : $root;

        if (false !== $path) {
            foreach ($path AS $key) {
                if (isset($here[$key])) {
                    $here = $here[$key];
                } elseif ($die) {
                    Core_Logger::error(get_class($this) . '::_getContent("' . implode('.', $path) . '"): Undefined key: "' . $key . '"');
                    return null;
                } else {
                    return null;
                }
            }
        }

        return $here;
    }

    /**
     * Load all the available configs with $this->_loadFile
     */
    private function _loadAll() {

        // Empty configs list
        $this->_config = [];

        // Init iterator
        $it = new RecursiveDirectoryIterator(CONFIG_DIR);

        // For each file
        foreach (new RecursiveIteratorIterator($it) as $file) {

            // If it's a yaml
            if (preg_match('/\.yaml$/i', $file, $matches)) {
                // Load the shit out of it
                $this->_loadFile((string) $file);
            }
        }

        // Add mandatory config(system oject, sunrise/sunset)
        $this->_loadAllAppendMandatoryConfig();

        // Save raw config
        $this->_rawConfig = $this->_config;

    }

    private function _loadAllAppendMandatoryConfig() {

        // NONE
        if (!isset($this->_config['Objects']['NONE'])) {
            $this->_config['Objects']['NONE'] = [
                'base' => '!import Module.System.NONE',
            ];
        }

        // System
        if (!isset($this->_config['Objects']['System'])) {
            $this->_config['Objects']['System'] = [
                'base' => '!import Module.System.System',
            ];
        }

        // Sun
        if (!isset($this->_config['Objects']['Sun'])) {
            $this->_config['Objects']['Sun'] = [
                'base' => '!import Module.System.SunriseSunsetAPI',
                'with' => [
                    'latitude'  => '44.438757',
                    'longitude' => '26.0206698',
                ],
            ];
        }

        // SystemNotify
        if (!isset($this->_config['Objects']['SystemNotify'])) {
            $this->_config['Objects']['SystemNotify'] = [
                'base' => '!import Module.foo',
            ];
        }

        // SystemWarn
        if (!isset($this->_config['Objects']['SystemWarn'])) {
            $this->_config['Objects']['SystemWarn'] = [
                'base' => '!import Module.foo',
            ];
        }
    }

    /**
     * Load a certain file in $this->_config
     *
     * @param string $file Absolute file path
     */
    private function _loadFile($file, $setInConfig = true) {

        // Load config from file
        $config = @yaml_parse_file($file, 0, $ndocs, Core_Config::$_yamlCallbacks);
        if (false === $config) {
            // Don't throw errors for empty files
            if (!empty(trim(file_get_contents($file)))) {
                Core_Logger::error('Failed reading file: ' . $file);
            }
            return false;
        }

        // Remove extension
        $file = preg_replace('/\.yaml$/i', '', $file);

        // Generate array keys from file path
        $tree = [];
        foreach (explode('/', str_replace(CONFIG_DIR, '', $file)) AS $section) {
            $section = trim($section);
            if (!empty($section)) {
                $tree[] = $section;
            }
        }

        // Set the config in the private var
        if ($setInConfig) {
            $ref = &$this->_getContentReference($tree, true);
            $ref = $config;
        }

        return $config;
    }

    /**
     * Copy part of an array to another part
     *
     * @param string $required    Required import to be processed
     * @param array  $destination Where the import array should be set
     */
    private function _processImport($required, $destination) {

        // Avoid infinite loops
        if (++$this->_safetyCounter >= 10000) {
            Core_Logger::error(get_class($this) . '::_processImport("' . $required . '", "' . implode('.', $destination) . '"): App stopped at 10000 imports. You\'ve probably created an infinite loop.');
            die;
        }

        // Get the value we should set
        $requiredPath    = explode('.', preg_replace('/\.yaml$/i', '', $required));
        $requiredAbsPath = $this->_existsPathInParents($requiredPath, $destination);

        // If we didn't find the value to import
        if (false === $requiredAbsPath) {
            Core_Logger::error(get_class($this) . '::_processImport("' . $required . '", "' . implode('.', $destination) . '"): Import not found: "' . $required . '"');
            die;
        }

        // Get the place where we have to set the value
        $destinationReference = &$this->_getContentReference($destination);

        // Set the value
        $destinationReference = $this->_getContent($requiredAbsPath);
    }

    /**
     * Scan everything that was loaded and launch $this->_processImport for every !import
     *
     * @param array $path Used for recursive self launches
     */
    private function _processImports($path = []) {

        // Go to current path
        $here = $this->_getContent($path);

        // Search for required imports
        foreach ($here AS $key => $value) {

            // If array, go deeper into the tree
            if (is_array($value)) {
                $newPath   = $path;
                $newPath[] = $key;
                $this->_processImports($newPath);
                continue;
            }

            // If we should import something
            if (is_string($value) && preg_match('/^!import /i', $value, $matches)) {

                // Get the required path
                $required = preg_replace('/^!import /i', '', $value);

                // Build the destination path
                $destination   = $path;
                $destination[] = $key;

                // Import
                $this->_processImport($required, $destination);
            }
        }
    }

    /**
     * @return null
     */
    private function _processObjects() {

        // Don't do anything if there are no objects
        if (!isset($this->_config['Objects'])) {
            return;
        }

        // For each object
        foreach (array_keys($this->_config['Objects']) AS $name) {

            // Set object reference
            $object = &$this->_config['Objects'][$name];

            // Make sure we have a module base
            if (!isset($object['base'])) {
                Core_Logger::error(get_class($this) . '::_processObjects(): Couldn\'t find a base for the object "' . $name . '"');
                die;
            }

            // Set custom values
            if (isset($object['with'])) {
                foreach ($object['with'] AS $where => $value) {
                    if (strpos($where, '.') === false) {
                        $where = 'Properties.' . $where;
                    }
                    $path = array_merge(
                        ['Objects', $name, 'base'],
                        explode('.', $where)
                    );
                    $ref = &$this->_getContentReference($path, true);
                    $ref = $value;
                }
            }

            // Set object name
            $object['base']['Properties']['selfObjectName'] = $name;

            // Replace the config with the object contents
            $object = $object['base'];
        }

        // Resolve variables for all objects
        $this->_resolveVariablesInPath(['Objects']);
    }

    /**
     * @param array $path
     */
    private function _resolveVariablesInPath($path = []) {

        // Go to current path
        $here = &$this->_getContentReference($path);

        // Search for variables
        foreach ($here AS $key => $value) {

            // If array, go deeper into the tree
            if (is_array($value)) {

                // Prepare new path
                $newPath   = $path;
                $newPath[] = $key;

                // Go deeper
                $this->_resolveVariablesInPath($newPath);
            }

            // If the value is a string containing variables
            elseif (is_string($value) && preg_match_all('/\${([^\}]+)}/i', $value, $matches)) {
                // Replace with new value
                $here[$key] = $this->_resolveVariablesInString($value, $matches, $path);
            }

            // After resolving the value check the key for variables
            if (preg_match_all('/\${([^\}]+)}/i', $key, $matches)) {
                // Get new key
                $newKey = $this->_resolveVariablesInString($key, $matches, $path);

                // Replace
                $here[$newKey] = $here[$key];
                unset($here[$key]);
            }
        }
    }

    /**
     * @param  string  $string
     * @param  array   $matches
     * @param  array   $startPath
     * @return mixed
     */
    private function _resolveVariablesInString(string $string, array $matches, array $startPath) {

        // Initialize new string
        $newString = $string;

        // For each variable found
        foreach ($matches[1] AS $var) {

            // Split the variable path to search
            $varPath = explode('.', $var);

            // If it's a param from the payload
            if ('PARAMS' == $varPath[0]) {
                array_shift($varPath);
                $payload  = $this->_app->getPayload();
                $varValue = $this->_getContent($varPath, $payload, false);
            }

            // If it's a param from the function response payload do nothing (must be replaced at runtime)
            elseif (in_array($varPath[0], ['RESPONSE', 'ARGS'])) {
                continue;
            }

            // Otherwise search in config
            else {

                // Get the absolute path of the variable
                $varAbsPath = $this->_existsPathInParents($varPath, $startPath);

                // Get the actual value
                $varValue = $this->_getContent($varAbsPath);
            }

            // If we've got an array something went wrong
            if (is_array($varValue)) {
                if ('Properties' === $varPath[0]) {
                    continue;
                    // Core_Logger::error(get_class($this) . '::_resolveVariablesInPath(' . implode('.', $startPath) . '): Variable "' . $var . '" points to an array! Did you forget to set it in Properties?');
                } else {
                    Core_Logger::error(get_class($this) . '::_resolveVariablesInPath(' . implode('.', $startPath) . '): Variable "' . $var . '" points to an array!');
                }
                continue;
            }

            // Replace in the new string
            $newString = str_replace('${' . $var . '}', $varValue, $newString);
        }

        // Return the new string
        return $newString;
    }

    private function _setRuntimeConstants() {
        $time = time();

        $this->_config['timestamp'] = $time;
        $this->_config['datetime']  = date('Y-m-d H:i:s', $time);
        $this->_config['time']      = date('H:i:s', $time);
        $this->_config['date']      = date('Y-m-d', $time);
    }
}
