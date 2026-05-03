# Claude Sonnet Build Prompt - Dream Blanks POS System

## COMPREHENSIVE BUILD INSTRUCTIONS FOR CLAUDE SONNET 4.6

You are tasked with **completely building from scratch** the Dream Blanks POS System based on comprehensive documentation. Follow ALL specifications precisely.

---

## 📋 REFERENCE DOCUMENTATION

You have access to these complete specification files:
1. **plan.md** - Original requirements
2. **project-overview.md** - Business context and objectives
3. **project-full-features-list.md** - 100+ detailed features
4. **project-architecture.md** - Technical architecture and patterns
5. **project-database-schema.md** - Complete database design
6. **project-ui-guidelines.md** - Design system and components
7. **project-full-detailed-plan.md** - Implementation roadmap
8. **project-to-do.md** - Task checklist
9. **project-api-endpoints.md** - API specifications
10. **project-installation-setup.md** - Setup and deployment guide

**INSTRUCTION**: Reference these files constantly. Every file, folder, table, field, component, and feature must align with these specifications.

---

## 🎯 PRIMARY OBJECTIVE

Build a **production-ready PHP vanilla + MySQL POS system** that is:
- ✅ Fully functional end-to-end
- ✅ Follows all architecture patterns
- ✅ Implements all core features
- ✅ Uses professional design system
- ✅ Includes proper error handling
- ✅ Ready for Hostinger deployment
- ✅ Well-documented and maintainable

---

## 🏗️ BUILD STRUCTURE (PRIORITY ORDER)

### PHASE 1: Foundation (Critical - Build First)
1. **Folder Structure** - Create complete directory hierarchy per project-architecture.md Section 3
2. **Core Framework** - Build Router, Request, Response, Database classes
3. **Configuration** - Create .env.example, config files, constants
4. **.htaccess** - URL rewriting rules
5. **Error Handling** - Custom exception classes

### PHASE 2: Database & Models
1. **Database Schema** - Create complete schema from project-database-schema.md
2. **Model Classes** - Create all base and specific model classes
3. **Repositories** - Create data access layer for each model
4. **Migrations** - Create database setup file (schema.sql)
5. **Seeders** - Create initial data (roles, permissions, settings)

### PHASE 3: Authentication & Authorization
1. **User Model & Migration**
2. **Role & Permission Models**
3. **AuthController** - Login, logout, forgot password, OTP
4. **AuthService** - Authentication business logic
5. **AuthMiddleware** - Session and permission checking
6. **Login Views** - HTML forms with styling
7. **Password Recovery** - Email-based OTP system

### PHASE 4: Core Management Modules
1. **User Management** - CRUD operations, profiles
2. **Client Management** - Clients with addresses and contacts
3. **Product Management** - Products with variants (category, color, size)
4. **Inventory Management** - Stock tracking, restock orders

### PHASE 5: POS & Sales
1. **POS Interface** - Product grid, shopping cart
2. **Checkout System** - Cart management, discounts, taxes
3. **Invoice System** - Invoice generation and templates
4. **Payment Tracking** - Multiple payments per invoice

### PHASE 6: Logging & Audit
1. **Transaction Logging** - Record all business transactions
2. **Audit Logging** - Log all user actions
3. **Stock Movements** - Track inventory changes

### PHASE 7: Reporting & Analytics
1. **Report Generation** - Sales, inventory, financial reports
2. **Dashboard** - Metrics, charts, widgets
3. **Data Export** - CSV/PDF export functionality

### PHASE 8: Notifications & UI Polish
1. **Notification System** - In-app and email notifications
2. **Frontend Components** - Reusable components library
3. **Styling** - CSS framework with responsive design
4. **Page Layouts** - All views with proper styling

### PHASE 9: Settings & System
1. **Settings Management** - Business settings, email config
2. **Invoice Template Editor** - Customizable invoice format
3. **API Endpoints** - All 50+ endpoints from project-api-endpoints.md

---

## 🗂️ FOLDER STRUCTURE TO CREATE

Create this exact structure per project-architecture.md Section 3:

```
dream_blanks_pos_system/
├── public/
│   ├── index.php
│   ├── .htaccess
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   ├── images/
│   │   └── fonts/
│   └── uploads/
│       ├── products/
│       ├── clients/
│       └── invoices/
├── src/
│   ├── Core/
│   ├── Models/
│   ├── Controllers/
│   ├── Services/
│   ├── Repositories/
│   ├── Middleware/
│   ├── Helpers/
│   ├── Traits/
│   ├── Exceptions/
│   ├── Validations/
│   └── Views/
├── config/
├── database/
│   ├── migrations/
│   └── seeds/
├── logs/
├── .env.example
├── .htaccess
└── README.md
```

