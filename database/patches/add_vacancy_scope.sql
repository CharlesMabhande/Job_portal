-- Internal vs external vacancy (open to staff only vs public).
-- Run once on existing databases: mysql -u root -p job_portal < database/patches/add_vacancy_scope.sql

ALTER TABLE `jobs`
  ADD COLUMN `vacancy_scope` ENUM('Internal','External') NOT NULL DEFAULT 'External'
  AFTER `job_type`;
