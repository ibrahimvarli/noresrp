<?php
// Redirect if not logged in
if (!isLoggedIn()) {
    redirect("index.php?page=login");
}

// Get user's active character
$userId = (int)$_SESSION['user_id'];
$activeCharacter = getActiveCharacter($userId);

// Redirect if no active character
if (!$activeCharacter) {
    $_SESSION['flash_message'] = "Please create or select a character first.";
    $_SESSION['flash_type'] = "warning";
    redirect("index.php?page=characters");
}

// Get all locations
global $db;
$sql = "SELECT * FROM locations ORDER BY level_req ASC";
$result = $db->query($sql);
$locations = $result->fetch_all(MYSQLI_ASSOC);

// Get active character's current location
$characterLocationId = $activeCharacter['location_id'];
$currentLocation = null;
foreach ($locations as $location) {
    if ($location['id'] == $characterLocationId) {
        $currentLocation = $location;
        break;
    }
}

// Process travel request
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['travel_to'])) {
    $destinationId = (int)$_POST['travel_to'];
    $destination = null;
    
    // Find destination location
    foreach ($locations as $location) {
        if ($location['id'] == $destinationId) {
            $destination = $location;
            break;
        }
    }
    
    if (!$destination) {
        $error = "Invalid destination selected.";
    } elseif ($destination['level_req'] > $activeCharacter['level']) {
        $error = "You need to be level " . $destination['level_req'] . " to travel to " . $destination['name'] . ".";
    } else {
        // Update character location
        $sql = "UPDATE characters SET location_id = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ii", $destinationId, $activeCharacter['id']);
        
        if ($stmt->execute()) {
            $success = "You have traveled to " . $destination['name'] . ".";
            $characterLocationId = $destinationId;
            $currentLocation = $destination;
            
            // Update local character data
            $activeCharacter['location_id'] = $destinationId;
            
            // Random chance of encounter based on location type
            if (!$destination['is_safe'] && mt_rand(1, 100) <= 30) {
                $_SESSION['flash_message'] = "You encountered enemies on your journey!";
                $_SESSION['flash_type'] = "warning";
                redirect("index.php?page=battle");
            }
        } else {
            $error = "Failed to travel to the destination.";
        }
    }
}

// Get enemies in current location
$enemies = [];
if ($currentLocation) {
    $sql = "SELECT * FROM enemies WHERE location_id = ? ORDER BY level ASC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterLocationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $enemies = $result->fetch_all(MYSQLI_ASSOC);
}

