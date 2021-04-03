<?php

// Load autoloader
require "autoloader.php";

// Initialize app
$app = new Core_Controller_MqttListener;

// Launch app
$app->run();
