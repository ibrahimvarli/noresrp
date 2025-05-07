<?php
/**
 * Life System Functions
 * This file contains functions for managing character's life systems:
 * - Nutrition and hunger
 * - Sleep and fatigue
 * - Health (diseases, injuries, treatments)
 * - Hygiene and personal care
 */

/**
 * Update character's hunger status
 * Hunger increases over time and decreases when eating food
 */
function updateHunger($characterId, $timePassed = 1) {
    global $db;
    
    // Get character's current hunger
    $sql = "SELECT hunger, max_hunger FROM character_life_stats WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Initialize life stats if not exist
        initializeLifeStats($characterId);
        return;
    }
    
    $lifeStats = $result->fetch_assoc();
    
    // Increase hunger based on time passed (1 point per hour of game time)
    $newHunger = min($lifeStats['max_hunger'], $lifeStats['hunger'] + ($timePassed * 5));
    
    // Update character's hunger
    $sql = "UPDATE character_life_stats SET hunger = ? WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $newHunger, $characterId);
    $stmt->execute();
    
    // Apply effects if hunger is high
    if ($newHunger >= 70) {
        applyHungerEffects($characterId, $newHunger);
    }
}

/**
 * Character eats food to reduce hunger
 */
function eatFood($characterId, $itemId) {
    global $db;
    
    // Check if item exists in inventory
    $sql = "SELECT i.*, it.nutrition_value FROM inventory i 
            JOIN items it ON i.item_id = it.id 
            WHERE i.character_id = ? AND i.item_id = ? AND i.quantity > 0";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $characterId, $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return [
            'success' => false,
            'message' => 'You do not have this food item.'
        ];
    }
    
    $item = $result->fetch_assoc();
    
    // Check if item is food
    if ($item['nutrition_value'] <= 0) {
        return [
            'success' => false,
            'message' => 'This item cannot be eaten.'
        ];
    }
    
    // Get character's current hunger
    $sql = "SELECT hunger, health, max_health FROM character_life_stats 
            WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    $lifeStats = $stmt->get_result()->fetch_assoc();
    
    // Calculate new hunger value
    $newHunger = max(0, $lifeStats['hunger'] - $item['nutrition_value']);
    
    // Add health if eating when not full
    $healthBonus = 0;
    if ($lifeStats['hunger'] > 10) {
        $healthBonus = min(5, $item['nutrition_value'] / 5);
        $newHealth = min($lifeStats['max_health'], $lifeStats['health'] + $healthBonus);
    } else {
        $newHealth = $lifeStats['health'];
    }
    
    // Update character stats
    $sql = "UPDATE character_life_stats SET hunger = ?, health = ? WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("iii", $newHunger, $newHealth, $characterId);
    $stmt->execute();
    
    // Reduce item quantity
    $newQuantity = $item['quantity'] - 1;
    if ($newQuantity > 0) {
        $sql = "UPDATE inventory SET quantity = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ii", $newQuantity, $item['id']);
    } else {
        $sql = "DELETE FROM inventory WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $item['id']);
    }
    $stmt->execute();
    
    $message = "You ate " . $item['name'] . " and reduced your hunger.";
    if ($healthBonus > 0) {
        $message .= " You also gained " . $healthBonus . " health.";
    }
    
    return [
        'success' => true,
        'message' => $message
    ];
}

/**
 * Apply effects of hunger to character
 */
function applyHungerEffects($characterId, $hungerLevel) {
    global $db;
    
    // Higher hunger levels have negative effects
    if ($hungerLevel >= 90) {
        // Extreme hunger - health loss and stat penalties
        $sql = "UPDATE characters SET health = GREATEST(1, health - 5) WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $characterId);
        $stmt->execute();
        
        // Add hunger disease chance
        addDiseaseChance($characterId, 'malnutrition', 15);
        
    } else if ($hungerLevel >= 70) {
        // High hunger - stat penalties only
        // These will be applied when character stats are retrieved
    }
}

/**
 * Update character's fatigue based on time and activities
 */
