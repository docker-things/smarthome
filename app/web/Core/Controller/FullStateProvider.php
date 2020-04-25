<?php

class Core_Controller_FullStateProvider extends Core_Controller_Base {
  /**
   * Watch the MQTT feed and simulate incoming requests
   */
  public function run() {

    $cmd = "mosquitto_sub -h localhost -v -t 'core-state/full-state-request'";

    $descriptorspec = [
      0 => ["pipe", "r"], // stdin is a pipe that the child will read from
      1 => ["pipe", "w"], // stdout is a pipe that the child will write to
      2 => ["pipe", "w"], // stderr is a pipe that the child will write to
    ];

    Core_Logger::info('Start listening to MQTT');

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

    Core_Logger::info('Waiting requests...');

    // Whenever a new line is fetched
    while ($line = fgets($pipes[1])) {

      Core_Logger::info('Got request');

      // Reload the state from DB
      $this->getState()->reload();

      // Get the new state
      $state = $this->getState()->getFullState();

      // Batch data
      $batches   = [];
      $batchSize = 30;
      $paramsBatchSize = 500;

      // While we've got state objects
      while (!empty($state)) {

        // Init batch
        $batch = [];

        // For each object
        foreach (array_keys($state) AS $key) {

          // If object has more than $batchSize props split it
          if (count($state[$key]) > $batchSize) {
            $subBatch = [];
            foreach (array_keys($state[$key]) AS $subKey) {
              $subBatch[$subKey] = $state[$key][$subKey];
              unset($state[$key][$subKey]);

              if (count($subBatch) == $paramsBatchSize) {
                $batches[] = [$key => $subBatch];
                $subBatch  = [];
              }
            }

            if (count($subBatch) != 0) {
              $batches[] = [$key => $subBatch];
            }
          }
          // Otherwise add data to batch
          else {
            $batch[$key] = $state[$key];
          }

          // Remove data from state
          unset($state[$key]);

          // Append batch every $batchSize objects
          if (count($batch) == $batchSize) {
            $batches[] = $batch;
            break;
          }
        }
      }

      // If we've got an unappended batch
      if (!empty($batch)) {
        $batches[] = $batch;
      }

      // Send batches
      foreach ($batches AS $batch) {
        $this->_send($batch);
      }

      // Send everything else
      $this->_send($state);
    }

    Core_Logger::info('Stopped listening to MQTT');
  }

  /**
   * @param $state
   */
  private function _send($state) {
    if (empty($state)) {
      return;
    }
    Core_Logger::info('Sending batch [' . count($state) . ']: ' . implode(', ', array_keys($state)));
    $cmd = "mosquitto_pub -h localhost -t 'core-state/full-state-provider' -m \"" . str_replace('"', '\"', json_encode($state)) . "\"";
    // file_put_contents('/app/data/dump', $cmd);
    // ob_start();
    system($cmd . ' 2>&1 &', $retval);
    // ob_end_clean();
  }
}