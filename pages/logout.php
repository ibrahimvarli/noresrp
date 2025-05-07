<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mark the user as offline before logout
if (isset($_SESSION['user_id'])) {
    require_once '../includes/config.php';
    require_once '../includes/functions.php';
    require_once '../includes/db.php';
    
    markUserOffline();
}

// Clear all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Delete remember me cookie if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Set flash message
session_start();
$_SESSION['flash_message'] = __('logout_success');
$_SESSION['flash_type'] = "success";

// Redirect to home page
header("Location: index.php?page=home");
exit;
?> 