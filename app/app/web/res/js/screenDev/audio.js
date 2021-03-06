var prevAudioStreamStatus = {}

function setAudioStreamStatus(stream, status) {
  if (stream in prevAudioStreamStatus) {
    prevAudioStreamStatus[stream] = 'unknown'
  }
  if ((status != prevAudioStreamStatus[stream] && status == 'playing') && KEEP_RETURNING_TO != 'nowplaying') {
    showScreenSlideForStaticDashboard('audio');
  }
  prevAudioStreamStatus[stream] = status

  audioUpdateStatus()
}

function audioUpdateStatus() {
  let status;
  for (i in prevAudioStreamStatus) {
    if (prevAudioStreamStatus[i] == 'playing') {
      status = 'Playing'
    } else {
      status = 'Idle'
    }
  }
  $('.screen.audio > .titleContainer .status').text(status);
}

$(document).ready(function() {

  // TURNTABLE STATUS
  setTrigger('Turntable', 'status', (props) => {
    if ((props.value == 'on' || props.value == 'playing') && KEEP_RETURNING_TO != 'nowplaying') {
      showScreenSlideForStaticDashboard('audio');
    }
  });
})
