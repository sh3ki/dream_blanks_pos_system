-- Migration: Add link, notes, photo columns to project_lineups
-- Run once against the database

ALTER TABLE `project_lineups`
  ADD COLUMN `link`  VARCHAR(500)  NULL DEFAULT NULL AFTER `deadline`,
  ADD COLUMN `notes` TEXT          NULL DEFAULT NULL AFTER `link`,
  ADD COLUMN `photo` VARCHAR(255)  NULL DEFAULT NULL AFTER `notes`;
