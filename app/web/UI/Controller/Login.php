<?php

class UI_Controller_Login extends UI_Controller_Base {
    public function run() {

        // Get payload
        $payload = $this->getPayload();

        // Check if we have the required params
        foreach (['username', 'password'] AS $field) {
            if (!isset($payload[$field]) || empty($payload[$field])) {
                $this->_dumpError('Please enter ' . $field . '!');
            }
        }

        // Check credentials and store the result in session
        $_SESSION['login'] = $this->getState()->isUserValid($payload['username'], $payload['password']);

        // Return result
        if ($_SESSION['login']) {
            $this->_dumpSuccess([]);
        } else {
            unset($_SESSION['login']);
            $this->_dumpError('Invalid credentials!');
        }
    }
}