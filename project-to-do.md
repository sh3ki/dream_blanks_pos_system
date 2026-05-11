# Dream Blanks POS System - Project To-Do & Implementation Roadmap

## Status Legend
- ⬜ Not Started
- 🟡 In Progress
- ✅ Completed
- ⚠️ Blocked
- 📋 Pending Review

---

## PHASE 1: Foundation & Setup (Week 1-2)

### Project Infrastructure
- ⬜ Initialize Git repository
- ⬜ Create project folder structure
- ⬜ Set up .env configuration template
- ⬜ Create .gitignore file
- ⬜ Create README.md with setup instructions
- ⬜ Set up local development environment

### Database Setup
- ⬜ Design complete database schema
- ⬜ Create MySQL database
- ⬜ Run migration scripts to create tables
- ⬜ Create database seeders for initial data
- ⬜ Verify all table relationships
- ⬜ Create backup/restore procedures

### Core Framework
- ⬜ Create Router.php for URL routing
- ⬜ Create Database.php connection class
- ⬜ Create base Model class
- ⬜ Create base Controller class
- ⬜ Create Request.php class
- ⬜ Create Response.php class
- ⬜ Create error handling system
- ⬜ Create exception classes

### Documentation
- ⬜ Create project-overview.md
- ⬜ Create project-architecture.md
- ⬜ Create database-schema documentation
- ⬜ Create API specifications
- ⬜ Create developer setup guide

---

## PHASE 2: Authentication & User Management (Week 2-3)

### Authentication System
- ⬜ Create User model
- ⬜ Create user migration script
- ⬜ Create AuthController
- ⬜ Implement login functionality
- ⬜ Implement logout functionality
- ⬜ Create session management
- ⬜ Implement CSRF token protection
- ⬜ Create login form HTML/CSS

### Password Recovery
- ⬜ Create forgot password form
- ⬜ Implement OTP generation
- ⬜ Set up email service
- ⬜ Implement OTP validation
- ⬜ Create password reset form
- ⬜ Implement password reset functionality
- ⬜ Create OTP email template

### User Management Module
- ⬜ Create UserController
- ⬜ Implement user list page
- ⬜ Implement user creation form
- ⬜ Implement user edit functionality
- ⬜ Implement user delete functionality
- ⬜ Add user search and filter
- ⬜ Add user pagination
- ⬜ Create user profile page
- ⬜ Implement profile editing

### Roles & Permissions
- ⬜ Create Role model
- ⬜ Create Permission model
- ⬜ Create user_roles junction table
- ⬜ Create role_permissions junction table
- ⬜ Create RoleController
- ⬜ Create permission assignment interface
- ⬜ Create PermissionMiddleware
- ⬜ Implement permission checking logic
- ⬜ Create role management interface
- ⬜ Create permission management interface

---

## PHASE 3: Core Business Modules (Week 3-5)

### Client Management
- ⬜ Create Client model
- ⬜ Create ClientAddress model
- ⬜ Create ClientContact model
- ⬜ Create ClientController
- ⬜ Implement client list page
- ⬜ Implement client creation form
- ⬜ Implement client edit functionality
- ⬜ Implement client delete functionality
- ⬜ Add client search and filter
- ⬜ Implement address management (up to 3)
- ⬜ Implement contact management (up to 5)
- ⬜ Create client detail page
- ⬜ Add client validation

### Product Management
- ⬜ Create Category model
- ⬜ Create Color model
- ⬜ Create Size model
- ⬜ Create Product model
- ⬜ Create ProductImage model
- ⬜ Create ProductController
- ⬜ Implement product list page
- ⬜ Implement product creation form
- ⬜ Implement product image upload
- ⬜ Implement product edit functionality
- ⬜ Implement product delete functionality
- ⬜ Add product search and filter
- ⬜ Add product sorting by price, stock, etc.
- ⬜ Create variant management interface (categories, colors, sizes)
- ⬜ Implement bulk product import (CSV)
- ⬜ Add product validation

### Inventory Management
- ⬜ Create Inventory model
- ⬜ Create StockMovement model
- ⬜ Create InventoryController
- ⬜ Create inventory dashboard
- ⬜ Implement stock tracking system
- ⬜ Implement low stock alert threshold
- ⬜ Create automatic low stock alerts
- ⬜ Implement stock adjustment functionality
- ⬜ Log all stock movements
- ⬜ Create inventory report page
- ⬜ Add inventory search and filter

