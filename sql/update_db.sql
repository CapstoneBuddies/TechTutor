-- Add recording visibility table to track which recordings are visible to students
CREATE TABLE IF NOT EXISTS `recording_visibility` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recording_id` varchar(255) NOT NULL,
  `class_id` int NOT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_recording` (`recording_id`),
  KEY `fk_rec_vis_class` (`class_id`),
  KEY `fk_rec_vis_creator` (`created_by`),
  CONSTRAINT `fk_rec_vis_class` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rec_vis_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`uid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 