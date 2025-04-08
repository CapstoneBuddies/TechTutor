<?php
require_once '../../backends/main.php'; 
require_once BACKEND . 'transactions_management.php';
require_once ROOT_PATH . '/backends/handler/paymongo_config.php';

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: " . BASE . "login");
    exit();
}

// Initialize PayMongo helper
$payMongo = new PayMongoHelper();

// Default parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'pending';
$message = '';
$alert_type = '';

// Handle transaction status update
if (isset($_POST['update_status'])) {
    $transaction_id = (int)$_POST['transaction_id'];
    $new_status = $_POST['new_status'];
    $error_message = isset($_POST['error_message']) ? $_POST['error_message'] : '';
    
    try {
        // Update transaction status
        $query = "UPDATE transactions SET 
                status = ?, 
                error_message = ?,
                updated_at = CURRENT_TIMESTAMP 
              WHERE transaction_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssi', $new_status, $error_message, $transaction_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $message = "Transaction #$transaction_id status updated to $new_status";
            $alert_type = 'success';
            
            // Create notification for user if transaction failed
            if ($new_status === 'failed') {
                // Get transaction details
                $query = "SELECT t.user_id, t.amount FROM transactions t WHERE t.transaction_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('i', $transaction_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    // Send notification to user
                    sendNotification(
                        $row['user_id'],
                        '', // Send to any role
                        "Your payment of ₱" . number_format($row['amount'], 2) . " failed. Please try again.", 
                        BASE . "payment", 
                        null, 
                        'bi-exclamation-circle', 
                        'text-danger'
                    );
                }
            }
            elseif ($new_status === 'succeeded') {

                $get_token = $conn->prepare("SELECT user_id, amount FROM transactions WHERE transaction_id = ?");
                $get_token->bind_param('i', $transaction_id);
                $get_token->execute();
                $result = $get_token->get_result();
                
                if ($row = $result->fetch_assoc()) { 
                    // Update tokens
                    $amount = $row['amount'];
                    $user_id = $row['user_id'];
                    $VAT_RATE = 0.1;  // 10%
                    $SERVICE_RATE = 0.002;  // 0.2%
                    $baseAmount = $amount / (1 + $VAT_RATE + $SERVICE_RATE);
                    $tokenAmount = round($baseAmount);  // Round to nearest whole token

                    // Update user's token balance
                    $query = "UPDATE users SET token_balance = token_balance + ? WHERE uid = ?";
                    $update_stmt = $conn->prepare($query);
                    $update_stmt->bind_param('di', $tokenAmount, $user_id);
                    $update_stmt->execute();

                    header("location: status");
                    exit();
                }
            }
        } else {
            $message = "No changes made to transaction #$transaction_id";
            $alert_type = 'info';
        }
    } catch (Exception $e) {
        $message = "Error updating transaction: " . $e->getMessage();
        $alert_type = 'danger';
        log_error("Error updating transaction #$transaction_id: " . $e->getMessage(), 'database');
    }
}

// Handle checking transaction status with PayMongo
if (isset($_POST['check_status'])) {
    $transaction_id = (int)$_POST['transaction_id'];
    $payment_intent_id = $_POST['payment_intent_id'];
    
    try {
        // Check status with PayMongo
        $statusCheck = $payMongo->checkPaymentIntentStatus($payment_intent_id);
        
        if ($statusCheck['exists']) {
            $paymongo_status = $statusCheck['status'];
            $mapped_status = mapPayMongoStatus($paymongo_status);
            
            // Get current status from database
            $query = "SELECT status FROM transactions WHERE transaction_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $transaction_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $current_status = $result->fetch_assoc()['status'];
            
            // Only update if status is different
            if ($current_status !== $mapped_status) {
                // Update transaction status
                $query = "UPDATE transactions SET 
                        status = ?, 
                        updated_at = CURRENT_TIMESTAMP 
                      WHERE transaction_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('si', $mapped_status, $transaction_id);
                $stmt->execute();
                
                if ($stmt->affected_rows > 0) {
                    $message = "Transaction #$transaction_id status updated from $current_status to $mapped_status based on PayMongo status: $paymongo_status";
                    $alert_type = 'success';
                    
                    // Create notification for user if transaction failed
                    if ($mapped_status === 'failed') {
                        // Get transaction details
                        $query = "SELECT t.user_id, t.amount FROM transactions t WHERE t.transaction_id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param('i', $transaction_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($row = $result->fetch_assoc()) {
                            // Send notification to user
                            sendNotification(
                                $row['user_id'],
                                '', // Send to any role
                                "Your payment of ₱" . number_format($row['amount'], 2) . " failed. Please try again.", 
                                BASE . "payment", 
                                null, 
                                'bi-exclamation-circle', 
                                'text-danger'
                            );
                        }
                    }
                } else {
                    $message = "No changes needed for transaction #$transaction_id. PayMongo status: $paymongo_status";
                    $alert_type = 'info';
                }
            } else {
                $message = "Transaction #$transaction_id already has correct status: $current_status. PayMongo status: $paymongo_status";
                $alert_type = 'info';
            }
        } else {
            $message = "PayMongo payment intent not found: $payment_intent_id";
            $alert_type = 'warning';
        }
    } catch (Exception $e) {
        $message = "Error checking transaction status: " . $e->getMessage();
        $alert_type = 'danger';
        log_error("Error checking transaction #$transaction_id status: " . $e->getMessage(), 'payment_error');
    }
}

