<?php

class SunriseSunsetAPI {
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
     * What keys to return from the API
     *
     * @var array
     */
    private $_results = [
        'sunrise',
        'sunset',
        'solar_noon',
        'day_length',
        'civil_twilight_begin',
        'civil_twilight_end',
        'nautical_twilight_begin',
        'nautical_twilight_end',
        'astronomical_twilight_begin',
        'astronomical_twilight_end',
    ];

    /**
     * Timezone
     *
     * @var string
     */
    private $_timezone;

    /**
     * API URL mask
     *
     * @var string
     */
    private $_url = 'https://api.sunrise-sunset.org/json?lat=[LAT]&lng=[LON]&date=[DATE]&formatted=0';

    /**
     * Set the location for which the API will be used
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     */
    public function __construct($lat, $lon) {
        $this->_lat      = $lat;
        $this->_lon      = $lon;
        $this->_timezone = getenv('TZ');
    }

    /**
     * Get data for a certain given date
     *
     * @param  string $date          Date in format: Y-m-d
     * @return array  Sunrise/sunset times
     */
    public function getDate($date) {
        return $this->_getApi($this->_lat, $this->_lon, date('Y-m-d', strtotime($date)));
    }

    /**
     * The main function doing stuff to get data
     *
     * @param  float  $lat           Latitude
     * @param  float  $lon           Longitude
     * @param  string $date          Date in format: Y-m-d
     * @return array  Sunrise/sunset times
     */
    private function _getApi($lat, $lon, $date) {

        // Bulid URL
        $url = $this->_url;
        $url = str_replace('[LAT]', $lat, $url);
        $url = str_replace('[LON]', $lon, $url);
        $url = str_replace('[DATE]', $date, $url);

        // Make API request
        $jsonString = file_get_contents($url);

        // Decode JSON
        $json = json_decode($jsonString, true);

        // Check if JSON is OK
        if ($json && isset($json['status']) && 'OK' === $json['status']) {

            // Turn 12-hour to 24-hour time & set timezone
            foreach ($this->_results AS $key) {

                // If key was not received
                if (!isset($json['results'][$key])) {
                    $json['results'][$key] = 'unknown';
                    continue;
                }

                if ('day_length' == $key) {
                    continue;
                }

                // Transform the value
                $json['results'][$key] = DateTime::createFromFormat(
                    'H:i',
                    date('H:i', strtotime($json['results'][$key])),
                    new DateTimeZone('UTC')
                )->setTimeZone(new DateTimeZone($this->_timezone))
                 ->format('H:i');
            }
            return $json;
        }

        return [
            'results' => [],
            'status'  => 'error',
        ];
    }
}

$api = new SunriseSunsetAPI($argv[1], $argv[2]);
echo json_encode($api->getDate($argv[3]));
