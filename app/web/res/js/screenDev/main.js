$(document).ready(function() {

  // const CURRENT_TEMP_PROVIDER = 'current.temp';
  const CURRENT_TEMP_PROVIDER = 'current.feels_like';

  // const CURRENT_DESC_PROVIDER = 'current.weather.0.main';
  const CURRENT_DESC_PROVIDER = 'current.weather.0.description';
  const CURRENT_DESC_ICON_PROVIDER = 'current.weather.0.id';

  /**
   * Need to add the following weather backgrounds:
   *
   *  - ash-day.jpg
   *  - ash-night.jpg
   *
   *  - dust-night.jpg
   *
   *  - sand-day.jpg
   *  - sand-night.jpg
   *
   *  - smoke-day.jpg
   *  - smoke-night.jpg
   *
   *  - squall-night.jpg
   */
  const BACKGROUND_PROVIDER = 'current.weather.0.main';

  const MIN_MAX_PROVIDERS = [
    // 'daily.0.temp.min',
    // 'daily.0.temp.max',
    'daily.0.feels_like.day',
    'daily.0.feels_like.eve',
    'daily.0.feels_like.morn',
    'daily.0.feels_like.night',
  ];

  const ICONS = {
    'day': {
      200: 'day-thunderstorm',
      201: 'day-thunderstorm',
      202: 'day-thunderstorm',
      210: 'day-lightning',
      211: 'day-lightning',
      212: 'day-lightning',
      221: 'day-lightning',
      230: 'day-thunderstorm',
      231: 'day-thunderstorm',
      232: 'day-thunderstorm',
      300: 'day-sprinkle',
      301: 'day-sprinkle',
      302: 'day-rain',
      310: 'day-rain',
      311: 'day-rain',
      312: 'day-rain',
      313: 'day-rain',
      314: 'day-rain',
      321: 'day-sprinkle',
      500: 'day-sprinkle',
      501: 'day-rain',
      502: 'day-rain',
      503: 'day-rain',
      504: 'day-rain',
      511: 'day-rain-mix',
      520: 'day-showers',
      521: 'day-showers',
      522: 'day-showers',
      531: 'day-storm-showers',
      600: 'day-snow',
      601: 'day-sleet',
      602: 'day-snow',
      611: 'day-rain-mix',
      612: 'day-rain-mix',
      615: 'day-rain-mix',
      616: 'day-rain-mix',
      620: 'day-rain-mix',
      621: 'day-snow',
      622: 'day-snow',
      701: 'day-showers',
      711: 'smoke',
      721: 'day-haze',
      731: 'dust',
      741: 'day-fog',
      761: 'dust',
      762: 'dust',
      771: 'cloudy-gusts',
      781: 'tornado',
      800: 'day-sunny',
      801: 'day-cloudy-gusts',
      802: 'day-cloudy-gusts',
      803: 'day-cloudy-gusts',
      804: 'day-sunny-overcast',
      900: 'tornado',
      901: 'storm-showers',
      902: 'hurricane',
      903: 'snowflake-cold',
      904: 'hot',
      905: 'windy',
      906: 'day-hail',
      957: 'strong-wind',
    },
    'night': {
      200: 'night-alt-thunderstorm',
      201: 'night-alt-thunderstorm',
      202: 'night-alt-thunderstorm',
      210: 'night-alt-lightning',
      211: 'night-alt-lightning',
      212: 'night-alt-lightning',
      221: 'night-alt-lightning',
      230: 'night-alt-thunderstorm',
      231: 'night-alt-thunderstorm',
      232: 'night-alt-thunderstorm',
      300: 'night-alt-sprinkle',
      301: 'night-alt-sprinkle',
      302: 'night-alt-rain',
      310: 'night-alt-rain',
      311: 'night-alt-rain',
      312: 'night-alt-rain',
      313: 'night-alt-rain',
      314: 'night-alt-rain',
      321: 'night-alt-sprinkle',
      500: 'night-alt-sprinkle',
      501: 'night-alt-rain',
      502: 'night-alt-rain',
      503: 'night-alt-rain',
      504: 'night-alt-rain',
      511: 'night-alt-rain-mix',
      520: 'night-alt-showers',
      521: 'night-alt-showers',
      522: 'night-alt-showers',
      531: 'night-alt-storm-showers',
      600: 'night-alt-snow',
      601: 'night-alt-sleet',
      602: 'night-alt-snow',
      611: 'night-alt-rain-mix',
      612: 'night-alt-rain-mix',
      615: 'night-alt-rain-mix',
      616: 'night-alt-rain-mix',
      620: 'night-alt-rain-mix',
      621: 'night-alt-snow',
      622: 'night-alt-snow',
      701: 'night-alt-showers',
      711: 'smoke',
      721: 'day-haze',
      731: 'dust',
      741: 'night-fog',
      761: 'dust',
      762: 'dust',
      771: 'cloudy-gusts',
      781: 'tornado',
      800: 'night-clear',
      801: 'night-alt-cloudy-gusts',
      802: 'night-alt-cloudy-gusts',
      803: 'night-alt-cloudy-gusts',
      804: 'night-alt-cloudy',
      900: 'tornado',
      901: 'storm-showers',
      902: 'hurricane',
      903: 'snowflake-cold',
      904: 'hot',
      905: 'windy',
      906: 'night-alt-hail',
      957: 'strong-wind',
    },
  };

  function roundValue(value) {
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

  function getDayNight() {
    return getStateValue('Sun', 'state') == 'night' ? 'night' : 'day';
  }

  function setCurrentWeatherIcon(props) {
    const iconId = getStateValue('Weather', CURRENT_DESC_ICON_PROVIDER);
    const dayNight = getDayNight();

    const icon = $('.screen.main .weatherSummary .textLine .icon i');
    icon.attr('class', '');
    if (iconId in ICONS[dayNight]) {
      icon.addClass('wi wi-' + ICONS[dayNight][iconId]);
    } else {
      icon.addClass('wi wi-alien');
    }
  }

  function setBackground(props) {
    const name = getStateValue('Weather', BACKGROUND_PROVIDER).toLowerCase();
    const dayNight = getDayNight();

    const type = name + '-' + dayNight;

    // If there's at least an image for the current weather type
    if (type in WEATHER_BACKGROUNDS && WEATHER_BACKGROUNDS[type].length != 0) {

      // Get random index
      const imageIndex = Math.floor(Math.random() * WEATHER_BACKGROUNDS[type].length);

      // Actual image path
      const imagePath = WEATHER_BACKGROUNDS[type][imageIndex];

      // Set it
      $('.screen.main').css('background-image', 'url("' + imagePath + '")');
    } else {
      $('.screen.main').css('background-image', 'inherit');
    }
  }

  function activateTriggers() {

    setTrigger('Weather', CURRENT_TEMP_PROVIDER, (props) => {
      $('.screen.main .weatherSummary .bigTemp .value').text(roundValue(props.value));
    });

    setTrigger('Weather', CURRENT_DESC_PROVIDER, (props) => {
      $('.screen.main .weatherSummary .textLine .value').text(props.value);
    });

    setTrigger('Weather', CURRENT_DESC_ICON_PROVIDER, setCurrentWeatherIcon);
    setTrigger('Sun', 'state', setCurrentWeatherIcon);

    setTrigger('Weather', BACKGROUND_PROVIDER, setBackground);
    setTrigger('Sun', 'state', setBackground);

    for (var i = MIN_MAX_PROVIDERS.length - 1; i >= 0; i--) {
      setTrigger('Weather', MIN_MAX_PROVIDERS[i], setMinMaxTemp);
    }
  }

  function activateImageChangerWhenFullScreen() {

    function showImageChanger() {
      if (isFullScreen() || DASHBOARD_ROOM == 'NONE') {
        $('.screen.main>.container>.changeImageButton').removeClass('hidden')
      } else {
        $('.screen.main>.container>.changeImageButton').addClass('hidden')
      }
    }

    showImageChanger();
    $(window).resize(showImageChanger);

    $('.screen.main>.container>.changeImageButton')
      .click(setBackground)
      .bind(setBackground);
  }

  // Actually do something
  activateTriggers();
  activateImageChangerWhenFullScreen();
})
