<?php
require_once '../main.php';
require_once ROOT_PATH . '/backends/handler/paymongo_config.php';
require_once BACKEND . 'transactions_management.php';

/**
 * Handle payment creation request
 * @param mysqli $conn Database connection
 */
function handleCreatePayment($conn) {
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
    $transactionType = isset($_POST['transaction_type']) ? $_POST['transaction_type'] : '';
    $classId = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;

    if ($amount < 100) {
        echo json_encode(['success' => false, 'message' => 'Minimum amount is ₱100']);
        exit;
    }

    // Validate class ID if transaction type is 'class'
    if ($transactionType === 'class' && $classId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid class selection']);
        exit;
    }

    try {
        $payMongo = new PayMongoHelper();
        
        // Create payment intent
        $paymentIntent = $payMongo->createPaymentIntent($amount, $description);
        
        if (isset($paymentIntent['error'])) {
            log_error("PayMongo API Error: " . json_encode($paymentIntent), 'payment_error');
            echo json_encode(['success' => false, 'message' => 'Failed to create payment. Please try again.']);
            exit;
        }

        // Store payment intent in database with transaction type
        $query = "INSERT INTO transactions (user_id, payment_intent_id, amount, currency, status, payment_method_type, description, transaction_type, class_id) 
                 VALUES (?, ?, ?, 'PHP', 'pending', ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('isdssis', 
            $_SESSION['user'],
            $paymentIntent['data']['id'],
            $amount,
            $paymentMethod,
            $description,
            $transactionType,
            $classId
        );
        $stmt->execute();

        if ($stmt->affected_rows <= 0) {
            log_error("Failed to insert transaction record", 'database');
            echo json_encode(['success' => false, 'message' => 'Failed to record transaction. Please try again.']);
            exit;
        }

        // Get the transaction ID
        $transactionId = $conn->insert_id;
        log_error("Created transaction #" . $transactionId . " with payment intent " . $paymentIntent['data']['id'], 'info');

        if ($paymentMethod === 'card') {
            echo json_encode([
                'success' => true,
                'clientKey' => $paymentIntent['data']['id'],
                'transactionId' => $transactionId
            ]);
        } else {
            // For e-wallets, create source and return checkout URL
            $source = $payMongo->createSource([
                'type' => $paymentMethod,
                'amount' => $amount * 100,
                'currency' => 'PHP',
                'redirect' => [
                    'success' => BASE . 'payment-success',
                    'failed' => BASE . 'payment-failed'
                ]
            ]);

            if (isset($source['data']['attributes']['redirect']['checkout_url'])) {
                // Update transaction with source ID
                if (isset($source['data']['id'])) {
                    $query = "UPDATE transactions SET payment_method_id = ? WHERE transaction_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param('si', $source['data']['id'], $transactionId);
                    $stmt->execute();
                }

                echo json_encode([
                    'success' => true,
                    'checkoutUrl' => $source['data']['attributes']['redirect']['checkout_url'],
                    'transactionId' => $transactionId
                ]);
            } else {
                log_error("Failed to create payment source: " . json_encode($source), 'payment_error');
                echo json_encode(['success' => false, 'message' => 'Failed to create payment link']);
            }
        }
    } catch (Exception $e) {
        log_error("Payment creation error: " . $e->getMessage(), 'payment_error');
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
    }
}

/**
 * Handle card payment processing
 * @param mysqli $conn Database connection
 */
function handleCardPayment($conn) {
    $clientKey = isset($_POST['client_key']) ? $_POST['client_key'] : '';
    $cardNumber = isset($_POST['card_number']) ? $_POST['card_number'] : '';
    $expiryDate = isset($_POST['expiry_date']) ? $_POST['expiry_date'] : '';
    $cvv = isset($_POST['cvv']) ? $_POST['cvv'] : '';

    try {
        $payMongo = new PayMongoHelper();
        
        // Clean up card data
        $cardNumber = preg_replace('/\D/', '', $cardNumber); // Remove all non-digits
        
        // Parse expiry date correctly
        $expParts = explode('/', $expiryDate);
        if (count($expParts) !== 2) {
            echo json_encode(['success' => false, 'message' => 'Invalid expiry date format. Use MM/YY']);
            exit;
        }
        
        // Convert to integers as required by PayMongo
        $expMonth = (int)$expParts[0];
        $expYear = (int)('20' . $expParts[1]); // Convert YY to 20YY and make it an integer
        
        // Validate inputs
        if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
            echo json_encode(['success' => false, 'message' => 'Invalid card number length']);
            exit;
        }
        
        if ($expMonth < 1 || $expMonth > 12) {
            echo json_encode(['success' => false, 'message' => 'Invalid expiry month']);
            exit;
        }
        
        // Log formatting for debugging
        log_error("Formatting card details: " . json_encode([
            'card_number' => substr($cardNumber, 0, 4) . '****', // Only log first 4 digits for security
            'exp_month' => $expMonth,
            'exp_year' => $expYear,
            'cvc' => '***' // Don't log CVV
        ]), 'payment_debug');
        
        // Create payment method with properly formatted data
        $paymentMethod = $payMongo->createPaymentMethod([
            'type' => 'card',
            'details' => [
                'card_number' => $cardNumber,
                'exp_month' => $expMonth,
                'exp_year' => $expYear,
                'cvc' => $cvv
            ]
        ]);

        // Check for API error or timeout
        if (isset($paymentMethod['error'])) {
            log_error("Card payment method error: " . json_encode($paymentMethod), 'payment_error');
            echo json_encode(['success' => false, 'message' => 'Payment service is currently unavailable. Please try again later.']);
            exit;
        }

        // Check if we have a valid payment method response
        if (!isset($paymentMethod['data']) || !isset($paymentMethod['data']['id'])) {
            log_error("Invalid payment method response: " . json_encode($paymentMethod), 'payment_error');
            echo json_encode(['success' => false, 'message' => 'Failed to create payment method. Please check your card details and try again.']);
            exit;
        }

        // Attach payment method to intent
        $result = $payMongo->attachPaymentMethod($clientKey, $paymentMethod['data']['id']);
        
        // Check for API error or timeout
        if (isset($result['error'])) {
            log_error("Error attaching payment method: " . json_encode($result), 'payment_error');
            echo json_encode(['success' => false, 'message' => 'Payment service is currently unavailable. Please try again.']);
            exit;
        }

        if (isset($result['data']['attributes']['status']) && $result['data']['attributes']['status'] === 'succeeded') {
            // Find the transaction by payment intent
            $query = "SELECT t.transaction_id, t.user_id, t.amount, t.description, t.transaction_type, t.class_id 
                      FROM transactions t 
                      WHERE t.payment_intent_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('s', $clientKey);
            $stmt->execute();
            $transactionResult = $stmt->get_result();
            
            if ($row = $transactionResult->fetch_assoc()) {
                $transactionId = $row['transaction_id'];
                $userId = $row['user_id'];
                $amount = $row['amount'];
                $description = $row['description'];
                $transactionType = $row['transaction_type'];
                $classId = $row['class_id'];
                
                // Start a transaction for database updates
                $conn->begin_transaction();
                
                try {
                    // Update transaction status
                    $query = "UPDATE transactions SET 
                            status = 'succeeded', 
                            payment_method_id = ?, 
                            updated_at = CURRENT_TIMESTAMP 
                          WHERE transaction_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param('si', $paymentMethod['data']['id'], $transactionId);
                    $stmt->execute();
                    
                    // Check if this is a token purchase
                    if ($transactionType === 'token') {
                        // Convert PHP amount to tokens (1:1 ratio for simplicity, adjust as needed)
                        $tokenAmount = $amount;
                        
                        // Update user's token balance
                        $query = "UPDATE users SET token_balance = token_balance + ? WHERE uid = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param('di', $tokenAmount, $userId);
                        $stmt->execute();
                        
                        // Log the update for debugging
                        log_error("Token balance update for user {$userId}: Amount: {$tokenAmount}", 'tokens');
                        
                        // Store token update message and transaction type in session for post-redirect display
                        $_SESSION['transaction_type'] = 'token';
                        $_SESSION['transaction_amount'] = $tokenAmount;
                        
                        // Create a notification for the user about their token purchase
                        require_once BACKEND . 'management/notifications_management.php';
                        sendNotification(
                            $userId,
                            '', // Send to any role
                            "Your payment of ₱" . number_format($amount, 2) . " was successful! {$tokenAmount} tokens have been added to your account.",
                            BASE . "dashboard", 
                            null, 
                            'bi-coin', 
                            'text-success'
                        );
                    }
                    
                    // Check if this is for class enrollment
                    if ($transactionType === 'class' && $classId > 0) {
                        // Store class info in session for post-redirect display
                        $_SESSION['transaction_type'] = 'class';
                        $_SESSION['class_id'] = $classId;
                        
                        // Try to get class name
                        try {
                            $query = "SELECT class_name FROM class WHERE class_id = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param('i', $classId);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($row = $result->fetch_assoc()) {
                                $_SESSION['class_name'] = $row['class_name'];
                            }
                        } catch (Exception $e) {
                            log_error("Error fetching class details: " . $e->getMessage(), 'class_enrollment');
                        }
                        
                        // Log class enrollment payment
                        log_error("Payment completed for class #{$classId} enrollment for user ID {$userId}", 'class_enrollment');
                    }
                    
                    $conn->commit();
                    log_error("Payment succeeded for transaction #" . $transactionId, 'info');
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    log_error("Error processing successful payment: " . $e->getMessage(), 'payment_error');
                    echo json_encode(['success' => false, 'message' => 'Payment was successful but an error occurred while updating your account.']);
                    exit;
                }
            } else {
                log_error("Could not find transaction for payment intent: " . $clientKey, 'payment_error');
            }

            echo json_encode(['success' => true]);
        } else {
            // Log payment failure reason if available
            if (isset($result['data']['attributes']['last_payment_error'])) {
                $errorMessage = $result['data']['attributes']['last_payment_error'];
                log_error("Payment failed: " . json_encode($errorMessage), 'payment_error');
                
                // Store error message in database for the transaction
                $query = "UPDATE transactions SET 
                        status = 'failed', 
                        error_message = ?,
                        updated_at = CURRENT_TIMESTAMP 
                      WHERE payment_intent_id = ?";
                $stmt = $conn->prepare($query);
                $errorJson = json_encode($errorMessage);
                $stmt->bind_param('ss', $errorJson, $clientKey);
                $stmt->execute();
            }
            
            echo json_encode(['success' => false, 'message' => 'Payment failed. Please try again.']);
        }
    } catch (Exception $e) {
        log_error("Card payment error: " . $e->getMessage(), 'payment_error');
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
    }
}

