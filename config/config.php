<?php
/**
 * Leave Management System - Configuration File
 * Core settings and constants
 */

// ============================================
// DATABASE CONFIGURATION
// ============================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'leavemgt_db');
define('DB_PORT', 3306);

// ============================================
// APPLICATION SETTINGS
// ============================================
define('APP_NAME', 'Leave Management System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/leavemgt');
define('APP_TIMEZONE', 'UTC');

// ============================================
// SECURITY SETTINGS
// ============================================
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_SPECIAL_CHARS', true);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 900); // 15 minutes

// ============================================
// FILE UPLOAD SETTINGS
// ============================================
define('MAX_UPLOAD_SIZE', 5242880); // 5MB in bytes
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);

// ============================================
// LEAVE SETTINGS
// ============================================
define('FINANCIAL_YEAR_START', '01-01'); // MM-DD format
define('FINANCIAL_YEAR_END', '12-31'); // MM-DD format
define('DEFAULT_ANNUAL_LEAVE_DAYS', 20);
define('WEEKEND_DAYS', [0, 6]); // 0=Sunday, 6=Saturday

// ============================================
// EMAIL SETTINGS
// ============================================
define('ENABLE_EMAIL_NOTIFICATIONS', false);
define('MAIL_HOST', 'smtp.example.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'noreply@example.com');
define('MAIL_PASSWORD', '');
define('MAIL_FROM_NAME', 'Leave Management System');

// ============================================
// LOGGING
// ============================================
define('ENABLE_LOGGING', true);
define('LOG_DIR', __DIR__ . '/../logs/');
define('LOG_LEVEL', 'INFO'); // ERROR, WARNING, INFO, DEBUG

// ============================================
// PAGINATION
// ============================================
define('ITEMS_PER_PAGE', 15);
define('MAX_ITEMS_PER_PAGE', 100);

// ============================================
// REPORTING
// ============================================
define('ENABLE_PDF_EXPORT', false);
define('REPORT_DATE_FORMAT', 'Y-m-d');

// ============================================
// TIMEZONE
// ============================================
date_default_timezone_set(APP_TIMEZONE);

// ============================================
// ERROR HANDLING
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', LOG_DIR . 'php_errors.log');

// ============================================
// SESSION CONFIGURATION
// ============================================
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');

// ============================================
// ENSURE REQUIRED DIRECTORIES EXIST
// ============================================
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

// ============================================
// ROLE CONSTANTS
// ============================================
define('ROLE_ADMIN', 1);
define('ROLE_EMPLOYEE', 2);
define('ROLE_MANAGER', 3);

// ============================================
// STATUS CONSTANTS
// ============================================
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');
define('STATUS_PENDING', 'pending');
define('STATUS_APPROVED', 'approved');
define('STATUS_REJECTED', 'rejected');
define('STATUS_CANCELLED', 'cancelled');
