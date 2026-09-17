-- MindCare Database Export
-- Host: 127.0.0.1:3307
-- Database: mindcare_db
-- Updated & Verified for Production
-- Generation Time: 2026-09-17 12:36:38

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table structure for `academic_relief_requests`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `academic_relief_requests`;
CREATE TABLE `academic_relief_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `token` varchar(10) NOT NULL,
  `accommodation_type` varchar(200) NOT NULL,
  `forwarded_by_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reject_reason` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `forwarded_by_id` (`forwarded_by_id`),
  CONSTRAINT `academic_relief_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  CONSTRAINT `academic_relief_requests_ibfk_2` FOREIGN KEY (`forwarded_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `academic_relief_requests`

INSERT INTO `academic_relief_requests` (`id`, `student_id`, `token`, `accommodation_type`, `forwarded_by_id`, `status`, `reject_reason`, `reviewed_by`, `reviewed_at`, `created_at`) VALUES ('1', '2', 'STU0001', 'Assignment Deadline Extension (7 days)', '4', 'pending', NULL, NULL, NULL, '2026-06-20 12:14:16');

-- --------------------------------------------------------
-- Table structure for `advisor_email_log`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `advisor_email_log`;
CREATE TABLE `advisor_email_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `advisor_id` int(11) NOT NULL,
  `counselor_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `sent_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `advisor_id` (`advisor_id`),
  KEY `counselor_id` (`counselor_id`),
  CONSTRAINT `advisor_email_log_ibfk_1` FOREIGN KEY (`advisor_id`) REFERENCES `users` (`id`),
  CONSTRAINT `advisor_email_log_ibfk_2` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `ai_chat_messages`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ai_chat_messages`;
CREATE TABLE `ai_chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `role` enum('user','assistant') NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `ai_chat_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `appointments`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `appointments`;
CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `counselor_id` int(11) NOT NULL,
  `guardian_id` int(11) DEFAULT NULL,
  `booking_source` enum('student','guardian','counselor_assigned') NOT NULL DEFAULT 'student',
  `session_type` enum('physical','online') NOT NULL,
  `preferred_date` date NOT NULL,
  `preferred_time` varchar(20) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','accepted','postponed','completed','cancelled') DEFAULT 'pending',
  `reschedule_reason` text DEFAULT NULL,
  `new_date` date DEFAULT NULL,
  `new_time` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `counselor_id` (`counselor_id`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `appointments`

INSERT INTO `appointments` (`id`, `student_id`, `counselor_id`, `guardian_id`, `booking_source`, `session_type`, `preferred_date`, `preferred_time`, `notes`, `status`, `reschedule_reason`, `new_date`, `new_time`, `created_at`) VALUES ('1', '2', '4', NULL, 'student', 'physical', '2026-06-24', '9:00 AM', 'Test', 'accepted', '', NULL, NULL, '2026-06-24 13:35:19');
INSERT INTO `appointments` (`id`, `student_id`, `counselor_id`, `guardian_id`, `booking_source`, `session_type`, `preferred_date`, `preferred_time`, `notes`, `status`, `reschedule_reason`, `new_date`, `new_time`, `created_at`) VALUES ('2', '2', '5', NULL, 'student', 'physical', '2026-06-24', '2:00 PM', 'Test', 'pending', NULL, NULL, NULL, '2026-06-24 13:37:25');
INSERT INTO `appointments` (`id`, `student_id`, `counselor_id`, `guardian_id`, `booking_source`, `session_type`, `preferred_date`, `preferred_time`, `notes`, `status`, `reschedule_reason`, `new_date`, `new_time`, `created_at`) VALUES ('3', '2', '4', NULL, 'student', 'physical', '2026-06-25', '9:00 AM', '', 'accepted', '', NULL, NULL, '2026-06-25 09:55:30');
INSERT INTO `appointments` (`id`, `student_id`, `counselor_id`, `guardian_id`, `booking_source`, `session_type`, `preferred_date`, `preferred_time`, `notes`, `status`, `reschedule_reason`, `new_date`, `new_time`, `created_at`) VALUES ('4', '2', '5', NULL, 'student', 'online', '2026-06-25', '3:00 PM', '', 'pending', NULL, NULL, NULL, '2026-06-25 09:56:01');
INSERT INTO `appointments` (`id`, `student_id`, `counselor_id`, `guardian_id`, `booking_source`, `session_type`, `preferred_date`, `preferred_time`, `notes`, `status`, `reschedule_reason`, `new_date`, `new_time`, `created_at`) VALUES ('5', '2', '4', NULL, 'student', 'online', '2026-06-25', '10:30 AM', '', 'accepted', '', NULL, NULL, '2026-06-25 09:57:49');
INSERT INTO `appointments` (`id`, `student_id`, `counselor_id`, `guardian_id`, `booking_source`, `session_type`, `preferred_date`, `preferred_time`, `notes`, `status`, `reschedule_reason`, `new_date`, `new_time`, `created_at`) VALUES ('6', '2', '5', NULL, 'student', 'physical', '2026-06-25', '2:30 PM', '', 'pending', NULL, NULL, NULL, '2026-06-25 10:00:25');
INSERT INTO `appointments` (`id`, `student_id`, `counselor_id`, `guardian_id`, `booking_source`, `session_type`, `preferred_date`, `preferred_time`, `notes`, `status`, `reschedule_reason`, `new_date`, `new_time`, `created_at`) VALUES ('7', '2', '5', NULL, 'student', 'physical', '2026-06-25', '3:00 PM', 'Test block', 'pending', NULL, NULL, NULL, '2026-06-25 10:03:16');
INSERT INTO `appointments` (`id`, `student_id`, `counselor_id`, `guardian_id`, `booking_source`, `session_type`, `preferred_date`, `preferred_time`, `notes`, `status`, `reschedule_reason`, `new_date`, `new_time`, `created_at`) VALUES ('8', '2', '5', NULL, 'student', 'online', '2026-06-25', '9:30 AM', '', 'pending', NULL, NULL, NULL, '2026-06-25 10:08:06');
INSERT INTO `appointments` (`id`, `student_id`, `counselor_id`, `guardian_id`, `booking_source`, `session_type`, `preferred_date`, `preferred_time`, `notes`, `status`, `reschedule_reason`, `new_date`, `new_time`, `created_at`) VALUES ('9', '2', '4', NULL, 'student', 'physical', '2026-06-25', '2:00 PM', '', 'accepted', '', NULL, NULL, '2026-06-25 10:09:04');
INSERT INTO `appointments` (`id`, `student_id`, `counselor_id`, `guardian_id`, `booking_source`, `session_type`, `preferred_date`, `preferred_time`, `notes`, `status`, `reschedule_reason`, `new_date`, `new_time`, `created_at`) VALUES ('11', '1', '4', NULL, 'student', 'physical', '2026-06-25', '11:00 AM', 'emergency block', 'accepted', '', NULL, NULL, '2026-06-25 10:20:50');
INSERT INTO `appointments` (`id`, `student_id`, `counselor_id`, `guardian_id`, `booking_source`, `session_type`, `preferred_date`, `preferred_time`, `notes`, `status`, `reschedule_reason`, `new_date`, `new_time`, `created_at`) VALUES ('12', '2', '5', NULL, 'student', 'online', '2026-06-25', '2:00 PM', '', 'pending', NULL, NULL, NULL, '2026-06-25 11:43:47');
INSERT INTO `appointments` (`id`, `student_id`, `counselor_id`, `guardian_id`, `booking_source`, `session_type`, `preferred_date`, `preferred_time`, `notes`, `status`, `reschedule_reason`, `new_date`, `new_time`, `created_at`) VALUES ('13', '2', '2', NULL, 'student', 'physical', '2026-06-26', '10:00 AM', 'Exam stress', 'pending', NULL, NULL, NULL, '2026-06-26 03:31:39');
INSERT INTO `appointments` (`id`, `student_id`, `counselor_id`, `guardian_id`, `booking_source`, `session_type`, `preferred_date`, `preferred_time`, `notes`, `status`, `reschedule_reason`, `new_date`, `new_time`, `created_at`) VALUES ('14', '2', '5', NULL, 'student', 'physical', '2026-06-28', '9:00 AM', '', 'pending', NULL, NULL, NULL, '2026-06-28 13:59:26');
INSERT INTO `appointments` (`id`, `student_id`, `counselor_id`, `guardian_id`, `booking_source`, `session_type`, `preferred_date`, `preferred_time`, `notes`, `status`, `reschedule_reason`, `new_date`, `new_time`, `created_at`) VALUES ('15', '2', '4', NULL, 'student', 'online', '2026-06-30', '10:30 AM', '', 'accepted', '', NULL, NULL, '2026-06-30 08:44:53');
INSERT INTO `appointments` (`id`, `student_id`, `counselor_id`, `guardian_id`, `booking_source`, `session_type`, `preferred_date`, `preferred_time`, `notes`, `status`, `reschedule_reason`, `new_date`, `new_time`, `created_at`) VALUES ('16', '2', '4', NULL, 'student', 'physical', '2026-08-13', '2:00 PM', '', 'accepted', '', NULL, NULL, '2026-08-13 10:43:58');
INSERT INTO `appointments` (`id`, `student_id`, `counselor_id`, `guardian_id`, `booking_source`, `session_type`, `preferred_date`, `preferred_time`, `notes`, `status`, `reschedule_reason`, `new_date`, `new_time`, `created_at`) VALUES ('17', '2', '4', NULL, 'student', 'physical', '2026-08-14', '3:30 PM', '', 'accepted', '', NULL, NULL, '2026-08-14 11:53:55');

-- --------------------------------------------------------
-- Table structure for `blocked_slots`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `blocked_slots`;
CREATE TABLE `blocked_slots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `counselor_id` int(11) NOT NULL,
  `block_date` date NOT NULL,
  `block_time` varchar(20) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `counselor_id` (`counselor_id`),
  CONSTRAINT `blocked_slots_ibfk_1` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `blocked_slots`

INSERT INTO `blocked_slots` (`id`, `counselor_id`, `block_date`, `block_time`, `reason`, `created_at`) VALUES ('1', '4', '2026-08-14', '9:00 AM', 'fewvfewdv', '2026-08-14 09:44:16');

-- --------------------------------------------------------
-- Table structure for `counselor_notes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `counselor_notes`;
CREATE TABLE `counselor_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `counselor_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `notes` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `counselor_id` (`counselor_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `counselor_notes_ibfk_1` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`id`),
  CONSTRAINT `counselor_notes_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `daily_checkins`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `daily_checkins`;
CREATE TABLE `daily_checkins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `mood_emoji` varchar(10) DEFAULT NULL,
  `checkin_date` date NOT NULL,
  `q1_interest` tinyint(1) NOT NULL,
  `q2_mood` tinyint(1) NOT NULL,
  `q3_sleep` tinyint(1) NOT NULL,
  `q4_energy` tinyint(1) NOT NULL,
  `q5_appetite` tinyint(1) NOT NULL,
  `q6_selfworth` tinyint(1) NOT NULL,
  `q7_concentration` tinyint(1) NOT NULL,
  `q8_restlessness` tinyint(1) NOT NULL,
  `q9_selfharm` tinyint(1) NOT NULL,
  `total_score` tinyint(4) DEFAULT NULL,
  `severity` varchar(30) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `daily_checkins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `daily_checkins`

INSERT INTO `daily_checkins` (`id`, `user_id`, `mood_emoji`, `checkin_date`, `q1_interest`, `q2_mood`, `q3_sleep`, `q4_energy`, `q5_appetite`, `q6_selfworth`, `q7_concentration`, `q8_restlessness`, `q9_selfharm`, `total_score`, `severity`, `created_at`) VALUES ('1', '2', '', '2026-08-13', '0', '0', '0', '0', '0', '0', '0', '1', '0', '1', 'Minimal', '2026-08-13 17:39:44');
INSERT INTO `daily_checkins` (`id`, `user_id`, `mood_emoji`, `checkin_date`, `q1_interest`, `q2_mood`, `q3_sleep`, `q4_energy`, `q5_appetite`, `q6_selfworth`, `q7_concentration`, `q8_restlessness`, `q9_selfharm`, `total_score`, `severity`, `created_at`) VALUES ('2', '2', '😢', '2026-08-14', '1', '0', '3', '0', '0', '0', '0', '0', '0', '4', 'Minimal', '2026-08-14 09:43:25');

-- --------------------------------------------------------
-- Table structure for `diary_entries`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `diary_entries`;
CREATE TABLE `diary_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `content` text NOT NULL,
  `share_with_counselor` tinyint(1) DEFAULT 0,
  `share_with_guardian` tinyint(1) DEFAULT 0,
  `is_pinned` tinyint(1) DEFAULT 0,
  `mood_emoji` varchar(10) DEFAULT NULL,
  `entry_date` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `diary_entries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `diary_entries`

INSERT INTO `diary_entries` (`id`, `user_id`, `title`, `content`, `share_with_counselor`, `share_with_guardian`, `is_pinned`, `mood_emoji`, `entry_date`, `created_at`) VALUES ('1', '2', 'MONDAY', '7f9XuGSIvrfPtev3pkl1hQ==::/SVa3NFiOGsX9EoTxaBL84weZW4GoZXiQxc2Ro71Feo=', '0', '0', '0', NULL, '2026-06-24', '2026-06-24 11:43:24');
INSERT INTO `diary_entries` (`id`, `user_id`, `title`, `content`, `share_with_counselor`, `share_with_guardian`, `is_pinned`, `mood_emoji`, `entry_date`, `created_at`) VALUES ('5', '4', '', 'wraJmtIcjl7TTnFm1PtBMw==::VYjdC5fhHXsERNB7ASaMag==', '0', '0', '1', '', '2026-08-14', '2026-08-14 11:51:29');
INSERT INTO `diary_entries` (`id`, `user_id`, `title`, `content`, `share_with_counselor`, `share_with_guardian`, `is_pinned`, `mood_emoji`, `entry_date`, `created_at`) VALUES ('6', '4', '', 'LGnT4KBVM2hZpCFoqgyuxw==::PJudBmYnecjF6R7tqg4uhQ==', '0', '0', '0', '', '2026-08-14', '2026-08-14 11:52:03');
INSERT INTO `diary_entries` (`id`, `user_id`, `title`, `content`, `share_with_counselor`, `share_with_guardian`, `is_pinned`, `mood_emoji`, `entry_date`, `created_at`) VALUES ('7', '4', '', 'FdXxA3y7cgnDfVN2VtI2iw==::nixBoa4s6vsT85JbybcRWcKnlkoaiT5P9WIbxv8xv6U=', '0', '0', '1', '', '2026-08-14', '2026-08-14 11:52:13');
INSERT INTO `diary_entries` (`id`, `user_id`, `title`, `content`, `share_with_counselor`, `share_with_guardian`, `is_pinned`, `mood_emoji`, `entry_date`, `created_at`) VALUES ('8', '4', 'exam', 'nLoqyg9+FbPoe7NfWde6jA==::fkTOEc6+2OYrDVYJlNSquw==', '0', '0', '0', '', '2026-08-14', '2026-08-14 12:26:54');
INSERT INTO `diary_entries` (`id`, `user_id`, `title`, `content`, `share_with_counselor`, `share_with_guardian`, `is_pinned`, `mood_emoji`, `entry_date`, `created_at`) VALUES ('9', '6', 'exam', 'g7QciYB9eNOcZOVSOudAKQ==::igFsFHGHuGVaK49Ud+rr0Q==', '0', '0', '0', '', '2026-08-14', '2026-08-14 12:55:01');
INSERT INTO `diary_entries` (`id`, `user_id`, `title`, `content`, `share_with_counselor`, `share_with_guardian`, `is_pinned`, `mood_emoji`, `entry_date`, `created_at`) VALUES ('10', '6', '3gvr54vg5r', 'mLBicT7pt9czqBVFfhHMAw==::nzOA2XB/9esSyYSYTHRlmw==', '0', '0', '0', '', '2026-08-14', '2026-08-14 12:55:13');

-- --------------------------------------------------------
-- Table structure for `forum_posts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `forum_posts`;
CREATE TABLE `forum_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `anon_key` varchar(20) NOT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `content` text NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `is_reported` tinyint(1) DEFAULT 0,
  `is_removed` tinyint(1) DEFAULT 0,
  `is_approved` tinyint(1) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `forum_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `forum_posts`

