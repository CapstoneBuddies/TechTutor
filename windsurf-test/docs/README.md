# TechTutor Unit Testing Suite

This directory contains a comprehensive unit testing suite for the TechTutor application. The tests cover utility functions, authentication, database operations, user management, and notifications.

## Test Structure

- `phpunit.xml` - PHPUnit configuration
- `bootstrap.php` - Test environment setup
- `UtilsTest.php` - Tests for utility functions from main.php
- `AuthTest.php` - Tests for authentication functionality
- `DatabaseTest.php` - Tests for database operations
- `UserManagementTest.php` - Tests for user management functions
- `NotificationsTest.php` - Tests for notification functionality
- `generate_test_report.php` - Script to run tests and generate a tabular HTML report

## Test Credentials

The test suite uses the following credentials:

- Admin: `admin@test.com` / `Abc123!!`
- TechGuru: `tutor@test.com` / `Abc123!!`
- TechKid: `student@test.com` / `Abc123!!`

## Running Tests

### To run all tests with PHPUnit:

```bash
cd d:\xampp\htdocs\capstone-1\windsurf-test
php ../assets/vendor/bin/phpunit
```

### To generate a tabular HTML report:

```bash
cd d:\xampp\htdocs\capstone-1\windsurf-test
php generate_test_report.php
```

This will create `test_report.html` in the windsurf-test directory with a detailed breakdown of test results in a user-friendly format.

## Test Report

The generated report includes:

1. A summary table showing:
   - Total number of tests
   - Tests passed
   - Tests failed
   - Errors
   - Skipped tests
   - Incomplete tests
   - Risky tests

2. A detailed table showing:
   - Test class
   - Test method
   - Status (passed, failed, error, etc.)
   - Error message (if applicable)
   - Execution time

## Adding New Tests

To add new tests:

1. Create a new test file in the `windsurf-test` directory
2. Make sure your test class extends `PHPUnit\Framework\TestCase`
3. Add your test class to the `$testClasses` array in `generate_test_report.php`

## Database Considerations

Some tests use mock database functionality to avoid modifying the real database. Tests that would interact with the actual database are marked as skipped by default for safety.

For tests that need to access the database, make sure to:
- Set up proper test data
- Run the tests in a controlled environment
- Clean up after testing