/**
 * Handle webhook from PayMongo for payment status updates
 * @param mysqli $conn Database connection
 */
function handlePaymentWebhook($conn) {
    // Get the JSON payload
    $payload = file_get_contents('php://input');
    $signature = isset($_SERVER['HTTP_PAYMONGO_SIGNATURE']) ? $_SERVER['HTTP_PAYMONGO_SIGNATURE'] : '';
    
    log_error("Received PayMongo webhook: " . $payload, 'webhooks');
    
    // Verify the webhook signature (in production, implement proper signature verification)
    if (empty($signature)) {
        http_response_code(401);
        exit('Unauthorized');
    }
    
    try {
        $event = json_decode($payload, true);
        
        if (!isset($event['data']['attributes']['type'])) {
            log_error("Invalid webhook payload", 'webhooks');
            http_response_code(400);
            exit('Invalid payload');
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
                
            default:
                log_error("Unhandled webhook event type: " . $eventType, 'webhooks');
        }
        
        http_response_code(200);
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        log_error("Webhook processing error: " . $e->getMessage(), 'webhooks');
        http_response_code(500);
        exit('Internal error');
    }
}

/**
 * Handle source.chargeable webhook event
 */
function handleSourceChargeable($conn, $event) {
    $sourceId = $event['data']['attributes']['data']['id'] ?? null;
    
    if (!$sourceId) {
        log_error("Missing source ID in chargeable event", 'webhooks');
        return;
    }
    
    // Find transaction by source ID
    $query = "SELECT transaction_id FROM transactions WHERE payment_method_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $sourceId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $transactionId = $row['transaction_id'];
        
        // Update transaction status to processing
        $query = "UPDATE transactions SET status = 'processing', updated_at = CURRENT_TIMESTAMP WHERE transaction_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $transactionId);
        $stmt->execute();
        
        log_error("Source chargeable for transaction #" . $transactionId, 'webhooks');
        
        // In production, you would now create a Payment resource to complete the transaction
    } else {
        log_error("Could not find transaction for source: " . $sourceId, 'webhooks');
    }
}

