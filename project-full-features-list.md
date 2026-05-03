# Dream Blanks POS System - Complete Features List

## 1. Authentication & User Management

### 1.1 Login System
- **Email/Username Login**: Users can authenticate using either email or username
- **Password-Based Authentication**: Secure password validation
- **Session Management**: User sessions maintained throughout the day
- **Login Audit Trail**: All login attempts logged with timestamp and IP

### 1.2 Password Recovery
- **Forgot Password**: Email-based password reset flow
- **OTP Verification**: One-Time Password sent to registered email
- **OTP Validation**: Time-limited OTP (configurable expiry)
- **Password Reset**: Secure password reset after OTP verification
- **Email Confirmation**: Confirmation email after successful password reset

### 1.3 User Profile Management
- **Profile Information**:
  - First Name, Middle Name, Last Name
  - Email Address (unique)
  - Username (unique)
  - Profile Photo (optional)
- **Profile Editing**: Users can update their own information
- **Account Settings**: Change password, update contact information
- **User Deactivation**: Admin can deactivate user accounts

---

## 2. Roles & Permissions Management

### 2.1 Role Configuration
- **Predefined Roles**: Admin, Manager, Sales Staff, Inventory Staff
- **Custom Roles**: Create custom roles as needed
- **Role Management**: Edit, delete, and activate/deactivate roles

### 2.2 Permission Types
- **View**: Read-only access to module data
- **Add**: Create new records
- **Edit**: Modify existing records
- **Delete**: Remove records

### 2.3 Permission Assignment
- **Granular Control**: Assign permissions per module for each role
- **Module-Level Permissions**: Permissions for each major feature/module
- **Permission Matrix**: Visual representation of role-permission mapping
- **Permission Inheritance**: Option for roles to inherit permissions

### 2.4 User-Role Assignment
- **Single or Multiple Roles**: Assign one or more roles to users
- **Role Activation/Deactivation**: Enable/disable roles per user
- **Effective Permissions**: System combines all assigned role permissions

---

## 3. Client Management

### 3.1 Client Profile
- **Basic Information**:
  - First Name, Middle Name, Last Name (required)
  - Profile Photo (optional)
  - Email (optional)
  - Status (Active/Inactive)
  - Registration Date

### 3.2 Client Contact Information
- **Multiple Contacts**: Store up to 5 contact numbers
- **Contact Types**: Mobile, Landline, Work, Home, Other
- **Primary Contact**: Designate one as primary
- **Contact Verification**: Flag for verified contacts

### 3.3 Client Addresses
- **Multiple Addresses**: Store up to 3 addresses
- **Address Types**: Billing, Shipping, Work, Home, Other
- **Primary Address**: Designate one as primary for invoicing
- **Address Details**: Street, Barangay, City, Province, Postal Code, Country

### 3.4 Client Operations
- **Add Client**: Create new client records
- **Edit Client**: Modify client information
- **Delete Client**: Remove client (soft delete with archive)
- **View Client**: Access complete client profile
- **Search Clients**: Search by name, contact, email
- **Filter Clients**: Filter by status, date range, location
- **Export Clients**: Download client list as CSV

### 3.5 Client History
- **Transaction History**: View all transactions for a client
- **Payment History**: Track all payments received from client
- **Account Balance**: Outstanding amount owed by client

---

## 4. Product Management

### 4.1 Product Information
- **Product Details**:
  - Product Name (required)
  - Product Image (optional, can upload multiple images)
  - Description (optional)
  - SKU (unique identifier)
  - Barcode (optional)
  - Status (Active/Inactive - default: Active)

### 4.2 Product Pricing & Inventory
- **Pricing**:
  - Cost Price (required) - internal cost to business
  - Selling Price (required) - retail price
  - Profit Margin (auto-calculated)
- **Inventory**:
  - Unit Type (Piece, Box, Dozen, etc. - default: Piece)
  - Initial Stock (required)
  - Current Stock (tracked automatically)
  - Low Stock Alert Threshold (optional)

### 4.3 Product Variants/Attributes
- **Category**: Select from database (optional)
- **Color**: Select from database (optional)
- **Size**: Select from database (optional)
- **Custom Attributes**: Add additional variant fields if needed

