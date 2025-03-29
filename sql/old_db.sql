SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO"; 
START TRANSACTION;

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `status` enum('present','absent','late') NOT NULL DEFAULT 'present',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
);
CREATE TABLE `certificate` (
  `cert_id` int(11) NOT NULL,
  `cert_uuid` varchar(255) NOT NULL,
  `recipient` int(11) NOT NULL,
  `award` varchar(255) NOT NULL,
  `donor` int(11) NOT NULL,
  `issue_date` date NOT NULL
);
CREATE TABLE `class` (
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `class_name` varchar(255) NOT NULL,
  `class_desc` text NOT NULL,
  `tutor_id` int(11) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `class_size` int(11) DEFAULT NULL,
  `status` enum('active','restricted','completed','pending') NOT NULL DEFAULT 'active',
  `is_free` tinyint(1) NOT NULL DEFAULT 1,
  `price` float(10,2) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
);
CREATE TABLE `class_ratings` (
  `classRating_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
);
CREATE TABLE `class_schedule` (
  `schedule_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('pending','confirmed','completed','canceled') DEFAULT 'pending'
);
CREATE TABLE `course` (
  `course_id` int(11) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `course_desc` text DEFAULT NULL
);
CREATE TABLE `enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','completed','dropped','pending') NOT NULL DEFAULT 'active'
);
CREATE TABLE `file_access` (
  `access_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `granted_by` int(11) NOT NULL
);
CREATE TABLE `file_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
);
CREATE TABLE `file_category_mapping` (
  `file_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
);
CREATE TABLE `file_folders` (
  `folder_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `folder_name` varchar(255) NOT NULL,
  `parent_folder_id` int(11) DEFAULT NULL,
  `google_folder_id` varchar(255) NOT NULL,
  `visibility` enum('private','public','class_only','specific_users') DEFAULT 'private',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
);
CREATE TABLE `file_management` (
  `file_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `google_file_id` varchar(255) NOT NULL,
  `drive_link` text NOT NULL,
  `folder_id` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_personal` tinyint(1) DEFAULT 0,
  `is_visible` tinyint(1) DEFAULT 0,
  `upload_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
);
CREATE TABLE `file_permissions` (
  `permission_id` int(11) NOT NULL,
  `file_id` int(11) DEFAULT NULL,
  `folder_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `access_type` enum('view','edit','owner') DEFAULT 'view',
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `granted_by` int(11) NOT NULL
);
CREATE TABLE `file_requests` (
  `request_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `request_title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` datetime NOT NULL,
  `status` enum('pending','submitted','rejected','approved','expired') DEFAULT 'pending',
  `response_file_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
);
CREATE TABLE `file_tags` (
  `tag_id` int(11) NOT NULL,
  `tag_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
);
CREATE TABLE `file_tag_map` (
  `map_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
);
CREATE TABLE `file_upload_requests` (
  `request_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `tutor_id` int(11) NOT NULL,
  `request_title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` datetime NOT NULL,
  `status` enum('pending','completed','expired') DEFAULT 'pending',
  `file_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
);
CREATE TABLE `login_tokens` (
  `token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `verification_code` varchar(6) DEFAULT NULL,
  `type` enum('remember_me','email_verification','reset') NOT NULL,
  `expiration_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
);
CREATE TABLE `meetings` (
  `meeting_id` int(11) NOT NULL,
  `meeting_uid` varchar(50) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `meeting_name` varchar(255) NOT NULL,
  `attendee_pw` varchar(255) DEFAULT NULL,
  `moderator_pw` varchar(255) DEFAULT NULL,
  `is_running` tinyint(1) NOT NULL DEFAULT 1,
  `createtime` bigint(20) NOT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
);
CREATE TABLE `meeting_analytics` (
  `id` int(11) NOT NULL,
  `meeting_id` int(11) NOT NULL,
  `tutor_id` int(11) NOT NULL,
  `participant_count` int(11) DEFAULT 0,
  `duration` int(11) DEFAULT 0,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `recording_available` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
);
CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `recipient_id` int(11) DEFAULT NULL,
  `recipient_role` enum('ADMIN','TECHGURU','TECHKID','ALL') NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `icon` varchar(50) NOT NULL,
  `icon_color` varchar(50) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
);
CREATE TABLE `session_feedback` (
  `rating_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `tutor_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_archived` tinyint(1) NOT NULL DEFAULT 0
);
CREATE TABLE `subject` (
  `subject_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `subject_name` varchar(255) NOT NULL,
  `subject_desc` text DEFAULT NULL,
  `image` varchar(255) DEFAULT 'default.jpg',
  `is_active` tinyint(1) NOT NULL DEFAULT 1
);
CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `payment_intent_id` varchar(255) NOT NULL,
  `payment_method_id` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'PHP',
  `status` enum('pending','processing','succeeded','failed') DEFAULT 'pending',
  `payment_method_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
);
CREATE TABLE `unified_files` (
  `file_id` int(11) NOT NULL,
  `file_uuid` varchar(255) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `folder_id` int(11) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `google_file_id` varchar(255) NOT NULL,
  `drive_link` text NOT NULL,
  `description` text DEFAULT NULL,
  `visibility` enum('private','public','class_only','specific_users') DEFAULT 'private',
  `file_purpose` enum('personal','class_material','assignment','submission') DEFAULT 'personal',
  `category_id` int(11) DEFAULT NULL,
  `upload_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
);
CREATE TABLE `users` (
  `uid` int(11) NOT NULL,
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
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `role` enum('TECHGURU','TECHKID','ADMIN') NOT NULL DEFAULT 'TECHKID',
  `created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
);
CREATE TABLE IF NOT EXISTS `recording_visibility` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recording_id` varchar(255) NOT NULL,
  `class_id` int(11) NOT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT 0,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_recording` (`recording_id`),
  KEY `fk_rec_vis_class` (`class_id`),
  KEY `fk_rec_vis_creator` (`created_by`),
  CONSTRAINT `fk_rec_vis_class` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rec_vis_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`uid`) ON DELETE CASCADE
);
-- Triggers
DELIMITER $$
CREATE TRIGGER `after_session_feedback_delete` AFTER DELETE ON `session_feedback` FOR EACH ROW BEGIN
    -- Update tutor's rating count and average rating
    UPDATE users u
    SET u.rating_count = (
            SELECT COUNT(*) 
            FROM session_feedback 
            WHERE tutor_id = OLD.tutor_id
        ),
        u.rating = COALESCE(
            (SELECT AVG(rating) 
             FROM session_feedback 
             WHERE tutor_id = OLD.tutor_id),
            0
        )
    WHERE u.uid = OLD.tutor_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_session_feedback_insert` AFTER INSERT ON `session_feedback` FOR EACH ROW BEGIN
    -- Update tutor's rating count and average rating
    UPDATE users u
    SET u.rating_count = (
            SELECT COUNT(*) 
            FROM session_feedback 
            WHERE tutor_id = NEW.tutor_id
        ),
        u.rating = (
            SELECT AVG(rating) 
            FROM session_feedback 
            WHERE tutor_id = NEW.tutor_id
        )
    WHERE u.uid = NEW.tutor_id;
END
$$
DELIMITER ;

--
-- Dumped Values
--
INSERT INTO `file_categories` (`category_id`, `category_name`, `description`) VALUES
(1, 'Lecture Notes', 'Course lecture materials and presentations'),
(2, 'Assignments', 'Homework and practice exercises'),
(3, 'Resources', 'Additional learning materials and references'),
(4, 'Solutions', 'Answer keys and solution guides'),
(5, 'Supplementary', 'Extra materials for advanced learning');
INSERT INTO `course`(`course_name`,`course_desc`) VALUES('Computer Programming','Learn how to write, debug, and develop software applications using various programming languages and methodologies.'), ('Computer Networking','Understand the principles of networking, including setup, security, and cloud integration to build robust IT infrastructures.'), ('Graphics Design','Master digital design, UI/UX, and animation techniques to create stunning visuals and interactive experiences.');
INSERT INTO `subject` (`subject_id`, `course_id`, `subject_name`, `subject_desc`, `image`, `is_active`) VALUES
(1, 1, 'Python Programming', 'Learn the fundamentals of Python, including syntax, data structures, and object-oriented programming.', 'default.jpg', 1),
(2, 1, 'Java Development', 'Covers Java programming basics, OOP principles, and application development.', 'default.jpg', 1),
(3, 1, 'C++ for Beginners', 'Introduces students to C++ programming, focusing on memory management, data structures, and algorithms.', 'default.jpg', 1),
(4, 2, 'Network Fundamentals', 'Explores basic networking concepts, protocols, and network topologies.', 'default.jpg', 1),
(5, 2, 'Routing and Switching', 'Focuses on configuring routers and switches, subnetting, and VLANs.', 'default.jpg', 1),
(6, 2, 'Network Security', 'Covers firewalls, intrusion detection systems, and encryption techniques for secure networking.', 'default.jpg', 1),
(7, 3, 'UI/UX Designing', 'Teaches principles of user interface and user experience design for web and mobile applications.', 'default.jpg', 1),
(8, 3, 'Adobe Photoshop Essentials', 'Covers photo editing, digital painting, and design techniques using Adobe Photoshop.', 'default.jpg', 1),
(9, 3, 'Vector Illustration with Adobe Illustrator', 'Focuses on creating digital illustrations, logos, and typography with Illustrator.', 'default.jpg', 1);
INSERT INTO `users` (`uid`, `email`, `password`, `first_name`, `last_name`, `gender`, `profile_picture`, `address`, `contact_number`, `rating`, `rating_count`, `is_verified`, `status`, `role`, `created_on`, `last_login`) VALUES
(1, 'tutor@test.com', '$2y$10$FwM//r8Nn2GUWpHSBMv0RuYxw7oBScsxjf.cYlnUuq1V2KcQkyM3.', 'Test', 'Tutor', 'U', 'default.jpg', NULL, NULL, 0.00, 0, 1, 1, 'TECHGURU', '2025-03-29 06:09:37', '2025-03-29 07:02:13'),
(2, 'student@test.com', '$2y$10$FwM//r8Nn2GUWpHSBMv0RuYxw7oBScsxjf.cYlnUuq1V2KcQkyM3.', 'Test', 'Student', 'U', 'default.jpg', NULL, NULL, 0.00, 0, 1, 1, 'TECHKID', '2025-03-29 06:09:37', '2025-03-29 07:01:31'),
(3, 'admin@test.com', '$2y$10$FwM//r8Nn2GUWpHSBMv0RuYxw7oBScsxjf.cYlnUuq1V2KcQkyM3.', 'Test', 'Admin', 'U', 'default.jpg', NULL, NULL, 0.00, 0, 1, 1, 'ADMIN', '2025-03-29 06:09:37', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `unique_attendance` (`schedule_id`,`student_id`),
  ADD KEY `idx_attendance_schedule` (`schedule_id`),
  ADD KEY `idx_attendance_student` (`student_id`),
  ADD KEY `idx_attendance_status` (`status`);

--
-- Indexes for table `certificate`
--
ALTER TABLE `certificate`
  ADD PRIMARY KEY (`cert_id`),
  ADD UNIQUE KEY `cert_uuid` (`cert_uuid`),
  ADD KEY `recipient` (`recipient`),
  ADD KEY `donor` (`donor`);

--
-- Indexes for table `class`
--
ALTER TABLE `class`
  ADD PRIMARY KEY (`class_id`),
  ADD KEY `idx_class_name` (`class_name`),
  ADD KEY `idx_class_status` (`status`),
  ADD KEY `idx_class_tutor` (`tutor_id`),
  ADD KEY `idx_class_subject` (`subject_id`);

--
-- Indexes for table `class_ratings`
--
ALTER TABLE `class_ratings`
  ADD PRIMARY KEY (`classRating_id`),
  ADD KEY `idx_class_rating_class` (`class_id`),
  ADD KEY `idx_class_rating_student` (`student_id`);

--
-- Indexes for table `class_schedule`
--
ALTER TABLE `class_schedule`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `idx_schedule_class` (`class_id`),
  ADD KEY `idx_schedule_date` (`session_date`),
  ADD KEY `idx_schedule_status` (`status`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`course_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `unique_enrollment` (`class_id`,`student_id`),
  ADD KEY `idx_enrollment_class` (`class_id`),
  ADD KEY `idx_enrollment_student` (`student_id`),
  ADD KEY `idx_enrollment_status` (`status`);

