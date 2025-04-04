<?php
require_once '../main.php';
require_once BACKEND.'transactions_management.php';

// Initialize response array
$response = ['success' => false];

// Get the current endpoint from the URL path
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = parse_url(BASE, PHP_URL_PATH);
$endpoint = str_replace($basePath, '', $requestUri);
$endpoint = strtok($endpoint, '?'); // Remove query string

// Authentication check for all endpoints except webhook
if ($endpoint !== 'webhook') {
    // Verify user is logged in
    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        $response['message'] = 'Please login to continue';
        echo json_encode($response);
        exit;
    }
}

// Log request for debugging
log_error("Transaction handler processing endpoint: $endpoint", "info");

// Get transactions list
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $endpoint === 'get-transactions') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $role = isset($_GET['role']) ? $_GET['role'] : $_SESSION['role'];
    $userId = isset($_GET['userId']) ? (int)$_GET['userId'] : $_SESSION['user'];

    $result = getTransactions($page, $filter, $role, $userId);
    echo json_encode($result);
    exit;
}

// Get transaction details
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $endpoint === 'get-transaction-details') {
    if (!isset($_GET['id'])) {
        $response['message'] = 'Transaction ID is required';
        echo json_encode($response);
        exit;
    }

    $result = getTransactionDetails($_GET['id']);
    
    // Check if user has permission to view this transaction
    if ($result['success'] && $_SESSION['role'] !== 'ADMIN' && $result['transaction']['userId'] != $_SESSION['user']) {
        $response['message'] = 'You do not have permission to view this transaction';
        echo json_encode($response);
        exit;
    }

    echo json_encode($result);
    exit;
}

// Export transactions
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $endpoint === 'export-transactions') {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $role = $_SESSION['role'];
    $userId = $_SESSION['user'];
    
    // Only admins can export
    if ($role !== 'ADMIN') {
        $response['message'] = 'You do not have permission to export transactions';
        echo json_encode($response);
        exit;
    }

    $result = exportTransactions($filter, $role, $userId);
    
    if ($result['success']) {
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="transactions_' . date('Y-m-d') . '.csv"');
        
        // Create a file pointer connected to the output stream
        $output = fopen('php://output', 'w');
        
        // Output the column headings
        fputcsv($output, array_keys($result['transactions'][0]));
        
        // Output the data
        foreach ($result['transactions'] as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    echo json_encode($result);
    exit;
}

// Create transaction dispute
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $endpoint === 'create-dispute') {
    // Validate required fields
    if (!isset($_POST['transaction_id']) || !isset($_POST['reason']) || empty($_POST['reason'])) {
        $response['message'] = 'Transaction ID and reason are required';
        echo json_encode($response);
        exit;
    }
    
    $transactionId = (int)$_POST['transaction_id'];
    $userId = $_SESSION['user'];
    $reason = $_POST['reason'];
    
    // Only TECHKID and TECHGURU can create disputes
    if ($_SESSION['role'] === 'ADMIN') {
        $response['message'] = 'Admins cannot create disputes';
        echo json_encode($response);
        exit;
    }
    
    $result = createTransactionDispute($transactionId, $userId, $reason);
    echo json_encode($result);
    exit;
}

// Get disputes list
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $endpoint === 'get-disputes') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    $role = $_SESSION['role'];
    $userId = $_SESSION['user'];

    $result = getDisputes($page, $status, $role, $userId);
    echo json_encode($result);
    exit;
}

// Update dispute status (Admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $endpoint === 'update-dispute') {
    // Verify user is admin
    if ($_SESSION['role'] !== 'ADMIN') {
        $response['message'] = 'You do not have permission to update dispute status';
        echo json_encode($response);
        exit;
    }
    
    // Validate required fields
    if (!isset($_POST['dispute_id']) || !isset($_POST['status'])) {
        $response['message'] = 'Dispute ID and status are required';
        echo json_encode($response);
        exit;
    }
    
    $disputeId = (int)$_POST['dispute_id'];
    $status = $_POST['status'];
    $adminNotes = isset($_POST['admin_notes']) ? $_POST['admin_notes'] : null;
    $adminId = $_SESSION['user'];
    
    // Validate status
    $validStatuses = ['pending', 'under_review', 'resolved', 'rejected', 'cancelled'];
    if (!in_array($status, $validStatuses)) {
        $response['message'] = 'Invalid status value';
        echo json_encode($response);
        exit;
    }
    
    $result = updateDisputeStatus($disputeId, $status, $adminNotes, $adminId);
    echo json_encode($result);
    exit;
}

// Cancel dispute (can be done by the disputing user)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $endpoint === 'cancel-dispute') {
    // Validate required fields
    if (!isset($_POST['dispute_id'])) {
        $response['message'] = 'Dispute ID is required';
        echo json_encode($response);
        exit;
    }
    
    $disputeId = (int)$_POST['dispute_id'];
    $userId = $_SESSION['user'];
    $role = $_SESSION['role'];
    
    // Check if user has permission to cancel this dispute
    if ($role !== 'ADMIN') {
        // Get the dispute details to verify ownership
        $query = "SELECT * FROM transaction_disputes WHERE dispute_id = ?";
        $stmt = $GLOBALS['conn']->prepare($query);
        $stmt->bind_param('i', $disputeId);
        $stmt->execute();
        $dispute = $stmt->get_result()->fetch_assoc();
        
        if (!$dispute || $dispute['user_id'] != $userId) {
            $response['message'] = 'You do not have permission to cancel this dispute';
            echo json_encode($response);
            exit;
        }
    }
    
    // Cancel the dispute
    $result = updateDisputeStatus($disputeId, 'cancelled', 'Cancelled by user', $userId);
    echo json_encode($result);
    exit;
}

// Process refund (Admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $endpoint === 'process-refund') {
    // Verify user is admin
    if ($_SESSION['role'] !== 'ADMIN') {
        $response['message'] = 'You do not have permission to process refunds';
        echo json_encode($response);
        exit;
    }
    
    // Validate required fields
    if (!isset($_POST['transaction_id']) || !isset($_POST['dispute_id']) || !isset($_POST['amount'])) {
        $response['message'] = 'Transaction ID, dispute ID, and amount are required';
        echo json_encode($response);
        exit;
    }
    
    $transactionId = (int)$_POST['transaction_id'];
    $disputeId = (int)$_POST['dispute_id'];
    $amount = (float)$_POST['amount'];
    $adminId = $_SESSION['user'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : null;
    
    // Validate amount
    if ($amount <= 0) {
        $response['message'] = 'Refund amount must be greater than zero';
        echo json_encode($response);
        exit;
    }
    
    $result = processRefund($transactionId, $disputeId, $amount, $adminId, $notes);
    echo json_encode($result);
    exit;
}

// Process PayMongo webhook
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $endpoint === 'webhook') {
    require_once BACKEND.'api/payment-webhook.php';
    exit;
}

// Invalid request
$response['message'] = 'Invalid request';
echo json_encode($response);
exit;
?>
