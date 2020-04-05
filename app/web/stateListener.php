<?php

// Load autoloader
require "autoloader.php";

// Initialize app
$app = new UI_Controller_Ajax_StateListener;

// Launch app
$app->run();
