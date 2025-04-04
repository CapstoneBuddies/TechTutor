<?php
/**
 * Cronjob script to update stale pending/processing transactions
 * This should be run every 3 hours to check for and update pending transactions
 * 
 * Usage: php update_pending_transactions.php
 */

// This script should be run from the command line
if (php_sapi_name() !== 'cli') {
    echo "This script can only be run from the command line.";
    exit(1);
}

// Set the base path relative to the script's location
define('ROOT_PATH', realpath(dirname(__FILE__) . '/../..'));
require_once ROOT_PATH . '/backends/main.php';
require_once ROOT_PATH . '/backends/handler/paymongo_config.php';

// Check that the PayMongo classes are loaded
if (!class_exists('PayMongoHelper')) {
    echo "ERROR: PayMongoHelper class not found. Make sure paymongo_config.php is properly included.\n";
    exit(1);
}

// Log start of process
log_error("Starting pending transaction cleanup process", "info");

/**
 * Updates stale pending/processing transactions
 * 
 * @param mysqli $conn Database connection
 * @return array Result statistics
 */
function updateStalePendingTransactions($conn) {
    $stats = [
        'checked' => 0,
        'updated' => 0,
        'failed' => 0,
        'verified_with_paymongo' => 0,
        'errors' => []
    ];
    
    try {
        // Get transactions that are pending/processing for more than 15 minutes
        $query = "SELECT transaction_id, payment_intent_id, payment_method_id, 
                 amount, status, user_id, created_at, 
                 TIMESTAMPDIFF(MINUTE, created_at, NOW()) as age_minutes
                 FROM transactions 
                 WHERE (status = 'pending' OR status = 'processing')
                 AND created_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE)
                 ORDER BY created_at ASC";
        
        $result = $conn->query($query);
        
        if (!$result) {
            throw new Exception("Database query failed: " . $conn->error);
        }
        
        $payMongo = new PayMongoHelper();
        
        // Process each transaction
        while ($tx = $result->fetch_assoc()) {
            $stats['checked']++;
            
            $transactionId = $tx['transaction_id'];
            $paymentIntentId = $tx['payment_intent_id'];
            $userId = $tx['user_id'];
            
            log_error("Checking transaction #$transactionId (Payment Intent: $paymentIntentId, Age: {$tx['age_minutes']} minutes)", "info");
            
            // Check the actual status with PayMongo if we have a payment intent ID
            if (!empty($paymentIntentId)) {
                $stats['verified_with_paymongo']++;
                $paymentStatus = $payMongo->checkPaymentIntentStatus($paymentIntentId);
                
                if ($paymentStatus['exists']) {
                    // If we got a valid status and it's terminal, update our record
                    if ($paymentStatus['is_terminal']) {
                        $newStatus = $paymentStatus['status'] === 'succeeded' ? 'succeeded' : 'failed';
                        $errorMessage = $newStatus === 'failed' ? 'Transaction timed out or was abandoned' : null;
                        
                        // Update the transaction record
                        $updateQuery = "UPDATE transactions SET 
                                      status = ?, 
                                      error_message = ?,
                                      updated_at = NOW()
                                      WHERE transaction_id = ?";
                        $stmt = $conn->prepare($updateQuery);
                        $stmt->bind_param('ssi', $newStatus, $errorMessage, $transactionId);
                        
                        if ($stmt->execute()) {
                            $stats['updated']++;
                            log_error("Updated transaction #$transactionId to status: $newStatus (from PayMongo)", "info");
                            
                            // If transaction succeeded, update user's token balance
                            if ($newStatus === 'succeeded') {
                                try {
                                    updateUserTokenBalance($conn, $userId, $tx['amount'], $transactionId);
                                } catch (Exception $e) {
                                    log_error("Error updating token balance for transaction #$transactionId: " . $e->getMessage(), "error");
                                    $stats['errors'][] = "Token balance update failed for TX #$transactionId: " . $e->getMessage();
                                }
                            }
                        } else {
                            $stats['failed']++;
                            log_error("Failed to update transaction #$transactionId: " . $stmt->error, "error");
                            $stats['errors'][] = "Failed to update TX #$transactionId: " . $stmt->error;
                        }
                    } else {
                        log_error("Transaction #$transactionId has non-terminal status from PayMongo: {$paymentStatus['status']}", "info");
                    }
                } else {
                    // If payment intent doesn't exist, mark as failed
                    $errorMessage = "Payment intent not found or expired on PayMongo";
                    $updateQuery = "UPDATE transactions SET 
                                  status = 'failed', 
                                  error_message = ?,
                                  updated_at = NOW()
                                  WHERE transaction_id = ?";
                    $stmt = $conn->prepare($updateQuery);
                    $stmt->bind_param('si', $errorMessage, $transactionId);
                    
                    if ($stmt->execute()) {
                        $stats['updated']++;
                        log_error("Marked transaction #$transactionId as failed: Payment intent not found on PayMongo", "info");
                    } else {
                        $stats['failed']++;
                        log_error("Failed to update transaction #$transactionId: " . $stmt->error, "error");
                        $stats['errors'][] = "Failed to update TX #$transactionId: " . $stmt->error;
                    }
                }
            } else {
                // No payment intent ID, just mark as failed
                $errorMessage = "Transaction abandoned or payment intent missing";
                $updateQuery = "UPDATE transactions SET 
                              status = 'failed', 
                              error_message = ?,
                              updated_at = NOW()
                              WHERE transaction_id = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param('si', $errorMessage, $transactionId);
                
                if ($stmt->execute()) {
                    $stats['updated']++;
                    log_error("Marked transaction #$transactionId as failed: No payment intent ID to verify", "info");
                } else {
                    $stats['failed']++;
                    log_error("Failed to update transaction #$transactionId: " . $stmt->error, "error");
                    $stats['errors'][] = "Failed to update TX #$transactionId: " . $stmt->error;
                }
            }
        }
        
    } catch (Exception $e) {
        log_error("Error updating stale transactions: " . $e->getMessage(), "error");
        $stats['errors'][] = "Global error: " . $e->getMessage();
    }
    
    return $stats;
}

