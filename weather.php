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

// Get locations for forecast
$location_id = $character['location_id'];
$current_location = getLocationById($location_id);

// Get nearby locations
$sql = "SELECT * FROM locations WHERE id != " . intval($location_id) . " ORDER BY name LIMIT 5";
$result = $conn->query($sql);
$nearby_locations = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $nearby_locations[] = $row;
    }
}

// Get weather information
$current_weather = getCurrentWeather($location_id);
$weather_effects = json_decode($current_weather['weather_effects'], true);

// Get time and season information
$game_time = getCurrentGameTime();

// Get season details
$sql = "SELECT * FROM seasons WHERE name = '" . $conn->real_escape_string($game_time['current_season']) . "'";
$result = $conn->query($sql);
$season = $result->fetch_assoc();

// Include header
$pageTitle = "Weather & Time";
include 'inc/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h2>Weather & Time</h2>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="current-weather text-center mb-4">
                                <div class="weather-icon-large weather-<?= $current_weather['current_weather'] ?>"></div>
                                <h3><?= ucfirst($current_weather['current_weather']) ?></h3>
                                <h4><?= $current_weather['current_temperature'] ?>°C</h4>
                                <p>
                                    <span class="badge bg-info">Intensity: <?= $current_weather['weather_intensity'] ?>/100</span>
                                </p>
                                <p class="text-muted">
                                    Next weather change: In approximately 
                                    <?php 
                                    $next_change = strtotime($current_weather['next_change']);
                                    $now = time();
                                    $hours_remaining = max(0, floor(($next_change - $now) / 3600));
                                    $minutes_remaining = max(0, floor(($next_change - $now) % 3600 / 60));
                                    echo $hours_remaining . " hours, " . $minutes_remaining . " minutes";
                                    ?>
                                </p>
                            </div>
                            
                            <div class="weather-effects mb-4">
                                <h4>Current Weather Effects</h4>
                                <ul class="list-group">
                                    <?php if (!empty($weather_effects)): ?>
                                        <?php foreach ($weather_effects as $effect => $value): ?>
                                            <li class="list-group-item">
                                                <div class="d-flex justify-content-between">
                                                    <span><?= ucwords(str_replace('_', ' ', $effect)) ?></span>
                                                    <span class="badge <?= $value > 0 ? 'bg-success' : 'bg-danger' ?>">
                                                        <?= $value > 0 ? '+' . $value : $value ?>
                                                    </span>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li class="list-group-item">No significant effects from current weather.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="game-time text-center mb-4">
                                <h3><?= substr($game_time['current_time'], 0, 5) ?></h3>
                                <h4><?= date('l, F j, Y', strtotime($game_time['current_date'])) ?></h4>
                                <p>
                                    <span class="badge bg-primary">
                                        <?= $game_time['is_day_time'] ? 'Day Time' : 'Night Time' ?>
                                    </span>
                                </p>
                                <div class="day-cycle">
                                    <div class="sun-position" style="left: <?= calculateSunPosition($game_time) ?>%"></div>
                                    <div class="horizon">
                                        <div class="sunrise" style="left: <?= calculateTimePosition($game_time['sunrise_time']) ?>%" title="Sunrise: <?= substr($game_time['sunrise_time'], 0, 5) ?>"></div>
                                        <div class="sunset" style="left: <?= calculateTimePosition($game_time['sunset_time']) ?>%" title="Sunset: <?= substr($game_time['sunset_time'], 0, 5) ?>"></div>
                                    </div>
                                </div>
                                <div class="time-indicators d-flex justify-content-between small text-muted">
                                    <span>00:00</span>
                                    <span>06:00</span>
                                    <span>12:00</span>
                                    <span>18:00</span>
                                    <span>24:00</span>
                                </div>
                            </div>
                            
                            <div class="season-info mb-4">
                                <h4>Current Season: <?= ucfirst($season['display_name']) ?></h4>
                                <p><?= $season['description'] ?></p>
                                <ul class="list-group">
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <span>Day Length</span>
                                            <span><?= $season['day_length_hours'] ?> hours</span>
                                        </div>
                                    </li>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <span>Average Temperature</span>
                                            <span><?= $season['base_temperature'] ?>°C (±<?= $season['temperature_variation'] ?>°C)</span>
                                        </div>
                                    </li>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <span>Season Progress</span>
                                            <div class="progress" style="width: 50%;">
                                                <div class="progress-bar" role="progressbar" style="width: <?= calculateSeasonProgress($game_time['day_of_year'], $season) ?>%"></div>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h3>Weather in Nearby Locations</h3>
                    <div class="row">
                        <?php foreach ($nearby_locations as $location): ?>
                            <?php $loc_weather = getCurrentWeather($location['id']); ?>
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($location['name']) ?></h5>
                                        <div class="d-flex align-items-center">
                                            <div class="weather-icon weather-<?= $loc_weather['current_weather'] ?>"></div>
                                            <div class="ms-3">
                                                <div><?= ucfirst($loc_weather['current_weather']) ?></div>
                                                <div><?= $loc_weather['current_temperature'] ?>°C</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Weather Guide</h3>
                </div>
                <div class="card-body">
                    <div class="accordion" id="weatherAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingSunny">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSunny" aria-expanded="false" aria-controls="collapseSunny">
                                    <div class="weather-icon weather-sunny me-2"></div> Sunny
                                </button>
                            </h2>
                            <div id="collapseSunny" class="accordion-collapse collapse" aria-labelledby="headingSunny" data-bs-parent="#weatherAccordion">
                                <div class="accordion-body">
                                    <p>Clear skies and sunshine. Increases happiness and energy recovery.</p>
                                    <p><strong>Effects:</strong> Happiness +1 to +5, Energy Recovery +1 to +4</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingCloudy">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCloudy" aria-expanded="false" aria-controls="collapseCloudy">
                                    <div class="weather-icon weather-cloudy me-2"></div> Cloudy
                                </button>
                            </h2>
                            <div id="collapseCloudy" class="accordion-collapse collapse" aria-labelledby="headingCloudy" data-bs-parent="#weatherAccordion">
                                <div class="accordion-body">
                                    <p>Overcast skies with occasional breaks. Slightly reduces happiness.</p>
                                    <p><strong>Effects:</strong> Happiness -1</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingRainy">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRainy" aria-expanded="false" aria-controls="collapseRainy">
                                    <div class="weather-icon weather-rainy me-2"></div> Rainy
                                </button>
                            </h2>
                            <div id="collapseRainy" class="accordion-collapse collapse" aria-labelledby="headingRainy" data-bs-parent="#weatherAccordion">
                                <div class="accordion-body">
                                    <p>Steady rainfall that reduces happiness and slows movement.</p>
                                    <p><strong>Effects:</strong> Happiness -1 to -4, Movement Speed -1 to -3</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingStormy">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStormy" aria-expanded="false" aria-controls="collapseStormy">
                                    <div class="weather-icon weather-stormy me-2"></div> Stormy
                                </button>
                            </h2>
                            <div id="collapseStormy" class="accordion-collapse collapse" aria-labelledby="headingStormy" data-bs-parent="#weatherAccordion">
                                <div class="accordion-body">
                                    <p>Thunderstorms with heavy rain and strong winds. Significantly affects gameplay.</p>
                                    <p><strong>Effects:</strong> Happiness -2 to -5, Movement Speed -2 to -5, Combat Penalty -2 to -4</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingSnowy">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSnowy" aria-expanded="false" aria-controls="collapseSnowy">
                                    <div class="weather-icon weather-snowy me-2"></div> Snowy
                                </button>
                            </h2>
                            <div id="collapseSnowy" class="accordion-collapse collapse" aria-labelledby="headingSnowy" data-bs-parent="#weatherAccordion">
                                <div class="accordion-body">
                                    <p>Snowfall that can range from light flurries to heavy blizzards. Light snow can increase happiness, but heavy snow has negative effects.</p>
                                    <p><strong>Effects:</strong> Happiness +1 to -4 (depends on intensity), Movement Speed -2 to -7, Health Drain 1 to 3</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFoggy">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFoggy" aria-expanded="false" aria-controls="collapseFoggy">
                                    <div class="weather-icon weather-foggy me-2"></div> Foggy
                                </button>
                            </h2>
                            <div id="collapseFoggy" class="accordion-collapse collapse" aria-labelledby="headingFoggy" data-bs-parent="#weatherAccordion">
                                <div class="accordion-body">
                                    <p>Mist and fog that reduces visibility and adds an eerie atmosphere.</p>
                                    <p><strong>Effects:</strong> Visibility -3 to -10 (affects exploration and combat)</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingWindy">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWindy" aria-expanded="false" aria-controls="collapseWindy">
                                    <div class="weather-icon weather-windy me-2"></div> Windy
                                </button>
                            </h2>
                            <div id="collapseWindy" class="accordion-collapse collapse" aria-labelledby="headingWindy" data-bs-parent="#weatherAccordion">
                                <div class="accordion-body">
                                    <p>Strong winds that can range from mild breezes to powerful gusts. High intensity winds can slow movement.</p>
                                    <p><strong>Effects:</strong> Movement Speed 0 to -3 (only at high intensities)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Seasons</h3>
                </div>
                <div class="card-body">
                    <div class="seasons-overview">
                        <?php
                        $seasons_query = "SELECT * FROM seasons ORDER BY start_day";
                        $seasons_result = $conn->query($seasons_query);
                        while ($s = $seasons_result->fetch_assoc()):
                        ?>
                            <div class="season-card mb-3 <?= $s['name'] == $game_time['current_season'] ? 'current-season' : '' ?>">
                                <div class="d-flex align-items-center">
                                    <div class="season-icon season-<?= $s['name'] ?>"></div>
                                    <div class="ms-3">
                                        <h5 class="mb-0"><?= ucfirst($s['display_name']) ?></h5>
                                        <div class="text-muted small">Days <?= $s['start_day'] ?> - <?= $s['end_day'] ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.weather-icon {
    width: 32px;
    height: 32px;
    background-size: cover;
}

