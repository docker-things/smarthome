<?php

class UI_Controller_Screen_Create {
  /**
   * @param $class
   * @param $name
   */
  public function horizontalButton($class, $name) {
    return implode([
      '<div class="horizontalButton ' . $class . '">',
      '<div class="icon"></div>',
      '<div class="name">' . $name . '</div>',
      '</div>',
    ]);
  }

  /**
   * @param $info
   * @param $actions
   */
  public function map($info, $actions) {
    $rooms = [
      'Bathroom',
      'Bedroom',
      'Entrance',
      'Hallway',
      'Kitchen',
      'Livingroom',
    ];
    $html   = [];
    $html[] = '<div class="map">';
    foreach ($rooms AS $room) {
      $html[] = '<div class="room ' . $room . '" name="' . $room . '">';
      $html[] = '<div class="name">' . $room . '</div>';
      $html[] = '<div class="info">';
      if (isset($info['all'])) {
        $html[] = $info['all'];
      }
      if (isset($info[$room])) {
        $html[] = $info[$room];
      }
      $html[] = '</div>';
      $html[] = '<div class="actions">';
      if (isset($actions['all'])) {
        $html[] = $actions['all'];
      }
      if (isset($actions[$room])) {
        $html[] = $actions[$room];
      }
      $html[] = '</div>';
      $html[] = '</div>';
    }
    $html[] = '</div>';
    return implode('', $html);
  }

  /**
   * @param $class
   * @param $name
   * @param $hiddenName
   */
  public function verticalRoundButton($class, $name, $hiddenName = '') {
    return implode([
      '<div class="verticalRoundButton ' . $class . '" name="' . $hiddenName . '">',
      '<div class="icon"></div>',
      '<div class="name">' . $name . '</div>',
      '</div>',
    ]);
  }

  public function verticalSeparator() {
    return '<div class="verticalSeparator"></div>';
  }

  /**
   * @param $class
   * @param $name
   * @param $value
   * @param $unit
   */
  public function verticalStatus($class, $name, $value, $unit) {
    return implode([
      '<div class="verticalStatus ' . $class . '">',
      '<div class="valueContainer">',
      '<div class="value">' . $value . '</div>',
      '<div class="unit">' . $unit . '</div>',
      '</div>',
      '<div class="name">' . $name . '</div>',
      '</div>',
    ]);
  }
}
?>
