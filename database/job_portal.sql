-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 01, 2026 at 01:50 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `job_portal`
--
CREATE DATABASE IF NOT EXISTS `job_portal` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `job_portal`;

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `application_id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`application_id`, `job_id`, `candidate_id`, `cover_letter`, `cv_path`, `certificates_path`, `status`, `applied_at`, `reviewed_by`, `reviewed_at`, `review_notes`, `rejection_reason`, `updated_at`) VALUES
(1, 1, 3, 'hhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhh', 'cv/cv_1773999212_6dc04c6c868ddd8a.docx', 'documents/certs_1773999212_bc2e721ff1babd47.pdf', 'Interview Scheduled', '2026-03-20 09:33:32', 2, '2026-03-20 15:00:58', '', '', '2026-03-20 13:30:41'),
(2, 1, 4, 'mashurotat@gmail.com', 'cv/cv_1775042365_f67b5adfaddd811d.pdf', 'documents/certs_1775042365_277d35c374e7178d.pdf', 'Pending', '2026-04-01 11:20:36', NULL, NULL, NULL, NULL, '2026-04-01 11:20:36');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` text DEFAULT NULL COMMENT 'JSON',
  `new_values` text DEFAULT NULL COMMENT 'JSON',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`log_id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, NULL, 'user_registered', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-02-27 10:42:51'),
(2, 2, 'user_login', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-02-27 10:43:01'),
(3, 2, 'profile_updated', 'candidates', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-02-27 10:47:32'),
(4, 2, 'user_logout', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-02-27 10:48:01'),
(5, 1, 'user_login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-02-27 10:49:31'),
(6, 1, 'user_updated_by_sysadmin', 'users', 2, NULL, '{\"role_id\":2,\"is_active\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-02-27 10:51:49'),
(7, 1, 'user_logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-02-27 10:52:02'),
(8, 2, 'user_login', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-02-27 10:52:21'),
(9, 2, 'user_logout', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-02-27 10:53:16'),
(10, 1, 'user_login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-02-27 11:25:37'),
(11, 1, 'user_logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-02-27 12:57:09'),
(12, 2, 'user_login', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-02-27 13:40:45'),
(13, NULL, 'user_registered', 'users', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-02-27 14:26:08'),
(14, 3, 'user_login', 'users', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-02-27 14:26:11'),
(15, 3, 'user_logout', 'users', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-02-27 14:32:53'),
(16, 2, 'user_login', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 15:04:49'),
(17, 2, 'user_login', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-19 13:21:58'),
(18, 2, 'user_logout', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-19 13:22:25'),
(19, 2, 'user_login', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-19 14:36:45'),
(20, 2, 'user_logout', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-19 14:37:28'),
(21, 2, 'user_login', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-19 14:49:43'),
(22, 2, 'user_login', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 07:36:00'),
(23, 2, 'user_logout', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 07:36:35'),
(24, 2, 'user_login', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 08:09:01'),
(25, 2, 'user_logout', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 08:14:09'),
(26, NULL, 'user_registered', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 08:48:27'),
(27, 4, 'user_login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 08:48:33'),
(28, 4, 'profile_updated', 'candidates', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 08:50:41'),
(29, 4, 'profile_updated', 'candidates', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 09:16:10'),
(30, 4, 'user_logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 09:20:12'),
(31, 2, 'user_login', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 09:20:19'),
(32, 2, 'job_created', 'jobs', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 09:24:42'),
(33, 2, 'user_logout', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 09:25:20'),
(34, 2, 'user_login', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 09:26:06'),
(35, 2, 'job_updated', 'jobs', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 09:31:52'),
(36, 2, 'user_logout', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 09:31:56'),
(37, 4, 'user_login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 09:32:25'),
(38, 4, 'application_created', 'applications', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 09:33:52'),
(39, 4, 'user_logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 09:34:31'),
(40, 4, 'user_login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 09:34:45'),
(41, 4, 'user_logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 09:56:59'),
(42, 2, 'user_login', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 09:57:06'),
(43, 2, 'application_status_updated', 'applications', 1, '{\"status\":\"Pending\"}', '{\"status\":\"Shortlisted\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 12:58:37'),
(44, 2, 'user_logout', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 12:59:00'),
(45, 4, 'user_login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 12:59:09'),
(46, 4, 'user_logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 13:00:14'),
(47, 2, 'user_login', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 13:00:23'),
(48, 2, 'application_status_updated', 'applications', 1, '{\"status\":\"Shortlisted\"}', '{\"status\":\"Under Review\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 13:01:03'),
(49, 2, 'user_logout', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 13:01:08'),
(50, 4, 'user_login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 13:01:25'),
(51, 4, 'user_logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 13:01:38'),
(52, 2, 'user_login', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 13:16:54'),
(53, 2, 'interview_scheduled', 'interviews', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 13:30:43'),
(54, 2, 'user_logout', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 13:31:08'),
(55, 4, 'user_login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 13:31:44'),
(56, 4, 'user_logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 13:32:40'),
(57, 2, 'user_login', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 13:32:50'),
(58, 2, 'user_logout', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 13:54:06'),
(59, 1, 'user_login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 13:56:26'),
(60, 1, 'system_setting_updated', 'system_settings', NULL, NULL, '{\"setting_key\":\"allowed_file_types\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 14:01:33'),
(61, 1, 'system_setting_updated', 'system_settings', NULL, NULL, '{\"setting_key\":\"site_name\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 14:02:57'),
(62, 1, 'user_logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 14:15:39'),
(63, 2, 'user_login', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 14:16:55'),
(64, 2, 'user_logout', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 14:25:03'),
(65, 2, 'user_login', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 14:27:24'),
(66, 2, 'user_logout', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 14:27:51'),
(67, 4, 'user_login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-20 14:56:16'),
(68, 1, 'user_login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-21 07:52:24'),
(69, 1, 'user_logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-21 07:54:10'),
(70, 1, 'user_logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-21 07:54:49'),
(71, 1, 'user_login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-01 10:58:58'),
(72, 1, 'user_logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-01 10:59:07'),
(73, 2, 'user_login', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-01 10:59:18'),
(74, 2, 'user_logout', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-01 10:59:24'),
(75, 4, 'user_login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-01 10:59:38'),
(76, 4, 'user_logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-01 11:10:41'),
(77, NULL, 'user_registered', 'users', 5, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-01 11:17:25'),
(78, 5, 'user_login', 'users', 5, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-01 11:17:30'),
(79, 5, 'profile_updated', 'candidates', 5, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-01 11:19:25'),
(80, 5, 'application_created', 'applications', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-01 11:20:39'),
(81, 5, 'user_logout', 'users', 5, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-01 11:21:35'),
(82, 2, 'user_login', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-01 11:21:43');

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

CREATE TABLE `candidates` (
  `candidate_id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `candidates`
