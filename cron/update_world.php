<?php
/**
 * Cron script to update game time and weather
 * This script should be run periodically (e.g. every 5-15 minutes) to update the world state
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
require_once BASE_PATH . '/inc/character_lifecycle.php';

// Update game time
$time_result = updateGameTime();
echo "Time update: " . ($time_result['success'] ? "Success" : "Failed") . " - " . $time_result['message'] . "\n";

// Update weather system
$weather_result = updateWeather();
echo "Weather update: " . ($weather_result['success'] ? "Success" : "Failed") . " - " . $weather_result['message'] . "\n";

// Get current game time
$gameTime = getCurrentGameTime();
$currentGameDate = $gameTime['current_date'];

// Process character aging (daily)
// Only process character aging if the date has changed since the last run
$last_update_file = BASE_PATH . '/logs/last_character_update.txt';
$last_update_date = '';

if (file_exists($last_update_file)) {
    $last_update_date = trim(file_get_contents($last_update_file));
}

if ($last_update_date != $currentGameDate) {
    // Process character aging
    $aging_result = processCharacterAging($currentGameDate);
    echo "Character aging update: " . ($aging_result['success'] ? "Success" : "Failed") . " - " . $aging_result['message'] . "\n";
    
    // Process life stage changes
    $lifecycle_result = processLifecycleChanges();
    echo "Lifecycle changes: " . ($lifecycle_result['success'] ? "Success" : "Failed") . " - " . $lifecycle_result['message'] . "\n";
    
    // Process character aging events
    $events_result = processAgingEvents($currentGameDate);
    echo "Aging events: " . ($events_result['success'] ? "Success" : "Failed") . " - " . $events_result['message'] . "\n";
    
    // Update last update date
    file_put_contents($last_update_file, $currentGameDate);
}

// Additional world updates can be added here
// For example:
// - Restock shop inventories
// - Spawn special events
// - Apply weather effects to characters

// Log the update
$log_file = BASE_PATH . '/logs/world_updates.log';
$message = date('Y-m-d H:i:s') . " - Time: " . ($time_result['success'] ? "Success" : "Failed") . 
           ", Weather: " . ($weather_result['success'] ? "Success" : "Failed") . "\n";

// Ensure log directory exists
if (!is_dir(dirname($log_file))) {
    mkdir(dirname($log_file), 0755, true);
}

// Append to log file
file_put_contents($log_file, $message, FILE_APPEND);

echo "World update completed at " . date('Y-m-d H:i:s') . "\n"; 