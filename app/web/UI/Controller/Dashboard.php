<?php

class UI_Controller_Dashboard extends Core_Controller_Base {
  /**
   * CSS files
   * @var array
   */
  private $css = [
    'thirdparty/jquery.toast.min.css',
    'thirdparty/roundslider.min.css',
    'thirdparty/fontawesome.min.css',
    'thirdparty/weather-icons.min.css',
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
    'thirdparty/jquery.min.js',
    'thirdparty/jquery.toast.min.js',
    'thirdparty/roundslider.min.js',
    'thirdparty/paho-mqtt-min.js',
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
    'Main',
    'Roborock',
    'Audio',
    'NowPlaying',
    'Heating',
    // 'Sleeping',
    // 'Overview',
  ];

  /**
   * Raw JS script to be included in <head>
   * @var array
   */
  private $script = [];

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
    $this->create = new UI_Controller_Screen_Create;
  }

  public function run() {
    $this->initScreens();
    echo '<!doctype html>';
    echo '<html><head>';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1" />';
    echo '<title>' . $this->title . '</title>';
    echo "\n" . $this->getCSS();
    echo "\n" . $this->getStyle();
    echo "\n" . $this->getJS();
    echo "\n" . $this->getVariables();
    echo "\n" . $this->getScript();
    echo '</head><body>';
    echo '<div class="imagePreload"></div>';
    echo '<div class="mainContainer darkMode">';
    echo '<div class="overviewContainer">' . $this->getHTML() . '</div>';
    echo '<div class="overlay"></div>';
    echo '<div class="menuContainer">' . $this->getMenuHTML() . '</div>';
    echo '<div class="fullScreenButton hidden"><i class="fas fa-expand"></i></div>';
    echo '</div>';
    echo '<div class="disconnectedOverlay"><img src="/res/img/disconnected.png"></div>';
    echo '</body></html>';
  }

  private function getCSS() {
    $css = [];
    foreach ($this->css AS $path) {
      $css[] = '<link href="/res/css/' . $path . '?' . filemtime('res/css/' . $path) . '" rel="stylesheet" type="text/css" media="all">';
    }
    return implode("\n", $css);
  }

  private function getHTML() {
    return implode("\n", $this->html);
  }

  private function getJS() {
    $js = [];
    foreach ($this->js AS $path) {
      $js[] = '<script src="/res/js/' . $path . '?' . filemtime('res/js/' . $path) . '"></script>';
    }
    return implode("\n", $js);
  }

  private function getMenuHTML() {
    $html = [];
    // $html[] = '<div class="menuTitle">' . $this->title . '</div>';
    $html[] = '<div class="screensSelector">';
    foreach ($this->screens AS $screen) {
      $class  = strtolower($screen);
      $html[] = $this->create->verticalRoundButton($class, $screen, $class);
      $html[] = $this->create->verticalSeparator();
    }
    array_pop($html);
    $html[] = '</div>';
    return implode("\n", $html);
  }

  private function getScript() {
    return '<script>' . implode("\n", $this->script) . '</script>';
  }

  private function getStyle() {
    return '<style>' . implode("\n", $this->style) . '</style>';
  }

  private function getVariables() {
    $variables = [
      'DASHBOARD_ROOM' => isset($_GET['room']) ? $_GET['room'] : 'NONE',
      'KEEP_RETURNING_TO' => isset($_GET['keepReturningTo']) ? $_GET['keepReturningTo'] : 'NONE',
    ];

    $vars = [];
    foreach ($variables AS $name => $value) {
      $vars[] = 'var ' . $name . ' = \'' . $value . '\';';
    }
    return '<script>' . implode("\n", $vars) . '</script>';
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
        $js = 'screen' . $dev . '/' . $path;
        if (file_exists(WEB_DIR . '/res/js/' . $js)) {
          $this->js[] = $js;
        }
      }
      foreach ($screen->getCSS() AS $path) {
        $css = 'screen' . $dev . '/' . $path;
        if (file_exists(WEB_DIR . '/res/css/' . $css)) {
          $this->css[] = $css;
        }
      }
      $this->style  = array_merge($this->style, $screen->getStyle());
      $this->script = array_merge($this->script, $screen->getScript());
      $this->html   = array_merge($this->html, $screen->getHTML());
    }
  }
}
?>
