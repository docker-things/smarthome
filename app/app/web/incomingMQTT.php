<?php

// Load autoloader
require "autoloader.php";

for ($i = 1; $i < count($argv); $i++) {
  $_GET = json_decode(urldecode($argv[$i]), true);

  // Initialize app
  $app = new Core_Controller_Incoming;

  // Mark functions to run asynchronously
  $app->shouldRunFunctionsAsync(true);

  // Launch app
  $app->run();
}
