-- Add status field to class table for better class management
ALTER TABLE `class` 
ADD COLUMN `status` ENUM('active', 'restricted', 'completed', 'pending') NOT NULL DEFAULT 'active' AFTER `is_active`;

-- Add created_at and updated_at timestamps for tracking
ALTER TABLE `class`
ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add indexes for better query performance
CREATE INDEX idx_class_status ON class(status);
CREATE INDEX idx_class_tutor ON class(tutor_id);
CREATE INDEX idx_class_subject ON class(subject_id);

-- Update existing records
UPDATE `class` SET `status` = CASE WHEN `is_active` = 1 THEN 'active' ELSE 'restricted' END;


ALTER TABLE `class` 
DROP COLUMN `is_active`,
DROP COLUMN `status`,
ADD COLUMN `status` ENUM('active', 'restricted', 'completed', 'pending') NOT NULL DEFAULT 'pending' AFTER `class_size`;

-- Convert existing data
UPDATE `class` SET `status` = 'active' WHERE `is_active` = 1;
UPDATE `class` SET `status` = 'restricted' WHERE `is_active` = 0;