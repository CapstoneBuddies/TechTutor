<?php
require_once __DIR__.'/../main.php';
require_once ROOT_PATH . '/backends/handler/paymongo_config.php';
require_once BACKEND . 'transactions_management.php';

/**
 * Check if the user has pending or processing transactions within the last 15 minutes
 * Also check for recent completed transactions to avoid double charges
 * @param mysqli $conn Database connection
 * @param int $userId User ID to check
 * @return array Status and any pending transaction details
 */
function checkRecentPendingTransactions($conn, $userId) {
    // Initialize return array with default values to prevent undefined index errors
    $returnData = [
        'hasPending' => false,
        'hasRecentSuccess' => false
    ];
    
    try {
        // Check for any pending or processing transactions created in the last 15 minutes
        $query = "SELECT transaction_id, payment_intent_id, payment_method_id, amount, status, description, 
                  created_at, updated_at, 
                  TIMESTAMPDIFF(MINUTE, created_at, NOW()) as minutes_ago,
                  TIMESTAMPDIFF(MINUTE, updated_at, NOW()) as minutes_since_update
                  FROM transactions 
                  WHERE user_id = ? 
                  AND (
                      (status IN ('pending', 'processing') AND 
                       (created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE) OR
                        updated_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)))
                      OR 
                      (status = 'succeeded' AND updated_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE))
                  )
                  ORDER BY updated_at DESC, created_at DESC 
                  LIMIT 1";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $minutesAgo = $row['minutes_ago'];
            $minutesSinceUpdate = $row['minutes_since_update'];
            $status = $row['status'];
            
            // For pending/processing transactions, calculate remaining time
            if ($status == 'pending' || $status == 'processing') {
                $minutesRemaining = 15 - min($minutesAgo, $minutesSinceUpdate);
                
                $returnData['hasPending'] = true;
                $returnData['transaction'] = [
                    'id' => $row['transaction_id'],
                    'payment_intent_id' => $row['payment_intent_id'],
                    'payment_method_id' => $row['payment_method_id'],
                    'amount' => $row['amount'],
                    'status' => $status,
                    'description' => $row['description'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                    'minutes_ago' => $minutesAgo,
                    'minutes_since_update' => $minutesSinceUpdate,
                    'minutes_remaining' => $minutesRemaining
                ];
                
                return $returnData;
            } 
            // For succeeded transactions, check with PayMongo to avoid double payments
            else if ($status == 'succeeded') {
                // If there's a payment intent ID, verify with PayMongo
                if (!empty($row['payment_intent_id']) && class_exists('PayMongoHelper')) {
                    try {
                        $payMongo = new PayMongoHelper();
                        $statusCheck = $payMongo->checkPaymentIntentStatus($row['payment_intent_id']);
                        
                        // If payment was actually successful, warn about recent payment
                        if ($statusCheck['exists'] && $statusCheck['status'] === 'succeeded') {
                            $minutesSinceSuccess = $minutesSinceUpdate;
                            
                            $returnData['hasRecentSuccess'] = true;
                            $returnData['transaction'] = [
                                'id' => $row['transaction_id'],
                                'payment_intent_id' => $row['payment_intent_id'],
                                'payment_method_id' => $row['payment_method_id'],
                                'amount' => $row['amount'],
                                'status' => 'succeeded',
                                'description' => $row['description'],
                                'created_at' => $row['created_at'],
                                'updated_at' => $row['updated_at'],
                                'minutes_since_success' => $minutesSinceSuccess
                            ];
                            
                            return $returnData;
                        }
                    } catch (Exception $e) {
                        log_error("Error verifying payment with PayMongo: " . $e->getMessage(), 'payment_error');
                        // Continue if PayMongo check fails - don't block transactions
                    }
                }
            }
        }
        
        // Return the default values when no matching transaction is found
        return $returnData;
    } catch (Exception $e) {
        log_error("Error checking pending transactions: " . $e->getMessage(), 'payment_error');
        // If there's an error, proceed with caution - don't block the transaction
        $returnData['error'] = $e->getMessage();
        return $returnData;
    }
}

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
    $ignoreRecentSuccess = isset($_POST['ignore_recent_success']) && $_POST['ignore_recent_success'] === 'true';

    if ($amount < 25) {
        echo json_encode(['success' => false, 'message' => 'Minimum amount is ₱25']);
        exit;
    }

    // Validate class ID if transaction type is 'class'
    if ($transactionType === 'class' && $classId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid class selection']);
        exit;
    }
    
    // Check for recent pending or successful transactions
    $pendingCheck = checkRecentPendingTransactions($conn, $_SESSION['user']);
    
    // If there's a pending transaction, prevent new creation
    if (isset($pendingCheck['hasPending']) && $pendingCheck['hasPending']) {
        $tx = $pendingCheck['transaction'];
        $minutesRemaining = $tx['minutes_remaining'];

        // Create a more detailed message
        $message = "You have a pending transaction in progress (#{$tx['id']} for ₱{$tx['amount']}).";
        $message .= " Please wait {$minutesRemaining} " . ($minutesRemaining == 1 ? "minute" : "minutes");
        $message .= " before trying again, check your transaction history, or contact support if you need assistance.";
        
        // Check if we have payment intent ID to provide more options to the user
        $paymentOptions = null;
        if (!empty($tx['payment_intent_id'])) {
            // Get the original payment method type
            $paymentOptions = [
                'payment_intent_id' => $tx['payment_intent_id'],
                'payment_method' => $tx['payment_method_type'] ?? $paymentMethod,
                'amount' => $tx['amount']
            ];
        }
        
        echo json_encode([
            'success' => false, 
            'message' => $message,
            'pendingTransaction' => $tx,
            'paymentOptions' => $paymentOptions
        ]);
        exit;
    }
    
    // If there's a recent successful transaction and user hasn't explicitly ignored it, warn them
    if (!$ignoreRecentSuccess && isset($pendingCheck['hasRecentSuccess']) && $pendingCheck['hasRecentSuccess']) {
        $tx = $pendingCheck['transaction'];
        $minutesSinceSuccess = $tx['minutes_since_success'];
        
        // Create a warning message about recent successful transaction
        $message = "You have a recent successful transaction (#{$tx['id']} for ₱{$tx['amount']}) that was completed {$minutesSinceSuccess} minutes ago.";
        $message .= " Please check your token balance before proceeding to avoid duplicate payments.";
        
        echo json_encode([
            'success' => false, 
            'message' => $message,
            'recentSuccessfulTransaction' => $tx,
            'shouldIgnore' => true // Tell the frontend this can be ignored
        ]);
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
        $query = "INSERT INTO transactions (user_id, payment_intent_id, amount, currency, status, payment_method_type, description, metadata, transaction_type) 
                 VALUES (?, ?, ?, 'PHP', 'pending', ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $metadata = json_encode(['class' => $classId]);
        $stmt->bind_param('isdssss', 
            $_SESSION['user'],
            $paymentIntent['data']['id'],
            $amount,
            $paymentMethod,
            $description,
            $metadata,
            $transactionType
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
    $clientKey = isset($_POST['payment_intent_id']) ? $_POST['payment_intent_id'] : '';
    $cardNumber = isset($_POST['card_number']) ? $_POST['card_number'] : '';
    $expiryDate = isset($_POST['expiry_date']) ? $_POST['expiry_date'] : '';
    $cvv = isset($_POST['cvv']) ? $_POST['cvv'] : '';

    // Add debug log for tracking
    log_error("Processing card payment with payment_intent_id: {$clientKey}", 'payment_info');

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
            $query = "SELECT t.transaction_id, t.user_id, t.amount, t.description, t.metadata, t.transaction_type
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
                $metadata = json_decode($row['metadata'], true);

                $classId = $metadata['class'];
                
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
                        // Calculate the actual token amount (remove VAT and service charge)
                        $VAT_RATE = 0.1;  // 10%
                        $SERVICE_RATE = 0.002;  // 0.2%
                        $baseAmount = $amount / (1 + $VAT_RATE + $SERVICE_RATE);
                        $tokenAmount = round($baseAmount);  // Round to nearest whole token
                        
                        // Update user's token balance
                        $query = "UPDATE users SET token_balance = token_balance + ? WHERE uid = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param('di', $tokenAmount, $userId);
                        $stmt->execute();
                        
                        // Log token purchase
                        log_error("Added {$tokenAmount} tokens to user ID {$userId} from transaction #{$transactionId} (payment amount: {$amount})", 'tokens');
                        
                        // Create a notification for the user
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
    $query = "SELECT t.transaction_id, t.user_id, t.amount, t.description, t.class_id 
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
                // Calculate the actual token amount (remove VAT and service charge)
                $VAT_RATE = 0.1;  // 10%
                $SERVICE_RATE = 0.002;  // 0.2%
                $baseAmount = $amount / (1 + $VAT_RATE + $SERVICE_RATE);
                $tokenAmount = round($baseAmount);  // Round to nearest whole token
                
                // Update user's token balance
                $query = "UPDATE users SET token_balance = token_balance + ? WHERE uid = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('di', $tokenAmount, $userId);
                $stmt->execute();
                
                // Log token purchase
                log_error("Added {$tokenAmount} tokens to user ID {$userId} from transaction #{$transactionId} (payment amount: {$amount})", 'tokens');
                
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
        // Calculate the actual token amount (remove VAT and service charge)
        $VAT_RATE = 0.1;  // 10%
        $SERVICE_RATE = 0.002;  // 0.2%
        $baseAmount = $amount / (1 + $VAT_RATE + $SERVICE_RATE);
        $tokenAmount = round($baseAmount);  // Round to nearest whole token
        
        // Update user's token balance
        $query = "UPDATE users SET token_balance = token_balance + ? WHERE uid = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('di', $tokenAmount, $userId);
        $stmt->execute();
        
        // Log the token balance update regardless of affected rows
        log_error("Token balance update for user {$userId}: Amount: {$tokenAmount} (from payment: {$amount})", 'tokens');
        
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
} else if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    // Only redirect if this file is accessed directly, not when included
    header("Location: " . BASE . "payment");
    exit;
}
?>