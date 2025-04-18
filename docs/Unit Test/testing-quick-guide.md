# TechTutor Testing Quick Guide

This guide provides a quick solution for getting your unit tests working properly.

## Fixing the Database Connection Issues

The test failures you're seeing are related to database connection issues. Here's how to fix them:

1. **Use the Fixed UserTest.php**  
   I've provided an updated `UserTest.php` file that correctly handles the database connection by:
   - Creating its own connection in the setUp method
   - Properly closing the connection in tearDown
   - Including more robust tests that will work with your database

2. **Run the Database Setup Script**  
   Run the database setup script to add the necessary test users:
   ```bash
   cd d:/xampp/htdocs/capstone-1
   php test/setup-database.php
   ```
   This script will:
   - Verify your database connection
   - Check the users table structure
   - Add the test users with the correct credentials
   - Verify the test users were added properly

3. **Run the Updated Tests**  
   After setting up the database, run the updated tests:
   ```bash
   cd d:/xampp/htdocs/capstone-1
   php assets/vendor/bin/phpunit -c test/phpunit.xml test/unit/UserTest.php
   ```

## Understanding the XDEBUG_MODE Warning

The warning about `XDEBUG_MODE=coverage` is related to code coverage reporting and doesn't affect the test execution itself. You can ignore this warning unless you specifically want to generate code coverage reports.

If you want to generate code coverage reports, you need to:

1. Install the Xdebug extension for PHP
2. Configure it for coverage in php.ini:
   ```
   [xdebug]
   xdebug.mode=coverage
   ```

## Running Specific Tests

You can run specific test methods using the `--filter` option:

```bash
php assets/vendor/bin/phpunit -c test/phpunit.xml --filter testPhpUnitIsWorking test/unit/UserTest.php
```

## Troubleshooting

If you still experience issues:

1. **Check Database Credentials**  
   Make sure your database credentials in `backends/config.php` are correct.

2. **Check Table Structure**  
   Ensure your users table has all the required fields (uid, email, password, role, etc.).

3. **Manual Database Check**  
   Connect to your database manually to verify it's accessible:
   ```sql
   SELECT * FROM users LIMIT 5;
   ```

4. **PHP Error Reporting**  
   Increase PHP error reporting to see more detailed errors:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```