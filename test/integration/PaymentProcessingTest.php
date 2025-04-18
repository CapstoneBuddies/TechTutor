<?php
/**
 * Payment Processing Integration Test
 * 
 * This test demonstrates testing functionality that spans multiple components.
 * It shows how the PaymentGateway mock can be used in integration tests.
 */
use PHPUnit\Framework\TestCase;

class PaymentProcessingTest extends TestCase
{
    protected $paymentGateway;
    protected $db;
    
    /**
     * Set up test dependencies
     */
    protected function setUp(): void
    {
        // Include database connection
        require_once __DIR__ . '/../../backends/config.php';
        
        // Create a database connection
        $this->db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
        
        if ($this->db->connect_error) {
            $this->fail("Database connection failed: " . $this->db->connect_error);
        }
        
        // Load our mock payment gateway
        require_once __DIR__ . '/../mocks/PaymentGateway.php';
        $this->paymentGateway = new PaymentGateway();
    }
    
    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        if ($this->db) {
            $this->db->close();
        }
    }
    
    /**
     * Test a payment workflow integrated with the database
     * This demonstrates how to test functionality that spans multiple components
     */
    public function testPaymentWorkflowWithDatabase()
    {
        // 1. Create a test user if needed
        $testUserEmail = 'test_payment_user_' . time() . '@example.com';
        $testUserPassword = password_hash('TestPass123', PASSWORD_DEFAULT);
        $testUserId = $this->createTestUser($testUserEmail, $testUserPassword);
        
        $this->assertNotNull($testUserId, "Test user should be created successfully");
        
        try {
            // 2. Process a payment
            $paymentData = [
                'amount' => 150.00,
                'currency' => 'PHP',
                'card_details' => [
                    'card_number' => '4111111111111111',
                    'expiry_month' => '12',
                    'expiry_year' => date('Y') + 1,
                    'cvv' => '123'
                ],
                'user_id' => $testUserId
            ];
            
            // In a real application, you would call your payment service
            // For this test, we'll use our mock directly
            $paymentResult = $this->paymentGateway->processPayment(
                $paymentData['amount'],
                $paymentData['currency'],
                $paymentData['card_details']
            );
            
            $this->assertTrue($paymentResult['success'], "Payment should be processed successfully");
            
            // 3. Record the transaction in the database
            $transactionId = $this->recordTransaction(
                $testUserId,
                $paymentResult['transaction_id'],
                $paymentData['amount'],
                $paymentData['currency']
            );
            
            $this->assertNotNull($transactionId, "Transaction should be recorded in database");
            
            // 4. Verify the transaction exists in the database
            $transaction = $this->getTransaction($transactionId);
            $this->assertNotNull($transaction, "Transaction should exist in database");
            $this->assertEquals($paymentData['amount'], $transaction['amount']);
            $this->assertEquals($testUserId, $transaction['user_id']);
            
            // 5. Process a refund
            $refundResult = $this->paymentGateway->refundTransaction(
                $paymentResult['transaction_id'],
                $paymentData['amount']
            );
            
            $this->assertTrue($refundResult['success'], "Refund should be processed successfully");
            
            // 6. Update transaction status in database
            $this->updateTransactionStatus($transactionId, 'failed'); // Using 'failed' since there's no 'refunded' status
            
            // 7. Verify transaction status was updated
            $updatedTransaction = $this->getTransaction($transactionId);
            $this->assertEquals('failed', $updatedTransaction['status']); // Using 'failed' since there's no 'refunded' status
            
        } finally {
            // Clean up - remove test data
            $this->cleanupTestData($testUserId, $transactionId ?? null);
        }
    }
    
    /**
     * Helper method to create a test user
     */
    private function createTestUser($email, $password)
    {
        // Check if transactions table exists
        $result = $this->db->query("SHOW TABLES LIKE 'users'");
        if ($result->num_rows === 0) {
            $this->markTestSkipped("Users table does not exist - skipping test");
            return null;
        }
        
        $firstName = 'Test';
        $lastName = 'User';
        $role = 'TECHKID';
        
        $stmt = $this->db->prepare("INSERT INTO users (email, password, first_name, last_name, role, is_verified, status) VALUES (?, ?, ?, ?, ?, 1, 1)");
        if (!$stmt) {
            $this->fail("Failed to prepare statement: " . $this->db->error);
        }
        
        $stmt->bind_param("sssss", $email, $password, $firstName, $lastName, $role);
        
        if ($stmt->execute()) {
            $userId = $this->db->insert_id;
            $stmt->close();
            return $userId;
        } else {
            $stmt->close();
            $this->fail("Failed to create test user: " . $this->db->error);
            return null;
        }
    }
    
    /**
     * Helper method to record a transaction
     * This method has been updated to match your actual transactions table structure
     */
    private function recordTransaction($userId, $paymentIntentId, $amount, $currency)
    {
        // Check if transactions table exists
        $result = $this->db->query("SHOW TABLES LIKE 'transactions'");
        if ($result->num_rows === 0) {
            // If we don't have a transactions table, we'll skip this test
            $this->markTestSkipped("Transactions table does not exist - skipping test");
            return null;
        }
        
        // Check the structure of the transactions table
        $result = $this->db->query("DESCRIBE transactions");
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        // If we're missing required columns, we need to adapt our query
        if (!in_array('payment_intent_id', $columns)) {
            $this->markTestSkipped("Transactions table doesn't have the expected structure - skipping test");
            return null;
        }
        
        $status = 'succeeded'; // Using the status from your schema
        $paymentMethodType = 'credit_card';
        $description = 'Test payment transaction';
        $transactionType = 'token';
        
        $stmt = $this->db->prepare("
            INSERT INTO transactions (
                user_id, 
                payment_intent_id, 
                amount, 
                currency, 
                status, 
                payment_method_type,
                description,
                transaction_type
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            $this->fail("Failed to prepare statement: " . $this->db->error);
        }
        
        $stmt->bind_param("isdsssss", 
            $userId, 
            $paymentIntentId, 
            $amount, 
            $currency, 
            $status,
            $paymentMethodType,
            $description,
            $transactionType
        );
        
        if ($stmt->execute()) {
            $transactionId = $this->db->insert_id;
            $stmt->close();
            return $transactionId;
        } else {
            $stmt->close();
            $this->fail("Failed to record transaction: " . $this->db->error);
            return null;
        }
    }
    
    /**
     * Helper method to get a transaction from the database
     */
    private function getTransaction($transactionId)
    {
        $stmt = $this->db->prepare("SELECT * FROM transactions WHERE transaction_id = ?");
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $transaction = $result->fetch_assoc();
        
        $stmt->close();
        return $transaction;
    }
    
    /**
     * Helper method to update a transaction status
     */
    private function updateTransactionStatus($transactionId, $status)
    {
        $stmt = $this->db->prepare("UPDATE transactions SET status = ? WHERE transaction_id = ?");
        $stmt->bind_param("si", $status, $transactionId);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Helper method to clean up test data
     */
    private function cleanupTestData($userId, $transactionId)
    {
        if ($transactionId) {
            $stmt = $this->db->prepare("DELETE FROM transactions WHERE transaction_id = ?");
            $stmt->bind_param("i", $transactionId);
            $stmt->execute();
            $stmt->close();
        }
        
        if ($userId) {
            $stmt = $this->db->prepare("DELETE FROM users WHERE uid = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
        }
    }
}
