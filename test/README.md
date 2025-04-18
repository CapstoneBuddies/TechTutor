# TechTutor Testing Framework

This directory contains the testing framework for the TechTutor application.

## Directory Structure

- `/unit`: Unit tests for individual components
- `/integration`: Tests for component interactions
- `/functional`: Tests for complete features and workflows
- `/fixtures`: Test data files used by tests
- `/mocks`: Mock objects for simulating external dependencies
- `/coverage`: Test coverage reports (generated when running tests)

## Getting Started

1. Make sure PHPUnit is installed (already available in assets/vendor)
2. Run tests using the command:
   ```
   php ../assets/vendor/bin/phpunit -c phpunit.xml
   ```

## Writing Tests

- Place unit tests in the `/unit` directory
- Name test classes with the suffix `Test` (e.g., `UserTest.php`)
- Extend the `PHPUnit\Framework\TestCase` class
- Name test methods with the prefix `test` (e.g., `testUserLogin()`)

## Using Fixtures

Load test data using the `loadFixture()` function:

```php
$users = loadFixture('users');
```

## Using Mocks

Mock objects are available in the `/mocks` directory:

```php
$paymentGateway = new PaymentGateway();
```

## Running Specific Tests

To run a specific test file:
```
php ../assets/vendor/bin/phpunit -c phpunit.xml unit/UserTest.php
```

To run a specific test method:
```
php ../assets/vendor/bin/phpunit -c phpunit.xml --filter testUserLogin unit/UserTest.php
```

## Generating Coverage Reports

Run tests with coverage reporting:
```
php ../assets/vendor/bin/phpunit -c phpunit.xml --coverage-html coverage
```

Then open `coverage/index.html` in your browser to view the report.

## Documentation

For complete documentation on the testing framework, see the `/docs/Unit Test` directory.