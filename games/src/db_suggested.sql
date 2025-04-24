-- Create a unified game challenges database schema
-- For TechTutor platform - Gaming module

-- Ensure we're using the same database
USE game_db;

-- Create difficulty levels table
CREATE TABLE IF NOT EXISTS `game_difficulty_levels` (
    `difficulty_id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create unified game challenges table
CREATE TABLE IF NOT EXISTS `game_challenges` (
    `challenge_id` INT AUTO_INCREMENT PRIMARY KEY,
    `challenge_type` ENUM('programming', 'networking', 'ui') NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `difficulty_id` INT NOT NULL,
    `content` JSON NOT NULL,
    `badge_name` VARCHAR(100),
    `badge_image` VARCHAR(255),
    `xp_value` INT NOT NULL DEFAULT 10,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`difficulty_id`) REFERENCES `game_difficulty_levels`(`difficulty_id`),
    INDEX `idx_challenge_type` (`challenge_type`),
    INDEX `idx_difficulty` (`difficulty_id`),
    INDEX `idx_is_active` (`is_active`)
);

-- Table for user progress/achievements
CREATE TABLE IF NOT EXISTS `game_user_progress` (
    `progress_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `challenge_id` INT NOT NULL,
    `score` INT NOT NULL DEFAULT 0,
    `time_taken` INT DEFAULT NULL COMMENT 'Time taken in seconds',
    `completed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `user_challenge` (`user_id`, `challenge_id`),
    INDEX `idx_challenge_id` (`challenge_id`),
    INDEX `idx_user_id` (`user_id`),
    FOREIGN KEY (`challenge_id`) REFERENCES `game_challenges` (`challenge_id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE
);

-- Table for badges and achievements
CREATE TABLE IF NOT EXISTS `game_badges` (
    `badge_id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `requirements` JSON DEFAULT NULL COMMENT 'JSON encoded requirements to earn this badge',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_badge_name` (`name`),
    INDEX `idx_badge_name` (`name`)
);

-- Table for user earned badges
CREATE TABLE IF NOT EXISTS `game_user_badges` (
    `user_badge_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `badge_id` INT NOT NULL,
    `earned_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_badge` (`user_id`, `badge_id`),
    INDEX `idx_badge_id` (`badge_id`),
    INDEX `idx_user_id` (`user_id`),
    FOREIGN KEY (`badge_id`) REFERENCES `game_badges` (`badge_id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE
);

-- Create a view for easier challenge retrieval
CREATE OR REPLACE VIEW `challenge_view` AS
SELECT 
    c.`challenge_id`,
    c.`challenge_type`,
    c.`name`,
    c.`description`,
    c.`content`,
    c.`badge_name`,
    c.`badge_image`,
    c.`xp_value`,
    c.`is_active`,
    d.`name` as difficulty
FROM 
    `game_challenges` c
JOIN 
    `game_difficulty_levels` d ON c.`difficulty_id` = d.`difficulty_id`
WHERE 
    c.`is_active` = 1;

-- Insert default difficulty levels
INSERT INTO `game_difficulty_levels` (`name`, `description`) VALUES
('Beginner', 'Easy challenges suitable for newcomers'),
('Intermediate', 'Moderate challenges requiring some knowledge'),
('Advanced', 'Difficult challenges for experienced users');

-- Insert default programming challenges
INSERT INTO `game_challenges` (`challenge_type`, `name`, `description`, `difficulty_id`, `content`, `badge_name`, `badge_image`, `xp_value`) VALUES
('programming', 'Hello World', 'Write code to print ''hello world''.', 1, 
 JSON_OBJECT(
    'starter_code', '// Write your PHP code here\n// Your goal is to print ''hello world''\n\n// Example:\n// echo ''hello world'';',
    'expected_output', 'hello world'
 ),
 'Hello World', 'programming/hello_world.png', 10),

('programming', 'Factorial Calculator', 'Write a function to calculate the factorial of a number.', 1, 
 JSON_OBJECT(
    'starter_code', '// Write your PHP code here\nfunction factorial($n) {\n    // Your code here\n}\n\n// Test the function\n$n = 5;\necho factorial($n);',
    'expected_output', '120'
 ),
 'Factorial Master', 'programming/factorial.png', 20),

('programming', 'String Reversal', 'Write a function to reverse a string.', 1, 
 JSON_OBJECT(
    'starter_code', '// Write your PHP code here\nfunction reverseString($str) {\n    // Your code here\n}\n\n// Test the function\n$str = ''hello'';\necho reverseString($str);',
    'expected_output', 'olleh'
 ),
 'String Wizard', 'programming/string_reversal.png', 15);

