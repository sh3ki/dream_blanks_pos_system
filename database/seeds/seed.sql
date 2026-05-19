-- Dream Blanks POS System - Seed Data
-- Run AFTER schema.sql

-- Default Roles
INSERT IGNORE INTO `roles` (`name`, `description`, `status`) VALUES
('Admin', 'Full system access', 'active'),
('Manager', 'Sales and inventory management', 'active'),
('Sales Staff', 'Point of sale operations', 'active'),
('Inventory Staff', 'Inventory management only', 'active');

-- Default Permissions (module, action)
INSERT IGNORE INTO `permissions` (`module`, `action`, `description`) VALUES
('dashboard', 'view', 'View dashboard'),
('users', 'view', 'View users'),
('users', 'add', 'Add users'),
('users', 'edit', 'Edit users'),
('users', 'delete', 'Delete users'),
('roles', 'view', 'View roles'),
('roles', 'add', 'Add roles'),
('roles', 'edit', 'Edit roles'),
('roles', 'delete', 'Delete roles'),
('clients', 'view', 'View clients'),
('clients', 'add', 'Add clients'),
('clients', 'edit', 'Edit clients'),
('clients', 'delete', 'Delete clients'),
('products', 'view', 'View products'),
('products', 'add', 'Add products'),
('products', 'edit', 'Edit products'),
('products', 'delete', 'Delete products'),
('products', 'import', 'Import products via CSV'),
('inventory', 'view', 'View inventory'),
('inventory', 'restock', 'Create restock orders'),
('inventory', 'edit', 'Edit restock orders'),
('inventory', 'delete', 'Delete inventory records'),
('inventory', 'import', 'Import restock via CSV'),
('pos', 'view', 'Access POS'),
('pos', 'add', 'Create sales'),
('invoices', 'view', 'View invoices'),
('invoices', 'add', 'Create invoices'),
('invoices', 'edit', 'Edit invoices'),
('invoices', 'delete', 'Delete invoices'),
('payments', 'view', 'View payments'),
('payments', 'add', 'Add payments'),
('payments', 'edit', 'Edit payments'),
('payments', 'delete', 'Delete payments'),
('reports_sales',     'view',   'View sales report'),
('reports_sales',     'export', 'Export sales report as CSV'),
('reports_inventory', 'view',   'View inventory report'),
('reports_inventory', 'export', 'Export inventory report as CSV'),
('reports_financial', 'view',   'View financial report'),
('reports_financial', 'export', 'Export financial report as CSV'),
('settings', 'view', 'View settings'),
('settings', 'edit', 'Update settings'),
('audit_logs', 'view', 'View audit logs'),
('audit_logs', 'export', 'Export audit logs as CSV'),
('notifications', 'view', 'View notifications'),
('transactions', 'view', 'View transactions'),
('stock_products', 'adjust', 'Adjust stock quantity'),
('stock_products', 'import', 'Import stock products via CSV'),
('inventory', 'import', 'Import restock via CSV'),
('invoices', 'download', 'Download invoice PDF'),
('variations', 'view', 'View variations'),
('variations', 'add', 'Add variations'),
('variations', 'edit', 'Edit variations'),
('variations', 'delete', 'Delete variations');

-- Assign all permissions to Admin role
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions`;

-- Assign Manager permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM `permissions`
WHERE module IN ('clients','products','inventory','pos','invoices','payments','reports_sales','reports_inventory','reports_financial','notifications')
   OR (module = 'users' AND action = 'view');

-- Assign Sales Staff permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, id FROM `permissions`
WHERE module IN ('pos','invoices','payments','clients','notifications')
   OR (module = 'products' AND action = 'view');

-- Assign Inventory Staff permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, id FROM `permissions`
WHERE module IN ('inventory','notifications')
   OR (module = 'products' AND action IN ('view','edit'));

-- Default Admin User (password: password)
INSERT IGNORE INTO `users` (`username`, `email`, `first_name`, `last_name`, `password_hash`, `status`) VALUES
('admin', 'admin@dreamblanks.com', 'Admin', 'User', '$2y$12$vnqyTO/7VQbn2fOH5bhn7evKEZY/DLUjmDd3.aZ/fXb4dWQyzv4h6', 'active');

-- Assign Admin role to admin user
INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`) VALUES (1, 1);

-- Default Categories
INSERT IGNORE INTO `categories` (`name`, `code`, `description`, `status`) VALUES
('Apparel', 'APP', 'Clothing and garments', 'active'),
('Accessories', 'ACC', 'Fashion accessories', 'active'),
('Footwear', 'FOT', 'Shoes and sandals', 'active'),
('Bags', 'BAG', 'Bags and backpacks', 'active'),
('Others', 'OTH', 'Other products', 'active');

-- Default Colors
INSERT IGNORE INTO `colors` (`name`, `hex_code`, `status`) VALUES
('White', '#FFFFFF', 'active'),
('Black', '#000000', 'active'),
('Red', '#FF0000', 'active'),
('Blue', '#0000FF', 'active'),
('Green', '#008000', 'active'),
('Yellow', '#FFFF00', 'active'),
('Pink', '#FFC0CB', 'active'),
('Gray', '#808080', 'active'),
('Brown', '#A52A2A', 'active'),
('Navy', '#000080', 'active');

-- Default Sizes
INSERT IGNORE INTO `sizes` (`name`, `code`, `status`) VALUES
('Extra Small', 'XS', 'active'),
('Small', 'S', 'active'),
('Medium', 'M', 'active'),
('Large', 'L', 'active'),
('Extra Large', 'XL', 'active'),
('Double Extra Large', 'XXL', 'active'),
('One Size', 'OS', 'active'),
('Free Size', 'FS', 'active');

-- Default Types
INSERT IGNORE INTO `types` (`name`, `code`, `status`) VALUES
('Shirt', 'SHT', 'active'),
('Hoodie', 'HOD', 'active'),
('Tote Bag', 'TOT', 'active'),
('Cap', 'CAP', 'active'),
('Jacket', 'JKT', 'active');

-- Default Settings
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('business_name', 'Dream Blanks', 'string', 'Business name'),
('business_address', '123 Main Street, Quezon City, Philippines', 'string', 'Business address'),
('business_email', 'info@dreamblanks.com', 'string', 'Business email'),
('business_phone', '+63 912 345 6789', 'string', 'Business phone number'),
('currency_symbol', '₱', 'string', 'Currency symbol'),
('currency_code', 'PHP', 'string', 'Currency code'),
('date_format', 'MM/DD/YYYY', 'string', 'Date display format'),
('time_format', '12-hour', 'string', 'Time display format'),
('timezone', 'Asia/Manila', 'string', 'System timezone'),
('low_stock_alert_default', '10', 'integer', 'Default low stock alert threshold'),
('invoice_prefix', 'INV-', 'string', 'Invoice number prefix'),
('invoice_next_number', '1', 'integer', 'Next invoice sequence number'),
('invoice_reset_frequency', 'yearly', 'string', 'Invoice number reset frequency'),
('tax_rate', '0', 'integer', 'Default tax rate percentage'),
('receipt_footer', 'Thank you for your business!', 'string', 'Receipt footer message');