/**
 * Handle payment.paid webhook event
 */
function handlePaymentPaid($conn, $event) {
    $paymentIntentId = $event['data']['attributes']['data']['attributes']['payment_intent_id'] ?? null;
    
    if (!$paymentIntentId) {
        log_error("Missing payment intent ID in paid event", 'webhooks');
        return;
    }
    
    // Find transaction by payment intent ID
    $query = "SELECT t.transaction_id, t.user_id, t.amount, t.description, t.transaction_type, t.class_id 
              FROM transactions t 
              WHERE t.payment_intent_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $paymentIntentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $transactionId = $row['transaction_id'];
        $userId = $row['user_id'];
        $amount = $row['amount'];
        $description = $row['description'];
        $transactionType = $row['transaction_type'];
        $classId = $row['class_id'];
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Update transaction status to succeeded
            $query = "UPDATE transactions SET status = 'succeeded', updated_at = CURRENT_TIMESTAMP WHERE transaction_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $transactionId);
            $stmt->execute();
            
            // Check if this is a token purchase
            if ($transactionType === 'token') {
                // Convert PHP amount to tokens (1:1 ratio for simplicity, adjust as needed)
                $tokenAmount = $amount;
                
                // Update user's token balance
                $query = "UPDATE users SET token_balance = token_balance + ? WHERE uid = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('di', $tokenAmount, $userId);
                $stmt->execute();
                
                // Log token purchase without checking affected rows
                log_error("Token balance update via webhook for user {$userId}: Amount: {$tokenAmount}", 'tokens');
                
                // Create a notification for the user
                require_once BACKEND . 'management/notifications_management.php';
                $notificationMessage = "Your payment of ₱" . number_format($amount, 2) . " was successful! {$tokenAmount} tokens have been added to your account.";
                sendNotification(
                    $userId,
                    '', // Send to any role
                    $notificationMessage,
                    BASE . "dashboard", 
                    null, 
                    'bi-coin', 
                    'text-success'
                );
                
                // No need to set session variables here as webhook doesn't use the redirect flow
            } else {
                // Create a notification for the user
                require_once BACKEND . 'management/notifications_management.php';
                $notificationMessage = "Your payment of ₱" . number_format($amount, 2) . " was successful!";
                sendNotification(
                    $userId,
                    '', // Send to any role
                    $notificationMessage,
                    BASE . "transactions", 
                    $transactionId, 
                    'bi-check-circle', 
                    'text-success'
                );
            }
            
            // Check if this is for class enrollment
            if ($transactionType === 'class' && $classId > 0) {
                // Log payment for class enrollment
                log_error("Payment for class #{$classId} enrollment completed for user ID {$userId}", 'class_enrollment');
                
                // Create a notification for the user
                require_once BACKEND . 'management/notifications_management.php';
                sendNotification(
                    $userId,
                    '', // Send to any role
                    "Your payment for Class #{$classId} enrollment was successful. You can now complete your enrollment.",
                    BASE . "techkid/enroll-class?id={$classId}", 
                    null, 
                    'bi-mortarboard', 
                    'text-success'
                );
            }
            
            $conn->commit();
            log_error("Payment succeeded for transaction #" . $transactionId, 'webhooks');
            
        } catch (Exception $e) {
            $conn->rollback();
            log_error("Error processing successful payment webhook: " . $e->getMessage(), 'webhooks');
        }
    } else {
        log_error("Could not find transaction for payment intent: " . $paymentIntentId, 'webhooks');
    }
}

