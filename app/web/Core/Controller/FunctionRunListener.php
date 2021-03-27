<?php

class Core_Controller_FunctionRunListener extends Core_Controller_Base {
    /**
     * Watch the MQTT feed and simulate incoming requests
     */
    public function run() {

        $cmd = "mosquitto_sub -h localhost -t 'core-function/run'";

        $descriptorspec = [
            0 => ["pipe", "r"], // stdin is a pipe that the child will read from
            1 => ["pipe", "w"], // stdout is a pipe that the child will write to
            2 => ["pipe", "w"], // stderr is a pipe that the child will write to
        ];

        Core_Logger::info('Start listening to MQTT');

        // Try launching the process
        do {
            // Launch process
            $process = proc_open($cmd, $descriptorspec, $pipes, realpath('./'), []);

            // Stop if we can't
            if (!is_resource($process)) {
                Core_Logger::warn('Core_Controller_MqttListener::run(): Couldn\'t launch mosquitto_sub process with proc_open()! Sleeping 5 seconds...');
                sleep(5);
            }
        } while (!is_resource($process));

        Core_Logger::info('Waiting requests...');

        // Whenever a new line is fetched
        while ($line = trim(fgets($pipes[1]))) {

            // Show line
            Core_Logger::info('Function: ' . $line);

            // Run
            $functions = explode(';;', urldecode($line));
            foreach($functions AS $i => $function) {
                $functions[$i] = urlencode($function);
            }
            exec("php web/runFunctions.php '" . implode("' '", $functions) . "' > /dev/null 2>&1 &");
        }

        Core_Logger::info('Stopped listening to MQTT');
    }
}