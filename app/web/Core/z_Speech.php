<?php

class Core_Speech {
    private $aliases = array(
        'action'  => array(
            'on'          => '/(wake|pornestel?o?|aprindel?o?|deschidel?o?)/',
            'off'         => '/(close|shutdown|power|oprestel?o?|stingel?o?|inchidel?o?)/',
            'toggle'      => '/(toggle)/',
            'status'      => '/(state|snapshot|statusu?l?|cum|care|pornita?|aprinsa?|deschisa?|oprita?|stinsa?|inchisa?)/',
            'fullstatus'  => '/(fullstate|fullstatus)/',
            'brightness'  => '/(luminozitatea?|brightness|intensitatea?)/',
            'temperature' => '/(temperatura)/',
            'sleeping'    => '/(noapte|somn|stingerea)/',
            'awake'       => '/(neata|dimineata|trezirea?)/',
        ),
        'objects' => array(
            'tv'   => '/(televizoru?l?|teveul?|tvu?l?)/',
            'bulb' => '/(lumina|luminii|becu?l?|soarele|lustra|veioza|lampa)/',
            'db'   => '/(db|world|bd)/',
        ),
        'params'  => array(
            'numbers' => '/([0-9]+)/',
        ),
    );

    /**
     * Full message as received
     *
     * @var string
     */
    private $message;

    /**
     * All the words of a message (processed)
     *
     * @var array
     */
    private $words;

    /**
     * Initialize and set the message
     *
     * @param string $message Spoken message
     */
    public function __construct($message) {
        $this->message = $message;
        $this->parseMessage();
    }

    public function getPossibleMeanings() {
        $events = array();

        foreach ($this->words AS $messageNb => $words) {
            $matches = array(
                'params' => array(),
            );
            foreach ($words AS $word) {
                foreach ($this->aliases AS $type => $subtypes) {
                    foreach ($subtypes AS $subtype => $regex) {
                        preg_match_all($regex, $word, $regexMatches);
                        if (count($regexMatches[0]) != 0) {
                            if ($type == 'params') {
                                $matches[$type][$subtype][] = $word;
                            } else {
                                $matches[$type][] = $subtype;
                            }
                        }
                    }
                }
            }

            if (!isset($matches['action']) || empty($matches['action'])) {
                $events[] = array(
                    'object' => 'message',
                    'action' => 'n-am înțeles ce vrei să fac',
                );
                continue;
            } elseif (count($matches['action']) != 1) {
                if (in_array('fullstatus', $matches['action']) && in_array('status', $matches['action'])) {
                    for ($i = 0; $i < count($matches['action']); $i++) {
                        if ($matches['action'][$i] == 'status') {
                            unset($matches['action'][$i]);
                            break;
                        }
                    }
                    $matches['action'] = array_values($matches['action']);
                }
                if (count($matches['action']) != 1) {
                    $events[] = array(
                        'object' => 'message',
                        'action' => 'am primit mai mult de două acțiuni',
                    );
                    continue;
                }
            }

            if ($matches['action'][0] == 'brightness' && !isset($matches['objects'])) {
                $matches['objects'][] = 'bulb';
            }

            if (in_array($matches['action'][0], array('sleeping', 'awake')) && !isset($matches['objects'])) {
                $matches['objects'][] = 'db';
            }

            if (!isset($matches['objects']) || empty($matches['objects'])) {
                $events[] = array(
                    'object' => 'message',
                    'action' => 'n-am înțeles la ce te referi',
                );
                continue;
            }

            foreach ($matches['objects'] AS $object) {
                $events[] = array(
                    'object' => $object,
                    'action' => $matches['action'][0],
                    'params' => $matches['params'],
                );
            }
        }

        return $events;
    }

    /**
     * Process the message
     */
    private function parseMessage() {

        // Lower all the chars
        $message = strtolower($this->message);

        // Transform chars
        $diacritice = array(
            'ă' => 'a',
            'Ă' => 'a',
            'î' => 'i',
            'Î' => 'i',
            'â' => 'a',
            'Â' => 'a',
            'ș' => 's',
            'Ș' => 's',
            'ț' => 't',
            'Ț' => 't',
        );
        $message = str_replace(array_keys($diacritice), array_values($diacritice), $message);

        // Remove strange chars
        $message = preg_replace('/[^a-zA-Z0-9\, ]/', '', $message);

        // Replace 'and' with comma
        $message = str_replace(' si ', ',', $message);

        // Remove consecutive spaces
        $message = preg_replace('/[ \t\n\r]+/', ' ', $message);

        // Remove spaces before/after comma
        $message = preg_replace('/ ?, ?/', ',', $message);

        // Trim string
        $message = trim($message);

        // Split into multiple commands
        $messages = explode(',', $message);

        // Split each command into words
        foreach ($messages AS $message) {
            $this->words[] = explode(' ', $message);
        }
    }
}
