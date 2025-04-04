<?php
/**
 * Cron job to update the status of stale payment transactions
 * 
 * This script should be run via cron every 3 hours
 * Example crontab entry:
 * 0 */#3 * * * php /path/to/your/capstone-1/backends/cron/update_transactions.php

// Set script execution time limit to 5 minutes
set_time_limit(300);

// Load required files
require_once __DIR__ . '/../../backends/main.php';
require_once ROOT_PATH . '/backends/handler/paymongo_config.php';
require_once BACKEND . 'transactions_management.php';

// Log the start of the cron job
log_error("Starting transaction status update cron job", "cron");

try {
    // Find all pending/processing transactions older than 15 minutes
    $query = "SELECT transaction_id, payment_intent_id, user_id, amount, status, created_at, 
              TIMESTAMPDIFF(MINUTE, created_at, NOW()) as minutes_ago
              FROM transactions 
              WHERE (status = 'pending' OR status = 'processing')
              AND created_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE)
              ORDER BY created_at";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $updatedCount = 0;
    $failedCount = 0;
    
    // Process each stale transaction
    while ($row = $result->fetch_assoc()) {
        $transactionId = $row['transaction_id'];
        $paymentIntentId = $row['payment_intent_id'];
        $userId = $row['user_id'];
        $amount = $row['amount'];
        $status = $row['status'];
        $minutesAgo = $row['minutes_ago'];
        
        log_error("Processing stale transaction #{$transactionId} (payment intent: {$paymentIntentId}) from user {$userId}, created {$minutesAgo} minutes ago", "cron");
        
        // Check the actual payment status with PayMongo if we have a payment intent ID
        if (!empty($paymentIntentId)) {
            $payMongo = new PayMongoHelper();
            $paymentStatus = $payMongo->checkPaymentIntentStatus($paymentIntentId);
            
            // If the payment is in a terminal state (succeeded, cancelled, processing)
            if ($paymentStatus['exists'] && $paymentStatus['is_terminal']) {
                $newStatus = $paymentStatus['status'];
                log_error("Payment intent {$paymentIntentId} has terminal status: {$newStatus}", "cron");
                
                // Update the transaction status in our database
                $updateQuery = "UPDATE transactions SET status = ?, updated_at = NOW() WHERE transaction_id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param('si', $newStatus, $transactionId);
                $updateStmt->execute();
                
                if ($updateStmt->affected_rows > 0) {
                    log_error("Updated transaction #{$transactionId} status to {$newStatus}", "cron");
                    $updatedCount++;
                    
                    // If payment succeeded and it's a token purchase, add tokens to user
                    if ($newStatus === 'succeeded') {
                        // Check transaction type
                        $typeQuery = "SELECT transaction_type FROM transactions WHERE transaction_id = ?";
                        $typeStmt = $conn->prepare($typeQuery);
                        $typeStmt->bind_param('i', $transactionId);
                        $typeStmt->execute();
                        $typeResult = $typeStmt->get_result()->fetch_assoc();
                        
                        if ($typeResult && $typeResult['transaction_type'] === 'token') {
                            // Add tokens to user's balance (1:1 ratio)
                            $tokenAmount = $amount;
                            $updateBalanceQuery = "UPDATE users SET token_balance = token_balance + ? WHERE uid = ?";
                            $updateBalanceStmt = $conn->prepare($updateBalanceQuery);
                            $updateBalanceStmt->bind_param('di', $tokenAmount, $userId);
                            $updateBalanceStmt->execute();
                            
                            if ($updateBalanceStmt->affected_rows > 0) {
                                log_error("Added {$tokenAmount} tokens to user {$userId} balance from transaction #{$transactionId}", "cron");
                                
                                // Create notification for the user
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
                        }
                    }
                } else {
                    log_error("Failed to update transaction #{$transactionId}", "cron");
                    $failedCount++;
                }
            } else {
                // Payment intent doesn't exist or is not in a terminal state
                // Mark as failed after 15 minutes
                $updateQuery = "UPDATE transactions SET status = 'failed', updated_at = NOW() WHERE transaction_id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param('i', $transactionId);
                $updateStmt->execute();
                
                if ($updateStmt->affected_rows > 0) {
                    log_error("Marked transaction #{$transactionId} as failed after timeout", "cron");
                    $updatedCount++;
                } else {
                    log_error("Failed to update transaction #{$transactionId}", "cron");
                    $failedCount++;
                }
            }
        } else {
            // No payment intent ID, mark as failed
            $updateQuery = "UPDATE transactions SET status = 'failed', updated_at = NOW() WHERE transaction_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param('i', $transactionId);
            $updateStmt->execute();
            
            if ($updateStmt->affected_rows > 0) {
                log_error("Marked transaction #{$transactionId} without payment intent as failed", "cron");
                $updatedCount++;
            } else {
                log_error("Failed to update transaction #{$transactionId}", "cron");
                $failedCount++;
            }
        }
    }
    
    // Log the final summary
    log_error("Transaction update cron job completed. Updated: {$updatedCount}, Failed: {$failedCount}", "cron");
    
} catch (Exception $e) {
    log_error("Error in transaction update cron job: " . $e->getMessage(), "cron");
} 