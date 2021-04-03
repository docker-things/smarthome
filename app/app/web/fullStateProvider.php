<?php

// Load autoloader
require "autoloader.php";

// Initialize app
$app = new Core_Controller_FullStateProvider;

// Launch app
$app->run();
