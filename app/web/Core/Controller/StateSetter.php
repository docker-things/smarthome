<?php

class Core_Controller_StateSetter extends Core_Controller_Base {
  /**
   * Watch the MQTT feed and simulate incoming requests
   */
  public function run() {

    $cmd = "mosquitto_sub -h localhost -t 'core-state/set'";

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
        Core_Logger::warn('Core_Controller_StateSetter::run(): Couldn\'t launch mosquitto_sub process with proc_open()! Sleeping 5 seconds...');
        sleep(5);
      }
    } while (!is_resource($process));

    // Whenever a new line is fetched
    while ($line = fgets($pipes[1])) {

      // Show line
      Core_Logger::info('Received: ' . trim($line));

      // Decode json
      $json = json_decode($line, true);

      // If got invalid json
      if (!$json || !isset($json['source']) || !isset($json['name']) || !isset($json['value'])) {
        Core_Logger::warn('Got invalid JSON!');
        continue;
      }

      // Reload current state
      $this->getState()->reload();

      // Set new value
      $this->getState()->set($json['source'], $json['name'], $json['value']);
    }

    Core_Logger::info('Stopped listening to MQTT');
  }
}