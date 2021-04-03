<?php

class Core_Controller_MqttListener extends Core_Controller_Base {
  /**
   * Watch the MQTT feed and simulate incoming requests
   */
  public function run() {

    $cmd = "mosquitto_sub -h mqtt -v -t '#'";

    $descriptorspec = [
      0 => ["pipe", "r"], // stdin is a pipe that the child will read from
      1 => ["pipe", "w"], // stdout is a pipe that the child will write to
      2 => ["pipe", "w"], // stderr is a pipe that the child will write to
    ];

    // Try launching the process
    do {
      // Launch process
      $process = proc_open($cmd, $descriptorspec, $pipes, realpath('./'), []);

      // Stop if we can't
      if (!is_resource($process)) {
        Core_Logger::warn('Core_Controller_MqttListener::run(): Couldn\'t launch mosquitto_sub process with proc_open()! Sleeping 5 seconds...');
        sleep(5);
      }
    } while (!is_resource($process));

    // Skip the first line which is not recevied from devices
    fgets($pipes[1]);

    // Whenever a new line is fetched
    while ($line = fgets($pipes[1])) {

      // Show line
      Core_Logger::info('MQTT Received: ' . trim($line));

      // Split line by the first space
      $tmp = explode(' ', $line, 2);
      if (!isset($tmp[1])) {
        Core_Logger::warn('Got invalid line: ' . trim($line));
        continue;
      }

      // Get topic & json from the received line
      $topic = $tmp[0];
      if (substr($topic, 0, 5) == 'core-') {
        continue;
      }
      $json = json_decode($tmp[1], true);

      if (!$json || $json == $tmp[1]) {
        $json = [];
      }
      $json['RAW'] = trim($tmp[1]);

      // Set the variables required by the request handler
      $_GET = array_merge([
        'action'     => 'mqtt-loopback',
        'mqtt-topic' => $topic,
      ], $json);

      // Initialize incoming handler
      $incomingHandler = new Core_Controller_Incoming;

      // Mark functions to run asynchronously
      $incomingHandler->shouldRunFunctionsAsync(true);

      // Recognize request and run
      $incomingHandler->run();

      // Delete object
      $incomingHandler = null;
      unset($incomingHandler);
    }

    Core_Logger::info('Stopped listening to MQTT');
  }
}