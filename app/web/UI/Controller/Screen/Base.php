<?php

abstract class UI_Controller_Screen_Base {
  /**
   * The class name of the page
   * @var string
   */
  protected $class;

  /**
   * @var mixed
   */
  protected $create;

  /**
   * The display name of the page
   * @var string
   */
  protected $name;

  public function __construct() {
    $this->create = new UI_Controller_Screen_Create;
  }

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
   * @return mixed
   */
  public function getScript() {
    return $this->setScript();
  }

  /**
   * @return array
   */
  public function getStyle() {
    return $this->setStyle();
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

  protected function setScript() {
    return [];
  }

  protected function setStyle() {
    return [];
  }
}
?>
