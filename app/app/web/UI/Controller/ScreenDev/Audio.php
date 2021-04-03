<?php

class UI_Controller_ScreenDev_Audio extends UI_Controller_ScreenDev_Base {
  /**
   * The class name of the page
   * @var string
   */
  protected $class = 'audio';

  /**
   * The display name of the page
   * @var string
   */
  protected $name = 'Audio';

  protected function setHTML() {
    return [
      '<div id="showSnapControl"></div>',
    ];
  }

  protected function setJS() {
    return [
      'audio.snapcontrol.js',
    ];
  }
}
?>
