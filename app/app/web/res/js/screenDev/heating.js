function heatingSliderChangeTooltip(e) {
  return '<div class="value">' + e.value + '</div><div class="unit">°C</div>';
}

$(document).ready(function() {
  function activateSlider() {
    const sliderObject = $(".screen.heating .temperatureSlider");
    sliderObject.roundSlider({
      sliderType: "min-range",
      circleShape: "pie",
      startAngle: "315",
      lineCap: "round",
      radius: 130,
      width: 5,

      min: 15,
      max: 30,
      step: 0.5,
      value: 15,

      handleSize: '+40',
      handleShape: 'dot',
      pathColor: "rgba(197, 58, 13, .25)",
      rangeColor: "#C53A0D",

      svgMode: true,
      borderWidth: 0,
      startValue: 0,

      tooltipFormat: "heatingSliderChangeTooltip",

      valueChange: function(e) {
        // If this is not defined, stop() is sometimes not called.
      },
      stop: function(e) {
        const minTemp = e.value;
        const maxTemp = minTemp + 0.25;
        setState('Heating', 'presenceMinTemp', minTemp); // 22
        setState('Heating', 'presenceMaxTemp', maxTemp); // 22.25
        setState('Heating', 'sleepingMinTemp', minTemp - 2); // 20
        setState('Heating', 'sleepingMaxTemp', maxTemp - 2); // 20.25
        setState('Heating', 'noPresenceMinTemp', minTemp - 4); // 18
        setState('Heating', 'noPresenceMaxTemp', maxTemp - 4); // 18.25
      }
    });

    // sliderObject
    //   .bind('touchstart', preventTouchDrag)
    //   .bind('touchend', restoreTouchDrag)
    //   .bind('touchcancel', restoreTouchDrag)
  }

  function activateTempTrigger(tempName, tempParam, tempClass) {
    setTrigger(tempName, tempParam, function(props) {
      let timeSince = (Date.now() / 1000) - props.timestamp;
      if (timeSince <= 3600) {
        const value = Math.round(parseFloat(props.value) * 10) / 10;
        $('.screen.heating .top .' + tempClass + ' .value').text(value);
      }
    })
  }

  var initalTemp = true

  function activateTriggers() {
    setTrigger('Heating', 'presenceMinTemp', function(props) {
      $(".screen.heating .temperatureSlider").data("roundSlider").setValue(props.value);
      if (initalTemp) {
        initalTemp = false
      } else {
        showInfo('Temperature set to: ' + props.value)
      }
    })
    setTrigger('Heating', 'status', function(props) {
      $('.screen.heating > .titleContainer .status').text(props.value);
      if (props.value == 'on') {
        showScreenSlideForStaticDashboard('heating');
      }
    })
    activateTempTrigger('Livingroom-Temperature', 'temperature', 'livingroom')
    activateTempTrigger('Bedroom-Temperature', 'temperature', 'bedroom')
    activateTempTrigger('Bathroom-Temperature', 'temperature', 'bathroom')
    activateTempTrigger('Kitchen-Temperature', 'temperature', 'kitchen')
    activateTempTrigger('Diningroom-Temperature', 'temperature', 'diningroom')
    activateTempTrigger('Kidroom-Temperature', 'temperature', 'kidroom')
    activateTempTrigger('AirPurifier', 'temperature', 'purifier')
    activateTempTrigger('Weather', /*'current.temp'*/ 'current.feels_like', 'outside')
  }

  // Actually do something
  activateSlider();
  activateTriggers();
})
