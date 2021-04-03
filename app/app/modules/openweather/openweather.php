<?php

class SunriseSunsetAPI {
  /**
   * OpenWeather API key
   *
   * @var string
   */
  private $_apiKey;

  /**
   * Language (ex: 'en')
   *
   * @var string
   */
  private $_lang;

  /**
   * Latitude
   *
   * @var float
   */
  private $_lat;

  /**
   * Longitude
   *
   * @var float
   */
  private $_lon;

  /**
   * Units (metric|imperial)
   *
   * @var string
   */
  private $_units;

  /**
   * API URL mask
   *
   * @var string
   */
  private $_url = 'https://api.openweathermap.org/data/2.5/onecall?lat=[LAT]&lon=[LON]&units=[UNITS]&lang=[LANG]&appid=[API_KEY]';

  /**
   * Set the location for which the API will be used
   *
   * @param float $lat Latitude
   * @param float $lon Longitude
   */
  public function __construct($lat, $lon, $units, $lang, $apiKey) {
    $this->_lat    = $lat;
    $this->_lon    = $lon;
    $this->_units  = $units;
    $this->_lang   = $lang;
    $this->_apiKey = $apiKey;
  }

  /**
   * Get data for a certain given date
   *
   * @param  string $date          Date in format: Y-m-d
   * @return array  Sunrise/sunset times
   */
  public function getData() {
    return $this->_getApi(
      $this->_lat,
      $this->_lon,
      $this->_units,
      $this->_lang,
      $this->_apiKey
    );
  }

  /**
   * @param $lat
   * @param $lon
   * @param $apiKey
   */
  private function _getApi($lat, $lon, $units, $lang, $apiKey) {

    // Bulid URL
    $url = $this->_url;
    $url = str_replace('[LAT]', $lat, $url);
    $url = str_replace('[LON]', $lon, $url);
    $url = str_replace('[UNITS]', $units, $url);
    $url = str_replace('[LANG]', $lang, $url);
    $url = str_replace('[API_KEY]', $apiKey, $url);

    // Make API request
    $jsonString = file_get_contents($url);

    // Decode JSON
    $json = json_decode($jsonString, true);

    // Check if JSON is OK
    if ($json && isset($json['lat'])) {
      $json['status'] = 'OK';
      return $json;
    }

    return [
      'results' => [],
      'status'  => 'error',
    ];
  }
}

$api = new SunriseSunsetAPI($argv[1], $argv[2], $argv[3], $argv[4], $argv[5]);
echo json_encode($api->getData());
