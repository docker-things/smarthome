<?php

class UI_Controller_Screen_NowPlaying extends UI_Controller_Screen_Base {
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
      '<div class="record"><img src="res/img/screen/nowplaying/vinyl-record.png"></div>',
      '<div class="unknownAlbum"><img src="res/img/screen/nowplaying/unknown-album.jpg"></div>',
      '<div class="image pos1"><img src="res/img/screen/nowplaying/unknown-album.jpg"></div>',
      '<div class="image pos2"><img src="res/img/screen/nowplaying/unknown-album.jpg"></div>',
      '</div>',

      '<div class="title">&nbsp;',
      '<div class="pos1"><span></span></div>',
      '<div class="pos2"><span></span></div>',
      '</div>',

      '<div class="artist">&nbsp;',
      '<div class="pos1"><span></span></div>',
      '<div class="pos2"><span></span></div>',
      '</div>',

      '<div class="screenDimmer"></div>',
    ];
  }
}
?>
