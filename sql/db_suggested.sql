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
-- (04-01-2025)
ALTER TABLE `class_schedule`
ADD COLUMN `status_changed_at` DATETIME DEFAULT NULL;
DELIMITER $$

CREATE TRIGGER update_status_changed_at BEFORE UPDATE ON `class_schedule`
FOR EACH ROW
BEGIN
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        SET NEW.status_changed_at = NOW();
    END IF;
END $$

DELIMITER ;

alter table enrollments
drop column invitation_message,
add column `message` TEXT NULL;

-- Database schema updates to fix webhook handling

-- Option 1: Add tutor_id to the meetings table (simplest solution)
ALTER TABLE `meetings` 
ADD COLUMN `tutor_id` int(11) AFTER `schedule_id`,
ADD KEY `idx_meetings_tutor` (`tutor_id`),
ADD CONSTRAINT `meetings_ibfk_2` FOREIGN KEY (`tutor_id`) REFERENCES `users` (`uid`);

-- Update existing meetings with the tutor_id from the class table
UPDATE meetings m
JOIN class_schedule cs ON m.schedule_id = cs.schedule_id
JOIN class c ON cs.class_id = c.class_id
SET m.tutor_id = c.tutor_id
WHERE m.tutor_id IS NULL;

-- Update the meeting_analytics table to enforce the foreign key constraint
ALTER TABLE `meeting_analytics` MODIFY `meeting_id` varchar(50) NOT NULL;

-- Drop the existing foreign key constraint
ALTER TABLE `meeting_analytics` DROP FOREIGN KEY `meeting_analytics_ibfk_1`;

-- Add the new foreign key constraint
ALTER TABLE `meeting_analytics` 
ADD CONSTRAINT `meeting_analytics_ibfk_1` FOREIGN KEY (`meeting_id`) 
REFERENCES `meetings` (`meeting_uid`) ON DELETE CASCADE;
-- 04-04-2025

-- Transaction Dispute Table
CREATE TABLE `transaction_disputes` (
  `dispute_id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','under_review','resolved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`dispute_id`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_dispute_status` (`status`),
  CONSTRAINT `transaction_disputes_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`transaction_id`) ON DELETE CASCADE,
  CONSTRAINT `transaction_disputes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE
);

-- Optional: Transaction Refund Table
CREATE TABLE `transaction_refunds` (
  `refund_id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `dispute_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `refund_reference` varchar(255) DEFAULT NULL,
  `admin_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`refund_id`),
  KEY `idx_refund_transaction` (`transaction_id`),
  KEY `idx_refund_dispute` (`dispute_id`),
  KEY `idx_refund_admin` (`admin_id`),
  KEY `idx_refund_status` (`status`),
  CONSTRAINT `transaction_refunds_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`transaction_id`) ON DELETE CASCADE,
  CONSTRAINT `transaction_refunds_ibfk_2` FOREIGN KEY (`dispute_id`) REFERENCES `transaction_disputes` (`dispute_id`) ON DELETE SET NULL,
  CONSTRAINT `transaction_refunds_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE
);

ALTER TABLE users
ADD COLUMN `token_balance` double(5,2) NULL DEFAULT 0.00
AFTER `rating_count`;

-- 04-04-2025 Update: Add transaction_type to transactions table
ALTER TABLE transactions
ADD COLUMN `transaction_type` varchar(50) DEFAULT 'token' AFTER `description`,
ADD COLUMN `class_id` int(11) DEFAULT NULL AFTER `transaction_type`,
ADD KEY `idx_transaction_type` (`transaction_type`),
ADD KEY `idx_class_id` (`class_id`),
ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE SET NULL;
ALTER TABLE transactions
DROP FOREIGN KEY transactions_ibfk_2,
drop class_id;