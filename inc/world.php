<?php
/**
 * World and Environment System
 * Core functions for managing districts, points of interest, weather, and time systems
 */

// DISTRICT FUNCTIONS

// Get districts in a location
function getDistricts($location_id) {
    global $conn;
    
    $sql = "SELECT * FROM districts 
            WHERE location_id = " . intval($location_id) . " 
            AND is_active = 1
            ORDER BY name";
    
    $result = $conn->query($sql);
    $districts = array();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $districts[] = $row;
        }
    }
    
    return $districts;
}

// Get specific district by ID
function getDistrict($district_id) {
    global $conn;
    
    $sql = "SELECT d.*, l.name as location_name
            FROM districts d
            JOIN locations l ON d.location_id = l.id
            WHERE d.id = " . intval($district_id);
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return false;
}

// POINTS OF INTEREST FUNCTIONS

// Get all points of interest in a district
function getPointsOfInterest($district_id, $type = null) {
    global $conn;
    
    $sql = "SELECT * FROM points_of_interest 
            WHERE district_id = " . intval($district_id) . " 
            AND is_active = 1";
    
    if ($type) {
        $sql .= " AND type = '" . $conn->real_escape_string($type) . "'";
    }
    
    $sql .= " ORDER BY name";
    
    $result = $conn->query($sql);
    $points = array();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $points[] = $row;
        }
    }
    
    return $points;
}

// Get specific point of interest
function getPOI($poi_id) {
    global $conn;
    
    $sql = "SELECT poi.*, d.name as district_name, l.name as location_name
            FROM points_of_interest poi
            JOIN districts d ON poi.district_id = d.id
            JOIN locations l ON d.location_id = l.id
            WHERE poi.id = " . intval($poi_id);
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return false;
}

// Check if a point of interest is currently open
function isPOIOpen($poi_id) {
    global $conn;
    
    // Get current game time
    $game_time = getCurrentGameTime();
    $current_hour = (int)substr($game_time['current_time'], 0, 2);
    
    // Get POI hours
    $sql = "SELECT open_hour, close_hour FROM points_of_interest 
            WHERE id = " . intval($poi_id);
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $poi = $result->fetch_assoc();
        $open_hour = $poi['open_hour'];
        $close_hour = $poi['close_hour'];
        
        // Handle special cases where close_hour is less than open_hour (spanning midnight)
        if ($close_hour < $open_hour) {
            return ($current_hour >= $open_hour || $current_hour < $close_hour);
        } else {
            return ($current_hour >= $open_hour && $current_hour < $close_hour);
        }
    }
    
    return false;
}

// Get restaurant menu items
function getMenuItems($poi_id) {
    global $conn;
    
    // Get current season for seasonal items
    $current_season = getCurrentSeason();
    
    $sql = "SELECT * FROM menu_items 
            WHERE poi_id = " . intval($poi_id) . "
            AND (is_seasonal = 0 OR season = 'all' OR season = '" . $conn->real_escape_string($current_season) . "')
            ORDER BY type, price";
    
    $result = $conn->query($sql);
    $items = array();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }
    
    return $items;
}

// Get shop inventory
function getShopInventory($poi_id) {
    global $conn;
    
    $sql = "SELECT si.*, i.name, i.description, i.type, i.subtype, i.rarity, i.level_req, 
                   i.value, i.image, i.stat_strength, i.stat_dexterity, i.stat_intelligence, 
                   i.stat_wisdom, i.stat_charisma, i.damage_min, i.damage_max, i.armor
            FROM shop_inventory si
            JOIN items i ON si.item_id = i.id
            WHERE si.poi_id = " . intval($poi_id) . "
            AND si.stock_quantity > 0
            ORDER BY i.type, i.subtype, i.name";
    
    $result = $conn->query($sql);
    $inventory = array();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Calculate actual price
            $row['actual_price'] = floor($row['value'] * $row['price_modifier']);
            $inventory[] = $row;
        }
    }
    
    return $inventory;
}

