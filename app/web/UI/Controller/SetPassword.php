<?php

class UI_Controller_SetPassword extends UI_Controller_Base {
    public function run() {

        // Get payload
        $payload = $this->getPayload();

        // Hardcoded username - not allowing other users
        $payload['username'] = 'admin';

        // Check if we have the required params
        foreach (['username', 'currentPassword', 'password'] AS $field) {
            if (!isset($payload[$field]) || empty($payload[$field])) {
                $this->_dumpError('Please provide "' . $field . '"');
            }
        }

        // Check credentials
        if (!$this->getState()->isUserValid($payload['username'], $payload['currentPassword'])) {
            $this->_dumpError('Current password is different!');
        }

        // Set new password
        $this->getState()->setPassword($payload['username'], $payload['password']);

        // Return sccess message
        $this->_dumpSuccess([]);
    }
}