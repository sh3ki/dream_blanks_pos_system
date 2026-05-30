-- Migration: Add archived_at to project_lineups + archive permission

ALTER TABLE `project_lineups`
  ADD COLUMN `archived_at` DATETIME NULL DEFAULT NULL AFTER `authorized_approval`;

-- Add archive permission
INSERT IGNORE INTO `permissions` (`module`, `action`, `description`) VALUES
('project_lineup', 'archive', 'Archive/unarchive project lineup entries');

-- Grant to Admin role (role id = 1)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions`
WHERE module = 'project_lineup' AND action = 'archive';
