-- Migrate clients: merge first_name + middle_name + last_name → full_name
-- Run once against the dream_blanks_pos database

ALTER TABLE `clients`
  ADD COLUMN `full_name` VARCHAR(150) NOT NULL DEFAULT '' AFTER `id`;

UPDATE `clients`
  SET `full_name` = TRIM(CONCAT_WS(' ',
    NULLIF(TRIM(COALESCE(`first_name`, '')), ''),
    NULLIF(TRIM(COALESCE(`middle_name`, '')), ''),
    NULLIF(TRIM(COALESCE(`last_name`, '')), '')
  ));

ALTER TABLE `clients`
  DROP INDEX `idx_name`,
  DROP COLUMN `first_name`,
  DROP COLUMN `middle_name`,
  DROP COLUMN `last_name`;

ALTER TABLE `clients`
  ADD INDEX `idx_full_name` (`full_name`(100));
