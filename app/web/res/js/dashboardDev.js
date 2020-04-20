var MQTT_CLIENT = undefined;

var HOUSE_STATE = {}
var TRIGGERS = {}

var SERVER_CONNECTED_ONCE = false;

var SCREEN_TRANSITION_ENABLED = {
  transition: 'all 250ms ease-out'
};

var SCREEN_TRANSITION_DISABLED = {
  transition: 'none'
};

var PREVENT_TOUCH_DRAG = false;

function preventTouchDrag() {
  PREVENT_TOUCH_DRAG = true;
}

function restoreTouchDrag() {
  setTimeout(function() {
    PREVENT_TOUCH_DRAG = false;
  })
}

function showDashboard() {
  setTimeout(function() {
    $('.mainContainer').css({
      opacity: 1,
    })
  }, 500)
}

/**
 * SCREENS
 */

var SCREENS = [];
var ACTIVE_SCREEN = undefined;

function createScreenList() {
  $('.mainContainer > .overviewContainer > .screen').each(function() {
    SCREENS.push($(this).attr('name'));
  })
}

function showScreen(screen, touch) {
  if (screen == ACTIVE_SCREEN) {
    console.warn('showScreen(): Trying to show the same screen:', screen);
    return;
  }

  hideMenu(touch);

  ACTIVE_SCREEN = screen;
  const prevScreen = getPrevScreen();
  const nextScreen = getNextScreen();

  $('.mainContainer > .overviewContainer > .screen')
    .removeClass('prev')
    .removeClass('next')
    .removeClass('active');
  $('.mainContainer > .overviewContainer > .screen.' + screen)
    .removeClass('prev')
    .removeClass('next')
    .addClass('active');
  $('.mainContainer > .overviewContainer > .screen.' + prevScreen).addClass('prev');
  $('.mainContainer > .overviewContainer > .screen.' + nextScreen).addClass('next');

  resetScreenDrag(touch);
}

function getPrevScreen() {
  const index = SCREENS.indexOf(ACTIVE_SCREEN) - 1;
  if (index == -1) {
    return SCREENS[SCREENS.length - 1];
  }
  return SCREENS[index];
}

function getActiveScreen() {
  return ACTIVE_SCREEN;
}

function getNextScreen() {
  const index = SCREENS.indexOf(ACTIVE_SCREEN) + 1;
  if (index == SCREENS.length) {
    return SCREENS[0];
  }
  return SCREENS[index];
}

function getPrevScreenObject() {
  return $('.mainContainer > .overviewContainer > .screen.' + getPrevScreen());
}

function getActiveScreenObject() {
  return $('.mainContainer > .overviewContainer > .screen.' + getActiveScreen());
}

function getNextScreenObject() {
  return $('.mainContainer > .overviewContainer > .screen.' + getNextScreen());
}

function showPrevScreen() {
  showScreen(getPrevScreen());
}

function showNextScreen() {
  showScreen(getNextScreen());
}

function showNextScreenSlide() {
  touchDragScreens({
    active: false,
    prevScreen: getPrevScreenObject(),
    activeScreen: getActiveScreenObject(),
    nextScreen: getNextScreenObject(),
    event: 'end',
    direction: 'left',
    delta: {
      x: 1,
      y: 0,
    }
  })
}

function showPrevScreenSlide() {
  touchDragScreens({
    active: false,
    prevScreen: getPrevScreenObject(),
    activeScreen: getActiveScreenObject(),
    nextScreen: getNextScreenObject(),
    event: 'end',
    direction: 'right',
    delta: {
      x: -1,
      y: 0,
    }
  })
}

function oppositeDirection(direction) {
  switch (direction) {
    case 'right':
      return 'left';
    case 'left':
      return 'right';
    case 'up':
      return 'down';
    case 'down':
      return 'up';
  }
  return 'none';
}

function resetScreenDrag(touch) {
  if (touch) {
    getActiveScreenObject().css(SCREEN_TRANSITION_ENABLED)
    if (touch.direction == 'right') {
      getNextScreenObject().css(SCREEN_TRANSITION_ENABLED)
    } else if (touch.direction == 'left') {
      getPrevScreenObject().css(SCREEN_TRANSITION_ENABLED)
    } else {
      getNextScreenObject().css(SCREEN_TRANSITION_ENABLED)
      getPrevScreenObject().css(SCREEN_TRANSITION_ENABLED)
    }
    // $('.mainContainer > .overviewContainer > .screen').css({
    //   transform: 'scale(1)',
    // })
  }
  getActiveScreenObject().css({
    left: 0,
  })
  getPrevScreenObject().css({
    left: -$(window).width(),
  })
  getNextScreenObject().css({
    left: $(window).width(),
  })
}

