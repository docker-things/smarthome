<?php

// Make sure we get all the errors
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Define paths
define('MAIN_DIR', '/app');
define('DB_DIR', MAIN_DIR . '/data/db');
define('WEB_DIR', MAIN_DIR . '/web');
define('CONFIG_DIR', MAIN_DIR . '/data/config');

/**
 * @param $class
 */
function classExists($class): bool {
    return file_exists(WEB_DIR . '/' . str_replace('_', '/', $class) . '.php');
}

/**
 * @param $class
 */
function defines__autoload($class): void {
    if (classExists($class)) {
        require_once WEB_DIR . '/' . str_replace('_', '/', $class) . '.php';
    }
}

// Register autoloader
spl_autoload_register('defines__autoload');
