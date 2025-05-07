<?php
// Utility Functions

/**
 * Sanitize input data
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Redirect to another page
 */
function redirect($url) {
    if (!headers_sent()) {
        header("Location: $url");
        exit;
    } else {
        echo "<script>window.location.href='$url';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$url'></noscript>";
        exit;
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $db;
    $userId = (int)$_SESSION['user_id'];
    
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Get current language
 */
function getCurrentLanguage() {
    if (isset($_SESSION['language'])) {
        return $_SESSION['language'];
    }
    
    $currentUser = getCurrentUser();
    if ($currentUser && isset($currentUser['language'])) {
        return $currentUser['language'];
    }
    
    return DEFAULT_LANGUAGE;
}

/**
 * Set current language
 */
function setCurrentLanguage($language) {
    $availableLanguages = unserialize(AVAILABLE_LANGUAGES);
    
    if (array_key_exists($language, $availableLanguages)) {
        $_SESSION['language'] = $language;
        
        // Update user settings if logged in
        if (isLoggedIn()) {
            global $db;
            $userId = (int)$_SESSION['user_id'];
            
            $sql = "UPDATE users SET language = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("si", $language, $userId);
            $stmt->execute();
        }
        
        return true;
    }
    
    return false;
}

/**
 * Translate text
 */
function __($key) {
    static $translations = null;
    
    $language = getCurrentLanguage();
    
    // Load translations if not already loaded
    if ($translations === null) {
        $langFile = "languages/{$language}.php";
        if (file_exists($langFile)) {
            $translations = include($langFile);
        } else {
            $translations = [];
        }
    }
    
    // Return translation if it exists, otherwise return the key
    return isset($translations[$key]) ? $translations[$key] : $key;
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $randomString;
}

/**
 * Format date
 */
function formatDate($date, $format = 'd M Y, H:i') {
    return date($format, strtotime($date));
}

/**
 * Calculate experience needed for next level
 */
function getExpForLevel($level) {
    return 100 * $level * (1 + $level * 0.1);
}

/**
 * Get character stats based on race, class and level
 */
function calculateCharacterStats($race, $class, $level) {
    // Base stats
    $stats = [
        'health' => STARTING_HEALTH,
        'mana' => STARTING_MANA,
        'strength' => 10,
        'dexterity' => 10,
        'intelligence' => 10,
        'wisdom' => 10,
        'charisma' => 10
    ];
    
    // Apply race bonuses
    switch ($race) {
        case 'human':
            $stats['strength'] += 2;
            $stats['charisma'] += 2;
            break;
        case 'elf':
            $stats['dexterity'] += 3;
            $stats['intelligence'] += 1;
            break;
        case 'dwarf':
            $stats['strength'] += 3;
            $stats['health'] += 20;
            break;
        case 'orc':
            $stats['strength'] += 4;
            $stats['intelligence'] -= 1;
            break;
    }
    
    // Apply class bonuses
    switch ($class) {
        case 'warrior':
            $stats['health'] += 30;
            $stats['strength'] += 3;
            break;
        case 'mage':
            $stats['mana'] += 30;
            $stats['intelligence'] += 3;
            break;
        case 'rogue':
            $stats['dexterity'] += 3;
            $stats['charisma'] += 1;
            break;
        case 'cleric':
            $stats['wisdom'] += 3;
            $stats['mana'] += 15;
            $stats['health'] += 10;
            break;
    }
    
    // Apply level bonuses
    $levelBonus = $level - 1;
    $stats['health'] += $levelBonus * 10;
    $stats['mana'] += $levelBonus * 5;
    
    if ($class === 'warrior') {
        $stats['strength'] += floor($levelBonus * 0.5);
    } else if ($class === 'mage') {
        $stats['intelligence'] += floor($levelBonus * 0.5);
    } else if ($class === 'rogue') {
        $stats['dexterity'] += floor($levelBonus * 0.5);
    } else if ($class === 'cleric') {
        $stats['wisdom'] += floor($levelBonus * 0.5);
    }
    
    return $stats;
}

/**
 * Display error message
 */
function displayError($message) {
    return '<div class="alert alert-danger">' . $message . '</div>';
}

/**
 * Display success message
 */
function displaySuccess($message) {
    return '<div class="alert alert-success">' . $message . '</div>';
}

/**
 * Get user's active character
 */
function getActiveCharacter($userId) {
    global $db;
    
    $sql = "SELECT * FROM characters WHERE user_id = ? AND is_active = 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Update user online status
 * Called when user logs in or performs actions
 */
function updateUserActivity() {
    if (isLoggedIn()) {
        global $db;
        $userId = (int)$_SESSION['user_id'];
        
        $sql = "UPDATE users SET is_online = 1, last_activity = NOW() WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }
}

/**
 * Mark user as offline
 * Called during logout process
 */
function markUserOffline() {
    if (isLoggedIn()) {
        global $db;
        $userId = (int)$_SESSION['user_id'];
        
        $sql = "UPDATE users SET is_online = 0 WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }
}

/**
 * Check and update online status for all users
 * Users who haven't been active in the last 15 minutes are marked as offline
 */
function updateAllUserStatuses() {
    global $db;
    
    // Mark users as offline if they haven't been active in the last 15 minutes
    $sql = "UPDATE users SET is_online = 0 WHERE is_online = 1 AND last_activity < (NOW() - INTERVAL 15 MINUTE)";
    $db->query($sql);
}

/**
 * Get online users count
 */
function getOnlineUsersCount() {
    global $db;
    
    // First update all user statuses
    updateAllUserStatuses();
    
    // Count online users
    $sql = "SELECT COUNT(*) as count FROM users WHERE is_online = 1";
    $result = $db->query($sql);
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

/**
 * Get user by ID
 */
function getUserById($userId) {
    global $db;
    
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Check if password is strong enough
 */
function isStrongPassword($password) {
    // Password should be at least 8 characters
    if (strlen($password) < 8) {
        return false;
    }
    
    // Check for at least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    // Check for at least one number
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    return true;
}

/**
 * Authenticate user with remember me token
 */
function authenticateWithRememberToken() {
    if (isset($_COOKIE['remember_token']) && !isLoggedIn()) {
        $token = $_COOKIE['remember_token'];
        
        global $db;
        $sql = "SELECT * FROM users WHERE remember_token IS NOT NULL";
        $result = $db->query($sql);
        
        while ($user = $result->fetch_assoc()) {
            if (password_verify($token, $user['remember_token'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Update last login time and online status
                $sql = "UPDATE users SET last_login = NOW(), is_online = 1, last_activity = NOW() WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                
                return true;
            }
        }
    }
    
    return false;
}
?> 