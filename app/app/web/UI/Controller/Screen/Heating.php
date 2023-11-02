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
      $this->create->verticalStatus('livingroom', 'Livingroom', '-', '°C'),
      $this->create->verticalSeparator(),
      $this->create->verticalStatus('diningroom', 'Diningroom', '-', '°C'),
      $this->create->verticalSeparator(),
      $this->create->verticalStatus('kidroom', 'Kidroom', '-', '°C'),
      // $this->create->verticalSeparator(),
      // $this->create->verticalStatus('kitchen', 'Kitchen', '-', '°C'),
      '</div>',
      '<div class="temperatureSlider"></div>',
      '<div class="top verticalStatusContainer">',
      // $this->create->verticalStatus('purifier', 'Purifier', '-', '°C'),
      // $this->create->verticalSeparator(),
      $this->create->verticalStatus('outside', 'Outside', '-', '°C'),
      '</div>',
      '<div class="bottom"></div>',
    ];
  }
}
?>
