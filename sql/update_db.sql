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
ALTER TABLE enrollments MODIFY COLUMN status enum('active','completed','dropped', 'pending') NOT NULL DEFAULT 'active';

-- Create material_folders table
CREATE TABLE IF NOT EXISTS material_folders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    folder_name VARCHAR(255) NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES class(class_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(uid) ON DELETE CASCADE
);

-- Create class_materials table
CREATE TABLE IF NOT EXISTS class_materials (
    material_id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    folder_id INT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_uuid VARCHAR(255) NOT NULL UNIQUE,
    file_type VARCHAR(50) NOT NULL,
    description TEXT,
    upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT NOT NULL,
    FOREIGN KEY (class_id) REFERENCES class(class_id) ON DELETE CASCADE,
    FOREIGN KEY (folder_id) REFERENCES material_folders(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(uid) ON DELETE CASCADE
);

-- Create indexes for faster lookups
CREATE INDEX IF NOT EXISTS idx_class_folders ON material_folders(class_id);
CREATE INDEX IF NOT EXISTS idx_material_folders ON class_materials(folder_id);
CREATE INDEX IF NOT EXISTS idx_material_class ON class_materials(class_id);
CREATE INDEX IF NOT EXISTS idx_material_user ON class_materials(user_id);
CREATE INDEX IF NOT EXISTS idx_material_uuid ON class_materials(file_uuid);
--------------------------------------------------------------------------------------
-- Updated File Management Schema for Capstone-1
-- This update consolidates the file management tables for both TechGuru (teachers) and TechKid (students)

-- Drop existing tables (if needed in production, make sure to migrate data first)
DROP TABLE IF EXISTS file_access;
DROP TABLE IF EXISTS file_category_mapping;
DROP TABLE IF EXISTS file_upload_requests;
DROP TABLE IF EXISTS class_materials;
DROP TABLE IF EXISTS material_folders;

-- Create a unified files table that combines file_management and class_materials
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
    FOREIGN KEY (class_id) REFERENCES class(class_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(uid) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES file_categories(category_id) ON DELETE SET NULL
);

-- Create a unified folders table
CREATE TABLE IF NOT EXISTS `file_folders` (
    `folder_id` INT PRIMARY KEY AUTO_INCREMENT,
    `class_id` INT NULL,
    `user_id` INT NOT NULL,
    `folder_name` VARCHAR(255) NOT NULL,
    `parent_folder_id` INT NULL,
    `google_folder_id` VARCHAR(255) NOT NULL,
    `visibility` ENUM('private', 'public', 'class_only', 'specific_users') DEFAULT 'private',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES class(class_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(uid) ON DELETE CASCADE,
    FOREIGN KEY (parent_folder_id) REFERENCES file_folders(folder_id) ON DELETE CASCADE
);

-- Add the foreign key to link files to folders after folder table is created
ALTER TABLE unified_files 
ADD FOREIGN KEY (folder_id) REFERENCES file_folders(folder_id) ON DELETE SET NULL;

-- Create a simplified file access table
CREATE TABLE IF NOT EXISTS `file_permissions` (
    `permission_id` INT PRIMARY KEY AUTO_INCREMENT,
    `file_id` INT NULL,
    `folder_id` INT NULL,
    `user_id` INT NOT NULL,
    `access_type` ENUM('view', 'edit', 'owner') DEFAULT 'view',
    `granted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `granted_by` INT NOT NULL,
    FOREIGN KEY (file_id) REFERENCES unified_files(file_id) ON DELETE CASCADE,
    FOREIGN KEY (folder_id) REFERENCES file_folders(folder_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(uid) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(uid) ON DELETE CASCADE,
    CHECK (file_id IS NOT NULL OR folder_id IS NOT NULL)
);

-- Create a simplified file request table
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
    FOREIGN KEY (class_id) REFERENCES class(class_id) ON DELETE CASCADE,
    FOREIGN KEY (requester_id) REFERENCES users(uid) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(uid) ON DELETE CASCADE,
    FOREIGN KEY (response_file_id) REFERENCES unified_files(file_id) ON DELETE SET NULL
);

-- Create optimized indexes
CREATE INDEX IF NOT EXISTS idx_unified_files_class ON unified_files(class_id);
CREATE INDEX IF NOT EXISTS idx_unified_files_user ON unified_files(user_id);
CREATE INDEX IF NOT EXISTS idx_unified_files_folder ON unified_files(folder_id);
CREATE INDEX IF NOT EXISTS idx_unified_files_visibility ON unified_files(visibility);
CREATE INDEX IF NOT EXISTS idx_unified_files_purpose ON unified_files(file_purpose);
CREATE INDEX IF NOT EXISTS idx_unified_files_category ON unified_files(category_id);
CREATE INDEX IF NOT EXISTS idx_file_folders_class ON file_folders(class_id);
CREATE INDEX IF NOT EXISTS idx_file_folders_user ON file_folders(user_id);
CREATE INDEX IF NOT EXISTS idx_file_folders_parent ON file_folders(parent_folder_id);
CREATE INDEX IF NOT EXISTS idx_file_folders_visibility ON file_folders(visibility);
CREATE INDEX IF NOT EXISTS idx_file_permissions_file ON file_permissions(file_id);
CREATE INDEX IF NOT EXISTS idx_file_permissions_folder ON file_permissions(folder_id);
CREATE INDEX IF NOT EXISTS idx_file_permissions_user ON file_permissions(user_id);
CREATE INDEX IF NOT EXISTS idx_file_requests_class ON file_requests(class_id);
CREATE INDEX IF NOT EXISTS idx_file_requests_requester ON file_requests(requester_id);
CREATE INDEX IF NOT EXISTS idx_file_requests_recipient ON file_requests(recipient_id);
CREATE INDEX IF NOT EXISTS idx_file_requests_status ON file_requests(status);

-- Create file tags table 
CREATE TABLE IF NOT EXISTS `file_tags` (
    `tag_id` INT NOT NULL AUTO_INCREMENT,
    `tag_name` VARCHAR(50) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`tag_id`),
    UNIQUE KEY `tag_name_unique` (`tag_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create file tag mapping table
CREATE TABLE IF NOT EXISTS `file_tag_map` (
    `map_id` INT NOT NULL AUTO_INCREMENT,
    `file_id` INT NOT NULL,
    `tag_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`map_id`),
    UNIQUE KEY `file_tag_unique` (`file_id`, `tag_id`),
    FOREIGN KEY (`file_id`) REFERENCES `unified_files` (`file_id`) ON DELETE CASCADE,
    FOREIGN KEY (`tag_id`) REFERENCES `file_tags` (`tag_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
