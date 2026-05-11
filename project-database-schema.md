# Dream Blanks POS System - Database Schema

## 1. Database Overview
- **Database Name**: `dream_blanks_pos`
- **Character Set**: utf8mb4
- **Collation**: utf8mb4_unicode_ci
- **Engine**: InnoDB (for transactional support and referential integrity)
- **Total Tables**: 20+

---

## 2. Database Schema SQL

### Core Authentication & User Management

```sql
-- Users Table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    last_name VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    profile_photo_path VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status)
);

-- Roles Table
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_name (name)
);

-- Permissions Table
CREATE TABLE permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    module VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_permission (module, action),
    INDEX idx_module (module)
);

-- User Roles Junction Table
CREATE TABLE user_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_role (user_id, role_id),
    INDEX idx_user (user_id),
    INDEX idx_role (role_id)
);

-- Role Permissions Junction Table
CREATE TABLE role_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role_id, permission_id),
    INDEX idx_role (role_id),
    INDEX idx_permission (permission_id)
);
```

### Client Management

```sql
-- Clients Table
CREATE TABLE clients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(150),
    profile_photo_path VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_name (first_name, last_name),
    INDEX idx_email (email),
    INDEX idx_status (status)
);

-- Client Addresses Table (up to 3 addresses per client)
CREATE TABLE client_addresses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    address_type ENUM('billing', 'shipping', 'home', 'work', 'other') DEFAULT 'billing',
    street_address VARCHAR(255) NOT NULL,
    barangay VARCHAR(100),
    city VARCHAR(100) NOT NULL,
    province VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'Philippines',
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_type (address_type)
);

-- Client Contacts Table (up to 5 contacts per client)
CREATE TABLE client_contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    contact_type ENUM('mobile', 'landline', 'work', 'home', 'other') DEFAULT 'mobile',
    contact_number VARCHAR(20) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_contact (contact_number)
);
```

### Product Management

```sql
-- Categories Table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_name (name),
    INDEX idx_status (status)
);

-- Colors Table
CREATE TABLE colors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    hex_code VARCHAR(7),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_name (name),
    INDEX idx_status (status)
);

-- Sizes Table
CREATE TABLE sizes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    code VARCHAR(10),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_name (name),
    INDEX idx_status (status)
);

-- Products Table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sku VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT,
    color_id INT,
    size_id INT,
    cost_price DECIMAL(12, 2) NOT NULL,
    selling_price DECIMAL(12, 2) NOT NULL,
    unit_type ENUM('piece', 'box', 'dozen', 'kg', 'meter', 'liter', 'other') DEFAULT 'piece',
    current_stock INT DEFAULT 0,
    low_stock_alert INT,
    image_path VARCHAR(255),
    barcode VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE SET NULL,
    FOREIGN KEY (size_id) REFERENCES sizes(id) ON DELETE SET NULL,
    INDEX idx_sku (sku),
    INDEX idx_name (name),
    INDEX idx_category (category_id),
    INDEX idx_status (status)
);

-- Product Images Table (multiple images per product)
CREATE TABLE product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id)
);
```

### Inventory Management

> **Architecture Note (v2):** Inventory is now owned by **Stock Products**, not directly by sellable Products.
> Sellable products assign which stock products they consume and how many per unit sold.
> Checkout deducts stock from stock products only. Restock replenishes stock products only.

