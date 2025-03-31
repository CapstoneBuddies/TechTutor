-- Add is_archived column to recording_visibility table
ALTER TABLE `recording_visibility` 
ADD COLUMN `is_archived` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_visible`;

-- Recreate the table definition for reference
CREATE TABLE IF NOT EXISTS `recording_visibility` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recording_id` varchar(255) NOT NULL,
  `class_id` int(11) NOT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT 0,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_recording` (`recording_id`),
  KEY `fk_rec_vis_class` (`class_id`),
  KEY `fk_rec_vis_creator` (`created_by`),
  CONSTRAINT `fk_rec_vis_class` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rec_vis_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`uid`) ON DELETE CASCADE
); 
-- Database update suggestions for class invitations

-- Add invitation_message column to the enrollments table to store invitation messages from tutors
ALTER TABLE `enrollments` 
ADD COLUMN `invitation_message` TEXT NULL AFTER `status`;

-- Create a trigger to automatically create notification when enrollment status changes
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `enrollment_status_update_trigger` 
AFTER UPDATE ON `enrollments`
FOR EACH ROW
BEGIN
    DECLARE tutor_id INT;
    DECLARE class_name VARCHAR(255);
    DECLARE student_name VARCHAR(255);
    
    IF OLD.status != NEW.status THEN
        -- Get tutor ID and class name
        SELECT c.tutor_id, c.class_name INTO tutor_id, class_name
        FROM class c WHERE c.class_id = NEW.class_id;
        
        -- Get student name
        SELECT CONCAT(u.first_name, ' ', u.last_name) INTO student_name
        FROM users u WHERE u.uid = NEW.student_id;
        
        -- If status changed to active (accepted invitation)
        IF NEW.status = 'active' AND OLD.status = 'pending' THEN
            -- Insert notification for tutor
            INSERT INTO notifications (
                recipient_id, 
                recipient_role, 
                message, 
                link, 
                class_id, 
                icon, 
                icon_color
            ) VALUES (
                tutor_id, 
                'TECHGURU', 
                CONCAT(student_name, ' has accepted your invitation to "', class_name, '"'), 
                CONCAT('dashboard/t/class/details?id=', NEW.class_id), 
                NEW.class_id, 
                'bi-person-check', 
                'text-success'
            );
        END IF;
        
        -- If enrollment was dropped
        IF NEW.status = 'dropped' AND OLD.status = 'active' THEN
            -- Insert notification for tutor
            INSERT INTO notifications (
                recipient_id, 
                recipient_role, 
                message, 
                link, 
                class_id, 
                icon, 
                icon_color
            ) VALUES (
                tutor_id, 
                'TECHGURU', 
                CONCAT(student_name, ' has dropped from "', class_name, '"'), 
                CONCAT('dashboard/t/class/details?id=', NEW.class_id), 
                NEW.class_id, 
                'bi-person-x', 
                'text-danger'
            );
        END IF;
    END IF;
END//
DELIMITER ; 
ALTER TABLE `recording_visibility` 
ADD COLUMN `schedule_id` INT(11) NOT NULL DEFAULT 0 AFTER `class_id`;