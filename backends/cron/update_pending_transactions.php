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
require_once ROOT_PATH . '/backends/transactions_management.php';

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
                            log_error("Updated transaction #$transactionId to status: $newStatus", "info");
                            
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
                        log_error("Transaction #$transactionId has non-terminal status: {$paymentStatus['status']}", "info");
                    }
                } else {
                    // If payment intent doesn't exist, mark as failed
                    $errorMessage = "Payment intent not found or expired";
                    $updateQuery = "UPDATE transactions SET 
                                  status = 'failed', 
                                  error_message = ?,
                                  updated_at = NOW()
                                  WHERE transaction_id = ?";
                    $stmt = $conn->prepare($updateQuery);
                    $stmt->bind_param('si', $errorMessage, $transactionId);
                    
                    if ($stmt->execute()) {
                        $stats['updated']++;
                        log_error("Marked transaction #$transactionId as failed: Payment intent not found", "info");
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
                    log_error("Marked transaction #$transactionId as failed: No payment intent", "info");
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

// Run the update process
$stats = updateStalePendingTransactions($conn);

// Output summary
echo "Transaction Update Summary:\n";
echo "Checked: {$stats['checked']}\n";
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

exit(0); 