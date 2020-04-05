<?php

class UI_Controller_Ajax_Base extends Core_Controller_Base {
    /**
     * @var string
     */
    private $_controller = '';

    /**
     * @param string $message
     */
    protected function _dumpError(string $message) {
        http_response_code(500);
        die(json_encode([
            'status'  => 'Failed',
            'message' => $message,
        ]));
    }

    /**
     * @param mixed $data
     */
    protected function _dumpSuccess($data) {
        die(json_encode([
            'status' => 'success',
            'data'   => $data,
        ]));
    }
}