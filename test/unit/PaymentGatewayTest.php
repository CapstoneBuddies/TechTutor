<?php
/**
 * Payment Gateway Tests
 * Demonstrates how to test payment processing using the mock PaymentGateway
 */
use PHPUnit\Framework\TestCase;

class PaymentGatewayTest extends TestCase
{
    protected $paymentGateway;
    
    /**
     * Set up the payment gateway mock before each test
     */
    protected function setUp(): void
    {
        // Create an instance of our mock payment gateway
        require_once __DIR__ . '/../mocks/PaymentGateway.php';
        $this->paymentGateway = new PaymentGateway();
    }
    
    /**
     * Test successful payment processing
     */
    public function testSuccessfulPaymentProcessing()
    {
        // Arrange - prepare test data
        $amount = 100.00;
        $currency = 'PHP';
        $cardDetails = [
            'card_number' => '4111111111111111', // Valid Visa test number
            'expiry_month' => '12',
            'expiry_year' => date('Y') + 1, // Next year
            'cvv' => '123'
        ];
        
        // Act - process the payment
        $result = $this->paymentGateway->processPayment($amount, $currency, $cardDetails);
        
        // Assert - verify the results
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('transaction_id', $result);
        $this->assertStringStartsWith('test_', $result['transaction_id']);
        $this->assertEquals($amount, $result['amount']);
        $this->assertEquals($currency, $result['currency']);
    }
    
    /**
     * Test payment with invalid card number
     */
    public function testPaymentWithInvalidCardNumber()
    {
        // Arrange - invalid card number (too short)
        $cardDetails = [
            'card_number' => '411111', // Invalid - too short
            'expiry_month' => '12',
            'expiry_year' => date('Y') + 1,
            'cvv' => '123'
        ];
        
        // Act
        $result = $this->paymentGateway->processPayment(100.00, 'PHP', $cardDetails);
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid card details', $result['error']);
        $this->assertEquals('INVALID_CARD', $result['error_code']);
    }
    
    /**
     * Test payment with expired card
     */
    public function testPaymentWithExpiredCard()
    {
        // Arrange - expired card
        $cardDetails = [
            'card_number' => '4111111111111111',
            'expiry_month' => '01',
            'expiry_year' => date('Y') - 1, // Last year
            'cvv' => '123'
        ];
        
        // Act
        $result = $this->paymentGateway->processPayment(100.00, 'PHP', $cardDetails);
        
        // Assert
        $this->assertFalse($result['success']);
    }
    
    /**
     * Test payment with missing card details
     */
    public function testPaymentWithMissingCardDetails()
    {
        // Test with various missing card details
        $testCases = [
            // Missing card number
            [
                'expiry_month' => '12',
                'expiry_year' => date('Y') + 1,
                'cvv' => '123'
            ],
            // Missing expiry month
            [
                'card_number' => '4111111111111111',
                'expiry_year' => date('Y') + 1,
                'cvv' => '123'
            ],
            // Missing expiry year
            [
                'card_number' => '4111111111111111',
                'expiry_month' => '12',
                'cvv' => '123'
            ],
            // Missing CVV
            [
                'card_number' => '4111111111111111',
                'expiry_month' => '12',
                'expiry_year' => date('Y') + 1
            ]
        ];
        
        foreach ($testCases as $index => $cardDetails) {
            $result = $this->paymentGateway->processPayment(100.00, 'PHP', $cardDetails);
            $this->assertFalse($result['success'], "Test case $index should fail with missing details");
        }
    }
    
    /**
     * Test verification of a valid transaction
     */
    public function testVerifyValidTransaction()
    {
        // First create a transaction
        $cardDetails = [
            'card_number' => '4111111111111111',
            'expiry_month' => '12',
            'expiry_year' => date('Y') + 1,
            'cvv' => '123'
        ];
        
        $paymentResult = $this->paymentGateway->processPayment(100.00, 'PHP', $cardDetails);
        $transactionId = $paymentResult['transaction_id'];
        
        // Now verify the transaction
        $verificationResult = $this->paymentGateway->verifyTransaction($transactionId);
        
        $this->assertTrue($verificationResult['success']);
        $this->assertEquals($transactionId, $verificationResult['transaction_id']);
        $this->assertEquals('completed', $verificationResult['status']);
    }
    
    /**
     * Test verification of an invalid transaction
     */
    public function testVerifyInvalidTransaction()
    {
        $result = $this->paymentGateway->verifyTransaction('invalid_tx_123');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid transaction ID', $result['error']);
    }
    
    /**
     * Test refunding a transaction
     */
    public function testRefundTransaction()
    {
        // First create a transaction
        $cardDetails = [
            'card_number' => '4111111111111111',
            'expiry_month' => '12',
            'expiry_year' => date('Y') + 1,
            'cvv' => '123'
        ];
        
        $paymentResult = $this->paymentGateway->processPayment(100.00, 'PHP', $cardDetails);
        $transactionId = $paymentResult['transaction_id'];
        
        // Test full refund
        $fullRefundResult = $this->paymentGateway->refundTransaction($transactionId);
        $this->assertTrue($fullRefundResult['success']);
        $this->assertEquals($transactionId, $fullRefundResult['transaction_id']);
        $this->assertEquals(100.00, $fullRefundResult['amount']);
        
        // Test partial refund
        $partialRefundAmount = 50.00;
        $partialRefundResult = $this->paymentGateway->refundTransaction($transactionId, $partialRefundAmount);
        $this->assertTrue($partialRefundResult['success']);
        $this->assertEquals($partialRefundAmount, $partialRefundResult['amount']);
    }
    
    /**
     * Test refunding an invalid transaction
     */
    public function testRefundInvalidTransaction()
    {
        $result = $this->paymentGateway->refundTransaction('invalid_tx_123');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid transaction ID', $result['error']);
    }
    
    /**
     * Test a complete payment workflow
     */
    public function testCompletePaymentWorkflow()
    {
        // This is an integration test that tests the entire payment flow
        
        // 1. Process payment
        $amount = 250.75;
        $currency = 'PHP';
        $cardDetails = [
            'card_number' => '5555555555554444', // Mastercard test number
            'expiry_month' => '12',
            'expiry_year' => date('Y') + 1,
            'cvv' => '123'
        ];
        
        $paymentResult = $this->paymentGateway->processPayment($amount, $currency, $cardDetails);
        
        $this->assertTrue($paymentResult['success']);
        $transactionId = $paymentResult['transaction_id'];
        
        // 2. Verify the transaction
        $verificationResult = $this->paymentGateway->verifyTransaction($transactionId);
        $this->assertTrue($verificationResult['success']);
        $this->assertEquals('completed', $verificationResult['status']);
        
        // 3. Process a partial refund
        $refundAmount = 50.25;
        $partialRefundResult = $this->paymentGateway->refundTransaction($transactionId, $refundAmount);
        $this->assertTrue($partialRefundResult['success']);
        $this->assertEquals($refundAmount, $partialRefundResult['amount']);
        
        // 4. Process a full refund of remaining amount
        $remainingAmount = $amount - $refundAmount;
        $finalRefundResult = $this->paymentGateway->refundTransaction($transactionId, $remainingAmount);
        $this->assertTrue($finalRefundResult['success']);
        
        // In a real system, we might verify that the transaction is now fully refunded
    }
}