// Handle bulk status check
if (isset($_POST['check_all_pending'])) {
    $updated_count = 0;
    $checked_count = 0;
    $error_count = 0;
    
    try {
        // Get all pending/processing transactions
        $query = "SELECT transaction_id, payment_intent_id, status 
                  FROM transactions 
                  WHERE status IN ('pending', 'processing') 
                  AND payment_intent_id IS NOT NULL 
                  AND payment_intent_id != '' 
                  ORDER BY created_at ASC";
        $result = $conn->query($query);
        
        while ($row = $result->fetch_assoc()) {
            $checked_count++;
            $transaction_id = $row['transaction_id'];
            $payment_intent_id = $row['payment_intent_id'];
            $current_status = $row['status'];
            
            // Check status with PayMongo
            $statusCheck = $payMongo->checkPaymentIntentStatus($payment_intent_id);
            
            if ($statusCheck['exists']) {
                $paymongo_status = $statusCheck['status'];
                $mapped_status = mapPayMongoStatus($paymongo_status);
                
                // Only update if status is different
                if ($current_status !== $mapped_status) {
                    // Update transaction status
                    $query = "UPDATE transactions SET 
                            status = ?, 
                            updated_at = CURRENT_TIMESTAMP 
                          WHERE transaction_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param('si', $mapped_status, $transaction_id);
                    $stmt->execute();
                    
                    if ($stmt->affected_rows > 0) {
                        $updated_count++;
                        
                        // Create notification for user if transaction failed
                        if ($mapped_status === 'failed') {
                            // Get transaction details
                            $query = "SELECT t.user_id, t.amount FROM transactions t WHERE t.transaction_id = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param('i', $transaction_id);
                            $stmt->execute();
                            $result2 = $stmt->get_result();
                            
                            if ($row2 = $result2->fetch_assoc()) {
                                // Send notification to user
                                sendNotification(
                                    $row2['user_id'],
                                    '', // Send to any role
                                    "Your payment of ₱" . number_format($row2['amount'], 2) . " failed. Please try again.", 
                                    BASE . "payment", 
                                    null, 
                                    'bi-exclamation-circle', 
                                    'text-danger'
                                );
                            }
                        }
                    }
                }
            } else {
                $error_count++;
            }
        }
        
        $message = "Checked $checked_count transactions. Updated $updated_count. Errors: $error_count.";
        $alert_type = 'info';
        
    } catch (Exception $e) {
        $message = "Error in bulk status check: " . $e->getMessage();
        $alert_type = 'danger';
        log_error("Error in bulk transaction status check: " . $e->getMessage(), 'payment_error');
    }
}

// Function to map PayMongo status to our transaction status
function mapPayMongoStatus($paymongo_status) {
    switch ($paymongo_status) {
        case 'succeeded':
            return 'succeeded';
        case 'awaiting_payment_method':
        case 'awaiting_next_action':
            return 'pending';
        case 'processing':
            return 'processing';
        case 'cancelled':
        case 'failed':
            return 'failed';
        default:
            return 'pending';
    }
}

// Get transactions from database
$transactions = getTransactions($page, $filter, $_SESSION['role'], $_SESSION['user']);
?>

