<?php
/**
 * Test Database Setup Script
 * 
 * This script helps set up your test database and add the required test users.
 * Run this script from the command line before running your tests:
 * 
 * php test/setup-database.php
 */

echo "==== TechTutor Test Database Setup ====\n\n";

// Include the database configuration
require_once __DIR__ . '/../backends/config.php';

echo "Using database configuration:\n";
echo "- Host: " . DB_HOST . "\n";
echo "- Database: " . DB_NAME . "\n";
echo "- User: " . DB_USER . "\n";
echo "- Port: " . DB_PORT . "\n\n";

// Attempt to connect to the database
echo "Connecting to database... ";
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

if ($result->num_rows == 0) {
    echo "NOT FOUND!\n";
    echo "You need to create the users table before running this script.\n";
    echo "Please import your database schema first.\n";
    exit(1);
}

echo "FOUND!\n\n";

// Check table structure
echo "Checking users table structure... ";
$result = $conn->query("DESCRIBE users");

$requiredFields = ['uid', 'email', 'password', 'role', 'first_name', 'last_name'];
$fieldsFound = [];

while ($row = $result->fetch_assoc()) {
    $fieldsFound[] = $row['Field'];
}

$missingFields = array_diff($requiredFields, $fieldsFound);

if (!empty($missingFields)) {
    echo "INCOMPLETE!\n";
    echo "The following required fields are missing: " . implode(', ', $missingFields) . "\n";
    echo "Please ensure your users table has the correct structure.\n";
    exit(1);
}

echo "OK!\n\n";

// Add test users
echo "Adding test users...\n";

$testUsers = [
    [
        'email' => 'tutor@test.com',
        'password' => 'Abc123',
        'first_name' => 'Tech',
        'last_name' => 'Guru',
        'role' => 'TECHGURU'
    ],
    [
        'email' => 'student@test.com',
        'password' => 'Abc123',
        'first_name' => 'Tech',
        'last_name' => 'Kid',
        'role' => 'TECHKID'
    ],
    [
        'email' => 'admin@test.com',
        'password' => 'Abc123',
        'first_name' => 'Admin',
        'last_name' => 'User',
        'role' => 'ADMIN'
    ]
];

foreach ($testUsers as $user) {
    echo "- Processing user {$user['email']}... ";
    
    // Check if user already exists
    $stmt = $conn->prepare("SELECT uid FROM users WHERE email = ?");
    $stmt->bind_param("s", $user['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "ALREADY EXISTS\n";
        $stmt->close();
        continue;
    }
    
    $stmt->close();
    
    // Hash the password
    $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
    
    // Insert the user
    $stmt = $conn->prepare("
        INSERT INTO users (email, password, first_name, last_name, role, is_verified, status) 
        VALUES (?, ?, ?, ?, ?, 1, 1)
    ");
    
    $stmt->bind_param("sssss", 
        $user['email'], 
        $hashedPassword, 
        $user['first_name'], 
        $user['last_name'], 
        $user['role']
    );
    
    if ($stmt->execute()) {
        echo "ADDED\n";
    } else {
        echo "FAILED: " . $stmt->error . "\n";
    }
    
    $stmt->close();
}

echo "\nVerifying test users...\n";

foreach ($testUsers as $user) {
    echo "- Checking {$user['email']}... ";
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $user['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $dbUser = $result->fetch_assoc();
        echo "FOUND (ID: {$dbUser['uid']})\n";
        
        // Verify password
        if (password_verify($user['password'], $dbUser['password'])) {
            echo "  - Password verification: SUCCESS\n";
        } else {
            echo "  - Password verification: FAILED\n";
        }
        
        // Verify role
        if ($dbUser['role'] == $user['role']) {
            echo "  - Role verification: SUCCESS ({$dbUser['role']})\n";
        } else {
            echo "  - Role verification: FAILED (Expected: {$user['role']}, Found: {$dbUser['role']})\n";
        }
    } else {
        echo "NOT FOUND\n";
    }
    
    $stmt->close();
}

// Close the connection
$conn->close();

echo "\n==== Setup Complete ====\n";
echo "You can now run your tests using:\n";
echo "php assets/vendor/bin/phpunit -c test/phpunit.xml\n";