# Leave Management System (LMS)

A modern, mobile-first leave management system built with pure PHP. Digitize and automate employee leave operations with role-based access control, leave balance tracking, and comprehensive reporting.

## 🚀 Features

### Core Functionality
- **Employee Leave Management**
  - Submit leave requests with flexible date ranges
  - Track leave balance and remaining days
  - View leave request history and status
  - Automatic working day calculation (excludes weekends)

- **Admin Control Panel**
  - Manage employees and departments
  - Configure leave types and policies
  - Review and approve/reject leave requests
  - Generate comprehensive reports
  - Audit trail for all actions

- **Modern UI**
  - Mobile-first responsive design
  - Clean, intuitive interface
  - Real-time calculations
  - Interactive dashboards

### Security
- Secure password hashing (bcrypt)
- Session-based authentication
- SQL injection prevention (prepared statements)
- XSS protection
- Role-based access control (RBAC)
- CSRF token protection

## 📋 Requirements

- **PHP 7.4+** (tested with PHP 8.0+)
- **MySQL 5.7+** or **MariaDB 10.2+**
- **Apache Web Server** with mod_rewrite
- **Composer** (optional, not required for this pure PHP implementation)

## 🔧 Installation

### 1. Database Setup

Create a new database:
```sql
CREATE DATABASE leavemgt_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'leavemgt_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON leavemgt_db.* TO 'leavemgt_user'@'localhost';
FLUSH PRIVILEGES;
```

Import the database schema:
```bash
mysql -u root -p leavemgt_db < database.sql
```

### 2. File Structure
Extract the project to your web root:
```
c:\xampp\htdocs\leavemgt\
├── config/
│   └── config.php
├── includes/
│   ├── Database.php
│   ├── Auth.php
│   └── helpers.php
├── admin/
│   ├── dashboard.php
│   ├── employees.php
│   ├── leave-types.php
│   └── approve-requests.php
├── assets/
│   ├── css/
│   │   ├── styles.css
│   │   └── components.css
│   ├── js/
│   └── images/
├── bootstrap.php
├── login.php
├── dashboard.php
├── request-leave.php
├── my-requests.php
├── logout.php
└── database.sql
```

### 3. Configuration

Edit `config/config.php` and update database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'leavemgt_user');
define('DB_PASS', 'secure_password');
define('DB_NAME', 'leavemgt_db');
```

Adjust other settings as needed:
- `APP_URL`: Base URL of the application
- `SESSION_TIMEOUT`: Session expiration time (default: 30 minutes)
- `DEFAULT_ANNUAL_LEAVE_DAYS`: Default leave days per year
- `WEEKEND_DAYS`: Define which days are weekends

### 4. Directory Permissions

Ensure the following directories are writable:
```bash
chmod 755 uploads/
chmod 755 logs/
```

### 5. Initial Setup

1. Access the application: `http://localhost/leavemgt/login.php`
2. Create initial admin account in database:

```sql
INSERT INTO roles (id, role_name, description) VALUES
(1, 'Admin', 'System Administrator - Full access'),
(2, 'Employee', 'Regular Employee - Basic access'),
(3, 'Manager', 'Department Manager - Approval rights');

INSERT INTO users (username, email, password_hash, first_name, last_name, role_id, status) VALUES
('admin', 'admin@example.com', '$2y$12$...', 'Admin', 'User', 1, 'active');
```

To generate a password hash in PHP:
```php
echo password_hash('secure_password', PASSWORD_BCRYPT);
```

## 👥 User Roles

### Administrator
- Full system access
- Manage employees and departments
- Configure leave types and policies
- Approve/reject leave requests
- Generate reports
- View audit logs

### Employee
- Submit leave requests
- View personal leave balance
- Track request status
- View leave history
- Upload supporting documents (optional)

### Manager
- Employee oversight capabilities
- Limited approval rights
- Department reporting

## 🎯 Usage Guide

### For Employees

1. **Login**: Enter credentials on login page
2. **View Dashboard**: See leave balances at a glance
3. **Request Leave**: 
   - Click "New Leave Request"
   - Select leave type, dates, and reason
   - System calculates working days automatically
   - Submit for approval
4. **Track Requests**: View status of all submitted requests
5. **View History**: Access complete leave history

### For Administrators

1. **Dashboard**: Overview of system status
2. **Manage Employees**:
   - Add new employees
   - Edit employee details
   - Assign departments
   - Deactivate inactive users
3. **Configure Leave Types**:
   - Create new leave types
   - Set maximum days per year
   - Enable carry-forward options
   - Set color codes for visual organization
4. **Approve Requests**:
   - Review pending requests
   - Add approval comments
   - Approve or reject
   - View history of processed requests
5. **Reports**: Analyze leave usage trends

## 📊 Database Schema

### Key Tables

**users** - Employee and admin accounts
- id, username, email, password_hash, first_name, last_name, role_id, department_id, status, last_login

**leave_types** - Leave policy definitions
- id, leave_name, description, max_days_per_year, color_code, carry_forward, require_document, status

**leave_requests** - Individual leave requests
- id, user_id, leave_type_id, start_date, end_date, number_of_days, reason, status, approver_id, approval_date, approval_comments

