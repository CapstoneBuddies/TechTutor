-- Check first if it exists
ALTER TABLE class_schedule DROP COLUMN role;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS `file_upload_requests`;
DROP TABLE IF EXISTS `file_management`;

-- Create enhanced file_management table
CREATE TABLE IF NOT EXISTS `file_management` (
    `file_id` INT PRIMARY KEY AUTO_INCREMENT,
    `class_id` INT NULL,
    `user_id` INT NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(50) NOT NULL,
    `file_size` BIGINT NOT NULL,
    `google_file_id` VARCHAR(255) NOT NULL,
    `drive_link` TEXT NOT NULL,
    `folder_id` VARCHAR(255) NULL,
    `description` TEXT,
    `is_personal` BOOLEAN DEFAULT FALSE,
    `is_visible` BOOLEAN DEFAULT FALSE,
    `upload_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_modified` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES class(class_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(uid) ON DELETE CASCADE
);

-- Create table for file upload requests from tutors
CREATE TABLE IF NOT EXISTS `file_upload_requests` (
    `request_id` INT PRIMARY KEY AUTO_INCREMENT,
    `class_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `tutor_id` INT NOT NULL,
    `request_title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `due_date` DATETIME NOT NULL,
    `status` ENUM('pending', 'completed', 'expired') DEFAULT 'pending',
    `file_id` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES class(class_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(uid) ON DELETE CASCADE,
    FOREIGN KEY (tutor_id) REFERENCES users(uid) ON DELETE CASCADE,
    FOREIGN KEY (file_id) REFERENCES file_management(file_id) ON DELETE SET NULL
);

-- Create table for file access permissions
CREATE TABLE IF NOT EXISTS `file_access` (
    `access_id` INT PRIMARY KEY AUTO_INCREMENT,
    `file_id` INT NOT NULL,
    `enrollment_id` INT NOT NULL,
    `granted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `granted_by` INT NOT NULL,
    FOREIGN KEY (file_id) REFERENCES file_management(file_id) ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(enrollment_id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(uid) ON DELETE CASCADE,
    UNIQUE KEY `unique_file_access` (`file_id`, `enrollment_id`)
);

-- Create table for file categories
CREATE TABLE IF NOT EXISTS `file_categories` (
    `category_id` INT PRIMARY KEY AUTO_INCREMENT,
    `category_name` VARCHAR(50) NOT NULL,
    `description` TEXT,
    UNIQUE KEY `unique_category_name` (`category_name`)
);

-- Create table for file-category relationships
CREATE TABLE IF NOT EXISTS `file_category_mapping` (
    `file_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    PRIMARY KEY (`file_id`, `category_id`),
    FOREIGN KEY (file_id) REFERENCES file_management(file_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES file_categories(category_id) ON DELETE CASCADE
);

-- Insert default file categories
INSERT INTO `file_categories` (`category_name`, `description`) VALUES
('Lecture Notes', 'Course lecture materials and presentations'),
('Assignments', 'Homework and practice exercises'),
('Resources', 'Additional learning materials and references'),
('Solutions', 'Answer keys and solution guides'),
('Supplementary', 'Extra materials for advanced learning');

-- Add indexes for optimization
CREATE INDEX idx_file_class ON file_management(class_id);
CREATE INDEX idx_file_user ON file_management(user_id);
CREATE INDEX idx_file_visibility ON file_management(is_visible);
CREATE INDEX idx_file_personal ON file_management(is_personal);
CREATE INDEX idx_request_student ON file_upload_requests(student_id);
CREATE INDEX idx_request_class ON file_upload_requests(class_id);
CREATE INDEX idx_request_status ON file_upload_requests(status);

-- Drop existing ratings table as we're replacing it with a more specific one
DROP TABLE IF EXISTS `ratings`;

-- Create session feedback table
CREATE TABLE IF NOT EXISTS `session_feedback` (
    `rating_id` INT PRIMARY KEY AUTO_INCREMENT,
    `session_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `tutor_id` INT NOT NULL,
    `rating` INT CHECK (`rating` BETWEEN 1 AND 5),
    `feedback` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`session_id`) REFERENCES `class_schedule`(`schedule_id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    FOREIGN KEY (`tutor_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    UNIQUE KEY `unique_session_feedback` (`session_id`, `student_id`),
    INDEX `idx_tutor_ratings` (`tutor_id`, `rating`),
    INDEX `idx_session_feedback` (`session_id`),
    INDEX `idx_student_feedback` (`student_id`)
);

-- Add rating_count column to users table to optimize rating calculations
ALTER TABLE `users`
ADD COLUMN `rating_count` INT DEFAULT 0 AFTER `rating`,
MODIFY COLUMN `rating` DECIMAL(3,2) DEFAULT 0.00;

-- Create trigger to update user's rating and rating_count
DELIMITER //
CREATE TRIGGER after_session_feedback_insert
AFTER INSERT ON session_feedback
FOR EACH ROW
BEGIN
    -- Update tutor's rating count and average rating
    UPDATE users u
    SET u.rating_count = (
            SELECT COUNT(*) 
            FROM session_feedback 
            WHERE tutor_id = NEW.tutor_id
        ),
        u.rating = (
            SELECT AVG(rating) 
            FROM session_feedback 
            WHERE tutor_id = NEW.tutor_id
        )
    WHERE u.uid = NEW.tutor_id;
END //
DELIMITER ;

-- Create trigger to update user's rating after feedback deletion
DELIMITER //
CREATE TRIGGER after_session_feedback_delete
AFTER DELETE ON session_feedback
FOR EACH ROW
BEGIN
    -- Update tutor's rating count and average rating
    UPDATE users u
    SET u.rating_count = (
            SELECT COUNT(*) 
            FROM session_feedback 
            WHERE tutor_id = OLD.tutor_id
        ),
        u.rating = COALESCE(
            (SELECT AVG(rating) 
             FROM session_feedback 
             WHERE tutor_id = OLD.tutor_id),
            0
        )
    WHERE u.uid = OLD.tutor_id;
END //
DELIMITER ;

-- Create attendance table
CREATE TABLE attendance (
    attendance_id INT PRIMARY KEY AUTO_INCREMENT,
    schedule_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('present', 'absent', 'late') NOT NULL DEFAULT 'present',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES class_schedule(schedule_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES enrollments(student_id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (schedule_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better performance
CREATE INDEX idx_attendance_schedule ON attendance(schedule_id);
CREATE INDEX idx_attendance_student ON attendance(student_id);
CREATE INDEX idx_attendance_status ON attendance(status);

-- Add comment to explain the table
ALTER TABLE attendance COMMENT = 'Tracks student attendance for each class session';
--------------------------------------------------------------------------------------