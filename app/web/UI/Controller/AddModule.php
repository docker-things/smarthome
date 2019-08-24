<?php

class UI_Controller_AddModule extends UI_Controller_Base {
    public function run() {

        // Get payload
        $payload = $this->getPayload();

        // List of required files
        $moduleFiles = [
            'Cron'       => '',
            'Functions'  => '',
            'GUI'        => "---\n\ntype: sensor",
            'Incoming'   => '',
            'Properties' => '',
        ];

        // Check if we have the required params nonempty
        foreach (['name'] AS $field) {
            if (!isset($payload[$field]) || empty($payload[$field])) {
                $this->_dumpError('Please provide "' . $field . '"');
            }
        }

        // Save the files
        foreach ($moduleFiles AS $file => $content) {
            $result = $this->getConfig()->saveRawFile('Module.' . $payload['name'] . '.' . $file, $content);
            if (!empty($result)) {
                $this->_dumpError($result);
            }
        }

        // Return success message
        $this->_dumpSuccess([]);
    }
}