<?php

class UI_Controller_Screen_Main extends UI_Controller_Screen_Base {
  /**
   * The class name of the page
   * @var string
   */
  protected $class = 'main';

  /**
   * The display name of the page
   * @var string
   */
  protected $name = '';

  protected function setHTML() {
    return [
      '<img class="weatherSummaryBackground" src="/res/img/screen/main/main-temp-bkg.png">',

      '<div class="weatherSummary">',

      '<div class="textLine">',
      '<div class="icon"><i></i></div>',
      '<div class="value"></div>',
      '</div>',

      '<div class="minMaxLine">',
      '<div class="max">',
      '<div class="icon"><i class="fa fa-long-arrow-alt-up"></i></div>',
      '<div class="value"></div><div class="unit">°</div>',
      '</div>',
      '<div class="min">',
      '<div class="icon"><i class="fa fa-long-arrow-alt-down"></i></div>',
      '<div class="value"></div><div class="unit">°</div>',
      '</div>',
      '</div>',

      '<div class="bigTemp">',
      '<div class="value"></div>',
      '<div class="unit">°</div>',
      '</div>',

      '</div>',
      '<div class="changeImageButton hidden"><i class="fa fa-sync-alt"></i></div>',
      // $this->dumpWeatherIcons(),
    ];
  }

  protected function setScript() {
    $backgrounds = [];

    $path = 'res/img/screen/main/background/';
    foreach (glob($path . '*/*/*') AS $file) {

      // Ignore dirs
      if (!is_file($file)) {
        continue;
      }

      $tmp = explode('/', str_replace($path, '', $file), 3);

      $type     = $tmp[0];
      $dayNight = $tmp[1];

      $backgrounds[$type . '-' . $dayNight][] = '/' . $file . '?' . filemtime($file);
    }
    return [
      'const WEATHER_BACKGROUNDS = ' . str_replace('\/', '/', json_encode($backgrounds)) . ';',
    ];
  }

