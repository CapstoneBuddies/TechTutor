<?php
/**
 * Unit Testing Bootstrap File
 * 
 * This file is executed before running unit tests to set up the testing environment.
 */

// Autoload dependencies
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables for testing
$dotenv = new \Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

// Set testing specific constants
define('TESTING', true);
define('TEST_ROOT', __DIR__);
define('APP_ROOT', dirname(__DIR__));

// Initialize database for testing
require_once __DIR__ . '/init-test-database.php';

// Mock global functions or variables if needed
require_once __DIR__ . '/mock-functions.php';

// Setup error handling for tests
error_reporting(E_ALL);
ini_set('display_errors', 1);