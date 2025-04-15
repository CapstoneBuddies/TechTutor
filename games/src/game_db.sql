-- Create the database
CREATE DATABASE game_db;

-- Use the database
USE game_db;

-- Table to store user information (optional, for multi-user support)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table to store game history
CREATE TABLE game_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, -- Assuming you have a user system
    challenge_name VARCHAR(255) NOT NULL,
    result ENUM('Solved', 'Not Solved') NOT NULL,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Table to store badges earned by users
CREATE TABLE badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    badge_name VARCHAR(100) NOT NULL,
    badge_image LONGBLOB, -- Column to store the image of the badge
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table to store the last saved state of a game
CREATE TABLE saved_games (
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
(1, 0, 'Newbie', 'badges/level1.png'),
(2, 100, 'Junior Coder', 'badges/level2.png'),
(3, 250, 'Debug Detective', 'badges/level3.png'),
(4, 500, 'Script Sorcerer', 'badges/level4.png'),
(5, 1000, 'Code Crusader', 'badges/level5.png'),
(6, 2000, 'Algorithm Ace', 'badges/level6.png'),
(7, 3500, 'Data Dynamo', 'badges/level7.png'),
(8, 5500, 'System Sage', 'badges/level8.png'),
(9, 8000, 'Network Navigator', 'badges/level9.png'),
(10, 12000, 'Cyber Sentinel', 'badges/level10.png'),
(11, 16000, 'Cloud Conqueror', 'badges/level11.png'),
(12, 20000, 'API Architect', 'badges/level12.png'),
(13, 25000, 'Full-Stack Fanatic', 'badges/level13.png'),
(14, 30000, 'DevOps Dynamo', 'badges/level14.png'),
(15, 40000, 'Machine Learning Maestro', 'badges/level15.png'),
(16, 50000, 'Artificial Intelligence Architect', 'badges/level16.png'),
(17, 65000, 'Blockchain Baron', 'badges/level17.png'),
(18, 80000, 'Quantum Quoder', 'badges/level18.png'),
(19, 100000, 'Tech Titan', 'badges/level19.png'),
(20, 125000, 'Legendary Innovator', 'badges/level20.png');

-- Update challenge XP values (to be assigned when a challenge is completed)
CREATE TABLE IF NOT EXISTS challenge_xp (
    challenge_id INT PRIMARY KEY,
    xp_value INT NOT NULL DEFAULT 10,
    challenge_type VARCHAR(50) NOT NULL
);

-- Insert default XP values for each challenge
INSERT INTO challenge_xp (challenge_id, xp_value, challenge_type) VALUES
(1, 10, 'Coding'),   -- Hello World (easiest)
(2, 20, 'Coding'),   -- Factorial 
(3, 15, 'Coding'),   -- String Reversal
(4, 25, 'Coding'),   -- Palindrome
(5, 30, 'Coding'),   -- FizzBuzz
(6, 20, 'Coding'),   -- Array Sum
(7, 35, 'Coding'),   -- Prime Number
(8, 40, 'Coding'),   -- Max Value
(9, 45, 'Coding'),   -- Password Validator
(10, 50, 'Coding'),  -- Caesar Cipher
(11, 40, 'Coding'),  -- Anagram Checker
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