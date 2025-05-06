-- Suggested database schema improvements for TechTutor platform

-- Add missing indexes for performance
ALTER TABLE `class`
  ADD INDEX `idx_class_status` (`status`),
  ADD INDEX `idx_class_tutor` (`tutor_id`),
  ADD INDEX `idx_class_subject` (`subject_id`);

ALTER TABLE `subject`
  ADD INDEX `idx_subject_course` (`course_id`),
  ADD INDEX `idx_subject_active` (`is_active`);

ALTER TABLE `enrollments`
  ADD UNIQUE KEY `unique_enrollment` (`class_id`, `student_id`),
  ADD INDEX `idx_enrollment_status` (`status`),
  ADD INDEX `idx_enrollment_student` (`student_id`),
  ADD INDEX `idx_enrollment_class` (`class_id`);

ALTER TABLE `class_schedule`
  ADD INDEX `idx_schedule_class` (`class_id`),
  ADD INDEX `idx_schedule_date` (`session_date`),
  ADD INDEX `idx_schedule_status` (`status`);

-- Ensure all foreign keys have ON DELETE CASCADE where appropriate
ALTER TABLE `class`
  DROP FOREIGN KEY IF EXISTS `class_ibfk_1`,
  ADD CONSTRAINT `class_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`) ON DELETE SET NULL,
  DROP FOREIGN KEY IF EXISTS `class_ibfk_2`,
  ADD CONSTRAINT `class_ibfk_2` FOREIGN KEY (`tutor_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE;

ALTER TABLE `subject`
  DROP FOREIGN KEY IF EXISTS `subject_ibfk_1`,
  ADD CONSTRAINT `subject_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`) ON DELETE CASCADE;

ALTER TABLE `enrollments`
  DROP FOREIGN KEY IF EXISTS `enrollments_ibfk_1`,
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE,
  DROP FOREIGN KEY IF EXISTS `enrollments_ibfk_2`,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE;

ALTER TABLE `class_schedule`
  DROP FOREIGN KEY IF EXISTS `class_schedule_ibfk_1`,
  ADD CONSTRAINT `class_schedule_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE;

-- Add a view for active classes with subject and course info (for analytics)
CREATE OR REPLACE VIEW `active_classes_view` AS
SELECT 
  c.class_id, c.class_name, c.status, c.tutor_id, c.subject_id, s.subject_name, s.course_id, co.course_name
FROM class c
JOIN subject s ON c.subject_id = s.subject_id
JOIN course co ON s.course_id = co.course_id
WHERE c.status = 'active';

-- Add a trigger to automatically expire old pending enrollments (example)
DELIMITER $$
CREATE TRIGGER expire_old_pending_enrollments
AFTER UPDATE ON enrollments
FOR EACH ROW
BEGIN
  IF NEW.status = 'pending' AND TIMESTAMPDIFF(DAY, NEW.enrollment_date, NOW()) > 7 THEN
    UPDATE enrollments SET status = 'dropped' WHERE enrollment_id = NEW.enrollment_id;
  END IF;
END$$
DELIMITER ;

-- Add/clarify ENUM values to match PHP logic
ALTER TABLE `class`
  MODIFY COLUMN `status` ENUM('active','restricted','completed','pending') NOT NULL DEFAULT 'active';

ALTER TABLE `enrollments`
  MODIFY COLUMN `status` ENUM('active','completed','dropped','pending') NOT NULL DEFAULT 'active';

ALTER TABLE `class_schedule`
  MODIFY COLUMN `status` ENUM('pending','confirmed','completed','canceled') DEFAULT 'pending';

-- Add missing constraints for file management if not present
ALTER TABLE `file_management`
  ADD INDEX `idx_file_class` (`class_id`),
  ADD INDEX `idx_file_user` (`user_id`),
  ADD INDEX `idx_file_visibility` (`is_visible`);

-- Add a view for subject statistics (for admin dashboard)
CREATE OR REPLACE VIEW `subject_statistics_view` AS
SELECT 
  s.subject_id,
  s.subject_name,
  s.is_active,
  c.course_id,
  c.course_name,
  COUNT(DISTINCT cl.class_id) AS class_count,
  COUNT(DISTINCT e.student_id) AS student_count
FROM subject s
LEFT JOIN course c ON s.course_id = c.course_id
LEFT JOIN class cl ON s.subject_id = cl.subject_id
LEFT JOIN enrollments e ON cl.class_id = e.class_id AND e.status = 'active'
GROUP BY s.subject_id, s.subject_name, s.is_active, c.course_id, c.course_name;

-- End of suggested schema updates

CREATE TABLE IF NOT EXISTS `performances` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `level` VARCHAR(3) NOT NULL,
  `title` VARCHAR(20) NOT NULL,
  `description` TEXT
);

INSERT INTO `performances`(`level`,`title`,`description`) VALUES
('A1', 'Novice', 'New to the topic. No prior knowledge or experience.'),
('A2', 'Beginner', 'Understands basic concepts and terms. Can follow instructions with guidance.'),
('B1', 'Developing', 'Grasps core concepts and can perform simple tasks. Still needs occasional support.'),
('B2', 'Proficient', 'Solid understanding and able to apply concepts independently. This is the minimum level required to teach or tutor others.'),
('C1', 'Advanced', 'Applies knowledge to solve real-world or novel problems. Can evaluate and explain complex concepts.'),
('C2', 'Mastery', 'Demonstrates full command and flexibility. Can innovate, mentor, and teach at expert levels with ease.');

ALTER TABLE `enrollments` ADD COLUMN `performance_id` int NULL AFTER `students_id`;
ALTER TABLE `enrollments` ADD CONSTRAINT `enrollments_ibfk_3` FOREIGN KEY (`performance_id`) REFERENCES `performances`(`id`);

ALTER TABLE `class` ADD COLUMN `diagnostics` JSON null AFTER `class_size`;
ALTER TABLE `enrollments` ADD COLUMN `diagnostics_taken` TINYINT(1) DEFAULT 0 AFTER `status`;
CREATE TABLE `student_progress` (
  `progress_id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `performance_score` decimal(5,2) DEFAULT 0.00,
  `assessment_date` date NOT NULL,
  `assessment_type` enum('diagnostic','midterm','final') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`progress_id`),
  KEY `idx_progress_class` (`class_id`),
  KEY `idx_progress_student` (`student_id`),
  KEY `idx_progress_date` (`assessment_date`),
  KEY `idx_progress_type` (`assessment_type`),
  CONSTRAINT `student_progress_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE,
  CONSTRAINT `student_progress_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE
);