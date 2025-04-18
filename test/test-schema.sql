-- Test database schema for unit testing
-- This is a simplified version of the main schema with only the necessary tables for testing

-- Create users table
CREATE TABLE IF NOT EXISTS `users` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `gender` enum('M','F','U') DEFAULT 'U',
  `profile_picture` varchar(255) DEFAULT 'default.jpg',
  `address` text DEFAULT NULL,
  `contact_number` varchar(16) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `rating_count` int(11) DEFAULT 0,
  `token_balance` double(5,2) NULL DEFAULT 0.00,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `role` enum('TECHGURU','TECHKID','ADMIN') NOT NULL DEFAULT 'TECHKID',
  `created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `email` (`email`)
);

-- Create transactions table
CREATE TABLE IF NOT EXISTS `transactions` (
  `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `payment_intent_id` varchar(255) NOT NULL,
  `payment_method_id` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'PHP',
  `status` enum('pending','processing','succeeded','failed') DEFAULT 'pending',
  `payment_method_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `transaction_type` varchar(50) DEFAULT 'token',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `error_message` text DEFAULT NULL, 
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`transaction_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE
);

-- Insert test users
INSERT INTO `users` (`email`, `password`, `first_name`, `last_name`, `role`, `is_verified`, `status`) VALUES
('tutor@test.com', '$2y$10$4JaK3Qj7zqwZU6PBNIB3ZO5oORZo7a1MdkCWjwVz.OFGWgfRmKtCm', 'Tech', 'Guru', 'TECHGURU', 1, 1),
('student@test.com', '$2y$10$4JaK3Qj7zqwZU6PBNIB3ZO5oORZo7a1MdkCWjwVz.OFGWgfRmKtCm', 'Tech', 'Kid', 'TECHKID', 1, 1),
('admin@test.com', '$2y$10$4JaK3Qj7zqwZU6PBNIB3ZO5oORZo7a1MdkCWjwVz.OFGWgfRmKtCm', 'Admin', 'User', 'ADMIN', 1, 1);