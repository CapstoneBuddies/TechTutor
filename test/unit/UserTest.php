<?php
/**
 * User Test
 * Tests basic user functionality and database connection
 */
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    /**
     * Database connection
     */
    protected $conn;
    
    /**
     * Set up database connection before each test
     */
    protected function setUp(): void
    {
        // First include config.php to get database constants
        if (!defined('DB_HOST')) {
            require_once __DIR__ . '/../../backends/config.php';
        }
        
        // Now create a new database connection for testing
        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
            
            if ($this->conn->connect_error) {
                $this->markTestSkipped('Database connection failed: ' . $this->conn->connect_error);
            }
        } catch (Exception $e) {
            $this->markTestSkipped('Database connection exception: ' . $e->getMessage());
        }
    }
    
    /**
     * Clean up database connection after each test
     */
    protected function tearDown(): void
    {
        if ($this->conn) {
            $this->conn->close();
            $this->conn = null;
        }
    }
    
    /**
     * Sample test to verify PHPUnit is working
     */
    public function testPhpUnitIsWorking()
    {
        $this->assertTrue(true);
    }
    
    /**
     * Test database connection
     */
    public function testDatabaseConnection()
    {
        $this->assertInstanceOf(mysqli::class, $this->conn);
        $this->assertFalse((bool)$this->conn->connect_error);
        
        // Test basic query
        $result = $this->conn->query('SELECT 1');
        $this->assertInstanceOf(mysqli_result::class, $result);
        
        $row = $result->fetch_row();
        $this->assertEquals(1, $row[0]);
    }
    
    /**
     * Test users table exists
     */
    public function testUsersTableExists()
    {
        $result = $this->conn->query("SHOW TABLES LIKE 'users'");
        $this->assertTrue($result->num_rows > 0, 'Users table should exist');
    }
    
    /**
     * Test user structure (fields)
     */
    public function testUserTableStructure()
    {
        $result = $this->conn->query("DESCRIBE users");
        $this->assertInstanceOf(mysqli_result::class, $result);
        
        $fields = [];
        while ($row = $result->fetch_assoc()) {
            $fields[] = $row['Field'];
        }
        
        // Test essential fields exist
        $this->assertContains('uid', $fields, 'Users table should have uid field');
        $this->assertContains('email', $fields, 'Users table should have email field');
        $this->assertContains('password', $fields, 'Users table should have password field');
        $this->assertContains('role', $fields, 'Users table should have role field');
    }
    
    /**
     * Test inserting and retrieving a user
     */
    public function testInsertAndRetrieveUser()
    {
        // Skip this test if we can't set up properly
        if (!$this->conn) {
            $this->markTestSkipped('Database connection not available');
        }
        
        // Generate a unique test email
        $testEmail = 'test_' . time() . '@example.com';
        $testPassword = password_hash('TestPassword123', PASSWORD_DEFAULT);
        $testFirstName = 'Test';
        $testLastName = 'User';
        $testRole = 'TECHKID';
        
        try {
            // Insert test user
            $stmt = $this->conn->prepare("
                INSERT INTO users (email, password, first_name, last_name, role, is_verified, status) 
                VALUES (?, ?, ?, ?, ?, 1, 1)
            ");
            
            $stmt->bind_param('sssss', $testEmail, $testPassword, $testFirstName, $testLastName, $testRole);
            $insertResult = $stmt->execute();
            $stmt->close();
            
            $this->assertTrue($insertResult, 'Should be able to insert a test user');
            
            // Get the inserted user
            $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param('s', $testEmail);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            // Verify the user was inserted correctly
            $this->assertIsArray($user, 'Should retrieve user as array');
            $this->assertEquals($testEmail, $user['email']);
            $this->assertEquals($testRole, $user['role']);
            $this->assertEquals($testFirstName, $user['first_name']);
            $this->assertEquals($testLastName, $user['last_name']);
            
            // Now delete the test user
            $stmt = $this->conn->prepare("DELETE FROM users WHERE email = ?");
            $stmt->bind_param('s', $testEmail);
            $stmt->execute();
            $stmt->close();
            
        } catch (Exception $e) {
            $this->fail('Exception during test: ' . $e->getMessage());
        }
    }
    
    /**
     * Test if test users exist
     */
    public function testTestUsersExist()
    {
        $testEmails = [
            'tutor@test.com' => 'TECHGURU',
            'student@test.com' => 'TECHKID',
            'admin@test.com' => 'ADMIN'
        ];
        
        foreach ($testEmails as $email => $expectedRole) {
            $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            $this->assertNotNull($user, "Test user with email $email should exist");
            if ($user) {
                $this->assertEquals($expectedRole, $user['role'], "User $email should have role $expectedRole");
            }
        }
    }
}
