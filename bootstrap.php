<?php
/**
 * Bootstrap File
 * Initialize all necessary includes and start sessions
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include configuration
require_once __DIR__ . '/config/config.php';

// Include database class
require_once __DIR__ . '/includes/Database.php';

// Include authentication class
require_once __DIR__ . '/includes/Auth.php';

// Include helper functions
require_once __DIR__ . '/includes/helpers.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set current user from session
$currentUser = null;
$isLoggedIn = false;

if (isset($_SESSION['session_id'])) {
    $auth = new Auth();
    $session = $auth->verifySession($_SESSION['session_id']);
    
    if ($session) {
        $currentUser = $session;
        $isLoggedIn = true;
    } else {
        session_destroy();
        unset($_SESSION['session_id']);
    }
}
