-- Create enrollments table if it doesn't exist
CREATE TABLE IF NOT EXISTS `enrollments` (
    `enrollment_id` INT PRIMARY KEY AUTO_INCREMENT,
    `class_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `enrollment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('active', 'completed', 'dropped') NOT NULL DEFAULT 'active',
    FOREIGN KEY (class_id) REFERENCES class(class_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(uid) ON DELETE CASCADE,
    UNIQUE KEY `unique_enrollment` (`class_id`, `student_id`)
);

-- Add indexes for better query performance
CREATE INDEX idx_enrollment_class ON enrollments(class_id);
CREATE INDEX idx_enrollment_student ON enrollments(student_id);
CREATE INDEX idx_enrollment_status ON enrollments(status);
