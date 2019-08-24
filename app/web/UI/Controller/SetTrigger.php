<?php

class UI_Controller_SetTrigger extends UI_Controller_Base {
    public function run() {

        // Get payload
        $payload = $this->getPayload();

        // Check if we have the required params
        foreach (['trigger', 'actions'] AS $field) {
            if (!isset($payload[$field]) || empty($payload[$field])) {
                $this->_dumpError('Please provide "' . $field . '"');
            }
        }

        // Rebuld the trigger in the format needed for the configuration
        $trigger = $this->_rebuildTrigger($payload['actions']);

        // Check if the trigger is valid
        $this->_validateTrigger($trigger);

        // Set new definition in config
        $this->getConfig()->setRawTriggerDefinition(
            $payload['trigger'],
            $trigger
        );

        // Save config to disk
        if (!$this->getConfig()->saveRaw('Triggers')) {
            $this->_dumpError('Couldn\'t save the configuration!');
        }

        // Return sccess message
        $this->_dumpSuccess([]);
    }

    /**
     * @param  $payload
     * @return mixed
     */
    private function _rebuildTrigger($actions) {

        $triggers = [];

        foreach ($actions AS $action) {

            $trigger = [];

            // rebuild ifs
            foreach ($action['if'] AS $if) {
                $trigger['if'][] = implode(' ', [
                    $if['arg1'],
                    $if['cond'],
                    $if['arg2'],
                ]);
            }

            // rebuild runs
            foreach ($action['run'] AS $run) {

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

                // Prepare action params to be replaced
                $replace = [];
                foreach ($run['params'] AS $param) {
                    $replace['[' . $param['name'] . ']'] = "'" . $param['value'] . "'";
                }
                $function = str_replace(array_keys($replace), array_values($replace), $function);

                // Append
                $trigger['run'][] = $function;
            }
            $triggers[$action['name']] = $trigger;
        }

        return $triggers;
    }

    /**
     * @param $trigger
     */
    private function _validateTrigger($trigger) {
        if (empty($trigger)) {
            $this->_dumpError('No trigger contents!');
        }

        foreach ($trigger AS $group) {
            if (!isset($group['run']) || empty($group['run'])) {
                $this->_dumpError('You must add at least a function!');
            }
        }
    }
}