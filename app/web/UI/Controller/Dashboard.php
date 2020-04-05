<?php

class UI_Controller_Dashboard extends Core_Controller_Base {
    /**
     * @var array
     */
    private $map = [
        'url'   => '/res/img/house-plan.png',
        'size'  => [556, 425], // width, height
        'rooms' => [
            'Bedroom'    => [
                'position' => [6, 6],     // x, y
                'size'     => [242, 282], // width, height
            ],
            'Livingroom' => [
                'position' => [255, 6],   // x, y
                'size'     => [295, 185], // width, height
            ],
            'Kitchen'    => [
                'position' => [6, 295],   // x, y
                'size'     => [242, 124], // width, height
            ],
            'Bathroom'   => [
                'position' => [348, 197], // x, y
                'size'     => [202, 135], // width, height
            ],
            'Hallway'    => [
                'position' => [255, 197], // x, y
                'size'     => [87, 222],  // width, height
            ],
            'Entrance'   => [
                'position' => [342, 338], // x, y
                'size'     => [209, 81],  // width, height
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
        echo $this->createMap();
        echo '</body></html>';
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
        foreach ($this->map['rooms'] AS $name => $room) {
            $class = '.room.room' . $name;

            $css[$class . ''] = [
                'left'   => $room['position'][0] . 'px',
                'top'    => $room['position'][1] . 'px',
                'width'  => $room['size'][0] . 'px',
                'height' => $room['size'][1] . 'px',
            ];
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
            $html .= '<div class="name">' . $name . '</div>';
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