// Get quests in current location
$quests = [];
if ($currentLocation) {
    $sql = "SELECT * FROM quests WHERE location_id = ? AND level_req <= ? ORDER BY level_req ASC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $characterLocationId, $activeCharacter['level']);
    $stmt->execute();
    $result = $stmt->get_result();
    $quests = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="world-container">
    <div class="page-header">
        <h1>World Map</h1>
        <p>Explore the magical realm of Eldoria</p>
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
    
    <div class="world-wrapper">
        <div class="world-map">
            <?php foreach ($locations as $location): ?>
                <?php 
                // Check if location is locked based on level requirement
                $isLocked = $location['level_req'] > $activeCharacter['level'];
                $isActive = $location['id'] == $characterLocationId;
                $canTravel = !$isLocked && !$isActive;
                
                // Calculate position on map based on coordinates
                $left = ($location['x_coord'] / 10) . '%';
                $top = ($location['y_coord'] / 10) . '%';
                ?>
                
                <div class="map-location <?php echo $isLocked ? 'locked' : ''; ?> <?php echo $isActive ? 'active' : ''; ?>" 
                    style="left: <?php echo $left; ?>; top: <?php echo $top; ?>;"
                    data-location="<?php echo $location['id']; ?>"
                    data-name="<?php echo $location['name']; ?>"
                    data-description="<?php echo $location['description']; ?>"
                    data-image="assets/images/locations/<?php echo $location['image'] ?: 'default.jpg'; ?>">
                    
                    <div class="location-marker">
                        <i class="<?php echo getLocationIcon($location['type']); ?>"></i>
                    </div>
                    
                    <?php if ($isLocked): ?>
                        <div class="location-lock">
                            <i class="fas fa-lock"></i>
                            <span class="location-level">Lvl <?php echo $location['level_req']; ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="location-name">
                        <?php echo $location['name']; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Add map connections -->
            <svg class="map-connections">
                <path d="M50,50 L60,45" class="connection" />
                <path d="M50,50 L40,55" class="connection" />
                <path d="M60,45 L70,50" class="connection" />
                <path d="M40,55 L45,35" class="connection" />
            </svg>
        </div>
        
        <div class="location-info">
            <?php if ($currentLocation): ?>
                <h2><?php echo $currentLocation['name']; ?></h2>
                
                <div class="location-image">
                    <img src="assets/images/locations/<?php echo $currentLocation['image'] ?: 'default.jpg'; ?>" alt="<?php echo $currentLocation['name']; ?>">
                </div>
                
                <div class="location-description">
                    <p><?php echo $currentLocation['description']; ?></p>
                </div>
                
                <div class="location-status">
                    <?php if ($currentLocation['is_safe']): ?>
                        <div class="status-badge safe">
                            <i class="fas fa-shield-alt"></i> Safe Zone
                        </div>
                    <?php else: ?>
                        <div class="status-badge dangerous">
                            <i class="fas fa-skull"></i> Dangerous Area
                        </div>
                    <?php endif; ?>
                    
                    <div class="status-badge level">
                        <i class="fas fa-star"></i> Level <?php echo $currentLocation['level_req']; ?>+
                    </div>
                </div>
                
                <?php if ($enemies): ?>
                    <div class="location-section">
                        <h3><i class="fas fa-skull-crossbones"></i> Enemies</h3>
                        <div class="enemy-list">
                            <?php foreach ($enemies as $enemy): ?>
                                <div class="enemy-card">
                                    <div class="enemy-avatar">
                                        <img src="assets/images/enemies/<?php echo $enemy['image'] ?: 'default.jpg'; ?>" alt="<?php echo $enemy['name']; ?>">
                                    </div>
                                    <div class="enemy-info">
                                        <h4><?php echo $enemy['name']; ?></h4>
                                        <div class="enemy-level">Level <?php echo $enemy['level']; ?></div>
                                        <div class="enemy-type"><?php echo ucfirst($enemy['type']); ?></div>
                                    </div>
                                    <a href="?page=battle&enemy=<?php echo $enemy['id']; ?>" class="btn btn-danger btn-sm">
                                        <i class="fas fa-crosshairs"></i> Fight
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($quests): ?>
                    <div class="location-section">
                        <h3><i class="fas fa-scroll"></i> Quests</h3>
                        <div class="quest-list">
                            <?php foreach ($quests as $quest): ?>
                                <div class="quest-card">
                                    <div class="quest-info">
                                        <h4><?php echo $quest['title']; ?></h4>
                                        <div class="quest-level">Level <?php echo $quest['level_req']; ?></div>
                                        <div class="quest-giver">From: <?php echo $quest['quest_giver']; ?></div>
                                        <?php if ($quest['is_main_quest']): ?>
                                            <div class="quest-badge main">Main Quest</div>
                                        <?php endif; ?>
                                    </div>
                                    <a href="?page=quest&id=<?php echo $quest['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-book-open"></i> Details
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="location-section">
                    <h3><i class="fas fa-road"></i> Travel</h3>
                    <div class="travel-options">
                        <?php 
                        $travelOptions = [];
                        foreach ($locations as $location) {
                            if ($location['id'] != $characterLocationId && $location['level_req'] <= $activeCharacter['level']) {
                                $travelOptions[] = $location;
                            }
                        }
                        
                        if ($travelOptions): 
                        ?>
                            <form method="POST" action="">
                                <div class="form-group">
                                    <select name="travel_to" class="travel-select">
                                        <option value="">Select destination...</option>
                                        <?php foreach ($travelOptions as $option): ?>
                                            <option value="<?php echo $option['id']; ?>">
                                                <?php echo $option['name']; ?> (Level <?php echo $option['level_req']; ?>+)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-hiking"></i> Travel
                                </button>
                            </form>
                        <?php else: ?>
                            <p class="no-travel">No available travel destinations at your current level.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="location-placeholder">
                    <div class="placeholder-icon">
                        <i class="fas fa-map-marked"></i>
                    </div>
                    <h3>Select a Location</h3>
                    <p>Click on a location on the map to view details</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Map location interactions
    const mapLocations = document.querySelectorAll('.map-location:not(.locked)');
    const locationInfo = document.querySelector('.location-info');
    
    mapLocations.forEach(location => {
        location.addEventListener('click', function() {
            if (this.classList.contains('active')) return;
            
            const locationId = this.dataset.location;
            const locationName = this.dataset.name;
            const locationDesc = this.dataset.description;
            const locationImage = this.dataset.image;
            
            // Redirect to selected location
            window.location.href = `?page=world&location=${locationId}`;
        });
    });
});
</script>

