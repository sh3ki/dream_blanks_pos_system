# Dream Blanks POS System - Installation & Setup Guide

## 1. System Requirements

### 1.1 Development Environment
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher / MariaDB 10.3+
- **Web Server**: Apache 2.4+
- **RAM**: 4GB minimum
- **Storage**: 5GB minimum

### 1.2 Production Environment (Hostinger)
- **Hosting**: Hostinger PHP Shared Hosting
- **PHP Version**: 7.4 or higher (configurable)
- **MySQL Database**: Included with hosting plan
- **SSL Certificate**: Free Let's Encrypt included
- **Disk Space**: 10GB minimum recommended
- **Monthly Bandwidth**: 100GB minimum

### 1.3 Development Tools
- **Git**: For version control
- **Code Editor**: VS Code, PHPStorm, or similar
- **Database Client**: PHPMyAdmin or MySQL Workbench
- **Postman**: For API testing (optional)
- **Terminal/Command Line**: For running commands

---

## 2. Local Development Setup

### 2.1 Prerequisites Installation

#### On Windows
1. **Download and Install Laragon** (Recommended for PHP development)
   - Download from: https://laragon.org/
   - Run installer and follow setup wizard
   - Choose full installation with PHP 7.4+, MySQL, Node.js

2. **Alternative: XAMPP/WAMP**
   - Download from: https://www.apachefriends.org/
   - Install following default settings
   - Ensure Apache and MySQL modules are enabled

3. **Install Git**
   - Download from: https://git-scm.com/
   - Run installer, use default settings
   - Verify installation: `git --version`

4. **Install Node.js** (Optional, for build tools)
   - Download from: https://nodejs.org/
   - Choose LTS version
   - Verify: `node --version`

#### On macOS
```bash
# Install Homebrew if not already installed
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Install PHP 7.4+
brew install php

# Install MySQL
brew install mysql
brew services start mysql

# Install Git
brew install git
```

#### On Linux (Ubuntu/Debian)
```bash
# Update package manager
sudo apt update

# Install PHP and required extensions
sudo apt install php php-mysql php-curl php-gd php-json

# Install MySQL
sudo apt install mysql-server

# Install Git
sudo apt install git
```

### 2.2 Project Setup

#### Step 1: Clone Repository
```bash
# Create a directory for the project
mkdir ~/www
cd ~/www

# Clone the repository (replace with actual repo URL)
git clone https://github.com/yourusername/dream-blanks-pos.git dream_blanks_pos_system
cd dream_blanks_pos_system
```

#### Step 2: Create Environment Configuration
```bash
# Copy example environment file
cp .env.example .env

# Edit .env with your local configuration
# Open .env in your editor and update:
APP_NAME="Dream Blanks POS"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_HOST=localhost
DB_PORT=3306
DB_NAME=dream_blanks_pos
DB_USER=root
DB_PASSWORD=

SESSION_DRIVER=file
MAIL_DRIVER=smtp
```

#### Step 3: Create Database
```bash
# Using MySQL command line
mysql -u root -p

# In MySQL console:
CREATE DATABASE dream_blanks_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

Or using PHPMyAdmin:
1. Open PHPMyAdmin (usually at `http://localhost/phpmyadmin`)
2. Click "Databases" tab
3. Enter database name "dream_blanks_pos"
4. Select UTF-8 collation
5. Click "Create"

#### Step 4: Import Database Schema
```bash
# Import the schema file
mysql -u root -p dream_blanks_pos < database/schema.sql

# Or using PHPMyAdmin:
# 1. Select the dream_blanks_pos database
# 2. Go to "Import" tab
# 3. Select database/schema.sql file
# 4. Click "Import"
```

#### Step 5: Set Up Project Structure
```bash
# The folder structure should already be created
# But verify these directories exist and are writable:

# Create directories if they don't exist
mkdir -p public/uploads/products
mkdir -p public/uploads/clients
mkdir -p public/uploads/invoices
mkdir -p logs

# Set permissions (Linux/macOS)
chmod -R 755 public/uploads
chmod -R 755 logs
```

#### Step 6: Install Dependencies (Optional)
```bash
# If using Composer
composer install

# If not using Composer, skip this step
```

#### Step 7: Start Development Server

**Using Laragon**:
1. Open Laragon application
2. Start Apache and MySQL services
3. Project will be accessible at: http://dream_blanks_pos_system.test

**Using XAMPP/WAMP**:
1. Start Apache and MySQL services
2. Place project in `htdocs` folder
3. Access at: http://localhost/dream_blanks_pos_system/public