-- Insert default network challenges
INSERT INTO `game_challenges` (`challenge_type`, `name`, `description`, `difficulty_id`, `content`, `badge_name`, `badge_image`, `xp_value`) VALUES
('networking', 'Basic Network Setup', 'Connect the client computers to the router to establish a basic home network.', 1, 
 JSON_OBJECT(
    'devices', JSON_OBJECT(
        'router1', JSON_OBJECT('type', 'router', 'x', 400, 'y', 200, 'ip', '192.168.1.1'),
        'pc1', JSON_OBJECT('type', 'computer', 'x', 200, 'y', 100, 'ip', '192.168.1.2'),
        'pc2', JSON_OBJECT('type', 'computer', 'x', 200, 'y', 300, 'ip', '192.168.1.3'),
        'laptop1', JSON_OBJECT('type', 'laptop', 'x', 600, 'y', 100, 'ip', '192.168.1.4'),
        'laptop2', JSON_OBJECT('type', 'laptop', 'x', 600, 'y', 300, 'ip', '192.168.1.5')
    ),
    'solution', JSON_ARRAY(
        JSON_OBJECT('source', 'pc1', 'target', 'router1'),
        JSON_OBJECT('source', 'pc2', 'target', 'router1'),
        JSON_OBJECT('source', 'laptop1', 'target', 'router1'),
        JSON_OBJECT('source', 'laptop2', 'target', 'router1')
    ),
    'points', 100
 ),
 'Network Novice', 'networking/basic_network.png', 100),

('networking', 'Internet Connection', 'Connect the router to the modem to establish an internet connection for your network.', 2, 
 JSON_OBJECT(
    'devices', JSON_OBJECT(
        'modem', JSON_OBJECT('type', 'modem', 'x', 400, 'y', 100, 'ip', '203.0.113.1'),
        'router1', JSON_OBJECT('type', 'router', 'x', 400, 'y', 250, 'ip', '192.168.1.1'),
        'pc1', JSON_OBJECT('type', 'computer', 'x', 200, 'y', 300, 'ip', '192.168.1.2'),
        'pc2', JSON_OBJECT('type', 'computer', 'x', 600, 'y', 300, 'ip', '192.168.1.3'),
        'server', JSON_OBJECT('type', 'server', 'x', 400, 'y', 400, 'ip', '192.168.1.4')
    ),
    'solution', JSON_ARRAY(
        JSON_OBJECT('source', 'router1', 'target', 'modem'),
        JSON_OBJECT('source', 'pc1', 'target', 'router1'),
        JSON_OBJECT('source', 'pc2', 'target', 'router1'),
        JSON_OBJECT('source', 'server', 'target', 'router1')
    ),
    'points', 150
 ),
 'Internet Explorer', 'networking/internet_connection.png', 150),

('networking', 'Office Network with Switch', 'Create a small office network using a switch to connect multiple computers to a router.', 2, 
 JSON_OBJECT(
    'devices', JSON_OBJECT(
        'router1', JSON_OBJECT('type', 'router', 'x', 400, 'y', 100, 'ip', '192.168.1.1'),
        'switch1', JSON_OBJECT('type', 'switch', 'x', 400, 'y', 250, 'ip', ''),
        'pc1', JSON_OBJECT('type', 'computer', 'x', 200, 'y', 350, 'ip', '192.168.1.2'),
        'pc2', JSON_OBJECT('type', 'computer', 'x', 350, 'y', 350, 'ip', '192.168.1.3'),
        'pc3', JSON_OBJECT('type', 'computer', 'x', 450, 'y', 350, 'ip', '192.168.1.4'),
        'pc4', JSON_OBJECT('type', 'computer', 'x', 600, 'y', 350, 'ip', '192.168.1.5'),
        'printer', JSON_OBJECT('type', 'printer', 'x', 600, 'y', 200, 'ip', '192.168.1.6')
    ),
    'solution', JSON_ARRAY(
        JSON_OBJECT('source', 'switch1', 'target', 'router1'),
        JSON_OBJECT('source', 'pc1', 'target', 'switch1'),
        JSON_OBJECT('source', 'pc2', 'target', 'switch1'),
        JSON_OBJECT('source', 'pc3', 'target', 'switch1'),
        JSON_OBJECT('source', 'pc4', 'target', 'switch1'),
        JSON_OBJECT('source', 'printer', 'target', 'switch1')
    ),
    'points', 200
 ),
 'Network Pro', 'networking/office_network.png', 200);

-- Add challenge_type column to existing challenge_xp table if it doesn't exist
ALTER TABLE `challenge_xp` 
MODIFY COLUMN `challenge_type` VARCHAR(50) NOT NULL DEFAULT 'Coding';

