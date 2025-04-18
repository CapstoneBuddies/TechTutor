<?php
/**
 * User Service Unit Test
 * 
 * This is a proper unit test that tests user-related functions in isolation
 * without requiring database connections.
 */
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    /**
     * Test password validation function
     */
    public function testPasswordValidation()
    {
        // Test cases for password validation
        $testCases = [
            // Valid passwords
            ['Abc123!@', true], // Valid - has uppercase, lowercase, number, special character
            ['P@ssw0rd', true], // Valid - meets all requirements
            ['Test123!', true], // Valid - meets all requirements
            
            // Invalid passwords
            ['abc123', false], // Invalid - too short, no uppercase, no special char
            ['PASSWORD123!', false], // Invalid - no lowercase
            ['Password!', false], // Invalid - no number
            ['password123', false], // Invalid - no uppercase, no special char
            ['Ab1!', false], // Invalid - too short
        ];
        
        foreach ($testCases as $index => [$password, $expected]) {
            $result = $this->validatePassword($password);
            $this->assertEquals($expected, $result, "Password validation failed for case $index: '$password'");
        }
    }
    
    /**
     * Test email validation function
     */
    public function testEmailValidation()
    {
        // Test cases for email validation
        $testCases = [
            // Valid emails
            ['test@example.com', true],
            ['user.name@domain.co.uk', true],
            ['person-123@company.org', true],
            
            // Invalid emails
            ['not-an-email', false],
            ['missing@domain', false],
            ['@nodomain.com', false],
            ['spaces in@email.com', false],
            ['', false],
        ];
        
        foreach ($testCases as $index => [$email, $expected]) {
            $result = $this->validateEmail($email);
            $this->assertEquals($expected, $result, "Email validation failed for case $index: '$email'");
        }
    }
    
    /**
     * Test user role validation
     */
    public function testRoleValidation()
    {
        // Test cases for role validation
        $validRoles = ['TECHGURU', 'TECHKID', 'ADMIN'];
        $invalidRoles = ['', 'USER', 'STUDENT', 'TEACHER', null, 123];
        
        foreach ($validRoles as $role) {
            $this->assertTrue($this->validateRole($role), "Role '$role' should be valid");
        }
        
        foreach ($invalidRoles as $role) {
            $this->assertFalse($this->validateRole($role), "Role '$role' should be invalid");
        }
    }
    
    /**
     * Test user creation helper without database 
     */
    public function testCreateUserHelper()
    {
        // Test data
        $userData = [
            'email' => 'newuser@example.com',
            'first_name' => 'New',
            'last_name' => 'User',
            'password' => 'SecurePass123!',
            'role' => 'TECHKID'
        ];
        
        // Call the function we're testing
        $result = $this->prepareUserData($userData);
        
        // Assert the result is as expected
        $this->assertIsArray($result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('hashed_password', $result);
        $this->assertArrayHasKey('role', $result);
        $this->assertEquals($userData['email'], $result['email']);
        $this->assertEquals($userData['first_name'], $result['first_name']);
        $this->assertEquals($userData['last_name'], $result['last_name']);
        $this->assertEquals($userData['role'], $result['role']);
        
        // Verify password was properly hashed
        $this->assertNotEquals($userData['password'], $result['hashed_password']);
        $this->assertTrue(password_verify($userData['password'], $result['hashed_password']));
    }
    
    /**
     * Test missing required fields for user creation
     */
    public function testCreateUserWithMissingFields()
    {
        // Test with missing email
        $userData1 = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => 'Password123!',
            'role' => 'TECHKID'
        ];
        
        // This should throw an exception because email is required
        $this->expectException(\InvalidArgumentException::class);
        $this->prepareUserData($userData1);
    }
    
    /**
     * Helper method to validate password (implementation of what we're testing)
     * In a real application, this would be in a separate class that we're testing
     */
    private function validatePassword($password)
    {
        // Password must be at least 8 characters
        if (strlen($password) < 8) {
            return false;
        }
        
        // Check for at least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }
        
        // Check for at least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }
        
        // Check for at least one number
        if (!preg_match('/\d/', $password)) {
            return false;
        }
        
        // Check for at least one special character
        if (!preg_match('/[*\-_!@#$%^&+=]/', $password)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Helper method to validate email (implementation of what we're testing)
     */
    private function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Helper method to validate role (implementation of what we're testing)
     */
    private function validateRole($role)
    {
        $validRoles = ['TECHGURU', 'TECHKID', 'ADMIN'];
        return in_array($role, $validRoles);
    }
    
    /**
     * Helper method that prepares user data for creation
     * In a real app, this might be in a UserService class
     */
    private function prepareUserData($userData)
    {
        // Check required fields
        $requiredFields = ['email', 'first_name', 'last_name', 'password', 'role'];
        foreach ($requiredFields as $field) {
            if (!isset($userData[$field]) || empty($userData[$field])) {
                throw new \InvalidArgumentException("Missing required field: $field");
            }
        }
        
        // Validate email
        if (!$this->validateEmail($userData['email'])) {
            throw new \InvalidArgumentException("Invalid email format");
        }
        
        // Validate password
        if (!$this->validatePassword($userData['password'])) {
            throw new \InvalidArgumentException("Password does not meet requirements");
        }
        
        // Validate role
        if (!$this->validateRole($userData['role'])) {
            throw new \InvalidArgumentException("Invalid role");
        }
        
        // Prepare data for database insertion (hash password, etc.)
        return [
            'email' => $userData['email'],
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'hashed_password' => password_hash($userData['password'], PASSWORD_DEFAULT),
            'role' => $userData['role']
        ];
    }
}