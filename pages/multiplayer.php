<?php
/**
 * Multiplayer Page
 * Displays real-time chat, player matching, and online players
 */

// Include necessary files
require_once 'includes/multiplayer_system.php';
require_once 'includes/social_system.php';

// Must be logged in
if (!isLoggedIn()) {
    header('Location: index.php?page=login');
    exit;
}

// Ensure active character is selected
if (!isset($_SESSION['active_character'])) {
    header('Location: index.php?page=characters');
    exit;
}

$activeCharacterId = $_SESSION['active_character'];

// Get character data
$character = getCharacter($activeCharacterId);
if (!$character) {
    header('Location: index.php?page=characters');
    exit;
}

// Update session for multi-session management
updateUserSession($_SESSION['user_id'], session_id());

// Process actions
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        
        // Send a message to another player
        if ($_POST['action'] == 'send_message' && isset($_POST['recipient_id']) && isset($_POST['message'])) {
            $recipientId = (int)$_POST['recipient_id'];
            $message = $_POST['message'];
            
            $result = sendPlayerMessage($activeCharacterId, $recipientId, $message);
            
            if ($result['success']) {
                $success = "Message sent!";
            } else {
                $error = $result['message'];
            }
        }
        
        // Find matching players
        else if ($_POST['action'] == 'find_players') {
            $criteria = [
                'level_range' => isset($_POST['level_range']) ? (int)$_POST['level_range'] : 5,
                'same_location' => isset($_POST['same_location']) ? ($_POST['same_location'] == 'on') : true,
                'exclude_friends' => isset($_POST['exclude_friends']) ? ($_POST['exclude_friends'] == 'on') : false,
                'activity' => $_POST['activity'] ?? 'any',
                'limit' => 20
            ];
            
            $matchingPlayers = findPlayersForMatching($activeCharacterId, $criteria);
            
            if (!$matchingPlayers['success']) {
                $error = $matchingPlayers['message'];
            }
        }
    }
}

// Get online players in the same location
$onlinePlayers = getOnlinePlayers($character['location_id']);

// Get recent conversations
$recentConversations = getRecentConversations($activeCharacterId);

// Get unread message count
$unreadCount = getUnreadMessageCount($activeCharacterId);

// Get player matching results if not already set
if (!isset($matchingPlayers)) {
    $matchingPlayers = findPlayersForMatching($activeCharacterId);
}

// Page title
$pageTitle = "Multiplayer Hub";

// Include header
include 'inc/header.php';
?>