// Get park activities
function getParkActivities($poi_id) {
    global $conn;
    
    // Get current weather and time
    $location_id = getLocationIdForPOI($poi_id);
    $weather = getCurrentWeather($location_id);
    $game_time = getCurrentGameTime();
    $is_day = $game_time['is_day_time'];
    
    $sql = "SELECT * FROM park_activities 
            WHERE poi_id = " . intval($poi_id);
    
    $result = $conn->query($sql);
    $activities = array();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Check if activity is available in current weather and time
            $weather_ok = ($row['required_weather'] == 'any' || $row['required_weather'] == $weather['current_weather']);
            $time_ok = ($row['required_time'] == 'any' || 
                       ($row['required_time'] == 'day' && $is_day) || 
                       ($row['required_time'] == 'night' && !$is_day));
            
            $row['is_available'] = $weather_ok && $time_ok;
            $activities[] = $row;
        }
    }
    
    return $activities;
}

// Helper function to get location for a POI
function getLocationIdForPOI($poi_id) {
    global $conn;
    
    $sql = "SELECT l.id
            FROM points_of_interest poi
            JOIN districts d ON poi.district_id = d.id
            JOIN locations l ON d.location_id = l.id
            WHERE poi.id = " . intval($poi_id);
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id'];
    }
    
    return 1; // Default to main city if not found
}

// WEATHER SYSTEM FUNCTIONS

// Get current weather for a location
function getCurrentWeather($location_id) {
    global $conn;
    
    $sql = "SELECT * FROM weather_system
            WHERE location_id = " . intval($location_id);
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    // Default weather if not found
    return array(
        'current_weather' => 'sunny',
        'current_temperature' => 20,
        'weather_intensity' => 50,
        'weather_effects' => null
    );
}

// Update weather (called periodically by cron job)
function updateWeather() {
    global $conn;
    
    // Get current game time
    $game_time = getCurrentGameTime();
    $current_season = $game_time['current_season'];
    
    // Get season data
    $sql = "SELECT * FROM seasons WHERE name = '" . $conn->real_escape_string($current_season) . "'";
    $result = $conn->query($sql);
    $season = $result->fetch_assoc();
    
    // Get all locations
    $sql = "SELECT * FROM weather_system WHERE next_change <= NOW()";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Parse weather probabilities from JSON
            $probabilities = json_decode($season['weather_probabilities'], true);
            
            // Generate random weather based on probabilities
            $rand = mt_rand(1, 100) / 100;
            $cumulative = 0;
            $new_weather = 'sunny'; // Default
            
            foreach ($probabilities as $weather => $prob) {
                $cumulative += $prob;
                if ($rand <= $cumulative) {
                    $new_weather = $weather;
                    break;
                }
            }
            
            // Generate random temperature within season range
            $temp_variation = mt_rand(-$season['temperature_variation'], $season['temperature_variation']);
            $new_temperature = $season['base_temperature'] + $temp_variation;
            
            // Generate random intensity
            $new_intensity = mt_rand(30, 90);
            
            // Calculate weather effects based on type and intensity
            $effects = calculateWeatherEffects($new_weather, $new_intensity);
            
            // Calculate next change time (between 3-12 hours)
            $hours = mt_rand(3, 12);
            
            // Update weather in database
            $sql = "UPDATE weather_system 
                    SET current_weather = '" . $conn->real_escape_string($new_weather) . "',
                        current_temperature = " . intval($new_temperature) . ",
                        weather_intensity = " . intval($new_intensity) . ",
                        weather_effects = '" . $conn->real_escape_string(json_encode($effects)) . "',
                        last_updated = NOW(),
                        next_change = DATE_ADD(NOW(), INTERVAL " . intval($hours) . " HOUR)
                    WHERE id = " . intval($row['id']);
            
            $conn->query($sql);
        }
    }
    
    return array('success' => true, 'message' => 'Weather updated successfully');
}

