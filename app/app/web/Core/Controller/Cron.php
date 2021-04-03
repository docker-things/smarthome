<?php

class Core_Controller_Cron extends Core_Controller_Base {
  /**
   * Process the cron jobs
   */
  public function run() {

    // Get the jobs from objects which implement cron
    $objectCronJobs = $this->_getObjectCronJobs();

    // Get the user defined jobs
    $userCronJobs = $this->_getUserCronJobs();

    // Merge jobs
    $cronJobs = array_merge($objectCronJobs, $userCronJobs);

    // If no valid job found
    if (empty($cronJobs)) {
      Core_Logger::info(get_class($this) . ': No cron job found.');
      return;
    }

    // Process valid jobs found
    $preProcessedJobs = $this->_preProcessJobs($cronJobs);

    // Launch jobs loop
    $this->_launchJobsLoop($preProcessedJobs);
  }

  /**
   * @param  $job
   * @return mixed
   */
  private function _checkJobConditions($job) {

    // Initialize conditions checking object
    $conditions = new Core_Conditions($this);

    // Set object name instead of 'this'
    if (isset($job['objectName'])) {
      $conditions->replaceThisWithObjectName('this', $job['objectName']);
    }

    // Will keep all the valid triggers
    $validTriggers = [];

    // If there's no condition assume we always run this job
    if (!isset($job['if'])) {
      $check = true;
    }

    // Otherwise check if the conditions are valid
    else {
      $check = $conditions->check($job['if']);
    }

    return $check;
  }

  /**
   * @return array
   */
  private function _getObjectCronJobs() {

    // Get objects which have cron definitions
    $objectNames = $this->getConfig()->getObjectsWhichHave('Cron');

    $jobs = [];

    // For each object
    foreach ($objectNames AS $objectName) {

      // Get the actual jobs
      $objectCronJobs = $this->getConfig()->getObjectCronJobs($objectName);

      // If we have jobs
      if (!empty($objectCronJobs)) {

        // Append them
        $jobs = array_merge($jobs, $objectCronJobs);
      }
    }

    return $jobs;
  }

  /**
   * @return array
   */
  private function _getUserCronJobs() {
    return $this->getConfig()->getUserCronJobs();
  }

  /**
   * @param $jobs
   */
  private function _launchJobsLoop($jobs) {

    // Number of jobs registered
    $jobsCount = count($jobs);

    // Loop until we have to reload the config
    while (!$this->_shouldReloadConfig()) {

      // Wait 1 second
      sleep(1);

      // For each job
      for ($i = 0; $i < $jobsCount; $i++) {

        // Decrement passed seconds
        $jobs[$i]['secondsUntilRun']--;

        // Core_Logger::debug('Job[' . $i . '][secondsUntilRun] = ' . $jobs[$i]['secondsUntilRun']);

        // Skip ff it's still not the time to run job
        if (0 != $jobs[$i]['secondsUntilRun']) {
          continue;
        }

        // Reset countdown
        $jobs[$i]['secondsUntilRun'] = $jobs[$i]['interval'];

        // Reload state before running anything
        $this->getState()->reload();

        // Check job conditions
        if (!$this->_checkJobConditions($jobs[$i])) {
          continue;
        }

        // Set new states
        $this->_setNewStates($jobs[$i]);

        // Launch functions
        if (isset($jobs[$i]['run'])) {
          $this->_runAsyncFunctions($jobs[$i]['run']);
        }
      }
    }
  }

  /**
   * @param $jobs
   */
  private function _preProcessJobs($jobs) {

    $processedJobs = [];

    foreach ($jobs AS $job) {
      $job['secondsUntilRun'] = $job['interval'];
      $processedJobs[]        = $job;
    }

    return $processedJobs;
  }

  /**
   * @param $functions
   */
  private function _runAsyncFunctions($functions) {
    $args = [];
    foreach ($functions AS $function) {
      $args[] = urlencode($function);
    }

    Core_Logger::info('Launching: ' . implode('; ', $functions));
    $cmd = "mosquitto_pub -h mqtt -t 'core-function/run' -m '" . implode(";;", $args) . "'";
    ob_start();
    system($cmd . ' 2>&1 &', $retval);
    ob_end_clean();
    // exec("php web/runFunctions.php '" . implode("' '", $args) . "' > /dev/null 2>&1 &");
  }

  /**
   * @param $where
   * @param $value
   */
  private function _setNewState($where, $value) {
    // Core_Logger::info('Core_Cron::_setNewState("' . $where . '", "' . $value . '");');

    $where = explode('.', $where, 2);
    if (!isset($where[1])) {
      Core_Logger::error('Core_Cron::_setNewState("' . $where . '", "' . $value . '"): INVALID $where PARAM!');
      return false;
    }

    Core_Logger::info('Setting: ' . $where[0] . '.' . $where[1] . ' = ' . $value);
    $this->getState()->set($where[0], $where[1], $value);
  }

  /**
   * @param $trigger
   */
  private function _setNewStates($trigger) {
    // Core_Logger::info('Core_Cron::_setNewStates();');

    // Skip trigger if there's nothing to set
    if (!isset($trigger['set'])) {
      return;
    }

    // Set each variable
    foreach ($trigger['set'] AS $where => $value) {
      $this->_setNewState($where, $value);
    }
  }

  private function _shouldReloadConfig() {
    return false;
  }
}