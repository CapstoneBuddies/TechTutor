<?php
class RatingManagement {
    private $db;

    public function __construct() {
        global $conn;
        $this->db = $conn;
    }

    /**
     * Get tutor's teaching performance trends over time
     * @param int $tutor_id The tutor's ID
     * @return array Teaching performance data
     */
    public function getTeachingPerformanceTrends($tutor_id) {
        $trends = [
            'labels' => [],
            'student_performance' => [],
            'completion_rates' => [],
            'attendance_rates' => [],
            'has_data' => false
        ];

        try {
            $sql = "SELECT 
                    DATE_FORMAT(cs.session_date, '%Y-%m') as month,
                    AVG(sp.performance_score) as avg_performance,
                    COUNT(DISTINCT e.enrollment_id) as total_enrollments,
                    COUNT(DISTINCT CASE WHEN e.status = 'completed' THEN e.enrollment_id END) as completed_enrollments,
                    COUNT(DISTINCT CASE WHEN a.status = 'present' THEN a.attendance_id END) as present_count,
                    COUNT(DISTINCT a.attendance_id) as total_attendance
                FROM class c
                JOIN class_schedule cs ON c.class_id = cs.class_id
                LEFT JOIN enrollments e ON c.class_id = e.class_id
                LEFT JOIN student_progress sp ON e.enrollment_id = sp.student_id AND c.class_id = sp.class_id
                LEFT JOIN attendance a ON cs.schedule_id = a.schedule_id
                WHERE c.tutor_id = ? AND cs.session_date <= CURRENT_DATE
                GROUP BY DATE_FORMAT(cs.session_date, '%Y-%m')
                ORDER BY month ASC
                LIMIT 12";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $tutor_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $trends['has_data'] = true;
                while ($row = $result->fetch_assoc()) {
                    $trends['labels'][] = date('M Y', strtotime($row['month']));
                    $trends['student_performance'][] = round($row['avg_performance'] ?? 0, 2);
                    $trends['completion_rates'][] = $row['total_enrollments'] > 0 ? 
                        round(($row['completed_enrollments'] / $row['total_enrollments']) * 100, 2) : 0;
                    $trends['attendance_rates'][] = $row['total_attendance'] > 0 ? 
                        round(($row['present_count'] / $row['total_attendance']) * 100, 2) : 0;
                }
            }

            return $trends;

        } catch (Exception $e) {
            log_error("Error in getTeachingPerformanceTrends: " . $e->getMessage(), 'database');
            return $trends;
        }
    }

    /**
     * Get tutor's rating distribution
     * @param int $tutor_id The tutor's ID
     * @return array Rating distribution data
     */
    public function getRatingDistribution($tutor_id) {
        $distribution = [
            'labels' => ['5 Stars', '4 Stars', '3 Stars', '2 Stars', '1 Star'],
            'counts' => [0, 0, 0, 0, 0],
            'colors' => [
                'rgba(25, 135, 84, 0.8)',   // Green (5 stars)
                'rgba(13, 110, 253, 0.8)',   // Blue (4 stars)
                'rgba(255, 193, 7, 0.8)',    // Yellow (3 stars)
                'rgba(255, 136, 0, 0.8)',    // Orange (2 stars)
                'rgba(220, 53, 69, 0.8)'     // Red (1 star)
            ],
            'has_data' => false
        ];
        
        // Add a function to get the color based on rating count
        $getColor = function($index, $count) use ($distribution) {
            return $count > 0 ? $distribution['colors'][$index] : 'rgba(128, 128, 128, 0.8)'; // Gray for 0 ratings
        };

        try {
            $sql = "SELECT 
                    rating,
                    COUNT(*) as count
                FROM session_feedback
                WHERE tutor_id = ? AND rating IS NOT NULL
                GROUP BY rating
                ORDER BY rating DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $tutor_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $distribution['has_data'] = true;
                while ($row = $result->fetch_assoc()) {
                    $rating_index = 5 - $row['rating'];
                    if ($rating_index >= 0 && $rating_index < 5) {
                        $distribution['counts'][$rating_index] = intval($row['count']);
                    }
                }
            }
            
            // Update colors based on counts
            for ($i = 0; $i < 5; $i++) {
                $distribution['colors'][$i] = $getColor($i, $distribution['counts'][$i]);
            }

            return $distribution;

        } catch (Exception $e) {
            log_error("Error in getRatingDistribution: " . $e->getMessage(), 'database');
            return $distribution;
        }
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
                    AND sf.rating IS NOT NULL
                    AND s.status = 1
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
                    AND cr.status = 'completed'
                    AND s.status = 1
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
                    AND sf.rating IS NOT NULL
                    AND s.status = 1
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
                    AND u.status = 1
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
                AND sf.rating IS NOT NULL
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
                AND sf.rating IS NOT NULL
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
    public function getClassFeedbacks($class_id, $tutor_id = null) {
        try {
            
            if(empty($tutor_id)) {
                // Get Class details
                $stmt = $this->db->prepare("
                    SELECT class_id 
                    FROM class 
                    WHERE class_id = ?
                ");
                
                $stmt->bind_param("i", $class_id);
                $stmt->execute();
            }
            else {
                // Verify the tutor owns this class
                $stmt = $this->db->prepare("
                    SELECT class_id 
                    FROM class 
                    WHERE class_id = ? AND tutor_id = ?
                ");
                
                $stmt->bind_param("ii", $class_id, $tutor_id);
                $stmt->execute();   
            }
            
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
                AND sf.rating IS NOT NULL
                AND u.status = 1
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
    /**
     * Get all archived feedback for a specific class (for TechGuru view)
     * 
     * @param int $class_id The class ID
     * @param int $tutor_id The tutor ID for access verification
     * @return array List of feedback entries
     */
    public function getArchivedClassFeedbacks($class_id, $tutor_id = null) {
        try {
            
            if(empty($tutor_id)) {
                // Get Class details
                $stmt = $this->db->prepare("
                    SELECT class_id 
                    FROM class 
                    WHERE class_id = ?
                ");
                
                $stmt->bind_param("i", $class_id);
                $stmt->execute();
            }
            else {
                // Verify the tutor owns this class
                $stmt = $this->db->prepare("
                    SELECT class_id 
                    FROM class 
                    WHERE class_id = ? AND tutor_id = ?
                ");
                
                $stmt->bind_param("ii", $class_id, $tutor_id);
                $stmt->execute();   
            }
            
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
                WHERE cs.class_id = ? AND sf.tutor_id = ? AND sf.is_archived = 1
                AND sf.rating IS NULL
                AND u.status = 1
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

    /**
     * Get recent teaching activities for a tutor
     * 
     * @param int $tutor_id The ID of the tutor
     * @param int $limit Number of activities to retrieve (default: 10)
     * @return array Array of recent activities with details
     */
    public function getTutorRecentActivities($tutor_id, $limit = 10) {
        global $conn;
        
        try {
            $query = "
                (SELECT 
                    'session' as activity_type,
                    'clock-fill' as icon,
                    'primary' as type_color,
                    cs.schedule_id as activity_id,
                    CONCAT('Class session: ', c.class_name) as title,
                    CONCAT('You completed a session with ', COUNT(DISTINCT a.student_id), ' students') as description,
                    cs.session_date as activity_date,
                    CONCAT(
                        DATE_FORMAT(cs.start_time, '%h:%i %p'), 
                        ' - ', 
                        DATE_FORMAT(cs.end_time, '%h:%i %p')
                    ) as activity_time,
                    cs.status_changed_at as timestamp
                FROM class_schedule cs
                JOIN class c ON cs.class_id = c.class_id
                LEFT JOIN attendance a ON cs.schedule_id = a.schedule_id AND a.status = 'present'
                WHERE c.tutor_id = ? AND cs.status = 'completed'
                GROUP BY cs.schedule_id
                ORDER BY cs.session_date DESC, cs.start_time DESC
                LIMIT 10)
                
                UNION
                
                (SELECT 
                    'feedback' as activity_type,
                    'star-fill' as icon,
                    'warning' as type_color,
                    sf.rating_id as activity_id,
                    CONCAT('New feedback: ', CASE WHEN sf.rating >= 4 THEN 'Excellent' 
                                             WHEN sf.rating = 3 THEN 'Good' 
                                             ELSE 'Needs improvement' END) as title,
                    CONCAT('You received a ', sf.rating, '-star rating from a student') as description,
                    NULL as activity_date,
                    NULL as activity_time,
                    sf.created_at as timestamp
                FROM session_feedback sf
                WHERE sf.tutor_id = ?
                ORDER BY sf.created_at DESC
                LIMIT 10)
                
                UNION
                
                (SELECT 
                    'class' as activity_type,
                    'mortarboard-fill' as icon,
                    'success' as type_color,
                    c.class_id as activity_id,
                    CONCAT('Class updated: ', c.class_name) as title,
                    CASE 
                        WHEN c.status = 'active' THEN 'Class is now active and open for enrollment'
                        WHEN c.status = 'completed' THEN 'Class has been marked as completed'
                        ELSE 'Class details were updated'
                    END as description,
                    NULL as activity_date,
                    NULL as activity_time,
                    c.updated_at as timestamp
                FROM class c
                WHERE c.tutor_id = ? AND c.updated_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY c.updated_at DESC
                LIMIT 10)
                
                ORDER BY timestamp DESC
                LIMIT ?";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iiii", $tutor_id, $tutor_id, $tutor_id, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            $activities = $result->fetch_all(MYSQLI_ASSOC);
            
            // Process the results
            foreach ($activities as &$activity) {
                // Format the timestamp for display
                $timestamp = new DateTime($activity['timestamp']);
                $now = new DateTime();
                $diff = $now->diff($timestamp);
                
                if ($diff->days == 0) {
                    if ($diff->h == 0) {
                        if ($diff->i == 0) {
                            $activity['timestamp'] = 'Just now';
                        } else {
                            $activity['timestamp'] = $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
                        }
                    } else {
                        $activity['timestamp'] = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
                    }
                } else if ($diff->days == 1) {
                    $activity['timestamp'] = 'Yesterday';
                } else if ($diff->days < 7) {
                    $activity['timestamp'] = $diff->days . ' days ago';
                } else {
                    $activity['timestamp'] = $timestamp->format('M d, Y');
                }
            }
            
            return $activities;
        } catch (Exception $e) {
            log_error("Error getting tutor recent activities: " . $e->getMessage(), 'database');
            return [];
        }
    }
}

/**
 * Get recent teaching activities for a tutor (non-class wrapper function)
 * 
 * @param int $tutor_id The ID of the tutor
 * @param int $limit Number of activities to retrieve (default: 10)
 * @return array Array of recent activities with details
 */
function getTutorRecentActivities($tutor_id, $limit = 10) {
    $ratingManager = new RatingManagement();
    return $ratingManager->getTutorRecentActivities($tutor_id, $limit);
}

/**
 * Get rating statistics for a tutor (non-class wrapper function)
 * 
 * @param int $tutor_id The ID of the tutor
 * @return array Rating statistics in the format needed for reports.php
 */
function getTutorRatingStats($tutor_id) {
    $ratingManager = new RatingManagement();
    $stats = $ratingManager->getTutorRatingStats($tutor_id);
    
    // Add distribution array for chart
    $distribution = [
        $stats['five_star'],
        $stats['four_star'],
        $stats['three_star'],
        $stats['two_star'],
        $stats['one_star']
    ];
    
    return array_merge($stats, ['distribution' => $distribution]);
}
