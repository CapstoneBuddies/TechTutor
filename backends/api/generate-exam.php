<?php
require_once '../main.php';
require_once BACKEND.'class_management.php';

header('Content-Type: application/json');

// Ensure user is logged in and is a TechGuru
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHGURU') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$class_id = isset($input['class_id']) ? intval($input['class_id']) : 0;
$exam_type = isset($input['exam_type']) ? trim($input['exam_type']) : '';
$exam_items = isset($input['exam_items']) ? intval($input['exam_items']) : 30;
$exam_start = isset($input['exam_start']) ? trim($input['exam_start']) : '';
$exam_end = isset($input['exam_end']) ? trim($input['exam_end']) : '';

if ($class_id <= 0 || !$exam_type || $exam_items <= 0 || !$exam_start || !$exam_end) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid parameters']);
    exit();
}

// Validate exam_type
$valid_types = ['diagnostic', 'midterm', 'final', 'quiz'];
if (!in_array($exam_type, $valid_types)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid exam type']);
    exit();
}

// Validate datetime format (basic)
if (strtotime($exam_start) === false || strtotime($exam_end) === false || strtotime($exam_start) >= strtotime($exam_end)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid exam start or end datetime']);
    exit();
}

try {
    // Generate exam JSON string
    $exam_json_str = generateExamJSON($class_id, $exam_type, $exam_items);
    if (!$exam_json_str) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to generate exam JSON']);
        exit();
    }

    // Calculate duration in minutes
    $start_ts = strtotime($exam_start);
    $end_ts = strtotime($exam_end);
    $duration = intval(($end_ts - $start_ts) / 60);

    // Insert into exams table
    $stmt = $conn->prepare("INSERT INTO exams (class_id, exam_item, exam_status, exam_start_datetime, exam_end_datetime, duration, exam_type, created_by) VALUES (?, ?, 'active', ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
        exit();
    }

    $stmt->bind_param(
        "isssssi",
        $class_id,
        $exam_json_str,
        $exam_start,
        $exam_end,
        $duration,
        $exam_type,
        $_SESSION['user']
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database execute error: ' . $stmt->error]);
    }
    $stmt->close();
} catch(Exception $e) {
    log_error("Error Generating Exams: ".$e, 'database');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unexpected Error Occured']);

}

?>
