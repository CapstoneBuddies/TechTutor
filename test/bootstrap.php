<?php
/**
 * Bootstrap file for PHPUnit tests
 * This file is loaded before running the tests to set up the environment
 */

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Include necessary configuration files
require_once BASE_PATH . '/backends/config.php';
require_once BASE_PATH . '/backends/db.php';

// Set testing environment
putenv("APP_ENV=testing");

// Helper function to create a test database connection
function getTestDbConnection() {
    // You might want to use a different database for testing
    // or reset your test database to a known state
    global $conn;
    return $conn;
}

// Helper function to load test fixtures
function loadFixture($fixtureName) {
    $fixturePath = BASE_PATH . '/test/fixtures/' . $fixtureName . '.php';
    if (file_exists($fixturePath)) {
        return include $fixturePath;
    }
    throw new Exception("Fixture not found: $fixtureName");
}

// Set up autoloading for classes
spl_autoload_register(function($className) {
    // Convert namespace to file path if using namespaces
    $classFile = str_replace('\\', '/', $className) . '.php';
    
    // Search in common class directories
    $directories = [
        BASE_PATH . '/backends/',
        BASE_PATH . '/components/',
        BASE_PATH . '/test/mocks/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $classFile;
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    
    return false;
});

// Initialize any required test environment variables
date_default_timezone_set('UTC');