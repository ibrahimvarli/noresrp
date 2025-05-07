<?php
/**
 * Multiplayer System Functions
 * This file contains functions for managing multiplayer features:
 * - Player-to-player interaction
 * - Real-time communication
 * - Player matching
 * - Multi-session management
 * - Server load balancing
 */

/**
 * Get online players in the same location
 */
function getOnlinePlayers($locationId = null, $limit = 50) {
    global $db;
    
    $sql = "SELECT c.id, c.name, c.gender, c.race, c.portrait, c.level, c.location_id, 
                   u.last_activity, l.name as location_name
            FROM characters c 
            JOIN users u ON c.user_id = u.id
            LEFT JOIN locations l ON c.location_id = l.id
            WHERE c.is_active = 1 
            AND u.last_activity > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
    
    if ($locationId) {
        $sql .= " AND c.location_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $locationId);
    } else {
        $stmt = $db->prepare($sql);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Send a real-time message to another player
 */
function sendPlayerMessage($senderId, $receiverId, $messageContent, $messageType = 'chat') {
    global $db;
    
    // Validate sender and receiver
    if (!characterExists($senderId) || !characterExists($receiverId)) {
        return [
            'success' => false,
            'message' => 'Invalid sender or receiver'
        ];
    }
    
    // Check for spam/flood protection
    $sql = "SELECT COUNT(*) as message_count FROM player_messages 
            WHERE sender_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $senderId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['message_count'] > 10) {
        return [
            'success' => false,
            'message' => 'Message sending rate limit exceeded. Please wait a moment.'
        ];
    }
    
    // Insert message
    $sql = "INSERT INTO player_messages (sender_id, receiver_id, message_content, message_type) 
            VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("iiss", $senderId, $receiverId, $messageContent, $messageType);
    
    if ($stmt->execute()) {
        $messageId = $db->insert_id;
        
        // Update real-time notification queue
        addRealTimeNotification($receiverId, [
            'type' => 'message',
            'from' => $senderId,
            'from_name' => getCharacterName($senderId),
            'message_id' => $messageId,
            'preview' => substr($messageContent, 0, 50) . (strlen($messageContent) > 50 ? '...' : '')
        ]);
        
        return [
            'success' => true,
            'message_id' => $messageId,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to send message: ' . $db->error
        ];
    }
}

/**
 * Get recent messages between two players
 */
function getPlayerMessages($characterId, $otherPlayerId, $limit = 50) {
    global $db;
    
    $sql = "SELECT pm.*, c1.name as sender_name, c1.portrait as sender_portrait 
            FROM player_messages pm
            JOIN characters c1 ON pm.sender_id = c1.id
            WHERE (pm.sender_id = ? AND pm.receiver_id = ?) 
            OR (pm.sender_id = ? AND pm.receiver_id = ?)
            ORDER BY pm.created_at DESC LIMIT ?";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("iiiii", $characterId, $otherPlayerId, $otherPlayerId, $characterId, $limit);
    $stmt->execute();
    
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Mark messages as read
    $sql = "UPDATE player_messages SET is_read = 1 
            WHERE receiver_id = ? AND sender_id = ? AND is_read = 0";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $characterId, $otherPlayerId);
    $stmt->execute();
    
    return array_reverse($messages); // Return in chronological order
}

/**
 * Get unread message count
 */
function getUnreadMessageCount($characterId) {
    global $db;
    
    $sql = "SELECT COUNT(*) as unread_count FROM player_messages 
            WHERE receiver_id = ? AND is_read = 0";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_assoc()['unread_count'];
}

/**
 * Add a notification to the real-time queue
 */
function addRealTimeNotification($characterId, $notificationData) {
    global $db;
    
    $sql = "INSERT INTO real_time_notifications (character_id, notification_data) 
            VALUES (?, ?)";
    $stmt = $db->prepare($sql);
    $jsonData = json_encode($notificationData);
    $stmt->bind_param("is", $characterId, $jsonData);
    
    return $stmt->execute();
}

/**
 * Get pending real-time notifications
 */
function getPendingNotifications($characterId) {
    global $db;
    
    $sql = "SELECT * FROM real_time_notifications 
            WHERE character_id = ? AND is_delivered = 0
            ORDER BY created_at ASC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    
    $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Mark notifications as delivered
    if (!empty($notifications)) {
        $notificationIds = array_column($notifications, 'id');
        $idList = implode(',', $notificationIds);
        
        $sql = "UPDATE real_time_notifications SET is_delivered = 1 
                WHERE id IN ($idList)";
        $db->query($sql);
    }
    
    return $notifications;
}

/**
 * Find players for matchmaking
 */
function findPlayersForMatching($characterId, $criteria = []) {
    global $db;
    
    // Get character info
    $character = getCharacter($characterId);
    if (!$character) {
        return [
            'success' => false,
            'message' => 'Character not found'
        ];
    }
    
    // Default criteria
    $defaultCriteria = [
        'level_range' => 5,         // Match players within 5 levels
        'same_location' => true,    // Match players in same location
        'exclude_friends' => false, // Include friends in results
        'activity' => 'any',        // Match based on activity type
        'limit' => 10               // Number of matches to return
    ];
    
    // Merge with provided criteria
    $criteria = array_merge($defaultCriteria, $criteria);
    
    // Build query
    $sql = "SELECT c.id, c.name, c.gender, c.race, c.portrait, c.level, 
                   c.location_id, l.name as location_name, u.last_activity
            FROM characters c
            JOIN users u ON c.user_id = u.id
            LEFT JOIN locations l ON c.location_id = l.id
            WHERE c.id != ? AND c.is_active = 1
            AND u.last_activity > DATE_SUB(NOW(), INTERVAL 30 MINUTE)";
    
    $params = [$characterId];
    $types = "i";
    
    // Apply level range filter
    if ($criteria['level_range'] > 0) {
        $minLevel = max(1, $character['level'] - $criteria['level_range']);
        $maxLevel = $character['level'] + $criteria['level_range'];
        $sql .= " AND c.level BETWEEN ? AND ?";
        $params[] = $minLevel;
        $params[] = $maxLevel;
        $types .= "ii";
    }
    
    // Apply location filter
    if ($criteria['same_location'] && $character['location_id']) {
        $sql .= " AND c.location_id = ?";
        $params[] = $character['location_id'];
        $types .= "i";
    }
    
    // Exclude friends if needed
    if ($criteria['exclude_friends']) {
        $sql .= " AND NOT EXISTS (
                    SELECT 1 FROM character_relationships
                    WHERE character_id = ? AND target_character_id = c.id AND status = 'active'
                  )";
        $params[] = $characterId;
        $types .= "i";
    }
    
    // Activity-specific filters
    if ($criteria['activity'] != 'any') {
        switch ($criteria['activity']) {
            case 'quest':
                $sql .= " AND EXISTS (
                            SELECT 1 FROM character_quests 
                            WHERE character_id = c.id AND status = 'in_progress'
                          )";
                break;
            case 'trade':
                $sql .= " AND EXISTS (
                            SELECT 1 FROM character_skills 
                            WHERE character_id = c.id AND skill_name IN ('trading', 'crafting', 'blacksmithing')
                          )";
                break;
            case 'combat':
                $sql .= " AND c.last_combat_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
                break;
        }
    }
    
    // Add limit and order by activity
    $sql .= " ORDER BY u.last_activity DESC LIMIT ?";
    $params[] = $criteria['limit'];
    $types .= "i";
    
    // Execute query
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    $matches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Calculate compatibility scores
    foreach ($matches as &$match) {
        $match['compatibility'] = calculateCompatibilityScore($character, $match);
    }
    
    // Sort by compatibility
    usort($matches, function($a, $b) {
        return $b['compatibility'] - $a['compatibility'];
    });
    
    return [
        'success' => true,
        'matches' => $matches
    ];
}