function resetMenuDrag(touch) {
  if (!touch) {
    touch = {
      menu: $('.mainContainer .menuContainer')
    }
  }
  touch.menu.css(SCREEN_TRANSITION_ENABLED)
  touch.menu.css({
    top: -touch.menu.height(),
  })
}

/**
 * TOGGLE THEME
 */

function setDarkMode() {
  $('.mainContainer').addClass('darkMode')
}

function setLightMode() {
  $('.mainContainer').removeClass('darkMode');
}

function toggleDarkMode() {
  if ($('.mainContainer').hasClass('darkMode')) {
    setLightMode()
  } else {
    setDarkMode()
  }
}

function touchDragScreens(touch) {
  if (touch.event == 'start') {
    getPrevScreenObject().css(SCREEN_TRANSITION_DISABLED)
    getActiveScreenObject().css(SCREEN_TRANSITION_DISABLED)
    getNextScreenObject().css(SCREEN_TRANSITION_DISABLED)
  }
  touch.prevScreen.css({
    left: touch.delta.x - $(window).width(),
  });
  touch.activeScreen.css({
    left: touch.delta.x,
  });
  touch.nextScreen.css({
    left: $(window).width() + touch.delta.x,
  });

  // let scale = 1 - Math.abs(touch.delta.x) / $(window).height()
  // if (scale > 1) {
  //   scale = 1;
  // }
  // if (scale < 0.95) {
  //   scale = 0.95;
  // }
  // $('.mainContainer > .overviewContainer > .screen').css({
  //   transform: 'scale(' + scale + ')',
  // })

  if (touch.event == 'end' && touch.delta.x != 0) {
    console.log(touch.direction)
    // End transition to the prev screen
    if (touch.direction == 'right') {
      getActiveScreenObject().css(SCREEN_TRANSITION_ENABLED)
      getPrevScreenObject().css(SCREEN_TRANSITION_ENABLED)
      showScreen(getPrevScreen(), touch);
    }
    // End transition to the next screen
    else if (touch.direction == 'left') {
      getActiveScreenObject().css(SCREEN_TRANSITION_ENABLED)
      getNextScreenObject().css(SCREEN_TRANSITION_ENABLED)
      showScreen(getNextScreen(), touch);
    }
    // End abort transition
    else {
      resetScreenDrag(touch)
    }
  }
}

function showMenu() {
  const menu = $('.mainContainer .menuContainer')
  menu.css(SCREEN_TRANSITION_ENABLED)
  menu.css({
    top: 0,
  })
  $('.mainContainer > .overlay').addClass('visible')
}

function hideMenu() {
  const menu = $('.mainContainer .menuContainer')
  menu.css(SCREEN_TRANSITION_ENABLED)
  menu.css({
    top: -menu.height()
  })
  menu.removeClass('visible')
  $('.mainContainer > .overlay').removeClass('visible')
}

function touchDragMenu(touch) {
  if (touch.event == 'start') {
    touch.menu.css(SCREEN_TRANSITION_DISABLED)
  }
  // console.log(touch.delta.y)
  if (touch.delta.y > touch.menu.height()) {
    touch.delta.y = touch.menu.height();
  }
  touch.menu.css({
    top: touch.delta.y - touch.menu.height()
  })
  if (touch.direction == 'down') {
    touch.menu.addClass('visible')
  }

  if (touch.event == 'end' && touch.delta.y != 0) {
    console.log(touch.direction)
    // End transition to the prev screen
    if (touch.direction == 'up') {
      hideMenu(touch)
    }
    // End transition to the next screen
    else if (touch.direction == 'down') {
      showMenu(touch)
    }
    // End abort transition
    else {
      resetMenuDrag(touch)
    }
  }
}

function touchDragTo(touch) {
  if (touch.mode == 'vertical') {
    touchDragMenu(touch);
  } else if (touch.mode == 'horizontal') {
    touchDragScreens(touch);
  }
}

/**
 * Screen touch evetns
 */

