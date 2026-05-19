-- Dream Blanks POS System - Database Schema
-- Character Set: utf8mb4 | Engine: InnoDB
-- Run this file to create the complete database structure

SET NAMES utf8mb4;
SET time_zone = '+08:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `username` VARCHAR(100) UNIQUE NOT NULL,
    `email` VARCHAR(150) UNIQUE NOT NULL,
    `first_name` VARCHAR(50) NOT NULL,
    `middle_name` VARCHAR(50) NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `profile_photo_path` VARCHAR(255) NULL,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `last_login` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_username` (`username`),
    INDEX `idx_email` (`email`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Roles Table
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(100) UNIQUE NOT NULL,
    `description` TEXT NULL,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissions Table
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `module` VARCHAR(50) NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_permission` (`module`,`action`),
    INDEX `idx_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Roles Junction
CREATE TABLE IF NOT EXISTS `user_roles` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `role_id` INT NOT NULL,
    `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_role` (`user_id`,`role_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_role` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role Permissions Junction
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `role_id` INT NOT NULL,
    `permission_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_role_permission` (`role_id`,`permission_id`),
    INDEX `idx_role` (`role_id`),
    INDEX `idx_permission` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password Resets Table
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `email` VARCHAR(150) NOT NULL,
    `otp` VARCHAR(255) NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_email` (`email`),
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clients Table
CREATE TABLE IF NOT EXISTS `clients` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `first_name` VARCHAR(50) NOT NULL,
    `middle_name` VARCHAR(50) NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `email` VARCHAR(150) NULL,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_name` (`first_name`,`last_name`),
    INDEX `idx_email` (`email`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Client Addresses (up to 3 per client)
CREATE TABLE IF NOT EXISTS `client_addresses` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `client_id` INT NOT NULL,
    `address_type` ENUM('billing','shipping','home','work','other') DEFAULT 'billing',
    `street_address` VARCHAR(255) NOT NULL,
    `barangay` VARCHAR(100) NULL,
    `city` VARCHAR(100) NOT NULL,
    `province` VARCHAR(100) NULL,
    `postal_code` VARCHAR(20) NULL,
    `country` VARCHAR(100) DEFAULT 'Philippines',
    `is_primary` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
    INDEX `idx_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Client Contacts (up to 5 per client)