<div class="container-fluid my-4">
    <div class="row">
        <!-- Left Sidebar - Online Players -->
        <div class="col-lg-3">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i> Online Players
                    </h5>
                    <span class="badge bg-light text-primary"><?= count($onlinePlayers) ?> online</span>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" style="max-height: 500px; overflow-y: auto;">
                        <?php if (empty($onlinePlayers)): ?>
                            <div class="list-group-item text-center text-muted">
                                No players online in your area
                            </div>
                        <?php else: ?>
                            <?php foreach ($onlinePlayers as $player): ?>
                                <?php if ($player['id'] != $activeCharacterId): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex align-items-center">
                                            <img src="assets/images/portraits/<?= $player['portrait'] ?: 'default.png' ?>" 
                                                 class="img-fluid rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                            <div>
                                                <h6 class="mb-0"><?= $player['name'] ?></h6>
                                                <small class="text-muted"><?= ucfirst($player['race']) ?>, Level <?= $player['level'] ?></small>
                                            </div>
                                            <div class="ms-auto">
                                                <button class="btn btn-sm btn-outline-primary start-chat-btn" 
                                                        data-player-id="<?= $player['id'] ?>" 
                                                        data-player-name="<?= $player['name'] ?>">
                                                    <i class="fas fa-comment"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i> 
                        Players in <?= $character['location_id'] ? getLocationName($character['location_id']) : "Unknown Location" ?>
                    </small>
                </div>
            </div>
            
            <!-- Recent Conversations -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i> Recent Chats
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge bg-danger float-end"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                        <?php if (empty($recentConversations)): ?>
                            <div class="list-group-item text-center text-muted">
                                No recent conversations
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentConversations as $conv): ?>
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        <img src="assets/images/portraits/<?= $conv['portrait'] ?: 'default.png' ?>" 
                                             class="img-fluid rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                        <div>
                                            <h6 class="mb-0"><?= $conv['name'] ?></h6>
                                            <small class="text-muted"><?= substr($conv['last_message'], 0, 30) . (strlen($conv['last_message']) > 30 ? '...' : '') ?></small>
                                        </div>
                                        <?php if ($conv['unread_count'] > 0): ?>
                                            <span class="badge bg-danger ms-auto"><?= $conv['unread_count'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content - Chat or Player Matching -->
        <div class="col-lg-6">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <!-- Chat Window (Hidden by default) -->
            <div class="card mb-4" id="chatWindow" style="display: none;">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0" id="chatRecipientName">Chat</h5>
                    <button type="button" class="btn-close btn-close-white" id="closeChatBtn"></button>
                </div>
                <div class="card-body p-0">
                    <div id="chatMessages" style="height: 400px; overflow-y: auto; padding: 15px;">
                        <div class="text-center text-muted my-5">
                            <i class="fas fa-comments fa-3x mb-3"></i>
                            <p>Select a player to start chatting</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <form id="chatForm" class="d-flex">
                        <input type="hidden" id="chatRecipientId" name="recipient_id">
                        <input type="text" class="form-control me-2" id="messageInput" name="message" placeholder="Type your message..." required>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Player Matching -->
            <div class="card mb-4" id="playerMatching">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-friends me-2"></i> Find Players
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post" class="mb-4">
                        <input type="hidden" name="action" value="find_players">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="level_range" class="form-label">Level Range</label>
                                    <select class="form-select" id="level_range" name="level_range">
                                        <option value="3">±3 levels</option>
                                        <option value="5" selected>±5 levels</option>
                                        <option value="10">±10 levels</option>
                                        <option value="0">Any level</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="activity" class="form-label">Activity Type</label>
                                    <select class="form-select" id="activity" name="activity">
                                        <option value="any" selected>Any activity</option>
                                        <option value="quest">Questing</option>
                                        <option value="trade">Trading</option>
                                        <option value="combat">Combat</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="same_location" name="same_location" checked>
                                    <label class="form-check-label" for="same_location">Same location only</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="exclude_friends" name="exclude_friends">
                                    <label class="form-check-label" for="exclude_friends">Exclude existing friends</label>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-search me-1"></i> Find Players
                        </button>
                    </form>
                    
                    <hr>
                    
                    <h5>Matching Players</h5>
                    
                    <?php if (empty($matchingPlayers['matches'])): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> No matching players found. Try adjusting your search criteria.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($matchingPlayers['matches'] as $match): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <img src="assets/images/portraits/<?= $match['portrait'] ?: 'default.png' ?>" 
                                                     class="img-fluid rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                <div>
                                                    <h5 class="mb-0"><?= $match['name'] ?></h5>
                                                    <p class="mb-0 small">
                                                        Level <?= $match['level'] ?> <?= ucfirst($match['race']) ?>
                                                    </p>
                                                    <p class="mb-0 small text-muted">
                                                        <?= $match['location_name'] ?? 'Unknown Location' ?>
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <label class="form-label small">Compatibility</label>
                                                <div class="progress">
                                                    <div class="progress-bar bg-<?= getCompatibilityColor($match['compatibility']) ?>" 
                                                         role="progressbar" style="width: <?= $match['compatibility'] ?>%" 
                                                         aria-valuenow="<?= $match['compatibility'] ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?= $match['compatibility'] ?>%
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between mt-3">
                                                <button class="btn btn-sm btn-outline-primary start-chat-btn" 
                                                        data-player-id="<?= $match['id'] ?>" 
                                                        data-player-name="<?= $match['name'] ?>">
                                                    <i class="fas fa-comment me-1"></i> Chat
                                                </button>
                                                
                                                <form method="post" action="index.php?page=relationships">
                                                    <input type="hidden" name="action" value="send_request">
                                                    <input type="hidden" name="target_id" value="<?= $match['id'] ?>">
                                                    <input type="hidden" name="relationship_type" value="friend">
                                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-user-plus me-1"></i> Add Friend
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Right Sidebar - Server Info & Multiplayer Activity -->
        <div class="col-lg-3">
            <!-- Server Info -->
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-server me-2"></i> Server Info
                    </h5>
                </div>
                <div class="card-body">
                    <?php $serverStats = getServerStats(); ?>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Active Players
                            <span class="badge bg-primary rounded-pill"><?= $serverStats['active_users'] ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Server Load
                            <span class="badge bg-<?= getServerLoadColor($serverStats['server_load']) ?> rounded-pill">
                                <?= number_format($serverStats['server_load'], 2) ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Response Time
                            <span class="badge bg-info rounded-pill">
                                <?= number_format($serverStats['avg_query_time'] * 1000, 2) ?> ms
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Group Activities -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-users-cog me-2"></i> Group Activities
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Group quests, raids, events, etc. -->
                    <div class="list-group">
                        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Dragon Hunt</h6>
                                <small class="text-muted">Level 25+ | 3/5 players</small>
                            </div>
                            <span class="badge bg-danger rounded-pill">Hard</span>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Trade Caravan</h6>
                                <small class="text-muted">Level 10+ | 2/4 players</small>
                            </div>
                            <span class="badge bg-success rounded-pill">Easy</span>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Dungeon Delve</h6>
                                <small class="text-muted">Level 15+ | 2/6 players</small>
                            </div>
                            <span class="badge bg-warning rounded-pill">Medium</span>
                        </a>
                    </div>
                    
                    <div class="d-grid mt-3">
                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                            <i class="fas fa-plus me-1"></i> Create Group
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Multiplayer Events -->
            <div class="card mb-4">
                <div class="card-header bg-purple text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i> Upcoming Events
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item">
                            <h6 class="mb-1">Tournament of Champions</h6>
                            <p class="mb-1 small">Compete against other players in a grand tournament!</p>
                            <small class="text-muted">Starts in 2 days</small>
                        </div>
                        <div class="list-group-item">
                            <h6 class="mb-1">Harvest Festival</h6>
                            <p class="mb-1 small">Join the seasonal celebration with special rewards.</p>
                            <small class="text-muted">Starts in 5 days</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Group Modal -->
<div class="modal fade" id="createGroupModal" tabindex="-1" aria-labelledby="createGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createGroupModalLabel">Create Group Activity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="group_name" class="form-label">Activity Name</label>
                        <input type="text" class="form-control" id="group_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="group_type" class="form-label">Activity Type</label>
                        <select class="form-select" id="group_type" required>
                            <option value="quest">Quest</option>
                            <option value="dungeon">Dungeon Run</option>
                            <option value="raid">Raid</option>
                            <option value="trade">Trading Mission</option>
                            <option value="crafting">Group Crafting</option>
                            <option value="exploration">Exploration</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="min_level" class="form-label">Minimum Level</label>
                                <input type="number" class="form-control" id="min_level" min="1" value="1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="max_players" class="form-label">Max Players</label>
                                <input type="number" class="form-control" id="max_players" min="2" max="10" value="4">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="group_description" class="form-label">Description</label>
                        <textarea class="form-control" id="group_description" rows="3"></textarea>
                    </div>
                    
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">Create Group</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chat functionality
    const chatWindow = document.getElementById('chatWindow');
    const playerMatching = document.getElementById('playerMatching');
    const chatMessages = document.getElementById('chatMessages');
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');
    const chatRecipientId = document.getElementById('chatRecipientId');
    const chatRecipientName = document.getElementById('chatRecipientName');
    const closeChatBtn = document.getElementById('closeChatBtn');
    
    // Handle starting a chat
    document.querySelectorAll('.start-chat-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const playerId = this.getAttribute('data-player-id');
            const playerName = this.getAttribute('data-player-name');
            
            // Set recipient info
            chatRecipientId.value = playerId;
            chatRecipientName.textContent = 'Chat with ' + playerName;
            
            // Show chat window, hide matching
            chatWindow.style.display = 'block';
            playerMatching.style.display = 'none';
            
            // Load chat history
            loadChatHistory(playerId);
        });
    });
    
    // Close chat
    closeChatBtn.addEventListener('click', function() {
        chatWindow.style.display = 'none';
        playerMatching.style.display = 'block';
        chatMessages.innerHTML = '';
    });
    
    // Handle sending messages
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (messageInput.value.trim() === '') return;
        
        const recipientId = chatRecipientId.value;
        const message = messageInput.value;
        
        // Send via AJAX
        fetch('ajax/send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=send_message&recipient_id=' + recipientId + '&message=' + encodeURIComponent(message)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add message to chat
                appendMessage({
                    sender_id: <?= $activeCharacterId ?>,
                    sender_name: '<?= $character['name'] ?>',
                    sender_portrait: '<?= $character['portrait'] ?: 'default.png' ?>',
                    message_content: message,
                    created_at: data.timestamp
                });
                
                // Clear input
                messageInput.value = '';
            } else {
                alert('Failed to send message: ' + data.message);
            }
        });
    });
    
    // Function to load chat history
    function loadChatHistory(otherPlayerId) {
        chatMessages.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div></div>';
        
        fetch('ajax/get_messages.php?player_id=' + otherPlayerId)
        .then(response => response.json())
        .then(data => {
            chatMessages.innerHTML = '';
            
            if (data.messages.length === 0) {
                chatMessages.innerHTML = '<div class="text-center text-muted my-5"><p>No messages yet. Start the conversation!</p></div>';
                return;
            }
            
            data.messages.forEach(message => {
                appendMessage(message);
            });
            
            // Scroll to bottom
            chatMessages.scrollTop = chatMessages.scrollHeight;
        });
    }
    
    // Function to append a message to the chat
    function appendMessage(message) {
        const isCurrentUser = message.sender_id == <?= $activeCharacterId ?>;
        const messageDiv = document.createElement('div');
        messageDiv.className = 'd-flex mb-3 ' + (isCurrentUser ? 'justify-content-end' : 'justify-content-start');
        
        const time = new Date(message.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        if (isCurrentUser) {
            messageDiv.innerHTML = `
                <div>
                    <div class="d-flex justify-content-end mb-1">
                        <small class="text-muted me-2">${time}</small>
                        <strong>${message.sender_name}</strong>
                    </div>
                    <div class="d-flex">
                        <div class="bg-primary text-white p-2 rounded-3 message-bubble">
                            ${message.message_content}
                        </div>
                        <img src="assets/images/portraits/${message.sender_portrait}" class="rounded-circle ms-2" width="40" height="40" style="object-fit: cover;">
                    </div>
                </div>
            `;
        } else {
            messageDiv.innerHTML = `
                <div>
                    <div class="d-flex justify-content-start mb-1">
                        <strong>${message.sender_name}</strong>
                        <small class="text-muted ms-2">${time}</small>
                    </div>
                    <div class="d-flex">
                        <img src="assets/images/portraits/${message.sender_portrait}" class="rounded-circle me-2" width="40" height="40" style="object-fit: cover;">
                        <div class="bg-light p-2 rounded-3 message-bubble">
                            ${message.message_content}
                        </div>
                    </div>
                </div>
            `;
        }
        
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Check for new notifications periodically
    function checkNotifications() {
        fetch('ajax/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.notifications && data.notifications.length > 0) {
                data.notifications.forEach(notification => {
                    if (notification.type === 'message') {
                        // If chat with this user is open, refresh messages
                        if (chatRecipientId.value == notification.from) {
                            loadChatHistory(notification.from);
                        } else {
                            // Show notification
                            const toast = `
                                <div class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                                    <div class="toast-header">
                                        <strong class="me-auto">${notification.from_name}</strong>
                                        <small>just now</small>
                                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                                    </div>
                                    <div class="toast-body">
                                        ${notification.preview}
                                    </div>
                                </div>
                            `;
                            
                            const toastContainer = document.getElementById('toastContainer');
                            toastContainer.innerHTML += toast;
                            const toastElement = toastContainer.querySelector('.toast:last-child');
                            const bsToast = new bootstrap.Toast(toastElement);
                            bsToast.show();
                        }
                    }
                });
            }
            
            // Check again in 10 seconds
            setTimeout(checkNotifications, 10000);
        });
    }
    
    // Start notification polling
    setTimeout(checkNotifications, 10000);
});
</script>