function updateFatigue($characterId, $timePassed = 1, $activityLevel = 'normal') {
    global $db;
    
    // Get character's current fatigue level
    $sql = "SELECT fatigue, max_fatigue FROM character_life_stats WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Initialize life stats if not exist
        initializeLifeStats($characterId);
        return;
    }
    
    $lifeStats = $result->fetch_assoc();
    
    // Calculate fatigue increase based on activity level
    $fatigueIncrease = $timePassed * 3; // Base increase per hour
    switch ($activityLevel) {
        case 'resting':
            $fatigueIncrease = $timePassed * 1;
            break;
        case 'light':
            $fatigueIncrease = $timePassed * 2;
            break;
        case 'intense':
            $fatigueIncrease = $timePassed * 5;
            break;
        case 'extreme':
            $fatigueIncrease = $timePassed * 8;
            break;
    }
    
    // New fatigue value
    $newFatigue = min($lifeStats['max_fatigue'], $lifeStats['fatigue'] + $fatigueIncrease);
    
    // Update character's fatigue
    $sql = "UPDATE character_life_stats SET fatigue = ? WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $newFatigue, $characterId);
    $stmt->execute();
    
    // Apply effects if fatigue is high
    if ($newFatigue >= 70) {
        applyFatigueEffects($characterId, $newFatigue);
    }
}

/**
 * Character sleeps to reduce fatigue
 */
function characterSleep($characterId, $hours) {
    global $db;
    
    // Validate hours
    $hours = max(1, min(12, $hours));
    
    // Get character stats
    $sql = "SELECT fatigue, health, max_health FROM character_life_stats WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    $lifeStats = $stmt->get_result()->fetch_assoc();
    
    // Calculate fatigue reduction and health regeneration
    $fatigueReduction = $hours * 10;
    $newFatigue = max(0, $lifeStats['fatigue'] - $fatigueReduction);
    
    // Health regeneration from sleep
    $healthRegen = $hours * 2;
    $newHealth = min($lifeStats['max_health'], $lifeStats['health'] + $healthRegen);
    
    // Update character stats
    $sql = "UPDATE character_life_stats SET fatigue = ?, health = ? WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("iii", $newFatigue, $newHealth, $characterId);
    $stmt->execute();
    
    // Update time in game world (if you have a game time system)
    // advanceGameTime($characterId, $hours);
    
    return [
        'success' => true,
        'message' => "You slept for $hours hours and feel refreshed. Your fatigue has decreased and you've recovered some health.",
        'fatigue_reduced' => $fatigueReduction,
        'health_gained' => $healthRegen
    ];
}

/**
 * Apply effects of fatigue to character
 */
function applyFatigueEffects($characterId, $fatigueLevel) {
    global $db;
    
    if ($fatigueLevel >= 90) {
        // Extreme fatigue - health loss and stat penalties
        $sql = "UPDATE characters SET health = GREATEST(1, health - 3) WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $characterId);
        $stmt->execute();
        
        // Add exhaustion disease chance
        addDiseaseChance($characterId, 'exhaustion', 10);
        
    } else if ($fatigueLevel >= 70) {
        // High fatigue - stat penalties only
        // These will be applied when character stats are retrieved
    }
}

/**
 * Update character's hygiene level over time
 */
function updateHygiene($characterId, $timePassed = 1, $environment = 'normal') {
    global $db;
    
    // Get character's current hygiene
    $sql = "SELECT hygiene, max_hygiene FROM character_life_stats WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        initializeLifeStats($characterId);
        return;
    }
    
    $lifeStats = $result->fetch_assoc();
    
    // Calculate hygiene decrease based on environment
    $hygieneDecrease = $timePassed * 2; // Base decrease per hour
    switch ($environment) {
        case 'clean':
            $hygieneDecrease = $timePassed * 1;
            break;
        case 'dirty':
            $hygieneDecrease = $timePassed * 3;
            break;
        case 'filthy':
            $hygieneDecrease = $timePassed * 5;
            break;
    }
    
    // New hygiene value
    $newHygiene = max(0, $lifeStats['hygiene'] - $hygieneDecrease);
    
    // Update character's hygiene
    $sql = "UPDATE character_life_stats SET hygiene = ? WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $newHygiene, $characterId);
    $stmt->execute();
    
    // Apply effects if hygiene is low
    if ($newHygiene <= 30) {
        applyHygieneEffects($characterId, $newHygiene);
    }
}

