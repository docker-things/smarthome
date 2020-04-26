<?php

class UI_Controller_Screen_Main extends UI_Controller_Screen_Base {
  /**
   * The class name of the page
   * @var string
   */
  protected $class = 'main';

  /**
   * The display name of the page
   * @var string
   */
  protected $name = 'Ambient';

  protected function setHTML() {
    return [
      '<div class="top verticalStatusContainer">',
      $this->create->verticalStatus('outsideTemp', 'Temperature', '-', '°C'),
      $this->create->verticalSeparator(),
      $this->create->verticalStatus('feelsLike', 'Feels Like', '-', '°C'),
      $this->create->verticalSeparator(),
      $this->create->verticalStatus('outsideHumidity', 'Humidity', '-', '%'),
      $this->create->verticalSeparator(),
      $this->create->verticalStatus('outsidePressure', 'Pressure', '-', 'hPa'),
      $this->create->verticalSeparator(),
      $this->create->verticalStatus('windSpeed', 'Wind', '-', 'km/h'),
      '</div>',
      '<div class="bottom verticalStatusContainer">',
      $this->create->verticalStatus('insideTemp', 'Temperature', '-', '°C'),
      $this->create->verticalSeparator(),
      $this->create->verticalStatus('none', '', '', ''),
      $this->create->verticalSeparator(),
      $this->create->verticalStatus('insideHumidity', 'Humidity', '-', '%'),
      $this->create->verticalSeparator(),
      $this->create->verticalStatus('insidePressure', 'Pressure', '-', 'hPa'),
      $this->create->verticalSeparator(),
      $this->create->verticalStatus('none', '', '', ''),
      '</div>',
    ];
  }
}
?>