### 4.4 Product Operations
- **Add Product**: Create new product records
- **Edit Product**: Modify product information (except SKU)
- **Delete Product**: Archive or soft delete products
- **View Product**: Full product details page
- **Search Products**: Search by name, SKU, barcode, category
- **Filter Products**: Filter by category, color, size, status, stock level
- **Bulk Upload**: Import products from CSV
- **Bulk Edit**: Edit multiple products at once
- **Export Products**: Download product list as CSV

### 4.5 Stock Management
- **Stock Tracking**: Real-time inventory count
- **Stock Adjustment**: Manual adjustment with reason logging
- **Low Stock Alerts**: Automatic alerts when stock falls below threshold
- **Stock History**: Audit trail of all stock movements

---

## 5. Product Variations Management

### 5.1 Categories
- **Category Management**:
  - Category Name (required, unique)
  - Description (optional)
  - Status (Active/Inactive - default: Active)
- **Operations**: Add, Edit, Delete, View
- **Category Display**: Used in product filtering and classification

### 5.2 Colors
- **Color Management**:
  - Color Name (required, unique)
  - Color Code (hex, optional)
  - Color Preview (visual representation)
  - Status (Active/Inactive - default: Active)
- **Operations**: Add, Edit, Delete, View
- **Multiple Products**: One color can be assigned to multiple products

### 5.3 Sizes
- **Size Management**:
  - Size Name (required, unique) - e.g., Small, Medium, Large, XL, etc.
  - Size Code (optional) - e.g., S, M, L, XL
  - Status (Active/Inactive - default: Active)
- **Operations**: Add, Edit, Delete, View
- **Multiple Products**: One size can be assigned to multiple products

### 5.4 Variant Operations
- **Bulk Management**: Add multiple variants at once
- **Search & Filter**: Find specific variants
- **Deactivation**: Deactivate unused variants without deleting
- **Usage Tracking**: See which products use each variant

---

## 6. Inventory Management

### 6.1 Inventory Overview
- **Stock Dashboard**: Real-time view of all product inventory
- **Stock Levels**: Display current quantity, reorder point, status
- **Stock Status**: 
  - In Stock (quantity > reorder point)
  - Low Stock (quantity ≤ reorder point)
  - Out of Stock (quantity = 0)

### 6.2 Inventory Operations
- **View Inventory**: Complete inventory listing with pagination
- **Search Inventory**: Find products by name, category, color, size
- **Filter Inventory**: Filter by stock status, category, date range
- **Sort Inventory**: Sort by quantity, product name, status
- **Export Inventory**: Download as CSV

### 6.3 Restocking Management
- **Restock Request**: Create restock orders for products
- **Bulk Restock**: Add multiple products to single restock order
- **Restock Details**:
  - Product (name, category, color, size)
  - Current Quantity
  - Restock Quantity (how many to order)
  - Order Date (recorded automatically)
  - Delivery Date (expected)
  - Supplier Name
  - Delivery Status:
    - Ordered (default)
    - Delivered
    - Incomplete
    - Problematic
  - Days to Deliver (auto-calculated from order to delivery date)
  - Notes (optional)
  - Recorded By (user who created the restock)

### 6.4 Restock Operations
- **Create Restock Order**: Initiate new restock request
- **View Restock Orders**: See all pending and completed restocks
- **Update Delivery Status**: Mark order as delivered/incomplete/problematic
- **Edit Restock**: Modify order details before delivery
- **Cancel Restock**: Cancel incomplete restock orders
- **Restock History**: Complete audit trail of all restocking activities
- **Filter & Search**: By supplier, status, date range
- **Export Restock Reports**: Download as CSV

### 6.5 Stock Movement Tracking
- **Movement Log**: Record of all inventory changes
- **Reasons for Movement**: Purchase, Sale, Adjustment, Damage, Loss
- **User Tracking**: Know who made each adjustment
- **Timestamp**: Exact time of each movement

---

## 7. Point of Sale (POS) Module

### 7.1 POS Interface Layout
- **Main Display**:
  - Left Side: Shopping cart with order items
  - Center: Product grid with search/filter
  - Right Side: Order summary and payment section

