<?php

class UI_Controller_Ajax_Main extends UI_Controller_Base {
    public function run() {

        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
        header('Access-Control-Max-Age: 999999');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

        // Get payload
        $payload = $this->getPayload();

        // Check if we received the controller name
        if (!isset($payload['controller'])) {
            $this->_dumpError('You didn\'t pass any webui endpoint!');
        }

        // Build the class name
        $controllerClass = 'UI_Controller_' . ucfirst($payload['controller']);

        // Check if it's an infinite loop
        if ('UI_Controller_Main' == $controllerClass) {
            $this->_dumpError('You can\'t require a reserved controller!');
        }

        // Check if the controller exists
        if (!classExists($controllerClass)) {
            $this->_dumpError('Required controller doesn\'t exist!');
        }

        // Start session (used for login)
        session_id('123');
        session_start();

        $payloadController = $payload['controller'];
        unset($payload['controller']);

        // Check if logged in
        if ('UI_Controller_Login' != $controllerClass && !isset($_SESSION['login'])) {
            $this->_dumpError('You\'re not logged in!');
        }

        // Initialize new controller starting from this one
        $controller = new $controllerClass($this);

        // Launch the controller
        $controller->run();
    }
}