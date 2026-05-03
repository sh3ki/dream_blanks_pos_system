# Dream Blanks POS System - Technical Architecture

## 1. System Architecture Overview

### 1.1 Architecture Style
- **Pattern**: Model-View-Controller (MVC) with modular, component-based OOP design
- **Paradigm**: Object-Oriented Programming (OOP)
- **Approach**: Modular components with shared utilities
- **Scalability**: Designed for shared PHP hosting with optimization for performance

### 1.2 High-Level Architecture Diagram
```
┌─────────────────────────────────────────────────────────────┐
│                     CLIENT TIER (Frontend)                  │
│         HTML5 | CSS3 | JavaScript (ES6+) | UI Components   │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Shared Components: Header, Sidebar, Toast, Modal    │  │
│  │  Theme: Gray & White Minimalist Design              │  │
│  │  Responsive: Desktop & Tablet                        │  │
│  └──────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────┘
                         ↑
                      REST API
                      (AJAX/Fetch)
                         ↓
┌─────────────────────────────────────────────────────────────┐
│              BUSINESS LOGIC TIER (Backend)                  │
│                      PHP 7.4+                               │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Controllers: Handle requests, business logic        │  │
│  │  Services: Core business operations                  │  │
│  │  Repositories: Data access layer                     │  │
│  │  Models: Data structures & ORM                       │  │
│  │  Helpers/Utilities: Shared functions                │  │
│  │  Middleware: Authentication, Authorization, etc.    │  │
│  └──────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────┘
                         ↑
                    SQL Queries
                         ↓
┌─────────────────────────────────────────────────────────────┐
│                 DATA TIER (Database)                        │
│                       MySQL                                 │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Tables: Users, Roles, Permissions, Products,       │  │
│  │  Clients, Inventory, Invoices, Transactions, etc.   │  │
│  │  Relationships: Foreign keys for data integrity      │  │
│  └──────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────┘
```

---

## 2. Technology Stack

### 2.1 Backend
- **Language**: PHP 7.4 or higher (Vanilla - no framework)
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Server**: Apache with mod_rewrite
- **Session Management**: PHP native sessions
- **Authentication**: Session-based authentication
- **Encryption**: bcrypt for passwords, OpenSSL for sensitive data

### 2.2 Frontend
- **HTML5**: Semantic markup
- **CSS3**: Custom styling with grid/flexbox layouts
- **JavaScript**: Vanilla ES6+ (no framework)
- **Libraries** (optional):
  - Chart.js or similar for data visualization
  - DataTables.js for advanced table features
  - date-fns or Moment.js for date handling
  - Fetch API for AJAX calls

