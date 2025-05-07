<?php
/**
 * Character Lifecycle System
 * 
 * Core functions for handling:
 * - Character aging
 * - Life stages (infant, child, teenager, adult, elder)
 * - Aging effects on attributes
 * - Birthday events
 * - Race-specific aging characteristics
 */

/**
 * Process aging for all characters based on game time
 */
function processCharacterAging($currentGameDate) {
    global $conn;
    
    // Get all active characters
    $sql = "SELECT id, name, race, age, birthday, last_aging_date 
            FROM characters 
            WHERE is_active = 1 OR last_active >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        return array('success' => false, 'message' => 'Error fetching characters: ' . $conn->error);
    }
    
    $updated = 0;
    $birthdays = 0;
    
    while ($character = $result->fetch_assoc()) {
        // Skip if already processed today
        if ($character['last_aging_date'] == $currentGameDate) {
            continue;
        }
        
        // Process birthday if applicable
        if ($character['birthday']) {
            $birthday = $character['birthday'];
            $isBirthday = (substr($birthday, 5) == substr($currentGameDate, 5)); // Compare month-day
            
            if ($isBirthday) {
                // Increment age on birthday
                $new_age = $character['age'] + 1;
                
                // Apply age-related changes
                applyAgeChanges($character['id'], $new_age, $character['race']);
                
                // Create birthday event
                createAgingEvent($character['id'], 'birthday', $currentGameDate, 
                    $character['name'] . ' celebrates their ' . $new_age . ' birthday!');
                
                $birthdays++;
            }
        }
        
        // Update last aging date
        $sql = "UPDATE characters SET last_aging_date = '" . $conn->real_escape_string($currentGameDate) . "' 
                WHERE id = " . intval($character['id']);
        $conn->query($sql);
        
        $updated++;
    }
    
    return array(
        'success' => true, 
        'message' => "Updated $updated characters, $birthdays birthdays processed"
    );
}

/**
 * Apply age-related changes to a character
 */
function applyAgeChanges($characterId, $newAge, $race) {
    global $conn;
    
    // Update age
    $sql = "UPDATE characters SET age = " . intval($newAge) . " WHERE id = " . intval($characterId);
    $conn->query($sql);
    
    // Get aging rate based on race
    $agingRate = getRaceAgingRate($race);
    
    // Get life stage
    $lifeStage = calculateLifeStage($newAge, $race);
    
    // Apply age-appropriate attribute changes
    $attributeChanges = [];
    
    switch ($lifeStage) {
        case 'infant': // 0-3 years
            $attributeChanges = [
                'strength' => 0,
                'dexterity' => 0,
                'intelligence' => 0,
                'wisdom' => 0,
                'charisma' => 0
            ];
            break;
            
        case 'child': // 3-12 years
            $attributeChanges = [
                'strength' => 1,
                'dexterity' => 2,
                'intelligence' => 2,
                'wisdom' => 1,
                'charisma' => 1
            ];
            break;
            
        case 'teenager': // 13-17 years
            $attributeChanges = [
                'strength' => 2,
                'dexterity' => 1,
                'intelligence' => 1,
                'wisdom' => 1,
                'charisma' => 1
            ];
            break;
            
        case 'young_adult': // 18-30 years
            $attributeChanges = [
                'strength' => 1,
                'dexterity' => 1,
                'intelligence' => 1,
                'wisdom' => 1,
                'charisma' => 1
            ];
            break;
            
        case 'adult': // 31-50 years
            $attributeChanges = [
                'strength' => 0,
                'dexterity' => 0,
                'intelligence' => 1,
                'wisdom' => 2,
                'charisma' => 1
            ];
            break;
            
        case 'middle_aged': // 51-70 years
            $attributeChanges = [
                'strength' => -1,
                'dexterity' => -1,
                'intelligence' => 0,
                'wisdom' => 2,
                'charisma' => 1
            ];
            break;
            
        case 'elder': // 71-90 years
            $attributeChanges = [
                'strength' => -2,
                'dexterity' => -2,
                'intelligence' => 0,
                'wisdom' => 1,
                'charisma' => 0
            ];
            break;
            
        case 'venerable': // 91+ years
            $attributeChanges = [
                'strength' => -3,
                'dexterity' => -3,
                'intelligence' => -1,
                'wisdom' => 1,
                'charisma' => -1
            ];
            break;
    }
    
    // Apply attribute changes
    $statChanges = [];
    foreach ($attributeChanges as $attribute => $change) {
        if ($change != 0) {
            // Only apply every few years based on race aging rate
            $shouldChange = (($newAge % $agingRate) == 0);
            
            if ($shouldChange) {
                $sql = "UPDATE characters SET $attribute = $attribute + " . intval($change) . " 
                       WHERE id = " . intval($characterId);
                $conn->query($sql);
                
                $statChanges[$attribute] = $change;
            }
        }
    }
    
    // Record significant attribute changes as events
    if (!empty($statChanges)) {
        createAgingEvent($characterId, 'attribute_change', null, 
            'Age-related attribute changes', json_encode($statChanges));
    }
    
    return true;
}

