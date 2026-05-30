<?php

use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;

$router = new Router();
$auth   = [AuthMiddleware::class];
$guest  = [GuestMiddleware::class];

// ---- Web Routes ----

// Auth
$router->get('/login',           [\App\Controllers\AuthController::class, 'showLogin'],         $guest);
$router->post('/login',          [\App\Controllers\AuthController::class, 'login'],              $guest);
$router->get('/logout',          [\App\Controllers\AuthController::class, 'logout'],             $auth);
$router->get('/forgot-password', [\App\Controllers\AuthController::class, 'showForgotPassword'], $guest);
$router->post('/forgot-password',[\App\Controllers\AuthController::class, 'forgotPassword'],     $guest);
$router->get('/reset-password',  [\App\Controllers\AuthController::class, 'showResetPassword'],  $guest);
$router->post('/reset-password', [\App\Controllers\AuthController::class, 'resetPassword'],      $guest);

// Dashboard
$router->get('/',          [\App\Controllers\DashboardController::class, 'index'], $auth);
$router->get('/dashboard', [\App\Controllers\DashboardController::class, 'index'], $auth);

// POS
$router->get('/pos', [\App\Controllers\PosController::class, 'index'], $auth);

// Profile
$router->get('/profile',              [\App\Controllers\UserController::class, 'profile'],            $auth);
$router->put('/api/v1/profile',       [\App\Controllers\UserController::class, 'updateProfile'],      $auth);
$router->get('/api/v1/profile/check-username', [\App\Controllers\UserController::class, 'checkUsername'], $auth);
$router->post('/api/v1/profile/image',[\App\Controllers\UserController::class, 'uploadProfileImage'], $auth);

// Users
$router->get('/users',        [\App\Controllers\UserController::class, 'index'], $auth);

// Clients
$router->get('/clients',      [\App\Controllers\ClientController::class, 'index'], $auth);

// Products
$router->get('/products',     [\App\Controllers\ProductController::class, 'index'], $auth);

// Inventory
$router->get('/inventory',        [\App\Controllers\InventoryController::class, 'index'], $auth);

// Invoices
$router->get('/invoices',     [\App\Controllers\InvoiceController::class, 'index'], $auth);

// Project Lineup
$router->get('/project-lineup', [\App\Controllers\ProjectLineupController::class, 'index'], $auth);

// Transactions
$router->get('/transactions', [\App\Controllers\TransactionController::class, 'index'], $auth);

// Reports
$router->get('/reports/sales',     [\App\Controllers\ReportController::class, 'sales'],     $auth);
$router->get('/reports/inventory', [\App\Controllers\ReportController::class, 'inventory'], $auth);
$router->get('/reports/financial', [\App\Controllers\ReportController::class, 'financial'], $auth);

// Roles
$router->get('/roles', [\App\Controllers\RoleController::class, 'index'], $auth);

// Audit Logs
$router->get('/audit-logs', [\App\Controllers\AuditLogController::class, 'index'], $auth);

// Variations
$router->get('/variations', [\App\Controllers\VariationController::class, 'index'], $auth);

// Settings
$router->get('/settings', [\App\Controllers\SettingsController::class, 'index'], $auth);

// ---- API Routes ----

// Auth API
$router->post('/api/v1/auth/login',          [\App\Controllers\AuthController::class, 'login']);
$router->post('/api/v1/auth/logout',         [\App\Controllers\AuthController::class, 'logout'],         $auth);
$router->post('/api/v1/auth/forgot-password',[\App\Controllers\AuthController::class, 'forgotPassword']);
$router->post('/api/v1/auth/verify-otp',     [\App\Controllers\AuthController::class, 'verifyOtp']);
$router->post('/api/v1/auth/reset-password', [\App\Controllers\AuthController::class, 'resetPassword']);

// Audit Logs API
$router->get('/api/v1/audit-logs',         [\App\Controllers\AuditLogController::class, 'index'],  $auth);
$router->get('/api/v1/audit-logs/export',  [\App\Controllers\AuditLogController::class, 'export'], $auth);

