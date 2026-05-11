-- Clean migration for MySQL 8.4 (no ADD INDEX IF NOT EXISTS)
-- Run against dreamblanks_db

-- 1. Create stock_products table
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
    INDEX `idx_sp_code`   (`code`),
    INDEX `idx_sp_type`   (`type_id`),
    INDEX `idx_sp_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create product_stock_requirements table
CREATE TABLE IF NOT EXISTS `product_stock_requirements` (
    `id`                    INT PRIMARY KEY AUTO_INCREMENT,
    `product_id`            INT NOT NULL,
    `stock_product_id`      INT NOT NULL,
    `qty_required_per_unit` DECIMAL(10,4) NOT NULL DEFAULT 1,
    `waste_percent`         DECIMAL(5,2) DEFAULT 0,
    `created_at`            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`)       REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`stock_product_id`) REFERENCES `stock_products`(`id`) ON DELETE RESTRICT,
    UNIQUE KEY `unique_product_stock` (`product_id`, `stock_product_id`),
    INDEX `idx_psr_product`      (`product_id`),
    INDEX `idx_psr_stock_product` (`stock_product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Backfill stock_products from existing products (1:1 seed)
INSERT INTO `stock_products` (`code`, `name`, `description`, `type_id`, `color_id`, `size_id`,
    `current_qty`, `low_stock_alert`, `status`, `created_at`, `updated_at`)
SELECT
    CONCAT('SP-', p.`sku`)              AS `code`,
    p.`name`                            AS `name`,
    p.`description`                     AS `description`,
    p.`type_id`                         AS `type_id`,
    p.`color_id`                        AS `color_id`,
    p.`size_id`                         AS `size_id`,
    COALESCE(p.`current_stock`, 0)      AS `current_qty`,
    COALESCE(p.`low_stock_alert`, 10)   AS `low_stock_alert`,
    p.`status`                          AS `status`,
    p.`created_at`,
    p.`updated_at`
FROM `products` p
WHERE p.`deleted_at` IS NULL
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- 4. Backfill product_stock_requirements (1:1, qty=1 per unit)
INSERT INTO `product_stock_requirements`
    (`product_id`, `stock_product_id`, `qty_required_per_unit`, `waste_percent`)
SELECT
    p.`id`   AS `product_id`,
    sp.`id`  AS `stock_product_id`,
    1        AS `qty_required_per_unit`,
    0        AS `waste_percent`
FROM `products` p
INNER JOIN `stock_products` sp ON sp.`code` = CONCAT('SP-', p.`sku`)
WHERE p.`deleted_at` IS NULL
ON DUPLICATE KEY UPDATE `qty_required_per_unit` = `qty_required_per_unit`;

-- 5. Add stock_product_id to inventory
-- (Column does not exist per DESCRIBE — safe to add directly)
ALTER TABLE `inventory` ADD COLUMN `stock_product_id` INT NULL AFTER `id`;
ALTER TABLE `inventory` ADD INDEX `idx_inv_stock_product` (`stock_product_id`);

UPDATE `inventory` i
INNER JOIN `stock_products` sp ON sp.`code` = CONCAT('SP-', (
    SELECT sku FROM `products` WHERE id = i.product_id LIMIT 1
))
SET i.`stock_product_id` = sp.`id`
WHERE i.`stock_product_id` IS NULL;

-- 6. Add stock_product_id to stock_movements
ALTER TABLE `stock_movements` ADD COLUMN `stock_product_id` INT NULL AFTER `id`;
ALTER TABLE `stock_movements` ADD INDEX `idx_sm_stock_product` (`stock_product_id`);

UPDATE `stock_movements` sm
INNER JOIN `stock_products` sp ON sp.`code` = CONCAT('SP-', (
    SELECT sku FROM `products` WHERE id = sm.product_id LIMIT 1
))
SET sm.`stock_product_id` = sp.`id`
WHERE sm.`stock_product_id` IS NULL;

-- 7. Add stock_product_id to restock_items
ALTER TABLE `restock_items` ADD COLUMN `stock_product_id` INT NULL AFTER `id`;
ALTER TABLE `restock_items` ADD INDEX `idx_ri_stock_product` (`stock_product_id`);

UPDATE `restock_items` ri
INNER JOIN `stock_products` sp ON sp.`code` = CONCAT('SP-', (
    SELECT sku FROM `products` WHERE id = ri.product_id LIMIT 1
))
SET ri.`stock_product_id` = sp.`id`
WHERE ri.`stock_product_id` IS NULL;

SELECT 'Migration complete' AS status;
SELECT COUNT(*) AS stock_products_count FROM `stock_products`;
SELECT COUNT(*) AS requirements_count FROM `product_stock_requirements`;
