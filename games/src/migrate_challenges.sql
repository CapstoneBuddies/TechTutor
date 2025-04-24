-- SQL script to migrate challenges from old_challenges.php to the game_challenges table
-- This follows the schema defined in game_db.sql

-- Insert challenges into game_challenges table
INSERT INTO `game_challenges` 
(`challenge_id`, `name`, `challenge_type`, `difficulty_id`, `content`, `badge_name`, `badge_image`, `xp_value`, `is_active`) 
VALUES
-- Challenge 1: Hello World
(1, 'Hello World', 'programming', 
  (SELECT difficulty_id FROM game_difficulty_levels WHERE name = 'beginner' LIMIT 1),
  JSON_OBJECT(
    'starter_code', '// Write your PHP code here\n// Your goal is to print \'hello world\'\n\n// Example:\n// echo \'hello world\';',
    'expected_output', 'hello world',
    'languages', JSON_ARRAY('php', 'javascript', 'python')
  ),
  'Hello World', 'programming/hello_world.png', 10, 1),

-- Challenge 2: Factorial Calculator
(2, 'Factorial Calculator', 'programming', 
  (SELECT difficulty_id FROM game_difficulty_levels WHERE name = 'beginner' LIMIT 1),
  JSON_OBJECT(
    'starter_code', '// Write your PHP code here\nfunction factorial($n) {\n    // Your code here\n}\n\n// Test the function\n$n = 5;\necho factorial($n);',
    'expected_output', '120',
    'languages', JSON_ARRAY('php', 'javascript', 'python')
  ),
  'Factorial Master', 'programming/factorial.png', 20, 1),

-- Challenge 3: String Reversal
(3, 'String Reversal', 'programming', 
  (SELECT difficulty_id FROM game_difficulty_levels WHERE name = 'beginner' LIMIT 1),
  JSON_OBJECT(
    'starter_code', '// Write your PHP code here\nfunction reverseString($str) {\n    // Your code here\n}\n\n// Test the function\n$str = \'hello\';\necho reverseString($str);',
    'expected_output', 'olleh',
    'languages', JSON_ARRAY('php', 'javascript', 'python')
  ),
  'String Wizard', 'programming/string_reversal.png', 15, 1),

-- Challenge 4: Palindrome Checker
(4, 'Palindrome Checker', 'programming', 
  (SELECT difficulty_id FROM game_difficulty_levels WHERE name = 'intermediate' LIMIT 1),
  JSON_OBJECT(
    'starter_code', '// Write a function to check if a string is a palindrome\nfunction isPalindrome($str) {\n    // Your code here\n}\n\n// Test with this word\n$word = \'racecar\';\necho isPalindrome($word) ? \'true\' : \'false\';',
    'expected_output', 'true',
    'languages', JSON_ARRAY('php', 'javascript', 'python')
  ),
  'Palindrome Detective', 'programming/palindrome.png', 25, 1),

-- Challenge 5: FizzBuzz Challenge
(5, 'FizzBuzz Challenge', 'programming', 
  (SELECT difficulty_id FROM game_difficulty_levels WHERE name = 'intermediate' LIMIT 1),
  JSON_OBJECT(
    'starter_code', '// Implement the FizzBuzz challenge for numbers 1-15\n\n// Your code here',
    'expected_output', '1\n2\nFizz\n4\nBuzz\nFizz\n7\n8\nFizz\nBuzz\n11\nFizz\n13\n14\nFizzBuzz',
    'languages', JSON_ARRAY('php', 'javascript', 'python')
  ),
  'FizzBuzz Champion', 'programming/fizzbuzz.png', 30, 1),

-- Challenge 6: Array Sum
(6, 'Array Sum', 'programming', 
  (SELECT difficulty_id FROM game_difficulty_levels WHERE name = 'beginner' LIMIT 1),
  JSON_OBJECT(
    'starter_code', '// Create a function to sum all elements in an array\nfunction arraySum($arr) {\n    // Your code here\n}\n\n// Test with this array\n$numbers = [5, 10, 15, 20, 25];\necho arraySum($numbers);',
    'expected_output', '75',
    'languages', JSON_ARRAY('php', 'javascript', 'python')
  ),
  'Array Master', 'programming/array_sum.png', 20, 1),

-- Challenge 7: Prime Number Checker
(7, 'Prime Number Checker', 'programming', 
  (SELECT difficulty_id FROM game_difficulty_levels WHERE name = 'intermediate' LIMIT 1),
  JSON_OBJECT(
    'starter_code', '// Write a function to check if a number is prime\nfunction isPrime($number) {\n    // Your code here\n}\n\n// Test with this number\n$num = 29;\necho isPrime($num) ? \'true\' : \'false\';',
    'expected_output', 'true',
    'languages', JSON_ARRAY('php', 'javascript', 'python')
  ),
  'Prime Detective', 'programming/prime.png', 35, 1),