--
-- Indexes for table `file_access`
--
ALTER TABLE `file_access`
  ADD PRIMARY KEY (`access_id`),
  ADD UNIQUE KEY `unique_file_access` (`file_id`,`enrollment_id`),
  ADD KEY `enrollment_id` (`enrollment_id`),
  ADD KEY `granted_by` (`granted_by`);

--
-- Indexes for table `file_categories`
--
ALTER TABLE `file_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `unique_category_name` (`category_name`);

--
-- Indexes for table `file_category_mapping`
--
ALTER TABLE `file_category_mapping`
  ADD PRIMARY KEY (`file_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `file_folders`
--
ALTER TABLE `file_folders`
  ADD PRIMARY KEY (`folder_id`),
  ADD KEY `idx_file_folders_class` (`class_id`),
  ADD KEY `idx_file_folders_user` (`user_id`),
  ADD KEY `idx_file_folders_parent` (`parent_folder_id`),
  ADD KEY `idx_file_folders_visibility` (`visibility`);

--
-- Indexes for table `file_management`
--
ALTER TABLE `file_management`
  ADD PRIMARY KEY (`file_id`),
  ADD KEY `idx_file_class` (`class_id`),
  ADD KEY `idx_file_user` (`user_id`),
  ADD KEY `idx_file_visibility` (`is_visible`),
  ADD KEY `idx_file_personal` (`is_personal`),
  ADD KEY `idx_file_uuid` (`google_file_id`);

