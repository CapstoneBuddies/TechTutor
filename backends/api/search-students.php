<?php
require_once '../main.php';

try {
    // Check if user is logged in and is a TechGuru
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
        throw new Exception('Unauthorized access');
    }

    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        throw new Exception('Invalid request data');
    }

    // Validate required fields
    if (!isset($data['query']) || !isset($data['class_id'])) {
        throw new Exception('Missing required fields');
    }

    $query = $data['query'];
    $class_id = intval($data['class_id']);

    // Ensure query is at least 4 characters
    if (strlen($query) < 4) {
        throw new Exception('Search query must be at least 4 characters');
    }

    // Search for students not already enrolled in the class
    $stmt = $conn->prepare("
        SELECT u.uid AS 'user_id', u.first_name, u.last_name, u.email, u.profile_picture
        FROM users u
        LEFT JOIN enrollments e ON u.uid = e.student_id AND e.class_id = ?
        WHERE u.role = 'TECHKID'
        AND e.enrollment_id IS NULL
        AND (
            u.email LIKE ? OR
            CONCAT(u.first_name, ' ', u.last_name) LIKE ?
        )
        LIMIT 10
    ");

    $searchPattern = $query . '%';
    $stmt->bind_param('iss', $class_id, $searchPattern, $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = $result->fetch_all(MYSQLI_ASSOC);

    // Log successful search
    log_error("Student search performed for class {$class_id} with query: {$query}", "info");

    echo json_encode([
        'success' => true,
        'students' => $students
    ]);

} catch (Exception $e) {
    log_error("Student search error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 