/**
 * Update user token balance based on payment amount
 * @param mysqli $conn Database connection
 * @param int $userId User ID
 * @param float $amount Payment amount in PHP
 * @param string $transactionId Transaction ID for logging
 * @return bool Success status
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
        
        // Log the token balance update
        log_error("Token balance updated via cron for user {$userId}: Added {$tokenAmount} tokens (Transaction #{$transactionId})", 'tokens');
        
        // Create a notification for the user if notification system is available
        if (function_exists('sendNotification')) {
            sendNotification(
                $userId,
                '', // Send to any role
                "Your payment of ₱" . number_format($amount, 2) . " has been processed and {$tokenAmount} tokens have been added to your account.",
                "dashboard", 
                null, 
                'bi-coin', 
                'text-success'
            );
        }
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        log_error("Token update error (from cron): " . $e->getMessage(), 'error');
        return false;
    }
}

// Run the update process
$stats = updateStalePendingTransactions($conn);

// Output summary
echo "Transaction Update Summary:\n";
echo "Checked: {$stats['checked']}\n";
echo "Verified with PayMongo: {$stats['verified_with_paymongo']}\n";
echo "Updated: {$stats['updated']}\n";
echo "Failed: {$stats['failed']}\n";

if (!empty($stats['errors'])) {
    echo "\nErrors:\n";
    foreach ($stats['errors'] as $error) {
        echo "- $error\n";
    }
}

// Log completion
log_error("Completed pending transaction cleanup process. Checked: {$stats['checked']}, Updated: {$stats['updated']}, Failed: {$stats['failed']}", "info");

// Close database connection
if (isset($conn)) {
    $conn->close();
}

echo "\nProcess completed successfully.\n";
exit(0); 