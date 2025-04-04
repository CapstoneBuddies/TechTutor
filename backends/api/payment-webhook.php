<?php
require_once '../../backends/main.php';
require_once ROOT_PATH . '/backends/handler/paymongo_config.php';
require_once BACKEND . 'transactions_management.php';

/**
 * PayMongo Webhook Handler
 * 
 * This file processes webhook events from PayMongo to update payment statuses
 * and handle various payment events like successful payments, failed payments,
 * and other status changes.
 */

// Set proper content type for response
header('Content-Type: application/json');

// Get the JSON payload
$payload = file_get_contents('php://input');
$signature = isset($_SERVER['HTTP_PAYMONGO_SIGNATURE']) ? $_SERVER['HTTP_PAYMONGO_SIGNATURE'] : '';

// Log the received webhook
log_error("Received PayMongo webhook: " . $payload, 'webhooks');

// In production, you should verify the webhook signature
// For now we'll just check if it exists
if (empty($signature)) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

try {
    $event = json_decode($payload, true);
    
    if (!isset($event['data']['attributes']['type'])) {
        log_error("Invalid webhook payload", 'webhooks');
        http_response_code(400);
        exit(json_encode(['success' => false, 'message' => 'Invalid payload']));
    }
    
    // Process different event types
    $eventType = $event['data']['attributes']['type'];
    
    switch ($eventType) {
        case 'source.chargeable':
            handleSourceChargeable($conn, $event);
            break;
            
        case 'payment.paid':
            handlePaymentPaid($conn, $event);
            break;
            
        case 'payment.failed':
            handlePaymentFailed($conn, $event);
            break;
            
        case 'payment.refunded':
            handlePaymentRefunded($conn, $event);
            break;
            
        default:
            log_error("Unhandled webhook event type: " . $eventType, 'webhooks');
    }
    
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Webhook processed successfully']);
    
} catch (Exception $e) {
    log_error("Webhook processing error: " . $e->getMessage(), 'webhooks');
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'Internal server error']));
}

/**
 * Handle source.chargeable webhook event
 * This is triggered when an e-wallet payment source is ready to be charged
 */
function handleSourceChargeable($conn, $event) {
    $sourceId = $event['data']['attributes']['data']['id'] ?? null;
    
    if (!$sourceId) {
        log_error("Missing source ID in chargeable event", 'webhooks');
        return;
    }
    
    // Find transaction by source ID
    $query = "SELECT transaction_id, user_id FROM transactions WHERE payment_method_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $sourceId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $transactionId = $row['transaction_id'];
        $userId = $row['user_id'];
        
        // Update transaction status to processing
        $query = "UPDATE transactions SET status = 'processing', updated_at = CURRENT_TIMESTAMP WHERE transaction_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $transactionId);
        $stmt->execute();
        
        log_error("Source chargeable for transaction #" . $transactionId, 'webhooks');
        
        // Create a notification for the user
        sendNotification(
            $userId,
            '', // Send to any role
            "Your payment is being processed", 
            BASE . "transactions", 
            $transactionId, 
            'bi-credit-card', 
            'text-info'
        );
    } else {
        log_error("Could not find transaction for source: " . $sourceId, 'webhooks');
    }
}

/**
 * Handle payment.paid webhook event
 * This is triggered when a payment is successfully processed
 */
