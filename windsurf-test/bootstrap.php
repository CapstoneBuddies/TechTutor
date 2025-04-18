<?php
/**
 * Bootstrap file for PHPUnit tests
 * Sets up the testing environment with proper includes and configurations
 */

// Define the application root path for tests
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}
if (!defined('TEST_ROOT')) {
    define('TEST_ROOT', __DIR__);
}

// Include composer autoloader
require_once APP_ROOT . '/assets/vendor/autoload.php';

// Ensure sessions work properly in test environment
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include main application files needed for testing
require_once APP_ROOT . '/backends/config.php';
require_once APP_ROOT . '/backends/db.php';
require_once APP_ROOT . '/backends/main.php';

// Create mock connection and functions for testing if needed
class MockDB {
    public static $mockResponses = [];
    
    public static function setMockResponse($function, $args, $response) {
        self::$mockResponses[$function][serialize($args)] = $response;
    }
    
    public static function getMockResponse($function, $args) {
        if (isset(self::$mockResponses[$function][serialize($args)])) {
            return self::$mockResponses[$function][serialize($args)];
        }
        return null;
    }
    
    public static function clearMockResponses() {
        self::$mockResponses = [];
    }
}

// Set up test credentials
if (!defined('TEST_ADMIN_EMAIL')) {
    define('TEST_ADMIN_EMAIL', 'admin@test.com');
}
if (!defined('TEST_TECHGURU_EMAIL')) {
    define('TEST_TECHGURU_EMAIL', 'tutor@test.com');
}
if (!defined('TEST_TECHKID_EMAIL')) {
    define('TEST_TECHKID_EMAIL', 'student@test.com');
}
if (!defined('TEST_PASSWORD')) {
    define('TEST_PASSWORD', 'Abc123!!');
}

// Function to create test tables or reset test data if needed
function setupTestDatabase() {
    global $conn;
    
    // Use test database connection if available
    // In a real environment, you would use a separate test database
}

// Helper function to create a test user session
function createTestUserSession($email, $password) {
    $_POST['email'] = $email;
    $_POST['password'] = $password;
    $_POST['login'] = 'Login';
    
    // Call login function - we're intentionally triggering the real function to test integration
    login();
}

// Cleanup function to run after tests
function cleanupTests() {
    // Clear any test data or sessions
    session_unset();
    session_destroy();
    MockDB::clearMockResponses();
}