/**
 * Calculate compatibility score between two characters
 */
function calculateCompatibilityScore($character1, $character2) {
    $score = 50; // Base score
    
    // Level proximity (closer levels = higher score)
    $levelDiff = abs($character1['level'] - $character2['level']);
    $score += max(0, 20 - ($levelDiff * 2));
    
    // Same race bonus
    if ($character1['race'] == $character2['race']) {
        $score += 10;
    }
    
    // Same location bonus
    if ($character1['location_id'] == $character2['location_id']) {
        $score += 15;
    }
    
    // Add randomness factor to prevent always matching the same players
    $score += rand(-5, 5);
    
    return min(100, max(0, $score));
}

/**
 * Update user session data for multi-session management
 */
function updateUserSession($userId, $sessionId) {
    global $db;
    
    // Update last activity timestamp
    $sql = "UPDATE users SET last_activity = NOW(), session_id = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("si", $sessionId, $userId);
    
    if ($stmt->execute()) {
        // Check and clean up old sessions if needed
        cleanupOldSessions($userId);
        
        return true;
    }
    
    return false;
}

/**
 * Clean up old user sessions
 */
function cleanupOldSessions($userId) {
    global $db;
    
    // Get user's session data
    $sql = "SELECT session_data FROM user_sessions WHERE user_id = ? ORDER BY last_activity DESC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Keep only the last 3 sessions
    if (count($sessions) > 3) {
        $sql = "DELETE FROM user_sessions 
                WHERE user_id = ? AND id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM user_sessions 
                        WHERE user_id = ? 
                        ORDER BY last_activity DESC LIMIT 3
                    ) as keep_sessions
                )";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ii", $userId, $userId);
        $stmt->execute();
    }
}

