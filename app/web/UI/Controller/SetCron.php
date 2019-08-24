<?php

class UI_Controller_SetCron extends UI_Controller_Base {
    public function run() {

        // Get payload
        $payload = $this->getPayload();

        // Check if we have the required params
        foreach (['cron'] AS $field) {
            if (!isset($payload[$field]) || empty($payload[$field])) {
                $this->_dumpError('Please provide "' . $field . '"');
            }
        }

        // Rebuld the trigger in the format needed for the configuration
        $cron = $this->_rebuildCron($payload['cron']);

        // Check if the trigger is valid
        $this->_validateCron($cron);

        // Set new definition in config
        $this->getConfig()->setRawCronDefinition($cron);

        // Save config to disk
        if (!$this->getConfig()->saveRaw('Cron')) {
            $this->_dumpError('Couldn\'t save the configuration!');
        }

        // Return sccess message
        $this->_dumpSuccess([]);
    }

    /**
     * @param  $payload
     * @return mixed
     */
    private function _rebuildCron($jobs) {
        $cron = [];

        foreach ($jobs AS $job) {

            $trigger = [
                'interval' => $job['interval'],
            ];

            // rebuild ifs
            foreach ($job['if'] AS $if) {
                $trigger['if'][] = implode(' ', [
                    $if['arg1'],
                    $if['cond'],
                    $if['arg2'],
                ]);
            }

            // rebuild runs
            foreach ($job['run'] AS $run) {

                // Get function definition
                $function = $run['function'];

                // Check if there are any params
                if (!is_array($run['params']) || empty($run['params'])) {
                    $trigger['run'][] = $run['function'];
                    continue;
                }

                // Prepare function params to be replaced
                $replace = [
                    '(' => '([',
                    ')' => '])',
                    ',' => '],[',
                ];
                $function = str_replace(array_keys($replace), array_values($replace), $function);

                // Prepare job params to be replaced
                $replace = [];
                foreach ($run['params'] AS $param) {
                    $replace['[' . $param['name'] . ']'] = "'" . $param['value'] . "'";
                }
                $function = str_replace(array_keys($replace), array_values($replace), $function);

                // Append
                $trigger['run'][] = $function;
            }
            $cron[$job['name']] = $trigger;
        }

        return $cron;
    }

    /**
     * @param $cron
     */
    private function _validateCron($cron) {

        // $this->_dumpError(yaml_emit($cron));

        foreach($cron AS $job) {

            if (!isset($job['interval']) || empty($job['interval'])) {
                $this->_dumpError('You didn\'t provide the interval!');
            }

            if ($job['interval'] < 1) {
                $this->_dumpError('The interval can\'t be lower than 1 second!');
            }

            if (!isset($job['run']) || empty($job['run'])) {
                $this->_dumpError('You must add at least a function!');
            }
        }
    }
}