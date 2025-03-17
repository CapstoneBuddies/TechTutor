-- TechTutor Database Schema
-- Comprehensive schema with all improvements for better performance and data integrity

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+08:00";

-- --------------------------------------------------------
--
-- Core User Management Tables
--
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
    `uid` INT PRIMARY KEY AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `role` ENUM('ADMIN', 'TECHGURU', 'TECHKID') NOT NULL,
    `status` ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'pending',
    `profile_picture` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_tokens` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `type` ENUM('remember_me', 'verification', 'reset_password') NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    INDEX `idx_token` (`token`),
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
    `notification_id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('system', 'class', 'file_share', 'recording', 'certificate') NOT NULL DEFAULT 'system',
    `status` ENUM('unread', 'read') NOT NULL DEFAULT 'unread',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_user_notifications` (`user_id`, `status`),
    INDEX `idx_notification_type` (`type`),
    INDEX `idx_notification_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `code` VARCHAR(255) NOT NULL,
    `type` ENUM('code', 'token') DEFAULT 'code',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    INDEX `idx_user_reset` (`user_id`),
    INDEX `idx_reset_expiry` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `error_logs` (
    `log_id` INT PRIMARY KEY AUTO_INCREMENT,
    `error_type` VARCHAR(50) NOT NULL DEFAULT 'general',
    `error_message` TEXT NOT NULL,
    `error_details` TEXT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `line_number` INT NOT NULL,
    `user_id` INT NULL,
    `session_id` VARCHAR(255) NULL,
    `request_url` TEXT NULL,
    `request_method` VARCHAR(10) NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`uid`) ON DELETE SET NULL,
    INDEX `idx_error_type` (`error_type`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_session` (`session_id`),
    INDEX `idx_user_errors` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Course Management Tables
--
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `course` (
    `course_id` INT PRIMARY KEY AUTO_INCREMENT,
    `course_name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `thumbnail` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_course_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `subject` (
    `subject_id` INT PRIMARY KEY AUTO_INCREMENT,
    `course_id` INT NOT NULL,
    `subject_name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`course_id`) REFERENCES `course`(`course_id`) ON DELETE CASCADE,
    INDEX `idx_subject_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `class` (
    `class_id` INT PRIMARY KEY AUTO_INCREMENT,
    `subject_id` INT NOT NULL,
    `tutor_id` INT NOT NULL,
    `class_name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `thumbnail` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('active', 'inactive', 'completed') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`subject_id`) REFERENCES `subject`(`subject_id`) ON DELETE CASCADE,
    FOREIGN KEY (`tutor_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    INDEX `idx_class_status` (`status`),
    INDEX `idx_class_name` (`class_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `class_schedule` (
    `schedule_id` INT PRIMARY KEY AUTO_INCREMENT,
    `class_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `role` ENUM('STUDENT', 'TUTOR') NOT NULL,
    `session_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `status` ENUM('pending', 'ongoing', 'completed', 'canceled') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`class_id`) REFERENCES `class`(`class_id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    INDEX `idx_session_date` (`session_date`),
    INDEX `idx_enrollment_status` (`status`),
    INDEX `idx_user_role` (`user_id`, `role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- File and Meeting Management Tables
--
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `file_management` (
    `file_id` INT PRIMARY KEY AUTO_INCREMENT,
    `class_id` INT NOT NULL,
    `uploaded_by` INT NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `thumbnail_path` VARCHAR(255) NULL,
    `file_type` ENUM('document', 'video', 'pdf', 'image', 'assignment', 'resource') NOT NULL DEFAULT 'document',
    `file_size` BIGINT NOT NULL DEFAULT 0,
    `mobile_optimized` TINYINT(1) DEFAULT 0,
    `drive_file_id` VARCHAR(255) NULL,
    `drive_view_link` TEXT NULL,
    `status` ENUM('active', 'deleted') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`class_id`) REFERENCES `class`(`class_id`) ON DELETE CASCADE,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    INDEX `idx_file_status` (`status`),
    INDEX `idx_drive_file_id` (`drive_file_id`),
    INDEX `idx_file_type_status` (`file_type`, `status`),
    INDEX `idx_uploaded_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `file_categories` (
    `category_id` INT PRIMARY KEY AUTO_INCREMENT,
    `category_name` VARCHAR(32) NOT NULL,
    `extensions` VARCHAR(255) NOT NULL,
    `icon_class` VARCHAR(32) NOT NULL,
    `color_class` VARCHAR(32) NOT NULL,
    UNIQUE KEY `unique_category` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `file_shares` (
    `share_id` INT PRIMARY KEY AUTO_INCREMENT,
    `file_id` INT NOT NULL,
    `shared_by` INT NOT NULL,
    `shared_with` INT NOT NULL,
    `share_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `message` TEXT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    FOREIGN KEY (`file_id`) REFERENCES `file_management`(`file_id`) ON DELETE CASCADE,
    FOREIGN KEY (`shared_by`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    FOREIGN KEY (`shared_with`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    INDEX `idx_file_share` (`file_id`, `shared_with`),
    INDEX `idx_user_shares` (`shared_with`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `storage_tiers` (
    `tier_id` INT PRIMARY KEY AUTO_INCREMENT,
    `tier_name` VARCHAR(32) NOT NULL,
    `storage_limit` BIGINT NOT NULL,
    `price_monthly` DECIMAL(10,2) NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    UNIQUE KEY `unique_tier` (`tier_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `storage_quotas` (
    `quota_id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `total_size` BIGINT NOT NULL DEFAULT 0,
    `max_size` BIGINT NOT NULL DEFAULT 1073741824, -- 1GB default
    `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    INDEX `idx_user_quota` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `meetings` (
    `meeting_id` INT PRIMARY KEY AUTO_INCREMENT,
    `meeting_uid` VARCHAR(255) NOT NULL UNIQUE,
    `schedule_id` INT NULL,
    `meeting_url` VARCHAR(255) NOT NULL,
    `meeting_code` VARCHAR(20) NOT NULL,
    `attendee_pw` VARCHAR(64) NOT NULL,
    `moderator_pw` VARCHAR(64) NOT NULL,
    `join_url` TEXT NULL,
    `start_time` TIMESTAMP NULL,
    `end_time` TIMESTAMP NULL,
    `recording_url` TEXT NULL,
    `recording_enabled` TINYINT(1) DEFAULT 0,
    `auto_start_recording` TINYINT(1) DEFAULT 0,
    `allow_start_stop_recording` TINYINT(1) DEFAULT 1,
    `participant_count` INT DEFAULT 0,
    `moderator_count` INT DEFAULT 0,
    `attendee_count` INT DEFAULT 0,
    `max_users` INT DEFAULT 0,
    `has_user_joined` TINYINT(1) DEFAULT 0,
    `has_been_forcibly_ended` TINYINT(1) DEFAULT 0,
    `status` ENUM('scheduled', 'ongoing', 'completed', 'canceled') NOT NULL DEFAULT 'scheduled',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`schedule_id`) REFERENCES `class_schedule`(`schedule_id`) ON DELETE CASCADE,
    INDEX `idx_meeting_status` (`status`),
    INDEX `idx_meeting_code` (`meeting_code`),
    INDEX `idx_schedule_id` (`schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `meeting_recordings` (
    `recording_id` INT PRIMARY KEY AUTO_INCREMENT,
    `meeting_uid` VARCHAR(255) NOT NULL,
    `schedule_id` INT NOT NULL,
    `record_id` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `url` TEXT NOT NULL,
    `start_time` TIMESTAMP NULL,
    `end_time` TIMESTAMP NULL,
    `duration` INT DEFAULT 0,
    `size` BIGINT DEFAULT 0,
    `state` ENUM('processing', 'processed', 'published', 'unpublished', 'deleted') DEFAULT 'processing',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`schedule_id`) REFERENCES `class_schedule`(`schedule_id`) ON DELETE CASCADE,
    FOREIGN KEY (`meeting_uid`) REFERENCES `meetings`(`meeting_uid`) ON DELETE CASCADE,
    INDEX `idx_meeting_recordings` (`meeting_uid`, `state`),
    INDEX `idx_schedule_recordings` (`schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Transaction Management Tables
--
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `transactions` (
    `transaction_id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `recipient_id` INT NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `type` ENUM('payment', 'refund') NOT NULL,
    `status` ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL,
    `reference_number` VARCHAR(50) NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    FOREIGN KEY (`recipient_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    INDEX `idx_reference_number` (`reference_number`),
    INDEX `idx_transaction_status` (`status`),
    INDEX `idx_transaction_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `paymongo_transactions` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `transaction_id` INT NOT NULL,
    `payment_id` VARCHAR(50) NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL,
    `payment_status` VARCHAR(50) NOT NULL,
    `payment_details` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`transaction_id`) ON DELETE CASCADE,
    INDEX `idx_payment_id` (`payment_id`),
    INDEX `idx_payment_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Rating and Certificate Management Tables
--
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `ratings` (
    `rating_id` INT PRIMARY KEY AUTO_INCREMENT,
    `class_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `tutor_id` INT NOT NULL,
    `rating` INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    `comment` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`class_id`) REFERENCES `class`(`class_id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    FOREIGN KEY (`tutor_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    INDEX `idx_tutor_rating` (`tutor_id`, `rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `certificate` (
    `certificate_id` INT PRIMARY KEY AUTO_INCREMENT,
    `student_id` INT NOT NULL,
    `class_id` INT NOT NULL,
    `certificate_path` VARCHAR(255) NOT NULL,
    `issue_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('active', 'revoked') NOT NULL DEFAULT 'active',
    FOREIGN KEY (`student_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    FOREIGN KEY (`class_id`) REFERENCES `class`(`class_id`) ON DELETE CASCADE,
    INDEX `idx_student_cert` (`student_id`),
    INDEX `idx_cert_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Notification System Tables
--
-- --------------------------------------------------------

-- Insert sample notifications
INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `status`) 
SELECT 
    u.uid,
    'Welcome to TechTutor!',
    'Welcome to TechTutor! We are excited to have you join our learning platform.',
    'system',
    'unread'
FROM users u;

-- Sample Notifications
INSERT IGNORE INTO `notifications` (`user_id`, `title`, `message`, `type`, `status`, `created_at`) 
VALUES
-- System Notifications
(1, 'Welcome to TechTutor!', 'Welcome to TechTutor! Start your learning journey by exploring available courses.', 'system', 'unread', CURRENT_TIMESTAMP),
(1, 'Profile Update Required', 'Please complete your profile information to enhance your learning experience.', 'system', 'unread', CURRENT_TIMESTAMP),

-- Class Notifications
(2, 'New Class Scheduled', 'Your Python Programming Basics class is scheduled for tomorrow at 2:00 PM.', 'class', 'unread', CURRENT_TIMESTAMP),
(2, 'Class Reminder', 'Reminder: Your Web Development class starts in 1 hour.', 'class', 'read', CURRENT_TIMESTAMP),

-- File Share Notifications
(3, 'New Learning Material', 'New study materials have been uploaded for JavaScript Fundamentals.', 'file_share', 'unread', CURRENT_TIMESTAMP),
(3, 'Assignment Update', 'Your assignment for Database Design has been reviewed.', 'file_share', 'read', CURRENT_TIMESTAMP),

-- Recording Notifications
(4, 'Class Recording Available', 'The recording for your recent Python class is now available.', 'recording', 'unread', CURRENT_TIMESTAMP),
(4, 'Recording Expiry Notice', 'Your Java Programming recording will expire in 7 days.', 'recording', 'read', CURRENT_TIMESTAMP),

-- Certificate Notifications
(5, 'Certificate Earned', 'Congratulations! You have earned a certificate in Web Development Basics.', 'certificate', 'unread', CURRENT_TIMESTAMP),
(5, 'Certificate Available', 'Your Python Programming certificate is ready for download.', 'certificate', 'read', CURRENT_TIMESTAMP);

-- --------------------------------------------------------
--
-- Integration Settings Tables
--
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `integration_settings` (
    `setting_id` INT PRIMARY KEY AUTO_INCREMENT,
    `integration_type` ENUM('google_drive', 'bigbluebutton') NOT NULL,
    `setting_key` VARCHAR(64) NOT NULL,
    `setting_value` TEXT NOT NULL,
    `is_encrypted` TINYINT(1) DEFAULT 0,
    `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT NULL,
    UNIQUE KEY `unique_setting` (`integration_type`, `setting_key`),
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`uid`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Default Data
--
-- --------------------------------------------------------

-- Insert default file categories
INSERT INTO `file_categories` 
    (`category_name`, `extensions`, `icon_class`, `color_class`) 
VALUES 
    ('document', 'doc,docx,txt', 'bi-file-text-fill', 'text-primary'),
    ('pdf', 'pdf', 'bi-file-pdf-fill', 'text-danger'),
    ('image', 'jpg,jpeg,png,gif', 'bi-file-image-fill', 'text-success'),
    ('video', 'mp4,avi,mov', 'bi-camera-video-fill', 'text-info'),
    ('presentation', 'ppt,pptx', 'bi-easel-fill', 'text-warning'),
    ('spreadsheet', 'xls,xlsx', 'bi-table', 'text-success'),
    ('assignment', 'zip,rar', 'bi-file-zip-fill', 'text-secondary'),
    ('resource', '*', 'bi-folder-fill', 'text-dark');

-- Insert default storage tiers
INSERT INTO `storage_tiers` 
    (`tier_name`, `storage_limit`, `price_monthly`, `is_active`) 
VALUES 
    ('Basic', 1073741824, 0.00, 1),         -- 1GB free
    ('Standard', 5368709120, 4.99, 1),      -- 5GB
    ('Premium', 10737418240, 9.99, 1),      -- 10GB
    ('Professional', 26843545600, 19.99, 1); -- 25GB

-- Insert default BigBlueButton settings
INSERT INTO `integration_settings` 
    (`integration_type`, `setting_key`, `setting_value`, `is_encrypted`) 
VALUES 
    ('bigbluebutton', 'api_url', 'https://example.bbb.com/bigbluebutton/api/', 0),
    ('bigbluebutton', 'shared_secret', 'your-bbb-secret', 1),
    ('bigbluebutton', 'default_welcome_message', 'Welcome to TechTutor Online Session!', 0),
    ('bigbluebutton', 'default_duration', '60', 0),
    ('bigbluebutton', 'max_participants', '25', 0),
    ('bigbluebutton', 'default_mute_on_start', '1', 0),
    ('bigbluebutton', 'default_webcams_only_for_moderator', '0', 0),
    ('bigbluebutton', 'default_recording_enabled', '1', 0);

-- Insert default Google Drive settings
INSERT INTO `integration_settings` 
    (`integration_type`, `setting_key`, `setting_value`, `is_encrypted`) 
VALUES 
    ('google_drive', 'client_id', 'your-client-id.apps.googleusercontent.com', 1),
    ('google_drive', 'client_secret', 'your-client-secret', 1),
    ('google_drive', 'redirect_uri', 'https://your-domain.com/oauth2callback', 0),
    ('google_drive', 'application_name', 'TechTutor File Storage', 0),
    ('google_drive', 'root_folder_id', 'your-folder-id', 0),
    ('google_drive', 'max_file_size', '104857600', 0), -- 100MB
    ('google_drive', 'allowed_extensions', 'doc,docx,pdf,txt,jpg,jpeg,png,gif,mp4,avi,mov,ppt,pptx,xls,xlsx', 0);

-- Sample Data
INSERT INTO `users` (`email`, `password`, `role`, `status`, `first_name`, `last_name`) VALUES 
('tutor@test.com', '$2y$10$FwM//r8Nn2GUWpHSBMv0RuYxw7oBScsxjf.cYlnUuq1V2KcQkyM3.', 'TECHGURU', 'active', 'Test', 'Tutor'),
('student@test.com', '$2y$10$FwM//r8Nn2GUWpHSBMv0RuYxw7oBScsxjf.cYlnUuq1V2KcQkyM3.', 'TECHKID', 'active', 'Test', 'Student'),
('admin@test.com', '$2y$10$FwM//r8Nn2GUWpHSBMv0RuYxw7oBScsxjf.cYlnUuq1V2KcQkyM3.', 'ADMIN', 'active', 'Test', 'Admin');

INSERT INTO `course` (`course_name`, `description`) VALUES 
('Computer Programming', 'Learn how to write, debug, and develop software applications using various programming languages and methodologies.'),
('Computer Networking', 'Understand the principles of networking, including setup, security, and cloud integration to build robust IT infrastructures.'),
('Graphics Design', 'Master digital design, UI/UX, and animation techniques to create stunning visuals and interactive experiences.');

INSERT INTO `subject` (`course_id`, `subject_name`, `description`) VALUES 
(1, 'Python Programming', 'Learn the fundamentals of Python, including syntax, data structures, and object-oriented programming.'),
(1, 'Java Development', 'Covers Java programming basics, OOP principles, and application development.'),
(1, 'C++ for Beginners', 'Introduces students to C++ programming, focusing on memory management, data structures, and algorithms.'),
(2, 'Network Fundamentals', 'Explores basic networking concepts, protocols, and network topologies.'),
(2, 'Routing and Switching', 'Focuses on configuring routers and switches, subnetting, and VLANs.'),
(2, 'Network Security', 'Covers firewalls, intrusion detection systems, and encryption techniques for secure networking.'),
(3, 'UI/UX Designing', 'Teaches principles of user interface and user experience design for web and mobile applications.'),
(3, 'Adobe Photoshop Essentials', 'Covers photo editing, digital painting, and design techniques using Adobe Photoshop.'),
(3, 'Vector Illustration with Adobe Illustrator', 'Focuses on creating digital illustrations, logos, and typography with Illustrator.');

COMMIT;
