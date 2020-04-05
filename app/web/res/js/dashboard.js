MQTT_CLIENT = undefined;

HOUSE_STATE = {}
TRIGGERS = {}

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
      console.log("Lost connection while getting full state: " + responseObject.errorMessage);
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
      console.log("MQTT connection failed while getting full state!");
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
      console.log("State listener lost MQTT connection: " + responseObject.errorMessage);
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
      // Subscribe to the state change topic
      MQTT_CLIENT.subscribe("core-state/change");
    },

    onFailure: function() {
      console.log("State listener MQTT connection failed!");
    },
  });
}

function setBrightnessTriggers() {
  $('.map .room').each(function() {
    let roomObject = $(this);
    let name = $(roomObject).find('.name').text();
    setTrigger(name + '-Light', 'status', function(props) {
      if (props.value == 'on') {
        $(roomObject).addClass('bright');
      } else {
        $(roomObject).removeClass('bright');
      }
    })
  });
}

function clickedRoom(roomName, roomObject) {
  console.log(getStateValue(roomName + '-Light', 'status'))
  if (getStateValue(roomName + '-Light', 'status') == 'on') {
    runFunction(roomName + '.lightOff()');
  } else {
    runFunction(roomName + '.lightOn()');
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

/**
 * RUN STUFF!!!
 */
$(document).ready(function() {

  // Do local setup
  setBrightnessTriggers();
  setRoomClickListeners();

  // Get full state initially - one time
  getFullState();

  // Start state listener
  startStateListener();
})
