<?php

class UI_Controller_Dashboard extends Core_Controller_Base {
    /**
     * @var array
     */
    private $map = [
        'url'        => '/res/img/house-plan.png',
        'size'       => [556, 425], // width, height
        'wall-width' => 7,
        'rooms'      => [
            'Bedroom'    => [
                'position' => [6, 6],     // x, y
                'size'     => [242, 282], // width, height
                'door'     => [
                    'Bedroom-Door' => [
                        'wall'     => 'right',
                        'distance' => 215,
                        'width'    => 50,
                    ],
                ],
                'window'   => [
                    'Bedroom-Window-1' => [
                        'wall'     => 'left',
                        'distance' => 160,
                        'width'    => 100,
                    ],
                    'Bedroom-Window-2' => [
                        'wall'     => 'left',
                        'distance' => 35,
                        'width'    => 50,
                    ],
                ],
            ],
            'Livingroom' => [
                'position' => [255, 6],   // x, y
                'size'     => [295, 184], // width, height
                'door'     => [
                    'Livingroom-Door' => [
                        'wall'     => 'down',
                        'distance' => 17,
                        'width'    => 50,
                    ],
                ],
                'window'   => [
                    'Livingroom-Window' => [
                        'wall'     => 'up',
                        'distance' => 60,
                        'width'    => 110,
                    ],
                ],
            ],
            'Kitchen'    => [
                'position' => [6, 295],   // x, y
                'size'     => [242, 124], // width, height
                'door'     => [
                    'Kitchen-Door' => [
                        'wall'     => 'right',
                        'distance' => 38,
                        'width'    => 50,
                    ],
                ],
                'window'   => [
                    'Kitchen-Window' => [
                        'wall'     => 'left',
                        'distance' => 20,
                        'width'    => 80,
                    ],
                ],
            ],
            'Bathroom'   => [
                'position' => [348, 197], // x, y
                'size'     => [202, 135], // width, height
                'door'     => [
                    'Bathroom-Door' => [
                        'wall'     => 'left',
                        'distance' => 40,
                        'width'    => 50,
                    ],
                ],
            ],
            'Hallway'    => [
                'position' => [255, 197], // x, y
                'size'     => [87, 222],  // width, height
            ],
            'Entrance'   => [
                'position' => [342, 338], // x, y
                'size'     => [209, 81],  // width, height
                'door'     => [
                    'Entrance-Door' => [
                        'wall'     => 'down',
                        'distance' => 120,
                        'width'    => 60,
                    ],
                ],
            ],
        ],
    ];

    /**
     * @var string
     */
    private $title = 'SmartHome';

    public function run() {
        echo '<html><head>
            <meta name="viewport" content="width=device-width, initial-scale=1" />
            <title>' . $this->title . '</title>
            <link href="/res/css/dashboard.css" rel="stylesheet" type="text/css" media="all">
            <style>
            .mapContainer {
                min-width: ' . $this->map['size'][0] . 'px;
                min-height: ' . $this->map['size'][1] . 'px;
            }
            .map {
                width: ' . $this->map['size'][0] . 'px;
                height: ' . $this->map['size'][1] . 'px;
                background-image: url("' . $this->map['url'] . '");
            }
            ' . $this->createRoomsCSS() . '
            </style>
            <script src="/res/js/jquery.min.js"></script>
            <script src="/res/js/paho-mqtt-min.js"></script>
            <script src="/res/js/dashboard.js"></script>
            </head><body>';
        echo '<div class="container">';
        echo $this->createButtons();
        echo $this->createMap();
        echo '</div>';
        echo '</body></html>';
    }

    /**
     * @return mixed
     */
    private function createButtons() {
        $html = '';

        return $html;
    }

