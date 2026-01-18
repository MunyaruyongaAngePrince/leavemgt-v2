<?php
/**
 * Leave Management System - Index/Home Page
 * Auto-redirects based on user role
 */

require_once __DIR__ . '/bootstrap.php';

// If user is logged in, redirect to appropriate dashboard
if ($isLoggedIn) {
    if ($currentUser['role_id'] === ROLE_ADMIN) {
        redirect('/leavemgt/admin/dashboard.php');
    } else {
        redirect('/leavemgt/dashboard.php');
    }
}

// Otherwise redirect to login
redirect('/leavemgt/login.php');
