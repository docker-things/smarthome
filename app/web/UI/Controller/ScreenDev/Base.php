<?php

abstract class UI_Controller_ScreenDev_Base {
  /**
   * The class name of the page
   * @var string
   */
  protected $class;

  /**
   * The display name of the page
   * @var string
   */
  protected $name;

  /**
   * Get the actual content of the page
   * @return string HTML
   */
  public function get() {
    die('Screen doesn\'t implement get()');
  }

  /**
   * @return array
   */
  public function getCSS() {
    return array_merge([$this->class . '.css'], $this->setCSS());
  }

  /**
   * @return array
   */
  public function getHTML() {
    $html   = [];
    $html[] = '<div class="screen ' . $this->class . '" name="' . $this->class . '">';
    $html[] = '<div class="titleContainer">';
    $html[] = '<div class="prevButton">&lt;</div>';
    $html[] = '<div class="title">' . $this->name . '</div>';
    $html[] = '<div class="status"></div>';
    $html[] = '<div class="nextButton">&gt;</div>';
    $html[] = '</div>';
    $html[] = '<div class="container">';
    $html   = array_merge($html, $this->setHTML());
    $html[] = '</div>';
    $html[] = '</div>';
    return $html;
  }

  /**
   * @return array
   */
  public function getJS() {
    return array_merge([$this->class . '.js'], $this->setJS());
  }

  /**
   * @return array
   */
  public function getStyle() {
    return $this->setStyle();
  }

  /**
   * @param $class
   * @param $name
   * @param $action
   */
  protected function horizontalButton($class, $name) {
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
  protected function map($info, $actions) {
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

  protected function setCSS() {
    return [];
  }

  protected function setHTML() {
    return [];
  }

  protected function setJS() {
    return [];
  }

  protected function setStyle() {
    return [];
  }

  protected function verticalSeparator() {
    return '<div class="verticalSeparator"></div>';
  }

  /**
   * @param $class
   * @param $name
   * @param $value
   * @param $unit
   */
  protected function verticalStatus($class, $name, $value, $unit) {
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
