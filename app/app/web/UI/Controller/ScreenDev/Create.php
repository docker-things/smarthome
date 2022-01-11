<?php

class UI_Controller_ScreenDev_Create {
  public function arrowDown() {
    return '<svg class="Va(m) Px(6px) Cur(p)" width="20" height="20" viewBox="0 0 48 48" data-icon="arrow-down" style="fill: rgb(255, 255, 255); stroke: rgb(255, 255, 255); stroke-width: 0px; vertical-align: bottom;"><path d="M34.7 29.5c.793-.773.81-2.038.04-2.83-.77-.79-2.037-.81-2.83-.038l-5.677 5.525V8.567h-4v23.59l-5.68-5.525c-.79-.77-2.058-.753-2.827.04-.378.388-.566.89-.566 1.394 0 .52.202 1.042.605 1.434l10.472 10.183L34.7 29.5z"></path></svg>';
  }

  public function arrowUp() {
    return '<svg class="Va(m) Px(6px) Cur(p)" width="20" height="20" viewBox="0 0 48 48" data-icon="arrow-up" style="fill: rgb(255, 255, 255); stroke: rgb(255, 255, 255); stroke-width: 0px; vertical-align: bottom;"><path d="M13.764 18.75c-.792.772-.808 2.037-.04 2.828.772.792 2.038.81 2.83.04l5.678-5.526v23.59h4v-23.59l5.68 5.525c.79.77 2.058.753 2.827-.04.377-.388.565-.89.565-1.394 0-.52-.202-1.042-.605-1.434L24.23 8.566 13.763 18.75z"></path></svg>';
  }

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
      '<div class="icon" style="background-image:url(/res/img/screen/' . $class . '/menu-icon.png);"></div>',
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
