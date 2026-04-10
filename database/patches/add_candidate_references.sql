-- Professional / character references for candidates (profile).
-- Run once on existing databases.

CREATE TABLE IF NOT EXISTS `candidate_references` (
  `reference_id` int(11) NOT NULL AUTO_INCREMENT,
  `candidate_id` int(11) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `job_title` varchar(200) DEFAULT NULL COMMENT 'Role or relationship, e.g. Former manager',
  `organisation` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`reference_id`),
  KEY `candidate_id` (`candidate_id`),
  CONSTRAINT `candidate_references_ibfk_1` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`candidate_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
