<?php
/**
 * Relationships Page
 * Displays character's relationships, social interactions, and family
 */

// Include necessary files
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

// Process actions
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        
        // Send friend request
        if ($_POST['action'] == 'send_request' && isset($_POST['target_id'])) {
            $targetId = (int)$_POST['target_id'];
            $type = $_POST['relationship_type'] ?? 'friend';
            
            $result = initiateRelationship($activeCharacterId, $targetId, $type);
            
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
        
        // Accept friend request
        else if ($_POST['action'] == 'accept_request' && isset($_POST['relationship_id'])) {
            $relationshipId = (int)$_POST['relationship_id'];
            
            $result = acceptRelationship($relationshipId);
            
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
        
        // Decline friend request
        else if ($_POST['action'] == 'decline_request' && isset($_POST['relationship_id'])) {
            $relationshipId = (int)$_POST['relationship_id'];
            
            $result = endRelationship($relationshipId);
            
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
        
        // End relationship
        else if ($_POST['action'] == 'end_relationship' && isset($_POST['relationship_id'])) {
            $relationshipId = (int)$_POST['relationship_id'];
            
            $result = endRelationship($relationshipId);
            
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
        
        // Perform social interaction
        else if ($_POST['action'] == 'social_interaction' && isset($_POST['target_id']) && isset($_POST['interaction_type'])) {
            $targetId = (int)$_POST['target_id'];
            $interactionType = $_POST['interaction_type'];
            
            $result = performSocialInteraction($activeCharacterId, $targetId, $interactionType);
            
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
        
        // Upgrade relationship (dating, engaged, etc.)
        else if ($_POST['action'] == 'upgrade_relationship' && isset($_POST['relationship_id']) && isset($_POST['new_type'])) {
            $relationshipId = (int)$_POST['relationship_id'];
            $newType = $_POST['new_type'];
            
            $result = upgradeRelationship($relationshipId, $newType);
            
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
        
        // Plan wedding
        else if ($_POST['action'] == 'plan_wedding' && isset($_POST['partner_id']) && isset($_POST['location_id']) && isset($_POST['wedding_date'])) {
            $partnerId = (int)$_POST['partner_id'];
            $locationId = (int)$_POST['location_id'];
            $weddingDate = $_POST['wedding_date'];
            $isPrivate = isset($_POST['is_private']) ? 1 : 0;
            $maxAttendees = (int)($_POST['max_attendees'] ?? 0);
            
            $result = planWedding($activeCharacterId, $partnerId, $locationId, $weddingDate, $isPrivate, $maxAttendees);
            
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
        
        // Parent-child activity
        else if ($_POST['action'] == 'parenting' && isset($_POST['child_id']) && isset($_POST['activity_type'])) {
            $childId = (int)$_POST['child_id'];
            $activityType = $_POST['activity_type'];
            
            $result = performParentingActivity($activeCharacterId, $childId, $activityType);
            
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
        
        // Have a child
        else if ($_POST['action'] == 'have_child' && isset($_POST['partner_id']) && isset($_POST['child_name']) && isset($_POST['child_gender'])) {
            $partnerId = (int)$_POST['partner_id'];
            $childName = $_POST['child_name'];
            $childGender = $_POST['child_gender'];
            
            $result = createChild($activeCharacterId, $partnerId, $childName, $childGender);
            
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Get relationship data
$friendships = getCharacterRelationships($activeCharacterId, 'friend');
$romanticRelationships = getCharacterRelationships($activeCharacterId, 'dating');
$engagements = getCharacterRelationships($activeCharacterId, 'engaged');
$marriages = getCharacterRelationships($activeCharacterId, 'married');
$pendingRequests = getPendingRelationshipRequests($activeCharacterId);
$family = getCharacterFamily($activeCharacterId);

// Get characters for new relationships
$potentialContacts = getPotentialSocialTargets($activeCharacterId, $character['location_id']);

// Page title
$pageTitle = "Relationships & Social";

// Include header
include 'inc/header.php';
?>

<div class="container my-4">
    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-heart me-2"></i> <?= $character['name'] ?>'s Relationships
                    </h5>
                </div>
                <div class="card-body">
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <!-- Relationship Navigation Tabs -->
                    <ul class="nav nav-tabs" id="relationshipTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="friendships-tab" data-bs-toggle="tab" data-bs-target="#friendships" type="button" role="tab">
                                <i class="fas fa-user-friends me-1"></i> Friendships
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="romance-tab" data-bs-toggle="tab" data-bs-target="#romance" type="button" role="tab">
                                <i class="fas fa-heart me-1"></i> Romance
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="family-tab" data-bs-toggle="tab" data-bs-target="#family" type="button" role="tab">
                                <i class="fas fa-home me-1"></i> Family
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button" role="tab">
                                <i class="fas fa-users me-1"></i> Social
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="requests-tab" data-bs-toggle="tab" data-bs-target="#requests" type="button" role="tab">
                                <i class="fas fa-envelope me-1"></i> Requests
                                <?php if (count($pendingRequests) > 0): ?>
                                    <span class="badge bg-danger"><?= count($pendingRequests) ?></span>
                                <?php endif; ?>
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content p-3 border border-top-0 rounded-bottom" id="relationshipTabContent">
                        <!-- Friendships Tab -->
                        <div class="tab-pane fade show active" id="friendships" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Your Friends</h5>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newFriendModal">
                                    <i class="fas fa-plus me-1"></i> Add Friend
                                </button>
                            </div>
                            
                            <?php if (empty($friendships)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> You haven't made any friends yet. Explore the world and meet new people!
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($friendships as $friend): ?>
                                        <div class="col-md-4 mb-4">
                                            <div class="card h-100">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <h5 class="mb-0"><?= $friend['target_name'] ?></h5>
                                                    <span class="badge bg-primary">Friend</span>
                                                </div>
                                                <div class="card-body">
                                                    <div class="text-center mb-3">
                                                        <img src="assets/images/portraits/<?= $friend['target_portrait'] ?: 'default.png' ?>" 
                                                             class="img-fluid rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Friendship Level</label>
                                                        <div class="progress">
                                                            <div class="progress-bar bg-success" role="progressbar" 
                                                                style="width: <?= $friend['relationship_level'] ?>%" 
                                                                aria-valuenow="<?= $friend['relationship_level'] ?>" 
                                                                aria-valuemin="0" aria-valuemax="100">
                                                                <?= $friend['relationship_level'] ?>%
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="text-muted small">
                                                            Friends since <?= date('M d, Y', strtotime($friend['started_at'])) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="card-footer d-flex justify-content-between">
                                                    <button class="btn btn-sm btn-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#interactionModal"
                                                            data-character-id="<?= $friend['target_character_id'] ?>"
                                                            data-character-name="<?= $friend['target_name'] ?>">
                                                        <i class="fas fa-comment me-1"></i> Interact
                                                    </button>
                                                    
                                                    <?php if ($friend['relationship_level'] >= 50): ?>
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-success dropdown-toggle" type="button" id="upgradeDropdown<?= $friend['id'] ?>" 
                                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                                Upgrade
                                                            </button>
                                                            <ul class="dropdown-menu" aria-labelledby="upgradeDropdown<?= $friend['id'] ?>">
                                                                <?php if ($friend['relationship_level'] >= 70): ?>
                                                                    <li>
                                                                        <form method="post">
                                                                            <input type="hidden" name="action" value="upgrade_relationship">
                                                                            <input type="hidden" name="relationship_id" value="<?= $friend['id'] ?>">
                                                                            <input type="hidden" name="new_type" value="best_friend">
                                                                            <button type="submit" class="dropdown-item">Best Friend</button>
                                                                        </form>
                                                                    </li>
                                                                <?php endif; ?>
                                                                <li>
                                                                    <form method="post">
                                                                        <input type="hidden" name="action" value="upgrade_relationship">
                                                                        <input type="hidden" name="relationship_id" value="<?= $friend['id'] ?>">
                                                                        <input type="hidden" name="new_type" value="dating">
                                                                        <button type="submit" class="dropdown-item">Start Dating</button>
                                                                    </form>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <form method="post" onsubmit="return confirm('Are you sure you want to end this friendship?');">
                                                        <input type="hidden" name="action" value="end_relationship">
                                                        <input type="hidden" name="relationship_id" value="<?= $friend['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-times me-1"></i> End
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Romance Tab -->
                        <div class="tab-pane fade" id="romance" role="tabpanel">
                            <h5>Romantic Relationships</h5>
                            
                            <?php if (empty($romanticRelationships) && empty($engagements) && empty($marriages)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> You are not in any romantic relationships yet. Build a close friendship and it may develop into something more!
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <!-- Dating Relationships -->
                                    <?php foreach ($romanticRelationships as $romance): ?>
                                        <div class="col-md-4 mb-4">
                                            <div class="card h-100 border-danger">
                                                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                                                    <h5 class="mb-0"><?= $romance['target_name'] ?></h5>
                                                    <span class="badge bg-light text-danger">Dating</span>
                                                </div>
                                                <div class="card-body">
                                                    <div class="text-center mb-3">
                                                        <img src="assets/images/portraits/<?= $romance['target_portrait'] ?: 'default.png' ?>" 
                                                             class="img-fluid rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Relationship Strength</label>
                                                        <div class="progress">
                                                            <div class="progress-bar bg-danger" role="progressbar" 
                                                                style="width: <?= $romance['relationship_level'] ?>%" 
                                                                aria-valuenow="<?= $romance['relationship_level'] ?>" 
                                                                aria-valuemin="0" aria-valuemax="100">
                                                                <?= $romance['relationship_level'] ?>%
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="text-muted small">
                                                            Dating since <?= date('M d, Y', strtotime($romance['started_at'])) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="card-footer d-flex justify-content-between">
                                                    <button class="btn btn-sm btn-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#interactionModal"
                                                            data-character-id="<?= $romance['target_character_id'] ?>"
                                                            data-character-name="<?= $romance['target_name'] ?>">
                                                        <i class="fas fa-comment me-1"></i> Interact
                                                    </button>
                                                    
                                                    <?php if ($romance['relationship_level'] >= 80): ?>
                                                        <form method="post">
                                                            <input type="hidden" name="action" value="upgrade_relationship">
                                                            <input type="hidden" name="relationship_id" value="<?= $romance['id'] ?>">
                                                            <input type="hidden" name="new_type" value="engaged">
                                                            <button type="submit" class="btn btn-sm btn-danger">
                                                                <i class="fas fa-ring me-1"></i> Propose
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <form method="post" onsubmit="return confirm('Are you sure you want to break up?');">
                                                        <input type="hidden" name="action" value="end_relationship">
                                                        <input type="hidden" name="relationship_id" value="<?= $romance['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-heart-broken me-1"></i> Break Up
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <!-- Engagements -->
                                    <?php foreach ($engagements as $engagement): ?>
                                        <div class="col-md-4 mb-4">
                                            <div class="card h-100 border-danger">
                                                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                                                    <h5 class="mb-0"><?= $engagement['target_name'] ?></h5>
                                                    <span class="badge bg-light text-danger">Engaged</span>
                                                </div>
                                                <div class="card-body">
                                                    <div class="text-center mb-3">
                                                        <img src="assets/images/portraits/<?= $engagement['target_portrait'] ?: 'default.png' ?>" 
                                                             class="img-fluid rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Relationship Strength</label>
                                                        <div class="progress">
                                                            <div class="progress-bar bg-danger" role="progressbar" 
                                                                style="width: <?= $engagement['relationship_level'] ?>%" 
                                                                aria-valuenow="<?= $engagement['relationship_level'] ?>" 
                                                                aria-valuemin="0" aria-valuemax="100">
                                                                <?= $engagement['relationship_level'] ?>%
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="text-muted small">
                                                            Engaged since <?= date('M d, Y', strtotime($engagement['started_at'])) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="card-footer d-flex justify-content-between">
                                                    <button class="btn btn-sm btn-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#interactionModal"
                                                            data-character-id="<?= $engagement['target_character_id'] ?>"
                                                            data-character-name="<?= $engagement['target_name'] ?>">
                                                        <i class="fas fa-comment me-1"></i> Interact
                                                    </button>
                                                    
                                                    <?php if ($engagement['relationship_level'] >= 90): ?>
                                                        <button class="btn btn-sm btn-danger" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#weddingModal"
                                                                data-partner-id="<?= $engagement['target_character_id'] ?>"
                                                                data-partner-name="<?= $engagement['target_name'] ?>">
                                                            <i class="fas fa-church me-1"></i> Plan Wedding
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <form method="post" onsubmit="return confirm('Are you sure you want to call off the engagement?');">
                                                        <input type="hidden" name="action" value="end_relationship">
                                                        <input type="hidden" name="relationship_id" value="<?= $engagement['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-heart-broken me-1"></i> Call Off
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <!-- Marriages -->
                                    <?php foreach ($marriages as $marriage): ?>
                                        <div class="col-md-4 mb-4">
                                            <div class="card h-100 border-danger">
                                                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                                                    <h5 class="mb-0"><?= $marriage['target_name'] ?></h5>
                                                    <span class="badge bg-light text-danger">Married</span>
                                                </div>
                                                <div class="card-body">
                                                    <div class="text-center mb-3">
                                                        <img src="assets/images/portraits/<?= $marriage['target_portrait'] ?: 'default.png' ?>" 
                                                             class="img-fluid rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                                                        <div class="mt-2">
                                                            <i class="fas fa-ring text-warning"></i> Spouse
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Marriage Bond</label>
                                                        <div class="progress">
                                                            <div class="progress-bar bg-danger" role="progressbar" 
                                                                style="width: <?= $marriage['relationship_level'] ?>%" 
                                                                aria-valuenow="<?= $marriage['relationship_level'] ?>" 
                                                                aria-valuemin="0" aria-valuemax="100">
                                                                <?= $marriage['relationship_level'] ?>%
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="text-muted small">
                                                            Married since <?= date('M d, Y', strtotime($marriage['started_at'])) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="card-footer d-flex justify-content-between">
                                                    <button class="btn btn-sm btn-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#interactionModal"
                                                            data-character-id="<?= $marriage['target_character_id'] ?>"
                                                            data-character-name="<?= $marriage['target_name'] ?>">
                                                        <i class="fas fa-comment me-1"></i> Interact
                                                    </button>
                                                    
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-danger dropdown-toggle" type="button" id="marriageOptionsDropdown" 
                                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                            Options
                                                        </button>
                                                        <ul class="dropdown-menu" aria-labelledby="marriageOptionsDropdown">
                                                            <li>
                                                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#createChildModal"
                                                                        data-partner-id="<?= $marriage['target_character_id'] ?>"
                                                                        data-partner-name="<?= $marriage['target_name'] ?>">
                                                                    <i class="fas fa-baby me-1"></i> Have Child
                                                                </button>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <form method="post" onsubmit="return confirm('Are you sure you want to divorce? This cannot be undone.');">
                                                                    <input type="hidden" name="action" value="end_relationship">
                                                                    <input type="hidden" name="relationship_id" value="<?= $marriage['id'] ?>">
                                                                    <button type="submit" class="dropdown-item text-danger">
                                                                        <i class="fas fa-heart-broken me-1"></i> Divorce
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Family Tab -->
                        <div class="tab-pane fade" id="family" role="tabpanel">
                            <h5>Your Family</h5>
                            
                            <?php if (empty($family)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> You don't have any family members yet. Get married to start a family!
                                </div>
                            <?php else: ?>
                                <!-- Spouse Section -->
                                <?php if (isset($family['spouse'])): ?>
                                    <div class="card mb-4">
                                        <div class="card-header bg-info text-white">
                                            <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i> Spouse</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <img src="assets/images/portraits/<?= $family['spouse']['target_portrait'] ?: 'default.png' ?>" 
                                                     class="img-fluid rounded-circle me-3" style="width: 80px; height: 80px; object-fit: cover;">
                                                <div>
                                                    <h5><?= $family['spouse']['target_name'] ?></h5>
                                                    <p class="mb-1">Married since <?= date('M d, Y', strtotime($family['spouse']['started_at'])) ?></p>
                                                    <button class="btn btn-sm btn-primary mt-2" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#interactionModal"
                                                            data-character-id="<?= $family['spouse']['target_character_id'] ?>"
                                                            data-character-name="<?= $family['spouse']['target_name'] ?>">
                                                        <i class="fas fa-comment me-1"></i> Interact
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Children Section -->
                                <?php if (isset($family['children']) && !empty($family['children'])): ?>
                                    <div class="card mb-4">
                                        <div class="card-header bg-warning text-dark">
                                            <h5 class="mb-0"><i class="fas fa-baby me-2"></i> Children</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <?php foreach ($family['children'] as $child): ?>
                                                    <div class="col-md-6 mb-3">
                                                        <div class="card h-100">
                                                            <div class="card-body">
                                                                <div class="d-flex align-items-center mb-3">
                                                                    <img src="assets/images/portraits/<?= $child['portrait'] ?: 'default.png' ?>" 
                                                                         class="img-fluid rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                                    <div>
                                                                        <h5 class="mb-0"><?= $child['name'] ?></h5>
                                                                        <span class="badge bg-<?= getGrowthStageColor($child['growth_stage']) ?>">
                                                                            <?= ucfirst($child['growth_stage']) ?>
                                                                        </span>
                                                                        <p class="mb-0 small">Age: <?= $child['age'] ?></p>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="dropdown">
                                                                    <button class="btn btn-sm btn-primary dropdown-toggle w-100" type="button" 
                                                                            id="parentingDropdown<?= $child['id'] ?>" 
                                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                                        Parenting Activities
                                                                    </button>
                                                                    <ul class="dropdown-menu" aria-labelledby="parentingDropdown<?= $child['id'] ?>">
                                                                        <li>
                                                                            <form method="post">
                                                                                <input type="hidden" name="action" value="parenting">
                                                                                <input type="hidden" name="child_id" value="<?= $child['child_character_id'] ?>">
                                                                                <input type="hidden" name="activity_type" value="play">
                                                                                <button type="submit" class="dropdown-item">
                                                                                    <i class="fas fa-gamepad me-1"></i> Play Together
                                                                                </button>
                                                                            </form>
                                                                        </li>
                                                                        <li>
                                                                            <form method="post">
                                                                                <input type="hidden" name="action" value="parenting">
                                                                                <input type="hidden" name="child_id" value="<?= $child['child_character_id'] ?>">
                                                                                <input type="hidden" name="activity_type" value="teach">
                                                                                <button type="submit" class="dropdown-item">
                                                                                    <i class="fas fa-book me-1"></i> Teach Something
                                                                                </button>
                                                                            </form>
                                                                        </li>
                                                                        <li>
                                                                            <form method="post">
                                                                                <input type="hidden" name="action" value="parenting">
                                                                                <input type="hidden" name="child_id" value="<?= $child['child_character_id'] ?>">
                                                                                <input type="hidden" name="activity_type" value="care">
                                                                                <button type="submit" class="dropdown-item">
                                                                                    <i class="fas fa-heart me-1"></i> Care For
                                                                                </button>
                                                                            </form>
                                                                        </li>
                                                                        <li>
                                                                            <form method="post">
                                                                                <input type="hidden" name="action" value="parenting">
                                                                                <input type="hidden" name="child_id" value="<?= $child['child_character_id'] ?>">
                                                                                <input type="hidden" name="activity_type" value="adventure">
                                                                                <button type="submit" class="dropdown-item">
                                                                                    <i class="fas fa-hiking me-1"></i> Go on Adventure
                                                                                </button>
                                                                            </form>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <div class="card-footer text-muted small">
                                                                Born: <?= date('M d, Y', strtotime($child['birth_date'])) ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Parents Section -->
                                <?php if (isset($family['parents']) && !empty($family['parents'])): ?>
                                    <div class="card mb-4">
                                        <div class="card-header bg-secondary text-white">
                                            <h5 class="mb-0"><i class="fas fa-users me-2"></i> Parents</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <?php foreach ($family['parents'] as $parent): ?>
                                                    <div class="col-md-6 mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <img src="assets/images/portraits/<?= $parent['portrait'] ?: 'default.png' ?>" 
                                                                 class="img-fluid rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                            <div>
                                                                <h5 class="mb-0"><?= $parent['name'] ?></h5>
                                                                <button class="btn btn-sm btn-primary mt-2" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#interactionModal"
                                                                        data-character-id="<?= ($parent['parent1_character_id'] == $activeCharacterId) ? $parent['parent2_character_id'] : $parent['parent1_character_id'] ?>"
                                                                        data-character-name="<?= $parent['name'] ?>">
                                                                    <i class="fas fa-comment me-1"></i> Interact
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Social Tab -->
                        <div class="tab-pane fade" id="social" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Characters Nearby</h5>
                                <span class="badge bg-secondary"><?= $character['location_id'] ? getLocationName($character['location_id']) : "Unknown Location" ?></span>
                            </div>
                            
                            <?php if (empty($potentialContacts)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> There's no one around to interact with. Try moving to a more populated area!
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($potentialContacts as $contact): ?>
                                        <div class="col-md-4 mb-4">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-center mb-3">
                                                        <img src="assets/images/portraits/<?= $contact['portrait'] ?: 'default.png' ?>" 
                                                             class="img-fluid rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                        <div>
                                                            <h5 class="mb-0"><?= $contact['name'] ?></h5>
                                                            <p class="mb-0 small">
                                                                Level <?= $contact['level'] ?> <?= ucfirst($contact['race']) ?> 
                                                                <span class="text-capitalize"><?= $contact['gender'] ?></span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="d-flex justify-content-between">
                                                        <button class="btn btn-sm btn-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#interactionModal"
                                                                data-character-id="<?= $contact['id'] ?>"
                                                                data-character-name="<?= $contact['name'] ?>">
                                                            <i class="fas fa-comment me-1"></i> Interact
                                                        </button>
                                                        
                                                        <form method="post">
                                                            <input type="hidden" name="action" value="send_request">
                                                            <input type="hidden" name="target_id" value="<?= $contact['id'] ?>">
                                                            <input type="hidden" name="relationship_type" value="friend">
                                                            <button type="submit" class="btn btn-sm btn-success">
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
                        
                        <!-- Requests Tab -->
                        <div class="tab-pane fade" id="requests" role="tabpanel">
                            <h5>Relationship Requests</h5>
                            
                            <?php if (empty($pendingRequests)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> You don't have any pending relationship requests.
                                </div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($pendingRequests as $request): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center">
                                                    <img src="assets/images/portraits/<?= $request['sender_portrait'] ?: 'default.png' ?>" 
                                                         class="img-fluid rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <div>
                                                        <h6 class="mb-0"><?= $request['sender_name'] ?></h6>
                                                        <p class="mb-0 small">
                                                            Wants to be your 
                                                            <span class="badge bg-primary"><?= getRelationshipTypeLabel($request['relationship_type']) ?></span>
                                                        </p>
                                                        <span class="text-muted small">
                                                            Sent <?= date('M d, Y', strtotime($request['started_at'])) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                
                                                <div>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="action" value="accept_request">
                                                        <input type="hidden" name="relationship_id" value="<?= $request['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            <i class="fas fa-check me-1"></i> Accept
                                                        </button>
                                                    </form>
                                                    
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="action" value="decline_request">
                                                        <input type="hidden" name="relationship_id" value="<?= $request['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-times me-1"></i> Decline
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Friend Modal -->
<div class="modal fade" id="newFriendModal" tabindex="-1" aria-labelledby="newFriendModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newFriendModalLabel">Add a New Friend</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="action" value="send_request">
                    <input type="hidden" name="relationship_type" value="friend">
                    
                    <div class="mb-3">
                        <label for="target_id" class="form-label">Select Character</label>
                        <select class="form-select" id="target_id" name="target_id" required>
                            <option value="">-- Select Character --</option>
                            <?php foreach ($potentialContacts as $contact): ?>
                                <option value="<?= $contact['id'] ?>"><?= $contact['name'] ?> (Level <?= $contact['level'] ?> <?= ucfirst($contact['race']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Send Friend Request</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Interaction Modal -->
<div class="modal fade" id="interactionModal" tabindex="-1" aria-labelledby="interactionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="interactionModalLabel">Interact with <span id="interactionTargetName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="action" value="social_interaction">
                    <input type="hidden" name="target_id" id="interactionTargetId">
                    
                    <div class="mb-3">
                        <label for="interaction_type" class="form-label">Choose Interaction</label>
                        <select class="form-select" id="interaction_type" name="interaction_type" required>
                            <option value="">-- Select Interaction --</option>
                            <optgroup label="Friendly">
                                <option value="Greet">Greet</option>
                                <option value="Small Talk">Small Talk</option>
                                <option value="Deep Conversation">Deep Conversation</option>
                                <option value="Tell Joke">Tell Joke</option>
                                <option value="Gift">Give a Gift</option>
                                <option value="Help with Task">Help with a Task</option>
                                <option value="Share Meal">Share a Meal</option>
                                <option value="Compliment">Compliment</option>
                            </optgroup>
                            <optgroup label="Romantic">
                                <option value="Flirt">Flirt</option>
                                <option value="Kiss">Kiss</option>
                                <option value="Date">Go on a Date</option>
                            </optgroup>
                            <optgroup label="Negative">
                                <option value="Insult">Insult</option>
                                <option value="Argue">Argue</option>
                            </optgroup>
                            <optgroup label="Recovery">
                                <option value="Apologize">Apologize</option>
                            </optgroup>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Interact</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Wedding Planning Modal -->
<div class="modal fade" id="weddingModal" tabindex="-1" aria-labelledby="weddingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="weddingModalLabel">Plan Your Wedding</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="action" value="plan_wedding">
                    <input type="hidden" name="partner_id" id="weddingPartnerId">
                    
                    <div class="mb-3">
                        <label class="form-label">Partner</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-heart"></i></span>
                            <input type="text" class="form-control" id="weddingPartnerName" disabled>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="location_id" class="form-label">Wedding Venue</label>
                        <select class="form-select" id="location_id" name="location_id" required>
                            <option value="">-- Select Venue --</option>
                            <!-- This would be filled with available venues from the database -->
                            <option value="1">Town Square</option>
                            <option value="2">Grand Cathedral</option>
                            <option value="3">Riverside Gardens</option>
                            <option value="4">Castle Ballroom</option>
                            <option value="5">Elven Glade</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="wedding_date" class="form-label">Wedding Date</label>
                        <input type="datetime-local" class="form-control" id="wedding_date" name="wedding_date" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_private" name="is_private">
                        <label class="form-check-label" for="is_private">Private Ceremony</label>
                    </div>
                    
                    <div class="mb-3">
                        <label for="max_attendees" class="form-label">Maximum Guests (0 for unlimited)</label>
                        <input type="number" class="form-control" id="max_attendees" name="max_attendees" min="0" value="0">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Plan Wedding</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Create Child Modal -->
<div class="modal fade" id="createChildModal" tabindex="-1" aria-labelledby="createChildModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createChildModalLabel">Have a Child</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="action" value="have_child">
                    <input type="hidden" name="partner_id" id="childPartnerId">
                    
                    <div class="mb-3">
                        <label class="form-label">With</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-heart"></i></span>
                            <input type="text" class="form-control" id="childPartnerName" disabled>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="child_name" class="form-label">Child's Name</label>
                        <input type="text" class="form-control" id="child_name" name="child_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="child_gender" class="form-label">Gender</label>
                        <select class="form-select" id="child_gender" name="child_gender" required>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Create Child</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize interaction modal
document.addEventListener('DOMContentLoaded', function () {
    const interactionModal = document.getElementById('interactionModal');
    if (interactionModal) {
        interactionModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const characterId = button.getAttribute('data-character-id');
            const characterName = button.getAttribute('data-character-name');
            
            document.getElementById('interactionTargetId').value = characterId;
            document.getElementById('interactionTargetName').textContent = characterName;
        });
    }
    
    const weddingModal = document.getElementById('weddingModal');
    if (weddingModal) {
        weddingModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const partnerId = button.getAttribute('data-partner-id');
            const partnerName = button.getAttribute('data-partner-name');
            
            document.getElementById('weddingPartnerId').value = partnerId;
            document.getElementById('weddingPartnerName').value = partnerName;
            
            // Set default date to one week from now
            const defaultDate = new Date();
            defaultDate.setDate(defaultDate.getDate() + 7);
            document.getElementById('wedding_date').value = defaultDate.toISOString().slice(0, 16);
        });
    }
    
    const createChildModal = document.getElementById('createChildModal');
    if (createChildModal) {
        createChildModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const partnerId = button.getAttribute('data-partner-id');
            const partnerName = button.getAttribute('data-partner-name');
            
            document.getElementById('childPartnerId').value = partnerId;
            document.getElementById('childPartnerName').value = partnerName;
        });
    }
});
</script>

<?php
/**
 * Helper function to get color class for growth stage
 */
function getGrowthStageColor($stage) {
    switch ($stage) {
        case 'infant':
            return 'info';
        case 'toddler':
            return 'primary';
        case 'child':
            return 'success';
        case 'teen':
            return 'warning';
        case 'adult':
            return 'secondary';
        default:
            return 'light';
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

/**
 * Helper function to get location name
 */
function getLocationName($locationId) {
    global $db;
    
    $sql = "SELECT name FROM locations WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $locationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['name'];
    }
    
    return "Unknown Location";
}
?>

<?php include 'inc/footer.php'; ?> 