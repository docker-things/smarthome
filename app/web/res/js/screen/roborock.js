$(document).ready(() => {

  const STATUS_CLEANING = [
    'Cleaning',
    'Segment_cleaning',
    'Spot_cleaning',
    'Zoned_cleaning',
  ];

  const STATUS_GOTO = [
    'Going_to_target',
  ];

  const STATUS_IDLE = [
    'Charger_disconnected',
    'Idle',
  ];

  const STATUS_PAUSED = [
    'Paused',
  ];

  const STATUS_CHARGING = [
    'Charging',
  ];

  const STATUS_KNOWN = [
    ...STATUS_CLEANING,
    ...STATUS_GOTO,
    ...STATUS_IDLE,
    ...STATUS_CHARGING,
  ];

  const SHOW_ROOM_SELECTION = [
    ...STATUS_CLEANING,
    ...STATUS_PAUSED,
  ];

  const SHOW_SCREEN = [
    ...STATUS_CLEANING,
    ...STATUS_GOTO,
  ];

  const SCREEN = $('.screen.roborock')

  let CURRENT_STATUS = '-';

  // STATUS CHECK

  function isCleaning(status) {
    return STATUS_CLEANING.indexOf(status) != -1;
  }

  function isIdle(status) {
    return STATUS_IDLE.indexOf(status) != -1;
  }

  function isPaused(status) {
    return STATUS_PAUSED.indexOf(status) != -1;
  }

  function isGoTo(status) {
    return STATUS_GOTO.indexOf(status) != -1;
  }

  function isCharging(status) {
    return STATUS_GOTO.indexOf(status) != -1;
  }

  function isKnownStatus(status) {
    return STATUS_KNOWN.indexOf(status) != -1;
  }

  function shouldShowRoomSelection(status) {
    return SHOW_ROOM_SELECTION.indexOf(status) != -1;
  }

  function shouldShowScreen(status) {
    return SHOW_SCREEN.indexOf(status) != -1;
  }

  function isZoneSet(zone) {
    return zone && zone != 'none' && zone != '';
  }

  // TRIGGERS

  function setStatus(status) {
    if (STATUS_CLEANING.indexOf(status) == -1) {
      setState('SystemWarn', 'message', 'Roborock dashboard got unknown status: ' + status);
      return;
    }

    // Get screen element
    const screen = $('.screen.roborock')

    // Remove current status
    for (var i = STATUS_KNOWN.length - 1; i >= 0; i--) {
      screen.removeClass(STATUS_KNOWN[i])
    }

    // Add new status
    screen.addClass(status);

    // Toggle room selections if required
    manageRoomSelections();

    // Switch to this screen when not charging
    if (shouldShowScreen(status)) {
      showScreenSlideForStaticDashboard('roborock');
    }
  }

  function manageRoomSelections() {
    const status = getStateValue('Roborock', 'status');
    const zone = getStateValue('Roborock', 'zone');

    // Select zone rooms
    if (shouldShowRoomSelection(status) && isZoneSet(zone)) {
      const room = $('.screen.roborock .map .room.' + ucfirst(zone));
      if (room.length > 1) {
        setState('SystemWarn', 'message', 'Roborock dashboard got multiple rooms for zone: ' + zone);
      } else if (room.length == 0) {
        setState('SystemWarn', 'message', 'Roborock dashboard got no room for zone: ' + zone);
      } else if (!room.hasClass('selected')) {
        room.click()
      }
    }
    // Hide room selections otherwise if the status requries it
    else {
      $('.screen.roborock .map .room.selected').click()
    }
  }

  function activateTriggers() {

    // STATUS
    setTrigger('Roborock', 'status', (props) => {
      const status = props.value;
      $('.screen.roborock > .titleContainer .status').text(status.replace(/_/g, ' '));
      setStatus(status);
      manageRoomSelections();
    });

    // ROOM SELECTION
    setTrigger('Roborock', 'zone', (props) => {
      manageRoomSelections()
    });

    // AREA
    setTrigger('Roborock', 'cleaned_area', (props) => {
      $('.screen.roborock .area .value').text(Math.round(parseFloat(props.value)));
    });

    // BATTERY
    setTrigger('Roborock', 'battery', (props) => {
      $('.screen.roborock .battery .value').text(parseInt(props.value));
    });

    // TIME
    setTrigger('Roborock', 'cleaning_since', (props) => {
      const raw = props.value.split(':');
      const hours = parseInt(raw[0]);
      const minutes = parseInt(raw[1]);
      const seconds = parseInt(raw[2]);
      const value = Math.round(minutes + (seconds / 60));
      $('.screen.roborock .time .value').text(value);
    });
  }

  function activateRoomClick() {
    $('.screen.roborock .map .room').click(() => {
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

  function activateButtons() {
    $('.screen.roborock .cleanHouseButton').click(() => {
      runFunction('Roborock.start()');
    })
    $('.screen.roborock .cleanRoomButton').click(() => {
      const roomName = $('.screen.roborock .room.selected').attr('name');
      runFunction('Roborock.clean' + roomName + '()');
    })
    $('.screen.roborock .dockButton').click(() => {
      runFunction('Roborock.home()');
    })
    $('.screen.roborock .pauseButton').click(() => {
      runFunction('Roborock.pause()');
    })
    $('.screen.roborock .resumeButton').click(() => {
      runFunction('Roborock.resume()');
    })
  }

  // Actually do something
  activateTriggers();
  activateRoomClick();
  activateButtons();
})