  private function dumpWeatherIcons() {
    $icons = [
      'wi-alien',
      'wi-barometer',
      'wi-celsius',
      'wi-cloud',
      'wi-cloud-down',
      'wi-cloud-refresh',
      'wi-cloud-up',
      'wi-cloudy',
      'wi-cloudy-gusts',
      'wi-cloudy-windy',
      'wi-day-cloudy',
      'wi-day-cloudy-gusts',
      'wi-day-cloudy-high',
      'wi-day-cloudy-windy',
      'wi-day-fog',
      'wi-day-hail',
      'wi-day-haze',
      'wi-day-light-wind',
      'wi-day-lightning',
      'wi-day-rain',
      'wi-day-rain-mix',
      'wi-day-rain-wind',
      'wi-day-showers',
      'wi-day-sleet',
      'wi-day-sleet-storm',
      'wi-day-snow',
      'wi-day-snow-thunderstorm',
      'wi-day-snow-wind',
      'wi-day-sprinkle',
      'wi-day-storm-showers',
      'wi-day-sunny',
      'wi-day-sunny-overcast',
      'wi-day-thunderstorm',
      'wi-day-windy',
      'wi-degrees',
      'wi-direction-down',
      'wi-direction-down-left',
      'wi-direction-down-right',
      'wi-direction-left',
      'wi-direction-right',
      'wi-direction-up',
      'wi-direction-up-left',
      'wi-direction-up-right',
      'wi-dust',
      'wi-earthquake',
      'wi-fahrenheit',
      'wi-fire',
      'wi-flood',
      'wi-fog',
      'wi-forecast-io-clear-day',
      'wi-forecast-io-clear-night',
      'wi-forecast-io-cloudy',
      'wi-forecast-io-fog',
      'wi-forecast-io-hail',
      'wi-forecast-io-partly-cloudy-day',
      'wi-forecast-io-partly-cloudy-night',
      'wi-forecast-io-rain',
      'wi-forecast-io-sleet',
      'wi-forecast-io-snow',
      'wi-forecast-io-thunderstorm',
      'wi-forecast-io-tornado',
      'wi-forecast-io-wind',
      'wi-gale-warning',
      'wi-hail',
      'wi-horizon',
      'wi-horizon-alt',
      'wi-hot',
      'wi-humidity',
      'wi-hurricane',
      'wi-hurricane-warning',
      'wi-lightning',
      'wi-lunar-eclipse',
      'wi-meteor',
      'wi-moon-0',
      'wi-moon-1',
      'wi-moon-10',
      'wi-moon-11',
      'wi-moon-12',
      'wi-moon-13',
      'wi-moon-14',
      'wi-moon-15',
      'wi-moon-16',
      'wi-moon-17',
      'wi-moon-18',
      'wi-moon-19',
      'wi-moon-2',
      'wi-moon-20',
      'wi-moon-21',
      'wi-moon-22',
      'wi-moon-23',
      'wi-moon-24',
      'wi-moon-25',
      'wi-moon-26',
      'wi-moon-27',
      'wi-moon-3',
      'wi-moon-4',
      'wi-moon-5',
      'wi-moon-6',
      'wi-moon-7',
      'wi-moon-8',
      'wi-moon-9',
      'wi-moon-alt-first-quarter',
      'wi-moon-alt-full',
      'wi-moon-alt-new',
      'wi-moon-alt-third-quarter',
      'wi-moon-alt-waning-crescent-1',
      'wi-moon-alt-waning-crescent-2',
      'wi-moon-alt-waning-crescent-3',
      'wi-moon-alt-waning-crescent-4',
      'wi-moon-alt-waning-crescent-5',
      'wi-moon-alt-waning-crescent-6',
      'wi-moon-alt-waning-gibbous-1',
      'wi-moon-alt-waning-gibbous-2',
      'wi-moon-alt-waning-gibbous-3',
      'wi-moon-alt-waning-gibbous-4',
      'wi-moon-alt-waning-gibbous-5',
      'wi-moon-alt-waning-gibbous-6',
      'wi-moon-alt-waxing-crescent-1',
      'wi-moon-alt-waxing-crescent-2',
      'wi-moon-alt-waxing-crescent-3',
      'wi-moon-alt-waxing-crescent-4',
      'wi-moon-alt-waxing-crescent-5',
      'wi-moon-alt-waxing-crescent-6',
      'wi-moon-alt-waxing-gibbous-1',
      'wi-moon-alt-waxing-gibbous-2',
      'wi-moon-alt-waxing-gibbous-3',
      'wi-moon-alt-waxing-gibbous-4',
      'wi-moon-alt-waxing-gibbous-5',
      'wi-moon-alt-waxing-gibbous-6',
      'wi-moon-first-quarter',
      'wi-moon-full',
      'wi-moon-new',
      'wi-moon-third-quarter',
      'wi-moon-waning-crescent-1',
      'wi-moon-waning-crescent-2',
      'wi-moon-waning-crescent-3',
      'wi-moon-waning-crescent-4',
      'wi-moon-waning-crescent-5',
      'wi-moon-waning-crescent-6',
      'wi-moon-waning-gibbous-1',
      'wi-moon-waning-gibbous-2',
      'wi-moon-waning-gibbous-3',
      'wi-moon-waning-gibbous-4',
      'wi-moon-waning-gibbous-5',
      'wi-moon-waning-gibbous-6',
      'wi-moon-waxing-crescent-1',
      'wi-moon-waxing-crescent-2',
      'wi-moon-waxing-crescent-3',
      'wi-moon-waxing-crescent-4',
      'wi-moon-waxing-crescent-5',
      'wi-moon-waxing-crescent-6',
      'wi-moon-waxing-gibbous-1',
      'wi-moon-waxing-gibbous-2',
      'wi-moon-waxing-gibbous-3',
      'wi-moon-waxing-gibbous-4',
      'wi-moon-waxing-gibbous-5',
      'wi-moon-waxing-gibbous-6',
      'wi-moonrise',
      'wi-moonset',
      'wi-na',
      'wi-night-alt-cloudy',
      'wi-night-alt-cloudy-gusts',
      'wi-night-alt-cloudy-high',
      'wi-night-alt-cloudy-windy',
      'wi-night-alt-hail',
      'wi-night-alt-lightning',
      'wi-night-alt-partly-cloudy',
      'wi-night-alt-rain',
      'wi-night-alt-rain-mix',
      'wi-night-alt-rain-wind',
      'wi-night-alt-showers',
      'wi-night-alt-sleet',
      'wi-night-alt-sleet-storm',
      'wi-night-alt-snow',
      'wi-night-alt-snow-thunderstorm',
      'wi-night-alt-snow-wind',
      'wi-night-alt-sprinkle',
      'wi-night-alt-storm-showers',
      'wi-night-alt-thunderstorm',
      'wi-night-clear',
      'wi-night-cloudy',
      'wi-night-cloudy-gusts',
      'wi-night-cloudy-high',
      'wi-night-cloudy-windy',
      'wi-night-fog',
      'wi-night-hail',
      'wi-night-lightning',
      'wi-night-partly-cloudy',
      'wi-night-rain',
      'wi-night-rain-mix',
      'wi-night-rain-wind',
      'wi-night-showers',
      'wi-night-sleet',
      'wi-night-sleet-storm',
      'wi-night-snow',
      'wi-night-snow-thunderstorm',
      'wi-night-snow-wind',
      'wi-night-sprinkle',
      'wi-night-storm-showers',
      'wi-night-thunderstorm',
      'wi-owm-200',
      'wi-owm-201',
      'wi-owm-202',
      'wi-owm-210',
      'wi-owm-211',
      'wi-owm-212',
      'wi-owm-221',
      'wi-owm-230',
      'wi-owm-231',
      'wi-owm-232',
      'wi-owm-300',
      'wi-owm-301',
      'wi-owm-302',
      'wi-owm-310',
      'wi-owm-311',
      'wi-owm-312',
      'wi-owm-313',
      'wi-owm-314',
      'wi-owm-321',
      'wi-owm-500',
      'wi-owm-501',
      'wi-owm-502',
      'wi-owm-503',
      'wi-owm-504',
      'wi-owm-511',
      'wi-owm-520',
      'wi-owm-521',
      'wi-owm-522',
      'wi-owm-531',
      'wi-owm-600',
      'wi-owm-601',
      'wi-owm-602',
      'wi-owm-611',
      'wi-owm-612',
      'wi-owm-615',
      'wi-owm-616',
      'wi-owm-620',
      'wi-owm-621',
      'wi-owm-622',
      'wi-owm-701',
      'wi-owm-711',
      'wi-owm-721',
      'wi-owm-731',
      'wi-owm-741',
      'wi-owm-761',
      'wi-owm-762',
      'wi-owm-771',
      'wi-owm-781',
      'wi-owm-800',
      'wi-owm-801',
      'wi-owm-802',
      'wi-owm-803',
      'wi-owm-804',
      'wi-owm-900',
      'wi-owm-901',
      'wi-owm-902',
      'wi-owm-903',
      'wi-owm-904',
      'wi-owm-905',
      'wi-owm-906',
      'wi-owm-957',
      'wi-owm-day-200',
      'wi-owm-day-201',
      'wi-owm-day-202',
      'wi-owm-day-210',
      'wi-owm-day-211',
      'wi-owm-day-212',
      'wi-owm-day-221',
      'wi-owm-day-230',
      'wi-owm-day-231',
      'wi-owm-day-232',
      'wi-owm-day-300',
      'wi-owm-day-301',
      'wi-owm-day-302',
      'wi-owm-day-310',
      'wi-owm-day-311',
      'wi-owm-day-312',
      'wi-owm-day-313',
      'wi-owm-day-314',
      'wi-owm-day-321',
      'wi-owm-day-500',
      'wi-owm-day-501',
      'wi-owm-day-502',
      'wi-owm-day-503',
      'wi-owm-day-504',
      'wi-owm-day-511',
      'wi-owm-day-520',
      'wi-owm-day-521',
      'wi-owm-day-522',
      'wi-owm-day-531',
      'wi-owm-day-600',
      'wi-owm-day-601',
      'wi-owm-day-602',
      'wi-owm-day-611',
      'wi-owm-day-612',
      'wi-owm-day-615',
      'wi-owm-day-616',
      'wi-owm-day-620',
      'wi-owm-day-621',
      'wi-owm-day-622',
      'wi-owm-day-701',
      'wi-owm-day-711',
      'wi-owm-day-721',
      'wi-owm-day-731',
      'wi-owm-day-741',
      'wi-owm-day-761',
      'wi-owm-day-762',
      'wi-owm-day-781',
      'wi-owm-day-800',
      'wi-owm-day-801',
      'wi-owm-day-802',
      'wi-owm-day-803',
      'wi-owm-day-804',
      'wi-owm-day-900',
      'wi-owm-day-902',
      'wi-owm-day-903',
      'wi-owm-day-904',
      'wi-owm-day-906',
      'wi-owm-day-957',
      'wi-owm-night-200',
      'wi-owm-night-201',
      'wi-owm-night-202',
      'wi-owm-night-210',
      'wi-owm-night-211',
      'wi-owm-night-212',
      'wi-owm-night-221',
      'wi-owm-night-230',
      'wi-owm-night-231',
      'wi-owm-night-232',
      'wi-owm-night-300',
      'wi-owm-night-301',
      'wi-owm-night-302',
      'wi-owm-night-310',
      'wi-owm-night-311',
      'wi-owm-night-312',
      'wi-owm-night-313',
      'wi-owm-night-314',
      'wi-owm-night-321',
      'wi-owm-night-500',
      'wi-owm-night-501',
      'wi-owm-night-502',
      'wi-owm-night-503',
      'wi-owm-night-504',
      'wi-owm-night-511',
      'wi-owm-night-520',
      'wi-owm-night-521',
      'wi-owm-night-522',
      'wi-owm-night-531',
      'wi-owm-night-600',
      'wi-owm-night-601',
      'wi-owm-night-602',
      'wi-owm-night-611',
      'wi-owm-night-612',
      'wi-owm-night-615',
      'wi-owm-night-616',
      'wi-owm-night-620',
      'wi-owm-night-621',
      'wi-owm-night-622',
      'wi-owm-night-701',
      'wi-owm-night-711',
      'wi-owm-night-721',
      'wi-owm-night-731',
      'wi-owm-night-741',
      'wi-owm-night-761',
      'wi-owm-night-762',
      'wi-owm-night-781',
      'wi-owm-night-800',
      'wi-owm-night-801',
      'wi-owm-night-802',
      'wi-owm-night-803',
      'wi-owm-night-804',
      'wi-owm-night-900',
      'wi-owm-night-902',
      'wi-owm-night-903',
      'wi-owm-night-904',
      'wi-owm-night-906',
      'wi-owm-night-957',
      'wi-rain',
      'wi-rain-mix',
      'wi-rain-wind',
      'wi-raindrop',
      'wi-raindrops',
      'wi-refresh',
      'wi-refresh-alt',
      'wi-sandstorm',
      'wi-showers',
      'wi-sleet',
      'wi-small-craft-advisory',
      'wi-smog',
      'wi-smoke',
      'wi-snow',
      'wi-snow',
      'wi-snow-wind',
      'wi-snowflake-cold',
      'wi-solar-eclipse',
      'wi-sprinkle',
      'wi-stars',
      'wi-storm-showers',
      'wi-storm-showers',
      'wi-storm-warning',
      'wi-strong-wind',
      'wi-sunrise',
      'wi-sunset',
      'wi-thermometer',
      'wi-thermometer-exterior',
      'wi-thermometer-internal',
      'wi-thunderstorm',
      'wi-thunderstorm',
      'wi-time-1',
      'wi-time-10',
      'wi-time-11',
      'wi-time-12',
      'wi-time-2',
      'wi-time-3',
      'wi-time-4',
      'wi-time-5',
      'wi-time-6',
      'wi-time-7',
      'wi-time-8',
      'wi-time-9',
      'wi-tornado',
      'wi-train',
      'wi-tsunami',
      'wi-umbrella',
      'wi-volcano',
      'wi-wind-beaufort-0',
      'wi-wind-beaufort-1',
      'wi-wind-beaufort-10',
      'wi-wind-beaufort-11',
      'wi-wind-beaufort-12',
      'wi-wind-beaufort-2',
      'wi-wind-beaufort-3',
      'wi-wind-beaufort-4',
      'wi-wind-beaufort-5',
      'wi-wind-beaufort-6',
      'wi-wind-beaufort-7',
      'wi-wind-beaufort-8',
      'wi-wind-beaufort-9',
      'wi-wind-direction',
      'wi-windy',
      'wi-wmo4680-0,',
      'wi-wmo4680-00',
      'wi-wmo4680-01',
      'wi-wmo4680-02',
      'wi-wmo4680-03',
      'wi-wmo4680-04',
      'wi-wmo4680-05',
      'wi-wmo4680-1,',
      'wi-wmo4680-10',
      'wi-wmo4680-11',
      'wi-wmo4680-12',
      'wi-wmo4680-18',
      'wi-wmo4680-2,',
      'wi-wmo4680-20',
      'wi-wmo4680-21',
      'wi-wmo4680-22',
      'wi-wmo4680-23',
      'wi-wmo4680-24',
      'wi-wmo4680-25',
      'wi-wmo4680-26',
      'wi-wmo4680-27',
      'wi-wmo4680-28',
      'wi-wmo4680-29',
      'wi-wmo4680-3,',
      'wi-wmo4680-30',
      'wi-wmo4680-31',
      'wi-wmo4680-32',
      'wi-wmo4680-33',
      'wi-wmo4680-34',
      'wi-wmo4680-35',
      'wi-wmo4680-4,',
      'wi-wmo4680-40',
      'wi-wmo4680-41',
      'wi-wmo4680-42',
      'wi-wmo4680-43',
      'wi-wmo4680-44',
      'wi-wmo4680-45',
      'wi-wmo4680-46',
      'wi-wmo4680-47',
      'wi-wmo4680-48',
      'wi-wmo4680-5,',
      'wi-wmo4680-50',
      'wi-wmo4680-51',
      'wi-wmo4680-52',
      'wi-wmo4680-53',
      'wi-wmo4680-54',
      'wi-wmo4680-55',
      'wi-wmo4680-56',
      'wi-wmo4680-57',
      'wi-wmo4680-58',
      'wi-wmo4680-60',
      'wi-wmo4680-61',
      'wi-wmo4680-62',
      'wi-wmo4680-63',
      'wi-wmo4680-64',
      'wi-wmo4680-65',
      'wi-wmo4680-66',
      'wi-wmo4680-67',
      'wi-wmo4680-68',
      'wi-wmo4680-70',
      'wi-wmo4680-71',
      'wi-wmo4680-72',
      'wi-wmo4680-73',
      'wi-wmo4680-74',
      'wi-wmo4680-75',
      'wi-wmo4680-76',
      'wi-wmo4680-77',
      'wi-wmo4680-78',
      'wi-wmo4680-80',
      'wi-wmo4680-81',
      'wi-wmo4680-82',
      'wi-wmo4680-83',
      'wi-wmo4680-84',
      'wi-wmo4680-85',
      'wi-wmo4680-86',
      'wi-wmo4680-87',
      'wi-wmo4680-89',
      'wi-wmo4680-90',
      'wi-wmo4680-91',
      'wi-wmo4680-92',
      'wi-wmo4680-93',
      'wi-wmo4680-94',
      'wi-wmo4680-95',
      'wi-wmo4680-96',
      'wi-wmo4680-99',
      'wi-wu-chanceflurries',
      'wi-wu-chancerain',
      'wi-wu-chancesleat',
      'wi-wu-chancesnow',
      'wi-wu-chancetstorms',
      'wi-wu-clear',
      'wi-wu-cloudy',
      'wi-wu-flurries',
      'wi-wu-hazy',
      'wi-wu-mostlycloudy',
      'wi-wu-mostlysunny',
      'wi-wu-partlycloudy',
      'wi-wu-partlysunny',
      'wi-wu-rain',
      'wi-wu-sleat',
      'wi-wu-snow',
      'wi-wu-sunny',
      'wi-wu-tstorms',
      'wi-wu-unknown',
      'wi-yahoo-0',
      'wi-yahoo-1',
      'wi-yahoo-10',
      'wi-yahoo-11',
      'wi-yahoo-12',
      'wi-yahoo-13',
      'wi-yahoo-14',
      'wi-yahoo-15',
      'wi-yahoo-16',
      'wi-yahoo-17',
      'wi-yahoo-18',
      'wi-yahoo-19',
      'wi-yahoo-2',
      'wi-yahoo-20',
      'wi-yahoo-21',
      'wi-yahoo-22',
      'wi-yahoo-23',
      'wi-yahoo-24',
      'wi-yahoo-25',
      'wi-yahoo-26',
      'wi-yahoo-27',
      'wi-yahoo-28',
      'wi-yahoo-29',
      'wi-yahoo-3',
      'wi-yahoo-30',
      'wi-yahoo-31',
      'wi-yahoo-32',
      'wi-yahoo-3200',
      'wi-yahoo-33',
      'wi-yahoo-34',
      'wi-yahoo-35',
      'wi-yahoo-36',
      'wi-yahoo-37',
      'wi-yahoo-38',
      'wi-yahoo-39',
      'wi-yahoo-4',
      'wi-yahoo-40',
      'wi-yahoo-41',
      'wi-yahoo-42',
      'wi-yahoo-43',
      'wi-yahoo-44',
      'wi-yahoo-45',
      'wi-yahoo-46',
      'wi-yahoo-47',
      'wi-yahoo-5',
      'wi-yahoo-6',
      'wi-yahoo-7',
      'wi-yahoo-8',
      'wi-yahoo-9',
    ];
    $html   = [];
    $html[] = '<div class="weatherIcons">';
    foreach ($icons AS $icon) {
      if (isset($_GET['filter']) && stripos($icon, $_GET['filter']) === false) {
        continue;
      }
      $html[] = '<div><i class="wi ' . $icon . '"></i>' . $icon . '</div>';
    }
    $html[] = '</div>';
    return implode("\n", $html);
  }
}
?>
