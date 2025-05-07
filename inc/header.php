<?php
// Include weather and time functions if needed
if (file_exists('inc/world.php')) {
    require_once 'inc/world.php';
}

// Add weather and time info if character is active
$show_weather = false;
$weather_data = null;
$time_data = null;

if (isset($_SESSION['user_id'])) {
    // Check if user has an active character
    $active_char_check = "SELECT * FROM characters WHERE user_id = " . intval($_SESSION['user_id']) . " AND is_active = 1";
    $active_char_result = $conn->query($active_char_check);
    
    if ($active_char_result && $active_char_result->num_rows > 0) {
        $active_char = $active_char_result->fetch_assoc();
        
        // Only load weather if world.php is included and functions exist
        if (function_exists('getCurrentWeather') && function_exists('getCurrentGameTime')) {
            $show_weather = true;
            $weather_data = getCurrentWeather($active_char['location_id']);
            $time_data = getCurrentGameTime();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'NoRestRP Fantasy Role-Playing'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/world.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">NoRestRP</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="charactersDropdown" role="button" data-bs-toggle="dropdown">
                                Characters
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="charactersDropdown">
                                <li><a class="dropdown-item" href="characters.php">My Characters</a></li>
                                <li><a class="dropdown-item" href="index.php?page=character_lifecycle">Lifecycle</a></li>
                                <li><a class="dropdown-item" href="index.php?page=basic_needs">Basic Needs</a></li>
                                <li><a class="dropdown-item" href="index.php?page=career">Career</a></li>
                                <li><a class="dropdown-item" href="inventory.php">Inventory</a></li>
                                <li><a class="dropdown-item" href="skills.php">Skills</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="worldDropdown" role="button" data-bs-toggle="dropdown">
                                World
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="worldDropdown">
                                <li><a class="dropdown-item" href="city.php">City Explorer</a></li>
                                <li><a class="dropdown-item" href="weather.php">Weather & Time</a></li>
                                <li><a class="dropdown-item" href="map.php">World Map</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="socialDropdown" role="button" data-bs-toggle="dropdown">
                                Social
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="socialDropdown">
                                <li><a class="dropdown-item" href="index.php?page=relationships">Relationships</a></li>
                                <li><a class="dropdown-item" href="messages.php">Messages</a></li>
                                <li><a class="dropdown-item" href="events.php">Events</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="propertyDropdown" role="button" data-bs-toggle="dropdown">
                                Property
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="propertyDropdown">
                                <li><a class="dropdown-item" href="property.php">My Properties</a></li>
                                <li><a class="dropdown-item" href="property_market.php">Property Market</a></li>
                                <li><a class="dropdown-item" href="furniture.php">Furniture Store</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="about.php">About</a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <?php if ($show_weather): ?>
                <!-- Weather and Time Display -->
                <div class="weather-display me-3">
                    <div class="weather-icon weather-<?= $weather_data['current_weather'] ?>"></div>
                    <div>
                        <span class="current-weather"><?= ucfirst($weather_data['current_weather']) ?>, <?= $weather_data['current_temperature'] ?>°C</span>
                        <span class="mx-2">|</span>
                        <span class="current-time"><?= substr($time_data['current_time'], 0, 5) ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <?php echo $_SESSION['username']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Content starts here -->
</body>
</html> 