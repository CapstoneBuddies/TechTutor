<?php
require_once '../main.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'ADMIN') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add-course':
            $courseName = $_POST['course_name'] ?? '';
            $courseDesc = $_POST['course_desc'] ?? '';
            $result = addCourse($courseName, $courseDesc);
            break;

        case 'edit-course':
            $courseId = $_POST['course_id'] ?? '';
            $courseName = $_POST['course_name'] ?? '';
            $courseDesc = $_POST['course_desc'] ?? '';
            
            if (!$courseId || !$courseName) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Course ID and name are required']);
                exit();
            }

            $stmt = $conn->prepare("UPDATE course SET course_name = ?, course_desc = ? WHERE course_id = ?");
            $stmt->bind_param("ssi", $courseName, $courseDesc, $courseId);
            $result = ['success' => $stmt->execute(), 'message' => 'Course updated successfully'];
            break;

        case 'delete-course':
            $courseId = $_POST['course_id'] ?? '';
            
            if (!$courseId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Course ID is required']);
                exit();
            }

            // Check if course has subjects
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM subject WHERE course_id = ?");
            $stmt->bind_param("i", $courseId);
            $stmt->execute();
            $count = $stmt->get_result()->fetch_assoc()['count'];

            if ($count > 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cannot delete course with existing subjects']);
                exit();
            }

            $stmt = $conn->prepare("DELETE FROM course WHERE course_id = ?");
            $stmt->bind_param("i", $courseId);
            $result = ['success' => $stmt->execute(), 'message' => 'Course deleted successfully'];
            break;

        case 'toggle-subject':
            $subjectId = $_POST['subject_id'] ?? null;
            $status = $_POST['status'] ?? null;
            
            if (!$subjectId || !isset($status)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Subject ID and status are required']);
                exit();
            }

            $result = updateSubjectStatus($subjectId, $status === 'true');
            break;

        case 'add-subject':
            $courseId = $_POST['course_id'] ?? '';
            $subjectName = $_POST['subject_name'] ?? '';
            $subjectDesc = $_POST['subject_desc'] ?? '';
            $result = addSubject($courseId, $subjectName, $subjectDesc);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit();
    }

    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);
} catch (Exception $e) {
    log_error("Error in course handler: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
