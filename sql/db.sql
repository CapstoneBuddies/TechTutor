-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `uid` INT PRIMARY KEY AUTO_INCREMENT,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(255) NOT NULL,
    `last_name` VARCHAR(255) NOT NULL,
    `gender` ENUM('M','F', 'U') DEFAULT 'U',
    `profile_picture` VARCHAR(255) DEFAULT 'default.jpg',
    `address` TEXT NULL,
    `contact_number` VARCHAR(16) NULL,
    `rating` DECIMAL(3,2) DEFAULT 0.00,
    `rating_count` INT DEFAULT 0,
    `is_verified` BOOLEAN NOT NULL DEFAULT FALSE,
    `status` BOOLEAN NOT NULL DEFAULT TRUE,
    `role` ENUM('TECHGURU', 'TECHKID', 'ADMIN') NOT NULL DEFAULT 'TECHKID',
    `created_on` TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
    `last_login` TIMESTAMP NULL,
    INDEX `idx_user_status` (`status`),
    INDEX `idx_email` (`email`),
    INDEX `idx_user_role` (`role`)
);

-- Login Tokens Table
CREATE TABLE IF NOT EXISTS `login_tokens` (
    `token_id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `verification_code` VARCHAR(6) NULL,
    `type` ENUM('remember_me', 'email_verification','reset') NOT NULL,
    `expiration_date` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    UNIQUE (`user_id`,`token`),
    INDEX `idx_token` (`token`)
);

-- Course Table
CREATE TABLE IF NOT EXISTS `course` (
    `course_id` INT PRIMARY KEY AUTO_INCREMENT,
    `course_name` VARCHAR(255) NOT NULL,
    `course_desc` TEXT
);

-- Subject Table
CREATE TABLE IF NOT EXISTS `subject` (
    `subject_id` INT PRIMARY KEY AUTO_INCREMENT,
    `course_id` INT,
    `subject_name` VARCHAR(255) NOT NULL,
    `subject_desc` TEXT,
    `image` VARCHAR(255) DEFAULT 'default.jpg',
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (`course_id`) REFERENCES `course`(`course_id`)
);

-- Class Table
CREATE TABLE IF NOT EXISTS `class` (
    `class_id` INT PRIMARY KEY AUTO_INCREMENT,
    `subject_id` INT,
    `class_name` VARCHAR(255) NOT NULL,
    `class_desc` TEXT NOT NULL,
    `tutor_id` INT NOT NULL,
    `start_date` DATETIME NOT NULL,
    `end_date` DATETIME NOT NULL,
    `class_size` INT NULL,
    `status` ENUM('active', 'restricted', 'completed', 'pending') NOT NULL DEFAULT 'active',
    `is_free` BOOLEAN NOT NULL DEFAULT TRUE,
    `price` FLOAT(10,2) NULL,
    `thumbnail` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`subject_id`) REFERENCES `subject`(`subject_id`),
    FOREIGN KEY (`tutor_id`) REFERENCES `users`(`uid`),
    INDEX `idx_class_name` (`class_name`),
    INDEX `idx_class_status` (`status`),
    INDEX `idx_class_tutor` (`tutor_id`),
    INDEX `idx_class_subject` (`subject_id`)
);

-- Class Schedule Table
CREATE TABLE IF NOT EXISTS `class_schedule` (
    `schedule_id` INT PRIMARY KEY AUTO_INCREMENT,
    `class_id` INT NOT NULL,
    `session_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `status` ENUM('pending', 'confirmed', 'completed', 'canceled') DEFAULT 'pending',
    FOREIGN KEY (`class_id`) REFERENCES `class`(`class_id`),
    INDEX `idx_schedule_class` (`class_id`),
    INDEX `idx_schedule_date` (`session_date`),
    INDEX `idx_schedule_status` (`status`)
);

