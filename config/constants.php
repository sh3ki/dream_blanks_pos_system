<?php

// Payment modes
define('PAYMENT_CASH',  'cash');
define('PAYMENT_BDO',   'bdo');
define('PAYMENT_GCASH', 'gcash');

// Payment statuses
define('PAYMENT_STATUS_FULLY_PAID',    'fully_paid');
define('PAYMENT_STATUS_PARTIALLY_PAID','partially_paid');
define('PAYMENT_STATUS_UNPAID',        'unpaid');

// Invoice sent statuses
define('INVOICE_SENT',     'sent');
define('INVOICE_NOT_SENT', 'not_sent');

// Stock statuses
define('STOCK_IN_STOCK',  'in_stock');
define('STOCK_LOW_STOCK', 'low_stock');
define('STOCK_OUT',       'out_of_stock');

// Delivery statuses
define('DELIVERY_ORDERED',     'ordered');
define('DELIVERY_DELIVERED',   'delivered');
define('DELIVERY_INCOMPLETE',  'incomplete');
define('DELIVERY_PROBLEMATIC', 'problematic');

// Audit action types
define('AUDIT_CREATE', 'create');
define('AUDIT_UPDATE', 'update');
define('AUDIT_DELETE', 'delete');
define('AUDIT_LOGIN',  'login');
define('AUDIT_LOGOUT', 'logout');
define('AUDIT_VIEW',   'view');

// User statuses
define('STATUS_ACTIVE',   'active');
define('STATUS_INACTIVE', 'inactive');

// Transaction types
define('TXN_SALE',       'sale');
define('TXN_PURCHASE',   'purchase');
define('TXN_ADJUSTMENT', 'adjustment');
define('TXN_EXPENSE',    'expense');

// Stock movement types
define('MOVEMENT_PURCHASE',   'purchase');
define('MOVEMENT_SALE',       'sale');
define('MOVEMENT_ADJUSTMENT', 'adjustment');
define('MOVEMENT_DAMAGE',     'damage');
define('MOVEMENT_LOSS',       'loss');
define('MOVEMENT_RESTOCK',    'purchase'); // alias: initial stock import = purchase

// Roles
define('ROLE_ADMIN',      'Admin');
define('ROLE_MANAGER',    'Manager');
define('ROLE_SALES',      'Sales Staff');
define('ROLE_INVENTORY',  'Inventory Staff');

// Modules for permissions
define('MODULE_USERS',          'users');
define('MODULE_ROLES',          'roles');
define('MODULE_CLIENTS',        'clients');
define('MODULE_PRODUCTS',       'products');
define('MODULE_STOCK_PRODUCTS', 'stock_products');
define('MODULE_INVENTORY',      'inventory');
define('MODULE_POS',            'pos');
define('MODULE_INVOICES',       'invoices');
define('MODULE_PAYMENTS',       'payments');
define('MODULE_REPORTS',        'reports');
define('MODULE_SETTINGS',       'settings');
define('MODULE_AUDIT_LOGS',     'audit_logs');
define('MODULE_NOTIFICATIONS',  'notifications');

// Permission actions
define('ACTION_VIEW',   'view');
define('ACTION_ADD',    'add');
define('ACTION_EDIT',   'edit');
define('ACTION_DELETE', 'delete');

// Paths
if (!defined('ROOT_PATH')) {
	define('ROOT_PATH', dirname(__DIR__));
}
if (!defined('SRC_PATH')) {
	define('SRC_PATH', ROOT_PATH . '/src');
}
if (!defined('CONFIG_PATH')) {
	define('CONFIG_PATH', ROOT_PATH . '/config');
}
if (!defined('PUBLIC_PATH')) {
	define('PUBLIC_PATH', ROOT_PATH . '/public');
}
if (!defined('UPLOAD_PATH')) {
	define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');
}
if (!defined('LOG_PATH')) {
	define('LOG_PATH', ROOT_PATH . '/logs');
}
if (!defined('VIEW_PATH')) {
	define('VIEW_PATH', SRC_PATH . '/Views');
}
if (!defined('DB_PATH')) {
	define('DB_PATH', ROOT_PATH . '/database');
}