INSERT INTO `forum_posts` (`id`, `user_id`, `anon_key`, `display_name`, `content`, `category`, `is_reported`, `is_removed`, `is_approved`, `likes`, `created_at`) VALUES ('1', '2', 'User#1234', NULL, 'Feeling overwhelmed with assignments this week. Anyone else?', 'Academic Pressure', '1', '0', '1', '0', '2026-06-24 17:20:28');
INSERT INTO `forum_posts` (`id`, `user_id`, `anon_key`, `display_name`, `content`, `category`, `is_reported`, `is_removed`, `is_approved`, `likes`, `created_at`) VALUES ('2', '4', 'User#4545', NULL, 'wishing your a good day', 'General', '1', '0', '1', '0', '2026-06-25 10:57:51');
INSERT INTO `forum_posts` (`id`, `user_id`, `anon_key`, `display_name`, `content`, `category`, `is_reported`, `is_removed`, `is_approved`, `likes`, `created_at`) VALUES ('3', '5', 'User#1123', NULL, 'wishing you a good day', 'General', '0', '1', '0', '0', '2026-06-25 11:08:45');

-- --------------------------------------------------------
-- Table structure for `guardian_daily_slots`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `guardian_daily_slots`;
CREATE TABLE `guardian_daily_slots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `counselor_id` int(11) NOT NULL,
  `slot_time` varchar(20) NOT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `counselor_id` (`counselor_id`),
  CONSTRAINT `guardian_daily_slots_ibfk_1` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `guardian_daily_slots`

INSERT INTO `guardian_daily_slots` (`id`, `counselor_id`, `slot_time`, `updated_at`) VALUES ('1', '4', '11:00 AM', '2026-08-14 09:44:09');
INSERT INTO `guardian_daily_slots` (`id`, `counselor_id`, `slot_time`, `updated_at`) VALUES ('2', '5', '3:30 PM', '2026-08-13 00:23:35');

-- --------------------------------------------------------
-- Table structure for `guardian_sessions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `guardian_sessions`;
CREATE TABLE `guardian_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_phone` varchar(255) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `otp_code` varchar(6) NOT NULL,
  `otp_expires` datetime NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `guardian_sessions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `guardian_sessions`

INSERT INTO `guardian_sessions` (`id`, `guardian_name`, `guardian_phone`, `student_id`, `otp_code`, `otp_expires`, `is_verified`, `created_at`) VALUES ('39', NULL, 'tharushid2003@gmail.com', '2', '144935', '2026-08-12 15:06:52', '0', '2026-08-12 15:01:52');
INSERT INTO `guardian_sessions` (`id`, `guardian_name`, `guardian_phone`, `student_id`, `otp_code`, `otp_expires`, `is_verified`, `created_at`) VALUES ('40', NULL, 'tharushid2003@gmail.com', '2', '148607', '2026-08-12 15:07:32', '0', '2026-08-12 15:02:32');
INSERT INTO `guardian_sessions` (`id`, `guardian_name`, `guardian_phone`, `student_id`, `otp_code`, `otp_expires`, `is_verified`, `created_at`) VALUES ('41', NULL, 'sudeera.uog09@edu.lnbti.lk', NULL, '443764', '2026-08-12 15:30:11', '0', '2026-08-12 15:15:11');
INSERT INTO `guardian_sessions` (`id`, `guardian_name`, `guardian_phone`, `student_id`, `otp_code`, `otp_expires`, `is_verified`, `created_at`) VALUES ('42', NULL, 'sudeera.uog10@edu.lnbti.lk', NULL, '349000', '2026-08-12 23:05:54', '0', '2026-08-12 22:50:54');
INSERT INTO `guardian_sessions` (`id`, `guardian_name`, `guardian_phone`, `student_id`, `otp_code`, `otp_expires`, `is_verified`, `created_at`) VALUES ('43', NULL, 'sudeera.bsc.se03@edu.lnbti.lk', NULL, '090845', '2026-08-12 23:06:16', '0', '2026-08-12 22:51:16');
INSERT INTO `guardian_sessions` (`id`, `guardian_name`, `guardian_phone`, `student_id`, `otp_code`, `otp_expires`, `is_verified`, `created_at`) VALUES ('45', NULL, 'tharushi.uog09@edu.lnbti.lk', NULL, '178876', '2026-08-13 01:21:56', '0', '2026-08-13 01:06:56');

-- --------------------------------------------------------
-- Table structure for `keyword_rules`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `keyword_rules`;
CREATE TABLE `keyword_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(100) NOT NULL,
  `language` enum('english','sinhala','tamil') NOT NULL,
  `severity` enum('medium','high','critical') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `keyword_rules`