### Restocking System
- ⬜ Create RestockOrder model
- ⬜ Create RestockItem model
- ⬜ Create RestockController
- ⬜ Implement restock request creation
- ⬜ Implement restock order list page
- ⬜ Implement restock status tracking
- ⬜ Create delivery status updates
- ⬜ Implement automatic inventory update on delivery
- ⬜ Add restock search and filter
- ⬜ Create restock report

### System Settings
- ⬜ Create Settings model
- ⬜ Create SettingsController
- ⬜ Create business settings page
- ⬜ Implement invoice number format configuration
- ⬜ Implement email configuration
- ⬜ Create notification preferences
- ⬜ Implement settings validation

---

## PHASE 4: Sales & Invoicing (Week 5-6)

### Point of Sale (POS)
- ⬜ Create PosController
- ⬜ Design POS interface layout
- ⬜ Implement product grid with search
- ⬜ Implement product filter (category, color, size)
- ⬜ Create shopping cart component
- ⬜ Implement add to cart functionality
- ⬜ Implement remove from cart functionality
- ⬜ Implement quantity adjustment
- ⬜ Implement discount calculation
- ⬜ Implement tax calculation
- ⬜ Implement additional fees
- ⬜ Create payment mode selector
- ⬜ Implement client selection dropdown
- ⬜ Create checkout review page
- ⬜ Implement checkout process
- ⬜ Test complete POS flow

### Invoice System
- ⬜ Create Invoice model
- ⬜ Create InvoiceItem model
- ⬜ Create InvoiceController
- ⬜ Design invoice template (based on provided image)
- ⬜ Implement invoice number generation
- ⬜ Implement invoice auto-generation from POS
- ⬜ Create invoice detail page
- ⬜ Create invoice printing functionality
- ⬜ Create invoice preview page
- ⬜ Implement invoice email sending
- ⬜ Create invoice list page
- ⬜ Add invoice search and filter

### Invoice Customization
- ⬜ Create InvoiceTemplate model
- ⬜ Create invoice template editor page
- ⬜ Implement drag-and-drop layout builder
- ⬜ Implement field selection
- ⬜ Implement styling customization
- ⬜ Create template preview
- ⬜ Implement template saving
- ⬜ Create multiple template support

### Payment Management
- ⬜ Create Payment model
- ⬜ Create PaymentController
- ⬜ Implement payment recording interface
- ⬜ Implement multiple payment per invoice
- ⬜ Create payment tracking page
- ⬜ Implement payment status updates
- ⬜ Create payment history
- ⬜ Implement outstanding receivables list
- ⬜ Create payment aging report
- ⬜ Add payment search and filter

---

## PHASE 5: Logging & Audit (Week 6-7)

### Transaction Logging
- ⬜ Create Transaction model
- ⬜ Create TransactionController
- ⬜ Implement automatic transaction creation from sales
- ⬜ Implement transaction recording from purchases
- ⬜ Create transaction list page
- ⬜ Add transaction search and filter
- ⬜ Implement transaction export to CSV
- ⬜ Create transaction report

### Audit System
- ⬜ Create AuditLog model
- ⬜ Create AuditService class
- ⬜ Create audit logging middleware
- ⬜ Implement user action logging (create, update, delete)
- ⬜ Implement login/logout logging
- ⬜ Implement permission change logging
- ⬜ Create audit log viewer page
- ⬜ Add audit log search and filter
- ⬜ Implement audit log export
- ⬜ Create audit log report

---

## PHASE 6: Reporting & Analytics (Week 7)

### Report Generation
- ⬜ Create ReportController
- ⬜ Implement sales report generator
- ⬜ Implement inventory report generator
- ⬜ Implement financial report generator
- ⬜ Implement client report generator
- ⬜ Implement audit report generator
- ⬜ Create report filtering interface
- ⬜ Implement CSV export
- ⬜ Implement PDF export
- ⬜ Create report templates

### Dashboard
- ⬜ Create DashboardController
- ⬜ Design dashboard layout
- ⬜ Create dashboard metrics cards (sales, revenue, etc.)
- ⬜ Implement line charts for trends
- ⬜ Implement bar charts for comparisons
- ⬜ Implement pie charts for proportions
- ⬜ Create recent transactions table
- ⬜ Add top products widget
- ⬜ Add top clients widget
- ⬜ Create low stock alerts widget
- ⬜ Implement dashboard filters
- ⬜ Create role-specific dashboards
- ⬜ Implement widget customization

