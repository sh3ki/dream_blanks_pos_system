-- =============================================================================
-- MIGRATION: Stock-Product-Based Inventory
-- Version: 2.0
-- Description: Introduces stock_products and product_stock_requirements tables.
--              Migrates inventory and movement tracking from products to stock_products.
--              Restock items are now keyed by stock_product_id.
--              Legacy product.current_stock column is kept during transition (Phase E removes it).
-- =============================================================================

-- PHASE A: Add new tables (safe, additive only)
-- -----------------------------------------------------------------------------

-- 1. Stock Products (the actual inventory-tracked items)
CREATE TABLE IF NOT EXISTS `stock_products` (
    `id`               INT PRIMARY KEY AUTO_INCREMENT,
    `code`             VARCHAR(100) UNIQUE NOT NULL,
    `name`             VARCHAR(255) NOT NULL,
    `description`      TEXT NULL,
    `type_id`          INT NULL,
    `color_id`         INT NULL,
    `size_id`          INT NULL,
    `current_qty`      INT DEFAULT 0,
    `low_stock_alert`  INT DEFAULT 10,
    `status`           ENUM('active','inactive') DEFAULT 'active',
    `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`       TIMESTAMP NULL,
    FOREIGN KEY (`type_id`)  REFERENCES `types`(`id`)  ON DELETE SET NULL,
    FOREIGN KEY (`color_id`) REFERENCES `colors`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`size_id`)  REFERENCES `sizes`(`id`)  ON DELETE SET NULL,
    INDEX `idx_code`   (`code`),
    INDEX `idx_name`   (`name`),
    INDEX `idx_type`   (`type_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Product-to-Stock-Product requirements (many-to-many with qty)
CREATE TABLE IF NOT EXISTS `product_stock_requirements` (
    `id`                   INT PRIMARY KEY AUTO_INCREMENT,
    `product_id`           INT NOT NULL,
    `stock_product_id`     INT NOT NULL,
    `qty_required_per_unit` DECIMAL(10,4) NOT NULL DEFAULT 1,
    `waste_percent`        DECIMAL(5,2) DEFAULT 0,
    `created_at`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`)       REFERENCES `products`(`id`)       ON DELETE CASCADE,
    FOREIGN KEY (`stock_product_id`) REFERENCES `stock_products`(`id`) ON DELETE RESTRICT,
    UNIQUE KEY `unique_product_stock` (`product_id`, `stock_product_id`),
    INDEX `idx_product`      (`product_id`),
    INDEX `idx_stock_product` (`stock_product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- PHASE B: Backfill stock_products from existing products
--          Creates one stock_product per distinct product (1:1 migration seed).
--          Auto-generates a code from product SKU.
-- -----------------------------------------------------------------------------

INSERT INTO `stock_products` (`code`, `name`, `description`, `type_id`, `color_id`, `size_id`, `current_qty`, `low_stock_alert`, `status`, `created_at`, `updated_at`)
SELECT
    CONCAT('SP-', p.sku)        AS `code`,
    p.`name`                    AS `name`,
    p.`description`             AS `description`,
    p.`type_id`                 AS `type_id`,
    p.`color_id`                AS `color_id`,
    p.`size_id`                 AS `size_id`,
    COALESCE(p.`current_stock`, 0) AS `current_qty`,
    COALESCE(p.`low_stock_alert`, 10) AS `low_stock_alert`,
    p.`status`                  AS `status`,
    p.`created_at`,
    p.`updated_at`
FROM `products` p
WHERE p.`deleted_at` IS NULL
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- Backfill product_stock_requirements (1:1, qty = 1 per unit)
INSERT INTO `product_stock_requirements` (`product_id`, `stock_product_id`, `qty_required_per_unit`, `waste_percent`)
SELECT
    p.`id`   AS `product_id`,
    sp.`id`  AS `stock_product_id`,
    1        AS `qty_required_per_unit`,
    0        AS `waste_percent`
FROM `products` p
INNER JOIN `stock_products` sp ON sp.`code` = CONCAT('SP-', p.`sku`)
WHERE p.`deleted_at` IS NULL
ON DUPLICATE KEY UPDATE `qty_required_per_unit` = `qty_required_per_unit`;

-- -----------------------------------------------------------------------------
-- PHASE C: Migrate inventory table from product_id to stock_product_id
-- -----------------------------------------------------------------------------

-- Add stock_product_id column to inventory (keep old product_id for rollback)
ALTER TABLE `inventory`
    ADD COLUMN IF NOT EXISTS `stock_product_id` INT NULL AFTER `id`,
    ADD INDEX IF NOT EXISTS `idx_stock_product` (`stock_product_id`);

-- Populate stock_product_id from backfilled stock_products
UPDATE `inventory` i
INNER JOIN `stock_products` sp ON sp.`code` = CONCAT('SP-', (
    SELECT sku FROM products WHERE id = i.product_id
))
SET i.`stock_product_id` = sp.`id`
WHERE i.`stock_product_id` IS NULL;

-- Add stock_product_id to stock_movements (keep product_id as secondary reference)
ALTER TABLE `stock_movements`
    ADD COLUMN IF NOT EXISTS `stock_product_id` INT NULL AFTER `id`,
    ADD INDEX IF NOT EXISTS `idx_sm_stock_product` (`stock_product_id`);

-- Populate stock_product_id in existing stock_movements
UPDATE `stock_movements` sm
INNER JOIN `stock_products` sp ON sp.`code` = CONCAT('SP-', (
    SELECT sku FROM products WHERE id = sm.product_id
))
SET sm.`stock_product_id` = sp.`id`
WHERE sm.`stock_product_id` IS NULL;

-- Migrate restock_items: add stock_product_id (keep old product_id for rollback)
ALTER TABLE `restock_items`
    ADD COLUMN IF NOT EXISTS `stock_product_id` INT NULL AFTER `id`,
    ADD INDEX IF NOT EXISTS `idx_ri_stock_product` (`stock_product_id`);

UPDATE `restock_items` ri
INNER JOIN `stock_products` sp ON sp.`code` = CONCAT('SP-', (
    SELECT sku FROM products WHERE id = ri.product_id
))
SET ri.`stock_product_id` = sp.`id`
WHERE ri.`stock_product_id` IS NULL;

-- -----------------------------------------------------------------------------
-- ROLLBACK NOTES (if needed, run these to revert):
--   DROP TABLE IF EXISTS product_stock_requirements;
--   DROP TABLE IF EXISTS stock_products;
--   ALTER TABLE inventory DROP COLUMN stock_product_id;
--   ALTER TABLE stock_movements DROP COLUMN stock_product_id;
--   ALTER TABLE restock_items DROP COLUMN stock_product_id;
-- NOTE: Do NOT drop product.current_stock or legacy product_id columns until Phase E.
-- =============================================================================
