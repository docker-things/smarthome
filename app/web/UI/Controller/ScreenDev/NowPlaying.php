<?php

class UI_Controller_ScreenDev_NowPlaying extends UI_Controller_ScreenDev_Base {
  /**
   * The class name of the page
   * @var string
   */
  protected $class = 'nowplaying';

  /**
   * The display name of the page
   * @var string
   */
  protected $name = 'NowPlaying';

  protected function setHTML() {
    return [
      '<div class="backgroundDimmer"></div>',
      '<div class="cover">',
      '<div class="shadow"></div>',
      '<div class="unknownAlbum"><img src="res/img/screen/nowplaying/unknown-album.jpg"></div>',
      '<div class="image"><img></div>',
      '<div class="record"><img src="res/img/screen/nowplaying/vinyl-record.png"></div>',
      '</div>',
      '<div class="title"></div>',
      '<div class="artist"></div>',
      '<div class="screenDimmer"></div>',
    ];
  }
}
?>
