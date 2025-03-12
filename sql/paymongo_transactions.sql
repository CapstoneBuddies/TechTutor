CREATE TABLE IF NOT EXISTS `paymongo_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `payment_intent_id` VARCHAR(255) NOT NULL,
    `payment_method_id` VARCHAR(255),
    `amount` DECIMAL(10,2) NOT NULL,
    `currency` VARCHAR(3) DEFAULT 'PHP',
    `status` ENUM('pending', 'processing', 'succeeded', 'failed') DEFAULT 'pending',
    `payment_method_type` VARCHAR(50), -- card, gcash, grab_pay, paymaya
    `description` TEXT,
    `metadata` JSON,
    `error_message` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    INDEX `idx_payment_intent` (`payment_intent_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