/**
 * Handle payment.failed webhook event
 */
function handlePaymentFailed($conn, $event) {
    $paymentIntentId = $event['data']['attributes']['data']['attributes']['payment_intent_id'] ?? null;
    
    if (!$paymentIntentId) {
        log_error("Missing payment intent ID in failed event", 'webhooks');
        return;
    }
    
    // Find transaction by payment intent ID
    $query = "SELECT transaction_id FROM transactions WHERE payment_intent_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $paymentIntentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $transactionId = $row['transaction_id'];
        
        // Get error message if available
        $errorMessage = $event['data']['attributes']['data']['attributes']['last_payment_error'] ?? 'Unknown error';
        
        // Update transaction status to failed
        $query = "UPDATE transactions SET status = 'failed', error_message = ?, updated_at = CURRENT_TIMESTAMP WHERE transaction_id = ?";
        $stmt = $conn->prepare($query);
        $errorJson = json_encode($errorMessage);
        $stmt->bind_param('si', $errorJson, $transactionId);
        $stmt->execute();
        
        log_error("Payment failed for transaction #" . $transactionId . ": " . $errorJson, 'webhooks');
    } else {
        log_error("Could not find transaction for payment intent: " . $paymentIntentId, 'webhooks');
    }
}

/**
 * Update user token balance based on payment amount
 * @param mysqli $conn Database connection
 * @param int $userId User ID
 * @param float $amount Payment amount in PHP
 * @param string $transactionId Transaction ID for logging
 */