### 7.2 Product Display
- **Product Cards**:
  - Product Image (thumbnail)
  - Product Name
  - Category, Color, Size (variants)
  - Selling Price
  - Stock Status indicator
  - "Add to Cart" action (entire card is clickable)

### 7.3 Search & Filter
- **Search Functionality**: Search by product name, SKU, category
- **Category Filter**: Filter by product category
- **Color Filter**: Filter by available colors
- **Size Filter**: Filter by available sizes
- **Quick Search**: Real-time search as user types
- **Search History**: Recent searches (optional)

### 7.4 Shopping Cart
- **Add to Cart**:
  - Add product with default quantity (1)
  - Increment/Decrement quantity
  - Remove item from cart
  - Clear entire cart
- **Cart Display**:
  - Product details (name, category, color, size)
  - Unit price
  - Quantity
  - Line total (price × quantity)
  - Running cart total

### 7.5 Order Information
- **Client Selection** (optional):
  - Dropdown to select existing client
  - "Walk-in" option if no client selected
  - Quick add new client from POS (optional)

### 7.6 Order Adjustments
- **Discount** (optional):
  - Fixed amount discount
  - Percentage discount
  - Multiple discounts (cumulative)
  - Discount reason/note

- **Tax** (optional):
  - Fixed tax amount or percentage
  - Tax types (VAT, Sales Tax, etc.)
  - Tax amount calculated automatically

- **Additional Fee** (optional):
  - Shipping fee
  - Handling fee
  - Service charge
  - Custom fee with description

- **Notes** (optional):
  - Order notes/special instructions
  - Delivery notes
  - Internal notes for kitchen/preparation

### 7.7 Checkout & Payment

#### Payment Types
- **Full Payment**: Pay entire invoice amount
- **Partial Payment**: Pay portion of amount, balance due later
- **Unpaid**: Create invoice without payment (default is full payment)

#### Payment Modes
- **Cash**: Cash payment received
- **BDO**: Bank of the Philippines Islands card/transfer
- **GCash**: Mobile wallet payment

#### Checkout Process
1. Review order summary
2. Apply discounts/fees/tax if needed
3. Select payment mode
4. Enter payment amount
5. Confirm payment
6. Generate receipt/invoice
7. Print or send invoice
8. Order complete

### 7.8 Receipt/Invoice Generation
- **Auto-Generated Receipt**: Immediately upon checkout
- **Invoice Format**: Professional format (as per attached image template)
- **Invoice Details**:
  - Invoice number (auto-generated, customizable format in settings)
  - Invoice date and time
  - Business details (name, address, contact)
  - Client details (if applicable)
  - Itemized product list with quantities and prices
  - Subtotal, discounts, tax, fees
  - Grand total
  - Payment information (mode, amount received, balance)
  - Sales staff name
  - Notes/special instructions
  - Thank you message

### 7.9 POS Operations
- **View Active Orders**: See current session orders
- **Hold Order**: Temporarily hold an order and create new one
- **Retrieve Held Order**: Resume a held order later
- **Void Order**: Cancel entire order with reason
- **Print Receipt**: Print order receipt/invoice
- **Email Receipt**: Send receipt to client email
- **Save Draft Invoice**: Save order as draft without finalizing

---

## 8. Invoice Management

### 8.1 Invoice Generator
- **Custom Invoice Templates**:
  - Edit invoice format in settings
  - Drag-and-drop layout builder
  - Customize fields and their positions
  - Add company logo and branding
  - Choose color scheme
  - Set font styles and sizes
  - Add custom footer text/messages

### 8.2 Invoice Number Generation
- **Auto-Generated Numbers**: System generates unique invoice numbers
- **Customizable Format**:
  - Number sequence (000001, 001, etc.)
  - Prefix (INV-, 2026-, etc.)
  - Date components (YY, MM, DD)
  - Reset frequency (daily, monthly, yearly)
- **Manual Override**: Ability to manually enter invoice number if needed

### 8.3 Invoice Creation
- **From POS**: Automatically generated during checkout
- **Manual Creation**: Manually create invoices for historical transactions
- **Duplicate Invoice**: Create invoice copy for reprint/resend

