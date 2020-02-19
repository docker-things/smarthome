<?php

class Core_Controller_Config extends Core_Controller_Base {
    /**
     * @var array
     */
    private $customView = [
        'Heating' => [
            'alias'   => 'Heating Temperature',
            'details' => [
                // 'Bathroom-Temperature.temperature'   => 'Bathroom Temperature',
                'Livingroom-Temperature.temperature' => 'Livingroom Temperature',
            ],
            'data'    => [
                'Awake'       => [
                    'data' => 'presenceMinTemp',
                    'type' => 'number',
                    'attr' => 'step="0.5"',
                ],
                'Sleeping'    => [
                    'data' => 'sleepingMinTemp',
                    'type' => 'number',
                    'attr' => 'step="0.5"',
                ],
                'Empty-House' => [
                    'data' => 'noPresenceMinTemp',
                    'type' => 'number',
                    'attr' => 'step="0.5"',
                ],
            ],
        ],
    ];

    public function run() {
        $title = 'Config';
        if (isset($_GET['object'])) {
            $object = $_GET['object'];
            if (isset($this->customView[$object]['alias'])) {
                $title = $this->customView[$object]['alias'] . ' - Config';
            } else {
                $title = $object . ' - Config';
            }
        }

        echo '<html><head>
            <meta name="viewport" content="width=device-width, initial-scale=1" />
            <title>' . $title . '</title>
            <style>
            html, body {
                padding: 0;
                margin: 0;
                text-align: center;
            }
            body {
                padding: 10px;
            }
            pre {
                text-align: left;
                margin: 5px;
            }
            table {
                margin: 0 auto;
            }
            td {
                padding: 0px 10px;
            }
            .rawVariables {
                opacity: 0.5;
                color: red;
            }
            input[type=text],
            input[type=number],
            input[type=submit] {
                width: 150px;
                height: 32px;
                padding: 5px;
                margin: 5px;
            }
            input[type=submit] {
                width: 70px;
                padding: 0;
            }
            .rawVariables * {
                color: red;
            }
            </style>
            </head><body>';
        $this->_savePostData();
        foreach ($this->getState()->getState() as $object => $vars) {
            if (isset($_GET['object']) && strtolower($_GET['object']) != strtolower($object)) {
                continue;
            }
            echo $this->_editForm($object, $vars);
        }
        if (!isset($_GET['object']) || isset($_GET['raw'])) {
            echo $this->_addForm();
        }
        echo '</body></html>';
    }

    private function _addForm() {
        $object = isset($_GET['object']) ? $_GET['object'] : '';
        if (!empty($object)) {
            if (isset($this->customView[$object]) && !isset($_GET['raw'])) {
                return '';
            }
        }
        return '
        <form method="POST" name="addNew">
            <h2>ADD</h2>
            <input type="' . (empty($object) ? 'text' : 'hidden') . '" name="objectName" placeholder="Object" value="' . $object . '">
            <input type="text" name="variable" placeholder="Variable" value="">
            <input type="text" name="value" placeholder="Value" value="">
            <input type="submit" name="addButton" value="Add">
        </form>
        ';
    }

    /**
     * @param $object
     * @param $vars
     */
    private function _configObject($object, $vars) {
        echo '<h2>' . $object . '</h2>';
        foreach ($vars AS $name => $value) {
            echo $object . '.' . $name . ' = ' . $value . '<br>';
        }
        echo '<br><hr>';
    }

