-- Migration: Add stock_products permissions
-- Run this after migrate_stock_products.sql

-- Insert permissions for stock_products module
-- Schema: permissions(id, module, action, description, created_at, updated_at)
INSERT IGNORE INTO `permissions` (`module`, `action`, `description`) VALUES
('stock_products', 'view',   'View stock products'),
('stock_products', 'add',    'Add stock products'),
('stock_products', 'edit',   'Edit stock products'),
('stock_products', 'delete', 'Delete stock products');

-- Grant all stock_products permissions to Admin role (role_id = 1)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions` WHERE module = 'stock_products';

-- Grant stock_products view/add/edit to Manager role (role_id = 2)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM `permissions`
WHERE module = 'stock_products' AND action IN ('view', 'add', 'edit');

-- Grant stock_products view/edit to Inventory Staff role (role_id = 4)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, id FROM `permissions`
WHERE module = 'stock_products' AND action IN ('view', 'edit');
