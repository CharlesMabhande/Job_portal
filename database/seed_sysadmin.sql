-- Seed an initial SysAdmin user
-- IMPORTANT: change this password immediately after first login.

USE `job_portal`;

INSERT INTO `users` (`email`, `password`, `role_id`, `first_name`, `last_name`, `phone`, `is_active`, `email_verified`)
SELECT
  'admin@university.edu',
  '$2y$12$jKhCaqNbTZhXUv1ZDn8UM.HeqgkyQeSJoRu5Pu4tbaxMPCDE.zgES',
  4,
  'System',
  'Administrator',
  NULL,
  1,
  1
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `email` = 'admin@university.edu');