**Using PHP Built-in Server**:
```bash
cd public
php -S localhost:8000
# Access at: http://localhost:8000
```

### 2.3 Initial Data Setup

#### Create Initial Roles
```sql
INSERT INTO roles (name, description, status) VALUES
('Admin', 'Full system access', 'active'),
('Manager', 'Sales and inventory management', 'active'),
('Sales Staff', 'Point of sale operations', 'active'),
('Inventory Staff', 'Inventory management only', 'active');
```

#### Create Initial Permissions
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
-- Add more permissions as needed
```

#### Create Default Settings
```sql
INSERT INTO settings (setting_key, setting_value, setting_type) VALUES
('business_name', 'Dream Blanks', 'string'),
('currency_symbol', '₱', 'string'),
('date_format', 'MM/DD/YYYY', 'string'),
('invoice_prefix', 'INV-', 'string');
```

#### Create Admin User
```sql
-- Hash password: admin123 using bcrypt (cost: 12)
INSERT INTO users (username, email, first_name, last_name, password_hash, status) VALUES
('admin', 'admin@dreamblanks.com', 'Admin', 'User', '$2y$12$[hashed_password_here]', 'active');

-- Assign admin role to the user
INSERT INTO user_roles (user_id, role_id) VALUES (1, 1);
```

### 2.4 Verification Checklist

- [ ] Database created and accessible
- [ ] Database schema imported successfully
- [ ] All tables created without errors
- [ ] Environment configuration (.env) completed
- [ ] Project files in correct location
- [ ] Web server running without errors
- [ ] Can access application in browser
- [ ] Can login with admin credentials
- [ ] All permissions and roles assigned

---

## 3. Production Deployment on Hostinger

### 3.1 Hostinger Account Setup

1. **Purchase Hosting Plan**
   - Go to https://hostinger.com
   - Choose PHP Hosting plan (Business or Premium recommended)
   - Complete purchase and account setup
   - Verify email address

2. **Access Control Panel**
   - Log in to Hostinger Account
   - Go to "My Hosting" or similar
   - Access cPanel/hPanel (depending on account type)

### 3.2 Database Setup on Hostinger

1. **Create MySQL Database**
   - In cPanel, find "MySQL Databases" or "Database Manager"
   - Click "Create New Database"
   - Name: `dream_blanks_pos_prod`
   - Character set: UTF-8 (utf8mb4)
   - Click "Create"

2. **Create Database User**
   - In "MySQL Users" section
   - Create new user with strong password
   - Add user to database with ALL privileges

3. **Import Database Schema**
   - Download phpMyAdmin or similar tool
   - Access your database via phpMyAdmin
   - Import the schema.sql file
   - Verify all tables are created

### 3.3 Upload Project Files

#### Using FTP
1. **Connect to Server via FTP**
   - FTP Host: Your Hostinger FTP address
   - Username: FTP username from cPanel
   - Password: FTP password
   - Port: 21 (or specified port)

2. **Upload Files**
   - Use FTP client (FileZilla, WinSCP, etc.)
   - Upload all project files to `public_html` directory
   - Upload `.env` configuration file (update with production credentials)
   - Ensure permissions are set correctly (755 for folders, 644 for files)

#### Using Git (Recommended)
1. **Set Up Git on Server**
   - SSH into server
   - Navigate to public_html
   - Clone repository: `git clone [repository_url]`
   - Pull latest code: `git pull origin main`

### 3.4 Configuration for Production

1. **Update .env File**
```
APP_NAME="Dream Blanks POS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_HOST=localhost
DB_PORT=3306
DB_NAME=dream_blanks_pos_prod
DB_USER=db_user_name
DB_PASSWORD=strong_password

SESSION_DRIVER=file
MAIL_DRIVER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@yourdomain.com
```

2. **Set File Permissions**
```bash
# SSH into server
ssh username@hostinger_ip

# Navigate to project
cd public_html/dream_blanks_pos_system

