<?php

// Load autoloader
require "autoloader.php";

// Initialize app
$app = new Core_Controller_ClearDB;

// Launch app
$app->run();