/**
 * Character performs hygiene activities
 */
function performHygiene($characterId, $actionType) {
    global $db;
    
    // Get character's current hygiene
    $sql = "SELECT hygiene, max_hygiene FROM character_life_stats WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    $lifeStats = $stmt->get_result()->fetch_assoc();
    
    // Different hygiene actions have different effects
    $hygieneIncrease = 0;
    $message = "";
    
    switch ($actionType) {
        case 'quick_wash':
            $hygieneIncrease = 20;
            $message = "You quickly wash your face and hands, feeling somewhat cleaner.";
            break;
        case 'bath':
            $hygieneIncrease = 50;
            $message = "You take a refreshing bath, feeling much cleaner.";
            break;
        case 'luxury_bath':
            $hygieneIncrease = 80;
            $message = "You enjoy a luxurious bath with scented oils and feel completely refreshed.";
            break;
        case 'grooming':
            $hygieneIncrease = 30;
            $message = "You take time to groom yourself properly.";
            break;
    }
    
    // Calculate new hygiene value
    $newHygiene = min($lifeStats['max_hygiene'], $lifeStats['hygiene'] + $hygieneIncrease);
    
    // Update character stats
    $sql = "UPDATE character_life_stats SET hygiene = ? WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $newHygiene, $characterId);
    $stmt->execute();
    
    // Check if a disease should be cured due to improved hygiene
    if ($newHygiene >= 70) {
        tryToHealDisease($characterId, 'infection', 30);
    }
    
    return [
        'success' => true,
        'message' => $message,
        'hygiene_increased' => $hygieneIncrease
    ];
}

/**
 * Apply effects of poor hygiene
 */
function applyHygieneEffects($characterId, $hygieneLevel) {
    global $db;
    
    if ($hygieneLevel <= 10) {
        // Extremely poor hygiene - health loss and increased disease chance
        $sql = "UPDATE characters SET health = GREATEST(1, health - 2) WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $characterId);
        $stmt->execute();
        
        // Add disease chance
        addDiseaseChance($characterId, 'infection', 20);
        
    } else if ($hygieneLevel <= 30) {
        // Poor hygiene - minor disease chance
        addDiseaseChance($characterId, 'infection', 5);
    }
}

/**
 * Health System - Disease management
 */
function addDiseaseChance($characterId, $diseaseType, $chance) {
    // Only proceed if random chance is met
    if (rand(1, 100) > $chance) {
        return false;
    }
    
    global $db;
    
    // Check if character already has this disease
    $sql = "SELECT id FROM character_diseases WHERE character_id = ? AND disease_type = ? AND is_active = 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("is", $characterId, $diseaseType);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        // Already has disease, don't add it again
        return false;
    }
    
    // Set disease attributes based on type
    $diseaseName = "";
    $description = "";
    $severity = 1;
    $duration = 24; // hours
    
    switch ($diseaseType) {
        case 'infection':
            $diseaseName = "Infection";
            $description = "You've contracted an infection due to poor hygiene. Your health regeneration is reduced.";
            $severity = rand(1, 3);
            $duration = 24 * $severity;
            break;
        case 'malnutrition':
            $diseaseName = "Malnutrition";
            $description = "You're suffering from malnutrition due to hunger. Your stamina and strength are reduced.";
            $severity = rand(1, 3);
            $duration = 48 * $severity;
            break;
        case 'exhaustion':
            $diseaseName = "Exhaustion";
            $description = "You're suffering from exhaustion due to lack of sleep. All your stats are reduced.";
            $severity = rand(1, 3);
            $duration = 12 * $severity;
            break;
        // Add more diseases as needed
    }
    
    // Add disease to character
    $sql = "INSERT INTO character_diseases (character_id, disease_type, disease_name, description, severity, duration, is_active, contracted_at) 
            VALUES (?, ?, ?, ?, ?, ?, 1, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("isssii", $characterId, $diseaseType, $diseaseName, $description, $severity, $duration);
    
    if ($stmt->execute()) {
        // Notify the character about the disease
        return [
            'success' => true,
            'message' => "You have contracted $diseaseName: $description"
        ];
    }
    
    return false;
}

