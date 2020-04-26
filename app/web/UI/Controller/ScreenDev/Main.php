<?php

class UI_Controller_ScreenDev_Main extends UI_Controller_ScreenDev_Base {
  /**
   * The class name of the page
   * @var string
   */
  protected $class = 'main';

  /**
   * The display name of the page
   * @var string
   */
  protected $name = '';

  protected function setHTML() {
    return [
      '<div class="weatherSummary">',

      '<div class="textLine">',
      '<div class="icon"></div>',
      '<div class="value"></div>',
      '</div>',

      '<div class="minMaxLine">',
      '<div class="max"><div class="icon">',
      $this->create->arrowUp(),
      '</div><div class="value"></div><div class="unit">°</div></div>',
      '<div class="min"><div class="icon">',
      $this->create->arrowDown(),
      '</div><div class="value"></div><div class="unit">°</div></div>',
      '</div>',

      '<div class="bigTemp">',
      '<div class="value"></div>',
      '<div class="unit">°</div>',
      '</div>',

      '</div>',

      // '<div class="top verticalStatusContainer">',
      // $this->create->verticalStatus('outsideTemp', '', '', '°'),
      // $this->create->verticalSeparator(),
      // $this->create->verticalStatus('feelsLike', 'Feels Like', '-', '°C'),
      // $this->create->verticalSeparator(),
      // $this->create->verticalStatus('outsideHumidity', 'Humidity', '-', '%'),
      // $this->create->verticalSeparator(),
      // $this->create->verticalStatus('outsidePressure', 'Pressure', '-', 'hPa'),
      // $this->create->verticalSeparator(),
      // $this->create->verticalStatus('windSpeed', 'Wind', '-', 'km/h'),
      // '</div>',
      // '<div class="bottom verticalStatusContainer">',
      // $this->create->verticalStatus('insideTemp', 'Temperature', '-', '°C'),
      // $this->create->verticalSeparator(),
      // $this->create->verticalStatus('none', '', '', ''),
      // $this->create->verticalSeparator(),
      // $this->create->verticalStatus('insideHumidity', 'Humidity', '-', '%'),
      // $this->create->verticalSeparator(),
      // $this->create->verticalStatus('insidePressure', 'Pressure', '-', 'hPa'),
      // $this->create->verticalSeparator(),
      // $this->create->verticalStatus('none', '', '', ''),
      // '</div>',
    ];
  }
}
?>
