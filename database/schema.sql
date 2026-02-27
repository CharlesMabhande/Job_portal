-- University Job Application Portal Database Schema
-- MySQL/MariaDB

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Database: job_portal
CREATE DATABASE IF NOT EXISTS `job_portal` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `job_portal`;

-- Table: roles
CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `permissions` text DEFAULT NULL COMMENT 'JSON permissions',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default roles
INSERT INTO `roles` (`role_id`, `role_name`, `permissions`) VALUES
(1, 'Candidate', '{"apply_jobs": true, "view_profile": true, "track_applications": true}'),
(2, 'HR', '{"create_jobs": true, "view_applications": true, "shortlist": true, "reject": true, "schedule_interviews": true, "view_reports": true}'),
(3, 'Management', '{"view_dashboard": true, "approve_jobs": true, "approve_offers": true, "view_reports": true, "view_analytics": true}'),
(4, 'SysAdmin', '{"full_access": true, "manage_users": true, "manage_roles": true, "system_settings": true, "database_backup": true, "security_monitoring": true}');

-- Table: users
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL DEFAULT 1,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  KEY `is_active` (`is_active`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: candidates (extends users for candidate-specific data)
CREATE TABLE `candidates` (
  `candidate_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `cv_path` varchar(255) DEFAULT NULL,
  `certificates_path` varchar(255) DEFAULT NULL,
  `cover_letter_template` text DEFAULT NULL,
  `skills` text DEFAULT NULL COMMENT 'JSON array of skills',
  `education` text DEFAULT NULL COMMENT 'JSON array of education',
  `experience` text DEFAULT NULL COMMENT 'JSON array of experience',
  `profile_completed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`candidate_id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `candidates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: jobs
CREATE TABLE `jobs` (
  `job_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `description` text NOT NULL,
  `requirements` text DEFAULT NULL,
  `qualifications` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `job_type` enum('Full-time','Part-time','Contract','Internship') DEFAULT 'Full-time',
  `salary_min` decimal(10,2) DEFAULT NULL,
  `salary_max` decimal(10,2) DEFAULT NULL,
  `posted_by` int(11) NOT NULL COMMENT 'HR user_id',
  `status` enum('Draft','Pending Approval','Active','Closed','Cancelled') DEFAULT 'Draft',
  `approved_by` int(11) DEFAULT NULL COMMENT 'Management user_id',
  `approved_at` datetime DEFAULT NULL,
  `application_deadline` date DEFAULT NULL,
  `max_applications` int(11) DEFAULT NULL,
  `current_applications` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`job_id`),
  KEY `posted_by` (`posted_by`),
  KEY `approved_by` (`approved_by`),
  KEY `status` (`status`),
  FULLTEXT KEY `search_index` (`title`,`description`,`requirements`),
  CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT,
  CONSTRAINT `jobs_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: applications
CREATE TABLE `applications` (
  `application_id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `cv_path` varchar(255) DEFAULT NULL,
  `certificates_path` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Under Review','Shortlisted','Interview Scheduled','Rejected','Offer Extended','Accepted','Withdrawn') DEFAULT 'Pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL COMMENT 'HR user_id',
  `reviewed_at` datetime DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`application_id`),
  UNIQUE KEY `unique_application` (`job_id`,`candidate_id`),
  KEY `candidate_id` (`candidate_id`),
  KEY `status` (`status`),
  KEY `reviewed_by` (`reviewed_by`),
  CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE,
  CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`candidate_id`) ON DELETE CASCADE,
  CONSTRAINT `applications_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: interviews
CREATE TABLE `interviews` (
  `interview_id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `scheduled_by` int(11) NOT NULL COMMENT 'HR user_id',
  `interview_type` enum('Phone','Video','In-person','Panel') DEFAULT 'In-person',
  `scheduled_date` datetime NOT NULL,
  `duration_minutes` int(11) DEFAULT 60,
  `location` varchar(255) DEFAULT NULL,
  `meeting_link` varchar(500) DEFAULT NULL,
  `interviewer_notes` text DEFAULT NULL,
  `status` enum('Scheduled','Completed','Cancelled','Rescheduled') DEFAULT 'Scheduled',
  `feedback` text DEFAULT NULL,
  `rating` int(11) DEFAULT NULL COMMENT '1-5 rating',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`interview_id`),
  KEY `application_id` (`application_id`),
  KEY `scheduled_by` (`scheduled_by`),
  KEY `scheduled_date` (`scheduled_date`),
  CONSTRAINT `interviews_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE,
  CONSTRAINT `interviews_ibfk_2` FOREIGN KEY (`scheduled_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: notifications
CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL COMMENT 'job_id, application_id, etc.',
  `related_type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: audit_logs
CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` text DEFAULT NULL COMMENT 'JSON',
  `new_values` text DEFAULT NULL COMMENT 'JSON',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: system_settings
CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('site_name', 'University Job Portal', 'Name of the job portal'),
('site_email', 'noreply@university.edu', 'Default email address'),
('max_file_size', '5242880', 'Maximum file upload size in bytes (5MB)'),
('allowed_file_types', 'pdf,doc,docx', 'Allowed file extensions for uploads'),
('email_notifications_enabled', '1', 'Enable/disable email notifications'),
('maintenance_mode', '0', 'Enable/disable maintenance mode');

-- Table: job_alerts
CREATE TABLE `job_alerts` (
  `alert_id` int(11) NOT NULL AUTO_INCREMENT,
  `candidate_id` int(11) NOT NULL,
  `keywords` text DEFAULT NULL,
  `job_type` varchar(50) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`alert_id`),
  KEY `candidate_id` (`candidate_id`),
  CONSTRAINT `job_alerts_ibfk_1` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`candidate_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