function bindScreenTouchEvents() {
  var TOUCH = {};
  $('.mainContainer > .overviewContainer > .screen')
    .bind('touchstart', function(e) {
      if (PREVENT_TOUCH_DRAG) return;

      TOUCH = {
        event: 'start',
        active: true,
        step: 0,
        menu: $('.mainContainer .menuContainer'),
        prevScreen: getPrevScreenObject(),
        activeScreen: getActiveScreenObject(),
        nextScreen: getNextScreenObject(),
        start: {
          x: e.originalEvent.changedTouches[0].clientX,
          y: e.originalEvent.changedTouches[0].clientY,
        },
        delta: {
          x: 0,
          y: 0,
        },
        direction: 'none',
        mode: 'none',
      };
      touchDragTo(TOUCH);
      // e.preventDefault();
    })
    .bind('touchmove', function(e) {
      if (PREVENT_TOUCH_DRAG) return;

      TOUCH.event = 'move';
      const deltaX = e.originalEvent.changedTouches[0].clientX - TOUCH.start.x;
      const deltaY = e.originalEvent.changedTouches[0].clientY - TOUCH.start.y;
      if (Math.abs(deltaX) >= Math.abs(deltaY)) {
        TOUCH.direction = deltaX >= 0 ? 'right' : 'left'
        TOUCH.mode = 'horizontal';
      } else {
        TOUCH.direction = deltaY >= 0 ? 'down' : 'up';
        TOUCH.mode = 'vertical';
      }
      TOUCH.delta.x = deltaX;
      TOUCH.delta.y = deltaY;
      TOUCH.step++;
      if (Math.abs(TOUCH.delta.x) > 15 || Math.abs(TOUCH.delta.y) > 15) {
        touchDragTo(TOUCH);
      }
      // e.preventDefault();
    })
    .bind('touchend', function(e) {
      if (PREVENT_TOUCH_DRAG) return;

      TOUCH.event = 'end';
      TOUCH.active = false;
      TOUCH.delta.x = e.originalEvent.changedTouches[0].clientX - TOUCH.start.x;
      TOUCH.delta.y = e.originalEvent.changedTouches[0].clientY - TOUCH.start.y;
      if (Math.abs(TOUCH.delta.x) > 15 || Math.abs(TOUCH.delta.y) > 15) {
        touchDragTo(TOUCH);
      }
      // e.preventDefault();
    })
    .bind('touchcancel', function(e) {
      if (PREVENT_TOUCH_DRAG) return;

      TOUCH.event = 'end';
      TOUCH.active = false;
      TOUCH.delta.x = e.originalEvent.changedTouches[0].clientX - TOUCH.start.x;
      TOUCH.delta.y = e.originalEvent.changedTouches[0].clientY - TOUCH.start.y;
      if (Math.abs(TOUCH.delta.x) > 15 || Math.abs(TOUCH.delta.y) > 15) {
        touchDragTo(TOUCH);
      }
      // e.preventDefault();
    });
}

function bindOverlayClick() {
  $('.mainContainer > .overlay').click(function() {
    hideMenu()
  })
}

function bindMenuButtons() {
  $('.mainContainer > .menuContainer .screensSelector > div').click(function() {
    const screen = $(this).attr('name');
    showScreen(screen);
  })
}

function bindPrevNextScreenButtons() {
  $('.mainContainer > .overviewContainer > .screen > .titleContainer .prevButton')
    .click(function() {
      showPrevScreenSlide();
    });
  $('.mainContainer > .overviewContainer > .screen > .titleContainer .nextButton')
    .click(function() {
      showNextScreenSlide();
    });
}

/**
 * Show toast notifications
 */
function showToast(message, type) {
  $.toast({
    text: message,
    icon: type,
    position: 'top-center',
    showHideTransition: 'slide',
    loader: false
  })
}

function showInfo(message) {
  showToast(message, 'info');
}

function showWarn(message) {
  showToast(message, 'warning');
}

function showError(message) {
  showToast(message, 'error');
}

/**
 * Set state
 */
function setState(source, name, value) {
  message = new Paho.MQTT.Message(JSON.stringify({
    'source': source,
    'name': name,
    'value': value,
  }));
  message.destinationName = "core-state/set";
  MQTT_CLIENT.send(message);
}

/**
 * Run functions
 */
function runFunction(func) {
  message = new Paho.MQTT.Message(func);
  message.destinationName = "core-function/run";
  MQTT_CLIENT.send(message);
}

/**
 * Triggers
 */
function launchTriggers(source, name, value, prevValue, timestamp) {
  if (!(source in TRIGGERS)) return;
  if (!(name in TRIGGERS[source])) return;
  for (let i = 0; i < TRIGGERS[source][name].length; i++) {
    TRIGGERS[source][name][i]({
      'value': value,
      'prevValue': prevValue,
      'timestamp': timestamp,
    })
  }
}

function setTrigger(source, name, trigger) {
  if (!(source in TRIGGERS)) {
    TRIGGERS[source] = {};
  }
  if (!(name in TRIGGERS[source])) {
    TRIGGERS[source][name] = [];
  }
  TRIGGERS[source][name].push(trigger);
}

/**
 * Get/Set local state
 */