INSERT INTO `keyword_rules` (`id`, `keyword`, `language`, `severity`, `is_active`) VALUES ('1', 'kill myself', 'english', 'critical', '1');
INSERT INTO `keyword_rules` (`id`, `keyword`, `language`, `severity`, `is_active`) VALUES ('2', 'end my life', 'english', 'critical', '1');
INSERT INTO `keyword_rules` (`id`, `keyword`, `language`, `severity`, `is_active`) VALUES ('3', 'no reason to live', 'english', 'critical', '1');
INSERT INTO `keyword_rules` (`id`, `keyword`, `language`, `severity`, `is_active`) VALUES ('4', 'want to die', 'english', 'high', '1');
INSERT INTO `keyword_rules` (`id`, `keyword`, `language`, `severity`, `is_active`) VALUES ('5', 'suicide', 'english', 'high', '1');
INSERT INTO `keyword_rules` (`id`, `keyword`, `language`, `severity`, `is_active`) VALUES ('6', 'self harm', 'english', 'high', '1');
INSERT INTO `keyword_rules` (`id`, `keyword`, `language`, `severity`, `is_active`) VALUES ('7', 'hopeless', 'english', 'high', '1');
INSERT INTO `keyword_rules` (`id`, `keyword`, `language`, `severity`, `is_active`) VALUES ('8', 'worthless', 'english', 'medium', '1');
INSERT INTO `keyword_rules` (`id`, `keyword`, `language`, `severity`, `is_active`) VALUES ('9', 'cant take it', 'english', 'medium', '1');
INSERT INTO `keyword_rules` (`id`, `keyword`, `language`, `severity`, `is_active`) VALUES ('10', 'overwhelmed', 'english', 'medium', '1');

