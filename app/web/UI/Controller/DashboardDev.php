<?php

class UI_Controller_DashboardDev extends Core_Controller_Base {
  /**
   * CSS files
   * @var array
   */
  private $css = [
    'jquery.toast.min.css',
    'dashboardDev.css',
  ];

  /**
   * Generated screens HTML
   * @var array
   */
  private $html = [];

  /**
   * JS files
   * @var array
   */
  private $js = [
    'jquery.min.js',
    'jquery.toast.min.js',
    // 'popper.min.js',
    // 'bootstrap-material-design.min.js',
    'paho-mqtt-min.js',
    'dashboardDev.js',
  ];

  /**
   * @var array
   */
  private $screenObjects = [];

  /**
   * Screen classes
   * @var array
   */
  private $screens = [
    'Heating',
    'Roborock',
    'Main',
    // 'Sleeping',
    // 'Overview',
  ];

  /**
   * TODO: When trigger on roborock switch on its screen
   * When trigger on heating switch on its screen
   * When light movement at entrance switch on house overview
   * When sleeping switch on sleeping (check dashboard room where it's located)
   */

  /**
   * Raw CSS styles to be included in <head>
   * @var array
   */
  private $style = [];

  /**
   * @var string
   */
  private $title = 'SmartHome';

  public function __construct() {
    $this->create = new UI_Controller_ScreenDev_Create;
  }

  public function run() {
    $this->initScreens();
    echo '<!doctype html>';
    echo '<html><head>';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1" />';
    echo '<title>' . $this->title . '</title>';
    echo $this->getCSS();
    echo $this->getStyle();
    echo $this->getJS();
    echo '</head><body>';
    echo '<div class="mainContainer">';
    echo '<div class="overviewContainer">' . $this->getHTML() . '</div>';
    echo '<div class="overlay"></div>';
    echo '<div class="menuContainer">' . $this->getMenuHTML() . '</div>';
    echo '</div>';
    echo '</body></html>';
  }

  private function getCSS() {
    $css = [];
    foreach ($this->css AS $path) {
      $css[] = '<link href="/res/css/' . $path . '" rel="stylesheet" type="text/css" media="all">';
    }
    return implode("\n", $css);
  }

  private function getHTMl() {
    return implode("\n", $this->html);
  }

  private function getJS() {
    $js = [];
    foreach ($this->js AS $path) {
      $js[] = '<script src="/res/js/' . $path . '"></script>';
    }
    return implode("\n", $js);
  }

  private function getMenuHTML() {
    $html = [];
    // $html[] = '<div class="menuTitle">' . $this->title . '</div>';
    $html[] = '<div class="screensSelector">';
    foreach ($this->screens AS $screen) {
      $class = strtolower($screen);
      $html[] = $this->create->verticalRoundButton($class, $screen, $class);
      $html[] = $this->create->verticalSeparator();
    }
    array_pop($html);
    $html[] = '</div>';
    return implode("\n", $html);
  }

  private function getStyle() {
    return '<style>' . implode("\n", $this->style) . '</style>';
  }

  /**
   * @return mixed
   */
  private function initScreens() {
    if (get_class($this) == 'UI_Controller_DashboardDev') {
      $dev = 'Dev';
    } else {
      $dev = '';
    }

    foreach ($this->screens AS $name) {
      $objectName = 'UI_Controller_Screen' . $dev . '_' . $name;
      $screen     = new $objectName($this);

      $this->screenObjects[$name] = $screen;

      foreach ($screen->getJS() AS $path) {
        $this->js[] = 'screen' . $dev . '/' . $path;
      }
      foreach ($screen->getCSS() AS $path) {
        $this->css[] = 'screen' . $dev . '/' . $path;
      }
      $this->style = array_merge($this->style, $screen->getStyle());
      $this->html  = array_merge($this->html, $screen->getHTML());
    }
  }
}
?>
