-- =============================================================================
-- MIGRATION: Consolidate stock_products by type + color + size
-- Merges PLAIN / DTF / SILK variants into one shared stock product per combo.
-- 72 rows → 24 rows  (one per unique type_id + color_id + size_id)
-- =============================================================================

START TRANSACTION;

-- ---------------------------------------------------------------------------
-- Step 1: Insert the 24 consolidated stock products
--         Code  = {type.code}-{color.name}-{size.code}   e.g. C.T.-Black-S
--         Name  = {type.name} - {color.name} - {size.name}
--         qty   = SUM of the 3 old variants
-- ---------------------------------------------------------------------------
INSERT INTO `stock_products`
    (`code`, `name`, `type_id`, `color_id`, `size_id`, `current_qty`, `low_stock_alert`, `status`)
SELECT
    CONCAT(t.`code`, '-', c.`name`, '-', s.`code`)     AS `code`,
    CONCAT(t.`name`, ' - ', c.`name`, ' - ', s.`name`) AS `name`,
    sp.`type_id`,
    sp.`color_id`,
    sp.`size_id`,
    SUM(sp.`current_qty`)                               AS `current_qty`,
    MIN(sp.`low_stock_alert`)                           AS `low_stock_alert`,
    'active'                                            AS `status`
FROM `stock_products` sp
JOIN `types`  t ON t.`id` = sp.`type_id`
JOIN `colors` c ON c.`id` = sp.`color_id`
JOIN `sizes`  s ON s.`id` = sp.`size_id`
WHERE sp.`id` <= 72          -- only the original 72 rows
GROUP BY sp.`type_id`, sp.`color_id`, sp.`size_id`;

-- ---------------------------------------------------------------------------
-- Step 2: Re-point product_stock_requirements → new consolidated IDs
-- ---------------------------------------------------------------------------
UPDATE `product_stock_requirements` psr
JOIN `stock_products` old_sp ON old_sp.`id` = psr.`stock_product_id`
JOIN `stock_products` new_sp
    ON  new_sp.`type_id`  = old_sp.`type_id`
    AND new_sp.`color_id` = old_sp.`color_id`
    AND new_sp.`size_id`  = old_sp.`size_id`
    AND new_sp.`id` > 72
SET psr.`stock_product_id` = new_sp.`id`
WHERE old_sp.`id` <= 72;

-- ---------------------------------------------------------------------------
-- Step 3: Re-point inventory rows → new consolidated IDs
-- ---------------------------------------------------------------------------
UPDATE `inventory` i
JOIN `stock_products` old_sp ON old_sp.`id` = i.`stock_product_id`
JOIN `stock_products` new_sp
    ON  new_sp.`type_id`  = old_sp.`type_id`
    AND new_sp.`color_id` = old_sp.`color_id`
    AND new_sp.`size_id`  = old_sp.`size_id`
    AND new_sp.`id` > 72
SET i.`stock_product_id` = new_sp.`id`
WHERE old_sp.`id` <= 72;

-- ---------------------------------------------------------------------------
-- Step 4: Re-point stock_movements rows → new consolidated IDs
-- ---------------------------------------------------------------------------
UPDATE `stock_movements` sm
JOIN `stock_products` old_sp ON old_sp.`id` = sm.`stock_product_id`
JOIN `stock_products` new_sp
    ON  new_sp.`type_id`  = old_sp.`type_id`
    AND new_sp.`color_id` = old_sp.`color_id`
    AND new_sp.`size_id`  = old_sp.`size_id`
    AND new_sp.`id` > 72
SET sm.`stock_product_id` = new_sp.`id`
WHERE old_sp.`id` <= 72;

-- ---------------------------------------------------------------------------
-- Step 5: Re-point restock_items rows → new consolidated IDs
-- ---------------------------------------------------------------------------
UPDATE `restock_items` ri
JOIN `stock_products` old_sp ON old_sp.`id` = ri.`stock_product_id`
JOIN `stock_products` new_sp
    ON  new_sp.`type_id`  = old_sp.`type_id`
    AND new_sp.`color_id` = old_sp.`color_id`
    AND new_sp.`size_id`  = old_sp.`size_id`
    AND new_sp.`id` > 72
SET ri.`stock_product_id` = new_sp.`id`
WHERE old_sp.`id` <= 72;

-- ---------------------------------------------------------------------------
-- Step 6: Remove any duplicate product_stock_requirements rows that now
--         point the same product to the same consolidated stock product.
--         (Keeps the row with the lowest id.)
-- ---------------------------------------------------------------------------
DELETE psr1 FROM `product_stock_requirements` psr1
JOIN `product_stock_requirements` psr2
    ON  psr2.`product_id`       = psr1.`product_id`
    AND psr2.`stock_product_id` = psr1.`stock_product_id`
    AND psr2.`id` < psr1.`id`;

-- ---------------------------------------------------------------------------
-- Step 7: Delete the old 72 fragmented stock products
--         (All FK references have been re-pointed in steps 2-5)
-- ---------------------------------------------------------------------------
DELETE FROM `stock_products` WHERE `id` <= 72;

COMMIT;

-- Verify
SELECT id, code, name, current_qty FROM `stock_products` ORDER BY type_id, color_id, size_id;
SELECT COUNT(*) AS requirements_count FROM `product_stock_requirements`;