---

## PHASE 7: Notifications & UI Polish (Week 7)

### Notification System
- ⬜ Create Notification model
- ⬜ Create NotificationController
- ⬜ Create notification center page
- ⬜ Implement in-app notifications
- ⬜ Set up email notification system
- ⬜ Implement low stock notifications
- ⬜ Implement payment received notifications
- ⬜ Implement pending restock alerts
- ⬜ Implement overdue invoice notifications
- ⬜ Create notification preferences page
- ⬜ Implement notification logging

### Frontend Components
- ⬜ Create header/topbar component
- ⬜ Create collapsible sidebar component
- ⬜ Create toast notification component
- ⬜ Create modal/dialog component
- ⬜ Create table component with pagination
- ⬜ Create form components (input, select, textarea, etc.)
- ⬜ Create button component variants
- ⬜ Create card component
- ⬜ Create badge/status indicators
- ⬜ Create loading spinners
- ⬜ Create form validation feedback

### Styling & Theme
- ⬜ Create CSS framework file
- ⬜ Implement gray/white color theme
- ⬜ Create responsive grid system
- ⬜ Implement desktop styles
- ⬜ Implement tablet styles
- ⬜ Implement mobile styles
- ⬜ Create print stylesheets
- ⬜ Add loading animations
- ⬜ Add transition effects
- ⬜ Implement hover/active states
- ⬜ Add accessibility styles

---

## PHASE 8: Integration & Testing (Week 8)

### Testing & QA
- ⬜ Test authentication flow
- ⬜ Test user management
- ⬜ Test product management
- ⬜ Test inventory operations
- ⬜ Test complete POS flow
- ⬜ Test invoice generation
- ⬜ Test payment recording
- ⬜ Test report generation
- ⬜ Test notification system
- ⬜ Test audit logging
- ⬜ Perform security audit
- ⬜ Performance testing

### Optimization
- ⬜ Optimize database queries
- ⬜ Implement query caching
- ⬜ Minimize CSS and JavaScript
- ⬜ Optimize images
- ⬜ Implement lazy loading
- ⬜ Test page load times
- ⬜ Performance tuning

### Documentation
- ⬜ Update technical documentation
- ⬜ Create user manual
- ⬜ Document API endpoints
- ⬜ Create troubleshooting guide
- ⬜ Document deployment process
- ⬜ Create admin guide

---

## PHASE 9: Deployment & Launch (Week 8)

### Pre-Launch
- ⬜ Set up Hostinger hosting account
- ⬜ Configure PHP and MySQL
- ⬜ Set up SSL certificate
- ⬜ Configure email service
- ⬜ Create production database
- ⬜ Set up automated backups
- ⬜ Create deployment scripts

### Deployment
- ⬜ Push code to production
- ⬜ Run database migrations
- ⬜ Configure environment variables
- ⬜ Set up cron jobs (if needed)
- ⬜ Configure email service
- ⬜ Set up error logging
- ⬜ Full functionality test on production
- ⬜ Create test user accounts

### Post-Launch
- ⬜ Monitor system performance
- ⬜ Fix any production issues
- ⬜ Gather user feedback
- ⬜ Provide user training
- ⬜ Document lessons learned
- ⬜ Plan Phase 2 improvements

---

## Critical Path Tasks (Must Complete First)

1. **Database Setup** - Foundation for all modules
2. **Core Framework** - Required for all features
3. **Authentication** - Gatekeeper for the system
4. **Product Management** - Required for POS
5. **Inventory** - Required for POS
6. **POS Module** - Core business functionality
7. **Invoice System** - Essential for transactions
8. **Audit Logging** - Required for compliance

---

## Parallel Work Streams

### Stream 1: Backend (Developer 1)
- Core framework setup
- Database and models
- Controllers and services
- API endpoints
- Business logic

### Stream 2: Frontend/UI (Developer 2)
- Page layouts
- UI components
- Forms and styling
- Responsive design
- User interactions

### Stream 3: Integration/Testing (QA/Developer 3)
- Integration testing
- Performance testing
- Security review
- User acceptance testing
- Documentation

---

## Weekly Milestones