-- Challenge 8: Find Maximum Value
(8, 'Find Maximum Value', 'programming', 
  (SELECT difficulty_id FROM game_difficulty_levels WHERE name = 'advanced' LIMIT 1),
  JSON_OBJECT(
    'starter_code', '// Find the maximum value in a multi-dimensional array\nfunction findMaxValue($arr) {\n    // Your code here\n}\n\n// Test with this array\n$nestedArray = [[1, 2, 3], [4, 5], [6, 7, 8, 9]];\necho findMaxValue($nestedArray);',
    'expected_output', '9',
    'languages', JSON_ARRAY('php', 'javascript', 'python')
  ),
  'Maximum Explorer', 'programming/max_value.png', 40, 1),

-- Challenge 9: Password Validator
(9, 'Password Validator', 'programming', 
  (SELECT difficulty_id FROM game_difficulty_levels WHERE name = 'intermediate' LIMIT 1),
  JSON_OBJECT(
    'starter_code', '// Create a password validator function\n// It should return true if the password:\n// - Is at least 8 characters long\n// - Contains at least one uppercase letter\n// - Contains at least one number\nfunction isValidPassword($password) {\n    // Your code here\n}\n\n// Test with this password\n$pass = "Password123";\necho isValidPassword($pass) ? \'true\' : \'false\';',
    'expected_output', 'true',
    'languages', JSON_ARRAY('php', 'javascript', 'python')
  ),
  'Security Specialist', 'programming/password.png', 35, 1),

-- Challenge 10: Caesar Cipher
(10, 'Caesar Cipher', 'programming', 
  (SELECT difficulty_id FROM game_difficulty_levels WHERE name = 'advanced' LIMIT 1),
  JSON_OBJECT(
    'starter_code', '// Implement a Caesar Cipher with shift of 3\nfunction caesarCipher($text, $shift = 3) {\n    // Your code here\n}\n\n// Test with this text\n$message = "hello";\necho caesarCipher($message);',
    'expected_output', 'khoor',
    'languages', JSON_ARRAY('php', 'javascript', 'python')
  ),
  'Encryption Expert', 'programming/cipher.png', 50, 1),

-- Challenge 11: Anagram Checker
(11, 'Anagram Checker', 'programming', 
  (SELECT difficulty_id FROM game_difficulty_levels WHERE name = 'advanced' LIMIT 1),
  JSON_OBJECT(
    'starter_code', '// Create a function to check if two strings are anagrams\nfunction areAnagrams($str1, $str2) {\n    // Your code here\n}\n\n// Test with these words\n$word1 = "listen";\n$word2 = "silent";\necho areAnagrams($word1, $word2) ? \'true\' : \'false\';',
    'expected_output', 'true',
    'languages', JSON_ARRAY('php', 'javascript', 'python')
  ),
  'Anagram Detective', 'programming/anagram.png', 40, 1),

-- NETWORK CHALLENGES

-- Challenge 12: Basic Network Setup (Network Level 1)
(12, 'Basic Network Setup', 'networking', 
  (SELECT difficulty_id FROM game_difficulty_levels WHERE name = 'beginner' LIMIT 1),
  JSON_OBJECT(
    'description', 'Connect the client computers to the router to establish a basic home network.',
    'devices', JSON_OBJECT(
      'router1', JSON_OBJECT('type', 'router', 'x', 400, 'y', 200, 'ip', '192.168.1.1'),
      'laptop1', JSON_OBJECT('type', 'laptop', 'x', 200, 'y', 350, 'ip', '192.168.1.2'),
      'laptop2', JSON_OBJECT('type', 'laptop', 'x', 600, 'y', 350, 'ip', '192.168.1.3')
    ),
    'connections', JSON_ARRAY(
      JSON_OBJECT('source', 'laptop1', 'target', 'router1'),
      JSON_OBJECT('source', 'laptop2', 'target', 'router1')
    ),
    'points', 100
  ),
  'Network Novice', 'networking/basic_network.png', 100, 1),

