# TechTutor Platform - Unit Testing Plan

## Overview

This document outlines the unit testing strategy for the TechTutor platform. Unit testing focuses on verifying that individual components or functions work correctly in isolation. This is complementary to the integration testing plan which tests how components work together.

## Test Environment

### Prerequisites
- PHPUnit 9.5+
- PHP 7.4+ with xdebug for code coverage
- Mock database connections
- Isolated test environment

### Setup Process
1. Install PHPUnit via Composer
2. Create a separate test configuration for unit tests
3. Set up mock objects and data fixtures

## Unit Test Organization

### Directory Structure
```
tests/
├── Unit/
│   ├── Authentication/
│   ├── Class/
│   ├── File/
│   ├── Meeting/
│   ├── Notification/
│   ├── Payment/
│   └── User/
├── Mocks/
├── fixtures/
└── bootstrap.php
```

### Naming Convention
- Test files should be named with the format `{Class/Function}Test.php`
- Test methods should follow the pattern `test{MethodName}{Scenario}`

## Test Categories

### 1. Authentication Functions

#### 1.1 Registration Function Tests
**Test ID:** UT-AUTH-001
**Target:** `register()` function
**Test Cases:**
- Valid user registration with all required fields
- Missing required fields (email, name, password)
- Invalid email format
- Password complexity requirements not met
- Password mismatch
- Existing email address
- Invalid user role

#### 1.2 Login Function Tests
**Test ID:** UT-AUTH-002
**Target:** `login()` function
**Test Cases:**
- Valid login with correct credentials
- Invalid email
- Invalid password
- Deactivated account
- Unverified account
- Remember Me functionality
- Empty credentials

#### 1.3 Password Reset Tests
**Test ID:** UT-AUTH-003
**Target:** Password reset functions
**Test Cases:**
- Generate reset token
- Valid token verification
- Expired token handling
- Password update with valid token
- Invalid token handling

### 2. User Management Functions

#### 2.1 User Profile Functions
**Test ID:** UT-USER-001
**Target:** User profile management functions
**Test Cases:**
- Update user profile information
- Change password
- Upload profile picture
- Validate contact information
- Role-specific permission checks

#### 2.2 User Status Management
**Test ID:** UT-USER-002
**Target:** User status functions
**Test Cases:**
- Account activation
- Account deactivation
- Permission checks for status changes
- Status change notifications

### 3. Class Management Functions

#### 3.1 Class Creation
**Test ID:** UT-CLASS-001
**Target:** `createClass()` function
**Test Cases:**
- Valid class creation with all required data
- Missing required fields
- Invalid date ranges
- Subject validation
- Schedule generation
- Image upload handling

#### 3.2 Class Scheduling
**Test ID:** UT-CLASS-002
**Target:** Schedule management functions
**Test Cases:**
- Schedule creation with valid data
- Schedule updates
- Schedule deletion
- Schedule status changes
- Date/time validation
- Conflict detection

#### 3.3 Enrollment Functions
**Test ID:** UT-CLASS-003
**Target:** Enrollment management functions
**Test Cases:**
- Student enrollment with valid data
- Enrollment with class at capacity
- Enrollment status changes
- Student access validation
- Duplicate enrollment handling

### 4. File Management Functions

#### 4.1 File Operations
**Test ID:** UT-FILE-001
**Target:** File management functions
**Test Cases:**
- File validation
- File metadata extraction
- Folder creation
- Permission assignment
- Visibility settings

#### 4.2 File Access Control
**Test ID:** UT-FILE-002
**Target:** File access functions
**Test Cases:**
- Access permission checks
- User role-based access
- Enrollment-based access
- Public/private file handling

### 5. Meeting Management Functions

#### 5.1 Meeting Creation
**Test ID:** UT-MEETING-001
**Target:** Meeting creation functions
**Test Cases:**
- Valid meeting creation
- Error handling for API calls
- Meeting parameter validation
- Recording settings
- Join URL generation

