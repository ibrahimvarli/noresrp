<?php
/**
 * Cron script to update character aging and lifecycle events
 * This script should be run daily to update character ages and trigger lifecycle events
 */

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Define BASE_PATH as we're running from CLI
define('BASE_PATH', dirname(__DIR__));
chdir(BASE_PATH);

// Load core files
require_once BASE_PATH . '/inc/config.php';
require_once BASE_PATH . '/inc/functions.php';
require_once BASE_PATH . '/inc/world.php';

// Get current game time
$gameTime = getCurrentGameTime();
$currentGameDate = $gameTime['current_date'];

// Process character aging
$aging_result = processCharacterAging($currentGameDate);
echo "Character aging update: " . ($aging_result['success'] ? "Success" : "Failed") . " - " . $aging_result['message'] . "\n";

// Process life stage changes
$lifecycle_result = processLifecycleChanges();
echo "Lifecycle changes: " . ($lifecycle_result['success'] ? "Success" : "Failed") . " - " . $lifecycle_result['message'] . "\n";

// Process character aging events
$events_result = processAgingEvents($currentGameDate);
echo "Aging events: " . ($events_result['success'] ? "Success" : "Failed") . " - " . $events_result['message'] . "\n";

// Log the update
$log_file = BASE_PATH . '/logs/character_updates.log';
$message = date('Y-m-d H:i:s') . " - Aging: " . ($aging_result['success'] ? "Success" : "Failed") . 
           ", Lifecycle: " . ($lifecycle_result['success'] ? "Success" : "Failed") . 
           ", Events: " . ($events_result['success'] ? "Success" : "Failed") . "\n";

// Ensure log directory exists
if (!is_dir(dirname($log_file))) {
    mkdir(dirname($log_file), 0755, true);
}

// Append to log file
file_put_contents($log_file, $message, FILE_APPEND);

echo "Character updates completed at " . date('Y-m-d H:i:s') . "\n"; 