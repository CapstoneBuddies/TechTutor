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
                    WHERE schedule_id = ? AND status = 'completed'";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $sessionId);
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
                        u.first_name,
                        u.last_name,
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
                    WHERE u.uid = ? AND u.role = 'TECHGURU'
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
                    'one_star' => 0,
                    'first_name' => '',
                    'last_name' => ''
                ];
            }

            return $result;

        } catch (Exception $e) {
            log_error("Error in getTutorRatingStats: " . $e->getMessage(), 'database');
            return [
                'total_ratings' => 0,
                'average_rating' => 0,
                'rating_count' => 0,
                'five_star' => 0,
                'four_star' => 0,
                'three_star' => 0,
                'two_star' => 0,
                'one_star' => 0,
                'first_name' => '',
                'last_name' => ''
            ];
        }
    }

    /**
     * Get all feedback given by a student for a specific class
     * 
     * @param int $class_id The class ID
     * @param int $student_id The student ID
     * @return array List of feedback entries
     */
    public function getStudentFeedbacks($class_id, $student_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT sf.rating_id, sf.rating, sf.feedback, sf.created_at, sf.is_archived,
                       cs.session_date, cs.start_time, cs.end_time, cs.schedule_id
                FROM session_feedback sf
                JOIN class_schedule cs ON sf.session_id = cs.schedule_id
                WHERE cs.class_id = ? AND sf.student_id = ?
                ORDER BY sf.created_at DESC
            ");
            
            $stmt->bind_param("ii", $class_id, $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $feedbacks = [];
            while ($row = $result->fetch_assoc()) {
                $feedbacks[] = $row;
            }
            
            return $feedbacks;
        } catch (Exception $e) {
            log_error("Error retrieving student feedbacks: " . $e->getMessage(), "feedback");
            return [];
        }
    }

    /**
     * Get a specific feedback entry and verify access permissions
     * 
     * @param int $rating_id The feedback ID
     * @param int $student_id The student ID for access verification
     * @return array|null The feedback entry or null if not found/unauthorized
     */
    public function getFeedbackById($rating_id, $student_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT sf.*, cs.class_id, cs.session_date
                FROM session_feedback sf
                JOIN class_schedule cs ON sf.session_id = cs.schedule_id
                WHERE sf.rating_id = ? AND sf.student_id = ?
            ");
            
            $stmt->bind_param("ii", $rating_id, $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return null;
            }
            
            return $result->fetch_assoc();
        } catch (Exception $e) {
            log_error("Error retrieving feedback: " . $e->getMessage(), "feedback");
            return null;
        }
    }

    /**
     * Update a feedback entry
     * 
     * @param int $rating_id The feedback ID
     * @param int $student_id The student ID for access verification
     * @param int $rating The new rating value (1-5)
     * @param string $feedback The new feedback text
     * @return array Status and message
     */
    public function updateFeedback($rating_id, $student_id, $rating, $feedback) {
        try {
            // Get the feedback and check permissions
            $feedback_data = $this->getFeedbackById($rating_id, $student_id);
            
            if (!$feedback_data) {
                return ['success' => false, 'message' => 'Feedback not found or you are not authorized to edit it'];
            }
            
            // Check if it's within 24 hours of creation
            if (time() - strtotime($feedback_data['created_at']) >= 86400) {
                return ['success' => false, 'message' => 'Feedback can only be edited within 24 hours of submission'];
            }
            
            // Validate rating
            if ($rating < 1 || $rating > 5) {
                return ['success' => false, 'message' => 'Rating must be between 1 and 5'];
            }
            
            // Update the feedback
            $stmt = $this->db->prepare("
                UPDATE session_feedback 
                SET rating = ?, feedback = ?
                WHERE rating_id = ? AND student_id = ?
            ");
            
            $stmt->bind_param("isii", $rating, $feedback, $rating_id, $student_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update feedback: " . $stmt->error);
            }
            
            // Get the updated feedback
            $updated_feedback = $this->getFeedbackById($rating_id, $student_id);
            
            return [
                'success' => true,
                'message' => 'Feedback updated successfully',
                'data' => $updated_feedback
            ];
            
        } catch (Exception $e) {
            log_error("Error updating feedback: " . $e->getMessage(), "feedback");
            return ['success' => false, 'message' => 'Failed to update feedback: ' . $e->getMessage()];
        }
    }

    /**
     * Archive a feedback entry (hide from TechGuru)
     * 
     * @param int $rating_id The feedback ID
     * @param int $student_id The student ID for access verification
     * @return array Status and message
     */
    public function archiveFeedback($rating_id, $student_id) {
        try {
            // Get the feedback and check permissions
            $feedback_data = $this->getFeedbackById($rating_id, $student_id);
            
            if (!$feedback_data) {
                return ['success' => false, 'message' => 'Feedback not found or you are not authorized to archive it'];
            }
            
            // Archive the feedback
            $stmt = $this->db->prepare("
                UPDATE session_feedback 
                SET is_archived = 1
                WHERE rating_id = ? AND student_id = ?
            ");
            
            $stmt->bind_param("ii", $rating_id, $student_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to archive feedback: " . $stmt->error);
            }
            
            return [
                'success' => true,
                'message' => 'Feedback archived successfully'
            ];
            
        } catch (Exception $e) {
            log_error("Error archiving feedback: " . $e->getMessage(), "feedback");
            return ['success' => false, 'message' => 'Failed to archive feedback: ' . $e->getMessage()];
        }
    }

    /**
     * Unarchive a feedback entry (make visible to TechGuru again)
     * 
     * @param int $rating_id The feedback ID
     * @param int $student_id The student ID for access verification
     * @return array Status and message
     */
    public function unarchiveFeedback($rating_id, $student_id) {
        try {
            // Get the feedback and check permissions
            $feedback_data = $this->getFeedbackById($rating_id, $student_id);
            
            if (!$feedback_data) {
                return ['success' => false, 'message' => 'Feedback not found or you are not authorized to unarchive it'];
            }
            
            // Unarchive the feedback
            $stmt = $this->db->prepare("
                UPDATE session_feedback 
                SET is_archived = 0
                WHERE rating_id = ? AND student_id = ?
            ");
            
            $stmt->bind_param("ii", $rating_id, $student_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to unarchive feedback: " . $stmt->error);
            }
            
            return [
                'success' => true,
                'message' => 'Feedback unarchived successfully'
            ];
            
        } catch (Exception $e) {
            log_error("Error unarchiving feedback: " . $e->getMessage(), "feedback");
            return ['success' => false, 'message' => 'Failed to unarchive feedback: ' . $e->getMessage()];
        }
    }

    /**
     * Get all visible feedback for a specific class (for TechGuru view)
     * 
     * @param int $class_id The class ID
     * @param int $tutor_id The tutor ID for access verification
     * @return array List of feedback entries
     */
    public function getClassFeedbacks($class_id, $tutor_id) {
        try {
            // Verify the tutor owns this class
            $stmt = $this->db->prepare("
                SELECT class_id 
                FROM class 
                WHERE class_id = ? AND tutor_id = ?
            ");
            
            $stmt->bind_param("ii", $class_id, $tutor_id);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows === 0) {
                return [];
            }
            
            // Get all non-archived feedback for this class
            $stmt = $this->db->prepare("
                SELECT sf.rating_id, sf.rating, sf.feedback, sf.created_at,
                       cs.session_date, cs.start_time, cs.schedule_id,
                       u.first_name, u.last_name, u.profile_picture
                FROM session_feedback sf
                JOIN class_schedule cs ON sf.session_id = cs.schedule_id
                JOIN users u ON sf.student_id = u.uid
                WHERE cs.class_id = ? AND sf.tutor_id = ? AND sf.is_archived = 0
                ORDER BY sf.created_at DESC
            ");
            
            $stmt->bind_param("ii", $class_id, $tutor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $feedbacks = [];
            while ($row = $result->fetch_assoc()) {
                $feedbacks[] = $row;
            }
            
            return $feedbacks;
        } catch (Exception $e) {
            log_error("Error retrieving class feedbacks: " . $e->getMessage(), "feedback");
            return [];
        }
    }
}
