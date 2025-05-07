<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Update user activity if logged in
if (isLoggedIn()) {
    updateUserActivity();
}

// Update all user statuses (will mark inactive users as offline)
updateAllUserStatuses();

// Handle language switch
if (isset($_GET['language'])) {
    $language = $_GET['language'];
    if (setCurrentLanguage($language)) {
        // If this is the only parameter, redirect to current page without it
        if (count($_GET) === 1) {
            header('Location: ' . SITE_URL);
            exit;
        }
        
        // Otherwise, redirect to the same page without the language parameter
        $params = $_GET;
        unset($params['language']);
        $url = '?' . http_build_query($params);
        header('Location: ' . $url);
        exit;
    }
}

// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']);

// Get current page from URL
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Define valid pages
$validPages = ['home', 'login', 'register', 'profile', 'settings', 'characters', 'world', 'quests', 'inventory', 'battle', 'forum', 'logout', 'character_lifecycle', 'basic_needs', 'career'];

// Validate page
if (!in_array($page, $validPages)) {
    $page = 'home';
}

// Include header
include 'includes/header.php';

// Include page content
include 'pages/' . $page . '.php';

// Include footer
include 'includes/footer.php';
?> 