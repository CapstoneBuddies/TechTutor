CREATE TABLE IF NOT EXISTS `transactions` (
    `transaction_id` VARCHAR(36) NOT NULL,
    `user_id` INT NOT NULL,
    `type` ENUM('PAYMENT', 'REFUND', 'DEPOSIT', 'WITHDRAWAL') NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `status` ENUM('COMPLETED', 'PENDING', 'FAILED') NOT NULL DEFAULT 'PENDING',
    `description` TEXT,
    `reference_number` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`transaction_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
