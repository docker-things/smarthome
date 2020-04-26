$(document).ready(() => {
  function activateTriggers() {
    // const value = Math.round(parseFloat(props.value) * 10) / 10;

    setTrigger('Weather', 'current.temp', (props) => {
      $('.screen.main .outsideTemp .value').text(props.value);
    });

    setTrigger('Weather', 'current.feels_like', (props) => {
      $('.screen.main .feelsLike .value').text(props.value);
    });
    setTrigger('Weather', 'current.humidity', (props) => {
      $('.screen.main .outsideHumidity .value').text(props.value);
    });
    setTrigger('Weather', 'current.pressure', (props) => {
      $('.screen.main .outsidePressure .value').text(props.value);
    });
    setTrigger('Weather', 'current.wind_speed', (props) => {
      $('.screen.main .windSpeed .value').text(props.value);
    });

    setTrigger('Bedroom-Temperature', 'temperature', (props) => {
      $('.screen.main .insideTemp .value').text(props.value);
    });
    setTrigger('Bedroom-Temperature', 'humidity', (props) => {
      $('.screen.main .insideHumidity .value').text(props.value);
    });
    setTrigger('Bedroom-Temperature', 'pressure', (props) => {
      $('.screen.main .insidePressure .value').text(props.value);
    });
  }

  // Actually do something
  activateTriggers();
})
