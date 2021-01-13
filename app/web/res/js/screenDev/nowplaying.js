$(document).ready(function() {

  function setBackground() {
    setScreenBackground('nowplaying', imagePath)
  }

  // Background
  setTrigger('Turntable', 'img_background', (props) => {
    setScreenBackground('nowplaying', props.value)
  })

  // Cover HQ
  setTrigger('Turntable', 'img_cover_hq', (props) => {
    setCover(props.value)
  })

  // Artist
  setTrigger('Turntable', 'artist', (props) => {
    $('.screen.nowplaying .artist').text(props.value)
  })

  // Title
  setTrigger('Turntable', 'title', (props) => {
    $('.screen.nowplaying .title').text(props.value)
  })

  // Playing turntable
  setTrigger('Turntable', 'status', (props) => {
    if (props.value == 'playing') {
      $('.screen.nowplaying').addClass('playing').addClass('turntable')
    } else {
      $('.screen.nowplaying').removeClass('playing').removeClass('turntable')
    }
  })

  function setCover(image, callback) {
    if ($('.screen.nowplaying .cover .image img').attr('src') == image) {
      return;
    }
    if ($('.screen.nowplaying').hasClass('turntable')) {
      $('.screen.nowplaying').removeClass('turntable')
    }
    setTimeout(function() {
      $('.screen.nowplaying .cover').addClass("flip-hide")
      setTimeout(function() {
        $('.screen.nowplaying .cover .image img').attr('src', image)
        $('.screen.nowplaying .cover .image').css('opacity', 1)
        $('.screen.nowplaying .cover').removeClass("flip-hide").addClass("flip-show")
        setTimeout(function() {
          $('.screen.nowplaying .cover').removeClass("flip-show")
          let status = getStateValue('Turntable', 'status')
          if (status == 'playing') {
            $('.screen.nowplaying').addClass('playing').addClass('turntable')
          }
          if (callback) {
            callback()
          }
        }, 1000)
      }, 1000)
    }, 500)
  }

  // setTimeout(function() {
  //   $('.screen.nowplaying').addClass("playing turntable")
  //   setTimeout(function() {
  //     setCover()
  //   }, 1000)
  // }, 1000)
  // setTimeout(function() {
  //   $('.screen.nowplaying').addClass("playing turntable")
  // }, 1000)
  // setTimeout(function() {
  //   $('.screen.nowplaying').removeClass("playing")
  // }, 3000)
  // setTimeout(function() {
  //   $('.screen.nowplaying').addClass("playing")
  // }, 5000)
  // setTimeout(function() {
  //   $('.screen.nowplaying').removeClass("playing")
  // }, 7000)
})