/**
 * Try to cure a disease based on conditions
 */
function tryToHealDisease($characterId, $diseaseType, $chance) {
    // Only proceed if random chance is met
    if (rand(1, 100) > $chance) {
        return false;
    }
    
    global $db;
    
    // Check if character has this disease
    $sql = "SELECT id FROM character_diseases WHERE character_id = ? AND disease_type = ? AND is_active = 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("is", $characterId, $diseaseType);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Doesn't have the disease
        return false;
    }
    
    $diseaseId = $result->fetch_assoc()['id'];
    
    // Mark disease as cured
    $sql = "UPDATE character_diseases SET is_active = 0, cured_at = NOW() WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $diseaseId);
    
    return $stmt->execute();
}

/**
 * Use medicine to treat a disease
 */
function useMedicine($characterId, $itemId) {
    global $db;
    
    // Check if item exists in inventory
    $sql = "SELECT i.*, it.treatment_type, it.treatment_power FROM inventory i 
            JOIN items it ON i.item_id = it.id 
            WHERE i.character_id = ? AND i.item_id = ? AND i.quantity > 0";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $characterId, $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return [
            'success' => false,
            'message' => 'You do not have this medicine.'
        ];
    }
    
    $item = $result->fetch_assoc();
    
    // Check if item is medicine
    if (empty($item['treatment_type'])) {
        return [
            'success' => false,
            'message' => 'This item cannot be used as medicine.'
        ];
    }
    
    // Try to cure specific disease type based on medicine
    $cureChance = 20 + ($item['treatment_power'] * 10);
    $diseaseType = $item['treatment_type'];
    
    // Use medicine
    $cured = tryToHealDisease($characterId, $diseaseType, $cureChance);
    
    // Reduce item quantity
    $newQuantity = $item['quantity'] - 1;
    if ($newQuantity > 0) {
        $sql = "UPDATE inventory SET quantity = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ii", $newQuantity, $item['id']);
    } else {
        $sql = "DELETE FROM inventory WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $item['id']);
    }
    $stmt->execute();
    
    if ($cured) {
        return [
            'success' => true,
            'message' => "You used " . $item['name'] . " and cured your disease!"
        ];
    } else {
        return [
            'success' => true,
            'message' => "You used " . $item['name'] . " but it didn't seem to have any effect."
        ];
    }
}

/**
 * Initialize character life stats
 */
function initializeLifeStats($characterId) {
    global $db;
    
    // Check if stats already exist
    $sql = "SELECT id FROM character_life_stats WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        return; // Already initialized
    }
    
    // Create default life stats
    $sql = "INSERT INTO character_life_stats (
                character_id, hunger, max_hunger, fatigue, max_fatigue, 
                hygiene, max_hygiene, health, max_health, 
                last_update
            ) VALUES (?, 0, 100, 0, 100, 100, 100, 100, 100, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
}

/**
 * Update all life system stats for a character
 * Call this periodically (e.g., on login or after time has passed)
 */
function updateAllLifeStats($characterId, $hoursPassed = 1) {
    // Update individual systems
    updateHunger($characterId, $hoursPassed);
    updateFatigue($characterId, $hoursPassed);
    updateHygiene($characterId, $hoursPassed);
    
    // Update any active diseases
    processActiveDiseasesEffects($characterId);
    
    // Update last update timestamp
    global $db;
    $sql = "UPDATE character_life_stats SET last_update = NOW() WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
}

/**
 * Process effects from active diseases
 */
function processActiveDiseasesEffects($characterId) {
    global $db;
    
    // Get all active diseases
    $sql = "SELECT * FROM character_diseases WHERE character_id = ? AND is_active = 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    $diseases = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($diseases)) {
        return; // No active diseases
    }
    
    // Check disease duration and apply effects
    foreach ($diseases as $disease) {
        // Check if disease duration has expired
        $contractedTime = strtotime($disease['contracted_at']);
        $currentTime = time();
        $hoursPassed = ($currentTime - $contractedTime) / 3600;
        
        if ($hoursPassed >= $disease['duration']) {
            // Disease has run its course
            $sql = "UPDATE character_diseases SET is_active = 0, cured_at = NOW() WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("i", $disease['id']);
            $stmt->execute();
        } else {
            // Apply disease effects based on type and severity
            applyDiseaseEffects($characterId, $disease);
        }
    }
}

