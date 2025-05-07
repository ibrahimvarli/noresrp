<?php
/**
 * Basic Needs Page
 * Displays and manages character's hunger, thirst, sleep, hygiene and happiness
 */

// Include necessary files
require_once 'includes/life_system.php';

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
        
        // Eat food
        if ($_POST['action'] == 'eat' && isset($_POST['item_id'])) {
            $itemId = (int)$_POST['item_id'];
            $result = eatFood($activeCharacterId, $itemId);
            
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
        
        // Drink
        else if ($_POST['action'] == 'drink' && isset($_POST['item_id'])) {
            $itemId = (int)$_POST['item_id'];
            $result = drinkLiquid($activeCharacterId, $itemId);
            
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
        
        // Sleep
        else if ($_POST['action'] == 'sleep' && isset($_POST['hours'])) {
            $hours = (int)$_POST['hours'];
            $result = characterSleep($activeCharacterId, $hours);
            
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
        
        // Hygiene actions
        else if ($_POST['action'] == 'hygiene' && isset($_POST['hygiene_type'])) {
            $hygieneType = $_POST['hygiene_type'];
            $result = performHygiene($activeCharacterId, $hygieneType);
            
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Get character life stats
$lifeStats = getCharacterLifeStats($activeCharacterId);

// Get food items in inventory
$sql = "SELECT i.id as inventory_id, i.item_id, i.quantity, it.name, it.description, it.image, it.nutrition_value 
        FROM inventory i 
        JOIN items it ON i.item_id = it.id 
        WHERE i.character_id = ? AND it.nutrition_value > 0 AND i.quantity > 0
        ORDER BY it.nutrition_value DESC";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $activeCharacterId);
$stmt->execute();
$foodItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get drink items in inventory
$sql = "SELECT i.id as inventory_id, i.item_id, i.quantity, it.name, it.description, it.image, it.hydration_value 
        FROM inventory i 
        JOIN items it ON i.item_id = it.id 
        WHERE i.character_id = ? AND it.hydration_value > 0 AND i.quantity > 0
        ORDER BY it.hydration_value DESC";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $activeCharacterId);
$stmt->execute();
$drinkItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get hygiene items in inventory
$sql = "SELECT i.id as inventory_id, i.item_id, i.quantity, it.name, it.description, it.image 
        FROM inventory i 
        JOIN items it ON i.item_id = it.id 
        WHERE i.character_id = ? AND it.subtype = 'hygiene' AND i.quantity > 0";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $activeCharacterId);
$stmt->execute();
$hygieneItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Page title
$pageTitle = "Basic Needs";

// Include header
include 'inc/header.php';
?>

<div class="container my-4">
    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-heartbeat me-2"></i> <?= $character['name'] ?>'s Basic Needs
                    </h5>
                </div>
                <div class="card-body">
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body text-center p-3">
                                    <h5 class="card-title text-danger">
                                        <i class="fas fa-utensils me-2"></i>Hunger
                                    </h5>
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-<?= $lifeStats['stats']['hunger'] > 70 ? 'danger' : ($lifeStats['stats']['hunger'] > 40 ? 'warning' : 'success') ?>" 
                                             role="progressbar" 
                                             style="width: <?= $lifeStats['stats']['hunger'] ?>%" 
                                             aria-valuenow="<?= $lifeStats['stats']['hunger'] ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?= $lifeStats['stats']['hunger'] ?>%
                                        </div>
                                    </div>
                                    <p class="small text-muted mb-0"><?= $lifeStats['status']['hunger']['description'] ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body text-center p-3">
                                    <h5 class="card-title text-primary">
                                        <i class="fas fa-tint me-2"></i>Thirst
                                    </h5>
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-<?= $lifeStats['stats']['thirst'] > 70 ? 'danger' : ($lifeStats['stats']['thirst'] > 40 ? 'warning' : 'success') ?>" 
                                             role="progressbar" 
                                             style="width: <?= $lifeStats['stats']['thirst'] ?>%" 
                                             aria-valuenow="<?= $lifeStats['stats']['thirst'] ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?= $lifeStats['stats']['thirst'] ?>%
                                        </div>
                                    </div>
                                    <p class="small text-muted mb-0"><?= $lifeStats['status']['thirst']['description'] ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body text-center p-3">
                                    <h5 class="card-title text-secondary">
                                        <i class="fas fa-bed me-2"></i>Fatigue
                                    </h5>
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-<?= $lifeStats['stats']['fatigue'] > 70 ? 'danger' : ($lifeStats['stats']['fatigue'] > 40 ? 'warning' : 'success') ?>" 
                                             role="progressbar" 
                                             style="width: <?= $lifeStats['stats']['fatigue'] ?>%" 
                                             aria-valuenow="<?= $lifeStats['stats']['fatigue'] ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?= $lifeStats['stats']['fatigue'] ?>%
                                        </div>
                                    </div>
                                    <p class="small text-muted mb-0"><?= $lifeStats['status']['fatigue']['description'] ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body text-center p-3">
                                    <h5 class="card-title text-info">
                                        <i class="fas fa-shower me-2"></i>Hygiene
                                    </h5>
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-<?= $lifeStats['stats']['hygiene'] < 30 ? 'danger' : ($lifeStats['stats']['hygiene'] < 60 ? 'warning' : 'success') ?>" 
                                             role="progressbar" 
                                             style="width: <?= $lifeStats['stats']['hygiene'] ?>%" 
                                             aria-valuenow="<?= $lifeStats['stats']['hygiene'] ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?= $lifeStats['stats']['hygiene'] ?>%
                                        </div>
                                    </div>
                                    <p class="small text-muted mb-0"><?= $lifeStats['status']['hygiene']['description'] ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body text-center p-3">
                                    <h5 class="card-title text-success">
                                        <i class="fas fa-smile me-2"></i>Happiness
                                    </h5>
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-<?= $lifeStats['stats']['happiness'] < 30 ? 'danger' : ($lifeStats['stats']['happiness'] < 60 ? 'warning' : 'success') ?>" 
                                             role="progressbar" 
                                             style="width: <?= $lifeStats['stats']['happiness'] ?>%" 
                                             aria-valuenow="<?= $lifeStats['stats']['happiness'] ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?= $lifeStats['stats']['happiness'] ?>%
                                        </div>
                                    </div>
                                    <p class="mb-0">Current Mood: <strong><?= ucfirst($lifeStats['status']['mood']) ?></strong></p>
                                    <p class="small text-muted mb-0"><?= $lifeStats['status']['happiness']['description'] ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body text-center p-3">
                                    <h5 class="card-title text-danger">
                                        <i class="fas fa-heartbeat me-2"></i>Health
                                    </h5>
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-<?= ($lifeStats['stats']['health'] / $lifeStats['stats']['max_health']) * 100 < 30 ? 'danger' : (($lifeStats['stats']['health'] / $lifeStats['stats']['max_health']) * 100 < 60 ? 'warning' : 'success') ?>" 
                                             role="progressbar" 
                                             style="width: <?= ($lifeStats['stats']['health'] / $lifeStats['stats']['max_health']) * 100 ?>%" 
                                             aria-valuenow="<?= $lifeStats['stats']['health'] ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="<?= $lifeStats['stats']['max_health'] ?>">
                                            <?= $lifeStats['stats']['health'] ?>/<?= $lifeStats['stats']['max_health'] ?>
                                        </div>
                                    </div>
                                    <p class="small text-muted mb-0"><?= $lifeStats['status']['health']['description'] ?></p>
                                    
                                    <?php if (!empty($lifeStats['diseases'])): ?>
                                    <div class="mt-2">
                                        <p class="mb-1"><strong>Active Conditions:</strong></p>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach($lifeStats['diseases'] as $disease): ?>
                                            <li class="list-group-item py-1 px-2">
                                                <i class="fas fa-virus text-danger me-1"></i> 
                                                <?= $disease['disease_name'] ?> 
                                                <span class="badge bg-warning">Severity: <?= $disease['severity'] ?></span>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions Section -->
                    <div class="row">
                        <div class="col-md-12">
                            <ul class="nav nav-tabs" id="needsTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="food-tab" data-bs-toggle="tab" data-bs-target="#food" type="button" role="tab" aria-controls="food" aria-selected="true">
                                        <i class="fas fa-utensils me-1"></i> Food
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="drink-tab" data-bs-toggle="tab" data-bs-target="#drink" type="button" role="tab" aria-controls="drink" aria-selected="false">
                                        <i class="fas fa-tint me-1"></i> Drink
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="sleep-tab" data-bs-toggle="tab" data-bs-target="#sleep" type="button" role="tab" aria-controls="sleep" aria-selected="false">
                                        <i class="fas fa-bed me-1"></i> Sleep
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="hygiene-tab" data-bs-toggle="tab" data-bs-target="#hygiene" type="button" role="tab" aria-controls="hygiene" aria-selected="false">
                                        <i class="fas fa-shower me-1"></i> Hygiene
                                    </button>
                                </li>
                            </ul>
                            
                            <div class="tab-content p-3 border border-top-0 rounded-bottom" id="needsTabContent">
                                <!-- Food Tab -->
                                <div class="tab-pane fade show active" id="food" role="tabpanel" aria-labelledby="food-tab">
                                    <h5>Available Food</h5>
                                    <?php if (empty($foodItems)): ?>
                                        <p class="text-muted">You don't have any food items in your inventory.</p>
                                    <?php else: ?>
                                        <div class="row">
                                            <?php foreach ($foodItems as $item): ?>
                                            <div class="col-md-3 mb-3">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <h6 class="card-title"><?= $item['name'] ?></h6>
                                                        <p class="card-text small"><?= $item['description'] ?></p>
                                                        <div class="text-muted small mb-2">
                                                            <i class="fas fa-drumstick-bite me-1"></i> Nutrition: <?= $item['nutrition_value'] ?>
                                                        </div>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="badge bg-secondary">Qty: <?= $item['quantity'] ?></span>
                                                            <form method="post">
                                                                <input type="hidden" name="action" value="eat">
                                                                <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-success">Eat</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Drink Tab -->
                                <div class="tab-pane fade" id="drink" role="tabpanel" aria-labelledby="drink-tab">
                                    <h5>Available Drinks</h5>
                                    <?php if (empty($drinkItems)): ?>
                                        <p class="text-muted">You don't have any drink items in your inventory.</p>
                                    <?php else: ?>
                                        <div class="row">
                                            <?php foreach ($drinkItems as $item): ?>
                                            <div class="col-md-3 mb-3">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <h6 class="card-title"><?= $item['name'] ?></h6>
                                                        <p class="card-text small"><?= $item['description'] ?></p>
                                                        <div class="text-muted small mb-2">
                                                            <i class="fas fa-tint me-1"></i> Hydration: <?= $item['hydration_value'] ?>
                                                        </div>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="badge bg-secondary">Qty: <?= $item['quantity'] ?></span>
                                                            <form method="post">
                                                                <input type="hidden" name="action" value="drink">
                                                                <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-primary">Drink</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Sleep Tab -->
                                <div class="tab-pane fade" id="sleep" role="tabpanel" aria-labelledby="sleep-tab">
                                    <h5>Rest and Sleep</h5>
                                    <p>Sleep to restore energy and recover from fatigue.</p>
                                    
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <form method="post" class="row align-items-end">
                                                <div class="col-md-4">
                                                    <label for="sleepHours" class="form-label">Hours to Sleep</label>
                                                    <select class="form-select" id="sleepHours" name="hours">
                                                        <option value="1">1 hour (Quick nap)</option>
                                                        <option value="3">3 hours (Short rest)</option>
                                                        <option value="6">6 hours (Moderate sleep)</option>
                                                        <option value="8" selected>8 hours (Full night's sleep)</option>
                                                        <option value="10">10 hours (Long rest)</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <p class="text-muted mb-0">
                                                        <strong>Effects:</strong> Reduces fatigue, improves health, advances game time
                                                    </p>
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="hidden" name="action" value="sleep">
                                                    <button type="submit" class="btn btn-primary">Sleep</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i> Sleeping advances game time and may trigger events or status changes.
                                    </div>
                                </div>
                                
                                <!-- Hygiene Tab -->
                                <div class="tab-pane fade" id="hygiene" role="tabpanel" aria-labelledby="hygiene-tab">
                                    <h5>Hygiene and Personal Care</h5>
                                    <p>Maintain cleanliness to avoid diseases and maintain social standing.</p>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h6 class="card-title">Basic Washing</h6>
                                                    <p class="card-text small">Clean yourself with water.</p>
                                                    <div class="text-muted small mb-2">
                                                        <i class="fas fa-plus-circle me-1"></i> Hygiene: +15
                                                    </div>
                                                    <form method="post">
                                                        <input type="hidden" name="action" value="hygiene">
                                                        <input type="hidden" name="hygiene_type" value="basic_wash">
                                                        <button type="submit" class="btn btn-sm btn-primary">Wash</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h6 class="card-title">Take a Bath</h6>
                                                    <p class="card-text small">Thoroughly clean yourself in a bath.</p>
                                                    <div class="text-muted small mb-2">
                                                        <i class="fas fa-plus-circle me-1"></i> Hygiene: +35
                                                        <br>
                                                        <i class="fas fa-plus-circle me-1"></i> Happiness: +5
                                                    </div>
                                                    <form method="post">
                                                        <input type="hidden" name="action" value="hygiene">
                                                        <input type="hidden" name="hygiene_type" value="bath">
                                                        <button type="submit" class="btn btn-sm btn-primary">Take Bath</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($hygieneItems)): ?>
                                        <div class="col-md-4">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h6 class="card-title">Use Hygiene Item</h6>
                                                    <form method="post">
                                                        <input type="hidden" name="action" value="hygiene">
                                                        <select class="form-select form-select-sm mb-2" name="hygiene_item">
                                                            <?php foreach ($hygieneItems as $item): ?>
                                                            <option value="<?= $item['item_id'] ?>"><?= $item['name'] ?> (<?= $item['quantity'] ?>)</option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <input type="hidden" name="hygiene_type" value="use_item">
                                                        <button type="submit" class="btn btn-sm btn-primary">Use Item</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?> 