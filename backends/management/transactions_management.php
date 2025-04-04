<?php
/**
 * Get paginated list of transactions with optional filtering
 */
function getTransactions($page = 1, $filter = 'all', $role = '', $userId = null) {
    global $conn;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    try {
        $whereClause = '';
        $params = [];
        $types = '';

        // Filter by status if specified
        if ($filter !== 'all') {
            $whereClause .= " WHERE t.status = ?";
            $params[] = strtoupper($filter);
            $types .= 's';
        }

        // Role-based filtering
        if ($role !== 'ADMIN') {
            $whereClause = $whereClause ? $whereClause . " AND t.user_id = ?" : " WHERE t.user_id = ?";
            $params[] = $userId;
            $types .= 'i';
        }

        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total FROM transactions t" . $whereClause;
        $stmt = $conn->prepare($countQuery);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $totalResult = $stmt->get_result()->fetch_assoc();
        $total = $totalResult['total'];

        // Get transactions with pagination
        $query = "SELECT t.*, 
                        u.first_name, 
                        u.last_name, 
                        u.role as user_role,
                        (SELECT COUNT(*) FROM transaction_disputes td WHERE td.transaction_id = t.transaction_id) as has_dispute
                 FROM transactions t
                 LEFT JOIN users u ON t.user_id = u.uid" . 
                 $whereClause . 
                 " ORDER BY t.created_at DESC
                 LIMIT ? OFFSET ?";

        $stmt = $conn->prepare($query);
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = [
                'id' => $row['transaction_id'],
                'userId' => $row['user_id'],
                'userName' => $row['first_name'] . ' ' . $row['last_name'],
                'userRole' => $row['user_role'],
                'type' => $row['payment_method_type'],
                'amount' => $row['amount'],
                'status' => $row['status'],
                'date' => $row['created_at'],
                'description' => $row['description'],
                'reference' => $row['payment_method_id'],
                'hasDispute' => $row['has_dispute'] > 0
            ];
        }

        return [
            'success' => true,
            'transactions' => $transactions,
            'totalPages' => ceil($total / $limit),
            'currentPage' => $page
        ];

    } catch (Exception $e) {
        log_error($e->getMessage(), 'database');
        return [
            'success' => false,
            'message' => 'Failed to fetch transactions'
        ];
    }
}

/**
 * Get details of a specific transaction
 */