### 2.3 Hosting Environment
- **Provider**: Hostinger Shared Hosting (PHP)
- **PHP Version**: 7.4+
- **Storage**: Managed by Hostinger
- **SSL/TLS**: Free SSL certificate (Let's Encrypt)
- **Backup**: Hostinger automated backups
- **Maintenance**: Handled by Hostinger

### 2.4 Development Tools
- **Version Control**: Git
- **Code Editor**: VS Code (or preferred IDE)
- **Database Management**: phpMyAdmin (via Hostinger)
- **Testing**: Manual or PHPUnit (optional)
- **Documentation**: Markdown

---

## 3. Folder Structure

```
dream_blanks_pos_system/
│
├── public/                          # Web-accessible directory
│   ├── index.php                   # Entry point
│   ├── assets/                     # Static files
│   │   ├── css/
│   │   │   ├── style.css          # Main stylesheet
│   │   │   ├── theme.css          # Theme variables
│   │   │   └── responsive.css     # Responsive design
│   │   ├── js/
│   │   │   ├── app.js             # Main app initialization
│   │   │   ├── utils.js           # Utility functions
│   │   │   ├── api.js             # API communication layer
│   │   │   └── components.js      # Component initialization
│   │   ├── images/
│   │   │   ├── logo.png
│   │   │   └── icons/
│   │   └── fonts/
│   │
│   ├── uploads/                    # User uploads (products, profiles)
│   │   ├── products/
│   │   ├── clients/
│   │   └── invoices/
│   │
│   └── .htaccess                  # URL rewriting
│
├── src/                            # Application source code
│   │
│   ├── Core/                       # Core framework components
│   │   ├── Router.php             # URL routing
│   │   ├── Request.php            # HTTP request handling
│   │   ├── Response.php           # HTTP response handling
│   │   ├── Container.php          # Dependency injection
│   │   └── Database.php           # Database connection
│   │
│   ├── Models/                     # Data models
│   │   ├── User.php
│   │   ├── Role.php
│   │   ├── Permission.php
│   │   ├── Client.php
│   │   ├── Product.php
│   │   ├── Category.php
│   │   ├── Color.php
│   │   ├── Size.php
│   │   ├── Inventory.php
│   │   ├── Invoice.php
│   │   ├── InvoiceItem.php
│   │   ├── Payment.php
│   │   ├── Restock.php
│   │   ├── Transaction.php
│   │   ├── AuditLog.php
│   │   └── Notification.php
│   │
│   ├── Controllers/                # Request handlers
│   │   ├── AuthController.php
│   │   ├── UserController.php
│   │   ├── RoleController.php
│   │   ├── ClientController.php
│   │   ├── ProductController.php
│   │   ├── CategoryController.php
│   │   ├── InventoryController.php
│   │   ├── PosController.php
│   │   ├── InvoiceController.php
│   │   ├── PaymentController.php
│   │   ├── ReportController.php
│   │   ├── DashboardController.php
│   │   ├── NotificationController.php
│   │   └── SettingsController.php
│   │
│   ├── Services/                   # Business logic
│   │   ├── AuthService.php
│   │   ├── UserService.php
│   │   ├── RoleService.php
│   │   ├── ClientService.php
│   │   ├── ProductService.php
│   │   ├── InventoryService.php
│   │   ├── PosService.php
│   │   ├── InvoiceService.php
│   │   ├── PaymentService.php
│   │   ├── ReportService.php
│   │   ├── NotificationService.php
│   │   └── AuditService.php
│   │
│   ├── Repositories/               # Data access layer
│   │   ├── UserRepository.php
│   │   ├── ClientRepository.php
│   │   ├── ProductRepository.php
│   │   ├── InventoryRepository.php
│   │   ├── InvoiceRepository.php
│   │   ├── TransactionRepository.php
│   │   └── AuditLogRepository.php
│   │
│   ├── Middleware/                 # Request middleware
│   │   ├── AuthMiddleware.php
│   │   ├── PermissionMiddleware.php
│   │   └── LoggingMiddleware.php
│   │
│   ├── Helpers/                    # Utility functions
│   │   ├── DateHelper.php
│   │   ├── NumberHelper.php
│   │   ├── FileHelper.php
│   │   ├── ValidationHelper.php
│   │   ├── EncryptionHelper.php
│   │   └── PdfHelper.php
│   │
│   ├── Traits/                     # Shared functionality
│   │   ├── Timestampable.php       # created_at, updated_at
│   │   ├── SoftDeletable.php       # Soft delete support
│   │   └── Loggable.php            # Action logging
│   │
│   ├── Exceptions/                 # Custom exceptions
│   │   ├── AuthException.php
│   │   ├── ValidationException.php
│   │   └── NotFoundException.php
│   │
│   ├── Validations/                # Validation rules
│   │   ├── UserValidator.php
│   │   ├── ClientValidator.php
│   │   ├── ProductValidator.php
│   │   └── InvoiceValidator.php
│   │
│   └── Views/                      # View templates (HTML)
│       ├── layouts/
│       │   ├── main.php            # Main layout
│       │   ├── header.php
│       │   ├── sidebar.php
│       │   └── footer.php
│       │
│       ├── auth/
│       │   ├── login.php
│       │   ├── forgot-password.php
│       │   └── reset-password.php
│       │
│       ├── dashboard/
│       │   ├── index.php
│       │   └── widgets/
│       │
│       ├── users/
│       │   ├── index.php
│       │   ├── create.php
│       │   └── edit.php
│       │
│       ├── roles/
│       │   ├── index.php
│       │   └── manage-permissions.php
│       │
│       ├── clients/
│       │   ├── index.php
│       │   ├── create.php
│       │   ├── edit.php
│       │   └── show.php
│       │
│       ├── products/
│       │   ├── index.php
│       │   ├── create.php
│       │   ├── edit.php
│       │   └── show.php
│       │
│       ├── inventory/
│       │   ├── index.php
│       │   └── restock.php
│       │
│       ├── pos/
│       │   └── index.php
│       │
│       ├── invoices/
│       │   ├── index.php
│       │   ├── show.php
│       │   ├── generator.php      # Invoice template editor
│       │   └── print.php
│       │
│       ├── reports/
│       │   ├── sales.php
│       │   ├── inventory.php
│       │   ├── financial.php
│       │   └── audit.php
│       │
│       └── settings/
│           ├── general.php
│           ├── invoice-format.php
│           └── notifications.php
│
├── config/                         # Configuration files
│   ├── database.php               # DB connection config
│   ├── app.php                    # App configuration
│   ├── paths.php                  # Path definitions
│   └── constants.php              # Application constants
│
├── database/                       # Database related
│   ├── migrations/                # Database schema migrations
│   │   ├── 001_create_users_table.php
│   │   ├── 002_create_roles_table.php
│   │   ├── 003_create_permissions_table.php
│   │   └── [more migrations]
│   │
│   ├── seeds/                     # Database seeders
│   │   ├── UserSeeder.php
│   │   ├── RoleSeeder.php
│   │   └── PermissionSeeder.php
│   │
│   └── schema.sql                 # Full database schema
│
├── tests/                          # Test files (optional)
│   ├── Unit/
│   └── Feature/
│
├── logs/                           # Application logs
│   ├── error.log
│   ├── access.log
│   └── audit.log
│
├── .env.example                   # Environment configuration template
├── .gitignore                     # Git ignore rules
├── README.md                      # Project documentation
└── composer.json                  # PHP dependencies (optional)
```

---

## 4. Database Design

### 4.1 Core Tables

#### Users Table
```sql
users
├── id (PK)
├── username (UNIQUE)
├── email (UNIQUE)
├── first_name
├── middle_name
├── last_name
├── password (hashed)
├── profile_photo_path
├── status (active/inactive)
├── last_login
├── created_at
├── updated_at
└── deleted_at (soft delete)
```

#### Roles Table
```sql
roles
├── id (PK)
├── name (UNIQUE)
├── description
├── status (active/inactive)
├── created_at
├── updated_at
└── deleted_at
```

#### Permissions Table
```sql
permissions
├── id (PK)
├── name (view, add, edit, delete)
├── module (users, products, inventory, etc.)
├── description
├── created_at
└── updated_at
```

#### Role-Permission Junction Table
```sql
role_permissions
├── role_id (FK → roles)
├── permission_id (FK → permissions)
├── created_at
└── PRIMARY KEY (role_id, permission_id)
```

#### User-Role Junction Table
```sql
user_roles
├── user_id (FK → users)
├── role_id (FK → roles)
├── assigned_at
└── PRIMARY KEY (user_id, role_id)
```

#### Clients Table
```sql
clients
├── id (PK)
├── first_name
├── middle_name
├── last_name
├── email
├── profile_photo_path
├── status (active/inactive)
├── created_at
├── updated_at
└── deleted_at
```

#### Client-Addresses Table
```sql
client_addresses
├── id (PK)
├── client_id (FK → clients)
├── address_type (billing, shipping, home, etc.)
├── street_address
├── barangay
├── city
├── province
├── postal_code
├── country
├── is_primary
├── created_at
├── updated_at
└── deleted_at
```

#### Client-Contacts Table
```sql
client_contacts
├── id (PK)
├── client_id (FK → clients)
├── contact_type (mobile, landline, work, home, other)
├── contact_number
├── is_primary
├── is_verified
├── created_at
└── updated_at
```

#### Products Table
```sql
products
├── id (PK)
├── sku (UNIQUE)
├── name
├── description
├── category_id (FK → categories, nullable)
├── color_id (FK → colors, nullable)
├── size_id (FK → sizes, nullable)
├── cost_price
├── selling_price
├── unit_type (piece, box, dozen, etc.)
├── current_stock
├── low_stock_alert
├── image_path
├── barcode
├── status (active/inactive)
├── created_at
├── updated_at
└── deleted_at
```

#### Categories, Colors, Sizes Tables
```sql
categories
├── id (PK)
├── name (UNIQUE)
├── description
├── status (active/inactive)
├── created_at
├── updated_at
└── deleted_at

colors
├── id (PK)
├── name (UNIQUE)
├── hex_code
├── status (active/inactive)
├── created_at
├── updated_at
└── deleted_at

sizes
├── id (PK)
├── name (UNIQUE)
├── code
├── status (active/inactive)
├── created_at
├── updated_at
└── deleted_at
```

#### Inventory Table
```sql
inventory
├── id (PK)
├── product_id (FK → products)
├── quantity_on_hand
├── quantity_reserved
├── quantity_available (calculated)
├── stock_status (in_stock, low_stock, out_of_stock)
├── last_updated
└── updated_by (FK → users)
```

#### Restock Orders Table
```sql
restock_orders
├── id (PK)
├── order_date
├── delivery_date
├── supplier_id (nullable, text field for now)
├── delivery_status (ordered, delivered, incomplete, problematic)
├── created_by (FK → users)
├── notes
├── created_at
└── updated_at
```

#### Restock-Items Table
```sql
restock_items
├── id (PK)
├── restock_id (FK → restock_orders)
├── product_id (FK → products)
├── quantity_requested
├── quantity_received
├── created_at
└── updated_at
```

#### Invoices Table
```sql
invoices
├── id (PK)
├── invoice_number (UNIQUE)
├── invoice_date
├── client_id (FK → clients, nullable)
├── subtotal
├── discount_amount
├── tax_amount
├── additional_fee
├── total_amount
├── total_paid
├── payment_status (fully_paid, partially_paid, unpaid)
├── invoice_sent (sent/not_sent)
├── payment_mode (cash, bdo, gcash)
├── notes
├── created_by (FK → users)
├── created_at
├── updated_at
└── deleted_at
```

#### Invoice-Items Table
```sql
invoice_items
├── id (PK)
├── invoice_id (FK → invoices)
├── product_id (FK → products)
├── quantity
├── unit_price
├── line_total (quantity × unit_price)
└── created_at
```

#### Payments Table
```sql
payments
├── id (PK)
├── invoice_id (FK → invoices)
├── payment_date
├── payment_amount
├── payment_mode (cash, bdo, gcash)
├── reference_number
├── notes
├── recorded_by (FK → users)
├── created_at
└── updated_at
```

#### Transactions Table
```sql
transactions
├── id (PK)
├── transaction_date
├── transaction_type (sale, purchase, adjustment, expense)
├── related_invoice_id (FK → invoices, nullable)
├── related_product_id (FK → products, nullable)
├── amount
├── description
├── recorded_by (FK → users)
├── created_at
└── deleted_at
```

#### Audit-Logs Table
```sql
audit_logs
├── id (PK)
├── user_id (FK → users)
├── action_type (create, update, delete, login, logout, view, etc.)
├── module_name
├── record_id
├── old_value (JSON)
├── new_value (JSON)
├── ip_address
├── user_agent
├── status (success, failed)
├── description
├── created_at
└── deleted_at
```

#### Notifications Table
```sql
notifications
├── id (PK)
├── user_id (FK → users)
├── notification_type
├── title
├── message
├── related_record_id (nullable)
├── is_read
├── is_deleted
├── created_at
└── updated_at
```

---

## 5. API Architecture

### 5.1 API Endpoints Structure
- **Base URL**: `/api/v1/`
- **Response Format**: JSON
- **Authentication**: Session-based with CSRF token
- **HTTP Methods**: GET, POST, PUT, DELETE

### 5.2 Response Structure
```json
{
  "success": true,
  "code": 200,
  "message": "Operation successful",
  "data": { /* response data */ },
  "errors": null,
  "timestamp": "2026-05-01T12:00:00Z"
}
```

### 5.3 Core API Endpoints

#### Authentication
- `POST /api/v1/auth/login` - User login
- `POST /api/v1/auth/logout` - User logout
- `POST /api/v1/auth/forgot-password` - Request password reset
- `POST /api/v1/auth/verify-otp` - Verify OTP
- `POST /api/v1/auth/reset-password` - Reset password

#### Users
- `GET /api/v1/users` - List users
- `POST /api/v1/users` - Create user
- `GET /api/v1/users/{id}` - Get user
- `PUT /api/v1/users/{id}` - Update user
- `DELETE /api/v1/users/{id}` - Delete user

#### Roles & Permissions
- `GET /api/v1/roles` - List roles
- `POST /api/v1/roles` - Create role
- `PUT /api/v1/roles/{id}/permissions` - Assign permissions

#### Products
- `GET /api/v1/products` - List products
- `POST /api/v1/products` - Create product
- `GET /api/v1/products/{id}` - Get product
- `PUT /api/v1/products/{id}` - Update product
- `DELETE /api/v1/products/{id}` - Delete product

#### Inventory
- `GET /api/v1/inventory` - List inventory
- `POST /api/v1/inventory/restock` - Create restock order
- `PUT /api/v1/inventory/restock/{id}` - Update restock

#### Invoices
- `GET /api/v1/invoices` - List invoices
- `POST /api/v1/invoices` - Create invoice
- `GET /api/v1/invoices/{id}` - Get invoice
- `POST /api/v1/invoices/{id}/payments` - Add payment
- `GET /api/v1/invoices/{id}/print` - Get printable invoice

#### POS
- `GET /api/v1/pos/products` - Get products for POS
- `POST /api/v1/pos/checkout` - Process checkout
- `POST /api/v1/pos/receipt` - Generate receipt

#### Reports
- `GET /api/v1/reports/sales` - Sales report
- `GET /api/v1/reports/inventory` - Inventory report
- `GET /api/v1/reports/financial` - Financial report
- `GET /api/v1/reports/export` - Export report as CSV

#### Dashboard
- `GET /api/v1/dashboard/metrics` - Dashboard metrics
- `GET /api/v1/dashboard/charts` - Dashboard charts data

---

## 6. Security Architecture

### 6.1 Authentication & Authorization
- **Session-Based Auth**: PHP native sessions with secure cookies
- **Password Hashing**: bcrypt with cost factor 12
- **CSRF Protection**: Token validation on all state-changing requests
- **SQL Injection Prevention**: Prepared statements and parameterized queries
- **XSS Prevention**: Input validation and output escaping

### 6.2 Data Protection
- **Sensitive Data Encryption**: OpenSSL for emails, phone numbers
- **SSL/TLS**: HTTPS for all communications
- **Database Backups**: Regular automated backups
- **Access Logs**: All database access logged

### 6.3 Role-Based Access Control (RBAC)
- **Granular Permissions**: View, Add, Edit, Delete per module
- **Permission Checking**: Middleware validates permissions before request handling
- **Audit Trail**: All access logged in audit_logs table

---

## 7. Performance Optimization

### 7.1 Database Optimization
- **Indexing**: Indexes on frequently searched columns
- **Query Optimization**: Use of JOINs, avoiding N+1 queries
- **Connection Pooling**: Reuse database connections
- **Query Caching**: Cache frequently accessed data

### 7.2 Frontend Optimization
- **Asset Minification**: Minified CSS and JavaScript
- **Lazy Loading**: Images and components loaded on demand
- **Pagination**: Tables paginated to limit data transfer
- **Compression**: GZIP compression for responses

### 7.3 Caching Strategy
- **Session Caching**: User permissions cached in session
- **Page Caching**: Static pages cached
- **Data Caching**: Frequently accessed data cached (optional Redis)

---

## 8. Deployment Architecture

### 8.1 Hosting Environment
- **Server**: Hostinger PHP Shared Hosting
- **Web Server**: Apache
- **PHP Version**: 7.4 or higher
- **MySQL Version**: 5.7+
- **SSL**: Free Let's Encrypt certificate

### 8.2 Directory Structure on Server
```
public_html/
├── index.php              # Application entry point
├── .htaccess             # Apache rewrite rules
├── assets/               # Public assets
└── [rest of application directories]

private/                  # Private directory (outside public_html)
├── src/                  # Application code
├── config/               # Configuration
├── database/             # Database files
└── logs/                 # Application logs
```

### 8.3 Environment Configuration
- **.env file**: Database credentials, API keys, debug mode
- **Database Connection**: Hostinger MySQL database
- **File Uploads**: Hosted in public/uploads directory
- **Backups**: Daily automated backups via Hostinger

---

## 9. Code Organization Principles

### 9.1 OOP & Modularity
- **Classes**: Each major component is a class
- **Interfaces**: Define contracts for implementations
- **Inheritance**: Shared functionality inherited from base classes
- **Traits**: Reusable functionality (Timestampable, SoftDeletable, etc.)
- **Encapsulation**: Private methods and properties, public interfaces

### 9.2 Design Patterns
- **MVC Pattern**: Separation of concerns
- **Repository Pattern**: Data access abstraction
- **Service Pattern**: Business logic encapsulation
- **Middleware Pattern**: Request/response processing
- **Singleton Pattern**: Database connection (optional)

### 9.3 Namespacing
- All classes organized under `App\` namespace
- Sub-namespaces: `App\Models`, `App\Controllers`, `App\Services`, etc.
- PSR-4 autoloading with Composer

---

## 10. Dependency Management

### 10.1 External Libraries (Optional/Recommended)
- **Charts**: Chart.js (frontend)
- **Table Enhancement**: DataTables.js (frontend)
- **Date Handling**: date-fns or Moment.js (frontend)
- **PDF Generation**: TCPDF or mPDF (backend)
- **Email**: PHPMailer (backend)
- **Validation**: respect/validation (backend)

### 10.2 Package Management
- **Composer**: Manage PHP dependencies
- **composer.json**: Document project dependencies
- **Autoloading**: PSR-4 standard for class autoloading

---

## 11. Error Handling & Logging

### 11.1 Error Handling
- **Custom Exceptions**: AuthException, ValidationException, etc.
- **Error Pages**: User-friendly error pages
- **Debug Mode**: Toggle-able debug output for development
- **Production Safety**: Safe error messages in production

### 11.2 Logging
- **Application Logs**: Error, warning, info, debug levels
- **Audit Logs**: All user actions logged
- **Access Logs**: Web server access logs
- **File Location**: `/logs` directory with rotation

---

## 12. Testing Strategy

### 12.1 Testing Types
- **Unit Tests**: Test individual functions/methods
- **Integration Tests**: Test component interactions
- **System Tests**: Test entire workflows
- **Manual Testing**: UI/UX testing before release

### 12.2 Test Tools (Optional)
- **PHPUnit**: PHP unit testing framework
- **Postman**: API testing
- **Browser DevTools**: Frontend testing

---

## Architecture Summary

| Aspect | Technology |
|--------|-----------|
| **Language** | PHP 7.4+ (OOP, Vanilla) |
| **Database** | MySQL 5.7+ |
| **Frontend** | HTML5, CSS3, JavaScript (ES6+) |
| **Architecture** | MVC with modular components |
| **Hosting** | Hostinger PHP Shared |
| **Authentication** | Session-based + CSRF |
| **API** | RESTful JSON endpoints |
| **Caching** | Session & optional Redis |
| **Deployment** | Git + manual/automated FTP |

---

**Document Version**: 1.0 | **Last Updated**: May 2026