// Users API
$router->get('/api/v1/users',               [\App\Controllers\UserController::class, 'index'],   $auth);
$router->post('/api/v1/users',              [\App\Controllers\UserController::class, 'store'],   $auth);
$router->get('/api/v1/users/{user_id}',     [\App\Controllers\UserController::class, 'show'],    $auth);
$router->put('/api/v1/users/{user_id}',     [\App\Controllers\UserController::class, 'update'],  $auth);
$router->delete('/api/v1/users/{user_id}',  [\App\Controllers\UserController::class, 'destroy'], $auth);

// Roles API
$router->get('/api/v1/roles',                              [\App\Controllers\RoleController::class, 'index'],             $auth);
$router->post('/api/v1/roles',                             [\App\Controllers\RoleController::class, 'store'],             $auth);
$router->put('/api/v1/roles/{role_id}',                    [\App\Controllers\RoleController::class, 'update'],            $auth);
$router->delete('/api/v1/roles/{role_id}',                 [\App\Controllers\RoleController::class, 'destroy'],           $auth);
$router->put('/api/v1/roles/{role_id}/permissions',        [\App\Controllers\RoleController::class, 'updatePermissions'], $auth);
$router->get('/api/v1/permissions',                        [\App\Controllers\RoleController::class, 'permissions'],       $auth);

// Clients API
$router->get('/api/v1/clients',              [\App\Controllers\ClientController::class, 'index'],   $auth);
$router->post('/api/v1/clients',             [\App\Controllers\ClientController::class, 'store'],   $auth);
$router->get('/api/v1/clients/{client_id}',  [\App\Controllers\ClientController::class, 'show'],    $auth);
$router->put('/api/v1/clients/{client_id}',  [\App\Controllers\ClientController::class, 'update'],  $auth);
$router->delete('/api/v1/clients/{client_id}',[\App\Controllers\ClientController::class, 'destroy'],$auth);

// Products API
$router->get('/api/v1/products',                               [\App\Controllers\ProductController::class, 'index'],           $auth);
$router->post('/api/v1/products',                              [\App\Controllers\ProductController::class, 'store'],           $auth);
$router->post('/api/v1/products/bulk-import',                  [\App\Controllers\ProductController::class, 'bulkImport'],      $auth);
$router->get('/api/v1/products/import-template',               [\App\Controllers\ProductController::class, 'downloadTemplate'],$auth);
$router->get('/api/v1/products/{product_id}',                  [\App\Controllers\ProductController::class, 'show'],            $auth);
$router->put('/api/v1/products/{product_id}',                  [\App\Controllers\ProductController::class, 'update'],          $auth);
$router->delete('/api/v1/products/{product_id}',               [\App\Controllers\ProductController::class, 'destroy'],         $auth);
$router->get('/api/v1/products/{product_id}/stock-requirements', [\App\Controllers\ProductController::class, 'getRequirements'], $auth);
$router->put('/api/v1/products/{product_id}/stock-requirements', [\App\Controllers\ProductController::class, 'saveRequirements'], $auth);

// Stock Products web route
$router->get('/stock-products', [\App\Controllers\StockProductController::class, 'index'], $auth);

// Stock Products API
$router->get('/api/v1/stock-products',                              [\App\Controllers\StockProductController::class, 'list'],            $auth);
$router->post('/api/v1/stock-products',                             [\App\Controllers\StockProductController::class, 'store'],           $auth);
$router->get('/api/v1/stock-products/import-template',              [\App\Controllers\StockProductController::class, 'downloadTemplate'],$auth);
$router->post('/api/v1/stock-products/bulk-import',                 [\App\Controllers\StockProductController::class, 'bulkImport'],      $auth);
$router->get('/api/v1/stock-products/all',                          [\App\Controllers\StockProductController::class, 'allForSelect'],    $auth);
$router->post('/api/v1/stock-products/bulk-adjust',                 [\App\Controllers\StockProductController::class, 'bulkAdjust'],      $auth);
$router->get('/api/v1/stock-products/{stock_product_id}',           [\App\Controllers\StockProductController::class, 'show'],            $auth);
$router->put('/api/v1/stock-products/{stock_product_id}',           [\App\Controllers\StockProductController::class, 'update'],          $auth);
$router->delete('/api/v1/stock-products/{stock_product_id}',        [\App\Controllers\StockProductController::class, 'destroy'],         $auth);
$router->post('/api/v1/stock-products/{stock_product_id}/adjust',   [\App\Controllers\StockProductController::class, 'adjust'],          $auth);
$router->get('/api/v1/stock-products/{stock_product_id}/movements', [\App\Controllers\StockProductController::class, 'movements'],       $auth);

