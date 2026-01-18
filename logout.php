<?php
/**
 * Logout Page
 * Handles user logout
 */

require_once __DIR__ . '/bootstrap.php';

if ($isLoggedIn) {
    $auth = new Auth();
    $auth->logout($_SESSION['session_id'], $currentUser['id']);
    
    session_destroy();
    unset($_SESSION['session_id']);
}

setFlashMessage('success', 'You have been logged out successfully.');
redirect('/leavemgt/login.php');