-- --------------------------------------------------------
-- Table structure for `meditation_types`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `meditation_types`;
CREATE TABLE `meditation_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `icon` varchar(10) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `steps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`steps`)),
  `guidance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`guidance`)),
  `sound_url` text DEFAULT NULL,
  `stroke_color` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `meditation_types_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `meditation_types`

INSERT INTO `meditation_types` (`id`, `name`, `icon`, `description`, `steps`, `guidance`, `sound_url`, `stroke_color`, `is_active`, `created_by`, `created_at`) VALUES ('1', 'Focus', '🎯', 'Clear your mind before studying or an exam', '[\"Sit upright and close your eyes gently\",\"Take 3 deep breaths to settle your body\",\"Focus your attention on your breath\",\"When thoughts arise, gently return to your breath\",\"Let clarity build naturally with each breath\"]', '[{\"time\":0,\"text\":\"Lets begin. Find a comfortable position and gently close your eyes.\"},{\"time\":10,\"text\":\"Take a long, deep breath in through your nose...\"},{\"time\":35,\"text\":\"Bring your attention to your breath. Simply notice each inhale and exhale.\"},{\"time\":70,\"text\":\"If your mind wanders, gently bring it back.\"},{\"time\":120,\"text\":\"You are doing beautifully. Continue breathing slowly.\"}]', 'https://cdn.pixabay.com/download/audio/2022/03/10/audio_270f41e9bd.mp3', '#2D9B6A', '1', '1', '2026-06-26 03:40:24');
INSERT INTO `meditation_types` (`id`, `name`, `icon`, `description`, `steps`, `guidance`, `sound_url`, `stroke_color`, `is_active`, `created_by`, `created_at`) VALUES ('2', 'Anxiety Relief', '🌿', 'Calm racing thoughts and reduce tension', '[\"Find a comfortable position and relax your shoulders\",\"Place one hand on your chest, one on your belly\",\"Breathe slowly — feel your belly rise first\",\"With each exhale, consciously release tension\",\"Remind yourself: this feeling will pass\"]', '[{\"time\":0,\"text\":\"You are safe. Lets begin together.\"},{\"time\":10,\"text\":\"Place one hand gently on your belly. Take a slow breath in...\"},{\"time\":35,\"text\":\"With every breath out, release a little more tension.\"},{\"time\":70,\"text\":\"You dont need to fight your thoughts. Simply let them pass.\"},{\"time\":130,\"text\":\"This feeling will pass. You are safe in this moment.\"}]', 'https://cdn.pixabay.com/download/audio/2021/09/06/audio_6def761615.mp3', '#388E3C', '1', '1', '2026-06-26 03:40:24');
INSERT INTO `meditation_types` (`id`, `name`, `icon`, `description`, `steps`, `guidance`, `sound_url`, `stroke_color`, `is_active`, `created_by`, `created_at`) VALUES ('3', 'Sleep Prep', '🌙', 'Wind down and prepare for restful sleep', '[\"Lie down and let your body sink into the surface\",\"Starting from your toes, relax each part of your body\",\"Breathe slowly — inhale for 4, exhale for 6\",\"Let your thoughts drift without following them\",\"Allow yourself to feel heavy, warm, and safe\"]', '[{\"time\":0,\"text\":\"Its time to rest. Lie down and let your body be completely supported.\"},{\"time\":12,\"text\":\"Starting from your toes — let them relax completely.\"},{\"time\":50,\"text\":\"Your shoulders, your arms, your hands... completely at rest.\"},{\"time\":130,\"text\":\"Let your thoughts drift by without following them.\"}]', 'https://cdn.pixabay.com/download/audio/2022/03/24/audio_946df0d016.mp3', '#3D5A99', '1', '1', '2026-06-26 03:40:24');
INSERT INTO `meditation_types` (`id`, `name`, `icon`, `description`, `steps`, `guidance`, `sound_url`, `stroke_color`, `is_active`, `created_by`, `created_at`) VALUES ('4', 'Morning Reset', '🌅', 'Start your day with clarity and intention', '[\"Sit quietly before checking your phone\",\"Take 5 deep breaths and feel yourself wake up gently\",\"Set one intention for the day ahead\",\"Visualise yourself moving through the day with calm\",\"Open your eyes slowly and begin\"]', '[{\"time\":0,\"text\":\"Good morning. Before the day begins, take this moment just for you.\"},{\"time\":12,\"text\":\"Take a long, deep breath in and feel your body wake up gently.\"},{\"time\":45,\"text\":\"Think of one thing youre grateful for this morning.\"},{\"time\":80,\"text\":\"Set a simple intention for today.\"}]', 'https://cdn.pixabay.com/download/audio/2021/11/25/audio_91b32d278e.mp3', '#F57C00', '1', '1', '2026-06-26 03:40:24');
INSERT INTO `meditation_types` (`id`, `name`, `icon`, `description`, `steps`, `guidance`, `sound_url`, `stroke_color`, `is_active`, `created_by`, `created_at`) VALUES ('5', 'Stress Relief', '💆', 'Release tension after a difficult day', '[\"Sit or lie in a comfortable position\",\"Inhale deeply and tense your whole body for 5 seconds\",\"Exhale and release everything at once\",\"Notice the difference between tension and release\",\"Repeat — each cycle carries stress away\"]', '[{\"time\":0,\"text\":\"Youve made it through. This time is yours.\"},{\"time\":12,\"text\":\"Take a deep breath in and gently tense your whole body...\"},{\"time\":18,\"text\":\"Now let it all go. Exhale completely. Feel the release.\"},{\"time\":70,\"text\":\"Your body knows how to rest. Trust it.\"}]', 'https://cdn.pixabay.com/download/audio/2022/05/27/audio_1808fbf07a.mp3', '#C2185B', '1', '1', '2026-06-26 03:40:24');

