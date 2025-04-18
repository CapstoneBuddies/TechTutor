<?php
/**
 * Mock Payment Gateway for testing
 * This class simulates a payment gateway for testing payment functionality
 * without making actual API calls
 */
class PaymentGateway
{
    /**
     * Process a payment
     * 
     * @param float $amount The payment amount
     * @param string $currency The currency code
     * @param array $cardDetails The card details
     * @return array Response with success status and transaction ID or error
     */
    public function processPayment($amount, $currency, $cardDetails = [])
    {
        // Simulate card validation
        if (!$this->validateCard($cardDetails)) {
            return [
                'success' => false,
                'error' => 'Invalid card details',
                'error_code' => 'INVALID_CARD'
            ];
        }
        
        // Simulate payment processing
        // In a real gateway, this would make an API call
        
        // Generate a test transaction ID
        $transactionId = 'test_' . uniqid();
        
        // Simulate successful payment
        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'currency' => $currency,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Validate card details
     * 
     * @param array $cardDetails The card details to validate
     * @return bool True if valid, false otherwise
     */
    private function validateCard($cardDetails)
    {
        // Basic validation for testing
        if (empty($cardDetails['card_number']) || 
            empty($cardDetails['expiry_month']) || 
            empty($cardDetails['expiry_year']) || 
            empty($cardDetails['cvv'])) {
            return false;
        }
        
        // Check if card number is valid (simplified for testing)
        // In real tests, you might want more sophisticated validation
        $cardNumber = preg_replace('/\D/', '', $cardDetails['card_number']);
        if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
            return false;
        }
        
        // Check if card is not expired
        $currentYear = (int)date('Y');
        $currentMonth = (int)date('m');
        $expiryYear = (int)$cardDetails['expiry_year'];
        $expiryMonth = (int)$cardDetails['expiry_month'];
        
        if ($expiryYear < $currentYear || 
            ($expiryYear == $currentYear && $expiryMonth < $currentMonth)) {
            return false;
        }
        
        // Simulate valid card prefixes (for testing)
        $validPrefixes = ['4', '5', '3', '6']; // Visa, Mastercard, Amex, Discover
        if (!in_array(substr($cardNumber, 0, 1), $validPrefixes)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Verify a transaction
     * 
     * @param string $transactionId The transaction ID to verify
     * @return array Transaction details or error
     */
    public function verifyTransaction($transactionId)
    {
        // Simulate transaction verification
        if (strpos($transactionId, 'test_') !== 0) {
            return [
                'success' => false,
                'error' => 'Invalid transaction ID',
                'error_code' => 'INVALID_TRANSACTION'
            ];
        }
        
        // Simulate successful verification
        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'status' => 'completed',
            'amount' => 100.00,
            'currency' => 'PHP',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ];
    }
    
    /**
     * Refund a transaction
     * 
     * @param string $transactionId The transaction ID to refund
     * @param float $amount The amount to refund (optional, defaults to full amount)
     * @return array Refund details or error
     */
    public function refundTransaction($transactionId, $amount = null)
    {
        // Simulate transaction verification
        if (strpos($transactionId, 'test_') !== 0) {
            return [
                'success' => false,
                'error' => 'Invalid transaction ID',
                'error_code' => 'INVALID_TRANSACTION'
            ];
        }
        
        // Simulate successful refund
        return [
            'success' => true,
            'refund_id' => 'refund_' . uniqid(),
            'transaction_id' => $transactionId,
            'amount' => $amount ?? 100.00,
            'currency' => 'PHP',
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}