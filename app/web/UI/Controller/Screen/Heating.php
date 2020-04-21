<?php

class UI_Controller_Screen_Heating extends UI_Controller_Screen_Base {
  /**
   * The class name of the page
   * @var string
   */
  protected $class = 'heating';

  /**
   * The display name of the page
   * @var string
   */
  protected $name = 'Heating';

  protected function setHTML() {
    return [
      '<div class="top verticalStatusContainer">',
      $this->create->verticalStatus('bedroom', 'Bedroom', '-', '°C'),
      $this->create->verticalSeparator(),
      $this->create->verticalStatus('bathroom', 'Bathroom', '-', '°C'),
      $this->create->verticalSeparator(),
      $this->create->verticalStatus('outside', 'Outside', '-', '°C'),
      '</div>',
      '<div class="temperatureSlider"></div>',
      '<div class="bottom"></div>',
    ];
  }
}
?>