-- --------------------------------------------------------
-- Table structure for `music_tracks`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `music_tracks`;
CREATE TABLE `music_tracks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `category` enum('calm','sleep','energy','water','bowl') DEFAULT 'calm',
  `cover_url` text DEFAULT NULL,
  `audio_url` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `music_tracks_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `music_tracks`

INSERT INTO `music_tracks` (`id`, `title`, `category`, `cover_url`, `audio_url`, `is_active`, `created_by`, `created_at`) VALUES ('1', 'Gentle Rain', 'calm', 'https://images.unsplash.com/photo-1465146344425-f00d5f5c8f07?w=600&q=80', 'https://cdn.pixabay.com/download/audio/2022/05/27/audio_1808fbf07a.mp3', '1', '1', '2026-06-26 03:40:24');
INSERT INTO `music_tracks` (`id`, `title`, `category`, `cover_url`, `audio_url`, `is_active`, `created_by`, `created_at`) VALUES ('2', 'Forest Morning', 'calm', 'https://images.unsplash.com/photo-1448375240586-882707db888b?w=600&q=80', 'https://cdn.pixabay.com/download/audio/2021/09/06/audio_6def761615.mp3', '1', '1', '2026-06-26 03:40:24');
INSERT INTO `music_tracks` (`id`, `title`, `category`, `cover_url`, `audio_url`, `is_active`, `created_by`, `created_at`) VALUES ('3', 'Soft Piano', 'calm', 'https://images.unsplash.com/photo-1520523839897-bd0b52f945a0?w=600&q=80', 'https://cdn.pixabay.com/download/audio/2021/11/25/audio_91b32d278e.mp3', '1', '1', '2026-06-26 03:40:24');
INSERT INTO `music_tracks` (`id`, `title`, `category`, `cover_url`, `audio_url`, `is_active`, `created_by`, `created_at`) VALUES ('4', 'Ocean Waves', 'sleep', 'https://images.unsplash.com/photo-1505118380757-91f5f5632de0?w=600&q=80', 'https://cdn.pixabay.com/download/audio/2022/03/24/audio_946df0d016.mp3', '1', '1', '2026-06-26 03:40:24');
INSERT INTO `music_tracks` (`id`, `title`, `category`, `cover_url`, `audio_url`, `is_active`, `created_by`, `created_at`) VALUES ('5', 'Night Rain', 'sleep', 'https://images.unsplash.com/photo-1534274988757-a28bf1a57c17?w=600&q=80', 'https://cdn.pixabay.com/download/audio/2022/03/10/audio_270f41e9bd.mp3', '1', '1', '2026-06-26 03:40:24');
INSERT INTO `music_tracks` (`id`, `title`, `category`, `cover_url`, `audio_url`, `is_active`, `created_by`, `created_at`) VALUES ('6', 'Morning Light', 'energy', 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=600&q=80', 'https://cdn.pixabay.com/download/audio/2021/11/25/audio_91b32d278e.mp3', '1', '1', '2026-06-26 03:40:24');
INSERT INTO `music_tracks` (`id`, `title`, `category`, `cover_url`, `audio_url`, `is_active`, `created_by`, `created_at`) VALUES ('7', 'River Flow', 'water', 'https://images.unsplash.com/photo-1501854140801-50d01698950b?w=600&q=80', 'https://cdn.pixabay.com/download/audio/2022/03/24/audio_946df0d016.mp3', '1', '1', '2026-06-26 03:40:24');
INSERT INTO `music_tracks` (`id`, `title`, `category`, `cover_url`, `audio_url`, `is_active`, `created_by`, `created_at`) VALUES ('8', 'Tibetan Bowls', 'bowl', 'https://images.unsplash.com/photo-1545389336-cf090694435e?w=600&q=80', 'https://cdn.pixabay.com/download/audio/2022/03/10/audio_270f41e9bd.mp3', '1', '1', '2026-06-26 03:40:24');

-- --------------------------------------------------------
-- Table structure for `password_resets`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `otp_expires` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `recurring_slots`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `recurring_slots`;
CREATE TABLE `recurring_slots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `counselor_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `day_of_week` tinyint(1) NOT NULL COMMENT '0=Sunday .. 6=Saturday',
  `slot_time` varchar(20) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `counselor_id` (`counselor_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `recurring_slots_ibfk_1` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`id`),
  CONSTRAINT `recurring_slots_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `recurring_slots`

INSERT INTO `recurring_slots` (`id`, `counselor_id`, `student_id`, `day_of_week`, `slot_time`, `reason`, `is_active`, `created_at`) VALUES ('1', '4', '2', '3', '3:00 PM', 'Ongoing weekly support', '1', '2026-08-14 09:44:30');

-- --------------------------------------------------------
-- Table structure for `relaxation_exercises`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `relaxation_exercises`;
CREATE TABLE `relaxation_exercises` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('breathing','meditation') NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(10) DEFAULT NULL,
  `phases` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`phases`)),
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `relaxation_exercises_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `relaxation_exercises`

INSERT INTO `relaxation_exercises` (`id`, `type`, `name`, `description`, `icon`, `phases`, `is_active`, `created_by`, `created_at`) VALUES ('1', 'breathing', '4-7-8 Breathing', 'Inhale for 4 counts, hold for 7, exhale for 8. Reduces anxiety instantly.', '😮‍💨', '[{\"text\":\"Breathe In\",\"voice\":\"Breathe in slowly through your nose\",\"dur\":4,\"scale\":\"1.35\",\"bg\":\"#98E3B4\"},{\"text\":\"Hold\",\"voice\":\"Hold gently\",\"dur\":7,\"scale\":\"1.35\",\"bg\":\"#E9DBC4\"},{\"text\":\"Breathe Out\",\"voice\":\"Exhale completely through your mouth\",\"dur\":8,\"scale\":\"1.0\",\"bg\":\"#d0e8f5\"}]', '1', '1', '2026-06-26 03:40:24');
INSERT INTO `relaxation_exercises` (`id`, `type`, `name`, `description`, `icon`, `phases`, `is_active`, `created_by`, `created_at`) VALUES ('2', 'breathing', 'Box Breathing', 'Inhale 4, hold 4, exhale 4, hold 4. Used by Navy SEALs for focus under pressure.', '⬜', '[{\"text\":\"Breathe In\",\"voice\":\"Inhale slowly\",\"dur\":4,\"scale\":\"1.35\",\"bg\":\"#B3C6E7\"},{\"text\":\"Hold\",\"voice\":\"Hold\",\"dur\":4,\"scale\":\"1.35\",\"bg\":\"#E9DBC4\"},{\"text\":\"Breathe Out\",\"voice\":\"Exhale slowly\",\"dur\":4,\"scale\":\"1.0\",\"bg\":\"#d0e8f5\"},{\"text\":\"Hold\",\"voice\":\"Hold again\",\"dur\":4,\"scale\":\"1.0\",\"bg\":\"#f5e0d0\"}]', '1', '1', '2026-06-26 03:40:24');
INSERT INTO `relaxation_exercises` (`id`, `type`, `name`, `description`, `icon`, `phases`, `is_active`, `created_by`, `created_at`) VALUES ('3', 'breathing', 'Deep Belly', 'Breathe deeply into your belly. Activates natural relaxation response.', '🫁', '[{\"text\":\"Belly In\",\"voice\":\"Breathe deep into your belly\",\"dur\":5,\"scale\":\"1.4\",\"bg\":\"#C8E6C9\"},{\"text\":\"Breathe Out\",\"voice\":\"Release slowly and fully\",\"dur\":6,\"scale\":\"1.0\",\"bg\":\"#d0e8f5\"}]', '1', '1', '2026-06-26 03:40:24');
INSERT INTO `relaxation_exercises` (`id`, `type`, `name`, `description`, `icon`, `phases`, `is_active`, `created_by`, `created_at`) VALUES ('4', 'breathing', 'Energising', 'Short sharp inhales followed by full release. Wakes up body and mind.', '⚡', '[{\"text\":\"Quick In\",\"voice\":\"Sharp inhale\",\"dur\":2,\"scale\":\"1.2\",\"bg\":\"#FFE0B2\"},{\"text\":\"Quick In\",\"voice\":\"And again\",\"dur\":2,\"scale\":\"1.35\",\"bg\":\"#FFD08A\"},{\"text\":\"Release\",\"voice\":\"Full exhale — release\",\"dur\":4,\"scale\":\"1.0\",\"bg\":\"#d0e8f5\"}]', '1', '1', '2026-06-26 03:40:24');
INSERT INTO `relaxation_exercises` (`id`, `type`, `name`, `description`, `icon`, `phases`, `is_active`, `created_by`, `created_at`) VALUES ('5', 'breathing', 'Sleep Breath', 'Long slow exhales prepare body for rest.', '🌙', '[{\"text\":\"Breathe In\",\"voice\":\"Inhale gently\",\"dur\":4,\"scale\":\"1.3\",\"bg\":\"#B0C4DE\"},{\"text\":\"Breathe Out\",\"voice\":\"Exhale slowly and completely\",\"dur\":8,\"scale\":\"1.0\",\"bg\":\"#d0e8f5\"},{\"text\":\"Rest\",\"voice\":\"Rest... let your body feel heavy\",\"dur\":3,\"scale\":\"1.0\",\"bg\":\"#e8e0f5\"}]', '1', '1', '2026-06-26 03:40:24');

-- --------------------------------------------------------
-- Table structure for `resources`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `resources`;
CREATE TABLE `resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uploaded_by` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `resource_type` enum('article','audio','video') NOT NULL,
  `file_path` varchar(300) DEFAULT NULL,
  `external_url` varchar(500) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `resources`

INSERT INTO `resources` (`id`, `uploaded_by`, `title`, `description`, `resource_type`, `file_path`, `external_url`, `is_published`, `created_at`) VALUES ('2', '4', 'awvgssvgde', 'aegvedzsvgbed', 'article', '../../assets/images/resources/resource_be75a332de071ace.jpg', NULL, '1', '2026-08-13 22:56:30');
INSERT INTO `resources` (`id`, `uploaded_by`, `title`, `description`, `resource_type`, `file_path`, `external_url`, `is_published`, `created_at`) VALUES ('3', '4', 'hjfyrfuhjvhjfyufh', 'xfxgsrtdryugkjvcxstdrtugkjvcxzgartsw5ey6ryfujhvczgartw5etd', 'article', 'http://localhost/mindcare_final/backend/uploads/resources/cover_0deca7dd05e9688f.jpg', 'https://www.health.harvard.edu/topics/mental-health/all', '1', '2026-08-14 10:11:56');
INSERT INTO `resources` (`id`, `uploaded_by`, `title`, `description`, `resource_type`, `file_path`, `external_url`, `is_published`, `created_at`) VALUES ('4', '4', 'fgvfuhj', 'fyt7uguigukgkgkg', 'article', 'http://localhost/mindcare_final/backend/uploads/resources/cover_62d0dec3aa596e5b.webp', 'https://www.psychiatrictimes.com/', '1', '2026-08-14 11:48:30');

-- --------------------------------------------------------
-- Table structure for `risk_alerts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `risk_alerts`;
CREATE TABLE `risk_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `diary_entry_id` int(11) DEFAULT NULL,
  `keywords_found` text DEFAULT NULL,
  `severity` enum('medium','high','critical') NOT NULL,
  `status` enum('open','reviewed') DEFAULT 'open',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `risk_alerts_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `system_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(200) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_pinned` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `system_logs`

INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('1', '2', 'login', '::1', '2026-06-24 14:18:48', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('2', '2', 'login', '::1', '2026-06-24 14:34:19', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('3', '2', 'login', '::1', '2026-06-25 09:10:51', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('4', '4', 'login', '::1', '2026-06-25 09:12:48', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('5', '4', 'login', '::1', '2026-06-25 09:14:02', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('6', '6', 'login', '::1', '2026-06-25 09:16:25', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('7', '4', 'login', '::1', '2026-06-25 09:18:13', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('8', '1', 'login', '::1', '2026-06-25 09:19:04', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('9', '4', 'login', '::1', '2026-06-25 10:11:07', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('10', '2', 'login', '::1', '2026-06-25 11:42:14', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('11', '4', 'login', '::1', '2026-06-25 11:49:18', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('12', '4', 'login', '::1', '2026-06-25 11:49:54', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('13', '6', 'login', '::1', '2026-06-25 11:51:38', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('14', '1', 'login', '::1', '2026-06-25 11:52:39', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('15', '4', 'login', '::1', '2026-06-25 11:56:05', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('16', '2', 'login', '::1', '2026-06-25 22:35:39', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('17', '4', 'login', '::1', '2026-06-28 10:17:56', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('18', '2', 'login', '::1', '2026-06-28 10:41:50', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('19', '4', 'login', '::1', '2026-06-28 10:57:53', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('20', '2', 'login', '::1', '2026-06-28 12:26:21', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('21', '4', 'login', '::1', '2026-06-28 12:27:11', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('22', '6', 'login', '::1', '2026-06-28 12:27:42', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('23', '6', 'login', '::1', '2026-06-28 12:28:09', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('24', '1', 'login', '::1', '2026-06-28 12:28:27', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('25', '4', 'login', '::1', '2026-06-28 12:33:31', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('26', '2', 'login', '::1', '2026-06-28 12:43:48', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('27', '2', 'login', '::1', '2026-06-28 13:54:49', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('28', '4', 'login', '::1', '2026-06-28 14:04:32', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('29', '6', 'login', '::1', '2026-06-28 14:06:46', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('30', '1', 'login', '::1', '2026-06-28 14:09:13', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('31', '2', 'login', '::1', '2026-06-30 08:31:08', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('32', '4', 'login', '::1', '2026-06-30 08:32:50', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('33', '6', 'login', '::1', '2026-06-30 08:33:45', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('34', '1', 'login', '::1', '2026-06-30 08:34:19', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('35', '4', 'login', '::1', '2026-06-30 08:43:03', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('36', '2', 'login', '::1', '2026-06-30 08:44:33', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('37', '4', 'login', '::1', '2026-06-30 08:46:17', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('38', '4', 'login', '::1', '2026-08-12 13:22:22', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('39', '1', 'login', '::1', '2026-08-12 14:50:56', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('40', '1', 'login', '::1', '2026-08-12 14:54:22', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('41', '6', 'login', '::1', '2026-08-12 14:58:28', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('42', '2', 'login', '::1', '2026-08-12 15:03:32', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('43', '4', 'login', '::1', '2026-08-12 15:04:58', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('44', '4', 'login', '::1', '2026-08-12 15:58:54', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('45', '1', 'login', '::1', '2026-08-12 15:59:24', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('46', '4', 'login', '::1', '2026-08-13 00:58:40', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('47', '2', 'login', '::1', '2026-08-13 10:43:40', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('48', '4', 'login', '::1', '2026-08-13 10:44:22', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('49', '2', 'login', '::1', '2026-08-13 17:39:32', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('50', '4', 'login', '::1', '2026-08-13 22:27:19', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('51', '4', 'login', '::1', '2026-08-14 09:42:26', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('52', '2', 'login', '::1', '2026-08-14 09:43:16', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('53', '4', 'login', '::1', '2026-08-14 09:43:56', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('54', '2', 'login', '::1', '2026-08-14 10:00:09', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('55', '4', 'login', '::1', '2026-08-14 10:09:26', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('56', '4', 'login', '::1', '2026-08-14 11:04:02', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('57', '4', 'login', '::1', '2026-08-14 11:47:30', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('58', '4', 'login', '::1', '2026-08-14 11:49:19', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('59', '2', 'login', '::1', '2026-08-14 11:53:36', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('60', '4', 'login', '::1', '2026-08-14 11:54:21', '0');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `created_at`, `is_pinned`) VALUES ('61', '6', 'login', '::1', '2026-08-14 12:51:07', '0');

-- --------------------------------------------------------
-- Table structure for `system_settings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `users`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','counselor','learning_advisor','admin') NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `specialty` varchar(150) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `share_status_with_guardian` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `student_id` (`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `users`

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `student_id`, `phone`, `photo_url`, `specialty`, `is_active`, `created_at`, `share_status_with_guardian`) VALUES ('1', 'System Admin', 'admin@edu.lnbti.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, NULL, NULL, NULL, '1', '2026-06-20 12:14:15', '1');
INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `student_id`, `phone`, `photo_url`, `specialty`, `is_active`, `created_at`, `share_status_with_guardian`) VALUES ('2', 'Heshali Kaluarachchi', 'Heshali.uog09@edu.lnbti.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'UOG0923007', NULL, NULL, NULL, '1', '2026-06-20 12:14:16', '1');
INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `student_id`, `phone`, `photo_url`, `specialty`, `is_active`, `created_at`, `share_status_with_guardian`) VALUES ('3', 'Tharushi Devmini', 'tharushi.old@edu.lnbti.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'UOG0923003', NULL, NULL, NULL, '1', '2026-06-20 12:14:16', '1');
INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `student_id`, `phone`, `photo_url`, `specialty`, `is_active`, `created_at`, `share_status_with_guardian`) VALUES ('4', 'Miss Dhanushi Perera', 'Dhanushi@edu.lnbti.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'counselor', NULL, '+94 70 539 8145', 'dhanushi.jpg', 'Head Counselor', '1', '2026-06-20 12:14:16', '1');
INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `student_id`, `phone`, `photo_url`, `specialty`, `is_active`, `created_at`, `share_status_with_guardian`) VALUES ('5', 'Miss Mekala Harshani', 'mekala@edu.lnbti.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'counselor', NULL, '076 450 4263', 'mekala.jpg', 'Assistant Counselor', '1', '2026-06-20 12:14:16', '1');
INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `student_id`, `phone`, `photo_url`, `specialty`, `is_active`, `created_at`, `share_status_with_guardian`) VALUES ('6', 'Dr. Karunarathna', 'learningadvisor@edu.lnbti.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'learning_advisor', NULL, NULL, NULL, NULL, '1', '2026-06-20 12:14:16', '1');

SET FOREIGN_KEY_CHECKS = 1;