-- Session Feedback Table
CREATE TABLE IF NOT EXISTS `session_feedback` (
    `rating_id` INT PRIMARY KEY AUTO_INCREMENT,
    `session_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `tutor_id` INT NOT NULL,
    `rating` INT CHECK (`rating` BETWEEN 1 AND 5),
    `feedback` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `is_archived` BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (`session_id`) REFERENCES `class_schedule`(`schedule_id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    FOREIGN KEY (`tutor_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    UNIQUE KEY `unique_session_feedback` (`session_id`, `student_id`),
    INDEX `idx_tutor_ratings` (`tutor_id`, `rating`),
    INDEX `idx_session_feedback` (`session_id`),
    INDEX `idx_student_feedback` (`student_id`),
    INDEX `idx_session_feedback_archived` (`is_archived`)
);

-- Enrollments Table
CREATE TABLE IF NOT EXISTS `enrollments` (
    `enrollment_id` INT PRIMARY KEY AUTO_INCREMENT,
    `class_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `enrollment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('active','completed','dropped', 'pending') NOT NULL DEFAULT 'active',
    FOREIGN KEY (`class_id`) REFERENCES `class`(`class_id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    UNIQUE KEY `unique_enrollment` (`class_id`, `student_id`),
    INDEX `idx_enrollment_class` (`class_id`),
    INDEX `idx_enrollment_student` (`student_id`),
    INDEX `idx_enrollment_status` (`status`)
);

-- Meetings Table
CREATE TABLE IF NOT EXISTS `meetings` (
    `meeting_id` INT PRIMARY KEY AUTO_INCREMENT,
    `meeting_uid` VARCHAR(50) UNIQUE NOT NULL,
    `schedule_id` INT NOT NULL,
    `meeting_name` VARCHAR(255) NOT NULL,
    `attendee_pw` VARCHAR(255) NULL,
    `moderator_pw` VARCHAR(255) NULL,
    `is_running` BOOLEAN NOT NULL DEFAULT TRUE,
    `createtime` BIGINT NOT NULL, 
    `end_time` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT NOW(),
    FOREIGN KEY (`schedule_id`) REFERENCES `class_schedule`(`schedule_id`),
    INDEX `idx_meeting_uid` (`meeting_uid`),
    INDEX `idx_meeting_schedule` (`schedule_id`)
);

-- File Categories Table
CREATE TABLE IF NOT EXISTS `file_categories` (
    `category_id` INT PRIMARY KEY AUTO_INCREMENT,
    `category_name` VARCHAR(50) NOT NULL,
    `description` TEXT,
    UNIQUE KEY `unique_category_name` (`category_name`)
);

-- File Folders Table
CREATE TABLE IF NOT EXISTS `file_folders` (
    `folder_id` INT PRIMARY KEY AUTO_INCREMENT,
    `class_id` INT NULL,
    `user_id` INT NOT NULL,
    `folder_name` VARCHAR(255) NOT NULL,
    `parent_folder_id` INT NULL,
    `google_folder_id` VARCHAR(255) NOT NULL,
    `visibility` ENUM('private', 'public', 'class_only', 'specific_users') DEFAULT 'private',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`class_id`) REFERENCES `class`(`class_id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    FOREIGN KEY (`parent_folder_id`) REFERENCES `file_folders`(`folder_id`) ON DELETE CASCADE,
    INDEX `idx_file_folders_class` (`class_id`),
    INDEX `idx_file_folders_user` (`user_id`),
    INDEX `idx_file_folders_parent` (`parent_folder_id`),
    INDEX `idx_file_folders_visibility` (`visibility`)
);

-- Unified Files Table
CREATE TABLE IF NOT EXISTS `unified_files` (
    `file_id` INT PRIMARY KEY AUTO_INCREMENT,
    `file_uuid` VARCHAR(255) NOT NULL UNIQUE,
    `class_id` INT NULL,
    `user_id` INT NOT NULL,
    `folder_id` INT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(50) NOT NULL,
    `file_size` BIGINT NOT NULL,
    `google_file_id` VARCHAR(255) NOT NULL,
    `drive_link` TEXT NOT NULL,
    `description` TEXT,
    `visibility` ENUM('private', 'public', 'class_only', 'specific_users') DEFAULT 'private',
    `file_purpose` ENUM('personal', 'class_material', 'assignment', 'submission') DEFAULT 'personal',
    `category_id` INT NULL,
    `upload_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_modified` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`class_id`) REFERENCES `class`(`class_id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `file_categories`(`category_id`) ON DELETE SET NULL,
    FOREIGN KEY (`folder_id`) REFERENCES `file_folders`(`folder_id`) ON DELETE SET NULL,
    INDEX `idx_unified_files_class` (`class_id`),
    INDEX `idx_unified_files_user` (`user_id`),
    INDEX `idx_unified_files_folder` (`folder_id`),
    INDEX `idx_unified_files_visibility` (`visibility`),
    INDEX `idx_unified_files_purpose` (`file_purpose`),
    INDEX `idx_unified_files_category` (`category_id`)
);

-- Legacy File Management Table
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
    FOREIGN KEY (`class_id`) REFERENCES `class`(`class_id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    INDEX `idx_file_class` (`class_id`),
    INDEX `idx_file_user` (`user_id`),
    INDEX `idx_file_visibility` (`is_visible`),
    INDEX `idx_file_personal` (`is_personal`),
    INDEX `idx_file_uuid` (`google_file_id`)
);

-- File Permissions Table
CREATE TABLE IF NOT EXISTS `file_permissions` (
    `permission_id` INT PRIMARY KEY AUTO_INCREMENT,
    `file_id` INT NULL,
    `folder_id` INT NULL,
    `user_id` INT NOT NULL,
    `access_type` ENUM('view', 'edit', 'owner') DEFAULT 'view',
    `granted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `granted_by` INT NOT NULL,
    FOREIGN KEY (`file_id`) REFERENCES `unified_files`(`file_id`) ON DELETE CASCADE,
    FOREIGN KEY (`folder_id`) REFERENCES `file_folders`(`folder_id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    FOREIGN KEY (`granted_by`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    CHECK (`file_id` IS NOT NULL OR `folder_id` IS NOT NULL),
    INDEX `idx_file_permissions_file` (`file_id`),
    INDEX `idx_file_permissions_folder` (`folder_id`),
    INDEX `idx_file_permissions_user` (`user_id`)
);

-- File Requests Table
CREATE TABLE IF NOT EXISTS `file_requests` (
    `request_id` INT PRIMARY KEY AUTO_INCREMENT,
    `class_id` INT NOT NULL,
    `requester_id` INT NOT NULL,
    `recipient_id` INT NOT NULL,
    `request_title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `due_date` DATETIME NOT NULL,
    `status` ENUM('pending', 'submitted', 'rejected', 'approved', 'expired') DEFAULT 'pending',
    `response_file_id` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`class_id`) REFERENCES `class`(`class_id`) ON DELETE CASCADE,
    FOREIGN KEY (`requester_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    FOREIGN KEY (`recipient_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    FOREIGN KEY (`response_file_id`) REFERENCES `unified_files`(`file_id`) ON DELETE SET NULL,
    INDEX `idx_file_requests_class` (`class_id`),
    INDEX `idx_file_requests_requester` (`requester_id`),
    INDEX `idx_file_requests_recipient` (`recipient_id`),
    INDEX `idx_file_requests_status` (`status`)
);

-- File Upload Requests Table
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
    FOREIGN KEY (`class_id`) REFERENCES `class`(`class_id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    FOREIGN KEY (`tutor_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    FOREIGN KEY (`file_id`) REFERENCES `file_management`(`file_id`) ON DELETE SET NULL,
    INDEX `idx_request_student` (`student_id`),
    INDEX `idx_request_class` (`class_id`),
    INDEX `idx_request_status` (`status`)
);

-- File Access Table
CREATE TABLE IF NOT EXISTS `file_access` (
    `access_id` INT PRIMARY KEY AUTO_INCREMENT,
    `file_id` INT NOT NULL,
    `enrollment_id` INT NOT NULL,
    `granted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `granted_by` INT NOT NULL,
    FOREIGN KEY (`file_id`) REFERENCES `file_management`(`file_id`) ON DELETE CASCADE,
    FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments`(`enrollment_id`) ON DELETE CASCADE,
    FOREIGN KEY (`granted_by`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    UNIQUE KEY `unique_file_access` (`file_id`, `enrollment_id`)
);

-- File Tags Table
CREATE TABLE IF NOT EXISTS `file_tags` (
    `tag_id` INT NOT NULL AUTO_INCREMENT,
    `tag_name` VARCHAR(50) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`tag_id`),
    UNIQUE KEY `tag_name_unique` (`tag_name`)
);

-- File Tag Mapping Table
CREATE TABLE IF NOT EXISTS `file_tag_map` (
    `map_id` INT NOT NULL AUTO_INCREMENT,
    `file_id` INT NOT NULL,
    `tag_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`map_id`),
    UNIQUE KEY `file_tag_unique` (`file_id`, `tag_id`),
    FOREIGN KEY (`file_id`) REFERENCES `unified_files` (`file_id`) ON DELETE CASCADE,
    FOREIGN KEY (`tag_id`) REFERENCES `file_tags` (`tag_id`) ON DELETE CASCADE
);

-- File Category Mapping Table
CREATE TABLE IF NOT EXISTS `file_category_mapping` (
    `file_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    PRIMARY KEY (`file_id`, `category_id`),
    FOREIGN KEY (`file_id`) REFERENCES `file_management`(`file_id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `file_categories`(`category_id`) ON DELETE CASCADE
);

-- Certificate Table
CREATE TABLE IF NOT EXISTS `certificate` (
    `cert_id` INT PRIMARY KEY AUTO_INCREMENT,
    `cert_uuid` VARCHAR(255) UNIQUE NOT NULL,
    `recipient` INT NOT NULL,
    `award` VARCHAR(255) NOT NULL,
    `donor` INT NOT NULL,
    `issue_date` DATE NOT NULL,
    FOREIGN KEY (`recipient`) REFERENCES `users`(`uid`),
    FOREIGN KEY (`donor`) REFERENCES `users`(`uid`)
);

-- Notifications Table
CREATE TABLE IF NOT EXISTS `notifications` (
    `notification_id` INT PRIMARY KEY AUTO_INCREMENT,
    `recipient_id` INT,
    `recipient_role` ENUM('ADMIN', 'TECHGURU', 'TECHKID', 'ALL') NOT NULL,
    `class_id` INT,
    `message` TEXT NOT NULL,
    `link` VARCHAR(255),
    `icon` VARCHAR(50) NOT NULL,
    `icon_color` VARCHAR(50) NOT NULL,
    `is_read` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`recipient_id`) REFERENCES `users`(`uid`),
    FOREIGN KEY (`class_id`) REFERENCES `class`(`class_id`),
    INDEX `idx_notification_recipient` (`recipient_id`),
    INDEX `idx_notification_role` (`recipient_role`),
    INDEX `idx_notification_read` (`is_read`)
);

-- Transactions Table
CREATE TABLE IF NOT EXISTS `transactions` (
    `transaction_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `payment_intent_id` VARCHAR(255) NOT NULL,
    `payment_method_id` VARCHAR(255),
    `amount` DECIMAL(10,2) NOT NULL,
    `currency` VARCHAR(3) DEFAULT 'PHP',
    `status` ENUM('pending', 'processing', 'succeeded', 'failed') DEFAULT 'pending',
    `payment_method_type` VARCHAR(50), -- card, gcash, grab_pay, paymaya
    `description` TEXT,
    `metadata` JSON,
    `error_message` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    INDEX `idx_payment_intent` (`payment_intent_id`),
    INDEX `idx_transaction_user` (`user_id`),
    INDEX `idx_transaction_status` (`status`),
    INDEX `idx_transaction_created_at` (`created_at`)
);

-- Class Ratings Table
CREATE TABLE IF NOT EXISTS `class_ratings` (
    `classRating_id` INT PRIMARY KEY AUTO_INCREMENT,
    `class_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `rating` INT CHECK (`rating` BETWEEN 1 AND 5),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`class_id`) REFERENCES `class`(`class_id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `enrollments`(`student_id`) ON DELETE CASCADE,
    INDEX `idx_class_rating_class` (`class_id`),
    INDEX `idx_class_rating_student` (`student_id`)
);

-- Attendance Table
CREATE TABLE IF NOT EXISTS `attendance` (
    `attendance_id` INT PRIMARY KEY AUTO_INCREMENT,
    `schedule_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `status` ENUM('present', 'absent', 'late') NOT NULL DEFAULT 'present',
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`schedule_id`) REFERENCES `class_schedule`(`schedule_id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `enrollments`(`student_id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_attendance` (`schedule_id`, `student_id`),
    INDEX `idx_attendance_schedule` (`schedule_id`),
    INDEX `idx_attendance_student` (`student_id`),
    INDEX `idx_attendance_status` (`status`)
);

-- Meeting Analytics Table
CREATE TABLE IF NOT EXISTS `meeting_analytics` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `meeting_id` INT NOT NULL,
    `tutor_id` INT NOT NULL,
    `participant_count` INT DEFAULT 0,
    `duration` INT DEFAULT 0,
    `start_time` DATETIME,
    `end_time` DATETIME,
    `recording_available` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`meeting_id`) REFERENCES `meetings`(`meeting_id`),
    FOREIGN KEY (`tutor_id`) REFERENCES `users`(`uid`),
    INDEX `idx_meeting_analytics_meeting_id` (`meeting_id`),
    INDEX `idx_meeting_analytics_tutor_id` (`tutor_id`),
    INDEX `idx_meeting_analytics_start_time` (`start_time`)
);

-- Create trigger to update user's rating and rating_count
DELIMITER //
CREATE TRIGGER IF NOT EXISTS after_session_feedback_insert
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
CREATE TRIGGER IF NOT EXISTS after_session_feedback_delete
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

-- Sample Data
INSERT INTO `users`(`email`,`password`,`role`,`is_verified`, `first_name`, `last_name`) VALUES 
('tutor@test.com','$2y$10$FwM//r8Nn2GUWpHSBMv0RuYxw7oBScsxjf.cYlnUuq1V2KcQkyM3.','TECHGURU',1, 'Test', 'Tutor'),
('student@test.com','$2y$10$FwM//r8Nn2GUWpHSBMv0RuYxw7oBScsxjf.cYlnUuq1V2KcQkyM3.','TECHKID',1, 'Test', 'Student'),
('admin@test.com','$2y$10$FwM//r8Nn2GUWpHSBMv0RuYxw7oBScsxjf.cYlnUuq1V2KcQkyM3.','ADMIN',1, 'Test', 'Admin');

INSERT INTO `course`(`course_name`,`course_desc`) VALUES
('Computer Programming','Learn how to write, debug, and develop software applications using various programming languages and methodologies.'), 
('Computer Networking','Understand the principles of networking, including setup, security, and cloud integration to build robust IT infrastructures.'), 
('Graphics Design','Master digital design, UI/UX, and animation techniques to create stunning visuals and interactive experiences.');

INSERT INTO `subject`(`course_id`,`subject_name`,`subject_desc`) VALUES 
(1,'Python Programming', 'Learn the fundamentals of Python, including syntax, data structures, and object-oriented programming.'), 
(1,'Java Development', 'Covers Java programming basics, OOP principles, and application development.'), 
(1,'C++ for Beginners', 'Introduces students to C++ programming, focusing on memory management, data structures, and algorithms.'), 
(2,'Network Fundamentals', 'Explores basic networking concepts, protocols, and network topologies.'), 
(2,'Routing and Switching', 'Focuses on configuring routers and switches, subnetting, and VLANs.'), 
(2,'Network Security', 'Covers firewalls, intrusion detection systems, and encryption techniques for secure networking.'), 
(3,'UI/UX Designing', 'Teaches principles of user interface and user experience design for web and mobile applications.'),
(3,'Adobe Photoshop Essentials', 'Covers photo editing, digital painting, and design techniques using Adobe Photoshop.'), 
(3,'Vector Illustration with Adobe Illustrator', 'Focuses on creating digital illustrations, logos, and typography with Illustrator.');

-- Insert default file categories
INSERT INTO `file_categories` (`category_name`, `description`) VALUES
('Lecture Notes', 'Course lecture materials and presentations'),
('Assignments', 'Homework and practice exercises'),
('Resources', 'Additional learning materials and references'),
('Solutions', 'Answer keys and solution guides'),
('Supplementary', 'Extra materials for advanced learning'); 