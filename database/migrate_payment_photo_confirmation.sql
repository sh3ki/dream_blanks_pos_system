-- Migration: Add payment_photo_path and confirmation columns to payments table
-- Run this file once against the database AFTER initializing with schema.sql

SET NAMES utf8mb4;

-- Add photo path column (stores uploaded receipt/proof image path)
ALTER TABLE `payments`
  ADD COLUMN `payment_photo_path` VARCHAR(255) NULL AFTER `reference_number`;

-- Add confirmation fields
ALTER TABLE `payments`
  ADD COLUMN `is_confirmed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `payment_photo_path`,
  ADD COLUMN `confirmed_by` INT UNSIGNED NULL AFTER `is_confirmed`,
  ADD COLUMN `confirmed_at` TIMESTAMP NULL AFTER `confirmed_by`;

-- Add foreign key for confirmed_by
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_confirmed_by`
  FOREIGN KEY (`confirmed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL;

-- Add new permission: payments.confirm
-- Uses the app's schema (separate module + action columns)
INSERT IGNORE INTO `permissions` (`module`, `action`, `description`)
VALUES ('payments', 'confirm', 'Confirm/unconfirm payment receipt');

-- Assign payments.confirm to Admin role (role_id=1)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions` WHERE module = 'payments' AND action = 'confirm';
