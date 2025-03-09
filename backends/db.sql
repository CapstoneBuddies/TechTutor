CREATE TABLE IF NOT EXISTS `users` (
	`uid` INT PRIMARY KEY AUTO_INCREMENT,
	`email` VARCHAR(255) UNIQUE NOT NULL,
	`password` VARCHAR(255) NOT NULL,
	`is_verified` BOOLEAN NOT NULL DEFAULT FALSE,
	`status` BOOLEAN NOT NULL DEFAULT TRUE,
	`role` ENUM('TECHGURU', 'TECHKID', 'ADMIN') NOT NULL,
	`created_on` TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
    `last_login` TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS `user_details` (
    `user_id` INT UNIQUE,
    `first_name` VARCHAR(255) NOT NULL,
    `last_name` VARCHAR(255) NOT NULL,
    `profile_picture` VARCHAR(255) DEFAULT 'default.jpg',
    `address` TEXT NULL,
    `contact_number` VARCHAR(16) NULL,
    FOREIGN KEY (user_id) REFERENCES users(uid)
);

CREATE TABLE IF NOT EXISTS `login_tokens` (
    `token_id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `verification_code` VARCHAR(6) NULL,
    `type` ENUM('remember_me', 'email_verification') NOT NULL,
    `remember_expiration_date` DATETIME NOT NULL,
    `verification_expiration_date` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`uid`) 
    ON DELETE CASCADE,
    UNIQUE (`user_id`,`token`)
);

CREATE TABLE IF NOT EXISTS `course` (
    `course_id` INT PRIMARY KEY AUTO_INCREMENT,
    `course_name` VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS `subject` (
    `subject_id` INT PRIMARY KEY AUTO_INCREMENT,
    `course_id` INT,
    `subject_name` VARCHAR(255) NOT NULL,
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
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `is_free` BOOLEAN NOT NULL DEFAULT TRUE,
    `price` FLOAT(10,2) NULL,
    `thumbnail` VARCHAR(255),
    FOREIGN KEY (subject_id) REFERENCES subject(subject_id),
    FOREIGN KEY (tutor_id) REFERENCES users(uid)
);

CREATE TABLE IF NOT EXISTS `class_assignment` (
    `class_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    FOREIGN KEY (class_id) REFERENCES class(class_id),
    FOREIGN KEY (student_id) REFERENCES users(uid)
);

CREATE TABLE IF NOT EXISTS `meetings` (
    `meeting_id` INT PRIMARY KEY AUTO_INCREMENT,
    `meeting_uid` VARCHAR(50) UNIQUE NOT NULL,
    `meeting_name` VARCHAR(255) NOT NULL,
    `student_pw` VARCHAR(50) NOT NULL,
    `tutor_pw` VARCHAR(50) NOT NULL,
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

CREATE TABLE IF NOT EXISTS `notifcation` (
    `notif_id` INT PRIMARY KEY AUTO_INCREMENT,
    `notif_type` VARCHAR(50) NOT NULL,
    `creator` INT NOT NULL,
    `notif_title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `is_blast` BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (creator) REFERENCES users(uid)
);

CREATE TABLE IF NOT EXISTS `transactions` (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    merchant_id VARCHAR(255) NOT NULL,
    reference_number VARCHAR(255) NOT NULL,
    amount FLOAT(100,2) NOT NULL,
    transaction_date TIMESTAMP NOT NULL,
    status ENUM('Completed', 'Pending', 'Failed') NOT NULL,
    transaction_type ENUM('Deposit', 'Withdrawal') NOT NULL,
    sender_account VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    payment_method ENUM('Paypal', 'Credit Card', 'Bank Transfer') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(uid)
);

-- Indexing
CREATE INDEX idx_status ON users(status);
CREATE INDEX idx_first_name_last_name ON user_details(first_name, last_name);
CREATE INDEX idx_class_name ON class(class_name);
CREATE INDEX idx_meeting_uid ON meetings(meeting_uid);
CREATE INDEX idx_meetin_name ON meetings(meeting_name);
CREATE INDEX idx_file_uuid ON file_management(file_uuid);
CREATE INDEX idx_notif_header ON notifcation(notif_type, notif_title);
CREATE INDEX idx_reference_number ON transactions(reference_number);
CREATE INDEX idx_token ON login_tokens (token);


-- Adding Information
INSERT INTO `course`(`course_name`) VALUES('Computer Programming'), ('Computer Networking'), ('Graphics Design');
INSERT INTO `subject`(`course_id`,`subject_name`) VALUES (1,'Python Programming'), (2,'Networking'), (3,'UI/UX Designing');

-- Adding Sample User logins (default password is Abc123!!)
INSERT INTO `users`(`email`,`password`,`role`,`is_verified`) VALUES 
('tutor@test.com','$2y$10$FwM//r8Nn2GUWpHSBMv0RuYxw7oBScsxjf.cYlnUuq1V2KcQkyM3.','TECHGURU',1),
('student@test.com','$2y$10$FwM//r8Nn2GUWpHSBMv0RuYxw7oBScsxjf.cYlnUuq1V2KcQkyM3.','TECHKID',1),
('admin@test.com','$2y$10$FwM//r8Nn2GUWpHSBMv0RuYxw7oBScsxjf.cYlnUuq1V2KcQkyM3.','ADMIN',1);

INSERT INTO `user_details`(`user_id`,`first_name`,`last_name`) VALUES 
(1,'Test','Tutor'), (2,'Test','Student'), (3,'Test','Admin');