#### 5.2 Meeting Analytics
**Test ID:** UT-MEETING-002
**Target:** Meeting analytics functions
**Test Cases:**
- Analytics data processing
- Participation metrics calculation
- Duration tracking
- Attendance recording
- Data aggregation methods

### 6. Notification System Functions

#### 6.1 Notification Creation
**Test ID:** UT-NOTIF-001
**Target:** Notification creation functions
**Test Cases:**
- Create notifications for different events
- Target user/role selection
- Notification content generation
- Priority handling
- Duplicate prevention

#### 6.2 Notification Delivery
**Test ID:** UT-NOTIF-002
**Target:** Notification delivery functions
**Test Cases:**
- Mark as read/unread
- Notification count
- Filtering by type/status
- Batch notification handling

### 7. Payment System Functions

#### 7.1 Transaction Processing
**Test ID:** UT-PAYMENT-001
**Target:** Payment processing functions
**Test Cases:**
- Transaction creation
- Amount calculation
- Currency handling
- Status transitions
- Receipt generation

#### 7.2 Transaction Validation
**Test ID:** UT-PAYMENT-002
**Target:** Transaction validation functions
**Test Cases:**
- Payment data validation
- Required field verification
- Transaction ID uniqueness
- Amount verification
- Status validation

### 8. Utility Functions

#### 8.1 Data Formatting Functions
**Test ID:** UT-UTIL-001
**Target:** Data formatting utilities
**Test Cases:**
- Date/time formatting
- Currency formatting
- File size formatting
- Status text conversion
- String sanitization

#### 8.2 Error Logging
**Test ID:** UT-UTIL-002
**Target:** `log_error()` function
**Test Cases:**
- Different error types
- File path handling
- Log rotation
- Message formatting
- Context capture

## Mock Objects

The following mock objects should be created to isolate unit tests:

1. **MockDatabase**
   - Simulate database connections and queries
   - Predefined result sets for typical queries
   - Transaction simulation

2. **MockMailer**
   - Capture sent emails
   - Verify email content and recipients
   - Simulate success/failure scenarios

3. **MockBigBlueButton**
   - Simulate API responses
   - Test meeting creation without actual API calls
   - Mock recording processing

4. **MockFileSystem**
   - Simulate file operations
   - Mock upload process
   - Test file metadata extraction

## Data Fixtures

Prepare standard test data fixtures for:
- User accounts with different roles
- Class and subject data
- Schedule patterns
- File metadata
- Meeting configurations
- Transaction records

## Test Coverage Goals

- Core functions: 90%+ coverage
- Helper/utility functions: 80%+ coverage
- UI-related functions: 70%+ coverage
- Overall codebase: 80%+ coverage

## Best Practices for Unit Tests

1. **Isolation**
   - Each test should be independent and not rely on other tests
   - Use setUp() and tearDown() methods to prepare and clean test environments

2. **Deterministic**
   - Tests should produce the same results on each run
   - Avoid dependencies on external systems or random data

3. **Single Responsibility**
   - Each test should verify one specific behavior
   - Use descriptive test names that explain what is being tested

4. **Boundary Testing**
   - Test edge cases and boundary conditions
   - Include tests for invalid inputs and error conditions

5. **Code Coverage**
   - Use code coverage tools to identify untested code paths
   - Focus on testing business logic and critical paths

## Unit Test Example