.weather-icon-large {
    width: 80px;
    height: 80px;
    background-size: cover;
    margin: 0 auto 15px;
}

.weather-sunny { background-image: url('img/weather/sunny.png'); }
.weather-cloudy { background-image: url('img/weather/cloudy.png'); }
.weather-rainy { background-image: url('img/weather/rainy.png'); }
.weather-stormy { background-image: url('img/weather/stormy.png'); }
.weather-snowy { background-image: url('img/weather/snowy.png'); }
.weather-foggy { background-image: url('img/weather/foggy.png'); }
.weather-windy { background-image: url('img/weather/windy.png'); }

.season-icon {
    width: 48px;
    height: 48px;
    background-size: cover;
    border-radius: 50%;
}

.season-spring { background-image: url('img/seasons/spring.png'); }
.season-summer { background-image: url('img/seasons/summer.png'); }
.season-autumn { background-image: url('img/seasons/autumn.png'); }
.season-winter { background-image: url('img/seasons/winter.png'); }

.current-season {
    background-color: rgba(0, 123, 255, 0.1);
    border-radius: 8px;
    padding: 10px;
}

.day-cycle {
    position: relative;
    height: 60px;
    background: linear-gradient(to bottom, #87CEEB, #1E90FF 60%, #000033);
    border-radius: 100px 100px 0 0;
    margin-top: 20px;
    margin-bottom: 5px;
    overflow: hidden;
}

.sun-position {
    position: absolute;
    bottom: 0;
    width: 30px;
    height: 30px;
    background-color: yellow;
    border-radius: 50%;
    box-shadow: 0 0 10px 5px rgba(255, 255, 0, 0.5);
    transform: translateY(50%);
    margin-left: -15px;
}

.horizon {
    position: absolute;
    bottom: 0;
    width: 100%;
    height: 2px;
    background-color: #333;
}

.sunrise, .sunset {
    position: absolute;
    bottom: 0;
    width: 4px;
    height: 8px;
    background-color: orange;
    transform: translateX(-50%);
}
</style>

<?php
// Helper functions for the weather page

// Calculate sun position based on current time (0-100%)
function calculateSunPosition($game_time) {
    $sunrise_hour = (int)substr($game_time['sunrise_time'], 0, 2);
    $sunset_hour = (int)substr($game_time['sunset_time'], 0, 2);
    $current_hour = (int)substr($game_time['current_time'], 0, 2);
    $current_minute = (int)substr($game_time['current_time'], 3, 2);
    
    $current_decimal_hour = $current_hour + ($current_minute / 60);
    
    // If before sunrise or after sunset, position sun below horizon
    if ($current_decimal_hour < $sunrise_hour) {
        // Night before sunrise (0% to 5%)
        return ($current_decimal_hour / $sunrise_hour) * 5;
    } else if ($current_decimal_hour > $sunset_hour) {
        // Night after sunset (95% to 100%)
        return 95 + (($current_decimal_hour - $sunset_hour) / (24 - $sunset_hour + $sunrise_hour)) * 5;
    } else {
        // Day time (5% to 95%)
        return 5 + (($current_decimal_hour - $sunrise_hour) / ($sunset_hour - $sunrise_hour)) * 90;
    }
}

// Calculate position for sunrise/sunset time markers (0-100%)
function calculateTimePosition($time) {
    $hour = (int)substr($time, 0, 2);
    $minute = (int)substr($time, 3, 2);
    
    return ($hour * 60 + $minute) / (24 * 60) * 100;
}

// Calculate season progress (0-100%)
function calculateSeasonProgress($current_day, $season) {
    $season_start = $season['start_day'];
    $season_end = $season['end_day'];
    $season_length = $season_end - $season_start + 1;
    
    // Handle season spanning year boundary (winter)
    if ($current_day < $season_start) {
        $day_in_season = $current_day + 365 - $season_start;
    } else {
        $day_in_season = $current_day - $season_start;
    }
    
    return min(100, max(0, ($day_in_season / $season_length) * 100));
}
?>

<?php include 'inc/footer.php'; ?> 