    /**
     * @param $door
     */
    private function createInWallCss($roomSize, $wall, $distance, $width) {
        switch ($wall) {
            case 'right':
                return [
                    'left'   => $roomSize[0] . 'px',
                    'top'    => $distance . 'px',
                    'width'  => $this->map['wall-width'] . 'px',
                    'height' => $width . 'px',
                ];
            case 'left':
                return [
                    'left'   => -$this->map['wall-width'] . 'px',
                    'top'    => $distance . 'px',
                    'width'  => $this->map['wall-width'] . 'px',
                    'height' => $width . 'px',
                ];
            case 'up':
                return [
                    'left'   => $distance . 'px',
                    'top'    => -$this->map['wall-width'] . 'px',
                    'width'  => $width . 'px',
                    'height' => $this->map['wall-width'] . 'px',
                ];
            case 'down':
                return [
                    'left'   => $distance . 'px',
                    'top'    => $roomSize[1] . 'px',
                    'width'  => $width . 'px',
                    'height' => $this->map['wall-width'] . 'px',
                ];
            default:
                echo '[ERROR] Invalid door setting "wall" = "' . $door['wall'] . '"';
                return [];
        }
    }

    /**
     * @return mixed
     */
    private function createMap() {
        $html = '';
        $html .= '<div class="mapContainer">';
        $html .= '<div class="map">' . $this->createRoomsHTML() . '</div>';
        $html .= '</div>';
        return $html;
    }

    /**
     * @return mixed
     */
    private function createRoomsCSS() {
        $css = [];
        foreach ($this->map['rooms'] AS $roomName => $room) {
            $roomClass = '.room.room' . $roomName;

            $css[$roomClass] = [
                'left'   => $room['position'][0] . 'px',
                'top'    => $room['position'][1] . 'px',
                'width'  => $room['size'][0] . 'px',
                'height' => $room['size'][1] . 'px',
            ];

            // DOORS
            if (isset($room['door'])) {
                foreach ($room['door'] AS $doorName => $door) {
                    $doorClass = $roomClass . ' .door.door' . $doorName;

                    $css[$doorClass] = $this->createInWallCss(
                        $room['size'],
                        $door['wall'],
                        $door['distance'],
                        $door['width']
                    );
                }
            }

            // WINDOWS
            if (isset($room['window'])) {
                foreach ($room['window'] AS $windowName => $window) {
                    $windowClass = $roomClass . ' .window.window' . $windowName;

                    $css[$windowClass] = $this->createInWallCss(
                        $room['size'],
                        $window['wall'],
                        $window['distance'],
                        $window['width']
                    );
                }
            }
        }
        return $this->cssToString($css);
    }

    /**
     * @return mixed
     */
    private function createRoomsHTML() {
        $html = '';
        foreach ($this->map['rooms'] AS $name => $room) {
            $class = 'room' . $name;

            $html .= '<div class="room ' . $class . '">';
            $html .= '<div class="roomContainer">';
            $html .= '<div class="name">' . $name . '</div>';
            if (isset($room['door'])) {
                foreach ($room['door'] AS $doorName => $door) {
                    $html .= '<div class="door door' . $doorName . '" objectName="' . $doorName . '"></div>';
                }
            }
            if (isset($room['window'])) {
                foreach ($room['window'] AS $windowName => $window) {
                    $html .= '<div class="window window' . $windowName . '" objectName="' . $windowName . '"></div>';
                }
            }
            $html .= '<div class="details">';
            $html .= '<span class="temperature"><span class="value"></span><span class="unit">°C</span></span>';
            $html .= '<span class="humidity"><span class="value"></span><span class="unit">%</span></span>';
            $html .= '<span class="pressure"><span class="value"></span><span class="unit">hPa</span></span>';
            $html .= '</div>';
            $html .= '<div class="dimLayer"></div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        return $html;
    }

    /**
     * @param $css
     */
    private function cssToString($css) {
        foreach ($css AS $class => $props) {
            foreach ($props AS $prop => $value) {
                $props[$prop] = $prop . ':' . $value;
            }
            $css[$class] = $class . '{' . implode(';', $props) . '}';
        }
        return implode(' ', $css);
    }
}
?>