/**
 * Apply effects from a specific disease
 */
function applyDiseaseEffects($characterId, $disease) {
    global $db;
    
    // Effects vary based on disease type
    switch ($disease['disease_type']) {
        case 'infection':
            // Reduce health slightly
            $healthLoss = $disease['severity'];
            $sql = "UPDATE characters SET health = GREATEST(1, health - ?) WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ii", $healthLoss, $characterId);
            $stmt->execute();
            break;
            
        case 'malnutrition':
            // Reduce strength and stamina
            $statPenalty = $disease['severity'];
            $sql = "UPDATE characters SET strength = GREATEST(1, strength - ?), 
                    energy = GREATEST(1, energy - ?) WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("iii", $statPenalty, $statPenalty, $characterId);
            $stmt->execute();
            break;
            
        case 'exhaustion':
            // Reduce all stats slightly
            $statPenalty = $disease['severity'];
            $sql = "UPDATE characters SET strength = GREATEST(1, strength - ?), 
                    dexterity = GREATEST(1, dexterity - ?),
                    intelligence = GREATEST(1, intelligence - ?),
                    wisdom = GREATEST(1, wisdom - ?) WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("iiiii", $statPenalty, $statPenalty, $statPenalty, $statPenalty, $characterId);
            $stmt->execute();
            break;
    }
}

/**
 * Get character life stats with status descriptions
 */
function getCharacterLifeStats($characterId) {
    global $db;
    
    // Make sure life stats are initialized
    initializeLifeStats($characterId);
    
    // Get stats
    $sql = "SELECT * FROM character_life_stats WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    
    // Get active diseases
    $sql = "SELECT * FROM character_diseases WHERE character_id = ? AND is_active = 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    $diseases = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Calculate status descriptions
    $hungerStatus = getHungerStatus($stats['hunger']);
    $fatigueStatus = getFatigueStatus($stats['fatigue']);
    $hygieneStatus = getHygieneStatus($stats['hygiene']);
    $healthStatus = getHealthStatus($stats['health'], $stats['max_health'], $diseases);
    
    return [
        'stats' => $stats,
        'diseases' => $diseases,
        'status' => [
            'hunger' => $hungerStatus,
            'fatigue' => $fatigueStatus,
            'hygiene' => $hygieneStatus,
            'health' => $healthStatus
        ]
    ];
}

/**
 * Get hunger status description
 */
function getHungerStatus($hungerValue) {
    if ($hungerValue <= 10) {
        return ['level' => 'satisfied', 'description' => 'Full and satisfied'];
    } else if ($hungerValue <= 30) {
        return ['level' => 'normal', 'description' => 'Not hungry'];
    } else if ($hungerValue <= 60) {
        return ['level' => 'peckish', 'description' => 'Getting hungry'];
    } else if ($hungerValue <= 80) {
        return ['level' => 'hungry', 'description' => 'Quite hungry'];
    } else {
        return ['level' => 'starving', 'description' => 'Starving'];
    }
}

/**
 * Get fatigue status description
 */
function getFatigueStatus($fatigueValue) {
    if ($fatigueValue <= 10) {
        return ['level' => 'energetic', 'description' => 'Energetic and alert'];
    } else if ($fatigueValue <= 30) {
        return ['level' => 'rested', 'description' => 'Well rested'];
    } else if ($fatigueValue <= 60) {
        return ['level' => 'normal', 'description' => 'A bit tired'];
    } else if ($fatigueValue <= 80) {
        return ['level' => 'tired', 'description' => 'Quite tired'];
    } else {
        return ['level' => 'exhausted', 'description' => 'Exhausted'];
    }
}

/**
 * Get hygiene status description
 */
function getHygieneStatus($hygieneValue) {
    if ($hygieneValue >= 90) {
        return ['level' => 'pristine', 'description' => 'Pristine and well-groomed'];
    } else if ($hygieneValue >= 70) {
        return ['level' => 'clean', 'description' => 'Clean and well-kept'];
    } else if ($hygieneValue >= 40) {
        return ['level' => 'normal', 'description' => 'Reasonably clean'];
    } else if ($hygieneValue >= 20) {
        return ['level' => 'dirty', 'description' => 'Quite dirty'];
    } else {
        return ['level' => 'filthy', 'description' => 'Filthy and unkempt'];
    }
}

