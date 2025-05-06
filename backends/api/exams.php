<?php
require_once '../main.php';
require_once BACKEND.'class_management.php';

header('Content-Type: application/json');
$response = ['success' => false];

try {
    // Only allow POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        $response['message'] = 'Method not allowed';
        echo json_encode($response);
        exit;
    }

    // Check if user is logged in and is a TECHKID
    if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'TECHKID') {
        http_response_code(403);
        $response['message'] = 'Unauthorized';
        echo json_encode($response);
        exit;
    }

    // Parse and validate input
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }

    if (!isset($input['class_id'], $input['answers'])) {
        http_response_code(400);
        $response['message'] = 'Missing required parameters: class_id and answers';
        echo json_encode($response);
        exit;
    }

    $class_id = intval($input['class_id']);
    $answers = $input['answers'];
    $student_id = $_SESSION['user'];

    // Validate class_id
    if ($class_id <= 0) {
        throw new Exception('Invalid class ID');
    }

    // Get diagnostics exam data
    $stmt = $conn->prepare("SELECT diagnostics FROM class WHERE class_id = ?");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $stmt->bind_result($diagnostics_json);
    $stmt->fetch();
    $stmt->close();

    if (!$diagnostics_json) {
        http_response_code(404);
        $response['message'] = 'No diagnostic exam found for this class';
        echo json_encode($response);
        exit;
    }

    $diagnostics = json_decode($diagnostics_json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid diagnostics JSON: ' . json_last_error_msg());
    }

    $correct_answers = isset($diagnostics['answers']) ? $diagnostics['answers'] : [];
    $proficiency_criteria = isset($diagnostics['proficiency_criteria']) ? $diagnostics['proficiency_criteria'] : [];

    if (empty($correct_answers)) {
        throw new Exception('No answer key found in diagnostics data');
    }

    if (empty($proficiency_criteria)) {
        throw new Exception('No proficiency criteria found in diagnostics data');
    }

    // Calculate score
    $score = 0;
    $total = count($correct_answers);

    foreach ($correct_answers as $qnum => $correct) {
        $key = 'q' . $qnum;
        if (isset($answers[$key]) && strtolower($answers[$key]) === strtolower($correct)) {
            $score++;
        }
    }

    // Determine proficiency level
    $proficiency_level = 'Unknown';
    foreach ($proficiency_criteria as $level => $max_score) {
        if ($score <= $max_score) {
            $proficiency_level = $level;
            break;
        }
    }

    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Get performance ID for the proficiency level
        $perf_stmt = $conn->prepare("SELECT id FROM performances WHERE level = ?");
        if (!$perf_stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $perf_stmt->bind_param("s", $proficiency_level);
        $perf_stmt->execute();
        $perf_result = $perf_stmt->get_result();
        
        if ($perf_result->num_rows === 0) {
            throw new Exception('Performance level not found: ' . $proficiency_level);
        }
        
        $performance_id = $perf_result->fetch_assoc()['id'];
        $perf_stmt->close();

        // Enroll student with diagnostics results
        $update_stmt = $conn->prepare("UPDATE enrollments SET diagnostics_taken = 1, performance_id = ? 
                                      WHERE student_id = ? AND class_id = ?");
        if (!$update_stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $update_stmt->bind_param("iii", $performance_id, $student_id, $class_id);
        $update_result = $update_stmt->execute();
        
        if (!$update_result) {
            throw new Exception('Failed to update enrollment: ' . $update_stmt->error);
        }
        
        $update_stmt->close();

        // Commit transaction
        $conn->commit();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'score' => $score,
            'total' => $total,
            'proficiency_level' => $proficiency_level
        ]);
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }
} catch (Exception $e) {
    // Log the error
    log_error('Exam API error: ' . $e->getMessage(), 'api');
    
    // Return error response
    http_response_code(500);
    $response['message'] = 'An error occurred: ' . $e->getMessage();
    echo json_encode($response);
    exit;
}