function setLocalState(source, name, value, prevValue, timestamp) {
  if (!(source in HOUSE_STATE)) {
    HOUSE_STATE[source] = {};
  }
  if (!(name in HOUSE_STATE[source])) {
    HOUSE_STATE[source][name] = {};
  }
  if (!('timestamp' in HOUSE_STATE[source][name])) {
    HOUSE_STATE[source][name]['timestamp'] = 0;
  }
  if (HOUSE_STATE[source][name]['timestamp'] <= timestamp) {
    HOUSE_STATE[source][name]['value'] = value;
    HOUSE_STATE[source][name]['prevValue'] = prevValue;
    HOUSE_STATE[source][name]['timestamp'] = timestamp;

    launchTriggers(source, name, value, prevValue, timestamp);
  }
}

function getState(source, name) {
  if (!(source in HOUSE_STATE)) return undefined;
  if (!(name in HOUSE_STATE[source])) return undefined;
  return HOUSE_STATE[source][name];
}

function getStateValue(source, name) {
  if (!(source in HOUSE_STATE)) return undefined;
  if (!(name in HOUSE_STATE[source])) return undefined;
  return HOUSE_STATE[source][name]['value'];
}

/**
 * Launch state listener
 */
function getFullState() {

  let client = new Paho.MQTT.Client('192.168.0.100', 1884, "dashboard" + new Date().getTime());

  client.onConnectionLost = function(responseObject) {
    if (responseObject.errorCode !== 0) {
      showError('Lost MQTT connection: ' + responseObject.errorMessage);
    }
  };

  client.onMessageArrived = function(message) {
    state = jQuery.parseJSON(message.payloadString)
    for (source in state) {
      for (name in state[source]) {
        let props = state[source][name];
        setLocalState(source, name, props['value'], props['prevValue'], props['timestamp']);
      }
    }
    client.disconnect();
    showDashboard();
  };

  client.connect({
    useSSL: false,
    reconnect: true,

    onSuccess: function() {
      // Subscribe to the full state reporter
      client.subscribe("core-state/full-state-provider");

      // Request full state
      message = new Paho.MQTT.Message("get");
      message.destinationName = "core-state/full-state-request";
      client.send(message);
    },

    onFailure: function() {
      showError('Failed to connect to MQTT');
    },
  });
}

/**
 * Launch state listener
 */
function startStateListener() {

  MQTT_CLIENT = new Paho.MQTT.Client('192.168.0.100', 1884, "dashboard" + new Date().getTime());

  MQTT_CLIENT.onConnectionLost = function(responseObject) {
    if (responseObject.errorCode !== 0) {
      showError('Lost MQTT connection: ' + responseObject.errorMessage);
    }
  };

  MQTT_CLIENT.onMessageArrived = function(message) {
    state = jQuery.parseJSON(message.payloadString)
    setLocalState(state.source, state.name, state.value, state.prevValue, state.timestamp);
  };

  MQTT_CLIENT.connect({
    useSSL: false,
    reconnect: true,

    onSuccess: function() {
      // If server disconnected refresh just in case code changed
      if (SERVER_CONNECTED_ONCE) {
        showInfo('Reconnected to MQTT');
        window.location.reload(true);
      } else {
        // showInfo('Connected to MQTT');
        SERVER_CONNECTED_ONCE = true;
      }
      // Subscribe to the state change topic
      MQTT_CLIENT.subscribe("core-state/change");
    },

    onFailure: function() {
      showError('Failed to connect to MQTT');
    },
  });
}

function setNotificationTriggers() {
  let firstNotification = true;
  setTrigger('SystemNotify', 'message', function(props) {
    if (firstNotification) {
      firstNotification = false;
      return;
    }
    showInfo(props.value);
  });

  let firstWarn = true;
  setTrigger('SystemWarn', 'message', function(props) {
    if (firstWarn) {
      firstWarn = false;
      return;
    }
    showWarn(props.value);
  });
}

function goFullScreenOnAnyClick() {
  var elem = $('body').get(0)
  elem.onclick = function() {
    req = elem.requestFullScreen || elem.webkitRequestFullScreen || elem.mozRequestFullScreen;
    req.call(elem);
  }
}

function windowResizeHandler() {
  $(window).resize(function() {
    resetScreenDrag()
  })
}

/**
 * RUN STUFF!!!
 */
$(document).ready(function() {

  // Do local setup
  // setNotificationTriggers();

  // Set click listeners
  // goFullScreenOnAnyClick();

  // Set touch listeners
  bindMenuButtons();
  bindScreenTouchEvents();
  bindPrevNextScreenButtons();
  bindOverlayClick();

  // Show the screen
  createScreenList();
  showScreen(SCREENS[0]);

  // Window related handlers
  windowResizeHandler();

  // Get full state initially - one time
  getFullState();

  // Start state listener
  startStateListener();

})
