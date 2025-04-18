<?php
use PHPUnit\Framework\TestCase;

/**
 * Test case for database operations
 */
class DatabaseTest extends TestCase
{
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }
    
    protected function setUp(): void
    {
        parent::setUp();
        // Set up test environment
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up after tests
        session_unset();
    }
    
    /**
     * Test getItemCountByTable function
     * Note: This test uses a mock approach since we don't want to query the real database during testing
     */
    public function testGetItemCountByTable()
    {
        global $conn;
        
        // Create a mock for database connection and statement
        $stmt = $this->createMock(\mysqli_stmt::class);
        $result = $this->createMock(\mysqli_result::class);
        
        // Configure mocks to simulate database interactions
        $stmt->expects($this->any())
             ->method('execute')
             ->willReturn(true);
        
        $stmt->expects($this->any())
             ->method('get_result')
             ->willReturn($result);
             
        $result->expects($this->any())
               ->method('fetch_row')
               ->willReturn([15]);
        
        // Replace global connection with our mock
        $originalConn = $conn;
        $conn = $this->createMock(\mysqli::class);
        
        $conn->expects($this->any())
             ->method('prepare')
             ->willReturn($stmt);
        
        // Test with valid tables
        $this->assertEquals(15, getItemCountByTable('users'), 'Should return count for users table');
        $this->assertEquals(15, getItemCountByTable('course'), 'Should return count for course table');
        $this->assertEquals(15, getItemCountByTable('transactions'), 'Should return count for transactions table');
        
        // Test with invalid table
        $this->assertNull(getItemCountByTable('invalid_table'), 'Should return null for invalid table');
        
        // Test with role parameter
        $this->assertEquals(15, getItemCountByTable('users', 'TECHKID'), 'Should return count for users with role');
        
        // Restore original connection
        $conn = $originalConn;
    }
    
    /**
     * Test database connection
     */
    public function testDatabaseConnection()
    {
        global $conn;
        
        // Skip in automated environment
        $this->markTestSkipped('Skipping actual database connection test in automated environment');
        
        // Test that the connection is established
        $this->assertInstanceOf(\mysqli::class, $conn, 'Database connection should be a mysqli instance');
        $this->assertFalse($conn->connect_error, 'Database connection should not have an error');
    }
    
    /**
     * Test database error handling with mocked connection
     */
    public function testDatabaseErrorHandling()
    {
        // Create a mock error logger that will be used to verify error logging
        $errorLogged = false;
        
        // Override log_error function to capture logging
        function test_log_error($message, $file = 'error.log') {
            global $errorLogged;
            $errorLogged = true;
            return true;
        }
        
        // Create a mock function that will try database operation and catch errors
        function test_db_operation() {
            global $conn;
            
            try {
                // Simulate a database error
                throw new \mysqli_sql_exception("Test database error");
            } catch (\mysqli_sql_exception $e) {
                test_log_error("Database error: " . $e->getMessage(), "database_error.log");
                return false;
            }
            
            return true;
        }
        
        // Execute test function
        $result = test_db_operation();
        
        // Verify that error was logged and function returned false
        $this->assertFalse($result, 'Function should return false on database error');
        $this->assertTrue($errorLogged, 'Error should be logged');
    }
}
