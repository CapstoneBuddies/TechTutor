<?php
require_once 'config.php';

/**
 * Class management functions for TechTutor platform
 * Handles all class-related database operations for TechGuru users
 */

/**
 * Get all classes for a TechGuru
 * 
 * @param int $tutor_id The ID of the tutor
 * @return array Array of classes with their details
 */
function getTechGuruClasses($tutor_id) {
    global $conn;
    
    $query = "SELECT c.*, s.subject_name, COUNT(DISTINCT cs.user_id) as student_count 
             FROM class c 
             LEFT JOIN subject s ON c.subject_id = s.subject_id 
             LEFT JOIN class_schedule cs ON c.class_id = cs.class_id AND cs.role = 'STUDENT'
             WHERE c.tutor_id = ? 
             GROUP BY c.class_id 
             ORDER BY c.start_date DESC";
             
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $tutor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting tutor classes: " . $e->getMessage());
        return [];
    }
}

/**
 * Get statistics about a TechGuru's classes
 * 
 * @param int $tutor_id The ID of the tutor
 * @return array Array containing class statistics
 */
function getClassStats($tutor_id) {
    global $conn;
    
    $query = "SELECT 
                SUM(CASE WHEN c.is_active = 1 THEN 1 ELSE 0 END) as active_classes,
                COUNT(DISTINCT c.class_id) as total_classes,
                COUNT(DISTINCT cs.user_id) as total_students,
                SUM(CASE WHEN c.is_active = 0 THEN 1 ELSE 0 END) as completed_classes
              FROM class c
              LEFT JOIN class_schedule cs ON c.class_id = cs.class_id AND cs.role = 'STUDENT'
              WHERE c.tutor_id = ?";
              
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $tutor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error getting class stats: " . $e->getMessage());
        return [
            'active_classes' => 0,
            'total_classes' => 0,
            'total_students' => 0,
            'completed_classes' => 0
        ];
    }
}

/**
 * Delete a class and all its related data
 * 
 * @param int $class_id The ID of the class to delete
 * @param int $tutor_id The ID of the tutor who owns the class
 * @return bool True if deletion was successful, false otherwise
 */
function deleteClass($class_id, $tutor_id) {
    global $conn;
    
    $conn->begin_transaction();
    
    try {
        // First verify the class belongs to this tutor
        $verify = $conn->prepare("SELECT tutor_id, class_name FROM class WHERE class_id = ?");
        $verify->bind_param("i", $class_id);
        $verify->execute();
        $result = $verify->get_result()->fetch_assoc();
        
        if (!$result || $result['tutor_id'] != $tutor_id) {
            throw new Exception("Unauthorized deletion attempt");
        }
        
        // Delete class schedules
        $stmt = $conn->prepare("DELETE FROM class_schedule WHERE class_id = ?");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();

        // Delete any class files
        $stmt = $conn->prepare("DELETE FROM file_management WHERE class_id = ?");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        
        // Delete the class
        $stmt = $conn->prepare("DELETE FROM class WHERE class_id = ?");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        
        // Add notification for students
        require_once 'notifications.php';
        insertNotification(
            null,
            'TECHKID',
            'Class "' . htmlspecialchars($result['class_name']) . '" has been cancelled.',
            BASE . 'dashboard/courses',
            $class_id,
            'bi-exclamation-circle',
            'text-danger'
        );
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error deleting class: " . $e->getMessage());
        return false;
    }
}

/**
 * Get a single class's details
 * 
 * @param int $class_id The ID of the class
 * @param int $tutor_id The ID of the tutor (for verification)
 * @return array|null Class details or null if not found/unauthorized
 */
function getClassDetails($class_id, $tutor_id) {
    global $conn;
    
    $query = "SELECT c.*, s.subject_name, s.subject_desc,
                     COUNT(DISTINCT cs.user_id) as student_count
              FROM class c
              LEFT JOIN subject s ON c.subject_id = s.subject_id
              LEFT JOIN class_schedule cs ON c.class_id = cs.class_id AND cs.role = 'STUDENT'
              WHERE c.class_id = ? AND c.tutor_id = ?
              GROUP BY c.class_id";
              
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $class_id, $tutor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error getting class details: " . $e->getMessage());
        return null;
    }
}

/**
 * Update a class's status (active/inactive)
 * 
 * @param int $class_id The ID of the class
 * @param int $tutor_id The ID of the tutor (for verification)
 * @param bool $is_active The new status
 * @return bool True if update was successful
 */
function updateClassStatus($class_id, $tutor_id, $is_active) {
    global $conn;
    
    try {
        // Verify ownership
        $verify = $conn->prepare("SELECT tutor_id FROM class WHERE class_id = ?");
        $verify->bind_param("i", $class_id);
        $verify->execute();
        $result = $verify->get_result()->fetch_assoc();
        
        if (!$result || $result['tutor_id'] != $tutor_id) {
            return false;
        }
        
        $stmt = $conn->prepare("UPDATE class SET is_active = ? WHERE class_id = ?");
        $stmt->bind_param("ii", $is_active, $class_id);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error updating class status: " . $e->getMessage());
        return false;
    }
}