/**
 * Get aging rate modifier based on race
 */
function getRaceAgingRate($race) {
    switch ($race) {
        case 'elf':
            return 20; // Elves age very slowly
        case 'dwarf':
            return 10; // Dwarves age slowly
        case 'orc':
            return 2; // Orcs age quickly
        case 'human':
        default:
            return 5; // Humans are the baseline
    }
}

/**
 * Calculate life stage based on age and race
 */
function calculateLifeStage($age, $race) {
    // Adjust age thresholds based on race
    switch ($race) {
        case 'elf':
            // Elves live for hundreds of years
            if ($age < 15) return 'infant';
            if ($age < 50) return 'child';
            if ($age < 100) return 'teenager';
            if ($age < 200) return 'young_adult';
            if ($age < 400) return 'adult';
            if ($age < 600) return 'middle_aged';
            if ($age < 800) return 'elder';
            return 'venerable';
            
        case 'dwarf':
            // Dwarves live longer than humans but shorter than elves
            if ($age < 5) return 'infant';
            if ($age < 20) return 'child';
            if ($age < 30) return 'teenager';
            if ($age < 50) return 'young_adult';
            if ($age < 150) return 'adult';
            if ($age < 250) return 'middle_aged';
            if ($age < 300) return 'elder';
            return 'venerable';
            
        case 'orc':
            // Orcs have shorter lifespans
            if ($age < 2) return 'infant';
            if ($age < 8) return 'child';
            if ($age < 13) return 'teenager';
            if ($age < 20) return 'young_adult';
            if ($age < 35) return 'adult';
            if ($age < 45) return 'middle_aged';
            if ($age < 55) return 'elder';
            return 'venerable';
            
        case 'human':
        default:
            // Human standard
            if ($age < 3) return 'infant';
            if ($age < 12) return 'child';
            if ($age < 18) return 'teenager';
            if ($age < 30) return 'young_adult';
            if ($age < 50) return 'adult';
            if ($age < 70) return 'middle_aged';
            if ($age < 90) return 'elder';
            return 'venerable';
    }
}

/**
 * Create an aging event for a character
 */
function createAgingEvent($characterId, $eventType, $eventDate = null, $description = '', $statChanges = null) {
    global $conn;
    
    if ($eventDate === null) {
        $eventDate = date('Y-m-d'); // Default to current date
    }
    
    $sql = "INSERT INTO character_aging_events 
            (character_id, event_type, event_date, description, stat_changes, is_processed) 
            VALUES (" . intval($characterId) . ", 
                   '" . $conn->real_escape_string($eventType) . "', 
                   '" . $conn->real_escape_string($eventDate) . "', 
                   '" . $conn->real_escape_string($description) . "', 
                   " . ($statChanges ? "'" . $conn->real_escape_string($statChanges) . "'" : "NULL") . ", 
                   0)";
    
    $conn->query($sql);
    
    return $conn->insert_id;
}

/**
 * Process lifecycle stage changes
 */
