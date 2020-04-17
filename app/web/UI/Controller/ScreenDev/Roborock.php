<?php

class UI_Controller_ScreenDev_Roborock extends UI_Controller_ScreenDev_Base {
  /**
   * The class name of the page
   * @var string
   */
  protected $class = 'roborock';

  /**
   * The display name of the page
   * @var string
   */
  protected $name = 'Roborock';

  /**
   * @var array
   */
  protected $style = [];

  protected function setHTML() {
    return [
      '<div class="top verticalStatusContainer">',
      $this->create->verticalStatus('area', 'Area', '-', 'm²'),
      $this->create->verticalSeparator(),
      $this->create->verticalStatus('battery', 'Battery', '-', '%'),
      $this->create->verticalSeparator(),
      $this->create->verticalStatus('time', 'Time', '-', 'min'),
      '</div>',
      $this->create->map([
        'all' => '<div class="poiIcon"></div>',
      ], []),
      '<div class="bottom">',
      $this->create->horizontalButton('dockButton', 'Dock'),
      $this->create->verticalSeparator(),
      $this->create->horizontalButton('cleanHouseButton', 'Clean'),
      $this->create->horizontalButton('cleanRoomButton', 'Clean Room'),
      $this->create->horizontalButton('pauseButton', 'Pause'),
      $this->create->horizontalButton('resumeButton', 'Resume'),
      '</div>',
    ];
  }
}
?>