/**
 * Check if a session is valid
 */
function isSessionValid($userId, $sessionId) {
    global $db;
    
    $sql = "SELECT COUNT(*) as valid FROM users 
            WHERE id = ? AND session_id = ? AND last_activity > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("is", $userId, $sessionId);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_assoc()['valid'] > 0;
}

/**
 * Get server stats for load balancing
 */
function getServerStats() {
    global $db;
    
    // Get active user count
    $sql = "SELECT COUNT(*) as active_users FROM users 
            WHERE last_activity > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
    $result = $db->query($sql);
    $activeUsers = $result->fetch_assoc()['active_users'];
    
    // Get average query time if available
    $sql = "SELECT AVG(query_time) as avg_query_time 
            FROM performance_logs 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
    $result = $db->query($sql);
    $avgQueryTime = $result->fetch_assoc()['avg_query_time'] ?? 0;
    
    // Get server load if possible (this is OS-dependent)
    $serverLoad = 0;
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        $serverLoad = $load[0];
    }
    
    return [
        'active_users' => $activeUsers,
        'avg_query_time' => $avgQueryTime,
        'server_load' => $serverLoad,
        'timestamp' => time()
    ];
}

/**
 * Log performance metrics for monitoring
 */
function logPerformanceMetrics($queryTime, $endpoint) {
    global $db;
    
    $sql = "INSERT INTO performance_logs (query_time, endpoint, user_count) 
            VALUES (?, ?, (
                SELECT COUNT(*) FROM users 
                WHERE last_activity > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            ))";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ds", $queryTime, $endpoint);
    
    return $stmt->execute();
}

/**
 * Check if server should redirect to another node for load balancing
 */
function checkLoadBalancing() {
    global $db;
    
    // Get configured threshold
    $sql = "SELECT setting_value FROM game_settings WHERE setting_name = 'load_balance_threshold'";
    $result = $db->query($sql);
    $threshold = $result->fetch_assoc()['setting_value'] ?? 200;
    
    // Get current stats
    $stats = getServerStats();
    
    // Check if we need to redirect
    if ($stats['active_users'] > $threshold) {
        // Get available server nodes
        $sql = "SELECT * FROM server_nodes 
                WHERE status = 'active' AND active_users < ?
                ORDER BY active_users ASC LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $threshold);
        $stmt->execute();
        $alternative = $stmt->get_result()->fetch_assoc();
        
        if ($alternative) {
            return [
                'should_redirect' => true,
                'redirect_url' => $alternative['server_url'],
                'node_id' => $alternative['id']
            ];
        }
    }
    
    return [
        'should_redirect' => false
    ];
}

/**
 * Register a node in the server cluster
 */
function registerServerNode($nodeId, $serverUrl, $capacity) {
    global $db;
    
    $sql = "INSERT INTO server_nodes (id, server_url, capacity, active_users, status, last_heartbeat)
            VALUES (?, ?, ?, 0, 'active', NOW())
            ON DUPLICATE KEY UPDATE 
            server_url = ?, capacity = ?, last_heartbeat = NOW(), status = 'active'";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ssiss", $nodeId, $serverUrl, $capacity, $serverUrl, $capacity);
    
    return $stmt->execute();
}

/**
 * Update server node heartbeat
 */
function updateNodeHeartbeat($nodeId, $activeUsers) {
    global $db;
    
    $sql = "UPDATE server_nodes 
            SET last_heartbeat = NOW(), active_users = ?
            WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("is", $activeUsers, $nodeId);
    
    return $stmt->execute();
} 