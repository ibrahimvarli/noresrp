<?php
/**
 * AJAX endpoint for retrieving real-time notifications
 */

// Include necessary files
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/multiplayer_system.php';

// Process only AJAX requests
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in.'
    ]);
    exit;
}

// Ensure active character is selected
if (!isset($_SESSION['active_character'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please select an active character first.'
    ]);
    exit;
}

$activeCharacterId = $_SESSION['active_character'];

// Get pending notifications
$notifications = getPendingNotifications($activeCharacterId);

// Process any server-side events
processServerEvents($activeCharacterId);

// Send response
echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'unread_messages' => getUnreadMessageCount($activeCharacterId),
    'timestamp' => time()
]);
exit;

/**
 * Process any server-side events that should generate notifications
 */
function processServerEvents($characterId) {
    global $db;
    
    // Check for new relationship requests
    $sql = "SELECT COUNT(*) as new_requests FROM character_relationships 
            WHERE target_character_id = ? AND status = 'pending' 
            AND started_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            AND NOT EXISTS (
                SELECT 1 FROM real_time_notifications 
                WHERE character_id = ? 
                AND JSON_EXTRACT(notification_data, '$.type') = 'relationship_request'
                AND JSON_EXTRACT(notification_data, '$.relationship_id') = character_relationships.id
            )";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $characterId, $characterId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['new_requests'] > 0) {
        // Get the newest requests
        $sql = "SELECT cr.*, c.name as sender_name, c.portrait as sender_portrait 
                FROM character_relationships cr
                JOIN characters c ON cr.character_id = c.id
                WHERE cr.target_character_id = ? AND cr.status = 'pending' 
                AND cr.started_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                AND NOT EXISTS (
                    SELECT 1 FROM real_time_notifications 
                    WHERE character_id = ? 
                    AND JSON_EXTRACT(notification_data, '$.type') = 'relationship_request'
                    AND JSON_EXTRACT(notification_data, '$.relationship_id') = cr.id
                )
                ORDER BY cr.started_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ii", $characterId, $characterId);
        $stmt->execute();
        $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($requests as $request) {
            // Create notification for each request
            addRealTimeNotification($characterId, [
                'type' => 'relationship_request',
                'relationship_id' => $request['id'],
                'from' => $request['character_id'],
                'from_name' => $request['sender_name'],
                'relationship_type' => $request['relationship_type'],
                'message' => $request['sender_name'] . ' wants to be your ' . getRelationshipTypeLabel($request['relationship_type'])
            ]);
        }
    }
    
    // Check for upcoming multiplayer events
    $sql = "SELECT * FROM social_events 
            WHERE status = 'planned' 
            AND start_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 15 MINUTE)
            AND EXISTS (
                SELECT 1 FROM social_event_attendees
                WHERE event_id = social_events.id AND character_id = ? AND status = 'attending'
            )
            AND NOT EXISTS (
                SELECT 1 FROM real_time_notifications 
                WHERE character_id = ? 
                AND JSON_EXTRACT(notification_data, '$.type') = 'event_reminder'
                AND JSON_EXTRACT(notification_data, '$.event_id') = social_events.id
            )";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $characterId, $characterId);
    $stmt->execute();
    $events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($events as $event) {
        // Create notification for upcoming events
        addRealTimeNotification($characterId, [
            'type' => 'event_reminder',
            'event_id' => $event['id'],
            'title' => $event['title'],
            'start_time' => $event['start_date'],
            'message' => 'Reminder: ' . $event['title'] . ' starts soon!'
        ]);
    }
}

/**
 * Helper function to get relationship label
 */
function getRelationshipTypeLabel($type) {
    switch ($type) {
        case 'friend':
            return 'Friend';
        case 'best_friend':
            return 'Best Friend';
        case 'dating':
            return 'Dating Partner';
        case 'engaged':
            return 'Fiancé(e)';
        case 'married':
            return 'Spouse';
        default:
            return ucfirst(str_replace('_', ' ', $type));
    }
} 