-- Update level_definitions with additional levels if needed
INSERT INTO `level_definitions` (level, xp_required, badge_name, badge_image) 
VALUES 
(21, 150000, 'Programming Novice', 'badges/programming_novice.png'),
(22, 175000, 'Programming Adept', 'badges/programming_adept.png'),
(23, 200000, 'Network Technician', 'badges/network_technician.png'),
(24, 225000, 'Network Expert', 'badges/network_expert.png'),
(25, 250000, 'UI Designer', 'badges/ui_designer.png')
ON DUPLICATE KEY UPDATE 
badge_name = VALUES(badge_name),
badge_image = VALUES(badge_image);

-- Add additional challenge types to the challenge_xp table
INSERT INTO `challenge_xp` (challenge_id, xp_value, challenge_type) VALUES
(15, 60, 'UI'),   -- Basic UI Challenge
(16, 80, 'UI'),   -- Intermediate UI Challenge
(17, 100, 'UI')   -- Advanced UI Challenge
ON DUPLICATE KEY UPDATE 
xp_value = VALUES(xp_value),
challenge_type = VALUES(challenge_type);

-- Add more programming challenges to the challenge_xp table
INSERT INTO `challenge_xp` (challenge_id, xp_value, challenge_type) VALUES
(18, 25, 'Coding'),   -- String Manipulation
(19, 35, 'Coding'),   -- Array Operations
(20, 45, 'Coding')    -- Object-Oriented Programming
ON DUPLICATE KEY UPDATE 
xp_value = VALUES(xp_value),
challenge_type = VALUES(challenge_type);

-- Add more networking challenges to the challenge_xp table
INSERT INTO `challenge_xp` (challenge_id, xp_value, challenge_type) VALUES
(21, 80, 'Networking'),   -- Subnet Configuration
(22, 100, 'Networking'),  -- Firewall Setup
(23, 120, 'Networking')   -- VPN Configuration
ON DUPLICATE KEY UPDATE 
xp_value = VALUES(xp_value),
challenge_type = VALUES(challenge_type);

-- Add design challenge categories
CREATE TABLE IF NOT EXISTS `design_challenge_categories` (
    `category_id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `icon` VARCHAR(100)
);

-- Insert default categories
INSERT INTO `design_challenge_categories` (`name`, `description`, `icon`) VALUES
('Web Design', 'Challenges related to web page and website design', 'fa-globe'),
('Mobile UI', 'User interfaces for mobile applications', 'fa-mobile-alt'),
('Logos & Branding', 'Logo design and brand identity', 'fa-paint-brush'),
('UX Flow', 'User experience flow and interaction design', 'fa-project-diagram')
ON DUPLICATE KEY UPDATE
description = VALUES(description),
icon = VALUES(icon);

-- Enhance design_challenges table with additional fields
ALTER TABLE `design_challenges`
ADD COLUMN IF NOT EXISTS `category_id` INT,
ADD COLUMN IF NOT EXISTS `requirements` TEXT AFTER `criteria`,
ADD COLUMN IF NOT EXISTS `time_limit` INT COMMENT 'Time limit in minutes' AFTER `requirements`,
ADD COLUMN IF NOT EXISTS `max_score` INT DEFAULT 100 AFTER `time_limit`,
ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) DEFAULT 1 AFTER `max_score`,
ADD CONSTRAINT FOREIGN KEY IF NOT EXISTS (`category_id`) REFERENCES `design_challenge_categories`(`category_id`);

-- Enhance design_submissions table with additional fields
ALTER TABLE `design_submissions`
ADD COLUMN IF NOT EXISTS `design_notes` TEXT AFTER `feedback`,
ADD COLUMN IF NOT EXISTS `colors_used` VARCHAR(255) COMMENT 'JSON array of color codes' AFTER `design_notes`,
ADD COLUMN IF NOT EXISTS `tools_used` VARCHAR(255) COMMENT 'Tools used for design' AFTER `colors_used`,
ADD COLUMN IF NOT EXISTS `reviewer_id` INT AFTER `tools_used`,
ADD COLUMN IF NOT EXISTS `reviewed_at` TIMESTAMP NULL DEFAULT NULL AFTER `reviewer_id`;

-- Insert sample design challenges if none exist
INSERT INTO `design_challenges` (`title`, `description`, `difficulty`, `example_image`, `criteria`, `requirements`, `time_limit`, `category_id`, `is_active`, `max_score`) 
SELECT 'Landing Page Design', 'Create a landing page for a fictional tech startup', 'intermediate', 'examples/landing-page.jpg', 
  'Clean layout, clear call to action, responsive design, modern aesthetic', 
  'Must include: Hero section, Features section, About Us, Contact form', 
  60, 1, 1, 100
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `design_challenges` WHERE `title` = 'Landing Page Design');

INSERT INTO `design_challenges` (`title`, `description`, `difficulty`, `example_image`, `criteria`, `requirements`, `time_limit`, `category_id`, `is_active`, `max_score`) 
SELECT 'Mobile App Login Screen', 'Design a login screen for a mobile banking app', 'beginner', 'examples/login-screen.jpg',
  'Intuitive layout, security features, accessibility considerations, clean typography',
  'Must include: Username/password fields, biometric option, forgot password link, signup option',
  45, 2, 1, 100
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `design_challenges` WHERE `title` = 'Mobile App Login Screen');

INSERT INTO `design_challenges` (`title`, `description`, `difficulty`, `example_image`, `criteria`, `requirements`, `time_limit`, `category_id`, `is_active`, `max_score`) 
SELECT 'Company Logo Design', 'Create a logo for TechTutor learning platform', 'advanced', 'examples/logo-design.jpg',
  'Memorable, scalable, appropriate for education tech, works in color and B&W',
  'Must represent: Learning, technology, modern education. Include logo mark and wordmark versions.',
  90, 3, 1, 100
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `design_challenges` WHERE `title` = 'Company Logo Design');

-- Insert new badges for different challenge types
INSERT INTO `badges` (`badge_name`, `badge_image`) VALUES
('UI Novice', 'ui/ui_novice.png'),
('UI Designer', 'ui/ui_designer.png'),
('UI Master', 'ui/ui_master.png'),
('Web Design Star', 'ui/web_design_star.png'),
('Mobile Design Guru', 'ui/mobile_design_guru.png'),
('Logo Design Expert', 'ui/logo_design_expert.png')
ON DUPLICATE KEY UPDATE
badge_image = VALUES(badge_image);

-- Add tags table for categorizing challenges
CREATE TABLE IF NOT EXISTS `challenge_tags` (
    `tag_id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `color` VARCHAR(7) DEFAULT '#007bff'
);

