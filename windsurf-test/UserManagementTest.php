<?php
use PHPUnit\Framework\TestCase;

/**
 * Test case for user management functions
 * Tests functions from user_management.php
 */
class UserManagementTest extends TestCase
{
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }
    
    protected function setUp(): void
    {
        parent::setUp();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        session_unset();
        session_destroy();
    }
    
    /**
     * Test normalizeStatus function
     */
    public function testNormalizeStatus()
    {
        $this->assertEquals('active', normalizeStatus(1));
        $this->assertEquals('inactive', normalizeStatus(0));
        $this->assertEquals('inactive', normalizeStatus(null));
    }
    
    /**
     * Test getStatusBadgeClass function
     */
    public function testGetStatusBadgeClass()
    {
        $this->assertEquals('status-badge status-active', getStatusBadgeClass(1));
        $this->assertEquals('status-badge status-inactive', getStatusBadgeClass(0));
    }
    
    /**
     * Test user role validation 
     * Based on known role enum values: ADMIN, TECHGURU, TECHKID
     */
    public function testUserRoleValidation()
    {
        $validRoles = ['ADMIN', 'TECHGURU', 'TECHKID'];
        
        foreach ($validRoles as $role) {
            $this->assertTrue($this->isValidRole($role), "Role $role should be valid");
        }
        
        $invalidRoles = ['admin', 'techguru', 'teacher', 'student', '', null];
        
        foreach ($invalidRoles as $role) {
            $this->assertFalse($this->isValidRole($role), "Role $role should be invalid");
        }
    }
    
    /**
     * Helper function to validate user roles
     */
    private function isValidRole($role)
    {
        return in_array($role, ['ADMIN', 'TECHGURU', 'TECHKID']);
    }
    
    /**
     * Test email validation function
     */
    public function testEmailValidation()
    {
        // Valid email formats
        $validEmails = [
            'user@example.com',
            'user.name@example.com',
            'user+tag@example.com',
            'user@subdomain.example.com'
        ];
        
        foreach ($validEmails as $email) {
            $this->assertTrue($this->validateEmail($email), "Email $email should be valid");
        }
        
        // Invalid email formats
        $invalidEmails = [
            'user@',
            '@example.com',
            'user@.com',
            'user@example.',
            'user example.com',
            '',
            null
        ];
        
        foreach ($invalidEmails as $email) {
            $this->assertFalse($this->validateEmail($email), "Email $email should be invalid");
        }
    }
    
    /**
     * Helper function to validate email
     */
    private function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Test password validation based on requirements
     * Password requirements: 8-16 chars, mix of letters, numbers, and special chars
     */
    public function testPasswordValidation()
    {
        // Valid passwords
        $validPasswords = [
            'Abc123!!',
            'Password123!',
            'TestPass1*',
            'Secure@2023'
        ];
        
        foreach ($validPasswords as $password) {
            $this->assertTrue($this->validatePassword($password), "Password $password should be valid");
        }
        
        // Invalid passwords
        $invalidPasswords = [
            'abc123', // too short
            'password', // no numbers or special chars
            'PASSWORD123', // no lowercase
            'password123', // no uppercase
            'Password!', // no numbers
            'Pass123', // too short
            'ThisPasswordIsTooLong123!' // too long
        ];
        
        foreach ($invalidPasswords as $password) {
            $this->assertFalse($this->validatePassword($password), "Password $password should be invalid");
        }
    }
    
    /**
     * Helper function to validate password
     */
    private function validatePassword($password)
    {
        // Password must be 8-16 characters with mix of uppercase, lowercase, numbers, and special chars
        return preg_match("/^(?=(.*[A-Z]))(?=(.*[a-z]))(?=(.*\d))(?=(.*[*\-_!]))[\w*\-_!]{8,16}$/", $password) === 1;
    }
    
    /**
     * Test user status management
     */
    public function testUserStatusManagement()
    {
        // Test conversion functions
        $this->assertEquals('active', $this->getUserStatusText(1));
        $this->assertEquals('inactive', $this->getUserStatusText(0));
        
        // Test status class generation
        $this->assertEquals('badge bg-success', $this->getStatusBadge(1));
        $this->assertEquals('badge bg-danger', $this->getStatusBadge(0));
    }
    
    /**
     * Helper function to get status text
     */
    private function getUserStatusText($status)
    {
        return $status == 1 ? 'active' : 'inactive';
    }
    
    /**
     * Helper function to get status badge class
     */
    private function getStatusBadge($status)
    {
        $statusText = $this->getUserStatusText($status);
        $badgeClass = $statusText == 'active' ? 'bg-success' : 'bg-danger';
        return 'badge ' . $badgeClass;
    }
}