// Inventory API
$router->get('/api/v1/inventory',                                [\App\Controllers\InventoryController::class, 'index'],         $auth);
$router->post('/api/v1/inventory/restock',                       [\App\Controllers\InventoryController::class, 'createRestock'], $auth);
$router->post('/api/v1/inventory/restock/import-csv',            [\App\Controllers\InventoryController::class, 'importRestockCsv'], $auth);
$router->get('/api/v1/inventory/restock/{restock_id}',           [\App\Controllers\InventoryController::class, 'getRestock'],    $auth);
$router->put('/api/v1/inventory/restock/{restock_id}',           [\App\Controllers\InventoryController::class, 'updateRestock'], $auth);

// POS API
$router->get('/api/v1/pos/products', [\App\Controllers\PosController::class, 'products'], $auth);
$router->post('/api/v1/pos/checkout',[\App\Controllers\PosController::class, 'checkout'], $auth);
$router->post('/api/v1/upload/payment-photo', [\App\Controllers\UploadController::class, 'paymentPhoto'], $auth);

// Invoices API
$router->get('/api/v1/invoices',                              [\App\Controllers\InvoiceController::class, 'index'],     $auth);
$router->get('/api/v1/invoices/{invoice_id}',                 [\App\Controllers\InvoiceController::class, 'show'],      $auth);
$router->post('/api/v1/invoices/{invoice_id}/payments',       [\App\Controllers\InvoiceController::class, 'addPayment'],    $auth);
$router->put('/api/v1/payments/{payment_id}',                 [\App\Controllers\InvoiceController::class, 'updatePayment'], $auth);
$router->put('/api/v1/payments/{payment_id}/confirm',         [\App\Controllers\InvoiceController::class, 'confirmPayment'], $auth);
$router->delete('/api/v1/payments/{payment_id}',              [\App\Controllers\InvoiceController::class, 'deletePayment'], $auth);
$router->delete('/api/v1/invoices/{invoice_id}',              [\App\Controllers\InvoiceController::class, 'deleteInvoice'], $auth);
$router->put('/api/v1/invoices/{invoice_id}/toggle-sent',      [\App\Controllers\InvoiceController::class, 'toggleSent'], $auth);
$router->get('/api/v1/invoices/{invoice_id}/print',           [\App\Controllers\InvoiceController::class, 'print'],     $auth);
$router->post('/api/v1/invoices/{invoice_id}/send-email',     [\App\Controllers\InvoiceController::class, 'sendEmail'], $auth);

// Project Lineup API
$router->post('/api/v1/project-lineup',                        [\App\Controllers\ProjectLineupController::class, 'store'],        $auth);
$router->put('/api/v1/project-lineup/{lineup_id}',             [\App\Controllers\ProjectLineupController::class, 'update'],       $auth);
$router->put('/api/v1/project-lineup/{lineup_id}/status',      [\App\Controllers\ProjectLineupController::class, 'updateStatus'], $auth);
$router->put('/api/v1/project-lineup/{lineup_id}/archive',     [\App\Controllers\ProjectLineupController::class, 'archive'],      $auth);
$router->delete('/api/v1/project-lineup/{lineup_id}',          [\App\Controllers\ProjectLineupController::class, 'destroy'],      $auth);
$router->get('/api/v1/invoices/{invoice_id}/lineup-prefill',   [\App\Controllers\ProjectLineupController::class, 'getInvoicePrefill'], $auth);

