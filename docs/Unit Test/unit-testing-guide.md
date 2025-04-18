# Unit Testing Guide for TechTutor Project

## Overview
This document outlines the unit testing strategy for the TechTutor application, including setup instructions, best practices, and examples.

## Testing Environment Setup

### Prerequisites
- PHP 7.4 or higher
- PHPUnit 9.x (already installed in assets/vendor)
- XAMPP (local development environment)

### Directory Structure
The testing environment is organized as follows:
```
/test
  /unit          # Unit tests for individual components
  /integration   # Tests for component interactions
  /functional    # Tests for complete features
  /fixtures      # Test data files
  /mocks         # Mock objects for testing
  phpunit.xml    # PHPUnit configuration
```

## Running Tests

### Run All Tests
```
cd d:/xampp/htdocs/capstone-1
php assets/vendor/bin/phpunit -c test/phpunit.xml
```

### Run Specific Test Suite
```
php assets/vendor/bin/phpunit -c test/phpunit.xml --testsuite "TechTutor Test Suite"
```

### Run Specific Test File
```
php assets/vendor/bin/phpunit -c test/phpunit.xml test/unit/UserTest.php
```

### Generate Coverage Report
```
php assets/vendor/bin/phpunit -c test/phpunit.xml --coverage-html test/coverage
```
Then open `test/coverage/index.html` in your browser.

## Writing Tests

### Basic Test Structure
```php
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    protected function setUp(): void
    {
        // Setup code runs before each test
    }

    protected function tearDown(): void
    {
        // Cleanup code runs after each test
    }

    public function testSomething()
    {
        // Arrange - set up test conditions
        $expected = true;
        
        // Act - perform the action being tested
        $result = someFunction();
        
        // Assert - verify the result
        $this->assertEquals($expected, $result);
    }
}
```

### Naming Conventions
- Test classes should be named with the suffix `Test` (e.g., `UserTest`)
- Test methods should be prefixed with `test` (e.g., `testUserLogin`)
- Test files should match the class name (e.g., `UserTest.php`)

### Testing Patterns

#### 1. Arrange-Act-Assert (AAA)
Organize tests into three sections:
- **Arrange**: Set up the test conditions
- **Act**: Execute the code being tested
- **Assert**: Verify the results

#### 2. Given-When-Then
A behavior-driven approach:
- **Given**: The initial context
- **When**: An event occurs
- **Then**: Ensure outcomes

## Types of Tests

### Unit Tests
Test individual components in isolation.
```php
public function testPasswordHashing()
{
    $password = "secure123";
    $hashedPassword = hashPassword($password);
    
    $this->assertTrue(verifyPassword($password, $hashedPassword));
}
```

### Integration Tests
Test interactions between components.
```php
public function testUserRegistrationAndLogin()
{
    // Test user registration
    $userData = ['email' => 'test@example.com', 'password' => 'secure123'];
    $userId = registerUser($userData);
    
    // Test if the registered user can log in
    $loginResult = loginUser($userData['email'], $userData['password']);
    
    $this->assertTrue($loginResult);
}
```

### Functional Tests
Test complete features from the user's perspective.
```php
public function testCompleteRegistrationFlow()
{
    // Simulate registration form submission
    // Verify email verification process
    // Test login after verification
}
```

## Testing Database Operations

### Setup Test Database
1. Create a separate test database
2. Use the same schema as production
3. Populate with test data

### Example Database Test
```php
class UserDatabaseTest extends TestCase
{
    protected $db;
    
    protected function setUp(): void
    {
        $this->db = require __DIR__ . '/../../backends/db.php';
        // Reset test database to known state
        executeQuery("TRUNCATE TABLE users");
        executeQuery("INSERT INTO users (name, email, password) VALUES ('Test User', 'test@example.com', '$2y$10$hashedPassword')");
    }
    
    public function testFetchUserByEmail()
    {
        $user = getUserByEmail('test@example.com');
        $this->assertEquals('Test User', $user['name']);
    }
}
```

## Mocking External Dependencies

```php
class PaymentServiceTest extends TestCase
{
    public function testProcessPayment()
    {
        // Create a mock for the payment gateway
        $gatewayMock = $this->createMock(PaymentGateway::class);
        
        // Set expectations on the mock
        $gatewayMock->expects($this->once())
                   ->method('processPayment')
                   ->with(100, 'USD')
                   ->willReturn(true);
        
        // Inject the mock
        $paymentService = new PaymentService($gatewayMock);
        
        // Test with the mock
        $result = $paymentService->processPayment(100, 'USD');
        $this->assertTrue($result);
    }
}
```

## Best Practices

1. **Test Independence**: Each test should run independently
2. **Deterministic Tests**: Tests should produce the same results each time
3. **Test Coverage**: Aim for 70%+ code coverage, focus on critical paths
4. **Readable Tests**: Tests should clearly show what's being tested
5. **Fast Tests**: Tests should run quickly to encourage frequent testing
6. **Maintainable Tests**: Avoid duplication with setup fixtures and helper methods

## Troubleshooting Common Issues

### Database Connection Issues
- Ensure test database credentials are correct
- Check that database server is running
- Verify database permissions

### Path Issues
- Use absolute paths in PHPUnit configuration
- Check include/require paths in bootstrap file

### Memory Limit Exceeded
- Increase memory_limit in php.ini
- Run tests in smaller batches

## Resources

- [PHPUnit Documentation](https://phpunit.readthedocs.io/)
- [Mocking in PHPUnit](https://phpunit.readthedocs.io/en/9.5/test-doubles.html)
- [Test-Driven Development Guide](https://www.agilealliance.org/glossary/tdd/)