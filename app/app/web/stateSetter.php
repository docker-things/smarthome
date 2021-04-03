<?php

// Load autoloader
require "autoloader.php";

// Initialize app
$app = new Core_Controller_StateSetter;

// Launch app
$app->run();