<!-- Toast container for notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

<style>
.bg-purple {
    background-color: #6f42c1;
}
.message-bubble {
    max-width: 70%;
    word-break: break-word;
}
</style>

<?php
/**
 * Get recent conversations for the active character
 */
function getRecentConversations($characterId) {
    global $db;
    
    $sql = "SELECT 
                c.id, c.name, c.portrait,
                pm.message_content as last_message,
                (SELECT COUNT(*) FROM player_messages 
                 WHERE sender_id = c.id AND receiver_id = ? AND is_read = 0) as unread_count
            FROM player_messages pm
            JOIN characters c ON (
                CASE 
                    WHEN pm.sender_id = ? THEN pm.receiver_id = c.id
                    ELSE pm.sender_id = c.id
                END
            )
            WHERE (pm.sender_id = ? OR pm.receiver_id = ?)
            GROUP BY c.id
            ORDER BY MAX(pm.created_at) DESC
            LIMIT 10";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("iiii", $characterId, $characterId, $characterId, $characterId);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Helper function to get compatibility color
 */
function getCompatibilityColor($score) {
    if ($score >= 80) return 'success';
    if ($score >= 60) return 'info';
    if ($score >= 40) return 'warning';
    return 'danger';
}

/**
 * Helper function to get server load color
 */
function getServerLoadColor($load) {
    if ($load < 1) return 'success';
    if ($load < 2) return 'info';
    if ($load < 4) return 'warning';
    return 'danger';
}
?>

<?php include 'inc/footer.php'; ?> 