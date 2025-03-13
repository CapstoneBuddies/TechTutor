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
            $whereClause .= " WHERE status = ?";
            $params[] = strtoupper($filter);
            $types .= 's';
        }

        // Role-based filtering
        if ($role !== 'ADMIN') {
            $whereClause = $whereClause ? $whereClause . " AND user_id = ?" : " WHERE user_id = ?";
            $params[] = $userId;
            $types .= 'i';
        }

        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total FROM transactions" . $whereClause;
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
                        u.role as user_role
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
                'type' => $row['type'],
                'amount' => $row['amount'],
                'status' => $row['status'],
                'date' => $row['created_at'],
                'description' => $row['description'],
                'reference' => $row['reference_number']
            ];
        }

        return [
            'success' => true,
            'transactions' => $transactions,
            'totalPages' => ceil($total / $limit),
            'currentPage' => $page
        ];

    } catch (Exception $e) {
        log_error($e->getMessage(), 'database_error.log');
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
                        u.role as user_role
                 FROM transactions t
                 LEFT JOIN users u ON t.user_id = u.uid
                 WHERE t.transaction_id = ?";

        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $transactionId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return [
                'success' => true,
                'transaction' => [
                    'id' => $row['transaction_id'],
                    'userId' => $row['user_id'],
                    'userName' => $row['first_name'] . ' ' . $row['last_name'],
                    'userRole' => $row['user_role'],
                    'type' => $row['type'],
                    'amount' => $row['amount'],
                    'status' => $row['status'],
                    'date' => $row['created_at'],
                    'description' => $row['description'],
                    'reference' => $row['reference_number']
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'Transaction not found'
        ];

    } catch (Exception $e) {
        log_error($e->getMessage(), 'database_error.log');
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
                        t.type,
                        t.amount,
                        t.status,
                        t.description,
                        t.reference_number,
                        t.created_at,
                        CONCAT(u.first_name, ' ', u.last_name) as user_name,
                        u.role as user_role
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
        log_error($e->getMessage(), 'database_error.log');
        return [
            'success' => false,
            'message' => 'Failed to export transactions'
        ];
    }
}
?>