function processLifecycleChanges() {
    global $conn;
    
    // Get all characters
    $sql = "SELECT id, name, race, age, created_at FROM characters 
            WHERE is_active = 1 OR last_active >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        return array('success' => false, 'message' => 'Error fetching characters: ' . $conn->error);
    }
    
    $lifecycleChanges = 0;
    
    while ($character = $result->fetch_assoc()) {
        $currentStage = calculateLifeStage($character['age'], $character['race']);
        
        // Check if there's a previous stored life stage
        $sql = "SELECT value FROM character_metadata 
                WHERE character_id = " . intval($character['id']) . " 
                AND key_name = 'life_stage'";
        
        $metaResult = $conn->query($sql);
        
        if ($metaResult->num_rows > 0) {
            $meta = $metaResult->fetch_assoc();
            $previousStage = $meta['value'];
            
            // If life stage has changed
            if ($previousStage != $currentStage) {
                // Update the life stage
                $sql = "UPDATE character_metadata 
                        SET value = '" . $conn->real_escape_string($currentStage) . "' 
                        WHERE character_id = " . intval($character['id']) . " 
                        AND key_name = 'life_stage'";
                $conn->query($sql);
                
                // Create a major life event
                $eventDescription = $character['name'] . ' has entered a new stage of life: ' . 
                                   formatLifeStage($currentStage);
                createAgingEvent($character['id'], 'major_event', null, $eventDescription);
                
                $lifecycleChanges++;
            }
        } else {
            // First time storing life stage
            $sql = "INSERT INTO character_metadata (character_id, key_name, value) 
                    VALUES (" . intval($character['id']) . ", 'life_stage', 
                           '" . $conn->real_escape_string($currentStage) . "')";
            $conn->query($sql);
        }
    }
    
    return array(
        'success' => true, 
        'message' => "Processed $lifecycleChanges lifecycle changes"
    );
}

/**
 * Format life stage for display
 */
function formatLifeStage($stage) {
    switch ($stage) {
        case 'infant': return 'Infant';
        case 'child': return 'Child';
        case 'teenager': return 'Teenager';
        case 'young_adult': return 'Young Adult';
        case 'adult': return 'Adult';
        case 'middle_aged': return 'Middle-aged';
        case 'elder': return 'Elder';
        case 'venerable': return 'Venerable';
        default: return ucfirst($stage);
    }
}

/**
 * Process pending aging events
 */
function processAgingEvents($currentGameDate) {
    global $conn;
    
    // Get unprocessed events that are due
    $sql = "SELECT * FROM character_aging_events 
            WHERE is_processed = 0 
            AND event_date <= '" . $conn->real_escape_string($currentGameDate) . "'";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        return array('success' => false, 'message' => 'Error fetching events: ' . $conn->error);
    }
    
    $processedEvents = 0;
    
    while ($event = $result->fetch_assoc()) {
        // Process different event types
        switch ($event['event_type']) {
            case 'birthday':
                // Handle birthday events (notifications, rewards, etc.)
                handleBirthdayEvent($event);
                break;
                
            case 'attribute_change':
                // Handle attribute changes (might not need additional processing)
                break;
                
            case 'major_event':
                // Handle major events like life stage changes
                handleMajorEvent($event);
                break;
        }
        
        // Mark as processed
        $sql = "UPDATE character_aging_events 
                SET is_processed = 1 
                WHERE id = " . intval($event['id']);
        $conn->query($sql);
        
        $processedEvents++;
    }
    
    return array(
        'success' => true, 
        'message' => "Processed $processedEvents aging events"
    );
}

/**
 * Handle birthday event
 */
function handleBirthdayEvent($event) {
    global $conn;
    
    // Get character info
    $sql = "SELECT id, name, user_id FROM characters WHERE id = " . intval($event['character_id']);
    $result = $conn->query($sql);
    $character = $result->fetch_assoc();
    
    if (!$character) {
        return false;
    }
    
    // Create a notification
    $notification = "Happy Birthday to " . $character['name'] . "! " . $event['description'];
    
    // TODO: Add code to send notification to user's notification system
    
    // Give birthday rewards (if implemented)
    // giveBirthdayRewards($character['id']);
    
    return true;
}

/**
 * Handle major lifecycle event
 */
function handleMajorEvent($event) {
    global $conn;
    
    // Get character info
    $sql = "SELECT id, name, user_id FROM characters WHERE id = " . intval($event['character_id']);
    $result = $conn->query($sql);
    $character = $result->fetch_assoc();
    
    if (!$character) {
        return false;
    }
    
    // Create a notification
    $notification = "Life Event: " . $event['description'];
    
    // TODO: Add code to send notification to user's notification system
    
    return true;
}

/**
 * Get age-related attribute modifiers for a character
 */