### 8.4 Invoice Operations
- **View Invoice**: Full invoice details
- **Edit Invoice**: Modify invoice (before it's marked as sent)
- **Print Invoice**: Print to physical printer or PDF
- **Email Invoice**: Send to client's email address
- **Reprint Invoice**: Print copy of existing invoice
- **Resend Invoice**: Send invoice again to client
- **Delete Invoice**: Remove/archive invoice (soft delete)
- **Archive Invoice**: Move old invoices to archive

---

## 9. Invoice Tracking & Payment Management

### 9.1 Invoice Information
- **Invoice Details**:
  - Invoice Number (unique)
  - Invoice Date
  - Customer/Client Name
  - Total Amount (original invoice amount)
  - Status: Fully Paid, Partially Paid, Unpaid
  - Payment Mode(s): Cash, BDO, GCash

### 9.2 Invoice Status
- **Fully Paid**: Invoice amount completely received
- **Partially Paid**: Part of invoice paid, balance outstanding
- **Unpaid**: No payment received yet

### 9.3 Payment Tracking
- **Multiple Payments**: Track unlimited payments per invoice
- **Payment Details**:
  - Payment Number (sequence per invoice)
  - Payment Date
  - Payment Amount
  - Payment Mode (Cash, BDO, GCash)
  - Reference Number (check #, transaction ID, etc.)
  - Notes/Comments
  - Recorded By (user who entered payment)

### 9.4 Invoice Communication
- **Invoice Sent Status**:
  - Sent: Invoice has been sent to client
  - Not Sent: Invoice not yet sent (default: Sent)
- **Send Methods**:
  - Email: Send to client's email
  - Print: Physical copy sent/delivered
  - SMS: Send invoice link (optional)

### 9.5 Invoice Operations
- **View Invoice**: Full invoice and payment history
- **Add Payment**: Record new payment received
- **Edit Payment**: Modify payment details
- **Delete Payment**: Remove erroneous payment (with audit trail)
- **Invoice Report**: View summary of invoice status
- **Generate Statement**: Account statement for client
- **Reminder**: Send payment reminder to client
- **Filter & Search**: By client name, date, status, amount
- **Export Invoices**: Download as CSV

### 9.6 Outstanding Payments
- **Receivables Report**: List of unpaid/partially paid invoices
- **Aging Report**: How long invoices have been outstanding
- **Overdue Alerts**: Highlight invoices past due date
- **Collection Report**: Track payment collections over time

---

## 10. Transaction Logging

### 10.1 Transaction Types Logged
- **Sales Transactions**: 
  - POS sales
  - Invoice creation
  - Payment received
  - Order returns
  
- **Inventory Transactions**:
  - Product purchase
  - Stock adjustment
  - Restock received
  - Stock damage/loss

- **Expense Transactions** (Future):
  - Operating expenses
  - Overhead costs
  - Petty cash

### 10.2 Transaction Details
- **Transaction Information**:
  - Transaction ID (unique)
  - Transaction Type (Sale, Purchase, Adjustment, etc.)
  - Transaction Date & Time
  - Amount
  - Related Records (Invoice #, Product Name, Client Name)
  - Description/Notes
  - Recorded By (user)

### 10.3 Transaction Operations
- **View Transactions**: List all transactions with filters
- **Filter & Search**: By date range, type, amount, user, client
- **Transaction Details**: Drill down into specific transaction
- **Export Transactions**: Download as CSV
- **Transaction Report**: Summary and detailed reports
- **Reconciliation**: Match transactions with bank statements

---

## 11. Audit Logging

### 11.1 Audit Trail Coverage
**All actions logged including**:
- **User Actions**:
  - Login (successful and failed attempts)
  - Logout
  - Password changes
  - Profile updates
  - Session timeout

- **Data Modifications**:
  - Create: New record added (what was added)
  - Update: Existing record modified (old value → new value)
  - Delete: Record deleted (what was deleted)
  - Bulk operations: Bulk edit/delete actions

- **Access Actions**:
  - View reports
  - Download exports
  - Print documents
  - Access sensitive modules

- **System Actions**:
  - Settings changes
  - Permission modifications
  - Role assignments
  - System maintenance

### 11.2 Audit Log Details
- **Log Entry Contains**:
  - Audit ID (unique)
  - Timestamp (exact date, time, timezone)
  - User ID & Name (who performed action)
  - Action Type (Create, Update, Delete, Login, View, etc.)
  - Module/Entity (which feature affected)
  - Record ID (what record was affected)
  - Old Value (before change)
  - New Value (after change)
  - IP Address (for security)
  - User Agent (browser/device info)
  - Description (human-readable summary)
  - Status (Success/Failed)

### 11.3 Audit Operations
- **View Audit Logs**: Complete chronological log
- **Filter Audit Logs**:
  - By user
  - By action type
  - By date range
  - By module
  - By record ID
  - By status

- **Search Audit Logs**: Search by keywords
- **Audit Reports**: Generate audit reports
- **Export Audit Logs**: Download as CSV
- **Retention**: Archive old logs (configurable retention policy)

---

## 12. Reports & Analytics

### 12.1 Sales Reports
- **Daily Sales Report**:
  - Total sales amount
  - Number of transactions
  - Top products sold
  - Average transaction value
  - Sales by payment mode

- **Period Sales Report** (Weekly, Monthly, Yearly):
  - Sales trends over time
  - Comparison with previous period
  - Sales growth percentage
  - Top performing products
  - Sales by category

- **Client Sales Report**:
  - Sales per client
  - Client transaction history
  - Customer lifetime value
  - Top clients by sales amount
  - Customer retention metrics

- **Product Sales Report**:
  - Best selling products
  - Worst selling products
  - Product performance metrics
  - Category performance
  - Stock turnover rate

### 12.2 Inventory Reports
- **Stock Status Report**:
  - Current inventory levels
  - Low stock items
  - Out of stock items
  - Overstock items
  - Last updated date

- **Inventory Movement Report**:
  - Stock in/out movements
  - Restocking history
  - Adjustment history
  - Damage/loss tracking

- **Inventory Valuation Report**:
  - Total inventory value (at cost price)
  - Total inventory value (at selling price)
  - Valuation by category
  - Potential profit (selling value - cost value)

- **Restock Analysis**:
  - Restock orders status
  - Pending deliveries
  - Delivery delays
  - Supplier performance

### 12.3 Financial Reports
- **Revenue Report**:
  - Total revenue
  - Revenue by payment mode
  - Revenue by client
  - Revenue trends

- **Receivables Report**:
  - Outstanding invoices
  - Partially paid invoices
  - Aging analysis (30, 60, 90+ days)
  - Collection forecast

- **Transaction Report**:
  - All transactions summary
  - By type (Sales, Expenses, Adjustments)
  - Period totals

### 12.4 Report Features
- **Charts & Visualizations**:
  - Bar charts (sales, products, categories)
  - Line charts (trends over time)
  - Pie charts (sales breakdown, top clients)
  - Area charts (cumulative metrics)
  - Combo charts (multiple metrics)

- **Statistics Cards**:
  - Key metrics in card format
  - Comparison with previous period
  - Trend indicators (up/down)
  - Color-coded status

- **Data Tables**:
  - Sortable columns
  - Paginated results
  - Searchable/filterable
  - Detailed drill-down capability

- **Report Generation**:
  - Date range selection
  - Filters (by category, client, product, etc.)
  - Report scheduling (generate automatically)
  - Email reports to users

- **Export Options**:
  - CSV format (for Excel)
  - PDF format (for printing/sharing)
  - Excel format (with multiple sheets)
  - Print preview

---

## 13. Dashboard

### 13.1 Dashboard Widgets
- **Key Metrics Cards**:
  - Total Sales (today, this week, this month)
  - Total Revenue (today, this week, this month)
  - Outstanding Receivables (total amount due)
  - Low Stock Items (count)
  - Pending Restocks
  - Total Transactions
  - Average Transaction Value

- **Charts**:
  - Sales Trend (line chart - last 30 days)
  - Top Products (bar chart - best sellers)
  - Sales by Payment Mode (pie chart)
  - Sales by Category (donut chart)
  - Revenue Forecast (line chart - based on trends)
  - Inventory Status (horizontal bar - stock levels)

- **Tables**:
  - Recent Transactions
  - Top Clients (by sales)
  - Recently Added Products
  - Pending Restock Orders
  - Overdue Invoices

### 13.2 Dashboard Customization
- **Widget Selection**: Users can choose which widgets to display
- **Widget Arrangement**: Drag-and-drop to arrange widgets
- **Filters**: Apply global filters (date range, category, etc.)
- **Refresh Rate**: Auto-refresh dashboard data
- **Full Screen**: Expand any chart to full screen
- **Save Layout**: Save custom dashboard layout

### 13.3 Dashboard Access
- **Role-Based Dashboards**: Different dashboards for different roles
  - Admin: Full system overview
  - Manager: Sales and inventory focused
  - Sales Staff: Sales focused
  - Inventory Staff: Inventory focused
- **Personalization**: Each user can customize their dashboard

---

## 14. Notifications Module

### 14.1 Notification Types

#### System Notifications
- **Low Stock Alert**: When product stock falls below alert threshold
- **Restock Received**: When expected restock is delivered
- **Pending Restock**: Reminder of pending restock orders
- **Overdue Invoices**: Reminder of unpaid invoices
- **Invoice Payment Received**: Confirmation of payment received

#### User Notifications
- **Login/Logout**: Session start and end notifications
- **Account Changes**: Password change, profile update notifications
- **Permission Changes**: Notification when user permissions are modified
- **Role Changes**: Notification when user role is assigned/changed

#### Business Notifications
- **Daily Summary**: End-of-day sales summary
- **Weekly Report**: Weekly performance summary
- **Milestone Alerts**: When sales reach certain threshold
- **System Alerts**: System maintenance, backups, errors

### 14.2 Notification Delivery Methods
- **In-App Notifications**: Badge and notification center
- **Email Notifications**: Email alerts (configurable per user)
- **Push Notifications**: Browser push notifications (if applicable)

### 14.3 Notification Features
- **Notification Center**:
  - View all notifications in one place
  - Mark as read/unread
  - Delete notifications
  - Filter notifications by type
  - Search notifications

- **Notification Preferences**:
  - Configure which notifications to receive
  - Set frequency (immediate, daily digest, etc.)
  - Notification delivery method preferences
  - Quiet hours (no notifications during specified time)

- **Notification History**:
  - View notification archive
  - Track notification timeline
  - Export notification logs

---

## 15. System Settings & Administration

### 15.1 General Settings
- **Business Information**:
  - Business name
  - Business address
  - Contact information
  - Logo and branding
  - Tax ID/Registration numbers

- **System Settings**:
  - Date format (MM/DD/YY, DD/MM/YY, etc.)
  - Time format (12-hour, 24-hour)
  - Currency symbol and format
  - Timezone
  - Language (if multi-language support)

### 15.2 Invoice Settings
- **Invoice Number Format**:
  - Prefix/Suffix
  - Number sequence
  - Reset frequency
  - Starting number

- **Invoice Template**:
  - Edit layout and format
  - Add/remove fields
  - Customize styling
  - Footer message
  - Payment terms

### 15.3 Notification Settings
- **Email Configuration**:
  - SMTP server settings
  - Sender email address
  - Default notification recipients
  - Notification templates

- **Notification Rules**:
  - Configure which events trigger notifications
  - Set notification frequency
  - Configure recipients per notification type

### 15.4 User Management (Admin)
- **User List**: View all users with filters
- **Add User**: Create new user account
- **Edit User**: Modify user information
- **Delete User**: Deactivate/delete user
- **Reset Password**: Admin password reset for users
- **User Activity**: View user login history and activity

### 15.5 Backup & Security
- **Backup Management**:
  - Automatic daily backups
  - Manual backup creation
  - Backup download
  - Backup restoration (Admin only)

- **Security Settings**:
  - Password policy (minimum length, complexity)
  - Session timeout
  - IP whitelist (optional)
  - Two-factor authentication (future enhancement)

---

## Summary Statistics

- **Total Features**: 15+ major feature categories
- **Total Sub-features**: 100+ individual features
- **User Roles**: 4+ predefined roles
- **Modules**: 11 core operational modules
- **Report Types**: 10+ different report formats
- **Notification Types**: 15+ events generating notifications

---

**Document Version**: 1.0 | **Last Updated**: May 2026
