<?php

class UI_Controller_GetLogs extends UI_Controller_Base {
    public function run() {

        // Get payload
        $payload = $this->getPayload();

        // Check if we have the required params
        foreach (['lines'] AS $field) {
            if (!isset($payload[$field]) || empty($payload[$field])) {
                $this->_dumpError('Please provide "' . $field . '"');
            }
        }

        // Get last 100 lines of logs
        $logs = $this->getState()->getLogs($payload['lines']);

        // Return sccess message
        $this->_dumpSuccess($logs);
    }
}