// Helper function for weather effects
function calculateWeatherEffects($weather, $intensity) {
    $effects = array();
    
    switch ($weather) {
        case 'sunny':
            $effects['happiness_modifier'] = ceil($intensity / 20);
            $effects['energy_recovery'] = ceil($intensity / 25);
            break;
        case 'cloudy':
            $effects['happiness_modifier'] = -1;
            break;
        case 'rainy':
            $effects['happiness_modifier'] = -ceil($intensity / 25);
            $effects['movement_speed'] = -ceil($intensity / 30);
            break;
        case 'stormy':
            $effects['happiness_modifier'] = -ceil($intensity / 20);
            $effects['movement_speed'] = -ceil($intensity / 20);
            $effects['combat_penalty'] = -ceil($intensity / 25);
            break;
        case 'snowy':
            $effects['happiness_modifier'] = ($intensity < 60) ? 1 : -ceil(($intensity - 60) / 10);
            $effects['movement_speed'] = -ceil($intensity / 15);
            $effects['health_drain'] = ceil($intensity / 30);
            break;
        case 'foggy':
            $effects['visibility'] = -ceil($intensity / 10);
            break;
        case 'windy':
            $effects['movement_speed'] = ($intensity > 70) ? -ceil(($intensity - 70) / 10) : 0;
            break;
    }
    
    return $effects;
}

// TIME SYSTEM FUNCTIONS

// Get current game time
function getCurrentGameTime() {
    global $conn;
    
    $sql = "SELECT * FROM game_time ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    // Default time if not found
    return array(
        'current_date' => date('Y-m-d'),
        'current_time' => date('H:i:s'),
        'day_of_year' => date('z') + 1,
        'current_season' => 'spring',
        'sunrise_time' => '06:00:00',
        'sunset_time' => '18:00:00',
        'is_day_time' => true
    );
}

// Update game time (called by cron job)
function updateGameTime() {
    global $conn;
    
    // Get current game time
    $current = getCurrentGameTime();
    $time_speed = $current['time_speed'];
    
    // Calculate time increment (in minutes)
    $real_minutes_elapsed = (time() - strtotime($current['last_updated'])) / 60;
    $game_minutes_elapsed = $real_minutes_elapsed * $time_speed;
    
    // Skip if no significant time has passed
    if ($game_minutes_elapsed < 1) {
        return array('success' => true, 'message' => 'Not enough time has passed');
    }
    
    // Calculate new time
    $current_timestamp = strtotime($current['current_date'] . ' ' . $current['current_time']);
    $new_timestamp = $current_timestamp + ($game_minutes_elapsed * 60); // Convert minutes to seconds
    
    $new_date = date('Y-m-d', $new_timestamp);
    $new_time = date('H:i:s', $new_timestamp);
    $new_day_of_year = date('z', $new_timestamp) + 1; // 1-indexed day of year
    
    // Determine season based on day of year
    $season_query = "SELECT name FROM seasons 
                    WHERE start_day <= " . intval($new_day_of_year) . " 
                    AND end_day >= " . intval($new_day_of_year);
    $season_result = $conn->query($season_query);
    
    if ($season_result->num_rows > 0) {
        $season_row = $season_result->fetch_assoc();
        $new_season = $season_row['name'];
    } else {
        $new_season = 'spring'; // Default
    }
    
    // Get day length for the season
    $day_length_query = "SELECT day_length_hours FROM seasons WHERE name = '" . $conn->real_escape_string($new_season) . "'";
    $day_length_result = $conn->query($day_length_query);
    $day_length_row = $day_length_result->fetch_assoc();
    $day_length = $day_length_row['day_length_hours'];
    
    // Calculate sunrise and sunset times
    $night_length = 24 - $day_length;
    $half_night = $night_length / 2;
    
    $sunrise_hour = 6 + $half_night - 3; // Sunrise around 6 AM with seasonal adjustment
    $sunset_hour = $sunrise_hour + $day_length;
    
    $sunrise_time = sprintf('%02d:00:00', $sunrise_hour);
    $sunset_time = sprintf('%02d:00:00', $sunset_hour);
    
    // Check if it's day or night
    $current_hour = (int)date('H', $new_timestamp);
    $is_day_time = ($current_hour >= $sunrise_hour && $current_hour < $sunset_hour);
    
    // Update game time in database
    $sql = "UPDATE game_time 
            SET current_date = '" . $new_date . "',
                current_time = '" . $new_time . "',
                day_of_year = " . intval($new_day_of_year) . ",
                current_season = '" . $conn->real_escape_string($new_season) . "',
                sunrise_time = '" . $sunrise_time . "',
                sunset_time = '" . $sunset_time . "',
                is_day_time = " . ($is_day_time ? '1' : '0') . ",
                last_updated = NOW()
            WHERE id = " . intval($current['id']);
    
    $conn->query($sql);
    
    // Check for time-based events
    checkTimeEvents($new_time, $new_season, $new_date);
    
    return array('success' => true, 'message' => 'Game time updated successfully');
}