<style>
.world-container {
    max-width: var(--container-width);
    margin: 0 auto;
}

.page-header {
    text-align: center;
    margin-bottom: 4rem;
}

.page-header h1 {
    font-size: 4rem;
    margin-bottom: 1rem;
    color: var(--accent-primary);
}

.page-header p {
    font-size: 1.8rem;
    color: var(--text-secondary);
}

.world-wrapper {
    display: flex;
    gap: 3rem;
}

.world-map {
    flex: 3;
    height: 600px;
    background-color: var(--bg-secondary);
    background-image: url('assets/images/world-map.jpg');
    background-size: cover;
    background-position: center;
    border-radius: var(--border-radius);
    position: relative;
    box-shadow: 0 5px 15px var(--shadow);
    overflow: hidden;
}

.map-connections {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
}

.connection {
    stroke: rgba(255, 255, 255, 0.5);
    stroke-width: 2;
    fill: none;
    stroke-dasharray: 5;
    animation: dash 30s linear infinite;
}

@keyframes dash {
    to {
        stroke-dashoffset: 1000;
    }
}

.map-location {
    position: absolute;
    transform: translate(-50%, -50%);
    z-index: 2;
    cursor: pointer;
    transition: all 0.3s ease;
}

.map-location.locked {
    filter: grayscale(100%);
    opacity: 0.7;
    cursor: not-allowed;
}

.map-location.active {
    transform: translate(-50%, -50%) scale(1.2);
    z-index: 3;
}

.location-marker {
    width: 40px;
    height: 40px;
    background-color: var(--bg-tertiary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-primary);
    font-size: 1.8rem;
    box-shadow: 0 2px 10px var(--shadow);
    transition: all 0.3s ease;
}

.map-location:hover .location-marker {
    transform: scale(1.1);
}

.map-location.active .location-marker {
    background-color: var(--accent-primary);
    color: white;
}

.location-lock {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: var(--bg-tertiary);
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    box-shadow: 0 2px 5px var(--shadow);
}

.location-level {
    position: absolute;
    bottom: -20px;
    right: -10px;
    background-color: var(--bg-tertiary);
    border-radius: 10px;
    padding: 0.2rem 0.5rem;
    font-size: 1rem;
    box-shadow: 0 2px 5px var(--shadow);
}

.location-name {
    position: absolute;
    bottom: -30px;
    left: 50%;
    transform: translateX(-50%);
    white-space: nowrap;
    background-color: var(--bg-tertiary);
    padding: 0.3rem 1rem;
    border-radius: 10px;
    font-size: 1.2rem;
    font-weight: 600;
    opacity: 0;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px var(--shadow);
}

.map-location:hover .location-name,
.map-location.active .location-name {
    opacity: 1;
    bottom: -25px;
}