--
-- Indexes for table `file_permissions`
--
ALTER TABLE `file_permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD KEY `granted_by` (`granted_by`),
  ADD KEY `idx_file_permissions_file` (`file_id`),
  ADD KEY `idx_file_permissions_folder` (`folder_id`),
  ADD KEY `idx_file_permissions_user` (`user_id`);

--
-- Indexes for table `file_requests`
--
ALTER TABLE `file_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `response_file_id` (`response_file_id`),
  ADD KEY `idx_file_requests_class` (`class_id`),
  ADD KEY `idx_file_requests_requester` (`requester_id`),
  ADD KEY `idx_file_requests_recipient` (`recipient_id`),
  ADD KEY `idx_file_requests_status` (`status`);

--
-- Indexes for table `file_tags`
--
ALTER TABLE `file_tags`
  ADD PRIMARY KEY (`tag_id`),
  ADD UNIQUE KEY `tag_name_unique` (`tag_name`);

--
-- Indexes for table `file_tag_map`
--
ALTER TABLE `file_tag_map`
  ADD PRIMARY KEY (`map_id`),
  ADD UNIQUE KEY `file_tag_unique` (`file_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `file_upload_requests`
--
ALTER TABLE `file_upload_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `tutor_id` (`tutor_id`),
  ADD KEY `file_id` (`file_id`),
  ADD KEY `idx_request_student` (`student_id`),
  ADD KEY `idx_request_class` (`class_id`),
  ADD KEY `idx_request_status` (`status`);

--
-- Indexes for table `login_tokens`
--
ALTER TABLE `login_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`token`),
  ADD KEY `idx_token` (`token`);

--
-- Indexes for table `meetings`
--
ALTER TABLE `meetings`
  ADD PRIMARY KEY (`meeting_id`),
  ADD UNIQUE KEY `meeting_uid` (`meeting_uid`),
  ADD KEY `idx_meeting_uid` (`meeting_uid`),
  ADD KEY `idx_meeting_schedule` (`schedule_id`);

--
-- Indexes for table `meeting_analytics`
--
ALTER TABLE `meeting_analytics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_meeting_analytics_meeting_id` (`meeting_id`),
  ADD KEY `idx_meeting_analytics_tutor_id` (`tutor_id`),
  ADD KEY `idx_meeting_analytics_start_time` (`start_time`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `idx_notification_recipient` (`recipient_id`),
  ADD KEY `idx_notification_role` (`recipient_role`),
  ADD KEY `idx_notification_read` (`is_read`);

--
-- Indexes for table `session_feedback`
--
ALTER TABLE `session_feedback`
  ADD PRIMARY KEY (`rating_id`),
  ADD UNIQUE KEY `unique_session_feedback` (`session_id`,`student_id`),
  ADD KEY `idx_tutor_ratings` (`tutor_id`,`rating`),
  ADD KEY `idx_session_feedback` (`session_id`),
  ADD KEY `idx_student_feedback` (`student_id`),
  ADD KEY `idx_session_feedback_archived` (`is_archived`);

--
-- Indexes for table `subject`
--
ALTER TABLE `subject`
  ADD PRIMARY KEY (`subject_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `idx_payment_intent` (`payment_intent_id`),
  ADD KEY `idx_transaction_user` (`user_id`),
  ADD KEY `idx_transaction_status` (`status`),
  ADD KEY `idx_transaction_created_at` (`created_at`);

--
-- Indexes for table `unified_files`
--
ALTER TABLE `unified_files`
  ADD PRIMARY KEY (`file_id`),
  ADD UNIQUE KEY `file_uuid` (`file_uuid`),
  ADD KEY `idx_unified_files_class` (`class_id`),
  ADD KEY `idx_unified_files_user` (`user_id`),
  ADD KEY `idx_unified_files_folder` (`folder_id`),
  ADD KEY `idx_unified_files_visibility` (`visibility`),
  ADD KEY `idx_unified_files_purpose` (`file_purpose`),
  ADD KEY `idx_unified_files_category` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_status` (`status`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_user_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `certificate`
--
ALTER TABLE `certificate`
  MODIFY `cert_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class`
--
ALTER TABLE `class`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `class_ratings`
--
ALTER TABLE `class_ratings`
  MODIFY `classRating_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_schedule`
--
ALTER TABLE `class_schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `course`
--
ALTER TABLE `course`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `file_access`
--
ALTER TABLE `file_access`
  MODIFY `access_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `file_categories`
--
ALTER TABLE `file_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `file_folders`
--
ALTER TABLE `file_folders`
  MODIFY `folder_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `file_management`
--
ALTER TABLE `file_management`
  MODIFY `file_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `file_permissions`
--
ALTER TABLE `file_permissions`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `file_requests`
--
ALTER TABLE `file_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `file_tags`
--
ALTER TABLE `file_tags`
  MODIFY `tag_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `file_tag_map`
--
ALTER TABLE `file_tag_map`
  MODIFY `map_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `file_upload_requests`
--
ALTER TABLE `file_upload_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_tokens`
--
ALTER TABLE `login_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meetings`
--
ALTER TABLE `meetings`
  MODIFY `meeting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `meeting_analytics`
--
ALTER TABLE `meeting_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `session_feedback`
--
ALTER TABLE `session_feedback`
  MODIFY `rating_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subject`
--
ALTER TABLE `subject`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `unified_files`
--
ALTER TABLE `unified_files`
  MODIFY `file_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `class_schedule` (`schedule_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `enrollments` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `certificate`
--
ALTER TABLE `certificate`
  ADD CONSTRAINT `certificate_ibfk_1` FOREIGN KEY (`recipient`) REFERENCES `users` (`uid`),
  ADD CONSTRAINT `certificate_ibfk_2` FOREIGN KEY (`donor`) REFERENCES `users` (`uid`);

--
-- Constraints for table `class`
--
ALTER TABLE `class`
  ADD CONSTRAINT `class_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`),
  ADD CONSTRAINT `class_ibfk_2` FOREIGN KEY (`tutor_id`) REFERENCES `users` (`uid`);

--
-- Constraints for table `class_ratings`
--
ALTER TABLE `class_ratings`
  ADD CONSTRAINT `class_ratings_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_ratings_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `enrollments` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `class_schedule`
--
ALTER TABLE `class_schedule`
  ADD CONSTRAINT `class_schedule_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`);

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE;

--
-- Constraints for table `file_access`
--
ALTER TABLE `file_access`
  ADD CONSTRAINT `file_access_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `file_management` (`file_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_access_ibfk_2` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`enrollment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_access_ibfk_3` FOREIGN KEY (`granted_by`) REFERENCES `users` (`uid`) ON DELETE CASCADE;

--
-- Constraints for table `file_category_mapping`
--
ALTER TABLE `file_category_mapping`
  ADD CONSTRAINT `file_category_mapping_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `file_management` (`file_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_category_mapping_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `file_categories` (`category_id`) ON DELETE CASCADE;

--
-- Constraints for table `file_folders`
--
ALTER TABLE `file_folders`
  ADD CONSTRAINT `file_folders_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_folders_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_folders_ibfk_3` FOREIGN KEY (`parent_folder_id`) REFERENCES `file_folders` (`folder_id`) ON DELETE CASCADE;

--
-- Constraints for table `file_management`
--
ALTER TABLE `file_management`
  ADD CONSTRAINT `file_management_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_management_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE;

--
-- Constraints for table `file_permissions`
--
ALTER TABLE `file_permissions`
  ADD CONSTRAINT `file_permissions_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `unified_files` (`file_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_permissions_ibfk_2` FOREIGN KEY (`folder_id`) REFERENCES `file_folders` (`folder_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_permissions_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_permissions_ibfk_4` FOREIGN KEY (`granted_by`) REFERENCES `users` (`uid`) ON DELETE CASCADE;

--
-- Constraints for table `file_requests`
--
ALTER TABLE `file_requests`
  ADD CONSTRAINT `file_requests_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_requests_ibfk_2` FOREIGN KEY (`requester_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_requests_ibfk_3` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_requests_ibfk_4` FOREIGN KEY (`response_file_id`) REFERENCES `unified_files` (`file_id`) ON DELETE SET NULL;

--
-- Constraints for table `file_tag_map`
--
ALTER TABLE `file_tag_map`
  ADD CONSTRAINT `file_tag_map_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `unified_files` (`file_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_tag_map_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `file_tags` (`tag_id`) ON DELETE CASCADE;

--
-- Constraints for table `file_upload_requests`
--
ALTER TABLE `file_upload_requests`
  ADD CONSTRAINT `file_upload_requests_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_upload_requests_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_upload_requests_ibfk_3` FOREIGN KEY (`tutor_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_upload_requests_ibfk_4` FOREIGN KEY (`file_id`) REFERENCES `file_management` (`file_id`) ON DELETE SET NULL;

--
-- Constraints for table `login_tokens`
--
ALTER TABLE `login_tokens`
  ADD CONSTRAINT `login_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE;

--
-- Constraints for table `meetings`
--
ALTER TABLE `meetings`
  ADD CONSTRAINT `meetings_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `class_schedule` (`schedule_id`);

--
-- Constraints for table `meeting_analytics`
--
ALTER TABLE `meeting_analytics`
  ADD CONSTRAINT `meeting_analytics_ibfk_1` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`meeting_id`),
  ADD CONSTRAINT `meeting_analytics_ibfk_2` FOREIGN KEY (`tutor_id`) REFERENCES `users` (`uid`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`uid`),
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`);

--
-- Constraints for table `session_feedback`
--
ALTER TABLE `session_feedback`
  ADD CONSTRAINT `session_feedback_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `class_schedule` (`schedule_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `session_feedback_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE,
  ADD CONSTRAINT `session_feedback_ibfk_3` FOREIGN KEY (`tutor_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE;

--
-- Constraints for table `subject`
--
ALTER TABLE `subject`
  ADD CONSTRAINT `subject_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE;

--
-- Constraints for table `unified_files`
--
ALTER TABLE `unified_files`
  ADD CONSTRAINT `unified_files_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `unified_files_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE,
  ADD CONSTRAINT `unified_files_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `file_categories` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `unified_files_ibfk_4` FOREIGN KEY (`folder_id`) REFERENCES `file_folders` (`folder_id`) ON DELETE SET NULL;
COMMIT;