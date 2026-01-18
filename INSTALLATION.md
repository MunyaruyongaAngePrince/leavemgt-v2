# Installation Verification Checklist

## ✅ Pre-Installation Verification

### Environment Check
- [ ] PHP 7.4+ installed (`php -v`)
- [ ] MySQL/MariaDB 5.7+ running (`mysql --version`)
- [ ] Apache with mod_rewrite enabled
- [ ] PHP extensions: mysqli, json, session (standard)
- [ ] Write permissions on `/logs` and `/uploads` directories

## ✅ Database Installation

### Database & User Creation
```sql
-- Copy and run in MySQL client
CREATE DATABASE leavemgt_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'leavemgt_user'@'localhost' IDENTIFIED BY 'secure_password123';
GRANT ALL PRIVILEGES ON leavemgt_db.* TO 'leavemgt_user'@'localhost';
FLUSH PRIVILEGES;
```

### Schema Import
```bash
cd c:\xampp\htdocs\leavemgt
mysql -u leavemgt_user -p leavemgt_db < database.sql
# Enter password when prompted
```

### Verify Database
```sql
USE leavemgt_db;
SHOW TABLES;  -- Should display 9 tables
SELECT * FROM roles;  -- Should show 3 roles
```

## ✅ Configuration

### Update config/config.php
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'leavemgt_user');
define('DB_PASS', 'secure_password123');
define('DB_NAME', 'leavemgt_db');
define('APP_URL', 'http://localhost/leavemgt');
```

## ✅ File Permissions

### Set Proper Permissions
```bash
# Windows (skip if already correct)
cd c:\xampp\htdocs\leavemgt
mkdir uploads logs

# Linux/Mac
chmod 755 uploads
chmod 755 logs
chmod 644 config/config.php
chmod 644 database.sql
```

## ✅ Create Admin Account

### Method 1: MySQL Direct
```sql
USE leavemgt_db;

INSERT INTO users (
    username, email, password_hash, 
    first_name, last_name, role_id, status
) VALUES (
    'admin',
    'admin@company.com',
    '$2y$12$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lm',
    'Admin',
    'User',
    1,
    'active'
);
-- Password: password
```

### Method 2: Generate New Password Hash
```php
<?php
echo password_hash('your_password_here', PASSWORD_BCRYPT);
// Copy the output and use in INSERT statement
```

## ✅ Verify Installation

### File Structure Check
```
c:\xampp\htdocs\leavemgt\
├── ✅ bootstrap.php
├── ✅ config/config.php
├── ✅ includes/Database.php
├── ✅ includes/Auth.php
├── ✅ includes/helpers.php
├── ✅ admin/dashboard.php
├── ✅ admin/employees.php
├── ✅ admin/leave-types.php
├── ✅ admin/approve-requests.php
├── ✅ admin/reports.php
├── ✅ assets/css/styles.css
├── ✅ assets/css/components.css
├── ✅ login.php
├── ✅ dashboard.php
├── ✅ request-leave.php
├── ✅ my-requests.php
├── ✅ profile.php
├── ✅ logout.php
├── ✅ index.php
├── ✅ database.sql
├── ✅ README.md
└── ✅ QUICKSTART.md
```

### Database Tables Check
```sql
USE leavemgt_db;
DESCRIBE users;           -- Should have 15 columns
DESCRIBE roles;           -- Should have 4 columns
DESCRIBE leave_types;     -- Should have 9 columns
DESCRIBE leave_requests;  -- Should have 14 columns
DESCRIBE leave_balances;  -- Should have 7 columns
SELECT COUNT(*) FROM roles;  -- Should return 3
```

### Web Access Test
1. Open browser: `http://localhost/leavemgt/`
2. Should redirect to login page
3. Try login with:
   - Username: `admin`
   - Password: `password`
4. Should see admin dashboard

## ✅ Post-Installation Tasks

### Security Setup
- [ ] Change admin password immediately
- [ ] Review `config/config.php` settings
- [ ] Disable debug mode for production
- [ ] Set appropriate session timeout
- [ ] Configure email settings (if needed)
- [ ] Set up regular backups

