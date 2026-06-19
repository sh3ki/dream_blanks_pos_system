-- Migration: Add display order to variations (categories, types, colors, sizes)
-- Purpose: Support drag-and-drop reordering of variation items

-- Add order column to categories
ALTER TABLE categories ADD COLUMN `order` INT NOT NULL DEFAULT 0 AFTER `description`;
CREATE INDEX idx_categories_order ON categories(`order`);

-- Add order column to types
ALTER TABLE types ADD COLUMN `order` INT NOT NULL DEFAULT 0 AFTER `code`;
CREATE INDEX idx_types_order ON types(`order`);

-- Add order column to colors
ALTER TABLE colors ADD COLUMN `order` INT NOT NULL DEFAULT 0 AFTER `hex_code`;
CREATE INDEX idx_colors_order ON colors(`order`);

-- Add order column to sizes
ALTER TABLE sizes ADD COLUMN `order` INT NOT NULL DEFAULT 0 AFTER `code`;
CREATE INDEX idx_sizes_order ON sizes(`order`);

-- Set initial order values based on creation date (existing items will be ordered as they were created)
SET @row:=0;
UPDATE categories SET `order` = (@row:=@row+1) WHERE deleted_at IS NULL ORDER BY created_at ASC;

SET @row:=0;
UPDATE types SET `order` = (@row:=@row+1) WHERE deleted_at IS NULL ORDER BY created_at ASC;

SET @row:=0;
UPDATE colors SET `order` = (@row:=@row+1) WHERE deleted_at IS NULL ORDER BY created_at ASC;

SET @row:=0;
UPDATE sizes SET `order` = (@row:=@row+1) WHERE deleted_at IS NULL ORDER BY created_at ASC;