| Week | Milestone | Status |
|------|-----------|--------|
| Week 1 | Project setup, database design, core framework | ⬜ |
| Week 2 | Authentication system complete, user management | ⬜ |
| Week 3 | Client, Product, Inventory modules | ⬜ |
| Week 4 | Inventory restocking, basic POS | ⬜ |
| Week 5 | POS complete, invoice system | ⬜ |
| Week 6 | Invoice customization, payment management | ⬜ |
| Week 7 | Reporting, dashboard, notifications | ⬜ |
| Week 8 | Testing, optimization, deployment | ⬜ |

---

## Blockers & Dependencies

| Blocker | Impact | Status | Owner |
|---------|--------|--------|-------|
| Email service setup | Forgot password, notifications | ⬜ | DevOps |
| Database access on Hostinger | Production deployment | ⬜ | DevOps |
| SSL certificate | Live launch | ⬜ | DevOps |
| Design approval | UI implementation | ⬜ | Stakeholder |
| Data migration requirements | Data import | ⬜ | Client |

---

## Success Metrics

- [ ] All critical features implemented and tested
- [ ] System accessible on production server
- [ ] 95%+ uptime after launch
- [ ] Page load time < 3 seconds
- [ ] POS transaction completion < 5 seconds
- [ ] All team members trained on system
- [ ] User documentation complete
- [ ] Zero critical bugs at launch

---

## Notes & Observations

- Prioritize POS module as core revenue-generating feature
- Invoice template customization is high-value feature
- Audit logging essential for compliance and debugging
- Consider performance implications of reporting modules
- Plan for data migration if transitioning from existing system

---

**Document Version**: 1.0 | **Last Updated**: May 2026

---

## STOCK PRODUCTS REFACTOR (Completed — June 2026)

### Summary
Inventory ownership moved from sellable `products` to a new `stock_products` entity. Sellable products now assign stock via `product_stock_requirements`. POS checkout deducts from stock products, not product records.

### Completed Tasks
- ✅ Database: Created `stock_products` table (name, description, code, type_id, color_id, size_id, current_qty, low_stock_alert, status)
- ✅ Database: Created `product_stock_requirements` junction table (product_id, stock_product_id, qty_required_per_unit, waste_percent)
- ✅ Migration: `database/migrate_stock_products.sql` — safe 3-phase migration
- ✅ Model: `src/Models/StockProduct.php` — CRUD, computeMaxSellable(), syncInventoryStatus(), getLowStock()
- ✅ Model: `src/Models/ProductStockRequirement.php` — forProduct(), saveForProduct(), productsUsing()
- ✅ Model: `src/Models/StockMovement.php` — added logForStockProduct(), forStockProduct()
- ✅ Model: `src/Models/Product.php` — forPos() rewritten to compute stock from requirements
- ✅ Model: `src/Models/Inventory.php` — getAll(), getLowStock() now join stock_products
- ✅ Service: `src/Services/PosService.php` — checkout deducts from stock_products
- ✅ Service: `src/Services/NotificationService.php` — lowStockAlert() updated to stock_product signature
- ✅ Controller: `src/Controllers/StockProductController.php` — full CRUD + adjust + movements
- ✅ Controller: `src/Controllers/ProductController.php` — getRequirements(), saveRequirements()
- ✅ Controller: `src/Controllers/InventoryController.php` — restock items use stock_product_id
- ✅ Routes: `config/routes.php` — stock-products web + API routes added
- ✅ View: `src/Views/stock-products/index.php` — full CRUD with adjust modal
- ✅ View: `src/Views/products/index.php` — removed stock fields, added requirements UI
- ✅ View: `src/Views/inventory/index.php` — rewritten for stock product columns
- ✅ Nav: `src/Views/layouts/main.php` — "Stock Products" link added
- ✅ Constants: `config/constants.php` — MODULE_STOCK_PRODUCTS added

### Outstanding / Follow-up
- ⬜ Run `database/migrate_stock_products.sql` on production DB
- ⬜ Backfill existing products: create stock_product records and assign via product_stock_requirements
- ⬜ Verify `icon('package')` is defined in IconHelper.php (fallback to 'inventory' icon if not)
- ⬜ Add RBAC permission rows for MODULE_STOCK_PRODUCTS in DB seeder
- ⬜ Test POS checkout end-to-end after migration
