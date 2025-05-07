<?php
/**
 * Cron script for server maintenance tasks
 * This script should be run periodically (every 5-15 minutes) to:
 * - Cleanup expired sessions
 * - Update server statistics
 * - Handle load balancing between nodes
 * - Prune old performance logs
 */

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Define BASE_PATH as we're running from CLI
define('BASE_PATH', dirname(__DIR__));
chdir(BASE_PATH);

// Load core files
require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/db.php';
require_once BASE_PATH . '/includes/multiplayer_system.php';

// Generate a unique node ID if not set
$nodeId = getNodeId();

// Register this server in the cluster
$serverUrl = getServerUrl();
$capacity = getServerCapacity();
registerServerNode($nodeId, $serverUrl, $capacity);

// Get server statistics
$stats = getServerStats();
echo "Current server stats:\n";
echo "Active users: " . $stats['active_users'] . "\n";
echo "Average query time: " . number_format($stats['avg_query_time'] * 1000, 2) . " ms\n";
echo "Server load: " . number_format($stats['server_load'], 2) . "\n";

// Update heartbeat with current stats
updateNodeHeartbeat($nodeId, $stats['active_users']);

// Clean up expired sessions
$expiredSessions = cleanupExpiredSessions();
echo "Expired sessions removed: " . $expiredSessions . "\n";

// Check for inactive server nodes
$inactiveNodes = markInactiveNodes();
echo "Inactive nodes marked: " . $inactiveNodes . "\n";

// Prune old performance logs
$prunedLogs = pruneOldLogs();
echo "Old performance logs pruned: " . $prunedLogs . "\n";

// Clean up old notifications
$prunedNotifications = pruneOldNotifications();
echo "Old notifications pruned: " . $prunedNotifications . "\n";

/**
 * Get or generate a node ID for this server
 */
function getNodeId() {
    $configFile = BASE_PATH . '/includes/node_id.php';
    
    if (file_exists($configFile)) {
        include $configFile;
        return $nodeId;
    } else {
        // Generate a new UUID
        $nodeId = generateUuid();
        
        // Save to config file
        $content = "<?php\n// Auto-generated node ID\n\$nodeId = '$nodeId';\n";
        file_put_contents($configFile, $content);
        
        return $nodeId;
    }
}

/**
 * Generate a UUID v4
 */
function generateUuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

/**
 * Get the server URL
 */
function getServerUrl() {
    global $config;
    
    return isset($config['server_url']) ? $config['server_url'] : 'http://localhost';
}

/**
 * Get server capacity
 */
function getServerCapacity() {
    global $config, $db;
    
    // Try to get from config
    if (isset($config['server_capacity'])) {
        return (int)$config['server_capacity'];
    }
    
    // Try to get from settings table
    $sql = "SELECT setting_value FROM game_settings WHERE setting_name = 'server_capacity'";
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return (int)$result->fetch_assoc()['setting_value'];
    }
    
    // Default value
    return 200;
}

/**
 * Clean up expired sessions
 */
function cleanupExpiredSessions() {
    global $db;
    
    // Sessions inactive for more than 1 hour
    $sql = "DELETE FROM user_sessions 
            WHERE last_activity < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $db->query($sql);
    
    return $db->affected_rows;
}

/**
 * Mark inactive server nodes
 */
function markInactiveNodes() {
    global $db;
    
    // Nodes without heartbeat for more than 5 minutes
    $sql = "UPDATE server_nodes 
            SET status = 'inactive' 
            WHERE last_heartbeat < DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
            AND status = 'active'";
    $db->query($sql);
    
    return $db->affected_rows;
}

/**
 * Prune old performance logs
 */
function pruneOldLogs() {
    global $db;
    
    // Keep logs for the last 7 days
    $sql = "DELETE FROM performance_logs 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $db->query($sql);
    
    return $db->affected_rows;
}

/**
 * Prune old delivered notifications
 */
function pruneOldNotifications() {
    global $db;
    
    // Keep delivered notifications for 1 day
    $sql = "DELETE FROM real_time_notifications 
            WHERE is_delivered = 1 
            AND created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)";
    $db->query($sql);
    
    return $db->affected_rows;
} 