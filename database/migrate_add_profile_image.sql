-- Add profile_image column to users table
ALTER TABLE `users`
  ADD COLUMN `profile_image` VARCHAR(500) NULL DEFAULT NULL AFTER `last_name`;