function getAgeModifiers($characterId) {
    global $conn;
    
    // Get character's race and age
    $sql = "SELECT race, age FROM characters WHERE id = " . intval($characterId);
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        return array(); // Character not found
    }
    
    $character = $result->fetch_assoc();
    $lifeStage = calculateLifeStage($character['age'], $character['race']);
    
    // Define modifiers based on life stage
    switch ($lifeStage) {
        case 'infant':
            return array(
                'strength' => -8,
                'dexterity' => -8,
                'intelligence' => -8,
                'wisdom' => -8,
                'charisma' => 0
            );
            
        case 'child':
            return array(
                'strength' => -5,
                'dexterity' => -2,
                'intelligence' => -3,
                'wisdom' => -5,
                'charisma' => 0
            );
            
        case 'teenager':
            return array(
                'strength' => -2,
                'dexterity' => 0,
                'intelligence' => 0,
                'wisdom' => -2,
                'charisma' => 0
            );
            
        case 'young_adult':
            return array(
                'strength' => 0,
                'dexterity' => 1,
                'intelligence' => 0,
                'wisdom' => 0,
                'charisma' => 1
            );
            
        case 'adult':
            return array(
                'strength' => 0,
                'dexterity' => 0,
                'intelligence' => 1,
                'wisdom' => 1,
                'charisma' => 0
            );
            
        case 'middle_aged':
            return array(
                'strength' => -1,
                'dexterity' => -1,
                'intelligence' => 0,
                'wisdom' => 2,
                'charisma' => 0
            );
            
        case 'elder':
            return array(
                'strength' => -3,
                'dexterity' => -3,
                'intelligence' => -1,
                'wisdom' => 3,
                'charisma' => -1
            );
            
        case 'venerable':
            return array(
                'strength' => -6,
                'dexterity' => -6,
                'intelligence' => -2,
                'wisdom' => 4,
                'charisma' => -2
            );
            
        default:
            return array();
    }
}

/**
 * Get allowed activities based on character's life stage
 */
function getAllowedActivities($characterId) {
    global $conn;
    
    // Get character's race and age
    $sql = "SELECT race, age FROM characters WHERE id = " . intval($characterId);
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        return array(); // Character not found
    }
    
    $character = $result->fetch_assoc();
    $lifeStage = calculateLifeStage($character['age'], $character['race']);
    
    // Define allowed activities based on life stage
    $activities = array(
        'can_work' => false,
        'can_marry' => false,
        'can_own_property' => false,
        'can_have_children' => false,
        'can_fight' => false,
        'can_vote' => false,
        'can_drink' => false,
        'activity_description' => ''
    );
    
    switch ($lifeStage) {
        case 'infant':
            $activities['activity_description'] = 'Too young for most activities.';
            break;
            
        case 'child':
            $activities['activity_description'] = 'Can play and learn, but restricted from adult activities.';
            break;
            
        case 'teenager':
            $activities['can_work'] = true; // Basic jobs only
            $activities['can_fight'] = true; // Training only
            $activities['activity_description'] = 'Can take on apprenticeships and basic training.';
            break;
            
        case 'young_adult':
            $activities['can_work'] = true;
            $activities['can_marry'] = true;
            $activities['can_own_property'] = true;
            $activities['can_have_children'] = true;
            $activities['can_fight'] = true;
            $activities['can_vote'] = true;
            $activities['can_drink'] = true;
            $activities['activity_description'] = 'Full adult privileges.';
            break;
            
        case 'adult':
        case 'middle_aged':
            $activities['can_work'] = true;
            $activities['can_marry'] = true;
            $activities['can_own_property'] = true;
            $activities['can_have_children'] = true;
            $activities['can_fight'] = true;
            $activities['can_vote'] = true;
            $activities['can_drink'] = true;
            $activities['activity_description'] = 'Full adult privileges.';
            break;
            
        case 'elder':
            $activities['can_work'] = true; // May have penalties
            $activities['can_marry'] = true;
            $activities['can_own_property'] = true;
            $activities['can_have_children'] = false; // Too old
            $activities['can_fight'] = true; // With penalties
            $activities['can_vote'] = true;
            $activities['can_drink'] = true;
            $activities['activity_description'] = 'Some physical limitations due to age.';
            break;
            
        case 'venerable':
            $activities['can_work'] = false; // Retired
            $activities['can_marry'] = true;
            $activities['can_own_property'] = true;
            $activities['can_have_children'] = false;
            $activities['can_fight'] = false;
            $activities['can_vote'] = true;
            $activities['can_drink'] = true;
            $activities['activity_description'] = 'Significant physical limitations due to advanced age.';
            break;
    }
    
    return $activities;
} 