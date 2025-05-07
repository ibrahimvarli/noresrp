<?php
/**
 * Cron script to update relationships and family system
 * This script should be run daily to process:
 * - Child growth
 * - Relationship decay if no interactions
 * - Process any relationship events (anniversaries, etc.)
 */

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Define BASE_PATH as we're running from CLI
define('BASE_PATH', dirname(__DIR__));
chdir(BASE_PATH);

// Load core files
require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/db.php';
require_once BASE_PATH . '/includes/social_system.php';

// Process child growth stages
$childResult = processChildrenGrowth();
echo "Children processed: " . $childResult['children_processed'] . "\n";

// Process relationship decay
$decayResult = processRelationshipDecay();
echo "Relationships processed: " . $decayResult['relationships_processed'] . "\n";
echo "Relationships decayed: " . $decayResult['relationships_decayed'] . "\n";
echo "Relationships ended: " . $decayResult['relationships_ended'] . "\n";

// Process relationship milestones
$milestoneResult = processRelationshipMilestones();
echo "Relationship milestones processed: " . $milestoneResult['milestones_processed'] . "\n";

/**
 * Process relationship decay for inactive relationships
 */
function processRelationshipDecay() {
    global $db;
    
    // Get current game time
    $result = $db->query("SELECT current_date FROM game_time LIMIT 1");
    $currentDate = $result->fetch_assoc()['current_date'];
    
    // Inactive threshold (30 days)
    $inactiveThreshold = date('Y-m-d', strtotime($currentDate . ' - 30 days'));
    
    // Get all active relationships without recent interactions
    $sql = "SELECT * FROM character_relationships 
            WHERE status = 'active' 
            AND last_interaction < ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $inactiveThreshold);
    $stmt->execute();
    $relationships = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $processed = 0;
    $decayed = 0;
    $ended = 0;
    
    foreach ($relationships as $relationship) {
        $processed++;
        
        // Calculate days since last interaction
        $daysSinceInteraction = floor((strtotime($currentDate) - strtotime($relationship['last_interaction'])) / (60*60*24));
        
        // Determine decay amount (1 point per week of inactivity)
        $decayAmount = floor($daysSinceInteraction / 7);
        
        if ($decayAmount > 0) {
            $newLevel = max(1, $relationship['relationship_level'] - $decayAmount);
            
            // If relationship decays below certain threshold, end it
            if ($newLevel < 10 && $relationship['relationship_type'] != 'married') {
                // End the relationship
                $sql = "UPDATE character_relationships SET status = 'ended' WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("i", $relationship['id']);
                $stmt->execute();
                
                // Find and end the reciprocal relationship
                $sql = "UPDATE character_relationships 
                        SET status = 'ended' 
                        WHERE character_id = ? AND target_character_id = ? AND relationship_type = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("iis", 
                    $relationship['target_character_id'], 
                    $relationship['character_id'],
                    $relationship['relationship_type']
                );
                $stmt->execute();
                
                $ended++;
            } else {
                // Just decay the relationship
                $sql = "UPDATE character_relationships SET relationship_level = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("ii", $newLevel, $relationship['id']);
                $stmt->execute();
                
                // Update the reciprocal relationship too
                $sql = "UPDATE character_relationships 
                        SET relationship_level = ? 
                        WHERE character_id = ? AND target_character_id = ? AND relationship_type = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("iiis", 
                    $newLevel, 
                    $relationship['target_character_id'], 
                    $relationship['character_id'],
                    $relationship['relationship_type']
                );
                $stmt->execute();
                
                $decayed++;
            }
        }
    }
    
    return [
        'success' => true,
        'relationships_processed' => $processed,
        'relationships_decayed' => $decayed,
        'relationships_ended' => $ended
    ];
}

/**
 * Process relationship milestones like anniversaries
 */
function processRelationshipMilestones() {
    global $db;
    
    // Get current game time
    $result = $db->query("SELECT current_date FROM game_time LIMIT 1");
    $currentDate = $result->fetch_assoc()['current_date'];
    $currentDateObj = new DateTime($currentDate);
    
    // Get all marriages
    $sql = "SELECT cr.*, cm.marriage_date, c1.name as character_name, c2.name as target_name
            FROM character_relationships cr
            JOIN character_marriages cm ON cr.id = cm.relationship_id
            JOIN characters c1 ON cr.character_id = c1.id
            JOIN characters c2 ON cr.target_character_id = c2.id
            WHERE cr.relationship_type = 'married' AND cr.status = 'active'";
    $result = $db->query($sql);
    $marriages = $result->fetch_all(MYSQLI_ASSOC);
    
    $milestonesProcessed = 0;
    
    foreach ($marriages as $marriage) {
        // Check if today is anniversary
        $marriageDate = new DateTime($marriage['marriage_date']);
        $anniversary = false;
        
        // Same month and day = anniversary
        if ($marriageDate->format('m-d') == $currentDateObj->format('m-d') && 
            $marriageDate->format('Y') != $currentDateObj->format('Y')) {
            
            $yearsMarried = $currentDateObj->format('Y') - $marriageDate->format('Y');
            
            // Create a notification for both characters
            $message = "Today is your {$yearsMarried} year wedding anniversary with {$marriage['target_name']}!";
            
            // This would typically add a notification to the character's inbox
            // For now, we'll just log it
            error_log("Anniversary notification for {$marriage['character_name']}: $message");
            
            // Add a relationship interaction to record the anniversary
            $sql = "INSERT INTO relationship_interactions 
                    (relationship_id, interaction_type, value_change, notes) 
                    VALUES (?, 'anniversary', 10, ?)";
            $stmt = $db->prepare($sql);
            $notes = "{$yearsMarried} year anniversary";
            $stmt->bind_param("is", $marriage['id'], $notes);
            $stmt->execute();
            
            // Boost relationship level for anniversary
            $newLevel = min(100, $marriage['relationship_level'] + 5);
            $sql = "UPDATE character_relationships SET relationship_level = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ii", $newLevel, $marriage['id']);
            $stmt->execute();
            
            // Update the reciprocal relationship
            $sql = "UPDATE character_relationships 
                    SET relationship_level = ? 
                    WHERE character_id = ? AND target_character_id = ? AND relationship_type = 'married'";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("iii", $newLevel, $marriage['target_character_id'], $marriage['character_id']);
            $stmt->execute();
            
            $milestonesProcessed++;
        }
    }
    
    return [
        'success' => true,
        'milestones_processed' => $milestonesProcessed
    ];
} 