### Initial Configuration
- [ ] Create departments
- [ ] Add leave types (Annual, Sick, etc.)
- [ ] Import employees
- [ ] Test leave request workflow
- [ ] Verify approval system
- [ ] Check report generation

### Data Verification
```sql
-- Verify admin user created
SELECT * FROM users WHERE username = 'admin';

-- Verify roles
SELECT * FROM roles;

-- Verify leave types exist
SELECT * FROM leave_types;

-- Check no errors in recent requests
SELECT * FROM leave_requests LIMIT 5;
```

## ✅ Testing Workflow

### Test Path: Admin User
1. Login as admin
2. Go to Dashboard → See statistics
3. Go to Manage Employees → Verify empty list
4. Go to Leave Types → Verify active types
5. Create test employee
6. Go to Reports → Verify empty data

### Test Path: Employee User
1. Create new employee user in admin panel
2. Login as employee
3. View Dashboard → See leave balances
4. Go to Request Leave → Submit test request
5. Logout and login as admin
6. Approve request in Approve Requests
7. Login as employee → Verify balance updated

## ✅ Browser Compatibility

- [ ] Chrome/Edge (Latest)
- [ ] Firefox (Latest)
- [ ] Safari (Latest)
- [ ] Mobile browsers (iOS Safari, Chrome Android)
- [ ] Responsive design tested

## ✅ Performance Check

### Database Queries
- [ ] Login response < 1 second
- [ ] Dashboard load < 2 seconds
- [ ] Report generation < 3 seconds
- [ ] List pages with pagination load quickly

### File Uploads
- [ ] Upload directory writable
- [ ] Max file size set in config
- [ ] File validation working

## ⚠️ Common Issues & Solutions

### Issue: "Could not connect to database"
**Solution**:
- Verify MySQL is running
- Check credentials in `config/config.php`
- Verify user has proper privileges
```sql
SHOW GRANTS FOR 'leavemgt_user'@'localhost';
```

### Issue: "Table doesn't exist"
**Solution**:
- Verify schema was imported
- Check database name
```sql
USE leavemgt_db;
SHOW TABLES;
```

### Issue: Login fails with correct credentials
**Solution**:
- Verify admin user exists
- Check password hash is correct
- Verify user role_id is 1 (Admin)

### Issue: CSS/JS not loading
**Solution**:
- Verify APP_URL in config matches domain
- Check file paths in HTML
- Clear browser cache
- Check web server permissions

### Issue: Sessions expire immediately
**Solution**:
- Check SESSION_TIMEOUT in config
- Verify session directory permissions
- Check PHP session settings

### Issue: Leave balance not updating
**Solution**:
- Verify leave_balances table populated
- Check transaction support enabled
- Verify calculation logic

## 📞 Support Resources

### Documentation
- `README.md` - Full documentation
- `QUICKSTART.md` - Quick setup guide
- `database.sql` - Schema reference

### Logs
- Check `logs/` directory for errors
- Review PHP error log
- Check MySQL error log

### Database Queries
```sql
-- Debug queries
SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 10;
SELECT * FROM sessions;
SELECT * FROM leave_requests WHERE status = 'pending';
```

## ✅ Final Verification Checklist

- [ ] All database tables created
- [ ] Admin user created and can login
- [ ] Admin dashboard loads correctly
- [ ] Employee dashboard works
- [ ] Leave request submission working
- [ ] Approval workflow functional
- [ ] Leave balance calculations correct
- [ ] Reports generate successfully
- [ ] CSS/UI displays properly
- [ ] Mobile responsive design working
- [ ] No JavaScript errors in console
- [ ] Session management working
- [ ] Logout functionality works
- [ ] Logs directory created
- [ ] Backups configured

---

## 🎉 Installation Complete!

Your Leave Management System is ready to use. Start by:
1. Creating departments
2. Adding leave types
3. Importing employees
4. Testing the workflow

For detailed usage, refer to `README.md` and `QUICKSTART.md`