-- Insert default tags
INSERT INTO `challenge_tags` (`name`, `color`) VALUES
('Beginner', '#28a745'),
('Intermediate', '#ffc107'),
('Advanced', '#dc3545'),
('Frontend', '#007bff'),
('Backend', '#6f42c1'),
('Security', '#fd7e14'),
('Database', '#20c997')
ON DUPLICATE KEY UPDATE
color = VALUES(color);

-- Create challenge-tag mapping table
CREATE TABLE IF NOT EXISTS `challenge_tag_map` (
    `map_id` INT AUTO_INCREMENT PRIMARY KEY,
    `challenge_id` INT NOT NULL,
    `tag_id` INT NOT NULL,
    UNIQUE KEY `unique_challenge_tag` (`challenge_id`, `tag_id`)
);

-- Create comprehensive view for challenge information
CREATE OR REPLACE VIEW `challenge_details_view` AS
SELECT 
    cx.challenge_id,
    cx.challenge_type,
    cx.xp_value,
    CASE 
        WHEN cx.challenge_type = 'Coding' THEN 
            (SELECT title FROM design_challenges WHERE id = cx.challenge_id)
        WHEN cx.challenge_type = 'UI' THEN
            (SELECT title FROM design_challenges WHERE id = cx.challenge_id)
        WHEN cx.challenge_type = 'Networking' THEN
            CASE cx.challenge_id
                WHEN 12 THEN 'Basic Network Setup'
                WHEN 13 THEN 'Internet Connection'
                WHEN 14 THEN 'Office Network with Switch'
                WHEN 21 THEN 'Subnet Configuration'
                WHEN 22 THEN 'Firewall Setup'
                WHEN 23 THEN 'VPN Configuration'
                ELSE 'Unknown Networking Challenge'
            END
        ELSE 'Unknown Challenge'
    END AS challenge_name,
    CASE 
        WHEN cx.challenge_type = 'Coding' THEN 
            (SELECT difficulty FROM design_challenges WHERE id = cx.challenge_id)
        WHEN cx.challenge_type = 'UI' THEN
            (SELECT difficulty FROM design_challenges WHERE id = cx.challenge_id)
        WHEN cx.challenge_type = 'Networking' THEN
            CASE cx.challenge_id
                WHEN 12 THEN 'beginner'
                WHEN 13 THEN 'intermediate'
                WHEN 14 THEN 'advanced'
                WHEN 21 THEN 'intermediate'
                WHEN 22 THEN 'advanced'
                WHEN 23 THEN 'advanced'
                ELSE 'unknown'
            END
        ELSE 'unknown'
    END AS difficulty
FROM 
    challenge_xp cx;