function handlePaymentPaid($conn, $event) {
    $paymentIntentId = $event['data']['attributes']['data']['attributes']['payment_intent_id'] ?? null;
    
    if (!$paymentIntentId) {
        log_error("Missing payment intent ID in paid event", 'webhooks');
        return;
    }
    
    // Find transaction by payment intent ID
    $query = "SELECT transaction_id, user_id, amount, description FROM transactions WHERE payment_intent_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $paymentIntentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $transactionId = $row['transaction_id'];
        $userId = $row['user_id'];
        $amount = $row['amount'];
        $description = $row['description'];
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Update transaction status to succeeded
            $query = "UPDATE transactions SET status = 'succeeded', updated_at = CURRENT_TIMESTAMP WHERE transaction_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $transactionId);
            $stmt->execute();
            
            // Check if this is a token purchase and update user's token balance
            if (strpos(strtolower($description), 'token') !== false) {
                // Convert PHP amount to tokens (1:1 ratio for simplicity, adjust as needed)
                $tokenAmount = $amount;
                
                // Update user's token balance
                $query = "UPDATE users SET token_balance = token_balance + ? WHERE uid = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('di', $tokenAmount, $userId);
                $stmt->execute();
                
                // Log token purchase
                log_error("Added {$tokenAmount} tokens to user ID {$userId} from transaction #{$transactionId}", 'tokens');
            }
            
            // Check if this is for class enrollment and process it
            if (strpos(strtolower($description), 'class') !== false && strpos(strtolower($description), 'enrollment') !== false) {
                // Extract class ID from description if available
                preg_match('/class\s+#?(\d+)/i', $description, $matches);
                if (isset($matches[1])) {
                    $classId = $matches[1];
                    
                    // Process class enrollment
                    // This would call your class enrollment function
                    log_error("Payment for class #{$classId} enrollment completed for user ID {$userId}", 'class_enrollment');
                    
                    // You would add additional logic here to complete the enrollment
                }
            }
            
            // Create a notification for the user
            sendNotification(
                $userId,
                '', // Send to any role
                "Your payment of ₱" . number_format($amount, 2) . " was successful", 
                BASE . "transactions", 
                $transactionId, 
                'bi-check-circle', 
                'text-success'
            );
            
            $conn->commit();
            log_error("Payment succeeded for transaction #" . $transactionId, 'webhooks');
            
        } catch (Exception $e) {
            $conn->rollback();
            log_error("Error processing successful payment: " . $e->getMessage(), 'webhooks');
        }
    } else {
        log_error("Could not find transaction for payment intent: " . $paymentIntentId, 'webhooks');
    }
}

/**
 * Handle payment.failed webhook event
 * This is triggered when a payment fails to process
 */
function handlePaymentFailed($conn, $event) {
    $paymentIntentId = $event['data']['attributes']['data']['attributes']['payment_intent_id'] ?? null;
    
    if (!$paymentIntentId) {
        log_error("Missing payment intent ID in failed event", 'webhooks');
        return;
    }
    
    // Find transaction by payment intent ID
    $query = "SELECT transaction_id, user_id, amount FROM transactions WHERE payment_intent_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $paymentIntentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $transactionId = $row['transaction_id'];
        $userId = $row['user_id'];
        $amount = $row['amount'];
        
        // Get error message if available
        $errorMessage = $event['data']['attributes']['data']['attributes']['last_payment_error'] ?? 'Unknown error';
        
        // Update transaction status to failed
        $query = "UPDATE transactions SET status = 'failed', error_message = ?, updated_at = CURRENT_TIMESTAMP WHERE transaction_id = ?";
        $stmt = $conn->prepare($query);
        $errorJson = json_encode($errorMessage);
        $stmt->bind_param('si', $errorJson, $transactionId);
        $stmt->execute();
        
        // Create a notification for the user
        sendNotification(
            $userId,
            '', // Send to any role
            "Your payment of ₱" . number_format($amount, 2) . " failed. Please try again.", 
            BASE . "payment", 
            null, 
            'bi-exclamation-circle', 
            'text-danger'
        );
        
        log_error("Payment failed for transaction #" . $transactionId . ": " . $errorJson, 'webhooks');
    } else {
        log_error("Could not find transaction for payment intent: " . $paymentIntentId, 'webhooks');
    }
}

/**
 * Handle payment.refunded webhook event
 * This is triggered when a payment is refunded
 */
function handlePaymentRefunded($conn, $event) {
    $paymentIntentId = $event['data']['attributes']['data']['attributes']['payment_intent_id'] ?? null;
    
    if (!$paymentIntentId) {
        log_error("Missing payment intent ID in refunded event", 'webhooks');
        return;
    }
    
    // Find transaction by payment intent ID
    $query = "SELECT transaction_id, user_id, amount FROM transactions WHERE payment_intent_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $paymentIntentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $transactionId = $row['transaction_id'];
        $userId = $row['user_id'];
        $amount = $row['amount'];
        
        // Update transaction status to refunded
        $query = "UPDATE transactions SET status = 'refunded', updated_at = CURRENT_TIMESTAMP WHERE transaction_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $transactionId);
        $stmt->execute();
        
        // Create a notification for the user
        sendNotification(
            $userId,
            '', // Send to any role
            "Your payment of ₱" . number_format($amount, 2) . " has been refunded", 
            BASE . "transactions", 
            $transactionId, 
            'bi-arrow-return-left', 
            'text-warning'
        );
        
        log_error("Payment refunded for transaction #" . $transactionId, 'webhooks');
    } else {
        log_error("Could not find transaction for payment intent: " . $paymentIntentId, 'webhooks');
    }
}
?>

