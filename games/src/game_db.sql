-- Create the database
CREATE DATABASE game_db;

-- Use the database
USE game_db;

-- Table to store user information (optional, for multi-user support)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table to store game history
CREATE TABLE IF NOT EXISTS game_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, -- Assuming you have a user system
    challenge_name VARCHAR(255) NOT NULL,
    result ENUM('Solved', 'Not Solved') NOT NULL,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Table to store badges earned by users
CREATE TABLE IF NOT EXISTS badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    badge_name VARCHAR(100) NOT NULL,
    badge_image VARCHAR(255), -- Column to store the image of the badge
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table to store the last saved state of a game
CREATE TABLE IF NOT EXISTS saved_games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    score INT NOT NULL DEFAULT 0,
    incorrect_streak INT NOT NULL DEFAULT 0,
    current_question VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS design_challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    difficulty VARCHAR(50) NOT NULL,
    example_image VARCHAR(255),
    criteria TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS design_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    challenge_id INT,
    image_path VARCHAR(255) NOT NULL,
    feedback TEXT,
    score INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (challenge_id) REFERENCES design_challenges(id)
);
-- Add user_xp table to track experience points and levels
CREATE TABLE IF NOT EXISTS user_xp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    xp INT NOT NULL DEFAULT 0,
    level INT NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add XP values to each challenge completion in game_history
ALTER TABLE game_history ADD COLUMN xp_earned INT DEFAULT 0;

-- Define levels and XP thresholds
-- This table defines how much XP is needed for each level
CREATE TABLE IF NOT EXISTS level_definitions (
    level INT PRIMARY KEY,
    xp_required INT NOT NULL,
    badge_name VARCHAR(100),
    badge_image VARCHAR(255)
);

-- Insert default level definitions
-- Each level requires progressively more XP
INSERT INTO level_definitions (level, xp_required, badge_name, badge_image) VALUES
(1, 0, 'Newbie', 'level1.png'),
(2, 100, 'Junior Coder', 'level2.png'),
(3, 250, 'Debug Detective', 'level3.png'),
(4, 500, 'Script Sorcerer', 'level4.png'),
(5, 1000, 'Code Crusader', 'level5.png'),
(6, 2000, 'Algorithm Ace', 'level6.png'),
(7, 3500, 'Data Dynamo', 'level7.png'),
(8, 5500, 'System Sage', 'level8.png'),
(9, 8000, 'Network Navigator', 'level9.png'),
(10, 12000, 'Cyber Sentinel', 'level10.png'),
(11, 16000, 'Cloud Conqueror', 'level11.png'),
(12, 20000, 'API Architect', 'level12.png'),
(13, 25000, 'Full-Stack Fanatic', 'level13.png'),
(14, 30000, 'DevOps Dynamo', 'level14.png'),
(15, 40000, 'Machine Learning Maestro', 'level15.png'),
(16, 50000, 'Artificial Intelligence Architect', 'level16.png'),
(17, 65000, 'Blockchain Baron', 'level17.png'),
(18, 80000, 'Quantum Quoder', 'level18.png'),
(19, 100000, 'Tech Titan', 'level19.png'),
(20, 125000, 'Legendary Innovator', 'level20.png');

-- Update challenge XP values (to be assigned when a challenge is completed)
CREATE TABLE IF NOT EXISTS challenge_xp (
    challenge_id INT PRIMARY KEY,
    xp_value INT NOT NULL DEFAULT 10,
    challenge_type VARCHAR(50) NOT NULL
);

-- Insert default XP values for each challenge
INSERT INTO challenge_xp (challenge_id, xp_value, challenge_type) VALUES
(1, 10, 'programming'),   -- Hello World (easiest)
(2, 20, 'programming'),   -- Factorial 
(3, 15, 'programming'),   -- String Reversal
(4, 25, 'programming'),   -- Palindrome
(5, 30, 'programming'),   -- FizzBuzz
(6, 20, 'programming'),   -- Array Sum
(7, 35, 'programming'),   -- Prime Number
(8, 40, 'programming'),   -- Max Value
(9, 45, 'programming'),   -- Password Validator
(10, 50, 'programming'),  -- Caesar Cipher
(11, 40, 'programming'),  -- Anagram Checker
(12, 50, 'Networking'),  -- Level 1
(13, 75, 'Networking'),  -- Level 2
(14, 90, 'Networking');  -- Level 3