/**
 * Get health status description based on health value and active diseases
 */
function getHealthStatus($healthValue, $maxHealth, $diseases) {
    $healthPercent = ($healthValue / $maxHealth) * 100;
    $status = [];
    
    // Basic health status
    if ($healthPercent >= 90) {
        $status['level'] = 'healthy';
        $status['description'] = 'In peak health';
    } else if ($healthPercent >= 70) {
        $status['level'] = 'good';
        $status['description'] = 'Healthy';
    } else if ($healthPercent >= 40) {
        $status['level'] = 'injured';
        $status['description'] = 'Injured';
    } else if ($healthPercent >= 20) {
        $status['level'] = 'wounded';
        $status['description'] = 'Badly wounded';
    } else {
        $status['level'] = 'critical';
        $status['description'] = 'Critically injured';
    }
    
    // Add disease information if any
    if (!empty($diseases)) {
        $diseaseNames = array_column($diseases, 'disease_name');
        $status['diseases'] = $diseaseNames;
        $status['description'] .= ' and suffering from ' . implode(', ', $diseaseNames);
    }
    
    return $status;
}

/**
 * Update character's thirst status
 * Thirst increases over time and decreases when drinking
 */
function updateThirst($characterId, $timePassed = 1) {
    global $db;
    
    // Get character's current thirst
    $sql = "SELECT thirst, max_thirst FROM character_life_stats WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Initialize life stats if not exist
        initializeLifeStats($characterId);
        return;
    }
    
    $lifeStats = $result->fetch_assoc();
    
    // Increase thirst based on time passed (1.5x faster than hunger)
    $newThirst = min($lifeStats['max_thirst'], $lifeStats['thirst'] + ($timePassed * 7.5));
    
    // Update character's thirst
    $sql = "UPDATE character_life_stats SET thirst = ? WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $newThirst, $characterId);
    $stmt->execute();
    
    // Apply effects if thirst is high
    if ($newThirst >= 70) {
        applyThirstEffects($characterId, $newThirst);
    }
}

/**
 * Character drinks to reduce thirst
 */
function drinkLiquid($characterId, $itemId) {
    global $db;
    
    // Check if item exists in inventory
    $sql = "SELECT i.*, it.hydration_value FROM inventory i 
            JOIN items it ON i.item_id = it.id 
            WHERE i.character_id = ? AND i.item_id = ? AND i.quantity > 0";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $characterId, $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return [
            'success' => false,
            'message' => 'You do not have this drink item.'
        ];
    }
    
    $item = $result->fetch_assoc();
    
    // Check if item is a drink
    if (!isset($item['hydration_value']) || $item['hydration_value'] <= 0) {
        return [
            'success' => false,
            'message' => 'This item cannot be drunk.'
        ];
    }
    
    // Get character's current thirst
    $sql = "SELECT thirst, health, max_health FROM character_life_stats 
            WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    $lifeStats = $stmt->get_result()->fetch_assoc();
    
    // Calculate new thirst value
    $newThirst = max(0, $lifeStats['thirst'] - $item['hydration_value']);
    
    // Add health if drinking when very thirsty
    $healthBonus = 0;
    if ($lifeStats['thirst'] > 70) {
        $healthBonus = min(3, $item['hydration_value'] / 10);
        $newHealth = min($lifeStats['max_health'], $lifeStats['health'] + $healthBonus);
    } else {
        $newHealth = $lifeStats['health'];
    }
    
    // Update character stats
    $sql = "UPDATE character_life_stats SET thirst = ?, health = ?, last_drink = NOW() WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("iii", $newThirst, $newHealth, $characterId);
    $stmt->execute();
    
    // Reduce item quantity
    $newQuantity = $item['quantity'] - 1;
    if ($newQuantity > 0) {
        $sql = "UPDATE inventory SET quantity = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ii", $newQuantity, $item['id']);
    } else {
        $sql = "DELETE FROM inventory WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $item['id']);
    }
    $stmt->execute();
    
    $message = "You drank " . $item['name'] . " and quenched your thirst.";
    if ($healthBonus > 0) {
        $message .= " You also gained " . $healthBonus . " health.";
    }
    
    return [
        'success' => true,
        'message' => $message
    ];
}

