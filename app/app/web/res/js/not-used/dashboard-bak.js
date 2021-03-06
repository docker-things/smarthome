var MQTT_CLIENT = undefined;

var MENU_BUTTONS_OPENED = false;
var MENU_BUTTONS_OPENED_HANDLE = undefined;

var HOUSE_STATE = {}
var TRIGGERS = {}

var SERVER_CONNECTED_ONCE = false;

/**
 * Show toast notifications
 */
function showToast(message, type) {
  $.toast({
    text: message,
    icon: type,
    position: 'bottom-center',
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
 * Tab buttons actions
 */

function setTabButtonsActions() {
  $('.tabButtons .primary .button').click(function() {
    // Close buttons menu
    if ($(this).hasClass('active')) {
      clearTimeout(MENU_BUTTONS_OPENED_HANDLE);

      $('.tabButtons .primary .button').removeClass('active').removeClass('inactive');
      $('.tabButtons .secondary .group.active').removeClass('active');
    }

    // Open buttons menu
    else {
      // Self close after 1m
      MENU_BUTTONS_OPENED_HANDLE = setTimeout(function() {
        if ($('.tabButtons .primary .button.active').length != 0) {
          $('.tabButtons .primary .button.active').click();
        }
      }, 60000);

      $('.tabButtons .primary .button').addClass('inactive');
      $(this).removeClass('inactive').addClass('active');
      let group = $(this).attr('group');
      $('.tabButtons .secondary .group.' + group).addClass('active');
    }
  });

  $('.tabButtons .secondary .button').click(function() {
    $('.tabButtons .primary .button.active').click();
  })
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
        showInfo('Connected to MQTT');
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

function setTemperatureTriggers() {
  $('.map .room').each(function() {
    let roomObject = $(this);
    let name = $(roomObject).find('.name').text();
    setTrigger(name + '-Temperature', 'temperature', function(props) {
      $(roomObject).find('.temperature .value').html(props['value']);
      $(roomObject).find('.temperature').addClass('visible');
    })
  });
}

function setHumidityTriggers() {
  $('.map .room').each(function() {
    let roomObject = $(this);
    let name = $(roomObject).find('.name').text();
    setTrigger(name + '-Temperature', 'humidity', function(props) {
      $(roomObject).find('.humidity .value').html(props['value']);
      $(roomObject).find('.humidity').addClass('visible');
    })
  });
}

function setPressureTriggers() {
  $('.map .room').each(function() {
    let roomObject = $(this);
    let name = $(roomObject).find('.name').text();
    setTrigger(name + '-Temperature', 'pressure', function(props) {
      $(roomObject).find('.pressure .value').html(props['value']);
      $(roomObject).find('.pressure').addClass('visible');
    })
  });
}

function setBrightnessTriggers() {
  $('.map .room').each(function() {
    let roomObject = $(this);
    let name = $(roomObject).find('.name').text();
    setTrigger(name + '-Light', 'status', function(props) {
      if (props.value == 'on') {
        $(roomObject).find('.dimLayer').addClass('on');
      } else {
        $(roomObject).find('.dimLayer').removeClass('on');
      }
    })
  });
}

function setDoorTriggers() {
  $('.map .room').each(function() {
    let roomObject = $(this);
    $(roomObject).find('.door').each(function() {
      let doorObject = $(this);
      let objectName = $(doorObject).attr('objectName');
      setTrigger(objectName, 'contact', function(props) {
        if (props.value == 'true') {
          $(doorObject).removeClass('opened');
        } else {
          $(doorObject).addClass('opened');
        }
      })
    });
  });
}

function setWindowTriggers() {
  $('.map .room').each(function() {
    let roomObject = $(this);
    $(roomObject).find('.window').each(function() {
      let windowObject = $(this);
      let objectName = $(windowObject).attr('objectName');
      setTrigger(objectName, 'contact', function(props) {
        if (props.value == 'true') {
          $(windowObject).removeClass('opened');
        } else {
          $(windowObject).addClass('opened');
        }
      })
    });
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

function setHeatingTriggers() {
  // New state from mqtt
  setTrigger('Heating', 'status', function(props) {
    $('.tabButtons .primary [group=heating] .status').html(props.value);
    if (props.value == 'on') {
      $('.tabButtons .primary [group=heating]').addClass('green');
      $('.tabButtons .secondary .group.heating').addClass('green');
    } else {
      $('.tabButtons .primary [group=heating]').removeClass('green');
      $('.tabButtons .secondary .group.heating').removeClass('green');
    }
  });

  // New temperature from mqtt
  setTrigger('Heating', 'presenceMinTemp', function(props) {
    $('.tabButtons .secondary .group.heating input.temperature').val(props.value);
  });

  // On change set new value
  $('.tabButtons .secondary .group.heating input.temperature').change(function() {
    let newValue = parseFloat($(this).val());
    if (newValue < 1) {
      showError('Invalid value!');
      $(this).val(1);
      return;
    }
    setState('Heating', 'presenceMinTemp', $(this).val());
    setState('Heating', 'presenceMaxTemp', parseFloat($(this).val()) + 0.25);
  });
}

function setRoborockTriggers() {
  setTrigger('Roborock', 'status', function(props) {
    $('.tabButtons .primary [group=roborock] .status').html(props.value);
    if (props.value == 'Charging') {
      $('.tabButtons .primary [group=roborock]').removeClass('green');
      $('.tabButtons .secondary .group.roborock').removeClass('green');
    } else {
      $('.tabButtons .primary [group=roborock]').addClass('green');
      $('.tabButtons .secondary .group.roborock').addClass('green');
    }
  });
}

function heatingOn(forced) {
  setState('Heating', 'forceOff', 'false');
  runFunction('Heating.on()');
  setState('Heating', 'forceOn', forced ? 'true' : 'false');
}

function heatingOff(forced) {
  setState('Heating', 'forceOn', 'false');
  runFunction('Heating.off()');
  setState('Heating', 'forceOff', forced ? 'true' : 'false');
}

function heatingUnforceState(forced) {
  setState('Heating', 'forceOn', 'false');
  setState('Heating', 'forceOff', 'false');
}

function clickedRoom(roomName, roomObject) {
  // // Local cleaning
  // const status = getStateValue('Roborock', 'status');
  // if (status == 'Charging') {
  //   runFunction(roomName + '.cleanRoom()');
  // } else
  // if (status == 'Cleaning') {
  //   runFunction(roomName + '.pauseCleaning()');
  // } else if (status == 'Paused') {
  //   runFunction(roomName + '.resumeCleaningRoom()');
  // }

  // Toggle light
  if (getStateValue(roomName + '-Light', 'status') == 'on') {
    setState(roomName, 'forceLightOn', 'false');
    runFunction(roomName + '.lightOff()');
    setState(roomName, 'forceLightOff', 'true');
  } else {
    setState(roomName, 'forceLightOff', 'false');
    runFunction(roomName + '.lightOn()');
    setState(roomName, 'forceLightOn', 'true');
  }
}

function setRoomClickListeners() {
  $('.map .room').each(function() {
    let name = $(this).find('.name').text();
    $(this).click(function() {
      clickedRoom(name, this);
    })
  });
}

function goFullScreenOnAnyClick() {
  var elem = $('body').get(0)
  elem.onclick = function() {
    req = elem.requestFullScreen || elem.webkitRequestFullScreen || elem.mozRequestFullScreen;
    req.call(elem);
  }
}

/**
 * RUN STUFF!!!
 */
$(document).ready(function() {

  // Do local setup
  setTemperatureTriggers();
  setHumidityTriggers();
  setPressureTriggers();
  setBrightnessTriggers();
  setDoorTriggers();
  setWindowTriggers();
  setNotificationTriggers();
  setHeatingTriggers();
  setRoborockTriggers();

  // Set click listeners
  setTabButtonsActions();
  setRoomClickListeners();
  goFullScreenOnAnyClick();

  // Get full state initially - one time
  getFullState();

  // Start state listener
  startStateListener();
})
