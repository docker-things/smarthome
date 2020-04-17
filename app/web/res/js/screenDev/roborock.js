function setRoborockMode(mode) {
  $('.screen.roborock')
    .removeClass('Cleaning')
    .removeClass('Zoned_cleaning')
    .removeClass('Paused')
    .removeClass('Charging')
    .removeClass('Charger_disconnected');
  $('.screen.roborock')
    .addClass(mode);

  // Deselect room when going back to the dock
  if (mode == 'Charging') {
    $('.screen.roborock .map .room.selected').click();
  }
}

function setZoneCleaningRoom() {
  const status = getStateValue('Roborock', 'status');
  const zone = getStateValue('Roborock', 'zone');
  if (zone && zone != 'none' && zone != '' && status != 'Charging') {
    const roomName = zone.charAt(0).toUpperCase() + zone.slice(1);
    const room = $('.screen.roborock .map .room.' + roomName);
    if (!$(room).hasClass('selected')) {
      $(room).click()
    }
  } else {
    $('.screen.roborock .map .room.selected').click()
  }
}

function activateRoborockTriggers() {
  // STATUS
  setTrigger('Roborock', 'status', function(props) {
    const status = props.value;
    $('.screen.roborock > .titleContainer .status').text(status.replace('_', ' '));
    setRoborockMode(status);
    setZoneCleaningRoom()
  });

  // ROOM SELECTION
  setTrigger('Roborock', 'zone', function(props) {
    setZoneCleaningRoom()
  });

  // AREA
  // setTrigger('Roborock', 'last_area_cleaned', function(props) {
  setTrigger('Roborock', 'cleaned_area', function(props) {
    $('.screen.roborock .area .value').text(Math.round(parseFloat(props.value)));
  });

  // BATTERY
  setTrigger('Roborock', 'battery', function(props) {
    $('.screen.roborock .battery .value').text(parseInt(props.value));
  });

  // TIME
  // setTrigger('Roborock', 'last_duration', function(props) {
  setTrigger('Roborock', 'cleaning_since', function(props) {
    const raw = props.value.split(':');
    const hours = parseInt(raw[0]);
    const minutes = parseInt(raw[1]);
    const seconds = parseInt(raw[2]);
    const value = Math.round(minutes + (seconds / 60));
    $('.screen.roborock .time .value').text(value);
  });
}

function activateRoborockRoomClick() {
  $('.screen.roborock .map .room').click(function() {
    if ($(this).hasClass('selected')) {
      $(this).removeClass('selected').removeClass('darker');
      $('.screen.roborock').removeClass('roomSelected');
    } else {
      $('.screen.roborock .map .room.selected').removeClass('selected').removeClass('darker');
      $(this).addClass('selected').addClass('darker');
      $('.screen.roborock').addClass('roomSelected');
    }
  })
}

function activateRoborockButtons() {
  $('.screen.roborock .cleanHouseButton').click(function() {
    runFunction('Roborock.start()');
  })
  $('.screen.roborock .cleanRoomButton').click(function() {
    const roomName = $('.screen.roborock .room.selected').attr('name');
    runFunction('Roborock.clean' + roomName + '()');
  })
  $('.screen.roborock .dockButton').click(function() {
    runFunction('Roborock.home()');
  })
  $('.screen.roborock .pauseButton').click(function() {
    runFunction('Roborock.pause()');
  })
  $('.screen.roborock .resumeButton').click(function() {
    runFunction('Roborock.resume()');
  })
}

$(document).ready(function() {
  activateRoborockTriggers();
  activateRoborockRoomClick();
  activateRoborockButtons();
})
