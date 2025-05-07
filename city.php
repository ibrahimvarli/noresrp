<?php
require_once 'inc/config.php';
require_once 'inc/functions.php';
require_once 'inc/world.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get active character
$character = getActiveCharacter($_SESSION['user_id']);
if (!$character) {
    header("Location: characters.php");
    exit;
}

$location_id = $character['location_id'];
$location = getLocationById($location_id);

// Check if a district was selected
$district_id = isset($_GET['district']) ? (int)$_GET['district'] : null;
$poi_id = isset($_GET['poi']) ? (int)$_GET['poi'] : null;

// Process actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // Move to a district
    if ($action == 'move_district' && isset($_GET['district'])) {
        $new_district_id = (int)$_GET['district'];
        updateCharacterLocation($character['id'], $location_id, $new_district_id);
        header("Location: city.php?district=" . $new_district_id);
        exit;
    }
    
    // Enter a point of interest
    if ($action == 'enter_poi' && isset($_GET['poi'])) {
        $new_poi_id = (int)$_GET['poi'];
        $poi = getPOI($new_poi_id);
        
        if ($poi) {
            updateCharacterLocation($character['id'], $location_id, $poi['district_id'], $new_poi_id);
            header("Location: city.php?district=" . $poi['district_id'] . "&poi=" . $new_poi_id);
            exit;
        }
    }
    
    // Exit a point of interest
    if ($action == 'exit_poi' && isset($_GET['district'])) {
        $district_id = (int)$_GET['district'];
        updateCharacterLocation($character['id'], $location_id, $district_id);
        header("Location: city.php?district=" . $district_id);
        exit;
    }
}

// Get current weather
$weather = getCurrentWeather($location_id);
$game_time = getCurrentGameTime();

// Get districts in this location
$districts = getDistricts($location_id);

// Get current location details
$location_details = getCharacterLocationDetails($character['id']);

// Get points of interest if in a district
$points_of_interest = [];
if ($district_id) {
    $points_of_interest = getPointsOfInterest($district_id);
    $current_district = getDistrict($district_id);
}

// Get POI details if in a POI
$poi_details = null;
if ($poi_id) {
    $poi_details = getPOI($poi_id);
}