-- Create a view to easily get user levels and progress
CREATE OR REPLACE VIEW user_level_view AS
SELECT 
    u.id AS user_id,
    u.username,
    u.email,
    COALESCE(ux.xp, 0) AS total_xp,
    COALESCE(ux.level, 1) AS current_level,
    ld.xp_required AS current_level_xp,
    (SELECT xp_required FROM level_definitions WHERE level = COALESCE(ux.level, 1) + 1) AS next_level_xp,
    CASE 
        WHEN (SELECT xp_required FROM level_definitions WHERE level = COALESCE(ux.level, 1) + 1) IS NULL THEN 100
        ELSE 
            ROUND(
                (COALESCE(ux.xp, 0) - COALESCE(ld.xp_required, 0)) * 100.0 / 
                ((SELECT xp_required FROM level_definitions WHERE level = COALESCE(ux.level, 1) + 1) - COALESCE(ld.xp_required, 0))
            )
    END AS level_progress_percent
FROM 
    users u
LEFT JOIN 
    user_xp ux ON u.id = ux.user_id
LEFT JOIN 
    level_definitions ld ON COALESCE(ux.level, 1) = ld.level; 

UPDATE level_definitions
SET badge_image = REPLACE(badge_image, 'assets/badges/', '')
WHERE badge_image LIKE 'assets/badges/%';

ALTER TABLE `badges`
DROP COLUMN `badge_image`,
ADD COLUMN `badge_image` VARCHAR(255);
UPDATE badges
SET badge_image = CONCAT(
    REPLACE(LOWER(badge_name), ' ', '_'),
    '.png'
);
update badges set badge_name = 'Hello World' WHERE badge_name = 'Hello World Badge';
UPDATE badges
SET badge_image = CONCAT('programming/',
    REPLACE(LOWER(badge_name), ' ', '_'),
    '.png'
);

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
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
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
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
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
MODIFY COLUMN `challenge_type` VARCHAR(50) NOT NULL DEFAULT 'programming';

-- Update level_definitions with additional levels if needed
INSERT INTO `level_definitions` (level, xp_required, badge_name, badge_image) 
VALUES 
(21, 150000, 'Programming Novice', 'level21.png'),
(22, 175000, 'Programming Adept', 'level22.png'),
(23, 200000, 'Network Technician', 'level23.png'),
(24, 225000, 'Network Expert', 'level24.png'),
(25, 250000, 'UI Designer', 'level25.png')
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
(18, 25, 'programming'),   -- String Manipulation
(19, 35, 'programming'),   -- Array Operations
(20, 45, 'programming')    -- Object-Oriented Programming
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
        WHEN cx.challenge_type = 'programming' THEN 
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
        WHEN cx.challenge_type = 'programming' THEN 
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
DELIMITER $$

CREATE TRIGGER trg_insert_badge_from_challenge
AFTER INSERT ON game_challenges
FOR EACH ROW
BEGIN
    INSERT INTO game_badges (
        name,
        description,
        image_path,
        requirements,
        created_at
    ) VALUES (
        NEW.name,
        NEW.description,
        CONCAT(NEW.challenge_type, '/', REPLACE(LOWER(NEW.name), ' ', '_'), '.png'),
        JSON_OBJECT(
            'type', 'completion',
            'target', 'challenge',
            'name', NEW.name
        ),
        NOW()
    );
END$$

DELIMITER ;

UPDATE game_challenges SET badge_image = CONCAT(challenge_type,'/',REPLACE(LOWER(name), ' ', '_'),'.png')