<?php

// Load autoloader
require "autoloader.php";

// Core_Logger::warn('$_GET = '.print_r($_GET,1));
// Core_Logger::warn('$_SERVER = '.print_r($_SERVER,1));
// Core_Logger::warn('$_POST = '.print_r($_POST,1));

// Initialize app
$app = new Core_Controller_Incoming;

// Launch app
$app->run();
