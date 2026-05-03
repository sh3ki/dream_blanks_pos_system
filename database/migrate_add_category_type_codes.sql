SET @col_exists := (
    SELECT COUNT(1)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'categories'
      AND COLUMN_NAME = 'code'
);

SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE `categories` ADD COLUMN `code` VARCHAR(20) NULL AFTER `name`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(1)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'types'
      AND COLUMN_NAME = 'code'
);

SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE `types` ADD COLUMN `code` VARCHAR(20) NULL AFTER `name`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE categories SET code = CASE id
    WHEN 1 THEN 'PTS'
    WHEN 2 THEN 'DTF'
    WHEN 4 THEN 'SIL'
    ELSE CONCAT(UPPER(LEFT(REPLACE(REPLACE(name, '' '', ''''), ''-'', ''''), 3)), LPAD(id, 2, '0'))
END
WHERE code IS NULL OR code = '';

UPDATE types SET code = CASE id
    WHEN 1 THEN 'PRO'
    WHEN 2 THEN 'CRT'
    ELSE CONCAT(UPPER(LEFT(REPLACE(REPLACE(name, '' '', ''''), ''-'', ''''), 3)), LPAD(id, 2, '0'))
END
WHERE code IS NULL OR code = '';

SET @idx_exists := (
    SELECT COUNT(1)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'categories'
      AND INDEX_NAME = 'idx_code'
);
SET @sql := IF(
    @idx_exists = 0,
    'CREATE INDEX `idx_code` ON `categories` (`code`)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
    SELECT COUNT(1)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'types'
      AND INDEX_NAME = 'idx_code'
);
SET @sql := IF(
    @idx_exists = 0,
    'CREATE INDEX `idx_code` ON `types` (`code`)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE categories
    MODIFY code VARCHAR(20) NOT NULL,
    ADD UNIQUE KEY unique_categories_code (code);

ALTER TABLE types
    MODIFY code VARCHAR(20) NOT NULL,
    ADD UNIQUE KEY unique_types_code (code);
