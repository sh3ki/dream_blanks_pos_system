-- Migration: add label fields (brand_name, etiketa, tags) to invoices
-- Date: 2026-05-23

ALTER TABLE `invoices`
  ADD COLUMN `brand_name` VARCHAR(100) NULL DEFAULT NULL AFTER `notes`,
  ADD COLUMN `etiketa`    ENUM('neck','sleeve','hem') NULL DEFAULT NULL AFTER `brand_name`,
  ADD COLUMN `tags`       ENUM('sticker','hangtag')   NULL DEFAULT NULL AFTER `etiketa`;