/**
 * Apply effects of thirst to character
 */
function applyThirstEffects($characterId, $thirstLevel) {
    global $db;
    
    // Higher thirst levels have negative effects
    if ($thirstLevel >= 90) {
        // Extreme thirst - health loss and stat penalties
        $sql = "UPDATE characters SET health = GREATEST(1, health - 8) WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $characterId);
        $stmt->execute();
        
        // Add dehydration disease chance
        addDiseaseChance($characterId, 'dehydration', 25);
        
    } else if ($thirstLevel >= 70) {
        // High thirst - stat penalties only
        // These will be applied when character stats are retrieved
    }
}

/**
 * Update character's happiness based on conditions
 */
function updateHappiness($characterId, $timePassed = 1) {
    global $db;
    
    // Get character's current stats
    $sql = "SELECT 
                cls.happiness, cls.max_happiness, cls.hunger, cls.thirst, cls.fatigue, cls.hygiene,
                c.location_id, c.relationship_status
            FROM character_life_stats cls
            JOIN characters c ON c.id = cls.character_id
            WHERE cls.character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Initialize life stats if not exist
        initializeLifeStats($characterId);
        return;
    }
    
    $characterStats = $result->fetch_assoc();
    
    // Base happiness change (slight decay over time)
    $happinessChange = -1 * $timePassed;
    
    // Modify based on basic needs
    if ($characterStats['hunger'] >= 80) $happinessChange -= 2 * $timePassed;
    if ($characterStats['thirst'] >= 80) $happinessChange -= 3 * $timePassed;
    if ($characterStats['fatigue'] >= 80) $happinessChange -= 2 * $timePassed;
    if ($characterStats['hygiene'] <= 20) $happinessChange -= 1 * $timePassed;
    
    // Check if character is at home (property) for bonus
    $sql = "SELECT cp.happiness_bonus
            FROM character_properties cp
            JOIN properties p ON cp.property_id = p.id
            WHERE cp.character_id = ? AND cp.is_active = 1 AND p.location_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $characterId, $characterStats['location_id']);
    $stmt->execute();
    $propertyResult = $stmt->get_result();
    
    if ($propertyResult->num_rows > 0) {
        $property = $propertyResult->fetch_assoc();
        $happinessChange += $property['happiness_bonus'] * 0.1 * $timePassed;
    }
    
    // Relationship bonus
    if ($characterStats['relationship_status'] !== 'single') {
        $happinessChange += 0.5 * $timePassed;
    }
    
    // Calculate new happiness
    $newHappiness = max(0, min($characterStats['max_happiness'], $characterStats['happiness'] + $happinessChange));
    
    // Determine mood based on happiness level
    $mood = determineCharacterMood($newHappiness, $characterStats['hunger'], $characterStats['thirst'], $characterStats['fatigue']);
    
    // Update character's happiness and mood
    $sql = "UPDATE character_life_stats SET happiness = ?, mood = ? WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("isi", $newHappiness, $mood, $characterId);
    $stmt->execute();
}

/**
 * Determine character mood based on stats
 */
function determineCharacterMood($happiness, $hunger, $thirst, $fatigue) {
    // Extreme needs override mood
    if ($hunger >= 90 || $thirst >= 90) return 'miserable';
    if ($fatigue >= 90) return 'exhausted';
    
    // Happiness-based moods
    if ($happiness >= 90) return 'ecstatic';
    if ($happiness >= 75) return 'happy';
    if ($happiness >= 60) return 'content';
    if ($happiness >= 40) return 'neutral';
    if ($happiness >= 25) return 'displeased';
    if ($happiness >= 10) return 'unhappy';
    return 'depressed';
}

/**
 * Get thirst status text
 */