```sql
-- Stock Products Table (inventory-tracked raw/physical items)
-- NOTE: No category_id — stock identity is determined by type + color + size + code,
--       and the same stock can be shared across sellable products of different categories.
CREATE TABLE stock_products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type_id INT,
    color_id INT,
    size_id INT,
    current_qty INT DEFAULT 0,
    low_stock_alert INT DEFAULT 10,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (type_id) REFERENCES types(id) ON DELETE SET NULL,
    FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE SET NULL,
    FOREIGN KEY (size_id) REFERENCES sizes(id) ON DELETE SET NULL,
    INDEX idx_code (code),
    INDEX idx_name (name),
    INDEX idx_type (type_id),
    INDEX idx_status (status)
);

-- Product Stock Requirements Table (sellable product → stock product assignment)
CREATE TABLE product_stock_requirements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    stock_product_id INT NOT NULL,
    qty_required_per_unit DECIMAL(10, 4) NOT NULL DEFAULT 1,
    waste_percent DECIMAL(5, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (stock_product_id) REFERENCES stock_products(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_product_stock (product_id, stock_product_id),
    INDEX idx_product (product_id),
    INDEX idx_stock_product (stock_product_id)
);

-- Inventory Table (mirrors stock_products current state; keyed by stock_product_id)
CREATE TABLE inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    stock_product_id INT UNIQUE NOT NULL,
    quantity_on_hand INT DEFAULT 0,
    quantity_reserved INT DEFAULT 0,
    stock_status ENUM('in_stock', 'low_stock', 'out_of_stock') DEFAULT 'in_stock',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (stock_product_id) REFERENCES stock_products(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_stock_product (stock_product_id),
    INDEX idx_status (stock_status)
);

-- Stock Movement History Table (now tracks stock_product_id)
CREATE TABLE stock_movements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    stock_product_id INT NOT NULL,
    product_id INT,
    movement_type ENUM('purchase', 'sale', 'adjustment', 'damage', 'loss') NOT NULL,
    quantity_change INT NOT NULL,
    reason TEXT,
    reference_id INT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stock_product_id) REFERENCES stock_products(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_stock_product (stock_product_id),
    INDEX idx_product (product_id),
    INDEX idx_date (created_at),
    INDEX idx_type (movement_type)
);

-- Restock Orders Table
CREATE TABLE restock_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(100) UNIQUE NOT NULL,
    order_date DATE NOT NULL,
    delivery_date DATE,
    supplier_name VARCHAR(255),
    delivery_status ENUM('ordered', 'delivered', 'incomplete', 'problematic') DEFAULT 'ordered',
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_order_date (order_date),
    INDEX idx_status (delivery_status)
);

-- Restock Items Table (now targets stock_product_id, not product_id)
CREATE TABLE restock_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    restock_id INT NOT NULL,
    stock_product_id INT NOT NULL,
    quantity_requested INT NOT NULL,
    quantity_received INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (restock_id) REFERENCES restock_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (stock_product_id) REFERENCES stock_products(id) ON DELETE RESTRICT,
    INDEX idx_restock (restock_id),
    INDEX idx_stock_product (stock_product_id)
);
```

### Sales & Invoicing

```sql
-- Invoices Table
CREATE TABLE invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    invoice_date DATETIME NOT NULL,
    client_id INT,
    subtotal DECIMAL(12, 2) NOT NULL,
    discount_amount DECIMAL(12, 2) DEFAULT 0,
    discount_type ENUM('fixed', 'percentage'),
    tax_amount DECIMAL(12, 2) DEFAULT 0,
    tax_type ENUM('fixed', 'percentage'),
    additional_fee DECIMAL(12, 2) DEFAULT 0,
    total_amount DECIMAL(12, 2) NOT NULL,
    total_paid DECIMAL(12, 2) DEFAULT 0,
    payment_status ENUM('fully_paid', 'partially_paid', 'unpaid') DEFAULT 'unpaid',
    invoice_sent ENUM('sent', 'not_sent') DEFAULT 'sent',
    primary_payment_mode ENUM('cash', 'bdo', 'gcash'),
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_date (invoice_date),
    INDEX idx_client (client_id),
    INDEX idx_status (payment_status)
);

-- Invoice Items Table
CREATE TABLE invoice_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(12, 2) NOT NULL,
    line_total DECIMAL(12, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_invoice (invoice_id),
    INDEX idx_product (product_id)
);

-- Payments Table
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    payment_number INT NOT NULL,
    payment_date DATE NOT NULL,
    payment_amount DECIMAL(12, 2) NOT NULL,
    payment_mode ENUM('cash', 'bdo', 'gcash') NOT NULL,
    reference_number VARCHAR(100),
    notes TEXT,
    recorded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_invoice (invoice_id),
    INDEX idx_date (payment_date),
    UNIQUE KEY unique_payment (invoice_id, payment_number)
);
```

### Transactions & Audit

