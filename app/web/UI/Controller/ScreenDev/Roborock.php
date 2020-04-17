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
      $this->verticalStatus('area', 'Area', '-', 'm²'),
      $this->verticalSeparator(),
      $this->verticalStatus('battery', 'Battery', '-', '%'),
      $this->verticalSeparator(),
      $this->verticalStatus('time', 'Time', '-', 'min'),
      '</div>',
      $this->map([
        'all' => '<div class="poiIcon"></div>',
      ], []),
      '<div class="bottom">',
      $this->horizontalButton('dockButton', 'Dock'),
      $this->verticalSeparator(),
      $this->horizontalButton('cleanHouseButton', 'Clean'),
      $this->horizontalButton('cleanRoomButton', 'Clean Room'),
      $this->horizontalButton('pauseButton', 'Pause'),
      $this->horizontalButton('resumeButton', 'Resume'),
      '</div>',
    ];
  }
}
?>