--

INSERT INTO `candidates` (`candidate_id`, `user_id`, `date_of_birth`, `address`, `city`, `state`, `country`, `postal_code`, `cv_path`, `certificates_path`, `cover_letter_template`, `skills`, `education`, `experience`, `profile_completed`, `created_at`, `updated_at`) VALUES
(1, 2, '1996-11-12', '5029 Skyview', 'Chivhu', 'Mashonaland East', 'Zimbabwe', '00000', 'cv/cv_1772189252_98c4b19a8d008d9c.doc', NULL, NULL, NULL, NULL, NULL, 1, '2026-02-27 10:42:51', '2026-02-27 10:47:32'),
(2, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-02-27 14:26:08', '2026-02-27 14:26:08'),
(3, 4, '1999-11-02', '78 Mukoba 6', 'Gweru', 'Midlands', 'Zimbabwe', '00000', 'cv/cv_1773999212_6dc04c6c868ddd8a.docx', 'documents/certs_1773998170_fc3582c47ea8bdc1.pdf', NULL, NULL, NULL, NULL, 1, '2026-03-20 08:48:27', '2026-03-20 09:33:32'),
(4, 5, '2000-12-11', '50867 M EX', 'Chivhu', 'Mashonaland East', 'Zimbabwe', '00000', 'cv/cv_1775042365_f67b5adfaddd811d.pdf', 'documents/certs_1775042365_277d35c374e7178d.pdf', NULL, NULL, NULL, NULL, 1, '2026-04-01 11:17:25', '2026-04-01 11:19:25');

-- --------------------------------------------------------

--
-- Table structure for table `interviews`
--

CREATE TABLE `interviews` (
  `interview_id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `interviews`
--

INSERT INTO `interviews` (`interview_id`, `application_id`, `scheduled_by`, `interview_type`, `scheduled_date`, `duration_minutes`, `location`, `meeting_link`, `interviewer_notes`, `status`, `feedback`, `rating`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'In-person', '2026-06-12 09:00:00', 120, 'Main Campus', '', NULL, 'Scheduled', NULL, NULL, '2026-03-20 13:30:41', '2026-03-20 13:30:41');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `job_id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`job_id`, `title`, `department`, `description`, `requirements`, `qualifications`, `location`, `job_type`, `salary_min`, `salary_max`, `posted_by`, `status`, `approved_by`, `approved_at`, `application_deadline`, `max_applications`, `current_applications`, `created_at`, `updated_at`) VALUES
(1, 'SOFTWARE DEVELOPER', 'ICTS Department', 'Software developer', 'Developing and Maintaining University Software.', 'BSc Hons in Computer Science or and Related Degree.', 'Main Campus', 'Full-time', NULL, NULL, 2, 'Active', NULL, NULL, '2026-06-06', 0, 2, '2026-03-20 09:24:42', '2026-04-01 11:20:36');

-- --------------------------------------------------------

--
-- Table structure for table `job_alerts`
--

CREATE TABLE `job_alerts` (
  `alert_id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `keywords` text DEFAULT NULL,
  `job_type` varchar(50) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL COMMENT 'job_id, application_id, etc.',
  `related_type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `type`, `title`, `message`, `related_id`, `related_type`, `is_read`, `created_at`) VALUES
(1, 4, 'application_submitted', 'Application Submitted', 'Your application for SOFTWARE DEVELOPER has been submitted.', 1, 'application', 0, '2026-03-20 09:33:32'),
(2, 4, 'application_status_changed', 'Application Status Updated', 'Your application for SOFTWARE DEVELOPER is now: Shortlisted', 1, 'application', 0, '2026-03-20 12:58:31'),
(3, 4, 'application_status_changed', 'Application Status Updated', 'Your application for SOFTWARE DEVELOPER is now: Under Review', 1, 'application', 0, '2026-03-20 13:00:58'),
(4, 4, 'interview_scheduled', 'Interview Scheduled', 'Your interview for SOFTWARE DEVELOPER has been scheduled.', 1, 'interview', 0, '2026-03-20 13:30:41'),
(5, 5, 'application_submitted', 'Application Submitted', 'Your application for SOFTWARE DEVELOPER has been submitted.', 2, 'application', 0, '2026-04-01 11:20:36');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `permissions` text DEFAULT NULL COMMENT 'JSON permissions',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `permissions`, `created_at`) VALUES
(1, 'Candidate', '{\"apply_jobs\": true, \"view_profile\": true, \"track_applications\": true}', '2026-02-27 10:39:13'),
(2, 'HR', '{\"create_jobs\": true, \"view_applications\": true, \"shortlist\": true, \"reject\": true, \"schedule_interviews\": true, \"view_reports\": true}', '2026-02-27 10:39:13'),
(3, 'Management', '{\"view_dashboard\": true, \"approve_jobs\": true, \"approve_offers\": true, \"view_reports\": true, \"view_analytics\": true}', '2026-02-27 10:39:13'),
(4, 'SysAdmin', '{\"full_access\": true, \"manage_users\": true, \"manage_roles\": true, \"system_settings\": true, \"database_backup\": true, \"security_monitoring\": true}', '2026-02-27 10:39:13');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `description`, `updated_by`, `updated_at`) VALUES
(1, 'site_name', 'LSU Job Portal', 'Name of the job portal', 1, '2026-03-20 14:02:57'),
(2, 'site_email', 'noreply@university.edu', 'Default email address', NULL, '2026-02-27 10:39:14'),
(3, 'max_file_size', '5242880', 'Maximum file upload size in bytes (5MB)', NULL, '2026-02-27 10:39:14'),
(4, 'allowed_file_types', 'pdf', 'Allowed file extensions for uploads', 1, '2026-03-20 14:01:33'),
(5, 'email_notifications_enabled', '1', 'Enable/disable email notifications', NULL, '2026-02-27 10:39:14'),
(6, 'maintenance_mode', '0', 'Enable/disable maintenance mode', NULL, '2026-02-27 10:39:14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password`, `role_id`, `first_name`, `last_name`, `phone`, `is_active`, `email_verified`, `verification_token`, `reset_token`, `reset_token_expiry`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin@university.edu', '$2y$12$jKhCaqNbTZhXUv1ZDn8UM.HeqgkyQeSJoRu5Pu4tbaxMPCDE.zgES', 4, 'System', 'Administrator', NULL, 1, 1, NULL, NULL, NULL, '2026-04-01 12:58:58', '2026-02-27 10:40:48', '2026-04-01 10:58:58'),
(2, 'charliemabhande@gmail.com', '$2y$12$aIKhNkEb9wpw.If1mGMyBOkgEJeLotsB90ywiR.AGEHQTfzYqiyT6', 2, 'Charles', 'Mabhande', '0776318768', 1, 0, '886162ec86869a92d1df2a0375a36e4f0369c0d8d2ade67412c303eb8a57c55f', NULL, NULL, '2026-04-01 13:21:43', '2026-02-27 10:42:51', '2026-04-01 11:21:43'),
(3, 'simon@gmail.com', '$2y$12$1AkfdtCE22uMiUcaeAoKt.vL3gbfuHPiSSJunTId3vYGqdpJrwn3a', 1, 'Simon', 'Gobvu', '0777777777', 1, 0, '93336f566fa67d1a9f480ac19529b3967a4d2fdc1ea7c9d7fd2d52d691ca340a', NULL, NULL, '2026-02-27 16:26:11', '2026-02-27 14:26:08', '2026-02-27 14:26:11'),
(4, 'schibwe@gmail.com', '$2y$12$fXArZhG7t7PAELKkavl.8ev8X7OILHP0y/4PNF8U0uaQpkEiq0qEe', 1, 'Samuel', 'Chibwe', '+2637123333334', 1, 0, 'aa6493b42ea7ca343b296437cfa013aeaa98f4f1663197a599475d1deaab2492', NULL, NULL, '2026-04-01 12:59:38', '2026-03-20 08:48:27', '2026-04-01 10:59:38'),
(5, 'mashurotat@gmail.com', '$2y$12$hodYtU1WRJYX/qFvj5n3FuCfaKJ1K33O31Ocf1hnKfWwfegKL2/mS', 1, 'Tatenda', 'Mashuro', '+2637123333334', 1, 0, 'ed66324dd77e8009d3bf52a446b5eca65aa00cd9de89ec4106c21572dcde31ef', NULL, NULL, '2026-04-01 13:17:30', '2026-04-01 11:17:25', '2026-04-01 11:17:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`application_id`),
  ADD UNIQUE KEY `unique_application` (`job_id`,`candidate_id`),
  ADD KEY `candidate_id` (`candidate_id`),
  ADD KEY `status` (`status`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action` (`action`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`candidate_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `interviews`
--
ALTER TABLE `interviews`
  ADD PRIMARY KEY (`interview_id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `scheduled_by` (`scheduled_by`),
  ADD KEY `scheduled_date` (`scheduled_date`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`job_id`),
  ADD KEY `posted_by` (`posted_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `status` (`status`);
ALTER TABLE `jobs` ADD FULLTEXT KEY `search_index` (`title`,`description`,`requirements`);

--
-- Indexes for table `job_alerts`
--
ALTER TABLE `job_alerts`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `candidate_id` (`candidate_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `is_active` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `candidates`
--
ALTER TABLE `candidates`
  MODIFY `candidate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `interviews`
--
ALTER TABLE `interviews`
  MODIFY `interview_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `job_alerts`
--
ALTER TABLE `job_alerts`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`candidate_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `candidates`
--
ALTER TABLE `candidates`
  ADD CONSTRAINT `candidates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `interviews`
--
ALTER TABLE `interviews`
  ADD CONSTRAINT `interviews_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `interviews_ibfk_2` FOREIGN KEY (`scheduled_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `jobs_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `job_alerts`
--
ALTER TABLE `job_alerts`
  ADD CONSTRAINT `job_alerts_ibfk_1` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`candidate_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
