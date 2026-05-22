-- Migration: Project Lineup Table + Permissions
-- Run AFTER schema.sql and seed.sql

-- Project Lineups Table
CREATE TABLE IF NOT EXISTS `project_lineups` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `invoice_id` INT NOT NULL,
    `date` DATE NOT NULL,
    `brand_name` VARCHAR(255) NULL,
    `categories` TEXT NULL,
    `types` TEXT NULL,
    `qty` INT NOT NULL DEFAULT 0,
    `deadline` DATE NULL,
    `project_status` ENUM('pending','ongoing','for_releasing','released','completed') NOT NULL DEFAULT 'pending',
    `tshirt_status` ENUM('pending','ongoing','completed') NOT NULL DEFAULT 'pending',
    `tags_status` ENUM('pending','ongoing','completed') NOT NULL DEFAULT 'pending',
    `print_status` ENUM('pending','ongoing','completed') NOT NULL DEFAULT 'pending',
    `label_attached_status` ENUM('pending','ongoing','completed') NOT NULL DEFAULT 'pending',
    `qc_packing_status` ENUM('pending','ongoing','completed') NOT NULL DEFAULT 'pending',
    `authorized_approval` ENUM('pending','approved') NOT NULL DEFAULT 'pending',
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_invoice` (`invoice_id`),
    INDEX `idx_date` (`date`),
    INDEX `idx_project_status` (`project_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- New Permissions
INSERT IGNORE INTO `permissions` (`module`, `action`, `description`) VALUES
('project_lineup', 'view',   'View project lineup page'),
('project_lineup', 'add',    'Add project lineup entries'),
('project_lineup', 'edit',   'Edit project lineup entries'),
('project_lineup', 'delete', 'Delete project lineup entries'),
('invoices',       'forward','Forward invoice to project lineup');

-- Grant all new permissions to Admin role (role id = 1)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions`
WHERE (module = 'project_lineup') OR (module = 'invoices' AND action = 'forward');