<!DOCTYPE html>
<html lang="en">
<?php include ROOT_PATH . '/components/head.php'; ?>
<body>
    <?php include ROOT_PATH . '/components/header.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col">
                <h2>Transaction Status Management</h2>
                <p class="text-muted">Check and update payment transaction statuses</p>
                
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form method="post" class="mb-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Bulk Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <button type="submit" name="check_all_pending" class="btn btn-warning">
                                        <i class="bi bi-arrow-repeat"></i> Check All Pending Transactions
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                
                <div class="card">
                    <div class="card-header bg-light">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="card-title mb-0">Transactions</h5>
                            </div>
                            <div class="col-md-4">
                                <form method="get" class="d-flex">
                                    <select name="filter" class="form-select me-2">
                                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All</option>
                                        <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo $filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="succeeded" <?php echo $filter === 'succeeded' ? 'selected' : ''; ?>>Succeeded</option>
                                        <option value="failed" <?php echo $filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (isset($transactions['success']) && $transactions['success'] && !empty($transactions['transactions'])): ?>
                                        <?php foreach ($transactions['transactions'] as $transaction): ?>
                                            <?php 
                                            // Get direct DB data for missing fields
                                            $transactionId = isset($transaction['id']) ? $transaction['id'] : 
                                                (isset($transaction['transaction_id']) ? $transaction['transaction_id'] : 0);
                                            
                                            if ($transactionId > 0) {
                                                // Get full transaction data from the database
                                                $query = "SELECT t.*, u.first_name, u.last_name 
                                                          FROM transactions t 
                                                          LEFT JOIN users u ON t.user_id = u.uid 
                                                          WHERE t.transaction_id = ?";
                                                $stmt = $conn->prepare($query);
                                                $stmt->bind_param('i', $transactionId);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                $fullTransaction = $result->fetch_assoc();
                                                
                                                // If we have direct DB data, use it
                                                if ($fullTransaction) {
                                                    $transaction = array_merge($transaction, $fullTransaction);
                                                }
                                            }
                                            
                                            // Ensure we have all needed fields with fallbacks
                                            $id = isset($transaction['transaction_id']) ? $transaction['transaction_id'] : 
                                                 (isset($transaction['id']) ? $transaction['id'] : '--');
                                                 
                                            $firstName = isset($transaction['first_name']) ? $transaction['first_name'] : '';
                                            $lastName = isset($transaction['last_name']) ? $transaction['last_name'] : '';
                                            $userName = isset($transaction['userName']) ? $transaction['userName'] : "$firstName $lastName";
                                            
                                            $amount = isset($transaction['amount']) ? $transaction['amount'] : 0;
                                            
                                            $paymentMethod = isset($transaction['payment_method_type']) ? $transaction['payment_method_type'] : 
                                                           (isset($transaction['type']) ? $transaction['type'] : '--');
                                            
                                            $status = isset($transaction['status']) ? $transaction['status'] : '--';
                                            
                                            $createdAt = isset($transaction['created_at']) ? $transaction['created_at'] : 
                                                       (isset($transaction['date']) ? $transaction['date'] : null);
                                                       
                                            $updatedAt = isset($transaction['updated_at']) ? $transaction['updated_at'] : null;
                                            
                                            $paymentIntentId = isset($transaction['payment_intent_id']) ? $transaction['payment_intent_id'] : '--';
                                            ?>
                                            
                                            <tr>
                                                <td><?php echo $id; ?></td>
                                                <td><?php echo htmlspecialchars($userName); ?></td>
                                                <td>₱<?php echo number_format($amount, 2); ?></td>
                                                <td><?php echo htmlspecialchars($paymentMethod); ?></td>
                                                <td>
                                                    <span class="badge rounded-pill bg-<?php 
                                                        echo $status === 'succeeded' ? 'success' : 
                                                            ($status === 'failed' ? 'danger' : 
                                                                ($status === 'processing' ? 'warning' : 'info')); 
                                                    ?>">
                                                        <?php echo ucfirst($status); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $createdAt ? date('M d, Y H:i', strtotime($createdAt)) : '--'; ?></td>
                                                <td><?php echo $updatedAt ? date('M d, Y H:i', strtotime($updatedAt)) : '--'; ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $id; ?>">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        
                                                        <?php if (in_array($status, ['pending', 'processing'])): ?>
                                                            <form method="post" class="d-inline">
                                                                <input type="hidden" name="transaction_id" value="<?php echo $id; ?>">
                                                                <input type="hidden" name="payment_intent_id" value="<?php echo $paymentIntentId; ?>">
                                                                <button type="submit" name="check_status" class="btn btn-sm btn-warning">
                                                                    <i class="bi bi-arrow-repeat"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        
                                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateModal<?php echo $id; ?>">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                    </div>
                                                    
                                                    <!-- View Modal -->
                                                    <div class="modal fade" id="viewModal<?php echo $id; ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Transaction #<?php echo $id; ?> Details</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="row mb-3">
                                                                        <div class="col-md-6">
                                                                            <h6>Transaction Information</h6>
                                                                            <ul class="list-group">
                                                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                    ID
                                                                                    <span><?php echo $id; ?></span>
                                                                                </li>
                                                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                    Amount
                                                                                    <span>₱<?php echo number_format($amount, 2); ?></span>
                                                                                </li>
                                                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                    Status
                                                                                    <span class="badge rounded-pill bg-<?php 
                                                                                        echo $status === 'succeeded' ? 'success' : 
                                                                                            ($status === 'failed' ? 'danger' : 
                                                                                                ($status === 'processing' ? 'warning' : 'info')); 
                                                                                    ?>"><?php echo ucfirst($status); ?></span>
                                                                                </li>
                                                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                    Type
                                                                                    <span><?php echo htmlspecialchars(isset($transaction['transaction_type']) ? $transaction['transaction_type'] : '--'); ?></span>
                                                                                </li>
                                                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                    Payment Method
                                                                                    <span><?php echo htmlspecialchars($paymentMethod); ?></span>
                                                                                </li>
                                                                            </ul>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <h6>Payment Information</h6>
                                                                            <ul class="list-group">
                                                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                    Payment Intent ID
                                                                                    <span class="text-truncate" style="max-width: 200px;"><?php echo $paymentIntentId; ?></span>
                                                                                </li>
                                                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                    Payment Method ID
                                                                                    <span class="text-truncate" style="max-width: 200px;"><?php echo isset($transaction['payment_method_id']) ? $transaction['payment_method_id'] : '--'; ?></span>
                                                                                </li>
                                                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                    Created
                                                                                    <span><?php echo $createdAt ? date('M d, Y H:i', strtotime($createdAt)) : '--'; ?></span>
                                                                                </li>
                                                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                    Updated
                                                                                    <span><?php echo $updatedAt ? date('M d, Y H:i', strtotime($updatedAt)) : '--'; ?></span>
                                                                                </li>
                                                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                    Age
                                                                                    <span><?php 
                                                                                        if ($createdAt) {
                                                                                            $created = new DateTime($createdAt);
                                                                                        $now = new DateTime();
                                                                                        $diff = $created->diff($now);
                                                                                        if ($diff->d > 0) {
                                                                                            echo $diff->d . ' days, ' . $diff->h . ' hours';
                                                                                        } else {
                                                                                            echo $diff->h . ' hours, ' . $diff->i . ' minutes';
                                                                                            }
                                                                                        } else {
                                                                                            echo '--';
                                                                                        }
                                                                                    ?></span>
                                                                                </li>
                                                                            </ul>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="row">
                                                                        <div class="col-12">
                                                                            <h6>Description</h6>
                                                                            <p><?php echo htmlspecialchars(isset($transaction['description']) ? $transaction['description'] : ''); ?></p>
                                                                            
                                                                            <?php if (!empty($transaction['error_message'])): ?>
                                                                                <h6>Error Message</h6>
                                                                                <pre class="bg-light p-3"><?php echo htmlspecialchars($transaction['error_message']); ?></pre>
                                                                            <?php endif; ?>
                                                                            
                                                                            <?php if (!empty($transaction['metadata'])): ?>
                                                                                <h6>Metadata</h6>
                                                                                <pre class="bg-light p-3"><?php echo htmlspecialchars($transaction['metadata']); ?></pre>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Update Modal -->
                                                    <div class="modal fade" id="updateModal<?php echo $id; ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <form method="post">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Update Transaction #<?php echo $id; ?></h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="transaction_id" value="<?php echo $id; ?>">
                                                                        
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Current Status</label>
                                                                            <input type="text" class="form-control" value="<?php echo ucfirst($status); ?>" readonly>
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label for="new_status" class="form-label">New Status</label>
                                                                            <select class="form-select" name="new_status" id="new_status" required>
                                                                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                                <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                                                <option value="succeeded" <?php echo $status === 'succeeded' ? 'selected' : ''; ?>>Succeeded</option>
                                                                                <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                                                            </select>
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label for="error_message" class="form-label">Error Message (optional)</label>
                                                                            <textarea class="form-control" name="error_message" id="error_message" rows="3"><?php echo htmlspecialchars(isset($transaction['error_message']) ? $transaction['error_message'] : ''); ?></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No transactions found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if (isset($transactions['total_pages']) && $transactions['total_pages'] > 1): ?>
                            <nav aria-label="Transaction pagination">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&filter=<?php echo $filter; ?>">Previous</a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $transactions['total_pages']; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $transactions['total_pages']): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&filter=<?php echo $filter; ?>">Next</a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include ROOT_PATH . '/components/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-refresh the page every 5 minutes to keep data current
            setTimeout(function() {
                window.location.reload();
            }, 5 * 60 * 1000);
        });
    </script>
</body>
</html> 