function getTransactionDetails($transactionId) {
    global $conn;
    
    try {
        $query = "SELECT t.*, 
                        u.first_name, 
                        u.last_name, 
                        u.role as user_role,
                        (SELECT COUNT(*) FROM transaction_disputes td WHERE td.transaction_id = t.transaction_id) as has_dispute
                 FROM transactions t
                 LEFT JOIN users u ON t.user_id = u.uid
                 WHERE t.transaction_id = ?";

        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $transactionId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // If there's a dispute, get its details
            $dispute = null;
            if ($row['has_dispute'] > 0) {
                $disputeQuery = "SELECT * FROM transaction_disputes WHERE transaction_id = ? ORDER BY created_at DESC LIMIT 1";
                $disputeStmt = $conn->prepare($disputeQuery);
                $disputeStmt->bind_param('s', $transactionId);
                $disputeStmt->execute();
                $disputeResult = $disputeStmt->get_result();
                if ($disputeRow = $disputeResult->fetch_assoc()) {
                    $dispute = [
                        'id' => $disputeRow['dispute_id'],
                        'reason' => $disputeRow['reason'],
                        'status' => $disputeRow['status'],
                        'adminNotes' => $disputeRow['admin_notes'],
                        'createdAt' => $disputeRow['created_at'],
                        'updatedAt' => $disputeRow['updated_at']
                    ];
                }
            }

            return [
                'success' => true,
                'transaction' => [
                    'id' => $row['transaction_id'],
                    'userId' => $row['user_id'],
                    'userName' => $row['first_name'] . ' ' . $row['last_name'],
                    'userRole' => $row['user_role'],
                    'type' => $row['payment_method_type'],
                    'amount' => $row['amount'],
                    'status' => $row['status'],
                    'date' => $row['created_at'],
                    'description' => $row['description'],
                    'reference' => $row['payment_method_id'],
                    'hasDispute' => $row['has_dispute'] > 0,
                    'dispute' => $dispute
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'Transaction not found'
        ];

    } catch (Exception $e) {
        log_error($e->getMessage(), 'database');
        return [
            'success' => false,
            'message' => 'Failed to fetch transaction details'
        ];
    }
}

/**
 * Export transactions data based on filters
 */
function exportTransactions($filter = 'all', $role = '', $userId = null) {
    global $conn;
    
    try {
        $whereClause = '';
        $params = [];
        $types = '';

        if ($filter !== 'all') {
            $whereClause .= " WHERE t.status = ?";
            $params[] = strtoupper($filter);
            $types .= 's';
        }

        if ($role !== 'ADMIN') {
            $whereClause = $whereClause ? $whereClause . " AND t.user_id = ?" : " WHERE t.user_id = ?";
            $params[] = $userId;
            $types .= 'i';
        }

        $query = "SELECT t.transaction_id, 
                        t.payment_method_type as type,
                        t.amount,
                        t.currency,
                        t.status,
                        t.description,
                        t.payment_method_id as reference_number,
                        t.created_at,
                        CONCAT(u.first_name, ' ', u.last_name) as user_name,
                        u.role as user_role,
                        (SELECT COUNT(*) FROM transaction_disputes td WHERE td.transaction_id = t.transaction_id) as has_dispute
                 FROM transactions t
                 LEFT JOIN users u ON t.user_id = u.uid" . 
                 $whereClause . 
                 " ORDER BY t.created_at DESC";

        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        
        return [
            'success' => true,
            'transactions' => $transactions
        ];

    } catch (Exception $e) {
        log_error($e->getMessage(), 'database');
        return [
            'success' => false,
            'message' => 'Failed to export transactions'
        ];
    }
}

/**
 * Get Recent Transactions per user
 */
function getRecentTransactions($user_id) {
    global $conn;
    
    try {
        $query = "SELECT 
                    t.transaction_id as id,
                    t.payment_method_type as type,
                    t.amount,
                    t.status,
                    DATE_FORMAT(t.created_at, '%b %d, %Y') as date,
                    t.description as message,
                    t.payment_method_id as reference,
                    (SELECT COUNT(*) FROM transaction_disputes td WHERE td.transaction_id = t.transaction_id) as has_dispute
                FROM transactions t
                WHERE t.user_id = ?
                ORDER BY t.created_at DESC
                LIMIT 5";

        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        
        return $transactions;
    } catch (Exception $e) {
        log_error("Error fetching recent transactions: " . $e->getMessage(), 'database');
        return [];
    }
}

/**
 * Create a new transaction dispute
 */
function createTransactionDispute($transactionId, $userId, $reason) {
    global $conn;
    
    try {
        // First check if a dispute already exists for this transaction
        $checkQuery = "SELECT COUNT(*) as count FROM transaction_disputes 
                      WHERE transaction_id = ? AND status != 'cancelled'";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param('i', $transactionId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            return [
                'success' => false,
                'message' => 'A dispute for this transaction already exists'
            ];
        }
        
        // Check if the user owns this transaction
        $checkOwnerQuery = "SELECT user_id FROM transactions WHERE transaction_id = ?";
        $stmt = $conn->prepare($checkOwnerQuery);
        $stmt->bind_param('i', $transactionId);
        $stmt->execute();
        $ownerResult = $stmt->get_result()->fetch_assoc();
        
        if (!$ownerResult || $ownerResult['user_id'] != $userId) {
            return [
                'success' => false,
                'message' => 'You do not have permission to dispute this transaction'
            ];
        }
        
        // Create the dispute
        $query = "INSERT INTO transaction_disputes (transaction_id, user_id, reason) 
                  VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('iis', $transactionId, $userId, $reason);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            // Log the dispute creation
            log_error("User $userId created dispute for transaction $transactionId", 'info');
            
            return [
                'success' => true,
                'message' => 'Dispute created successfully',
                'disputeId' => $conn->insert_id
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to create dispute'
            ];
        }
    } catch (Exception $e) {
        log_error("Error creating transaction dispute: " . $e->getMessage(), 'database');
        return [
            'success' => false,
            'message' => 'An error occurred while creating the dispute'
        ];
    }
}

/**
 * Get disputes with optional filtering
 */
