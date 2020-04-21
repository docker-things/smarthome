function heatingSliderChangeTooltip(e) {
  return '<div class="value">' + e.value + '</div><div class="unit">°C</div>';
}

function heatingInitSlider() {
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
    value: 15,

    handleSize: '+40',
    handleShape: 'dot',
    pathColor: "rgba(197, 58, 13, .25)",
    rangeColor: "#C53A0D",

    svgMode: true,
    borderWidth: 0,
    startValue: 0,

    tooltipFormat: "heatingSliderChangeTooltip",

    // valueChange: function(e) {},
    stop: function(e) {
      const minTemp = e.value;
      const maxTemp = minTemp + 0.25;
      setState('Heating', 'presenceMinTemp', minTemp); // 22
      setState('Heating', 'presenceMaxTemp', maxTemp); // 22.25
      setState('Heating', 'sleepingMinTemp', minTemp - 4); // 18
      setState('Heating', 'sleepingMaxTemp', maxTemp - 4); // 18.25
      setState('Heating', 'noPresenceMinTemp', minTemp - 7); // 15
      setState('Heating', 'noPresenceMaxTemp', maxTemp - 7); // 15.25
    }
  });

  sliderObject
    .bind('touchstart', preventTouchDrag)
    .bind('touchend', restoreTouchDrag)
    .bind('touchcancel', restoreTouchDrag)
}

function heatingSetTriggers() {
  setTrigger('Heating', 'presenceMinTemp', function(props) {
    $(".screen.heating .temperatureSlider").data("roundSlider").setValue(props.value);
  })
  setTrigger('Heating', 'status', function(props) {
    $('.screen.heating > .titleContainer .status').text(props.value);
  })
  setTrigger('Bedroom-Temperature', 'temperature', function(props) {
    $('.screen.heating .top .bedroom .value').text(props.value);
  })
  setTrigger('Bathroom-Temperature', 'temperature', function(props) {
    $('.screen.heating .top .bathroom .value').text(props.value);
  })
}

$(document).ready(function() {
  heatingInitSlider();
  heatingSetTriggers();
})