// Include header
$pageTitle = $location['name'] . " - " . ($district_id ? $current_district['name'] : "City Map");
include 'inc/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2><?= htmlspecialchars($location['name']) ?></h2>
                    <div class="text-right">
                        <span class="weather-icon weather-<?= $weather['current_weather'] ?>"></span>
                        <?= ucfirst($weather['current_weather']) ?>, <?= $weather['current_temperature'] ?>°C
                        <br>
                        <?= substr($game_time['current_time'], 0, 5) ?> 
                        (<?= $game_time['is_day_time'] ? 'Day' : 'Night' ?>)
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!$district_id && !$poi_id): ?>
                        <h3>City Districts</h3>
                        <div class="city-map">
                            <?php foreach ($districts as $district): ?>
                                <div class="district-card" data-district-type="<?= $district['type'] ?>">
                                    <img src="img/districts/<?= $district['image'] ?>" alt="<?= htmlspecialchars($district['name']) ?>" class="district-image">
                                    <div class="district-info">
                                        <h4><?= htmlspecialchars($district['name']) ?></h4>
                                        <p class="district-description"><?= htmlspecialchars($district['description']) ?></p>
                                        <p>
                                            <span class="badge bg-<?= $district['safety_level'] >= 70 ? 'success' : ($district['safety_level'] >= 40 ? 'warning' : 'danger') ?>">
                                                Safety: <?= $district['safety_level'] ?>/100
                                            </span>
                                            <span class="badge bg-info">
                                                Population: <?= number_format($district['population']) ?>
                                            </span>
                                        </p>
                                        <a href="city.php?action=move_district&district=<?= $district['id'] ?>" class="btn btn-primary">Visit District</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($district_id && !$poi_id): ?>
                        <h3><?= htmlspecialchars($current_district['name']) ?></h3>
                        <p><?= htmlspecialchars($current_district['description']) ?></p>
                        
                        <div class="d-flex justify-content-between mb-4">
                            <a href="city.php" class="btn btn-secondary">Back to City Map</a>
                            <div>
                                <span class="badge bg-<?= $current_district['safety_level'] >= 70 ? 'success' : ($current_district['safety_level'] >= 40 ? 'warning' : 'danger') ?>">
                                    Safety: <?= $current_district['safety_level'] ?>/100
                                </span>
                                <span class="badge bg-<?= $current_district['wealth_level'] >= 70 ? 'success' : ($current_district['wealth_level'] >= 40 ? 'warning' : 'danger') ?>">
                                    Wealth: <?= $current_district['wealth_level'] ?>/100
                                </span>
                            </div>
                        </div>
                        
                        <h4>Points of Interest</h4>
                        <div class="row">
                            <?php foreach ($points_of_interest as $poi): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100">
                                        <img src="img/poi/<?= $poi['image'] ?>" alt="<?= htmlspecialchars($poi['name']) ?>" class="card-img-top">
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($poi['name']) ?></h5>
                                            <p class="card-text small"><?= htmlspecialchars($poi['description']) ?></p>
                                            <p>
                                                <span class="badge bg-info"><?= ucfirst($poi['type']) ?></span>
                                                <?php if (isPOIOpen($poi['id'])): ?>
                                                    <span class="badge bg-success">Open</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Closed</span>
                                                    <small>(<?= sprintf("%02d:00", $poi['open_hour']) ?> - <?= sprintf("%02d:00", $poi['close_hour']) ?>)</small>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <div class="card-footer">
                                            <a href="city.php?action=enter_poi&poi=<?= $poi['id'] ?>" class="btn btn-primary btn-sm">Visit</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($points_of_interest)): ?>
                                <div class="col-12">
                                    <p class="text-muted">No points of interest found in this district.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($poi_id): ?>
                        <h3><?= htmlspecialchars($poi_details['name']) ?></h3>
                        <p><?= htmlspecialchars($poi_details['description']) ?></p>
                        
                        <div class="d-flex justify-content-between mb-4">
                            <a href="city.php?district=<?= $district_id ?>" class="btn btn-secondary">Back to <?= htmlspecialchars($current_district['name']) ?></a>
                            <div>
                                <span class="badge bg-info"><?= ucfirst($poi_details['type']) ?></span>
                                <?php if (isPOIOpen($poi_id)): ?>
                                    <span class="badge bg-success">Open</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Closed</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($poi_details['type'] == 'restaurant' || $poi_details['type'] == 'tavern'): ?>
                            <!-- Restaurant UI -->
                            <?php $menu_items = getMenuItems($poi_id); ?>
                            
                            <h4>Menu</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Food</h5>
                                    <div class="list-group">
                                        <?php foreach ($menu_items as $item): ?>
                                            <?php if ($item['type'] == 'food'): ?>
                                                <div class="list-group-item">
                                                    <div class="d-flex justify-content-between">
                                                        <h6><?= htmlspecialchars($item['name']) ?></h6>
                                                        <span><?= $item['price'] ?> gold</span>
                                                    </div>
                                                    <p class="mb-1 small"><?= htmlspecialchars($item['description']) ?></p>
                                                    <div class="small text-muted">
                                                        Health: +<?= $item['health_restore'] ?>, Happiness: +<?= $item['happiness_bonus'] ?>
                                                        <?php if (!empty($item['effects'])): ?>
                                                            <br>Effect: <?= htmlspecialchars($item['effects']) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <button class="btn btn-sm btn-outline-primary mt-2">Order (<?= $item['price'] ?> gold)</button>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5>Drinks</h5>
                                    <div class="list-group">
                                        <?php foreach ($menu_items as $item): ?>
                                            <?php if ($item['type'] == 'drink'): ?>
                                                <div class="list-group-item">
                                                    <div class="d-flex justify-content-between">
                                                        <h6><?= htmlspecialchars($item['name']) ?></h6>
                                                        <span><?= $item['price'] ?> gold</span>
                                                    </div>
                                                    <p class="mb-1 small"><?= htmlspecialchars($item['description']) ?></p>
                                                    <div class="small text-muted">
                                                        Health: +<?= $item['health_restore'] ?>, Happiness: +<?= $item['happiness_bonus'] ?>
                                                        <?php if (!empty($item['effects'])): ?>
                                                            <br>Effect: <?= htmlspecialchars($item['effects']) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <button class="btn btn-sm btn-outline-primary mt-2">Order (<?= $item['price'] ?> gold)</button>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                        <?php elseif ($poi_details['type'] == 'shop'): ?>
                            <!-- Shop UI -->
                            <?php $inventory = getShopInventory($poi_id); ?>
                            
                            <h4>Shop Inventory</h4>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Type</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($inventory as $item): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($item['name']) ?></strong>
                                                    <div class="small text-muted"><?= htmlspecialchars($item['description']) ?></div>
                                                </td>
                                                <td><?= ucfirst($item['type']) ?><?= $item['subtype'] ? ' (' . ucfirst($item['subtype']) . ')' : '' ?></td>
                                                <td><?= $item['actual_price'] ?> gold</td>
                                                <td><?= $item['stock_quantity'] ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary">Buy</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($inventory)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No items available for purchase.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                        <?php elseif ($poi_details['type'] == 'park'): ?>
                            <!-- Park UI -->
                            <?php $activities = getParkActivities($poi_id); ?>
                            
                            <h4>Park Activities</h4>
                            <div class="row">
                                <?php foreach ($activities as $activity): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title"><?= htmlspecialchars($activity['name']) ?></h5>
                                                <p class="card-text"><?= htmlspecialchars($activity['description']) ?></p>
                                                <ul class="list-group list-group-flush small">
                                                    <li class="list-group-item">Happiness: +<?= $activity['happiness_gain'] ?></li>
                                                    <li class="list-group-item">Energy cost: <?= $activity['energy_cost'] ?></li>
                                                    <li class="list-group-item">Duration: <?= $activity['duration_minutes'] ?> minutes</li>
                                                    <?php if ($activity['required_weather'] != 'any'): ?>
                                                        <li class="list-group-item">Weather: <?= ucfirst($activity['required_weather']) ?></li>
                                                    <?php endif; ?>
                                                    <?php if ($activity['required_time'] != 'any'): ?>
                                                        <li class="list-group-item">Time: <?= ucfirst($activity['required_time']) ?></li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                            <div class="card-footer">
                                                <?php if ($activity['is_available']): ?>
                                                    <button class="btn btn-sm btn-success">Participate</button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-secondary" disabled>Not Available</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                        <?php else: ?>
                            <!-- Generic POI content -->
                            <div class="alert alert-info">
                                You are currently at <?= htmlspecialchars($poi_details['name']) ?>. 
                                Explore and interact with others here.
                            </div>
                            
                            <!-- Add more POI type-specific UIs as needed -->
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Character info sidebar -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Your Character</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="character-avatar me-3">
                            <!-- Character portrait -->
                            <img src="img/portraits/<?= $character['portrait'] ?: 'default.png' ?>" alt="Character Portrait" class="img-fluid rounded">
                        </div>
                        <div>
                            <h4><?= htmlspecialchars($character['name']) ?></h4>
                            <p class="mb-0">
                                Level <?= $character['level'] ?> <?= ucfirst($character['race']) ?> <?= ucfirst($character['class']) ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Character stats -->
                    <div class="mb-3">
                        <div class="progress mb-2" style="height: 20px;">
                            <div class="progress-bar bg-danger" role="progressbar" style="width: <?= ($character['health'] / $character['max_health']) * 100 ?>%;" aria-valuenow="<?= $character['health'] ?>" aria-valuemin="0" aria-valuemax="<?= $character['max_health'] ?>">
                                Health: <?= $character['health'] ?>/<?= $character['max_health'] ?>
                            </div>
                        </div>
                        <div class="progress mb-2" style="height: 20px;">
                            <div class="progress-bar bg-info" role="progressbar" style="width: <?= ($character['energy'] / $character['max_energy']) * 100 ?>%;" aria-valuenow="<?= $character['energy'] ?>" aria-valuemin="0" aria-valuemax="<?= $character['max_energy'] ?>">
                                Energy: <?= $character['energy'] ?>/<?= $character['max_energy'] ?>
                            </div>
                        </div>
                        <div class="progress mb-2" style="height: 20px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= ($character['happiness'] / $character['max_happiness']) * 100 ?>%;" aria-valuenow="<?= $character['happiness'] ?>" aria-valuemin="0" aria-valuemax="<?= $character['max_happiness'] ?>">
                                Happiness: <?= $character['happiness'] ?>/<?= $character['max_happiness'] ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <h5>Gold: <?= $character['gold'] ?></h5>
                    </div>
                </div>
            </div>
            
            <!-- Current location info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Current Location</h3>
                </div>
                <div class="card-body">
                    <p><strong>City:</strong> <?= htmlspecialchars($location['name']) ?></p>
                    
                    <?php if (isset($location_details['district_name']) && $location_details['district_name']): ?>
                        <p><strong>District:</strong> <?= htmlspecialchars($location_details['district_name']) ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($location_details['poi_name']) && $location_details['poi_name']): ?>
                        <p>
                            <strong>Place:</strong> <?= htmlspecialchars($location_details['poi_name']) ?> 
                            <small class="text-muted">(<?= ucfirst($location_details['poi_type']) ?>)</small>
                        </p>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <p>
                        <strong>Weather:</strong> <?= ucfirst($weather['current_weather']) ?>, <?= $weather['current_temperature'] ?>°C
                    </p>
                    
                    <p>
                        <strong>Time:</strong> <?= substr($game_time['current_time'], 0, 5) ?> 
                        (<?= $game_time['is_day_time'] ? 'Day' : 'Night' ?>)
                    </p>
                    
                    <p>
                        <strong>Season:</strong> <?= ucfirst($game_time['current_season']) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.city-map {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.district-card {
    width: 100%;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 1rem;
    display: flex;
    background-color: #f8f9fa;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.district-image {
    width: 150px;
    height: 150px;
    object-fit: cover;
}

.district-info {
    padding: 1rem;
    flex: 1;
}

.district-description {
    font-size: 0.9rem;
    color: #666;
}

.weather-icon {
    display: inline-block;
    width: 24px;
    height: 24px;
    background-size: cover;
    margin-right: 5px;
    vertical-align: middle;
}

.weather-sunny { background-image: url('img/weather/sunny.png'); }
.weather-cloudy { background-image: url('img/weather/cloudy.png'); }
.weather-rainy { background-image: url('img/weather/rainy.png'); }
.weather-stormy { background-image: url('img/weather/stormy.png'); }
.weather-snowy { background-image: url('img/weather/snowy.png'); }
.weather-foggy { background-image: url('img/weather/foggy.png'); }
.weather-windy { background-image: url('img/weather/windy.png'); }
</style>

<?php include 'inc/footer.php'; ?> 