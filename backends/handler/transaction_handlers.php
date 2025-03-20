<?php

// Initialize response array
$response = ['success' => false];

// Verify user is logged in
session_start();
if (!isset($_SESSION['user'])) {
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

// Get transactions list
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $role = $_SESSION['role'];
    $userId = $_SESSION['user'];

    $result = getTransactions($page, $filter, $role, $userId);
    echo json_encode($result);
    exit;
}

// Get transaction details
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'details') {
    if (!isset($_GET['id'])) {
        $response['message'] = 'Transaction ID is required';
        echo json_encode($response);
        exit;
    }

    $result = getTransactionDetails($_GET['id']);
    
    // Check if user has permission to view this transaction
    if ($result['success'] && $_SESSION['role'] !== 'ADMIN' && $result['transaction']['userId'] !== $_SESSION['user']) {
        $response['message'] = 'You do not have permission to view this transaction';
        echo json_encode($response);
        exit;
    }

    echo json_encode($result);
    exit;
}

// Export transactions
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'export') {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $role = $_SESSION['role'];
    $userId = $_SESSION['user'];

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

// Invalid request
$response['message'] = 'Invalid request';
echo json_encode($response);
exit;
?>
