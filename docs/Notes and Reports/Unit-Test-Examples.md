# TechTutor Unit Test Implementation Examples

This document provides concrete examples of how to implement unit tests for key functions in the TechTutor platform using PHPUnit.

## Setting Up PHPUnit for Unit Testing

### 1. Install PHPUnit

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
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_NAME" value="techtutor_test"/>
    </php>
</phpunit>
```

### 3. Create Bootstrap File

```php
<?php
// tests/bootstrap.php

define('TESTING', true);
define('ROOT_PATH', realpath(__DIR__ . '/..'));
require_once ROOT_PATH . '/tests/Mocks/MockDatabase.php';
require_once ROOT_PATH . '/tests/Mocks/MockMailer.php';

// Override global functions and objects for testing
global $conn;
$conn = new MockDatabase();
```

## Example Mock Objects

### 1. Mock Database

```php
<?php
// tests/Mocks/MockDatabase.php

class MockDatabase {
    public $mockResults = [];
    public $lastQuery;
    public $insert_id = 0;
    
    public function prepare($query) {
        $this->lastQuery = $query;
        return new MockStatement($this);
    }
    
    public function begin_transaction() {
        return true;
    }
    
    public function commit() {
        return true;
    }
    
    public function rollback() {
        return true;
    }
}

class MockStatement {
    private $db;
    
    public function __construct(MockDatabase $db) {
        $this->db = $db;
    }
    
    public function bind_param($types, ...$params) {
        return true;
    }
    
    public function execute() {
        return true;
    }
    
    public function get_result() {
        return new MockResult($this->db);
    }
    
    public function close() {
        return true;
    }
}

class MockResult {
    private $db;
    
    public function __construct(MockDatabase $db) {
        $this->db = $db;
    }
    
    public function fetch_assoc() {
        if (isset($this->db->mockResults[$this->db->lastQuery])) {
            return array_shift($this->db->mockResults[$this->db->lastQuery]);
        }
        return null;
    }
    
    public function fetch_all($resultType = MYSQLI_NUM) {
        return $this->db->mockResults[$this->db->lastQuery] ?? [];
    }
    
    public function num_rows() {
        return count($this->db->mockResults[$this->db->lastQuery] ?? []);
    }
}
```

### 2. Mock Mailer

```php
<?php
// tests/Mocks/MockMailer.php

class MockMailer {
    public $sentEmails = [];
    public $to = [];
    public $subject = '';
    public $body = '';
    
    public function addAddress($email) {
        $this->to[] = $email;
        return true;
    }
    
    public function __set($name, $value) {
        if ($name == 'Subject') {
            $this->subject = $value;
        } elseif ($name == 'Body') {
            $this->body = $value;
        }
    }
    
    public function send() {
        $this->sentEmails[] = [
            'to' => $this->to,
            'subject' => $this->subject,
            'body' => $this->body
        ];
        return true;
    }
}

// Override the getMailerInstance function
function getMailerInstance($fromName = "The Techtutor Team") {
    return new MockMailer();
}
```

## Example Unit Tests

### 1. Testing Authentication Functions

```php
<?php
// tests/Unit/Authentication/RegisterTest.php

use PHPUnit\Framework\TestCase;

class RegisterTest extends TestCase {
    private $mockDB;
    
    protected function setUp(): void {
        global $conn;
        $this->mockDB = $conn;
        
        // Reset test environment
        $_SESSION = [];
        $_POST = [];
    }
    
    /**
     * @test
     */
    public function testValidRegistration() {
        // Arrange
        $_POST['register'] = true;
        $_POST['email'] = 'test@example.com';
        $_POST['first-name'] = 'Test';
        $_POST['last-name'] = 'User';
        $_POST['password'] = 'Password123!';
        $_POST['confirm-password'] = 'Password123!';
        $_POST['role'] = 'TECHKID';
        
        // Mock email check - no existing user
        $this->mockDB->mockResults["SELECT uid FROM users WHERE email = ?"] = [];
        
        // Act
        register();
        
        // Assert
        $this->assertArrayHasKey('msg', $_SESSION);
        $this->assertStringContainsString('successfully created', $_SESSION['msg']);
    }
    
    /**
     * @test
     */
    public function testRegistrationWithExistingEmail() {
        // Arrange
        $_POST['register'] = true;
        $_POST['email'] = 'existing@example.com';
        $_POST['first-name'] = 'Test';
        $_POST['last-name'] = 'User';
        $_POST['password'] = 'Password123!';
        $_POST['confirm-password'] = 'Password123!';
        $_POST['role'] = 'TECHKID';
        
        // Mock email check - existing user
        $this->mockDB->mockResults["SELECT uid FROM users WHERE email = ?"] = [
            ['uid' => 123]
        ];
        
        // Act
        register();
        
        // Assert
        $this->assertArrayHasKey('msg', $_SESSION);
        $this->assertStringContainsString('already registered', $_SESSION['msg']);
    }
    
