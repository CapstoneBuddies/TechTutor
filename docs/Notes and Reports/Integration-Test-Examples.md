# TechTutor Integration Test Implementation Examples

This document provides concrete examples of how to implement the integration tests outlined in the Integration Testing Plan using PHPUnit.

## Setting Up PHPUnit for TechTutor

### 1. Install PHPUnit

Add PHPUnit to your project using Composer:

```bash
composer require --dev phpunit/phpunit ^9.5
```

### 2. Create PHPUnit Configuration

Create a `phpunit.xml` file in the project root:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php"
         colors="true"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_NAME" value="techtutor_test"/>
    </php>
</phpunit>
```

### 3. Create Bootstrap File

Create `tests/bootstrap.php`:

```php
<?php
// tests/bootstrap.php

// Set up test environment
define('TESTING', true);
define('ROOT_PATH', realpath(__DIR__ . '/..'));
require_once ROOT_PATH . '/backends/config.php';

// Create a function to reset the database to a known state
function resetTestDatabase() {
    global $conn;
    // Import the test database schema
    $sql = file_get_contents(ROOT_PATH . '/tests/fixtures/test_database.sql');
    $conn->multi_query($sql);
    
    // Clear all results to prevent "Commands out of sync" error
    while ($conn->more_results() && $conn->next_result()) {
        $discard = $conn->use_result();
        if ($discard) $discard->free();
    }
}
```

## Example Integration Test Cases

### 1. User Authentication Flow Test

```php
<?php
// tests/Integration/UserAuthenticationTest.php

use PHPUnit\Framework\TestCase;

class UserAuthenticationTest extends TestCase
{
    private static $conn;
    
    public static function setUpBeforeClass(): void
    {
        global $conn;
        self::$conn = $conn;
        resetTestDatabase();
    }
    
    protected function setUp(): void
    {
        // Start a new session for each test
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_start();
    }
    
