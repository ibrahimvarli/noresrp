<?php
// Redirect if not logged in
if (!isLoggedIn()) {
    redirect("index.php?page=login");
}

// Include life system functions
require_once("includes/life_system.php");

// Get user's active character
$userId = (int)$_SESSION['user_id'];
$activeCharacter = getActiveCharacter($userId);

// Redirect if no active character
if (!$activeCharacter) {
    $_SESSION['flash_message'] = "Please create or select a character first.";
    $_SESSION['flash_type'] = "warning";
    redirect("index.php?page=characters");
}

// Initialize character life stats if needed
initializeLifeStats($activeCharacter['id']);

// Process actions
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        
        // Eat food
        if ($_POST['action'] == 'eat' && isset($_POST['item_id'])) {
            $itemId = (int)$_POST['item_id'];
            $result = eatFood($activeCharacter['id'], $itemId);
            
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
        
        // Sleep
        else if ($_POST['action'] == 'sleep' && isset($_POST['hours'])) {
            $hours = (int)$_POST['hours'];
            $result = characterSleep($activeCharacter['id'], $hours);
            
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
        
        // Hygiene actions
        else if ($_POST['action'] == 'hygiene' && isset($_POST['hygiene_type'])) {
            $hygieneType = $_POST['hygiene_type'];
            $result = performHygiene($activeCharacter['id'], $hygieneType);
            
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
        
        // Use medicine
        else if ($_POST['action'] == 'medicine' && isset($_POST['item_id'])) {
            $itemId = (int)$_POST['item_id'];
            $result = useMedicine($activeCharacter['id'], $itemId);
            
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Get updated character life stats
$lifeStats = getCharacterLifeStats($activeCharacter['id']);

// Get food items in inventory
global $db;
$sql = "SELECT i.id as inventory_id, i.item_id, i.quantity, it.name, it.description, it.image, it.nutrition_value 
        FROM inventory i 
        JOIN items it ON i.item_id = it.id 
        WHERE i.character_id = ? AND it.nutrition_value > 0 AND i.quantity > 0
        ORDER BY it.nutrition_value DESC";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $activeCharacter['id']);
$stmt->execute();
$foodItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get medicine items in inventory
$sql = "SELECT i.id as inventory_id, i.item_id, i.quantity, it.name, it.description, it.image, it.treatment_type, it.treatment_power
        FROM inventory i 
        JOIN items it ON i.item_id = it.id 
        WHERE i.character_id = ? AND it.treatment_type IS NOT NULL AND i.quantity > 0
        ORDER BY it.treatment_power DESC";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $activeCharacter['id']);
$stmt->execute();
$medicineItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Update character stats (simulating time passing)
// This would normally be done by a cron job or when the character performs actions
$timeSinceLastUpdate = 0;
if (isset($lifeStats['stats']['last_update'])) {
    $lastUpdate = strtotime($lifeStats['stats']['last_update']);
    $currentTime = time();
    $timeSinceLastUpdate = max(0, ($currentTime - $lastUpdate) / 3600); // Hours
}

if ($timeSinceLastUpdate > 0.25) { // Only update if more than 15 minutes have passed
    updateAllLifeStats($activeCharacter['id'], $timeSinceLastUpdate);
    // Refresh stats after update
    $lifeStats = getCharacterLifeStats($activeCharacter['id']);
}
?>

<div class="life-system-container">
    <div class="page-header">
        <h1><?php echo $activeCharacter['name']; ?>'s Life Status</h1>
        <p>Manage your character's nutrition, rest, and hygiene</p>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <div class="life-system-wrapper">
        <!-- Life Stats Overview -->
        <div class="life-stats-overview">
            <div class="stat-card hunger">
                <div class="stat-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Hunger</div>
                    <div class="progress">
                        <div class="progress-bar 
                                <?php echo ($lifeStats['status']['hunger']['level'] == 'starving' || $lifeStats['status']['hunger']['level'] == 'hungry') ? 'bg-danger' : 
                                      ($lifeStats['status']['hunger']['level'] == 'peckish' ? 'bg-warning' : 'bg-success'); ?>" 
                             style="width: <?php echo $lifeStats['stats']['hunger']; ?>%"></div>
                    </div>
                    <div class="stat-description"><?php echo $lifeStats['status']['hunger']['description']; ?></div>
                </div>
            </div>
            
            <div class="stat-card fatigue">
                <div class="stat-icon">
                    <i class="fas fa-bed"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Fatigue</div>
                    <div class="progress">
                        <div class="progress-bar 
                                <?php echo ($lifeStats['status']['fatigue']['level'] == 'exhausted' || $lifeStats['status']['fatigue']['level'] == 'tired') ? 'bg-danger' : 
                                      ($lifeStats['status']['fatigue']['level'] == 'normal' ? 'bg-warning' : 'bg-success'); ?>" 
                             style="width: <?php echo $lifeStats['stats']['fatigue']; ?>%"></div>
                    </div>
                    <div class="stat-description"><?php echo $lifeStats['status']['fatigue']['description']; ?></div>
                </div>
            </div>
            
            <div class="stat-card hygiene">
                <div class="stat-icon">
                    <i class="fas fa-shower"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Hygiene</div>
                    <div class="progress">
                        <div class="progress-bar 
                                <?php echo ($lifeStats['status']['hygiene']['level'] == 'filthy' || $lifeStats['status']['hygiene']['level'] == 'dirty') ? 'bg-danger' : 
                                      ($lifeStats['status']['hygiene']['level'] == 'normal' ? 'bg-warning' : 'bg-success'); ?>" 
                             style="width: <?php echo $lifeStats['stats']['hygiene']; ?>%"></div>
                    </div>
                    <div class="stat-description"><?php echo $lifeStats['status']['hygiene']['description']; ?></div>
                </div>
            </div>
            
            <div class="stat-card health">
                <div class="stat-icon">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Health</div>
                    <div class="progress">
                        <div class="progress-bar 
                                <?php echo ($lifeStats['status']['health']['level'] == 'critical' || $lifeStats['status']['health']['level'] == 'wounded') ? 'bg-danger' : 
                                      ($lifeStats['status']['health']['level'] == 'injured' ? 'bg-warning' : 'bg-success'); ?>" 
                             style="width: <?php echo ($lifeStats['stats']['health'] / $lifeStats['stats']['max_health']) * 100; ?>%"></div>
                    </div>
                    <div class="stat-description"><?php echo $lifeStats['status']['health']['description']; ?></div>
                </div>
            </div>
            
            <?php if (!empty($lifeStats['diseases'])): ?>
                <div class="diseases-section">
                    <h3><i class="fas fa-disease"></i> Active Conditions</h3>
                    <ul class="disease-list">
                        <?php foreach ($lifeStats['diseases'] as $disease): ?>
                            <li>
                                <span class="disease-name"><?php echo $disease['disease_name']; ?></span>
                                <span class="disease-desc"><?php echo $disease['description']; ?></span>
                                <span class="disease-severity">Severity: <?php echo $disease['severity']; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Actions Section -->
        <div class="life-actions-section">
            <div class="action-tabs">
                <ul class="nav nav-tabs" id="lifeActionTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="food-tab" data-toggle="tab" href="#food" role="tab">
                            <i class="fas fa-utensils"></i> Food
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="sleep-tab" data-toggle="tab" href="#sleep" role="tab">
                            <i class="fas fa-bed"></i> Sleep
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="hygiene-tab" data-toggle="tab" href="#hygiene" role="tab">
                            <i class="fas fa-shower"></i> Hygiene
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="medicine-tab" data-toggle="tab" href="#medicine" role="tab">
                            <i class="fas fa-medkit"></i> Medicine
                        </a>
                    </li>
                </ul>
                
                <div class="tab-content" id="lifeActionTabContent">
                    <!-- Food Tab -->
                    <div class="tab-pane fade show active" id="food" role="tabpanel">
                        <h3>Available Food</h3>
                        
                        <?php if (empty($foodItems)): ?>
                            <p class="no-items">You don't have any food in your inventory. Visit a merchant to purchase some.</p>
                        <?php else: ?>
                            <div class="items-grid">
                                <?php foreach ($foodItems as $item): ?>
                                    <div class="item-card">
                                        <div class="item-image">
                                            <img src="assets/images/items/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                                        </div>
                                        <div class="item-info">
                                            <h4><?php echo $item['name']; ?></h4>
                                            <p><?php echo $item['description']; ?></p>
                                            <div class="item-stats">
                                                <span class="nutrition-value">Nutrition: <?php echo $item['nutrition_value']; ?></span>
                                                <span class="quantity">Quantity: <?php echo $item['quantity']; ?></span>
                                            </div>
                                            <form action="index.php?page=life" method="post">
                                                <input type="hidden" name="action" value="eat">
                                                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">Eat</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Sleep Tab -->
                    <div class="tab-pane fade" id="sleep" role="tabpanel">
                        <h3>Rest</h3>
                        <p>Sleep to recover from fatigue and regenerate health.</p>
                        
                        <div class="sleep-options">
                            <form action="index.php?page=life" method="post">
                                <input type="hidden" name="action" value="sleep">
                                
                                <div class="form-group">
                                    <label for="sleepHours">Hours to Sleep:</label>
                                    <select class="form-control" id="sleepHours" name="hours">
                                        <option value="1">1 hour (quick nap)</option>
                                        <option value="3">3 hours (short rest)</option>
                                        <option value="6">6 hours (partial sleep)</option>
                                        <option value="8" selected>8 hours (full night's sleep)</option>
                                        <option value="12">12 hours (extended recovery)</option>
                                    </select>
                                </div>
                                
                                <div class="sleep-warning">
                                    <p><i class="fas fa-exclamation-triangle"></i> Warning: Time will pass in the game world while you sleep.</p>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Sleep</button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Hygiene Tab -->
                    <div class="tab-pane fade" id="hygiene" role="tabpanel">
                        <h3>Personal Care</h3>
                        <p>Maintain hygiene to prevent diseases and improve social interactions.</p>
                        
                        <div class="hygiene-options">
                            <form action="index.php?page=life" method="post">
                                <input type="hidden" name="action" value="hygiene">
                                
                                <div class="hygiene-cards">
                                    <div class="hygiene-card">
                                        <div class="hygiene-icon">
                                            <i class="fas fa-hands-wash"></i>
                                        </div>
                                        <div class="hygiene-info">
                                            <h4>Quick Wash</h4>
                                            <p>Wash your face and hands. Quick but less effective.</p>
                                            <p><strong>Improves hygiene by 20%</strong></p>
                                            <button type="submit" name="hygiene_type" value="quick_wash" class="btn btn-primary btn-sm">Perform</button>
                                        </div>
                                    </div>
                                    
                                    <div class="hygiene-card">
                                        <div class="hygiene-icon">
                                            <i class="fas fa-bath"></i>
                                        </div>
                                        <div class="hygiene-info">
                                            <h4>Take a Bath</h4>
                                            <p>Take a thorough bath to clean yourself properly.</p>
                                            <p><strong>Improves hygiene by 50%</strong></p>
                                            <button type="submit" name="hygiene_type" value="bath" class="btn btn-primary btn-sm">Perform</button>
                                        </div>
                                    </div>
                                    
                                    <div class="hygiene-card">
                                        <div class="hygiene-icon">
                                            <i class="fas fa-hot-tub"></i>
                                        </div>
                                        <div class="hygiene-info">
                                            <h4>Luxury Bath</h4>
                                            <p>Enjoy a luxurious bath with scented oils and perfumes.</p>
                                            <p><strong>Improves hygiene by 80%</strong></p>
                                            <button type="submit" name="hygiene_type" value="luxury_bath" class="btn btn-primary btn-sm">Perform</button>
                                        </div>
                                    </div>
                                    
                                    <div class="hygiene-card">
                                        <div class="hygiene-icon">
                                            <i class="fas fa-cut"></i>
                                        </div>
                                        <div class="hygiene-info">
                                            <h4>Grooming</h4>
                                            <p>Take care of your hair, beard, and general appearance.</p>
                                            <p><strong>Improves hygiene by 30%</strong></p>
                                            <button type="submit" name="hygiene_type" value="grooming" class="btn btn-primary btn-sm">Perform</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Medicine Tab -->
                    <div class="tab-pane fade" id="medicine" role="tabpanel">
                        <h3>Medicine and Treatment</h3>
                        
                        <?php if (empty($medicineItems)): ?>
                            <p class="no-items">You don't have any medicine in your inventory. Visit an apothecary to purchase some.</p>
                        <?php else: ?>
                            <div class="items-grid">
                                <?php foreach ($medicineItems as $item): ?>
                                    <div class="item-card">
                                        <div class="item-image">
                                            <img src="assets/images/items/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                                        </div>
                                        <div class="item-info">
                                            <h4><?php echo $item['name']; ?></h4>
                                            <p><?php echo $item['description']; ?></p>
                                            <div class="item-stats">
                                                <span class="treatment-type">Treats: <?php echo ucfirst($item['treatment_type']); ?></span>
                                                <span class="treatment-power">Potency: <?php echo $item['treatment_power']; ?></span>
                                                <span class="quantity">Quantity: <?php echo $item['quantity']; ?></span>
                                            </div>
                                            <form action="index.php?page=life" method="post">
                                                <input type="hidden" name="action" value="medicine">
                                                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">Use</button>
                                            </form>
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

<style>
.life-system-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.life-system-wrapper {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.life-stats-overview {
    flex: 1;
    min-width: 300px;
    background: rgba(0, 0, 0, 0.2);
    border-radius: 10px;
    padding: 20px;
}

.life-actions-section {
    flex: 2;
    min-width: 400px;
    background: rgba(0, 0, 0, 0.2);
    border-radius: 10px;
    padding: 20px;
}

.stat-card {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
}

.stat-icon {
    font-size: 24px;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    margin-right: 15px;
}

.hunger .stat-icon {
    background: rgba(220, 53, 69, 0.2);
    color: #dc3545;
}

.fatigue .stat-icon {
    background: rgba(108, 117, 125, 0.2);
    color: #6c757d;
}

.hygiene .stat-icon {
    background: rgba(23, 162, 184, 0.2);
    color: #17a2b8;
}

.health .stat-icon {
    background: rgba(40, 167, 69, 0.2);
    color: #28a745;
}

.stat-info {
    flex: 1;
}

.stat-label {
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-description {
    font-size: 14px;
    color: #aaa;
    margin-top: 5px;
}

.diseases-section {
    margin-top: 20px;
    background: rgba(220, 53, 69, 0.1);
    border-radius: 5px;
    padding: 15px;
}

.disease-list {
    list-style: none;
    padding: 0;
}

.disease-list li {
    padding: 10px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.disease-name {
    font-weight: bold;
    color: #dc3545;
    display: block;
}

.disease-desc {
    font-size: 14px;
    color: #aaa;
    display: block;
    margin: 5px 0;
}

.disease-severity {
    font-size: 12px;
    color: #fff;
    background: rgba(220, 53, 69, 0.3);
    padding: 2px 5px;
    border-radius: 3px;
}

.items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.item-card {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 5px;
    padding: 15px;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.item-image {
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
}

.item-image img {
    max-height: 100%;
    max-width: 100%;
    object-fit: contain;
}

.item-info h4 {
    font-size: 16px;
    margin-bottom: 5px;
}

.item-info p {
    font-size: 13px;
    color: #aaa;
    margin-bottom: 10px;
}

.item-stats {
    font-size: 12px;
    margin-bottom: 10px;
}

.item-stats span {
    display: block;
    margin-bottom: 2px;
}

.hygiene-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.hygiene-card {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 5px;
    padding: 15px;
    height: 100%;
}

.hygiene-icon {
    font-size: 24px;
    margin-bottom: 10px;
    color: #17a2b8;
}

.hygiene-info h4 {
    font-size: 16px;
    margin-bottom: 5px;
}

.hygiene-info p {
    font-size: 13px;
    color: #aaa;
    margin-bottom: 10px;
}

.sleep-options {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 5px;
    padding: 20px;
    margin-top: 15px;
}

.sleep-warning {
    font-size: 14px;
    color: #ffc107;
    margin: 15px 0;
}

.sleep-warning i {
    margin-right: 5px;
}

.no-items {
    color: #aaa;
    font-style: italic;
    margin-top: 15px;
}

.action-tabs .nav-tabs {
    border-bottom-color: rgba(255, 255, 255, 0.1);
}

.action-tabs .nav-link {
    color: #aaa;
    border-color: transparent;
}

.action-tabs .nav-link.active {
    color: #fff;
    background-color: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.1);
    border-bottom-color: transparent;
}

.tab-content {
    padding: 20px 0;
}
</style>

<script>
$(document).ready(function() {
    // Initialize Bootstrap tabs
    $('#lifeActionTabs a').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
    });
});
</script> 