    /**
     * @param  $object
     * @param  $vars
     * @return mixed
     */
    private function _editForm($object, $vars) {
        $form = '';

        $gotCustomView = isset($this->customView[$object]);

        $form .= '<div class="' . ($gotCustomView ? 'gotCustomView' : 'rawView') . '">';

        // Custom view
        if ($gotCustomView && !isset($_GET['raw'])) {
            $form .= '<form method="POST" name="' . $object . '-CustomView" class="customVariables">';
            $form .= '<table>';
            $form .= '<tr><th colspan="3">';

            // Title
            $objectAlias = $object;
            if (isset($this->customView[$object]['alias'])) {
                $objectAlias = $this->customView[$object]['alias'];
            }
            $form .= '<h2>' . $objectAlias . '</h2>';
            $form .= '<input type="hidden" name="objectName" value="' . $object . '">';
            $form .= '</th></tr>';

            // Variables
            $first = true;
            foreach ($this->customView[$object]['data'] AS $alias => $var) {
                $attr = isset($var['attr']) ? $var['attr'] : '';
                $form .= '<tr>';
                $form .= '<td>' . str_replace('-', ' ', $alias) . '</td>';
                $form .= '<td><input type="' . $var['type'] . '" ' . $attr . ' name="' . $alias . '" placeholder="Value" value="' . $vars[$var['data']] . '"></td>';
                if ($first) {
                    $first = false;
                    $form .= '<td rowspan="' . count($this->customView[$object]['data']) . '"><input type="submit" name="editButton" value="Save"></td>';
                }
                $form .= '</tr>';
            }
            $form .= '</table>';
            $form .= '</form>';

            // Details
            $form .= '<table>';
            foreach ($this->customView[$object]['details'] AS $detail => $alias) {
                $tmp   = explode('.', $detail, 2);
                $value = $this->getState()->getVariableValue($tmp[0], $tmp[1], '?');
                $form .= '<tr>';
                $form .= '<td>' . $alias . ':</td>';
                $form .= '<td>' . $value . '</td>';
                $form .= '</tr>';
            }
            $form .= '</table>';

            // If no raw, return
            if (!isset($_GET['raw'])) {
                return $form;
            }
        }

        // RAW variables
        ksort($vars);
        $form .= '<form method="POST" name="' . $object . '" class="rawVariables">';
        $form .= '<table>';
        $form .= '<tr><th colspan="4">';

        // Title
        $form .= '<h2>' . $object . '</h2>';
        $form .= '<input type="hidden" name="objectName" value="' . $object . '">';
        $form .= '</th></tr>';

        // Variables
        $first = true;
        foreach ($vars AS $name => $value) {
            $form .= '<tr>';
            $form .= '<td>' . $name . '</td>';
            $form .= '<td><input type="text" name="' . $name . '" placeholder="Value" value="' . $value . '"></td>';
            if ($first) {
                $first = false;
                $form .= '<td rowspan="' . count($vars) . '"><input type="submit" name="editButton" value="Save"></td>';
            }
            $form .= '</tr>';
        }

        $form .= '</table>';
        $form .= '</form>';
        $form .= '</div>';
        return $form;
    }

    private function _savePostData() {
        if (!empty($_POST)) {
            if (isset($_POST['objectName'])) {
                $object = $_POST['objectName'];
                unset($_POST['objectName']);

                if (isset($_POST['editButton'])) {
                    unset($_POST['editButton']);
                    foreach ($_POST as $var => $value) {
                        $this->_setStateWrapper($object, $var, $value);
                    }
                } elseif (isset($_POST['addButton'])) {
                    unset($_POST['addButton']);
                    $this->_setStateWrapper(
                        $object,
                        $_POST['variable'],
                        $_POST['value']
                    );
                } elseif (isset($_POST['addButton'])) {
                    echo '<h2>Unknown action!</h2>';
                }
            } else {
                echo '<h2>Got no object name!</h2>';
            }
        }
    }

    /**
     * @param  $object
     * @param  $var
     * @param  $value
     * @return null
     */
    private function _setStateWrapper($object, $var, $value) {
        if (isset($_GET['raw'])) {
            echo '<pre>';
        } else {
            ob_start();
        }
        $done = false;
        if ('Heating' == $object) {
            if ('Awake' == $var) {
                $this->getState()->set($object, 'presenceMinTemp', floatval($value));
                $this->getState()->set($object, 'presenceMaxTemp', floatval($value) + 0.5);
                $done = true;
            } elseif ('Sleeping' == $var) {
                $this->getState()->set($object, 'sleepingMinTemp', floatval($value));
                $this->getState()->set($object, 'sleepingMaxTemp', floatval($value) + 0.5);
                $done = true;
            } elseif ('Empty-House' == $var) {
                $this->getState()->set($object, 'noPresenceMinTemp', floatval($value));
                $this->getState()->set($object, 'noPresenceMaxTemp', floatval($value) + 0.5);
                $done = true;
            }
        }
        if (!$done) {
            $this->getState()->set($object, $var, $value);
        }
        if (isset($_GET['raw'])) {
            echo '</pre>';
        } else {
            ob_get_clean();
        }
    }
}
?>