# Set permissions
chmod -R 755 public/uploads
chmod -R 755 logs
chmod 600 .env
```

3. **Configure PHP Settings** (in cPanel)
   - PHP Version: 7.4 or higher
   - Memory Limit: 256MB minimum
   - Max Upload Size: 50MB minimum
   - Execution Time: 300 seconds minimum

### 3.5 SSL Certificate Setup

1. **Install Free SSL Certificate**
   - In cPanel, find "AutoSSL" or "Let's Encrypt"
   - Install certificate for your domain
   - Verify HTTPS works on your domain

2. **Force HTTPS**
   - Edit `.htaccess` in public folder
   - Add redirect rules:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 3.6 Email Configuration

1. **Set Up SMTP**
   - Use Hostinger provided SMTP credentials
   - Or use third-party service (SendGrid, Mailgun, etc.)
   - Update MAIL_* variables in .env

2. **Test Email**
   - Send test email to verify configuration
   - Check if emails are received successfully

### 3.7 Backup Strategy

1. **Automated Backups**
   - In cPanel, enable automatic backups
   - Configure daily/weekly backups
   - Store backups in secure location

2. **Manual Backup**
   - Export database regularly
   - Download project files periodically
   - Store in secure cloud storage

3. **Disaster Recovery Plan**
   - Document restoration procedures
   - Test restore process regularly
   - Maintain off-site backup copies

### 3.8 Monitoring & Maintenance

1. **Monitor Performance**
   - Check server resource usage
   - Monitor database performance
   - Review error logs regularly

2. **Security**
   - Keep PHP updated
   - Keep MySQL updated
   - Monitor for suspicious activities
   - Regular security audits

3. **Updates & Patches**
   - Apply security patches promptly
   - Update third-party libraries
   - Test updates before deploying

---

## 4. Database Backup & Restore

### 4.1 Backup Database

**Command Line**:
```bash
# Backup with mysqldump
mysqldump -u root -p dream_blanks_pos > backup.sql

# Backup specific tables
mysqldump -u root -p dream_blanks_pos users roles permissions > backup_minimal.sql

# Backup with timestamp
mysqldump -u root -p dream_blanks_pos > backup_$(date +%Y%m%d_%H%M%S).sql
```

**PHPMyAdmin**:
1. Select database
2. Click "Export" tab
3. Choose format (SQL recommended)
4. Click "Go"

### 4.2 Restore Database

**Command Line**:
```bash
# Restore from backup
mysql -u root -p dream_blanks_pos < backup.sql

# Create database first, then restore
mysql -u root -p < backup.sql
```

**PHPMyAdmin**:
1. Create new database (if needed)
2. Click "Import" tab
3. Select backup file
4. Click "Import"

---

## 5. Troubleshooting

### Common Issues

| Issue | Solution |
|-------|----------|
| Database connection error | Verify credentials, check if MySQL is running |
| Page not found (404) | Check .htaccess rewrite rules, verify file structure |
| Permission denied errors | Set correct file permissions (755 for folders, 644 for files) |
| Email not sending | Verify SMTP settings, check firewall/port blocking |
| Slow performance | Optimize database queries, enable caching, upgrade hosting |
| SSL certificate errors | Ensure HTTPS is configured, check certificate expiry |
| Session issues | Check session directory permissions, verify session settings |

### Debug Mode

```php
// Enable debug mode in .env
APP_DEBUG=true

// Check error logs
tail -f logs/error.log

// Check audit logs for action tracking
SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 20;
```

---

## 6. Getting Started Checklist

### Pre-Launch
- [ ] All system requirements met
- [ ] Database schema created and verified
- [ ] Initial data (roles, permissions, settings) inserted
- [ ] Admin user created with strong password
- [ ] .env configuration file set up
- [ ] File permissions configured correctly
- [ ] Web server and MySQL running
- [ ] Application accessible in browser
- [ ] Login functionality verified
- [ ] Backup strategy implemented

### Post-Deployment
- [ ] Production database backed up
- [ ] SSL certificate installed and verified
- [ ] Email service configured and tested
- [ ] Error logging enabled
- [ ] Automated backups configured
- [ ] Monitoring set up
- [ ] Admin dashboard accessible
- [ ] All modules tested
- [ ] User training materials prepared
- [ ] Documentation updated

---

## 7. Support & Resources

### Documentation
- See `project-architecture.md` for system design
- See `project-full-features-list.md` for feature details
- See `project-api-endpoints.md` for API reference
- See `project-database-schema.md` for database structure

### Common Commands

```bash
# Check PHP version
php -v

# Check MySQL version
mysql -V

# Test database connection
mysql -h localhost -u root -p

# Check file permissions
ls -la

# Set directory permissions
chmod -R 755 directory_name

# Clear browser cache
# Chrome: Ctrl+Shift+Delete
# Firefox: Ctrl+Shift+Delete
```

### Useful Links
- PHP Documentation: https://www.php.net/docs.php
- MySQL Documentation: https://dev.mysql.com/doc/
- Apache Documentation: https://httpd.apache.org/docs/
- Hostinger Support: https://support.hostinger.com/

---

**Document Version**: 1.0 | **Last Updated**: May 2026
