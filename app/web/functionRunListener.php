<?php

// Load autoloader
require "autoloader.php";

// Initialize app
$app = new Core_Controller_FunctionRunListener;

// Launch app
$app->run();
