ALTER TABLE class_schedules DROP COLUMN role;

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