// Reports API
$router->get('/api/v1/reports/sales',     [\App\Controllers\ReportController::class, 'sales'],     $auth);
$router->get('/api/v1/reports/inventory', [\App\Controllers\ReportController::class, 'inventory'], $auth);
$router->get('/api/v1/reports/financial', [\App\Controllers\ReportController::class, 'financial'], $auth);
$router->get('/api/v1/reports/export',    [\App\Controllers\ReportController::class, 'export'],    $auth);

// Dashboard API
$router->get('/api/v1/dashboard/metrics', [\App\Controllers\DashboardController::class, 'metrics'], $auth);
$router->get('/api/v1/dashboard/charts',  [\App\Controllers\DashboardController::class, 'charts'],  $auth);

// Notifications API
$router->get('/api/v1/notifications',                            [\App\Controllers\NotificationController::class, 'index'],       $auth);
$router->put('/api/v1/notifications/read-all',                   [\App\Controllers\NotificationController::class, 'markAllRead'], $auth);
$router->put('/api/v1/notifications/{notification_id}/read',     [\App\Controllers\NotificationController::class, 'markRead'],    $auth);
$router->delete('/api/v1/notifications/{notification_id}',       [\App\Controllers\NotificationController::class, 'destroy'],     $auth);

// Audit Logs API
$router->get('/api/v1/audit-logs',        [\App\Controllers\AuditLogController::class, 'index'],  $auth);
$router->get('/api/v1/audit-logs/export', [\App\Controllers\AuditLogController::class, 'export'], $auth);

// Settings API
$router->get('/api/v1/settings', [\App\Controllers\SettingsController::class, 'index'],  $auth);
$router->put('/api/v1/settings', [\App\Controllers\SettingsController::class, 'update'], $auth);

// Variations API
$router->get('/api/v1/variations/categories',          [\App\Controllers\VariationController::class, 'listCategories'],   $auth);
$router->post('/api/v1/variations/categories',         [\App\Controllers\VariationController::class, 'storeCategory'],    $auth);
$router->put('/api/v1/variations/categories/{id}',     [\App\Controllers\VariationController::class, 'updateCategory'],   $auth);
$router->delete('/api/v1/variations/categories/{id}',  [\App\Controllers\VariationController::class, 'destroyCategory'],  $auth);

$router->get('/api/v1/variations/colors',              [\App\Controllers\VariationController::class, 'listColors'],       $auth);
$router->post('/api/v1/variations/colors',             [\App\Controllers\VariationController::class, 'storeColor'],       $auth);
$router->put('/api/v1/variations/colors/{id}',         [\App\Controllers\VariationController::class, 'updateColor'],      $auth);
$router->delete('/api/v1/variations/colors/{id}',      [\App\Controllers\VariationController::class, 'destroyColor'],     $auth);

$router->get('/api/v1/variations/sizes',               [\App\Controllers\VariationController::class, 'listSizes'],        $auth);
$router->post('/api/v1/variations/sizes',              [\App\Controllers\VariationController::class, 'storeSize'],        $auth);
$router->put('/api/v1/variations/sizes/{id}',          [\App\Controllers\VariationController::class, 'updateSize'],       $auth);
$router->delete('/api/v1/variations/sizes/{id}',       [\App\Controllers\VariationController::class, 'destroySize'],      $auth);

$router->get('/api/v1/variations/types',               [\App\Controllers\VariationController::class, 'listTypes'],        $auth);
$router->post('/api/v1/variations/types',              [\App\Controllers\VariationController::class, 'storeType'],        $auth);
$router->put('/api/v1/variations/types/{id}',          [\App\Controllers\VariationController::class, 'updateType'],       $auth);
$router->delete('/api/v1/variations/types/{id}',       [\App\Controllers\VariationController::class, 'destroyType'],      $auth);

return $router;
