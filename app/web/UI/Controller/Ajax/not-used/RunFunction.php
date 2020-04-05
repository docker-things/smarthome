<?php

class UI_Controller_RunFunction extends UI_Controller_Base {
    public function run() {

        // Get payload
        $payload = $this->getPayload();

        // Check if we have the required params
        foreach (['function'] AS $field) {
            if (!isset($payload[$field]) || empty($payload[$field])) {
                $this->_dumpError('Please provide "' . $field . '"');
            }
        }

        $tmp          = explode('.', $payload['function'], 2);
        $objectName   = $tmp[0];
        $tmp          = explode('(', $tmp[1], 2);
        $functionName = $tmp[0];
        $tmp          = explode(')', $tmp[1], 2);
        $params       = str_replace("'", '', $tmp[0]);

        $willRun = [];

        if ('brightness' == $functionName) {
            if (intval($params) == 0) {
                $willRun[] = $objectName . ".off()";
            } else {
                $willRun[] = $objectName . ".on()";
                $willRun[] = $objectName . ".brightness('" . $params . "')";
            }
        } else {
            $willRun[] = $payload['function'];
        }

        foreach ($willRun AS $function) {

            // Start buffering output
            ob_start();

            // Init function object
            $function = new Core_Function($this, $function);

            // Run function
            $result = $function->process();

            // Get buffered output
            $output = ob_get_clean();

            // check result
            if (!$result) {
                $this->_dumpError($output);
            }
        }

        $this->_dumpSuccess([]);
    }
}