    /**
     * @test
     */
    public function testRegistrationWithPasswordMismatch() {
        // Arrange
        $_POST['register'] = true;
        $_POST['email'] = 'test@example.com';
        $_POST['first-name'] = 'Test';
        $_POST['last-name'] = 'User';
        $_POST['password'] = 'Password123!';
        $_POST['confirm-password'] = 'DifferentPassword!';
        $_POST['role'] = 'TECHKID';
        
        // Act
        register();
        
        // Assert
        $this->assertArrayHasKey('msg', $_SESSION);
        $this->assertStringContainsString('Password does not match', $_SESSION['msg']);
    }
}
```

### 2. Testing Class Management Functions

```php
<?php
// tests/Unit/Class/ClassManagementTest.php

use PHPUnit\Framework\TestCase;

class ClassManagementTest extends TestCase {
    private $mockDB;
    
    protected function setUp(): void {
        global $conn;
        $this->mockDB = $conn;
        
        // Reset test environment
        $_SESSION = [
            'user' => 123,
            'role' => 'TECHGURU'
        ];
    }
    
    /**
     * @test
     */
    public function testGetTechGuruClasses() {
        // Arrange
        $tutorId = 123;
        $expectedClasses = [
            [
                'class_id' => 1,
                'class_name' => 'PHP Basics',
                'subject_name' => 'Web Development',
                'student_count' => 5,
                'completed_sessions' => 2,
                'total_sessions' => 10,
                'status' => 'active'
            ],
            [
                'class_id' => 2,
                'class_name' => 'JavaScript Fundamentals',
                'subject_name' => 'Web Development',
                'student_count' => 3,
                'completed_sessions' => 0,
                'total_sessions' => 8,
                'status' => 'pending'
            ]
        ];
        
        // Mock database results
        $this->mockDB->mockResults[
            "SELECT c.*, s.subject_name, COUNT(DISTINCT e.student_id) AS student_count, (SELECT COUNT(*) FROM class_schedule cs WHERE cs.class_id = c.class_id AND cs.status = 'completed') AS completed_sessions, (SELECT COUNT(*) FROM class_schedule cs WHERE cs.class_id = c.class_id) AS total_sessions, (SELECT cs.session_date FROM class_schedule cs WHERE cs.class_id = c.class_id AND cs.status IN ('pending', 'confirmed') AND cs.session_date >= CURDATE() ORDER BY cs.session_date ASC, cs.start_time ASC, cs.schedule_id ASC LIMIT 1) AS next_session_date, (SELECT CONCAT(DATE_FORMAT(cs.start_time, '%h:%i %p'), ' - ', DATE_FORMAT(cs.end_time, '%h:%i %p')) FROM class_schedule cs WHERE cs.class_id = c.class_id AND cs.status IN ('pending', 'confirmed') AND cs.session_date >= CURDATE() ORDER BY cs.session_date ASC, cs.start_time ASC, cs.schedule_id ASC LIMIT 1) AS next_session_time, (SELECT cs.status FROM class_schedule cs WHERE cs.class_id = c.class_id AND cs.status IN ('pending', 'confirmed') AND cs.session_date >= CURDATE() ORDER BY cs.session_date ASC, cs.start_time ASC, cs.schedule_id ASC LIMIT 1) AS next_session_status, (SELECT GROUP_CONCAT(cs.schedule_id ORDER BY cs.start_time ASC) FROM class_schedule cs WHERE cs.class_id = c.class_id AND cs.status IN ('pending', 'confirmed') AND cs.session_date = (SELECT MIN(cs2.session_date) FROM class_schedule cs2 WHERE cs2.class_id = c.class_id AND cs2.status IN ('pending', 'confirmed') AND cs2.session_date >= CURDATE())) AS next_session_id, (SELECT COUNT(*) FROM class_schedule cs WHERE cs.class_id = c.class_id AND cs.status IN ('pending', 'confirmed') AND cs.session_date = (SELECT MIN(cs2.session_date) FROM class_schedule cs2 WHERE cs2.class_id = c.class_id AND cs2.status IN ('pending', 'confirmed') AND cs2.session_date >= CURDATE())) AS next_session_count FROM class c LEFT JOIN subject s ON c.subject_id = s.subject_id LEFT JOIN enrollments e ON c.class_id = e.class_id AND e.status = 'active' WHERE c.tutor_id = ? GROUP BY c.class_id ORDER BY CASE WHEN c.status = 'active' THEN 1 WHEN c.status = 'pending' THEN 2 ELSE 3 END, next_session_date ASC, c.start_date DESC"
        ] = $expectedClasses;
        
        // Act
        $classes = getTechGuruClasses($tutorId);
        
        // Assert
        $this->assertCount(2, $classes);
        $this->assertEquals('PHP Basics', $classes[0]['class_name']);
        $this->assertEquals('JavaScript Fundamentals', $classes[1]['class_name']);
    }
    
