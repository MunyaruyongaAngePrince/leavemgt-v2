# 🚀 Quick Start Guide

## Installation Steps (5 minutes)

### Step 1: Database Setup
```bash
# Login to MySQL
mysql -u root -p

# Create database and user
CREATE DATABASE leavemgt_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'leavemgt_user'@'localhost' IDENTIFIED BY 'password123';
GRANT ALL PRIVILEGES ON leavemgt_db.* TO 'leavemgt_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 2: Import Schema
```bash
mysql -u leavemgt_user -p leavemgt_db < database.sql
# Enter password: password123
```

### Step 3: Configure Database Connection
Edit `config/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'leavemgt_user');
define('DB_PASS', 'password123');
define('DB_NAME', 'leavemgt_db');
```

### Step 4: Create Admin Account
```bash
# In MySQL
USE leavemgt_db;

# Insert default roles (if not already present)
INSERT INTO roles (id, role_name, description) VALUES
(1, 'Admin', 'System Administrator'),
(2, 'Employee', 'Regular Employee'),
(3, 'Manager', 'Department Manager');

# Create admin user
INSERT INTO users (username, email, password_hash, first_name, last_name, role_id, status) 
VALUES ('admin', 'admin@company.com', '$2y$12$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lm', 'Admin', 'User', 1, 'active');
# Password for this hash: password
```

### Step 5: Set Permissions
```bash
chmod 755 uploads/
chmod 755 logs/
chmod 644 config/config.php
```

### Step 6: Access Application
```
http://localhost/leavemgt/login.php
Username: admin
Password: password
```

## 📱 After Login

### For Admin Users
1. **Create Departments** (optional)
2. **Configure Leave Types** (Annual, Sick, Personal, etc.)
3. **Add Employees**
4. **Approve Leave Requests**
5. **View Reports**

### For Employee Users
1. **Check Leave Balance** (Dashboard)
2. **Submit Leave Request** 
3. **Track Request Status**
4. **View Leave History**

## 🔑 Default Credentials
- **Username**: admin
- **Password**: password
- **Email**: admin@company.com

**⚠️ Important: Change password immediately after first login!**

## 📂 Project Structure
```
leavemgt/
├── config/              # Configuration files
├── includes/            # Core classes & functions
├── admin/               # Admin pages
├── assets/
│   ├── css/            # Stylesheets
│   └── js/             # JavaScript files
├── bootstrap.php        # Application bootstrap
├── login.php           # Login page
├── dashboard.php       # Employee dashboard
├── request-leave.php   # Leave request form
├── my-requests.php     # View requests
└── database.sql        # Database schema
```

## ✅ Features Ready to Use

### Employee Features
- ✅ Submit leave requests
- ✅ View leave balance
- ✅ Track request status
- ✅ View leave history
- ✅ Profile management

### Admin Features
- ✅ Employee management
- ✅ Leave type configuration
- ✅ Request approval workflow
- ✅ Leave reports & analytics
- ✅ Department management
- ✅ Audit logging

## 🎨 Customization Tips

### Change Color Scheme
Edit `assets/css/styles.css`:
```css
--primary-color: #1E88E5;      /* Main color */
--success-color: #4CAF50;      /* Success color */
--danger-color: #F44336;       /* Error color */
```

### Adjust Settings
Edit `config/config.php`:
```php
define('DEFAULT_ANNUAL_LEAVE_DAYS', 20);        /* Annual leave days */
define('SESSION_TIMEOUT', 1800);                /* 30 minutes */
define('WEEKEND_DAYS', [0, 6]);                 /* Sunday & Saturday */
```

### Create New Leave Type
1. Login as Admin
2. Go to Admin → Leave Types
3. Fill the form and submit
4. System automatically assigns to all employees

## 🔒 Security Checklist

- [ ] Change admin password
- [ ] Update database credentials
- [ ] Set strong SESSION_TIMEOUT
- [ ] Verify HTTPS is enabled
- [ ] Regular database backups
- [ ] Update PHP to latest version
- [ ] Disable error display in production

## 📞 Common Issues & Solutions

### "Access Denied" Error
**Solution**: Check database credentials in `config/config.php`

### Can't Submit Leave Request
**Solution**: Verify leave types are configured and active

### CSS Not Loading
**Solution**: Check APP_URL in `config/config.php` matches your domain

### Pagination Not Working
**Solution**: Adjust ITEMS_PER_PAGE in `config/config.php`

## 📚 Next Steps

1. **Add departments** via Admin → Manage Employees
2. **Import employees** (create users manually or batch)
3. **Configure leave policies** 
4. **Test workflow**: Submit → Approve → Check Balance
5. **Generate reports** to monitor usage

## 📖 Full Documentation
See `README.md` for comprehensive documentation

---

**Ready to Go!** 🎉 Your Leave Management System is now operational.



-- Insert 5 users (1 Admin + 4 Employees)
INSERT INTO users (username, email, password_hash, first_name, last_name, role_id, employee_id, phone, status) VALUES

-- Admin User (Password: Admin@123)
('admin', 'admin@company.com', '$2y$12$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lm', 'Admin', 'User', 1, 'ADM001', '555-0001', 'active'),

-- Employee Users (Password: Employee@123 for all)
('john.doe', 'john.doe@company.com', '$2y$12$gSvqqUNVlXP2tfVFaWK1Be1DlH.PKZbv5H8KKzB1KQrVzPiKd.8Xa', 'John', 'Doe', 2, 'EMP001', '555-0002', 'active'),

('jane.smith', 'jane.smith@company.com', '$2y$12$gSvqqUNVlXP2tfVFaWK1Be1DlH.PKZbv5H8KKzB1KQrVzPiKd.8Xa', 'Jane', 'Smith', 2, 'EMP002', '555-0003', 'active'),

('michael.johnson', 'michael.johnson@company.com', '$2y$12$gSvqqUNVlXP2tfVFaWK1Be1DlH.PKZbv5H8KKzB1KQrVzPiKd.8Xa', 'Michael', 'Johnson', 2, 'EMP003', '555-0004', 'active'),

('sarah.williams', 'sarah.williams@company.com', '$2y$12$gSvqqUNVlXP2tfVFaWK1Be1DlH.PKZbv5H8KKzB1KQrVzPiKd.8Xa', 'Sarah', 'Williams', 2, 'EMP004', '555-0005', 'active');