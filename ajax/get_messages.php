<?php
/**
 * AJAX endpoint for retrieving message history between players
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

// Process get messages request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['player_id'])) {
    $otherPlayerId = (int)$_GET['player_id'];
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    
    // Validate the other player exists
    if (!characterExists($otherPlayerId)) {
        echo json_encode([
            'success' => false,
            'message' => 'Character not found.'
        ]);
        exit;
    }
    
    // Get message history
    $messages = getPlayerMessages($activeCharacterId, $otherPlayerId, $limit);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
    exit;
}

// Invalid request
echo json_encode([
    'success' => false,
    'message' => 'Invalid request.'
]);
exit; 