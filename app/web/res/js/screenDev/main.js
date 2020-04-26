$(document).ready(function() {

	// const CURRENT_TEMP_PROVIDER = 'current.temp';
	const CURRENT_TEMP_PROVIDER = 'current.feels_like';

	// const CURRENT_DESC_PROVIDER = 'current.weather.0.main';
	const CURRENT_DESC_PROVIDER = 'current.weather.0.description';

  const MIN_MAX_PROVIDERS = [
    // 'daily.0.temp.min',
    // 'daily.0.temp.max',
    'daily.0.feels_like.day',
    'daily.0.feels_like.eve',
    'daily.0.feels_like.morn',
    'daily.0.feels_like.night',
  ];

  function roundValue(value) {
    // return Math.round(parseFloat(value) * 10) / 10;
    return Math.round(parseFloat(value));
  }

  function setMinMaxTemp(props) {
    let min = 100;
    let max = -100;

    for (var i = MIN_MAX_PROVIDERS.length - 1; i >= 0; i--) {
      const value = getStateValue('Weather', MIN_MAX_PROVIDERS[i]);
      min = Math.min(min, value);
      max = Math.max(max, value);
    }

    $('.screen.main .weatherSummary .minMaxLine .min .value').text(roundValue(min));
    $('.screen.main .weatherSummary .minMaxLine .max .value').text(roundValue(max));
  }

  function activateTriggers() {

    setTrigger('Weather', CURRENT_TEMP_PROVIDER, (props) => {
      $('.screen.main .weatherSummary .bigTemp .value').text(roundValue(props.value));
    });

    setTrigger('Weather', CURRENT_DESC_PROVIDER, (props) => {
      $('.screen.main .weatherSummary .textLine .value').text(props.value);
    });

    for (var i = MIN_MAX_PROVIDERS.length - 1; i >= 0; i--) {
      setTrigger('Weather', MIN_MAX_PROVIDERS[i], setMinMaxTemp);
    }

    // setTrigger('Weather', 'current.feels_like', (props) => {
    //   $('.screen.main .feelsLike .value').text(props.value);
    // });
    // setTrigger('Weather', 'current.humidity', (props) => {
    //   $('.screen.main .outsideHumidity .value').text(props.value);
    // });
    // setTrigger('Weather', 'current.pressure', (props) => {
    //   $('.screen.main .outsidePressure .value').text(props.value);
    // });
    // setTrigger('Weather', 'current.wind_speed', (props) => {
    //   $('.screen.main .windSpeed .value').text(props.value);
    // });

    // setTrigger('Bedroom-Temperature', 'temperature', (props) => {
    //   $('.screen.main .insideTemp .value').text(props.value);
    // });
    // setTrigger('Bedroom-Temperature', 'humidity', (props) => {
    //   $('.screen.main .insideHumidity .value').text(props.value);
    // });
    // setTrigger('Bedroom-Temperature', 'pressure', (props) => {
    //   $('.screen.main .insidePressure .value').text(props.value);
    // });
  }

  // Actually do something
  activateTriggers();
})