    /**
     * @test
     */
    public function testCreateClass() {
        // Arrange
        $classData = [
            'subject_id' => 1,
            'class_name' => 'Test Class',
            'class_desc' => 'This is a test class',
            'tutor_id' => 123,
            'start_date' => '2023-12-01',
            'end_date' => '2023-12-31',
            'days' => ['Monday', 'Wednesday'],
            'time_slots' => [
                [
                    'start_time' => '09:00:00',
                    'end_time' => '10:00:00'
                ]
            ],
            'class_size' => 10,
            'is_free' => 1
        ];
        
        // Set up mock database to return success
        $this->mockDB->insert_id = 456; // New class ID
        
        // Act
        $result = createClass($classData);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(456, $result['class_id']);
    }
    
    /**
     * @test
     */
    public function testDeleteClass() {
        // Arrange
        $classId = 123;
        $tutorId = 456;
        
        // Mock verification query
        $this->mockDB->mockResults["SELECT tutor_id, class_name FROM class WHERE class_id = ?"] = [
            ['tutor_id' => $tutorId, 'class_name' => 'Test Class']
        ];
        
        // Act
        $result = deleteClass($classId, $tutorId);
        
        // Assert
        $this->assertTrue($result);
    }
}
```

### 3. Testing Utility Functions

```php
<?php
// tests/Unit/Utility/FormattingTest.php

use PHPUnit\Framework\TestCase;

class FormattingTest extends TestCase {
    /**
     * @test
     * @dataProvider provideTimestamps
     */
    public function testGetTimeAgo($timestamp, $expected) {
        // Act
        $result = getTimeAgo($timestamp);
        
        // Assert
        $this->assertEquals($expected, $result);
    }
    
    public function provideTimestamps() {
        $now = time();
        return [
            [date('Y-m-d H:i:s', $now - 30), 'Just now'],
            [date('Y-m-d H:i:s', $now - 120), '2 minutes ago'],
            [date('Y-m-d H:i:s', $now - 3600), '1 hour ago'],
            [date('Y-m-d H:i:s', $now - 7200), '2 hours ago'],
            [date('Y-m-d H:i:s', $now - 86400), '1 day ago'],
            [date('Y-m-d H:i:s', $now - 172800), '2 days ago'],
            [date('Y-m-d H:i:s', $now - 2592000), '1 month ago'],
            [date('Y-m-d H:i:s', $now - 5184000), '2 months ago'],
            [date('Y-m-d H:i:s', $now - 31536000), '1 year ago'],
            [date('Y-m-d H:i:s', $now - 63072000), '2 years ago'],
        ];
    }
    
    /**
     * @test
     * @dataProvider provideByteValues
     */
    public function testFormatBytes($bytes, $precision, $expected) {
        // Act
        $result = formatBytes($bytes, $precision);
        
        // Assert
        $this->assertEquals($expected, $result);
    }
    
    public function provideByteValues() {
        return [
            [0, 2, '0 B'],
            [1024, 2, '1 KB'],
            [1048576, 2, '1 MB'],
            [1073741824, 2, '1 GB'],
            [1099511627776, 2, '1 TB'],
            [1500, 2, '1.46 KB'],
            [1500000, 2, '1.43 MB'],
            [1500000000, 2, '1.4 GB'],
            [1500, 0, '1 KB'],
            [1500000, 1, '1.4 MB']
        ];
    }
}
```

## Running Unit Tests

```bash
# Run all unit tests
./vendor/bin/phpunit --testsuite Unit

# Run tests in a specific directory
./vendor/bin/phpunit tests/Unit/Authentication

# Run a specific test class
./vendor/bin/phpunit tests/Unit/Authentication/RegisterTest.php

# Generate code coverage report
./vendor/bin/phpunit --coverage-html coverage
```

## Best Practices for Unit Testing in PHP

1. **Test in isolation** - Each unit test should test only one specific functionality
2. **Use descriptive test names** - Make test names clearly describe what's being tested
3. **Follow AAA pattern** - Arrange (setup), Act (execution), Assert (verification)
4. **Mock external dependencies** - Don't rely on external systems for unit tests
5. **Test edge cases** - Include tests for boundary conditions and error scenarios
6. **Keep tests simple** - Tests should be easy to understand and maintain
7. **Run tests often** - Integrate tests into your development workflow
8. **Maintain test independence** - Tests should not depend on other tests

## Next Steps

1. Create a complete test suite covering all critical functions
2. Set up continuous integration to run tests automatically
3. Track code coverage to identify untested areas
4. Integrate unit testing into your development workflow 