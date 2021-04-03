$(document).ready(function() {

  function setStatus(status) {
    if (status == 'playing') {
      $('.screen.nowplaying').addClass('playing').addClass('turntable')
    } else {
      $('.screen.nowplaying').removeClass('playing').removeClass('turntable')
    }
  }

  function setBackground(url) {
    if (url == '' || url == '-') {
      url = '/res/img/screen/nowplaying/unknown-background.png'
    }
    setScreenBackground('nowplaying', url)
  }

  function setCover(url, callback) {
    if (url == '' || url == '-') {
      url = '/res/img/screen/nowplaying/unknown-album.jpg'
    }
    if ($('.screen.nowplaying .cover .image img').attr('src') == url) {
      return;
    }
    preloadImage(url, function() {
      let from = $('.screen.nowplaying .cover .image.pos1').hasClass('show') ? 1 : 2
      let to = from == 1 ? 2 : 1

      $('.screen.nowplaying .cover .image.pos' + to + ' img').attr('src', url)
      $('.screen.nowplaying .cover .image.pos' + from).removeClass('show')
      $('.screen.nowplaying .cover .image.pos' + to).addClass('show')

      // Hacky shit... shouldn't be needed
      setTimeout(function() {
        getFullState()
      }, 500)
    })
  }

  function setTitle(title) {
    if (title == '-') {
      title = ''
    }
    let from = $('.screen.nowplaying .title .pos1').hasClass('show') ? 1 : 2
    let to = from == 1 ? 2 : 1

    $('.screen.nowplaying .title .pos' + to + ' span').text(title)
    $('.screen.nowplaying .title .pos' + from).removeClass('show')
    $('.screen.nowplaying .title .pos' + to).addClass('show')
  }

  function setArtist(artist) {
    if (artist == '-') {
      artist = ''
    }
    let from = $('.screen.nowplaying .artist .pos1').hasClass('show') ? 1 : 2
    let to = from == 1 ? 2 : 1

    $('.screen.nowplaying .artist .pos' + to + ' span').text(artist)
    $('.screen.nowplaying .artist .pos' + from).removeClass('show')
    $('.screen.nowplaying .artist .pos' + to).addClass('show')
  }

  function setTriggers() {
    setTrigger('Turntable', 'img_background', (props) => {
      setBackground(props.value)
    })
    setTrigger('Turntable', 'img_cover_hq', (props) => {
      setCover(props.value)
    })
    setTrigger('Turntable', 'title', (props) => {
      setTitle(props.value)
    })
    setTrigger('Turntable', 'artist', (props) => {
      setArtist(props.value)
    })
    setTrigger('Turntable', 'status', (props) => {
      setStatus(props.value)
    })
  }

  setTriggers()
})
