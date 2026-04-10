-- Signed export registry (summary tables, etc.): verification URL + HMAC integrity.

CREATE TABLE IF NOT EXISTS `signed_exports` (
  `token` char(32) NOT NULL COMMENT 'hex, no separators',
  `export_type` varchar(64) NOT NULL,
  `job_id` int(11) DEFAULT NULL,
  `canonical_sha256` char(64) NOT NULL,
  `signature_hmac` char(64) NOT NULL,
  `payload_json` text DEFAULT NULL COMMENT 'Display metadata (job title, counts, …)',
  `issued_at` datetime NOT NULL,
  `issued_by_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`token`),
  KEY `idx_signed_exports_job` (`job_id`),
  KEY `idx_signed_exports_issued` (`issued_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
