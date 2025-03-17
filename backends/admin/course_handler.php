<?php
require_once '../main.php';
require_once '../admin_management.php';

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

            $conn->begin_transaction();
            try {
                // Delete related classes first
                $stmt = $conn->prepare("DELETE FROM class WHERE subject_id IN (SELECT subject_id FROM subject WHERE course_id = ?)");
                $stmt->bind_param("i", $courseId);
                $stmt->execute();

                // Delete subjects
                $stmt = $conn->prepare("DELETE FROM subject WHERE course_id = ?");
                $stmt->bind_param("i", $courseId);
                $stmt->execute();

                // Finally, delete the course
                $stmt = $conn->prepare("DELETE FROM course WHERE course_id = ?");
                $stmt->bind_param("i", $courseId);
                $stmt->execute();

                $conn->commit();

                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Course deleted successfully']);
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                log_error("Error deleting course: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to delete course']);
                exit();
            }
            break;




        case 'toggle-subject':
            $subjectId = $_POST['subject_id'] ?? null;
            $status = $_POST['status'] ?? null;

            if (!$subjectId || !isset($status)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Subject ID and status are required']);
                exit();
            }

            $isActive = $status == 1 ? 1 : 0; // Convert properly

            $result = updateSubjectStatus($subjectId, $isActive);
            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
            exit();
            break;


        case 'add-subject':
            $courseId = $_POST['course_id'] ?? '';
            $subjectName = $_POST['subject_name'] ?? '';
            $subjectDesc = $_POST['subject_desc'] ?? '';
            $result = addSubject($courseId, $subjectName, $subjectDesc);
            break;

        case 'edit-subject':
            $subjectId = $_POST['subject_id'] ?? '';
            $subjectName = $_POST['subject_name'] ?? '';
            $subjectDesc = $_POST['subject_desc'] ?? '';

            if (!$subjectId || !$subjectName) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Subject ID and name are required']);
                exit();
            }

            $stmt = $conn->prepare("UPDATE subject SET subject_name = ?, subject_desc = ? WHERE subject_id = ?");
            $stmt->bind_param("ssi", $subjectName, $subjectDesc, $subjectId);
            $result = ['success' => $stmt->execute(), 'message' => 'Subject updated successfully'];
            break;
            
        case 'delete-subject':
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