```sql
-- Transactions Table (Sales, Purchases, Expenses)
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_number VARCHAR(50) UNIQUE NOT NULL,
    transaction_date DATETIME NOT NULL,
    transaction_type ENUM('sale', 'purchase', 'adjustment', 'expense') NOT NULL,
    related_invoice_id INT,
    related_restock_id INT,
    amount DECIMAL(12, 2) NOT NULL,
    description TEXT,
    recorded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (related_invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
    FOREIGN KEY (related_restock_id) REFERENCES restock_orders(id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_type (transaction_type),
    INDEX idx_date (transaction_date),
    INDEX idx_invoice (related_invoice_id)
);

-- Audit Logs Table
CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action_type VARCHAR(50) NOT NULL,
    module_name VARCHAR(100),
    record_id INT,
    old_value LONGTEXT,
    new_value LONGTEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    status ENUM('success', 'failed') DEFAULT 'success',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action_type),
    INDEX idx_date (created_at),
    INDEX idx_module (module_name)
);
```

### System & Notifications

```sql
-- Notifications Table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_record_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_type (notification_type),
    INDEX idx_read (is_read),
    INDEX idx_date (created_at)
);

-- Settings Table
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value LONGTEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
);

-- Invoice Format Template Table
CREATE TABLE invoice_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_name VARCHAR(100) NOT NULL,
    template_content LONGTEXT NOT NULL,
    number_format VARCHAR(100),
    prefix VARCHAR(20),
    suffix VARCHAR(20),
    reset_frequency ENUM('daily', 'monthly', 'yearly') DEFAULT 'yearly',
    current_number INT DEFAULT 0,
    is_active BOOLEAN DEFAULT FALSE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_active (is_active)
);
```

---

## 3. Database Indexes Strategy

### Performance-Critical Indexes
```
users: username, email, status
clients: first_name, last_name, email, status
products: sku, name, category_id, status
invoices: invoice_number, invoice_date, client_id, payment_status
payments: invoice_id, payment_date
inventory: product_id, stock_status
stock_movements: product_id, created_at, movement_type
audit_logs: user_id, created_at, action_type, module_name
notifications: user_id, is_read, created_at
```

---

## 4. Sample Initial Data

### Default Roles
```sql
INSERT INTO roles (name, description) VALUES
('Admin', 'Full system access'),
('Manager', 'Sales and inventory management'),
('Sales Staff', 'Point of sale operations'),
('Inventory Staff', 'Inventory management only');
```

### Default Permissions
```sql
INSERT INTO permissions (module, action) VALUES
('users', 'view'),
('users', 'add'),
('users', 'edit'),
('users', 'delete'),
('products', 'view'),
('products', 'add'),
('products', 'edit'),
('products', 'delete'),
('invoices', 'view'),
('invoices', 'add'),
('invoices', 'edit'),
('invoices', 'delete'),
-- ... and so on for each module
```

### Default Settings
```sql
INSERT INTO settings (setting_key, setting_value, setting_type) VALUES
('business_name', 'Dream Blanks', 'string'),
('currency_symbol', '₱', 'string'),
('date_format', 'MM/DD/YYYY', 'string'),
('time_format', '12-hour', 'string'),
('low_stock_alert_default', '10', 'integer'),
('invoice_prefix', 'INV-', 'string');
```

---

## 5. Relationships Diagram

```
users ← → roles (M:M via user_roles)
roles ← → permissions (M:M via role_permissions)

clients ← 1:M → client_addresses
clients ← 1:M → client_contacts
clients ← 1:M → invoices

categories ← 1:M → products
colors ← 1:M → products
sizes ← 1:M → products

products ← 1:1 → inventory
products ← 1:M → stock_movements
products ← 1:M → invoice_items
products ← 1:M → restock_items

invoices ← 1:M → invoice_items
invoices ← 1:M → payments
invoices ← 1:M → transactions

restock_orders ← 1:M → restock_items
restock_orders ← 1:M → transactions

users ← 1:M → audit_logs
users ← 1:M → notifications
users ← 1:M → transactions
```

---

## 6. Data Integrity Constraints

- **Foreign Key Constraints**: Enforce referential integrity
- **Unique Constraints**: Username, email, SKU, invoice number
- **Check Constraints**: Ensure valid enum values
- **Default Values**: Timestamps, status defaults
- **Soft Deletes**: deleted_at column for data recovery

---

**Document Version**: 1.0 | **Last Updated**: May 2026