```php
<?php
// tests/Unit/Authentication/RegisterTest.php

use PHPUnit\Framework\TestCase;

class RegisterTest extends TestCase
{
    private $mockDatabase;
    private $mockMailer;
    
    protected function setUp(): void
    {
        $this->mockDatabase = $this->createMock(MockDatabase::class);
        $this->mockMailer = $this->createMock(MockMailer::class);
        
        // Set up the global mock connections
        global $conn, $mail;
        $conn = $this->mockDatabase;
        $mail = $this->mockMailer;
        
        // Reset session data
        $_SESSION = [];
        $_POST = [];
    }
    
    /**
     * @test
     * @covers ::register
     */
    public function testRegisterWithValidData()
    {
        // Arrange
        $_POST['register'] = true;
        $_POST['email'] = 'test@example.com';
        $_POST['first-name'] = 'Test';
        $_POST['last-name'] = 'User';
        $_POST['password'] = 'Password123!';
        $_POST['confirm-password'] = 'Password123!';
        $_POST['role'] = 'TECHKID';
        
        // Configure mock behavior
        $this->mockDatabase->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains("INSERT INTO users"))
            ->willReturn($this->createMock(\mysqli_stmt::class));
            
        $this->mockMailer->expects($this->once())
            ->method('send')
            ->willReturn(true);
            
        // Act
        register();
        
        // Assert
        $this->assertArrayHasKey('msg', $_SESSION);
        $this->assertStringContainsString('successfully created', $_SESSION['msg']);
    }
    
    /**
     * @test
     * @covers ::register
     */
    public function testRegisterWithMissingFields()
    {
        // Arrange
        $_POST['register'] = true;
        $_POST['email'] = 'test@example.com';
        // Missing first name
        $_POST['last-name'] = 'User';
        $_POST['password'] = 'Password123!';
        $_POST['confirm-password'] = 'Password123!';
        $_POST['role'] = 'TECHKID';
        
        // Act
        register();
        
        // Assert
        $this->assertArrayHasKey('msg', $_SESSION);
        $this->assertStringContainsString('required fields', $_SESSION['msg']);
    }
    
    /**
     * @test
     * @covers ::register
     */
    public function testRegisterWithPasswordMismatch()
    {
        // Arrange
        $_POST['register'] = true;
        $_POST['email'] = 'test@example.com';
        $_POST['first-name'] = 'Test';
        $_POST['last-name'] = 'User';
        $_POST['password'] = 'Password123!';
        $_POST['confirm-password'] = 'DifferentPassword123!';
        $_POST['role'] = 'TECHKID';
        
        // Act
        register();
        
        // Assert
        $this->assertArrayHasKey('msg', $_SESSION);
        $this->assertStringContainsString('Password does not match', $_SESSION['msg']);
    }
    
    /**
     * @test
     * @covers ::register
     */
    public function testRegisterWithExistingEmail()
    {
        // Arrange
        $_POST['register'] = true;
        $_POST['email'] = 'existing@example.com';
        $_POST['first-name'] = 'Test';
        $_POST['last-name'] = 'User';
        $_POST['password'] = 'Password123!';
        $_POST['confirm-password'] = 'Password123!';
        $_POST['role'] = 'TECHKID';
        
        // Mock the database to return that email exists
        $stmt = $this->createMock(\mysqli_stmt::class);
        $result = $this->createMock(\mysqli_result::class);
        
        $stmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $stmt->expects($this->once())
            ->method('get_result')
            ->willReturn($result);
        $result->expects($this->once())
            ->method('num_rows')
            ->willReturn(1);
            
        $this->mockDatabase->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains("SELECT uid FROM users WHERE email"))
            ->willReturn($stmt);
        
        // Act
        register();
        
        // Assert
        $this->assertArrayHasKey('msg', $_SESSION);
        $this->assertStringContainsString('already registered', $_SESSION['msg']);
    }
}
```

## Running Unit Tests

```bash
# Run all unit tests
./vendor/bin/phpunit --testsuite Unit

# Run specific test category
./vendor/bin/phpunit --group authentication

# Run with coverage report
./vendor/bin/phpunit --testsuite Unit --coverage-html coverage
```

## Test Reporting

Generate and track the following metrics:
- Test success/failure rates
- Code coverage percentage
- Number of assertions
- Test execution time

## Continuous Integration

Set up automated unit testing in CI pipeline:
1. Run unit tests on every commit
2. Block merges if unit tests fail
3. Generate and archive coverage reports
4. Track coverage trends over time

## Conclusion

This unit testing plan provides a structured approach to testing individual components of the TechTutor platform. Combined with the integration testing plan, it forms a comprehensive testing strategy that helps ensure the reliability and quality of the application.

**Version:** 1.0  
**Last Updated:** [Current Date]  
**Prepared By:** [Your Name] 