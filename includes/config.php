<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'fantasy_rp');

// Site Configuration
define('SITE_NAME', 'NoRestRP - Fantasy Role-Playing');
define('SITE_URL', 'http://localhost/norestrp/');
define('UPLOAD_DIR', 'uploads/');

// Language Settings
define('DEFAULT_LANGUAGE', 'en');
define('AVAILABLE_LANGUAGES', serialize(array('en' => 'English', 'tr' => 'Türkçe')));

// Game Settings
define('MAX_LEVEL', 100);
define('STARTING_GOLD', 100);
define('STARTING_HEALTH', 100);
define('STARTING_MANA', 50);

// Session lifetime (in seconds)
define('SESSION_LIFETIME', 86400); // 24 hours

// Default theme
define('DEFAULT_THEME', 'dark');
?> 