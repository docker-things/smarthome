<?php

// Load autoloader
require "autoloader.php";

// Initialize app
$app = new Core_Controller_RunFunctions($argv);

// Launch app
$app->run();
