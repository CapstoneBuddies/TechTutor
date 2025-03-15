CREATE TABLE IF NOT EXISTS `users` (
    `uid` INT PRIMARY KEY AUTO_INCREMENT,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(255) NOT NULL,
    `last_name` VARCHAR(255) NOT NULL,
    `profile_picture` VARCHAR(255) DEFAULT 'default.jpg',
    `address` TEXT NULL,
    `contact_number` VARCHAR(16) NULL,
    `rating` INT,
    `is_verified` BOOLEAN NOT NULL DEFAULT FALSE,
    `status` BOOLEAN NOT NULL DEFAULT TRUE,
    `role` ENUM('TECHGURU', 'TECHKID', 'ADMIN') NOT NULL DEFAULT 'TECHKID',
    `created_on` TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
    `last_login` TIMESTAMP NULL
);
CREATE TABLE IF NOT EXISTS `ratings` (
    `rating_id` INT PRIMARY KEY AUTO_INCREMENT,
    `student_id` INT NOT NULL,
    `tutor_id` INT NOT NULL,
    `rating` INT CHECK (`rating` BETWEEN 1 AND 5), -- 1 (worst) to 5 (best)
    `comment` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES users(uid) ON DELETE CASCADE,
    FOREIGN KEY (`tutor_id`) REFERENCES users(uid) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS `login_tokens` (
    `token_id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `verification_code` VARCHAR(6) NULL,
    `type` ENUM('remember_me', 'email_verification','reset') NOT NULL,
    `expiration_date` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`uid`) ON DELETE CASCADE,
    UNIQUE (`user_id`,`token`)
);

CREATE TABLE IF NOT EXISTS `course` (
    `course_id` INT PRIMARY KEY AUTO_INCREMENT,
    `course_name` VARCHAR(255) NOT NULL,
    `course_desc` TEXT
);

CREATE TABLE IF NOT EXISTS `subject` (
    `subject_id` INT PRIMARY KEY AUTO_INCREMENT,
    `course_id` INT,
    `subject_name` VARCHAR(255) NOT NULL,
    `subject_desc` TEXT,
    `image` VARCHAR(255) DEFAULT 'defaul.jpg',
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (course_id) REFERENCES course(course_id)
);

CREATE TABLE IF NOT EXISTS `class` (
    `class_id` INT PRIMARY KEY AUTO_INCREMENT,
    `subject_id` INT,
    `class_name` VARCHAR(255) NOT NULL,
    `class_desc` TEXT NOT NULL,
    `tutor_id` INT NOT NULL,
    `start_date` DATETIME NOT NULL,
    `end_date` DATETIME NOT NULL,
    `class_size` INT NULL,
    `status` ENUM('active', 'restricted', 'completed', 'pending') NOT NULL DEFAULT 'active',
    `is_free` BOOLEAN NOT NULL DEFAULT TRUE,
    `price` FLOAT(10,2) NULL,
    `thumbnail` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subject(subject_id),
    FOREIGN KEY (tutor_id) REFERENCES users(uid)
);

CREATE TABLE IF NOT EXISTS `class_schedule` (
    `schedule_id` INT PRIMARY KEY AUTO_INCREMENT,
    `class_id` INT NOT NULL,
    `user_id` INT NOT NULL,  -- Can be student or tutor
    `role` ENUM('TUTOR', 'STUDENT') NOT NULL,
    `session_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `status` ENUM('pending', 'confirmed', 'completed', 'canceled') DEFAULT 'pending',
    FOREIGN KEY (class_id) REFERENCES class(class_id),
    FOREIGN KEY (user_id) REFERENCES users(uid)
);
CREATE TABLE IF NOT EXISTS `enrollments` (
    `enrollment_id` INT PRIMARY KEY AUTO_INCREMENT,
    `class_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `enrollment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('active', 'completed', 'dropped') NOT NULL DEFAULT 'active',
    FOREIGN KEY (class_id) REFERENCES class(class_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(uid) ON DELETE CASCADE,
    UNIQUE KEY `unique_enrollment` (`class_id`, `student_id`)
);
CREATE TABLE IF NOT EXISTS `meetings` (
    `meeting_id` INT PRIMARY KEY AUTO_INCREMENT,
    `meeting_uid` VARCHAR(50) UNIQUE NOT NULL,
    `meeting_name` VARCHAR(255) NOT NULL,
    `createtime` BIGINT NOT NULL,
    `is_running` BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS `file_management` (
    `file_id` INT PRIMARY KEY AUTO_INCREMENT,
    `class_id` INT NULL,
    `user_id` INT NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_uuid` VARCHAR(255) UNIQUE NOT NULL,
    `upload_time` TIMESTAMP NOT NULL,
    FOREIGN KEY (class_id) REFERENCES class(class_id),
    FOREIGN KEY (user_id) REFERENCES users(uid)
);

CREATE TABLE IF NOT EXISTS `certificate` (
    `cert_id` INT PRIMARY KEY AUTO_INCREMENT,
    `cert_uuid` VARCHAR(255) UNIQUE NOT NULL,
    `recipient` INT NOT NULL,
    `award` VARCHAR(255) NOT NULL,
    `donor` INT NOT NULL,
    `issue_date` DATE NOT NULL,
    FOREIGN KEY (recipient) REFERENCES users(uid),
    FOREIGN KEY (donor) REFERENCES users(uid)
);

CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    recipient_id INT,
    recipient_role ENUM('ADMIN', 'TECHGURU', 'TECHKID', 'ALL') NOT NULL,
    class_id INT,
    message TEXT NOT NULL,
    link VARCHAR(255),
    icon VARCHAR(50) NOT NULL,
    icon_color VARCHAR(50) NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recipient_id) REFERENCES users(uid),
    FOREIGN KEY (class_id) REFERENCES class(class_id)
);
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
);
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
);

-- Indexing for optimization
CREATE INDEX idx_status ON users(status);
CREATE INDEX idx_email ON users(email);
CREATE INDEX idx_class_name ON class(class_name);
CREATE INDEX idx_meeting_uid ON meetings(meeting_uid);
CREATE INDEX idx_file_uuid ON file_management(file_uuid);
CREATE INDEX idx_reference_number ON transactions(reference_number);
CREATE INDEX idx_token ON login_tokens (token);
CREATE INDEX idx_class_status ON class(status);
CREATE INDEX idx_class_tutor ON class(tutor_id);
CREATE INDEX idx_class_subject ON class(subject_id);
CREATE INDEX idx_enrollment_class ON enrollments(class_id);
CREATE INDEX idx_enrollment_student ON enrollments(student_id);
CREATE INDEX idx_enrollment_status ON enrollments(status);

-- Sample Data
INSERT INTO `users`(`email`,`password`,`role`,`is_verified`, `first_name`, `last_name`) VALUES 
('tutor@test.com','$2y$10$FwM//r8Nn2GUWpHSBMv0RuYxw7oBScsxjf.cYlnUuq1V2KcQkyM3.','TECHGURU',1, 'Test', 'Tutor'),
('student@test.com','$2y$10$FwM//r8Nn2GUWpHSBMv0RuYxw7oBScsxjf.cYlnUuq1V2KcQkyM3.','TECHKID',1, 'Test', 'Student'),
('admin@test.com','$2y$10$FwM//r8Nn2GUWpHSBMv0RuYxw7oBScsxjf.cYlnUuq1V2KcQkyM3.','ADMIN',1, 'Test', 'Admin');
INSERT INTO `course`(`course_name`,`course_desc`) VALUES('Computer Programming','Learn how to write, debug, and develop software applications using various programming languages and methodologies.'), ('Computer Networking','Understand the principles of networking, including setup, security, and cloud integration to build robust IT infrastructures.'), ('Graphics Design','Master digital design, UI/UX, and animation techniques to create stunning visuals and interactive experiences.');
INSERT INTO `subject`(`course_id`,`subject_name`,`subject_desc`) VALUES 
(1,'Python Programming', 'Learn the fundamentals of Python, including syntax, data structures, and object-oriented programming.'), 
(1,'Java Development', 'Covers Java programming basics, OOP principles, and application development.'), 
(1,'C++ for Beginners', 'Introduces students to C++ programming, focusing on memory management, data structures, and algorithms.'), 
(2,'Network Fundamentals', 'Explores basic networking concepts, protocols, and network topologies.'), 
(2,'Routing and Switching', 'Focuses on configuring routers and switches, subnetting, and VLANs.'), 
(2,'Network Security', 'Covers firewalls, intrusion detection systems, and encryption techniques for secure networking.'), 
(3,'UI/UX Designing', 'Teaches principles of user interface and user experience design for web and mobile applications.'),
(3,'Adobe Photoshop Essentials', 'Covers photo editing, digital painting, and design techniques using Adobe Photoshop.'), 
(3,'Vector Illustration with Adobe Illustrator', 'Focuses on creating digital illustrations, logos, and typography with Illustrator.');