CREATE TABLE IF NOT EXISTS `client_contacts` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `client_id` INT NOT NULL,
    `contact_type` ENUM('mobile','landline','work','home','other') DEFAULT 'mobile',
    `contact_number` VARCHAR(20) NOT NULL,
    `is_primary` TINYINT(1) DEFAULT 0,
    `is_verified` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
    INDEX `idx_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(100) UNIQUE NOT NULL,
    `code` VARCHAR(20) UNIQUE NOT NULL,
    `description` TEXT NULL,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_name` (`name`),
    INDEX `idx_code` (`code`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Colors Table
CREATE TABLE IF NOT EXISTS `colors` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(50) UNIQUE NOT NULL,
    `hex_code` VARCHAR(7) NULL,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sizes Table
CREATE TABLE IF NOT EXISTS `sizes` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(50) UNIQUE NOT NULL,
    `code` VARCHAR(10) UNIQUE NOT NULL,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_name` (`name`),
    INDEX `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Types Table
CREATE TABLE IF NOT EXISTS `types` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(50) UNIQUE NOT NULL,
    `code` VARCHAR(20) UNIQUE NOT NULL,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_name` (`name`),
    INDEX `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products Table
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `sku` VARCHAR(100) UNIQUE NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `category_id` INT NULL,
    `color_id` INT NULL,
    `size_id` INT NULL,
    `type_id` INT NULL,
    `cost_price` DECIMAL(12,2) NOT NULL,
    `selling_price` DECIMAL(12,2) NOT NULL,
    `unit_type` ENUM('piece','box','dozen','kg','meter','liter','other') DEFAULT 'piece',
    `current_stock` INT DEFAULT 0,
    `low_stock_alert` INT NULL,
    `image_path` VARCHAR(255) NULL,
    `barcode` VARCHAR(100) NULL,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`color_id`) REFERENCES `colors`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`size_id`) REFERENCES `sizes`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`type_id`) REFERENCES `types`(`id`) ON DELETE SET NULL,
    INDEX `idx_sku` (`sku`),
    INDEX `idx_name` (`name`),
    INDEX `idx_category` (`category_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory Table
CREATE TABLE IF NOT EXISTS `inventory` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `product_id` INT UNIQUE NOT NULL,
    `quantity_on_hand` INT DEFAULT 0,
    `quantity_reserved` INT DEFAULT 0,
    `stock_status` ENUM('in_stock','low_stock','out_of_stock') DEFAULT 'in_stock',
    `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT NULL,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_product` (`product_id`),
    INDEX `idx_status` (`stock_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock Movements
CREATE TABLE IF NOT EXISTS `stock_movements` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `product_id` INT NOT NULL,
    `movement_type` ENUM('purchase','sale','adjustment','damage','loss') NOT NULL,
    `quantity_change` INT NOT NULL,
    `reason` TEXT NULL,
    `reference_id` INT NULL,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    INDEX `idx_product` (`product_id`),
    INDEX `idx_date` (`created_at`),
    INDEX `idx_type` (`movement_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Restock Orders
CREATE TABLE IF NOT EXISTS `restock_orders` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `order_number` VARCHAR(100) UNIQUE NOT NULL,
    `order_date` DATE NOT NULL,
    `delivery_date` DATE NULL,
    `supplier_name` VARCHAR(255) NULL,
    `delivery_status` ENUM('ordered','delivered','incomplete','problematic') DEFAULT 'ordered',
    `notes` TEXT NULL,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    INDEX `idx_order_date` (`order_date`),
    INDEX `idx_status` (`delivery_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Restock Items
CREATE TABLE IF NOT EXISTS `restock_items` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `restock_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity_requested` INT NOT NULL,
    `quantity_received` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`restock_id`) REFERENCES `restock_orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT,
    INDEX `idx_restock` (`restock_id`),
    INDEX `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoices Table
CREATE TABLE IF NOT EXISTS `invoices` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `invoice_number` VARCHAR(50) UNIQUE NOT NULL,
    `invoice_date` DATETIME NOT NULL,
    `client_id` INT NULL,
    `subtotal` DECIMAL(12,2) NOT NULL,
    `discount_amount` DECIMAL(12,2) DEFAULT 0,
    `discount_type` ENUM('fixed','percentage') NULL,
    `tax_amount` DECIMAL(12,2) DEFAULT 0,
    `tax_type` ENUM('fixed','percentage') NULL,
    `additional_fee` DECIMAL(12,2) DEFAULT 0,
    `total_amount` DECIMAL(12,2) NOT NULL,
    `total_paid` DECIMAL(12,2) DEFAULT 0,
    `payment_status` ENUM('fully_paid','partially_paid','unpaid') DEFAULT 'unpaid',
    `invoice_sent` ENUM('sent','not_sent') DEFAULT 'not_sent',
    `primary_payment_mode` ENUM('cash','bdo','gcash') NULL,
    `notes` TEXT NULL,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    INDEX `idx_invoice_number` (`invoice_number`),
    INDEX `idx_date` (`invoice_date`),
    INDEX `idx_client` (`client_id`),
    INDEX `idx_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoice Items
CREATE TABLE IF NOT EXISTS `invoice_items` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `invoice_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `unit_price` DECIMAL(12,2) NOT NULL,
    `line_total` DECIMAL(12,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT,
    INDEX `idx_invoice` (`invoice_id`),
    INDEX `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments Table
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `invoice_id` INT NOT NULL,
    `payment_number` INT NOT NULL,
    `payment_date` DATE NOT NULL,
    `payment_amount` DECIMAL(12,2) NOT NULL,
    `payment_mode` ENUM('cash','bdo','gcash') NOT NULL,
    `reference_number` VARCHAR(100) NULL,
    `notes` TEXT NULL,
    `recorded_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    INDEX `idx_invoice` (`invoice_id`),
    INDEX `idx_date` (`payment_date`),
    UNIQUE KEY `unique_payment` (`invoice_id`,`payment_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transactions Table
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `transaction_number` VARCHAR(50) UNIQUE NOT NULL,
    `transaction_date` DATETIME NOT NULL,
    `transaction_type` ENUM('sale','purchase','adjustment','expense') NOT NULL,
    `related_invoice_id` INT NULL,
    `related_restock_id` INT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `description` TEXT NULL,
    `recorded_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    FOREIGN KEY (`related_invoice_id`) REFERENCES `invoices`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`related_restock_id`) REFERENCES `restock_orders`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    INDEX `idx_type` (`transaction_type`),
    INDEX `idx_date` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit Logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NULL,
    `action_type` VARCHAR(50) NOT NULL,
    `module_name` VARCHAR(100) NULL,
    `record_id` INT NULL,
    `old_value` LONGTEXT NULL,
    `new_value` LONGTEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `status` ENUM('success','failed') DEFAULT 'success',
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_action` (`action_type`),
    INDEX `idx_date` (`created_at`),
    INDEX `idx_module` (`module_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `notification_type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `related_record_id` INT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `is_deleted` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `read_at` TIMESTAMP NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_type` (`notification_type`),
    INDEX `idx_read` (`is_read`),
    INDEX `idx_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings Table
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `setting_key` VARCHAR(100) UNIQUE NOT NULL,
    `setting_value` LONGTEXT NULL,
    `setting_type` ENUM('string','integer','boolean','json') DEFAULT 'string',
    `description` TEXT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoice Templates
CREATE TABLE IF NOT EXISTS `invoice_templates` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `template_name` VARCHAR(100) NOT NULL,
    `template_content` LONGTEXT NOT NULL,
    `number_format` VARCHAR(100) NULL,
    `prefix` VARCHAR(20) NULL,
    `suffix` VARCHAR(20) NULL,
    `reset_frequency` ENUM('daily','monthly','yearly') DEFAULT 'yearly',
    `current_number` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 0,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
