-- Migration: change etiketa and tags from ENUM to VARCHAR for multi-value storage
-- Date: 2026-05-23

ALTER TABLE `invoices`
  MODIFY COLUMN `etiketa` VARCHAR(100) NULL DEFAULT NULL,
  MODIFY COLUMN `tags`    VARCHAR(100) NULL DEFAULT NULL;
