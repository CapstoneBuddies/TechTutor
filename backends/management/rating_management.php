<?php
class RatingManagement {
    private $db;

    public function __construct() {
        global $conn;
        $this->db = $conn;
    }

    /**
     * Submit a rating for a class session
     */
    public function submitSessionRating($sessionId, $studentId, $rating, $feedback, $tutorId) {
        try {
            // Verify the session exists and belongs to the tutor
            $sql = "SELECT schedule_id FROM class_schedule 
                    WHERE schedule_id = ? AND user_id = ? AND status = 'completed'";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $sessionId, $tutorId);
            $stmt->execute();
            if (!$stmt->get_result()->num_rows) {
                throw new Exception("Invalid session or not completed yet");
            }

            // Check if rating already exists
            $sql = "SELECT rating_id FROM session_feedback 
                    WHERE session_id = ? AND student_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $sessionId, $studentId);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("You have already submitted feedback for this session");
            }

            // Insert session feedback
            $sql = "INSERT INTO session_feedback 
                    (session_id, student_id, tutor_id, rating, feedback, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("iiiis", $sessionId, $studentId, $tutorId, $rating, $feedback);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to submit feedback");
            }

            // The trigger will automatically update the tutor's rating
            return true;

        } catch (Exception $e) {
            log_error("Error in submitSessionRating: " . $e->getMessage(), 'database');
            throw $e;
        }
    }

    /**
     * Submit a rating for a class
     */
    public function submitClassRating($classId, $studentId, $rating, $review) {
        try {
            // Verify student is enrolled in the class
            $sql = "SELECT enrollment_id FROM enrollments 
                    WHERE class_id = ? AND student_id = ? AND status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $classId, $studentId);
            $stmt->execute();
            if (!$stmt->get_result()->num_rows) {
                throw new Exception("You are not enrolled in this class");
            }

            // Check if rating already exists
            $sql = "SELECT rating_id FROM class_ratings 
                    WHERE class_id = ? AND student_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $classId, $studentId);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("You have already rated this class");
            }

            // Insert class rating
            $sql = "INSERT INTO class_ratings 
                    (class_id, student_id, rating, review, created_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("iiis", $classId, $studentId, $rating, $review);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to submit class rating");
            }

            return true;

        } catch (Exception $e) {
            log_error("Error in submitClassRating: " . $e->getMessage(), 'database');
            throw $e;
        }
    }

    /**
     * Get session feedback for a specific session
     */
    public function getSessionFeedback($sessionId) {
        try {
            $sql = "SELECT sf.*, 
                           CONCAT(s.first_name, ' ', s.last_name) as student_name,
                           s.profile_picture as student_picture
                    FROM session_feedback sf
                    JOIN users s ON sf.student_id = s.uid
                    WHERE sf.session_id = ?
                    ORDER BY sf.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $sessionId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        } catch (Exception $e) {
            log_error("Error in getSessionFeedback: " . $e->getMessage(), 'database');
            return [];
        }
    }

    /**
     * Get class rating and reviews
     */
    public function getClassRating($classId) {
        try {
            $sql = "SELECT cr.*, 
                           CONCAT(s.first_name, ' ', s.last_name) as student_name,
                           s.profile_picture as student_picture
                    FROM class_ratings cr
                    JOIN users s ON cr.student_id = s.uid
                    WHERE cr.class_id = ?
                    ORDER BY cr.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $classId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        } catch (Exception $e) {
            log_error("Error in getClassRating: " . $e->getMessage(), 'database');
            return [];
        }
    }

    /**
     * Get all feedback for a tutor with pagination
     */
    public function getTutorFeedback($tutorId, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT sf.*, 
                           CONCAT(s.first_name, ' ', s.last_name) as student_name,
                           s.profile_picture as student_picture,
                           c.class_name,
                           cs.session_date
                    FROM session_feedback sf
                    JOIN users s ON sf.student_id = s.uid
                    JOIN class_schedule cs ON sf.session_id = cs.schedule_id
                    JOIN class c ON cs.class_id = c.class_id
                    WHERE sf.tutor_id = ?
                    ORDER BY sf.created_at DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("iii", $tutorId, $limit, $offset);
            $stmt->execute();
            $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Get total count for pagination
            $sql = "SELECT COUNT(*) as total FROM session_feedback WHERE tutor_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $tutorId);
            $stmt->execute();
            $total = $stmt->get_result()->fetch_assoc()['total'];

            return [
                'feedback' => $results,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];

        } catch (Exception $e) {
            log_error("Error in getTutorFeedback: " . $e->getMessage(), 'database');
            return [
                'feedback' => [],
                'total' => 0,
                'pages' => 0,
                'current_page' => $page
            ];
        }
    }

    /**
     * Check if a student has already rated a session
     */
    public function hasStudentRatedSession($sessionId, $studentId) {
        try {
            $sql = "SELECT rating_id FROM session_feedback 
                    WHERE session_id = ? AND student_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $sessionId, $studentId);
            $stmt->execute();
            return $stmt->get_result()->num_rows > 0;

        } catch (Exception $e) {
            log_error("Error in hasStudentRatedSession: " . $e->getMessage(), 'database');
            return false;
        }
    }

    /**
     * Get rating statistics for a tutor
     */
    public function getTutorRatingStats($tutorId) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_ratings,
                        u.rating as average_rating,
                        u.rating_count,
                        COUNT(CASE WHEN sf.rating = 5 THEN 1 END) as five_star,
                        COUNT(CASE WHEN sf.rating = 4 THEN 1 END) as four_star,
                        COUNT(CASE WHEN sf.rating = 3 THEN 1 END) as three_star,
                        COUNT(CASE WHEN sf.rating = 2 THEN 1 END) as two_star,
                        COUNT(CASE WHEN sf.rating = 1 THEN 1 END) as one_star
                    FROM users u
                    LEFT JOIN session_feedback sf ON u.uid = sf.tutor_id
                    WHERE u.uid = ?
                    GROUP BY u.uid";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $tutorId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            if (!$result) {
                return [
                    'total_ratings' => 0,
                    'average_rating' => 0,
                    'rating_count' => 0,
                    'five_star' => 0,
                    'four_star' => 0,
                    'three_star' => 0,
                    'two_star' => 0,
                    'one_star' => 0
                ];
            }

            return $result;

        } catch (Exception $e) {
            log_error("Error in getTutorRatingStats: " . $e->getMessage(), 'database');
            return null;
        }
    }
}
