-- Structured qualifications (JSON arrays) and work experience on candidate profile.
-- Each JSON array is a list of objects: institution/school, title/subject, grade, year (strings).

ALTER TABLE `candidates`
  ADD COLUMN `professional_qualifications` TEXT NULL COMMENT 'JSON: professional quals' AFTER `education`,
  ADD COLUMN `o_level_qualifications` TEXT NULL COMMENT 'JSON: O-Level' AFTER `professional_qualifications`,
  ADD COLUMN `a_level_qualifications` TEXT NULL COMMENT 'JSON: A-Level' AFTER `o_level_qualifications`,
  ADD COLUMN `other_certifications` TEXT NULL COMMENT 'JSON: other certs' AFTER `a_level_qualifications`;

-- Note: `experience` column already exists (JSON array of work history).
