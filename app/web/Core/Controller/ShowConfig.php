<?php

class Core_Controller_ShowConfig extends Core_Controller_Base {
    public function run() {

        // Get config
        $config = $this->getConfig()->getConfig();

        // Show page
        echo $this->_drawHTML($config);
    }

    /**
     * @param $config
     */
    private function _drawHTML($config) {
        return '
            <html>
                <head>
                    <meta http-equiv="refresh" content="3" >
                    <title>World Config</title>
                </head>
                <body>
                    <pre>' . preg_replace('/\n([a-zA-Z0-9\-\_]+):/', "\n\n<b>\\1</b>:", yaml_emit($config)) . '</pre>
                </body>
            </html>
        ';
    }
}