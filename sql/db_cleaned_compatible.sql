-- Cleaned and optimized database schema for TechTutor platform
-- Resolves circular dependencies, standardizes naming conventions,
-- and includes BigBlueButton integration tables
-- COMPATIBLE with existing certificate management code

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO"; 
START TRANSACTION;

-- =============================================
-- Core Tables (No Dependencies)
-- =============================================

CREATE TABLE `course` (
  `course_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_name` varchar(255) NOT NULL,
  `course_desc` text DEFAULT NULL,
  PRIMARY KEY (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `users` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `gender` enum('M','F','U') DEFAULT 'U',
  `profile_picture` varchar(255) DEFAULT 'default.jpg',
  `address` text DEFAULT NULL,
  `contact_number` varchar(16) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `rating_count` int(11) DEFAULT 0,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `role` enum('TECHGURU','TECHKID','ADMIN') NOT NULL DEFAULT 'TECHKID',
  `created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_user_status` (`status`),
  KEY `idx_email` (`email`),
  KEY `idx_user_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `file_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `unique_category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `file_tags` (
  `tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`tag_id`),
  UNIQUE KEY `tag_name_unique` (`tag_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `login_tokens` (
  `token_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `verification_code` varchar(6) DEFAULT NULL,
  `type` enum('remember_me','email_verification','reset') NOT NULL,
  `expiration_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`token_id`),
  UNIQUE KEY `user_id` (`user_id`,`token`),
  KEY `idx_token` (`token`),
  CONSTRAINT `login_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================
-- Second Level Tables (Depends on Core Tables)
-- =============================================

CREATE TABLE `subject` (
  `subject_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) DEFAULT NULL,
  `subject_name` varchar(255) NOT NULL,
  `subject_desc` text DEFAULT NULL,
  `image` varchar(255) DEFAULT 'default.jpg',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`subject_id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `subject_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `class` (
  `class_id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_id` int(11) DEFAULT NULL,
  `class_name` varchar(255) NOT NULL,
  `class_desc` text NOT NULL,
  `tutor_id` int(11) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `class_size` int(11) DEFAULT NULL,
  `status` enum('active','restricted','completed','pending') NOT NULL DEFAULT 'active',
  `is_free` tinyint(1) NOT NULL DEFAULT 1,
  `price` float(10,2) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`class_id`),
  KEY `idx_class_name` (`class_name`),
  KEY `idx_class_status` (`status`),
  KEY `idx_class_tutor` (`tutor_id`),
  KEY `idx_class_subject` (`subject_id`),
  KEY `idx_class_deleted` (`deleted_at`),
  CONSTRAINT `class_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`) ON DELETE SET NULL,
  CONSTRAINT `class_ibfk_2` FOREIGN KEY (`tutor_id`) REFERENCES `users` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================
-- Certificate Table (Compatible with existing code)
-- =============================================

CREATE TABLE `certificate` (
  `cert_id` int(11) NOT NULL AUTO_INCREMENT,
  `cert_uuid` varchar(255) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `recipient` int(11) NOT NULL,
  `award` varchar(255) NOT NULL,
  `donor` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  PRIMARY KEY (`cert_id`),
  UNIQUE KEY `cert_uuid` (`cert_uuid`),
  KEY `recipient` (`recipient`),
  KEY `donor` (`donor`),
  KEY `class_id` (`class_id`),
  CONSTRAINT `certificate_ibfk_1` FOREIGN KEY (`recipient`) REFERENCES `users` (`uid`),
  CONSTRAINT `certificate_ibfk_2` FOREIGN KEY (`donor`) REFERENCES `users` (`uid`),
  CONSTRAINT `certificate_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================
-- Enrollment and Class Schedule Tables
-- =============================================

CREATE TABLE `enrollments` (
  `enrollment_id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','completed','dropped','pending') NOT NULL DEFAULT 'active',
  `message` TEXT NULL,
  PRIMARY KEY (`enrollment_id`),
  UNIQUE KEY `unique_enrollment` (`class_id`,`student_id`),
  KEY `idx_enrollment_class` (`class_id`),
  KEY `idx_enrollment_student` (`student_id`),
  KEY `idx_enrollment_status` (`status`),
  CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE,
  CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `class_schedule` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('pending','confirmed','completed','canceled') DEFAULT 'pending',
  `status_changed_at` DATETIME DEFAULT NULL,
  `meeting_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`schedule_id`),
  KEY `idx_schedule_class` (`class_id`),
  KEY `idx_schedule_date` (`session_date`),
  KEY `idx_schedule_status` (`status`),
  KEY `idx_meeting_id` (`meeting_id`),
  CONSTRAINT `class_schedule_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL DEFAULT 'absent',
  `join_time` datetime DEFAULT NULL,
  `leave_time` datetime DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`attendance_id`),
  UNIQUE KEY `unique_attendance` (`schedule_id`,`student_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `class_schedule` (`schedule_id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `session_feedback` (
  `feedback_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (rating BETWEEN 1 AND 5),
  `comments` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`feedback_id`),
  UNIQUE KEY `unique_feedback` (`schedule_id`,`student_id`),
  KEY `idx_feedback_student` (`student_id`),
  KEY `idx_feedback_rating` (`rating`),
  CONSTRAINT `session_feedback_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `class_schedule` (`schedule_id`) ON DELETE CASCADE,
  CONSTRAINT `session_feedback_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================
-- File System Tables
-- =============================================

CREATE TABLE `file_folders` (
  `folder_id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `folder_name` varchar(255) NOT NULL,
  `parent_folder_id` int(11) DEFAULT NULL,
  `google_folder_id` varchar(255) NOT NULL,
  `visibility` enum('private','public','class_only','specific_users') DEFAULT 'private',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`folder_id`),
  KEY `idx_file_folders_class` (`class_id`),
  KEY `idx_file_folders_user` (`user_id`),
  KEY `idx_file_folders_parent` (`parent_folder_id`),
  KEY `idx_file_folders_visibility` (`visibility`),
  CONSTRAINT `file_folders_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE,
  CONSTRAINT `file_folders_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE,
  CONSTRAINT `file_folders_ibfk_3` FOREIGN KEY (`parent_folder_id`) REFERENCES `file_folders` (`folder_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `unified_files` (
  `file_id` int(11) NOT NULL AUTO_INCREMENT,
  `file_uuid` varchar(255) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `folder_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `google_file_id` varchar(255) NOT NULL,
  `drive_link` text NOT NULL,
  `description` text DEFAULT NULL,
  `visibility` enum('private','public','class_only','specific_users') DEFAULT 'private',
  `file_purpose` enum('personal','class_material','assignment','submission') DEFAULT 'personal',
  `upload_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`file_id`),
  UNIQUE KEY `file_uuid` (`file_uuid`),
  KEY `idx_unified_files_class` (`class_id`),
  KEY `idx_unified_files_user` (`user_id`),
  KEY `idx_unified_files_folder` (`folder_id`),
  KEY `idx_unified_files_visibility` (`visibility`),
  KEY `idx_unified_files_purpose` (`file_purpose`),
  KEY `idx_unified_files_category` (`category_id`),
  KEY `idx_unified_files_deleted` (`deleted_at`),
  CONSTRAINT `unified_files_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE,
  CONSTRAINT `unified_files_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE,
  CONSTRAINT `unified_files_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `file_categories` (`category_id`) ON DELETE SET NULL,
  CONSTRAINT `unified_files_ibfk_4` FOREIGN KEY (`folder_id`) REFERENCES `file_folders` (`folder_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `file_access` (
  `access_id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `granted_by` int(11) NOT NULL,
  PRIMARY KEY (`access_id`),
  UNIQUE KEY `unique_file_access` (`file_id`,`enrollment_id`),
  KEY `enrollment_id` (`enrollment_id`),
  KEY `granted_by` (`granted_by`),
  CONSTRAINT `file_access_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `unified_files` (`file_id`) ON DELETE CASCADE,
  CONSTRAINT `file_access_ibfk_2` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`enrollment_id`) ON DELETE CASCADE,
  CONSTRAINT `file_access_ibfk_3` FOREIGN KEY (`granted_by`) REFERENCES `users` (`uid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `file_tags_mapping` (
  `file_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`file_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `file_tags_mapping_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `unified_files` (`file_id`) ON DELETE CASCADE,
  CONSTRAINT `file_tags_mapping_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `file_tags` (`tag_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================
-- BigBlueButton Integration Tables
-- =============================================

CREATE TABLE `meetings` (
  `meeting_id` varchar(255) NOT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `class_id` int(11) NOT NULL,
  `tutor_id` int(11) NOT NULL,
  `meeting_name` varchar(255) NOT NULL,
  `attendee_pw` varchar(255) NOT NULL,
  `moderator_pw` varchar(255) NOT NULL,
  `welcome_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ended_at` timestamp NULL DEFAULT NULL,
  `status` enum('created','started','ended') DEFAULT 'created',
  PRIMARY KEY (`meeting_id`),
  KEY `idx_meeting_class` (`class_id`),
  KEY `idx_meeting_tutor` (`tutor_id`),
  KEY `idx_meeting_schedule` (`schedule_id`),
  CONSTRAINT `meetings_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE,
  CONSTRAINT `meetings_ibfk_2` FOREIGN KEY (`tutor_id`) REFERENCES `users` (`uid`),
  CONSTRAINT `meetings_ibfk_3` FOREIGN KEY (`schedule_id`) REFERENCES `class_schedule` (`schedule_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `meeting_analytics` (
  `analytics_id` int(11) NOT NULL AUTO_INCREMENT,
  `meeting_id` varchar(255) NOT NULL,
  `tutor_id` int(11) NOT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `duration` int(11) DEFAULT NULL COMMENT 'in minutes',
  `participant_count` int(11) DEFAULT 0,
  `recording_available` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`analytics_id`),
  UNIQUE KEY `unique_meeting` (`meeting_id`),
  KEY `tutor_id` (`tutor_id`),
  CONSTRAINT `meeting_analytics_ibfk_1` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`meeting_id`) ON DELETE CASCADE,
  CONSTRAINT `meeting_analytics_ibfk_2` FOREIGN KEY (`tutor_id`) REFERENCES `users` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `meeting_participants` (
  `participant_id` int(11) NOT NULL AUTO_INCREMENT,
  `meeting_id` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `join_time` datetime DEFAULT NULL,
  `leave_time` datetime DEFAULT NULL,
  `duration` int(11) DEFAULT NULL COMMENT 'in seconds',
  `is_moderator` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`participant_id`),
  KEY `meeting_id` (`meeting_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `meeting_participants_ibfk_1` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`meeting_id`) ON DELETE CASCADE,
  CONSTRAINT `meeting_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `meeting_recordings` (
  `recording_id` int(11) NOT NULL AUTO_INCREMENT,
  `meeting_id` varchar(255) NOT NULL,
  `bbb_recording_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `published` tinyint(1) DEFAULT 1,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `playback_url` text DEFAULT NULL,
  `playback_length` int(11) DEFAULT NULL COMMENT 'in seconds',
  `participants` int(11) DEFAULT NULL,
  PRIMARY KEY (`recording_id`),
  UNIQUE KEY `unique_bbb_recording` (`bbb_recording_id`),
  KEY `meeting_id` (`meeting_id`),
  CONSTRAINT `meeting_recordings_ibfk_1` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`meeting_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================
-- Notification System
-- =============================================

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error','system') DEFAULT 'info',
  `related_entity` varchar(50) DEFAULT NULL COMMENT 'e.g., class, meeting, file',
  `entity_id` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `idx_notif_user` (`user_id`),
  KEY `idx_notif_read` (`is_read`),
  KEY `idx_notif_type` (`type`),
  KEY `idx_notif_entity` (`related_entity`,`entity_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================
-- Transactions and Payments
-- =============================================

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('credit_card','debit_card','paypal','bank_transfer','other') NOT NULL,
  `status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `reference_id` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`transaction_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_transaction_status` (`status`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `class_payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`payment_id`),
  KEY `transaction_id` (`transaction_id`),
  KEY `class_id` (`class_id`),
  KEY `enrollment_id` (`enrollment_id`),
  CONSTRAINT `class_payments_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`transaction_id`),
  CONSTRAINT `class_payments_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`),
  CONSTRAINT `class_payments_ibfk_3` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`enrollment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT; 