-- Public application reference for tracking (e.g. LSU-2026-000042).
-- Run once: mysql -u root -p job_portal < database/patches/add_application_ref.sql

ALTER TABLE `applications`
  ADD COLUMN `application_ref` VARCHAR(32) NULL DEFAULT NULL AFTER `application_id`;

UPDATE `applications`
SET `application_ref` = CONCAT('LSU-', DATE_FORMAT(`applied_at`, '%Y'), '-', LPAD(`application_id`, 6, '0'))
WHERE `application_ref` IS NULL;

-- Nullable: new rows get a ref immediately after INSERT via PHP; UNIQUE allows multiple NULL only briefly if ever.
ALTER TABLE `applications`
  ADD UNIQUE KEY `uq_applications_ref` (`application_ref`);