function getThirstStatus($thirstValue) {
    if ($thirstValue >= 90) {
        return ['level' => 'dehydrated', 'description' => 'You are severely dehydrated and need water immediately!'];
    } else if ($thirstValue >= 70) {
        return ['level' => 'very thirsty', 'description' => 'You are very thirsty and need a drink soon.'];
    } else if ($thirstValue >= 50) {
        return ['level' => 'thirsty', 'description' => 'You could use a drink.'];
    } else if ($thirstValue >= 30) {
        return ['level' => 'slightly thirsty', 'description' => 'You\'re beginning to feel thirsty.'];
    } else {
        return ['level' => 'hydrated', 'description' => 'You are well hydrated.'];
    }
}

/**
 * Get happiness status text
 */
function getHappinessStatus($happinessValue, $mood) {
    if ($happinessValue >= 90) {
        return ['level' => 'jubilant', 'description' => 'You are practically bursting with joy!'];
    } else if ($happinessValue >= 75) {
        return ['level' => 'happy', 'description' => 'You are feeling very happy.'];
    } else if ($happinessValue >= 60) {
        return ['level' => 'content', 'description' => 'You are content with life.'];
    } else if ($happinessValue >= 40) {
        return ['level' => 'neutral', 'description' => 'You feel neither happy nor sad.'];
    } else if ($happinessValue >= 25) {
        return ['level' => 'displeased', 'description' => 'You are feeling somewhat down.'];
    } else if ($happinessValue >= 10) {
        return ['level' => 'unhappy', 'description' => 'You are quite unhappy.'];
    } else {
        return ['level' => 'depressed', 'description' => 'You are deeply unhappy and depressed.'];
    }
}

/**
 * Modify existing function to get all character life stats
 */
function getCharacterLifeStats($characterId) {
    global $db;
    
    // Get basic life stats
    $sql = "SELECT * FROM character_life_stats WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        initializeLifeStats($characterId);
        // Re-query after initialization
        $stmt->execute();
        $result = $stmt->get_result();
    }
    
    $lifeStats = $result->fetch_assoc();
    
    // Get active diseases
    $sql = "SELECT * FROM character_diseases WHERE character_id = ? AND is_active = 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    $diseases = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get formatted status descriptions
    $hungerStatus = getHungerStatus($lifeStats['hunger']);
    $fatigueStatus = getFatigueStatus($lifeStats['fatigue']);
    $hygieneStatus = getHygieneStatus($lifeStats['hygiene']);
    $healthStatus = getHealthStatus($lifeStats['health'], $lifeStats['max_health'], $diseases);
    $thirstStatus = getThirstStatus($lifeStats['thirst']);
    $happinessStatus = getHappinessStatus($lifeStats['happiness'], $lifeStats['mood']);
    
    return [
        'stats' => $lifeStats,
        'diseases' => $diseases,
        'status' => [
            'hunger' => $hungerStatus,
            'fatigue' => $fatigueStatus,
            'hygiene' => $hygieneStatus,
            'health' => $healthStatus,
            'thirst' => $thirstStatus,
            'happiness' => $happinessStatus,
            'mood' => $lifeStats['mood']
        ]
    ];
}

/**
 * Modified function to initialize life stats to include new features
 */
function initializeLifeStats($characterId) {
    global $db;
    
    // Check if stats already exist
    $sql = "SELECT id FROM character_life_stats WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        return; // Already exists
    }
    
    // Insert default values
    $sql = "INSERT INTO character_life_stats 
            (character_id, hunger, max_hunger, fatigue, max_fatigue, hygiene, max_hygiene, health, max_health, 
             happiness, max_happiness, mood, thirst, max_thirst) 
            VALUES (?, 10, 100, 5, 100, 90, 100, 100, 100, 75, 100, 'content', 15, 100)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
}

/**
 * Modified function to update all life stats with new features
 */
function updateAllLifeStats($characterId, $hoursPassed = 1) {
    // Update each stat type
    updateHunger($characterId, $hoursPassed);
    updateThirst($characterId, $hoursPassed);
    updateFatigue($characterId, $hoursPassed);
    updateHygiene($characterId, $hoursPassed);
    updateHappiness($characterId, $hoursPassed);
    
    // Process diseases
    processActiveDiseasesEffects($characterId);
    
    // Update the last update timestamp
    global $db;
    $sql = "UPDATE character_life_stats SET last_update = NOW() WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
} 