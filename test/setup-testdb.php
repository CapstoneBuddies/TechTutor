<?php
/**
 * Test Database Setup Script
 * 
 * This script helps you set up your test database and verifies the connection.
 * Run this script to check your database configuration and add test users.
 */

echo "=== TechTutor Test Database Setup ===\n\n";

// Load configuration
require_once __DIR__ . '/../backends/config.php';

echo "Using database configuration:\n";
echo "- Host: " . DB_HOST . "\n";
echo "- User: " . DB_USER . "\n";
echo "- Database: " . DB_NAME . "\n";
echo "- Port: " . DB_PORT . "\n\n";

// Test database connection
echo "Testing database connection... ";
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
    
    if ($conn->connect_error) {
        echo "FAILED!\n";
        echo "Error: " . $conn->connect_error . "\n";
        exit(1);
    }
    
    echo "SUCCESS!\n\n";
} catch (Exception $e) {
    echo "FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Check if users table exists
echo "Checking users table... ";
$result = $conn->query("SHOW TABLES LIKE 'users'");

if ($result->num_rows === 0) {
    echo "NOT FOUND!\n";
    echo "You need to create the users table. Import your database schema first.\n";
    exit(1);
}

echo "FOUND!\n\n";

// Ask if user wants to add test users
echo "Do you want to add test users to the database? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));

if (strtolower($line) === 'y') {
    echo "\nAdding test users...\n";
    
    // Define test users
    $testUsers = [
        [
            'email' => 'tutor@test.com',
            'password' => password_hash('Abc123', PASSWORD_DEFAULT),
            'first_name' => 'Tech',
            'last_name' => 'Guru',
            'role' => 'TECHGURU'
        ],
        [
            'email' => 'student@test.com',
            'password' => password_hash('Abc123', PASSWORD_DEFAULT),
            'first_name' => 'Tech',
            'last_name' => 'Kid',
            'role' => 'TECHKID'
        ],
        [
            'email' => 'admin@test.com',
            'password' => password_hash('Abc123', PASSWORD_DEFAULT),
            'first_name' => 'Admin',
            'last_name' => 'User',
            'role' => 'ADMIN'
        ]
    ];
    
    foreach ($testUsers as $user) {
        // Check if user already exists
        $stmt = $conn->prepare("SELECT uid FROM users WHERE email = ?");
        $stmt->bind_param("s", $user['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo "User {$user['email']} already exists, skipping...\n";
            $stmt->close();
            continue;
        }
        
        $stmt->close();
        
        // Insert the user
        $stmt = $conn->prepare("INSERT INTO users (email, password, first_name, last_name, role, is_verified, status) VALUES (?, ?, ?, ?, ?, 1, 1)");
        $stmt->bind_param("sssss", $user['email'], $user['password'], $user['first_name'], $user['last_name'], $user['role']);
        
        if ($stmt->execute()) {
            echo "Added user: {$user['email']} with role {$user['role']}\n";
        } else {
            echo "Failed to add user {$user['email']}: " . $stmt->error . "\n";
        }
        
        $stmt->close();
    }
    
    echo "\nTest users have been added successfully!\n";
} else {
    echo "\nSkipping test user creation.\n";
}

// Verify database structure for testing
echo "\nVerifying database structure for testing...\n";

$requiredTables = ['users', 'transactions', 'course'];
$missingTables = [];

foreach ($requiredTables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows === 0) {
        $missingTables[] = $table;
    } else {
        echo "- Table '$table' exists.\n";
    }
}

if (!empty($missingTables)) {
    echo "\nWARNING: The following tables are missing: " . implode(', ', $missingTables) . "\n";
    echo "You may encounter test failures if your tests depend on these tables.\n";
} else {
    echo "\nAll required tables exist!\n";
}

// Report table structure for users table
echo "\nUsers table structure:\n";
$result = $conn->query("DESCRIBE users");
while ($row = $result->fetch_assoc()) {
    echo "- {$row['Field']}: {$row['Type']}" . ($row['Null'] === 'NO' ? ' (required)' : '') . "\n";
}

// Close the connection
$conn->close();

echo "\n=== Setup Complete ===\n";
echo "You can now run your tests with:\n";
echo "php ../assets/vendor/bin/phpunit -c phpunit.xml\n";