-- Challenge 13: Internet Connection (Network Level 2)
(13, 'Internet Connection', 'networking', 
  (SELECT difficulty_id FROM game_difficulty_levels WHERE name = 'intermediate' LIMIT 1),
  JSON_OBJECT(
    'description', 'Connect the router to the modem to establish an internet connection for your network.',
    'devices', JSON_OBJECT(
      'modem', JSON_OBJECT('type', 'modem', 'x', 400, 'y', 100, 'ip', '10.0.0.1'),
      'router1', JSON_OBJECT('type', 'router', 'x', 400, 'y', 200, 'ip', '192.168.1.1'),
      'pc1', JSON_OBJECT('type', 'computer', 'x', 200, 'y', 300, 'ip', '192.168.1.2'),
      'pc2', JSON_OBJECT('type', 'computer', 'x', 600, 'y', 300, 'ip', '192.168.1.3'),
      'server', JSON_OBJECT('type', 'server', 'x', 400, 'y', 400, 'ip', '192.168.1.4')
    ),
    'connections', JSON_ARRAY(
      JSON_OBJECT('source', 'router1', 'target', 'modem'),
      JSON_OBJECT('source', 'pc1', 'target', 'router1'),
      JSON_OBJECT('source', 'pc2', 'target', 'router1'),
      JSON_OBJECT('source', 'server', 'target', 'router1')
    ),
    'points', 150
  ),
  'Internet Explorer', 'networking/internet_connection.png', 150, 1),

-- Challenge 14: Office Network with Switch (Network Level 3)
(14, 'Office Network with Switch', 'networking', 
  (SELECT difficulty_id FROM game_difficulty_levels WHERE name = 'advanced' LIMIT 1),
  JSON_OBJECT(
    'description', 'Create a small office network using a switch to connect multiple computers to a router.',
    'devices', JSON_OBJECT(
      'router1', JSON_OBJECT('type', 'router', 'x', 400, 'y', 100, 'ip', '192.168.1.1'),
      'switch1', JSON_OBJECT('type', 'switch', 'x', 400, 'y', 250, 'ip', ''),
      'pc1', JSON_OBJECT('type', 'computer', 'x', 200, 'y', 350, 'ip', '192.168.1.2'),
      'pc2', JSON_OBJECT('type', 'computer', 'x', 350, 'y', 350, 'ip', '192.168.1.3'),
      'pc3', JSON_OBJECT('type', 'computer', 'x', 450, 'y', 350, 'ip', '192.168.1.4'),
      'pc4', JSON_OBJECT('type', 'computer', 'x', 600, 'y', 350, 'ip', '192.168.1.5'),
      'printer', JSON_OBJECT('type', 'printer', 'x', 600, 'y', 200, 'ip', '192.168.1.6')
    ),
    'connections', JSON_ARRAY(
      JSON_OBJECT('source', 'switch1', 'target', 'router1'),
      JSON_OBJECT('source', 'pc1', 'target', 'switch1'),
      JSON_OBJECT('source', 'pc2', 'target', 'switch1'),
      JSON_OBJECT('source', 'pc3', 'target', 'switch1'),
      JSON_OBJECT('source', 'pc4', 'target', 'switch1'),
      JSON_OBJECT('source', 'printer', 'target', 'switch1')
    ),
    'points', 200
  ),
  'Network Pro', 'networking/office_network.png', 200, 1)
  
ON DUPLICATE KEY UPDATE 
  `name` = VALUES(`name`), 
  `challenge_type` = VALUES(`challenge_type`),
  `difficulty_id` = VALUES(`difficulty_id`),
  `content` = VALUES(`content`),
  `badge_name` = VALUES(`badge_name`), 
  `badge_image` = VALUES(`badge_image`),
  `xp_value` = VALUES(`xp_value`);

-- Also update the challenge_xp table for challenge XP tracking
INSERT INTO `challenge_xp` (`challenge_id`, `xp_value`, `challenge_type`) VALUES
(1, 10, 'Coding'),
(2, 20, 'Coding'),
(3, 15, 'Coding'),
(4, 25, 'Coding'),
(5, 30, 'Coding'),
(6, 20, 'Coding'),
(7, 35, 'Coding'),
(8, 40, 'Coding'),
(9, 35, 'Coding'),
(10, 50, 'Coding'),
(11, 40, 'Coding'),
-- Network challenges
(12, 100, 'Networking'),
(13, 150, 'Networking'),
(14, 200, 'Networking')
ON DUPLICATE KEY UPDATE `xp_value` = VALUES(`xp_value`), `challenge_type` = VALUES(`challenge_type`);

-- Make sure difficulty levels exist
INSERT IGNORE INTO `game_difficulty_levels` (`name`, `description`) VALUES
('beginner', 'Easy challenges suitable for beginners'),
('intermediate', 'Moderate difficulty challenges for learning'),
('advanced', 'Difficult challenges for experienced users');