---

## 💾 DATABASE REQUIREMENTS

**CRITICAL**: Implement complete database schema from project-database-schema.md:

- ✅ 20+ tables with proper relationships
- ✅ Foreign keys and constraints
- ✅ Indexes on critical fields
- ✅ Appropriate data types
- ✅ Soft delete support (deleted_at)
- ✅ Timestamps (created_at, updated_at)
- ✅ UTF-8 character set

Create **database/schema.sql** with all DDL statements ready to run.

---

## 🎨 UI/UX REQUIREMENTS

Follow project-ui-guidelines.md precisely:

- ✅ **Color Scheme**: Gray and white theme (#FFFFFF, #F5F5F5, #2D2D2D text)
- ✅ **Typography**: Segoe UI/Arial, proper font hierarchy
- ✅ **Components**: Header, Sidebar, Cards, Buttons, Forms, Tables
- ✅ **Responsive**: Desktop (1200px+), Tablet (768-1199px), Mobile (<768px)
- ✅ **Professional**: Modern, minimal, business-appropriate
- ✅ **Accessibility**: WCAG compliance, keyboard navigation

Create **public/assets/css/style.css** with complete styling.

---

## 🔌 API ENDPOINTS

Implement all endpoints from project-api-endpoints.md Section 2-11:

- ✅ Authentication (login, logout, forgot password, OTP, reset password)
- ✅ User Management (CRUD, search, filter)
- ✅ Roles & Permissions
- ✅ Products (CRUD, bulk import)
- ✅ Inventory (stock tracking, restocking)
- ✅ POS (product listing, checkout)
- ✅ Invoices (create, payments, print, email)
- ✅ Reports (sales, inventory, financial)
- ✅ Dashboard (metrics, charts)
- ✅ Notifications
- ✅ Audit Logs
- ✅ Settings

All endpoints return proper JSON responses with success/error handling.

---

## 🔐 SECURITY REQUIREMENTS

- ✅ Session-based authentication
- ✅ Password hashing with bcrypt (cost: 12)
- ✅ CSRF token protection
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS prevention (input validation, output escaping)
- ✅ Role-based access control (RBAC)
- ✅ Permission middleware on protected routes
- ✅ Secure file uploads (validation, isolated storage)

---

## 🎯 CORE FEATURES TO IMPLEMENT

### Authentication & Users
- [x] User login (email/username + password)
- [x] User logout
- [x] Forgot password with OTP
- [x] Password reset
- [x] User CRUD operations
- [x] User profiles

### Roles & Permissions
- [x] Create/edit/delete roles
- [x] Assign permissions to roles
- [x] Assign roles to users
- [x] Permission checking middleware

### Client Management
- [x] Client CRUD operations
- [x] Multiple addresses per client (up to 3)
- [x] Multiple contacts per client (up to 5)
- [x] Client search and filter

### Product Management
- [x] Product CRUD operations
- [x] Product categories, colors, sizes
- [x] Product images
- [x] Product search, filter, sort
- [x] Bulk product import

### Inventory Management
- [x] Inventory tracking
- [x] Low stock alerts
- [x] Stock movements history
- [x] Restock orders management

### POS Module
- [x] Product grid/display
- [x] Shopping cart
- [x] Search and filter
- [x] Add/remove/update items
- [x] Discount/tax/fee calculation
- [x] Checkout process
- [x] Payment recording

### Invoice System
- [x] Invoice auto-generation
- [x] Invoice number formatting
- [x] Invoice printing
- [x] Invoice email sending
- [x] Invoice template customization
- [x] Multiple payments per invoice
- [x] Payment status tracking

### Reporting & Dashboard
- [x] Sales reports
- [x] Inventory reports
- [x] Financial reports
- [x] Dashboard with metrics and charts
- [x] CSV/PDF export

### Logging & Audit
- [x] Transaction logging
- [x] Audit logging (all user actions)
- [x] Stock movement tracking

### Notifications
- [x] Low stock notifications
- [x] Payment received notifications
- [x] Overdue invoice alerts
- [x] Notification center

---

## 📝 CODE QUALITY STANDARDS

- ✅ Follow OOP principles throughout
- ✅ Use proper namespacing
- ✅ PSR-4 autoloading
- ✅ Proper error handling and logging
- ✅ Comments on complex logic
- ✅ Consistent naming conventions
- ✅ Modular and reusable components
- ✅ No hardcoded values (use config/constants)
- ✅ Validation on all inputs
- ✅ Prepared statements for all SQL

---

## 🚀 DEPLOYMENT READINESS

The built system must be ready for production deployment:

- ✅ .env.example configured
- ✅ Database migrations ready
- ✅ Error logging configured
- ✅ Session handling secure
- ✅ File permissions documented
- ✅ Backup procedures included
- ✅ README.md with setup instructions
- ✅ All dependencies documented

---

## 📊 PROGRESS TRACKING

Create progress summaries as you build:

**After Phase 1**: 
- [x] Folder structure created
- [x] Core framework operational
- [x] Basic routing working

**After Phase 2**:
- [x] All models created
- [x] Database schema ready
- [x] Repositories functional

**After Phase 3**:
- [x] Authentication system complete
- [x] Login/logout working
- [x] Password recovery functional

**And so on for each phase...**

---

## ⚠️ CRITICAL: TOKEN LIMIT MANAGEMENT

**IMPORTANT INSTRUCTION**: When you reach approximately **95% token usage**:

1. **STOP all implementations**
2. **IMMEDIATELY create 'remaining-implementations.md'** with:
   - Summary of what was accomplished
   - Detailed list of remaining tasks
   - Code snippets needed for remaining work
   - Database migrations not yet created
   - Views/templates not yet built
   - API endpoints not yet implemented
   - Features not yet complete
   - Next steps with exact instructions

3. **Format of remaining-implementations.md**:
   ```markdown
   # Remaining Implementations for Dream Blanks POS System

   ## Summary of Completed Work
   [Detailed summary of what was built]

   ## Remaining Tasks by Priority
   [Organized list of remaining tasks]

   ## Database Migrations Needed
   [SQL code for incomplete migrations]

   ## Views/Templates to Build
   [List with file paths and descriptions]

   ## API Endpoints to Implement
   [List of endpoints with specifications]

   ## Features to Complete
   [Detailed feature descriptions]

   ## Known Issues & Notes
   [Any issues discovered]

   ## Next Steps
   [Exact instructions for continuing]
   ```

4. **In the summary at 95% token usage**:
   - Report exact line count of code created
   - List all files created
   - List all database tables created
   - List all controllers/models/services created
   - List all views created
   - Percentage of system completion (roughly)
   - Critical next steps

---

## 📋 DELIVERABLES

By the end of this build session, you should have:

1. ✅ Complete folder structure
2. ✅ All model classes
3. ✅ All controller classes
4. ✅ Authentication system
5. ✅ Database schema (SQL file)
6. ✅ Core business logic classes
7. ✅ CSS framework and styling
8. ✅ Key view templates
9. ✅ API routing system
10. ✅ Comprehensive remaining-implementations.md

---

## 🎓 IMPORTANT NOTES

- **Follow specifications EXACTLY** - Every detail in the MD files matters
- **Use best practices** - OOP, security, performance
- **Test as you go** - Ensure each component works before moving on
- **Comment your code** - Future developers will thank you
- **Use consistent naming** - Per the documentation
- **Validate inputs** - Security first
- **Handle errors gracefully** - Proper error messages
- **Document as you build** - Update remaining-implementations.md
- **Create realistic data** - Make seeder data realistic
- **Think about users** - Make the UI intuitive

---

## 🚦 START NOW

Begin with PHASE 1 immediately:

1. Create folder structure
2. Build core framework (Router, Request, Response, Database)
3. Create base classes (Model, Controller, Service)
4. Set up error handling
5. Create configuration files

**Then proceed through phases in order, ensuring each phase is complete before starting the next.**

When approaching token limit (~95%), create the remaining-implementations.md file and summarize progress.

---

## 💡 REFERENCE THESE CONSTANTLY

- **project-architecture.md** - For system design
- **project-database-schema.md** - For database structure
- **project-ui-guidelines.md** - For design standards
- **project-full-features-list.md** - For feature specifications
- **project-api-endpoints.md** - For API structure

---

**BUILD TIME ESTIMATE**: 45,000-60,000 tokens for significant progress before needing remaining-implementations.md

**START BUILDING NOW** ➜

Good luck! Build this system with excellence. 🚀
