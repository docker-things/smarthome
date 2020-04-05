<?php

class UI_Controller_GetCron extends UI_Controller_GetTriggers {
    public function run() {

        // Get payload
        $payload = $this->getPayload();

        // Get data
        $possibleOperators = $this->_getPossibleOperators();
        $possibleFunctions = $this->_getPossibleFunctions();
        $cron              = $this->_getCron($possibleFunctions);

        // Return sccess message
        $this->_dumpSuccess([
            'cron'              => array_values($cron),
            'possibleOperators' => $possibleOperators,
            'possibleFunctions' => array_values($possibleFunctions),
        ]);
    }

    /**
     * @return array
     */
    private function _getCron(array $possibleFunctions) {

        // Get unprocessed triggers
        $tmp = $this->getConfig()->getRawConfig('Cron');

        // If empty
        if (!isset($tmp['jobs'])) {
            return [];
        }

        $cron = [];

        // Split the conditions and normalize the operators
        foreach ($tmp['jobs'] AS $jobName => $job) {

            if (!isset($job['if'])) {
                $job['if'] = [];
            }

            if (!isset($job['run'])) {
                continue;
            }

            $cron[] = [
                'name'     => $jobName,
                'interval' => $job['interval'],
                'if'       => $this->_processIFs($job['if']),
                'run'      => $this->_processRUNs($job['run'], $possibleFunctions),
            ];
        }

        return $cron;
    }
}