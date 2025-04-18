<?php
/**
 * Test Database Configuration
 * This file contains configuration specific to the test environment
 */

// Test Database Credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Change if using different MySQL credentials
define('DB_PASSWORD', '');     // Change if using different MySQL credentials
define('DB_NAME', 'techtutor_test');

// Other test-specific configuration
define('APP_ENV', 'testing');
define('TEST_MODE', true);

// Credentials for test users
define('TEST_TECHGURU_EMAIL', 'tutor@test.com');
define('TEST_TECHGURU_PASSWORD', 'Abc123');

define('TEST_TECHKID_EMAIL', 'student@test.com');
define('TEST_TECHKID_PASSWORD', 'Abc123');

define('TEST_ADMIN_EMAIL', 'admin@test.com');
define('TEST_ADMIN_PASSWORD', 'Abc123');