**leave_balances** - Track available leave per employee
- id, user_id, leave_type_id, year, total_days, used_days, remaining_days

**departments** - Organizational structure
- id, department_name, description, manager_id, status

**audit_logs** - Action logging for compliance
- id, user_id, action, entity_type, entity_id, old_values, new_values, ip_address, created_at

## 🔒 Security Considerations

1. **Database**:
   - All queries use prepared statements
   - Input validation and sanitization
   - Unique constraints on critical fields

2. **Authentication**:
   - Bcrypt password hashing (cost: 12)
   - Secure session handling
   - Session timeout after inactivity
   - CSRF token protection

3. **Authorization**:
   - Role-based access control
   - Permission checking on every request
   - Activity audit logging

4. **Best Practices**:
   - HTTPS recommended for production
   - Regular database backups
   - Keep PHP updated
   - Disable error display in production
   - Use strong database passwords

## 🛠️ Configuration Examples

### Change Session Timeout
```php
define('SESSION_TIMEOUT', 3600); // 1 hour
```

### Modify Weekend Settings
```php
define('WEEKEND_DAYS', [0, 6]); // Sunday, Saturday
// Use [6] for only Saturday, or [5, 6] for Friday and Saturday
```

### Set Fiscal Year Start
```php
define('FINANCIAL_YEAR_START', '01-04'); // April 1st start
define('FINANCIAL_YEAR_END', '03-31');   // March 31st end
```

## 📁 API/Functions Reference

### Helper Functions (includes/helpers.php)

**Authentication**
```php
Auth::hashPassword($password)           // Hash password
Auth::verifyPassword($password, $hash)  // Verify password
Auth::validatePasswordStrength($pwd)    // Check password requirements
```

**Date Utilities**
```php
calculateWorkingDays($start, $end)      // Calculate working days
getCurrentFinancialYear()                // Get current FY
formatDate($date, $format)              // Format date for display
validateDateRange($start, $end)         // Validate date range
```

**Leave Utilities**
```php
getUserLeaveBalance($userId, $typeId)   // Get balance info
hasOverlappingLeave($userId, $start, $end)  // Check conflicts
initializeLeaveBalances($userId)        // Create balances for new emp
```

**Utility Functions**
```php
sanitize($input)                        // XSS prevention
validateEmail($email)                   // Email validation
generateCsrfToken()                     // Create CSRF token
verifyCsrfToken($token)                 // Verify CSRF token
setFlashMessage($type, $message)        // Store flash message
getFlashMessage()                       // Retrieve flash message
```

### Database Class (includes/Database.php)

```php
$db = Database::getInstance();          // Get singleton instance
$db->query($sql, $params, $types)       // Execute query
$db->fetchRow($sql, $params, $types)    // Get single row
$db->fetchAll($sql, $params, $types)    // Get all rows
$db->insert($table, $data)              // Insert record
$db->update($table, $data, $where, $params)  // Update record
$db->delete($table, $where, $params)    // Delete record
$db->beginTransaction()                 // Start transaction
$db->commit()                           // Commit transaction
$db->rollback()                         // Rollback transaction
```

## 🎨 Customization

### CSS Customization
Edit `assets/css/styles.css` to modify:
- Color scheme (CSS variables at top)
- Typography
- Spacing
- Component styles

### Adding New Leave Types
1. Login as admin
2. Go to "Leave Types" section
3. Fill form and submit
4. Automatically assigned to all employees next fiscal year

### Extending the System

To add new features:
1. Create new files in `admin/` or root for user-facing pages
2. Add database tables to `database.sql`
3. Create corresponding classes in `includes/`
4. Use existing helper functions and database class

## 📧 Email Notifications (Optional)

Uncomment email settings in `config/config.php`:
```php
define('ENABLE_EMAIL_NOTIFICATIONS', true);
define('MAIL_HOST', 'smtp.gmail.com');
// Configure mail settings
```

## 🐛 Troubleshooting

### Can't Connect to Database
- Verify database credentials in `config/config.php`
- Check MySQL service is running
- Verify user has proper privileges

### Sessions Not Working
- Check `php.ini` session settings
- Ensure session directory is writable
- Clear browser cookies if needed

### CSS Not Loading
- Verify file permissions
- Check APP_URL setting
- Clear browser cache (Ctrl+Shift+Del)

### Leave Calculation Issues
- Verify WEEKEND_DAYS setting
- Check fiscal year dates
- Confirm leave balances initialized

## 📈 Future Enhancements

- [ ] Email notifications for approvals
- [ ] PDF report export
- [ ] Attendance integration
- [ ] Mobile app
- [ ] REST API
- [ ] Multi-company support
- [ ] Custom workflows
- [ ] Advanced analytics dashboard

## 📄 License

This Leave Management System is provided as-is for educational and commercial use.

## 🤝 Support

For issues or questions:
1. Check the troubleshooting section above
2. Review database.sql for schema understanding
3. Check error logs in `logs/` directory
4. Verify configuration in `config/config.php`

## 📞 Contact

For implementation support, refer to the technical documentation included in the project.

---

**Version**: 1.0.0  
**Last Updated**: January 2026  
**Built with**: Pure PHP, MySQL, HTML5, CSS3
