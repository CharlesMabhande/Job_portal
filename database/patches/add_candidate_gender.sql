-- Gender on candidate profile (Male, Female, Other). Run on existing databases.

ALTER TABLE `candidates`
  ADD COLUMN `gender` VARCHAR(20) DEFAULT NULL COMMENT 'Male, Female, Other' AFTER `date_of_birth`;