    /**
     * @test
     * @group integration
     * @covers ::register
     */
    public function testUserRegistrationToVerification()
    {
        // Simulate form submission
        $_POST['register'] = true;
        $_POST['email'] = 'test_user@example.com';
        $_POST['first-name'] = 'Test';
        $_POST['last-name'] = 'User';
        $_POST['password'] = 'Password123!';
        $_POST['confirm-password'] = 'Password123!';
        $_POST['role'] = 'TECHKID';
        
        // Call registration function
        register();
        
        // Assert user was created in database
        $stmt = self::$conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $_POST['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        $this->assertNotNull($user);
        $this->assertEquals($_POST['first-name'], $user['first_name']);
        $this->assertEquals($_POST['last-name'], $user['last_name']);
        $this->assertEquals(0, $user['is_verified']); // Should be unverified initially
        
        // Assert a token was created for verification
        $stmt = self::$conn->prepare("SELECT * FROM login_tokens WHERE user_id = ? AND type = 'email_verification'");
        $stmt->bind_param("i", $user['uid']);
        $stmt->execute();
        $result = $stmt->get_result();
        $token = $result->fetch_assoc();
        
        $this->assertNotNull($token);
        
        // Simulate verification process
        $_GET['token'] = $token['token'];
        include ROOT_PATH . '/pages/verify.php'; // This should process the verification
        
        // Check if user is now verified
        $stmt = self::$conn->prepare("SELECT is_verified FROM users WHERE email = ?");
        $stmt->bind_param("s", $_POST['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        $updated_user = $result->fetch_assoc();
        
        $this->assertEquals(1, $updated_user['is_verified']);
    }
    
    /**
     * @test
     * @group integration
     * @covers ::login
     */
    public function testLoginWithRememberMe()
    {
        // Arrange: Create a test user with known credentials
        $email = 'remember_me_test@example.com';
        $password = 'Password123!';
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = self::$conn->prepare("INSERT INTO users (email, password, first_name, last_name, role, is_verified, status) 
                                     VALUES (?, ?, 'Remember', 'Test', 'TECHKID', 1, 1)");
        $stmt->bind_param("ss", $email, $hashed_password);
        $stmt->execute();
        $user_id = self::$conn->insert_id;
        
        // Act: Login with remember me
        $_POST['email'] = $email;
        $_POST['password'] = $password;
        $_POST['remember'] = 'on';
        
        login();
        
        // Get the remember_me cookie
        $this->assertArrayHasKey('remember_me', $_COOKIE);
        $token = $_COOKIE['remember_me'];
        
        // Clear session to simulate browser restart
        session_destroy();
        session_start();
        unset($_SESSION['user']);
        
        // Assert: Token exists in database
        $stmt = self::$conn->prepare("SELECT * FROM login_tokens WHERE user_id = ? AND type = 'remember_me'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $token_record = $result->fetch_assoc();
        
        $this->assertNotNull($token_record);
        
        // Simulate coming back to the site with the cookie
        $_COOKIE['remember_me'] = $token;
        
        // This should process auto-login
        include ROOT_PATH . '/backends/main.php';
        
        // Verify user is logged in automatically
        $this->assertEquals($user_id, $_SESSION['user']);
    }
}
```

### 2. Class Management Flow Test

```php
<?php
// tests/Integration/ClassManagementTest.php

use PHPUnit\Framework\TestCase;

class ClassManagementTest extends TestCase
{
    private static $conn;
    private static $tutor_id;
    private static $student_id;
    private static $subject_id;
    
    public static function setUpBeforeClass(): void
    {
        global $conn;
        self::$conn = $conn;
        resetTestDatabase();
        
        // Create test users and subject
        $stmt = $conn->prepare("INSERT INTO users (email, password, first_name, last_name, role, is_verified, status) 
                               VALUES ('test_tutor@example.com', ?, 'Test', 'Tutor', 'TECHGURU', 1, 1)");
        $password = password_hash('Password123!', PASSWORD_DEFAULT);
        $stmt->bind_param("s", $password);
        $stmt->execute();
        self::$tutor_id = $conn->insert_id;
        
        $stmt = $conn->prepare("INSERT INTO users (email, password, first_name, last_name, role, is_verified, status) 
                               VALUES ('test_student@example.com', ?, 'Test', 'Student', 'TECHKID', 1, 1)");
        $stmt->bind_param("s", $password);
        $stmt->execute();
        self::$student_id = $conn->insert_id;
        
        $stmt = $conn->prepare("INSERT INTO course (course_name, course_desc) VALUES ('Test Course', 'Test Course Description')");
        $stmt->execute();
        $course_id = $conn->insert_id;
        
        $stmt = $conn->prepare("INSERT INTO subject (course_id, subject_name, subject_desc, is_active) 
                               VALUES (?, 'Test Subject', 'Test Subject Description', 1)");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        self::$subject_id = $conn->insert_id;
    }
    
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_start();
        $_SESSION['user'] = self::$tutor_id;
        $_SESSION['role'] = 'TECHGURU';
    }
    
    /**
     * @test
     * @group integration
     * @covers ::createClass
     * @covers ::getClassDetails
     * @covers ::enrollStudent
     */
    public function testCreateClassAndEnrollStudent()
    {
        // Arrange: Set up class data
        $class_data = [
            'subject_id' => self::$subject_id,
            'class_name' => 'Test Integration Class',
            'class_desc' => 'This is a test class for integration testing',
            'tutor_id' => self::$tutor_id,
            'start_date' => date('Y-m-d', strtotime('+1 day')),
            'end_date' => date('Y-m-d', strtotime('+30 days')),
            'days' => ['Monday', 'Wednesday', 'Friday'],
            'time_slots' => [
                [
                    'start_time' => '09:00:00',
                    'end_time' => '10:00:00'
                ]
            ],
            'class_size' => 10,
            'is_free' => 1
        ];
        
        // Act: Create the class
        $result = createClass($class_data);
        
        // Assert: Class was created successfully
        $this->assertTrue($result['success']);
        $class_id = $result['class_id'];
        
        // Get class details
        $class = getClassDetails($class_id, self::$tutor_id);
        
        $this->assertEquals($class_data['class_name'], $class['class_name']);
        $this->assertEquals($class_data['class_desc'], $class['class_desc']);
        $this->assertEquals('active', $class['status']);
        
        // Check schedules were created
        $schedules = getClassSchedules($class_id);
        $this->assertNotEmpty($schedules);
        
        // Now test enrollment as student
        $_SESSION['user'] = self::$student_id;
        $_SESSION['role'] = 'TECHKID';
        
        $enrollment_result = enrollStudent(self::$student_id, $class_id);
        $this->assertTrue($enrollment_result['success']);
        
        // Verify enrollment records
        $stmt = self::$conn->prepare("SELECT * FROM enrollments WHERE class_id = ? AND student_id = ?");
        $stmt->bind_param("ii", $class_id, self::$student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $enrollment = $result->fetch_assoc();
        
        $this->assertNotNull($enrollment);
        $this->assertEquals('active', $enrollment['status']);
        
        // Verify notification was created for tutor
        $stmt = self::$conn->prepare("SELECT * FROM notifications WHERE recipient_role = 'TECHGURU' AND user_id = ? AND target_id = ?");
        $stmt->bind_param("ii", self::$tutor_id, $class_id);
        $stmt->execute();
        $notification = $stmt->get_result()->fetch_assoc();
        
        $this->assertNotNull($notification);
    }
}
```

### 3. File Management Integration Test

```php
<?php
// tests/Integration/FileManagementTest.php

use PHPUnit\Framework\TestCase;

class FileManagementTest extends TestCase
{
    private static $conn;
    private static $tutor_id;
    private static $student_id;
    private static $class_id;
    
    public static function setUpBeforeClass(): void
    {
        global $conn;
        self::$conn = $conn;
        resetTestDatabase();
        
        // Create test users, subject, class, and enrollment
        // [setup code similar to ClassManagementTest]
        
        // Create a test class directly
        $stmt = $conn->prepare("INSERT INTO class (subject_id, class_name, class_desc, tutor_id, start_date, end_date, status, is_free)
                               VALUES (1, 'File Test Class', 'For file testing', ?, ?, ?, 'active', 1)");
        $start_date = date('Y-m-d H:i:s');
        $end_date = date('Y-m-d H:i:s', strtotime('+30 days'));
        $stmt->bind_param("iss", self::$tutor_id, $start_date, $end_date);
        $stmt->execute();
        self::$class_id = $conn->insert_id;
        
        // Enroll student
        $stmt = $conn->prepare("INSERT INTO enrollments (class_id, student_id, status) VALUES (?, ?, 'active')");
        $stmt->bind_param("ii", self::$class_id, self::$student_id);
        $stmt->execute();
    }
    
    /**
     * @test
     * @group integration
     * @covers ::createFolder
     * @covers ::getClassFolders
     */
    public function testCreateFolderAndVerifyAccess()
    {
        // Arrange: Set session as tutor
        $_SESSION['user'] = self::$tutor_id;
        $_SESSION['role'] = 'TECHGURU';
        
        // Act: Create a folder
        $folder_name = 'Test Integration Folder';
        $result = createFolder(self::$class_id, $folder_name, self::$tutor_id);
        
        // Assert: Folder was created
        $this->assertTrue($result['success']);
        $folder_id = $result['folder_id'];
        
        // Get folders for class
        $folders = getClassFolders(self::$class_id);
        $found = false;
        foreach ($folders as $folder) {
            if ($folder['folder_id'] == $folder_id) {
                $found = true;
                $this->assertEquals($folder_name, $folder['folder_name']);
                break;
            }
        }
        $this->assertTrue($found, 'Created folder not found in class folders');
        
        // Test student access to the folder
        $_SESSION['user'] = self::$student_id;
        $_SESSION['role'] = 'TECHKID';
        
        $student_folders = getClassFolders(self::$class_id);
        $found = false;
        foreach ($student_folders as $folder) {
            if ($folder['folder_id'] == $folder_id) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Student cannot see folder created by tutor');
    }
}
```

## Testing API Endpoints

For testing API endpoints, you can use PHPUnit's HTTP client integration or a dedicated library like Guzzle:

```php
<?php
// tests/Integration/ApiTest.php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class ApiTest extends TestCase
{
    private $client;
    private $base_url;
    
    protected function setUp(): void
    {
        $this->base_url = 'http://localhost/capstone-1/';
        $this->client = new Client([
            'base_uri' => $this->base_url,
            'http_errors' => false,
            'cookies' => true
        ]);
    }
    
    /**
     * @test
     * @group api
     */
    public function testGetTransactionsApi()
    {
        // Login first to get session cookie
        $response = $this->client->post('user-login', [
            'form_params' => [
                'email' => 'admin@techtutor.com',
                'password' => 'admin123'
            ]
        ]);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        // Call the transactions API
        $response = $this->client->get('get-transactions');
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('transactions', $data);
    }
}
```

## Running the Tests

To run all integration tests:

```bash
./vendor/bin/phpunit --testsuite Integration
```

To run a specific test group:

```bash
./vendor/bin/phpunit --group api
```

## Test Database Setup

Create a `tests/fixtures/test_database.sql` file with a minimal schema and test data:

```sql
-- Reset database to known state
SET FOREIGN_KEY_CHECKS=0;
TRUNCATE TABLE users;
TRUNCATE TABLE course;
TRUNCATE TABLE subject;
TRUNCATE TABLE class;
TRUNCATE TABLE class_schedule;
TRUNCATE TABLE enrollments;
TRUNCATE TABLE file_folders;
TRUNCATE TABLE file_management;
TRUNCATE TABLE login_tokens;
TRUNCATE TABLE notifications;
SET FOREIGN_KEY_CHECKS=1;

-- Minimal test data
INSERT INTO course (course_id, course_name, course_desc) 
VALUES (1, 'Test Course', 'For integration testing');

INSERT INTO subject (subject_id, course_id, subject_name, subject_desc, is_active) 
VALUES (1, 1, 'Test Subject', 'For integration testing', 1);
```

## Mocking External Services

For testing with external services like email or BigBlueButton, create mock classes:

```php
<?php
// tests/Mocks/MockMailer.php

class MockMailer extends PHPMailer\PHPMailer\PHPMailer
{
    public $sentEmails = [];
    
    public function send()
    {
        $this->sentEmails[] = [
            'to' => $this->getToAddresses(),
            'subject' => $this->Subject,
            'body' => $this->Body
        ];
        
        return true;
    }
}

// In bootstrap.php, add:
require_once 'tests/Mocks/MockMailer.php';

// Override getMailerInstance function for testing
function getMailerInstance($fromName = "The Techtutor Team")
{
    return new MockMailer(true);
}
```

## Conclusion

These example integration tests cover the key functionality described in the Integration Testing Plan. Implementing these tests will help ensure that the different components of the TechTutor platform work together correctly and maintain data integrity across operations.

Remember to adapt these examples to your specific codebase structure and implementation details. The goal is to test the integration points, not just individual functions in isolation. 