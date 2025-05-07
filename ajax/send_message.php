<?php
/**
 * AJAX endpoint for sending messages between players
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

// Process send message action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    // Validate inputs
    if (!isset($_POST['recipient_id']) || !isset($_POST['message']) || empty($_POST['message'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields.'
        ]);
        exit;
    }
    
    $recipientId = (int)$_POST['recipient_id'];
    $message = htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8');
    
    // Send the message
    $result = sendPlayerMessage($activeCharacterId, $recipientId, $message);
    
    echo json_encode($result);
    exit;
}

// Invalid request
echo json_encode([
    'success' => false,
    'message' => 'Invalid request.'
]);
exit; 