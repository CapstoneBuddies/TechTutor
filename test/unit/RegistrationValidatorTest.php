<?php
/**
 * Registration Validator Test
 * 
 * This is a unit test for a form validation component that doesn't rely on database connections.
 */
use PHPUnit\Framework\TestCase;

class RegistrationValidatorTest extends TestCase
{
    /**
     * Test complete valid registration data
     */
    public function testValidRegistrationData()
    {
        $formData = [
            'email' => 'test@example.com',
            'first-name' => 'John',
            'last-name' => 'Doe',
            'password' => 'SecureP@ss123',
            'confirm-password' => 'SecureP@ss123',
            'role' => 'TECHKID'
        ];
        
        $validator = new RegistrationValidator();
        $result = $validator->validate($formData);
        
        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['errors']);
    }
    
    /**
     * Test registration with empty fields
     */
    public function testEmptyFieldsValidation()
    {
        // Test with all empty fields
        $emptyData = [
            'email' => '',
            'first-name' => '',
            'last-name' => '',
            'password' => '',
            'confirm-password' => '',
            'role' => ''
        ];
        
        $validator = new RegistrationValidator();
        $result = $validator->validate($emptyData);
        
        $this->assertFalse($result['isValid']);
        $this->assertCount(6, $result['errors']);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertArrayHasKey('first-name', $result['errors']);
        $this->assertArrayHasKey('last-name', $result['errors']);
        $this->assertArrayHasKey('password', $result['errors']);
        $this->assertArrayHasKey('confirm-password', $result['errors']);
        $this->assertArrayHasKey('role', $result['errors']);
    }
    
    /**
     * Test registration with mismatched passwords
     */
    public function testPasswordMismatch()
    {
        $formData = [
            'email' => 'test@example.com',
            'first-name' => 'John',
            'last-name' => 'Doe',
            'password' => 'SecureP@ss123',
            'confirm-password' => 'DifferentP@ss123',
            'role' => 'TECHKID'
        ];
        
        $validator = new RegistrationValidator();
        $result = $validator->validate($formData);
        
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('confirm-password', $result['errors']);
        $this->assertEquals('Password does not match.', $result['errors']['confirm-password']);
    }
    
    /**
     * Test registration with invalid email
     */
    public function testInvalidEmail()
    {
        $formData = [
            'email' => 'not-an-email',
            'first-name' => 'John',
            'last-name' => 'Doe',
            'password' => 'SecureP@ss123',
            'confirm-password' => 'SecureP@ss123',
            'role' => 'TECHKID'
        ];
        
        $validator = new RegistrationValidator();
        $result = $validator->validate($formData);
        
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertEquals('Invalid email format.', $result['errors']['email']);
    }
    
    /**
     * Test password complexity requirements
     */
    public function testPasswordComplexity()
    {
        // Test cases for password validation with expected error messages
        $testCases = [
            // Password too short
            [
                'password' => 'Short1!',
                'error' => 'Password length does not match!'
            ],
            // No uppercase letter
            [
                'password' => 'lowercasep@ss123',
                'error' => 'Password must be 8-16 characters long and contain a mix of letters, numbers, and special characters.'
            ],
            // No lowercase letter
            [
                'password' => 'UPPERCASE@123',
                'error' => 'Password must be 8-16 characters long and contain a mix of letters, numbers, and special characters.'
            ],
            // No number
            [
                'password' => 'PasswordNoNumber!',
                'error' => 'Password must be 8-16 characters long and contain a mix of letters, numbers, and special characters.'
            ],
            // No special character
            [
                'password' => 'Password123',
                'error' => 'Password must be 8-16 characters long and contain a mix of letters, numbers, and special characters.'
            ]
        ];
        
        foreach ($testCases as $index => $testCase) {
            $formData = [
                'email' => 'test@example.com',
                'first-name' => 'John',
                'last-name' => 'Doe',
                'password' => $testCase['password'],
                'confirm-password' => $testCase['password'],
                'role' => 'TECHKID'
            ];
            
            $validator = new RegistrationValidator();
            $result = $validator->validate($formData);
            
            $this->assertFalse($result['isValid'], "Test case $index should fail validation");
            $this->assertArrayHasKey('password', $result['errors']);
            $this->assertEquals($testCase['error'], $result['errors']['password']);
        }
    }
    
    /**
     * Test invalid role validation
     */
    public function testInvalidRole()
    {
        $formData = [
            'email' => 'test@example.com',
            'first-name' => 'John',
            'last-name' => 'Doe',
            'password' => 'SecureP@ss123',
            'confirm-password' => 'SecureP@ss123',
            'role' => 'INVALID_ROLE'
        ];
        
        $validator = new RegistrationValidator();
        $result = $validator->validate($formData);
        
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('role', $result['errors']);
        $this->assertEquals('Invalid user role selected.', $result['errors']['role']);
    }
}

/**
 * Registration Validator Class
 * 
 * This is the class we're testing. In a real application, this would be in a separate file.
 */
class RegistrationValidator
{
    /**
     * Validate registration form data
     * 
     * @param array $data Form data to validate
     * @return array Result with isValid flag and any errors
     */
    public function validate($data)
    {
        $errors = [];
        
        // Check for empty fields
        $requiredFields = ['email', 'first-name', 'last-name', 'password', 'confirm-password', 'role'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[$field] = 'Please fill in all the required fields.';
            }
        }
        
        // If we already have errors, no need to do further validation
        if (!empty($errors)) {
            return [
                'isValid' => false,
                'errors' => $errors
            ];
        }
        
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format.';
        }
        
        // Validate password
        if (strlen($data['password']) < 8) {
            $errors['password'] = 'Password length does not match!';
        } elseif (!preg_match("/^(?=(.*[A-Z]))(?=(.*[a-z]))(?=(.*\d))(?=(.*[*\-_!@#$%^&+=])).[A-Za-z\d*\-_!@#$%^&+=]{8,16}$/", $data['password'])) {
            $errors['password'] = 'Password must be 8-16 characters long and contain a mix of letters, numbers, and special characters.';
        }
        
        // Check if passwords match
        if ($data['password'] !== $data['confirm-password']) {
            $errors['confirm-password'] = 'Password does not match.';
        }
        
        // Validate role
        if (!in_array($data['role'], ['TECHGURU', 'TECHKID'])) {
            $errors['role'] = 'Invalid user role selected.';
        }
        
        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }
}