function updateUserTokenBalance($conn, $userId, $amount, $transactionId) {
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update user's token balance (1:1 ratio for simplicity)
        $tokenAmount = $amount;
        $query = "UPDATE users SET token_balance = token_balance + ? WHERE uid = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('di', $tokenAmount, $userId);
        $stmt->execute();
        
        // Log the token balance update regardless of affected rows
        log_error("Token balance update for user {$userId}: Amount: {$tokenAmount}", 'tokens');
        
        // Set token update message in session
        $_SESSION['token_update'] = "Your account has been credited with {$tokenAmount} tokens!";
        
        // Create a notification for the user
        require_once BACKEND . 'management/notifications_management.php';
        sendNotification(
            $userId,
            '', // Send to any role
            "Your payment of ₱" . number_format($amount, 2) . " was successful! {$tokenAmount} tokens have been added to your account.",
            BASE . "dashboard", 
            null, 
            'bi-coin', 
            'text-success'
        );
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        log_error("Token update error: " . $e->getMessage(), 'tokens');
        return false;
    }
}
log_error(print_r($_POST,true));
// Simple router to handle direct API calls
if (isset($_POST['action'])) {

    $action = $_POST['action'];
    
    switch ($action) {
        case 'create_payment':
            handleCreatePayment($conn);
            break;
            
        case 'process_card_payment':
            handleCardPayment($conn);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], 'webhook') !== false) {
    // Handle webhook
    handlePaymentWebhook($conn);
} else {
    // If no action is specified and not a webhook, redirect to payment page
    header("Location: " . BASE . "payment");
    exit;
}
?>