function getDisputes($page = 1, $status = 'all', $role = '', $userId = null) {
    global $conn;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    try {
        $whereClause = '';
        $params = [];
        $types = '';

        // Filter by status if specified
        if ($status !== 'all') {
            $whereClause .= " WHERE td.status = ?";
            $params[] = $status;
            $types .= 's';
        }

        // Role-based filtering
        if ($role !== 'ADMIN') {
            $whereClause = $whereClause ? $whereClause . " AND td.user_id = ?" : " WHERE td.user_id = ?";
            $params[] = $userId;
            $types .= 'i';
        }

        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total FROM transaction_disputes td" . $whereClause;
        $stmt = $conn->prepare($countQuery);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $totalResult = $stmt->get_result()->fetch_assoc();
        $total = $totalResult['total'];

        // Get disputes with pagination
        $query = "SELECT td.*, 
                        t.payment_method_type, 
                        t.amount,
                        t.status as transaction_status,
                        u.first_name, 
                        u.last_name, 
                        u.role as user_role
                 FROM transaction_disputes td
                 LEFT JOIN transactions t ON td.transaction_id = t.transaction_id
                 LEFT JOIN users u ON td.user_id = u.uid" . 
                 $whereClause . 
                 " ORDER BY td.created_at DESC
                 LIMIT ? OFFSET ?";

        $stmt = $conn->prepare($query);
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $disputes = [];
        while ($row = $result->fetch_assoc()) {
            $disputes[] = [
                'id' => $row['dispute_id'],
                'transactionId' => $row['transaction_id'],
                'userId' => $row['user_id'],
                'userName' => $row['first_name'] . ' ' . $row['last_name'],
                'userRole' => $row['user_role'],
                'reason' => $row['reason'],
                'status' => $row['status'],
                'adminNotes' => $row['admin_notes'],
                'transactionType' => $row['payment_method_type'],
                'transactionAmount' => $row['amount'],
                'transactionStatus' => $row['transaction_status'],
                'createdAt' => $row['created_at'],
                'updatedAt' => $row['updated_at']
            ];
        }

        return [
            'success' => true,
            'disputes' => $disputes,
            'totalPages' => ceil($total / $limit),
            'currentPage' => $page
        ];

    } catch (Exception $e) {
        log_error($e->getMessage(), 'database');
        return [
            'success' => false,
            'message' => 'Failed to fetch disputes'
        ];
    }
}

/**
 * Update dispute status
 */
function updateDisputeStatus($disputeId, $status, $adminNotes = null, $adminId = null) {
    global $conn;
    
    try {
        $query = "UPDATE transaction_disputes SET status = ?, admin_notes = ? WHERE dispute_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssi', $status, $adminNotes, $disputeId);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            // Log the status update
            log_error("Dispute $disputeId updated to $status by admin $adminId", 'info');
            
            return [
                'success' => true,
                'message' => 'Dispute status updated successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update dispute status or no changes made'
            ];
        }
    } catch (Exception $e) {
        log_error("Error updating dispute status: " . $e->getMessage(), 'database');
        return [
            'success' => false,
            'message' => 'An error occurred while updating the dispute'
        ];
    }
}

/**
 * Process transaction refund
 */
function processRefund($transactionId, $disputeId, $amount, $adminId, $notes = null) {
    global $conn;
    
    try {
        // Verify the transaction exists and get details
        $txnQuery = "SELECT * FROM transactions WHERE transaction_id = ?";
        $stmt = $conn->prepare($txnQuery);
        $stmt->bind_param('i', $transactionId);
        $stmt->execute();
        $transaction = $stmt->get_result()->fetch_assoc();
        
        if (!$transaction) {
            return [
                'success' => false,
                'message' => 'Transaction not found'
            ];
        }
        
        // Verify amount doesn't exceed original transaction amount
        if ($amount > $transaction['amount']) {
            return [
                'success' => false,
                'message' => 'Refund amount cannot exceed original transaction amount'
            ];
        }
        
        // Create refund record
        $query = "INSERT INTO transaction_refunds 
                 (transaction_id, dispute_id, amount, admin_id, notes) 
                 VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('iidis', $transactionId, $disputeId, $amount, $adminId, $notes);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $refundId = $conn->insert_id;
            
            // Update dispute status if it exists
            if ($disputeId) {
                $updateQuery = "UPDATE transaction_disputes SET status = 'resolved' WHERE dispute_id = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param('i', $disputeId);
                $stmt->execute();
            }
            
            // Log the refund
            log_error("Refund $refundId created for transaction $transactionId by admin $adminId", 'info');
            
            // TODO: In a real scenario, we would call the payment gateway's API to process the actual refund
            // This is a simplified version that just records the refund intent
            
            return [
                'success' => true,
                'message' => 'Refund initiated successfully',
                'refundId' => $refundId
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to initiate refund'
            ];
        }
    } catch (Exception $e) {
        log_error("Error processing refund: " . $e->getMessage(), 'database');
        return [
            'success' => false,
            'message' => 'An error occurred while processing the refund'
        ];
    }
}
?>