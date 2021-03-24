<?php

class Core_Controller_Status extends Core_Controller_Base {
  /**
   * Watch the MQTT feed and simulate incoming requests
   */
  public function run() {

    $object = isset($_GET['object']) ? explode(',', $_GET['object']) : [];
    $param  = isset($_GET['param']) ? explode(',', $_GET['param']) : [];
    $value  = isset($_GET['value']) ? explode(',', $_GET['value']) : [];

    $no_object = isset($_GET['no-object']) ? explode(',', $_GET['no-object']) : [];
    $no_param  = isset($_GET['no-param']) ? explode(',', $_GET['no-param']) : [];
    $no_value  = isset($_GET['no-value']) ? explode(',', $_GET['no-value']) : [];

    $no_from = isset($_GET['no-from']);
    $no_time = isset($_GET['no-time']);

    // Get state
    $state = $this->getState()->getFullState();

    // Apply filters
    $state = $this->_applyObjectFilters($state, $object);
    $state = $this->_applyParamFilters($state, $param);
    $state = $this->_applyValueFilters($state, $value);
    $state = $this->_applyObjectExclusionFilters($state, $no_object);
    $state = $this->_applyParamExclusionFilters($state, $no_param);
    $state = $this->_applyValueExclusionFilters($state, $no_value);

    $today = date('Y-m-d');

    // Fill temporary values waiting to be set
    foreach ($state AS $object => $variables) {
      foreach ($variables AS $variable => $tmp) {
        $state[$object][$variable] = $tmp['value'] . '<span style="color:lightgrey;">';
        if (!$no_from) {
          $state[$object][$variable] .= ' from ' . $tmp['prevValue'];
        }
        if (!$no_time) {
          if (date('Y-m-d', $tmp['timestamp']) == $today) {
            $state[$object][$variable] .= ' @ ' . date('H:i:s', $tmp['timestamp']);
          } else {
            $state[$object][$variable] .= ' @ ' . date('Y-m-d H:i:s', $tmp['timestamp']);
          }
        }
        $state[$object][$variable] .= '</span>';

        if ('' !== $tmp['tmpValue']) {
          $state[$object][$variable] .= ' (Detected "' . $tmp['tmpValue'] . '" ' . $tmp['tmpTimes'] . ' time' . (1 == $tmp['tmpTimes'] ? '' : 's') . ')';
        }
      }
    }

    // Sort
    ksort($state);

    // Show page
    echo $this->_drawHTML($state);
  }

  private function _applyObjectExclusionFilters($state, $filters) {
    if (empty($filters)) {
      return $state;
    }
    $newState = [];
    foreach ($state AS $object => $values) {
      $ok = true;
      foreach ($filters AS $filter) {
        if (stripos($object, $filter) !== false) {
          $ok = false;
          break;
        }
      }
      if ($ok) {
        $newState[$object] = $values;
      }
    }
    return $newState;
  }

  private function _applyObjectFilters($state, $filters) {
    if (empty($filters)) {
      return $state;
    }
    $newState = [];
    foreach ($state AS $object => $values) {
      foreach ($filters AS $filter) {
        if (stripos($object, $filter) !== false) {
          $newState[$object] = $values;
        }
      }
    }
    return $newState;
  }

  private function _applyParamExclusionFilters($state, $filters) {
    if (empty($filters)) {
      return $state;
    }
    $newState = [];
    foreach ($state AS $object => $values) {
      foreach ($values AS $key => $value) {
        $ok = true;
        foreach ($filters AS $filter) {
          if (stripos($key, $filter) !== false) {
            $ok = false;
            break;
          }
        }
        if ($ok) {
          $newState[$object][$key] = $value;
        }
      }
    }
    return $newState;
  }

  private function _applyParamFilters($state, $filters) {
    if (empty($filters)) {
      return $state;
    }
    $newState = [];
    foreach ($state AS $object => $values) {
      foreach ($values AS $key => $value) {
        foreach ($filters AS $filter) {
          if (stripos($key, $filter) !== false) {
            $newState[$object][$key] = $value;
            break;
          }
        }
      }
    }
    return $newState;
  }

  private function _applyValueExclusionFilters($state, $filters) {
    if (empty($filters)) {
      return $state;
    }
    $newState = [];
    foreach ($state AS $object => $values) {
      foreach ($values AS $key => $value) {
        $ok = true;
        foreach ($filters AS $filter) {
          if (stripos($value['value'], $filter) !== false) {
            $ok = false;
            break;
          }
        }
        if ($ok) {
          $newState[$object][$key] = $value;
        }
      }
    }
    return $newState;
  }

  private function _applyValueFilters($state, $filters) {
    if (empty($filters)) {
      return $state;
    }
    $newState = [];
    foreach ($state AS $object => $values) {
      foreach ($values AS $key => $value) {
        foreach ($filters AS $filter) {
          if (stripos($value['value'], $filter) !== false) {
            $newState[$object][$key] = $value;
            break;
          }
        }
      }
    }
    return $newState;
  }

  /**
   * @param $state
   */
  private function _drawHTML($state) {
    return '
      <html>
        <head>
          <meta http-equiv="refresh" content="3" >
          <title>World State: ' . implode(', ', array_keys($state)) . '</title>
        </head>
        <body>
          <pre>' . preg_replace('/\n([a-zA-Z0-9\-\_]+):/', "\n\n<b>\\1</b>:", yaml_emit($state)) . '</pre>
        </body>
      </html>
      ';
  }
}