// Get current season
function getCurrentSeason() {
    $game_time = getCurrentGameTime();
    return $game_time['current_season'];
}

// Check for time-based events
function checkTimeEvents($current_time, $current_season, $current_date) {
    global $conn;
    
    // Time format for comparison (HH:MM:00)
    $time_for_comparison = substr($current_time, 0, 5) . ':00';
    
    // Query daily events that trigger at this time
    $sql = "SELECT * FROM time_events 
            WHERE event_type = 'daily' 
            AND trigger_time = '" . $conn->real_escape_string($time_for_comparison) . "'
            AND is_active = 1";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            triggerTimeEvent($row);
        }
    }
    
    // Query seasonal events
    $sql = "SELECT * FROM time_events 
            WHERE event_type = 'seasonal' 
            AND trigger_season = '" . $conn->real_escape_string($current_season) . "'
            AND trigger_time = '" . $conn->real_escape_string($time_for_comparison) . "'
            AND is_active = 1";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            triggerTimeEvent($row);
        }
    }
    
    // Query special date events
    $sql = "SELECT * FROM time_events 
            WHERE event_type = 'special' 
            AND trigger_date = '" . $conn->real_escape_string($current_date) . "'
            AND trigger_time = '" . $conn->real_escape_string($time_for_comparison) . "'
            AND is_active = 1";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            triggerTimeEvent($row);
        }
    }
}

// Handle a time event
function triggerTimeEvent($event) {
    global $conn;
    
    // Apply event effects based on type
    $effects = json_decode($event['effects'], true);
    
    if (!$effects) {
        return; // No effects to apply
    }
    
    // Handle different types of effects
    // For example, spawn special NPCs, change prices, create special quests, etc.
    
    // Log the event
    $sql = "INSERT INTO event_logs 
            (event_id, event_name, trigger_time, location_id, district_id) 
            VALUES (" . intval($event['id']) . ", '" . $conn->real_escape_string($event['name']) . "', 
            NOW(), " . ($event['location_id'] ? intval($event['location_id']) : 'NULL') . ", 
            " . ($event['district_id'] ? intval($event['district_id']) : 'NULL') . ")";
    
    $conn->query($sql);
}

// CHARACTER LOCATION TRACKING

// Update character's location
function updateCharacterLocation($character_id, $location_id, $district_id = null, $poi_id = null) {
    global $conn;
    
    // First check if there's an open record that needs to be closed
    $sql = "UPDATE character_location_history 
            SET departure_time = NOW() 
            WHERE character_id = " . intval($character_id) . " 
            AND departure_time IS NULL";
    $conn->query($sql);
    
    // Now add new location record
    $sql = "INSERT INTO character_location_history 
            (character_id, location_id, district_id, poi_id) 
            VALUES (" . intval($character_id) . ", " . intval($location_id) . ", 
            " . ($district_id ? intval($district_id) : 'NULL') . ", 
            " . ($poi_id ? intval($poi_id) : 'NULL') . ")";
    
    if ($conn->query($sql)) {
        // Also update character's current location
        $sql = "UPDATE characters SET location_id = " . intval($location_id) . " 
                WHERE id = " . intval($character_id);
        $conn->query($sql);
        
        return array('success' => true, 'message' => 'Location updated successfully');
    } else {
        return array('success' => false, 'message' => 'Error updating location');
    }
}

// Get character's current location details
function getCharacterLocationDetails($character_id) {
    global $conn;
    
    $sql = "SELECT clh.*, l.name as location_name, d.name as district_name, poi.name as poi_name, 
                  poi.type as poi_type, poi.subtype as poi_subtype
            FROM character_location_history clh
            JOIN locations l ON clh.location_id = l.id
            LEFT JOIN districts d ON clh.district_id = d.id
            LEFT JOIN points_of_interest poi ON clh.poi_id = poi.id
            WHERE clh.character_id = " . intval($character_id) . "
            AND clh.departure_time IS NULL";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    // If no open record, get character's basic location
    $sql = "SELECT c.location_id, l.name as location_name
            FROM characters c
            JOIN locations l ON c.location_id = l.id
            WHERE c.id = " . intval($character_id);
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return false;
} 