.location-info {
    flex: 2;
    background-color: var(--bg-secondary);
    border-radius: var(--border-radius);
    padding: 2rem;
    box-shadow: 0 5px 15px var(--shadow);
    overflow-y: auto;
    max-height: 600px;
}

.location-info h2 {
    font-size: 2.8rem;
    margin-bottom: 2rem;
    color: var(--accent-primary);
    text-align: center;
}

.location-image {
    margin-bottom: 2rem;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: 0 2px 10px var(--shadow);
}

.location-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.location-description {
    margin-bottom: 2rem;
    line-height: 1.8;
}

.location-status {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 2rem;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-size: 1.2rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-badge.safe {
    background-color: rgba(46, 204, 113, 0.2);
    color: #2ecc71;
}

.status-badge.dangerous {
    background-color: rgba(231, 76, 60, 0.2);
    color: #e74c3c;
}

.status-badge.level {
    background-color: rgba(52, 152, 219, 0.2);
    color: #3498db;
}

.location-section {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid var(--border);
}

.location-section h3 {
    font-size: 1.8rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

.enemy-list, .quest-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.enemy-card, .quest-card {
    display: flex;
    align-items: center;
    background-color: var(--bg-tertiary);
    border-radius: var(--border-radius);
    padding: 1rem;
    transition: transform 0.3s ease;
}

.enemy-card:hover, .quest-card:hover {
    transform: translateY(-2px);
}

.enemy-avatar {
    width: 50px;
    height: 50px;
    border-radius: var(--border-radius);
    overflow: hidden;
    margin-right: 1.5rem;
}

.enemy-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.enemy-info, .quest-info {
    flex: 1;
}

.enemy-info h4, .quest-info h4 {
    font-size: 1.6rem;
    margin-bottom: 0.3rem;
}

.enemy-level, .quest-level {
    font-size: 1.2rem;
    color: var(--text-secondary);
    margin-bottom: 0.3rem;
}

.enemy-type, .quest-giver {
    font-size: 1.2rem;
    color: var(--text-secondary);
}

.quest-badge {
    display: inline-block;
    padding: 0.2rem 0.5rem;
    border-radius: 0.3rem;
    font-size: 1rem;
    font-weight: 600;
    margin-top: 0.5rem;
}

.quest-badge.main {
    background-color: rgba(241, 196, 15, 0.2);
    color: #f1c40f;
}

.travel-options {
    display: flex;
    flex-direction: column;
}

.travel-select {
    width: 100%;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid var(--border);
    border-radius: var(--border-radius);
    background-color: var(--bg-tertiary);
    color: var(--text-primary);
    font-size: 1.4rem;
}

.travel-select:focus {
    outline: none;
    border-color: var(--accent-primary);
}

.no-travel {
    font-style: italic;
    color: var(--text-secondary);
    text-align: center;
    padding: 1rem;
}

.location-placeholder {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 4rem 2rem;
}

.placeholder-icon {
    font-size: 5rem;
    color: var(--text-secondary);
    margin-bottom: 2rem;
    opacity: 0.5;
}

.location-placeholder h3 {
    font-size: 2.2rem;
    margin-bottom: 1rem;
}

.location-placeholder p {
    font-size: 1.6rem;
    color: var(--text-secondary);
}

@media (max-width: 992px) {
    .world-wrapper {
        flex-direction: column;
    }
    
    .world-map {
        height: 500px;
    }
}

@media (max-width: 768px) {
    .world-map {
        height: 400px;
    }
}

@media (max-width: 480px) {
    .world-map {
        height: 300px;
    }
    
    .location-info h2 {
        font-size: 2.2rem;
    }
}
</style>

<?php
// Helper function to get location icon based on type
function getLocationIcon($type) {
    switch ($type) {
        case 'city':
            return 'fas fa-city';
        case 'dungeon':
            return 'fas fa-dungeon';
        case 'wilderness':
            return 'fas fa-tree';
        case 'special':
            return 'fas fa-landmark';
        default:
            return 'fas fa-map-marker-alt';
    }
}
?> 