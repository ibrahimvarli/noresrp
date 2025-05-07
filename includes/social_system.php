<?php
/**
 * Social System Functions
 * This file contains functions for managing character relationships:
 * - Friendship and trust system
 * - Romantic relationships and dating
 * - Marriage system
 * - Family management and child raising
 */

/**
 * Get all relationships for a character
 */
function getCharacterRelationships($characterId, $type = null) {
    global $db;
    
    $sql = "SELECT cr.*, c.name as target_name, c.gender as target_gender, c.portrait as target_portrait 
            FROM character_relationships cr
            JOIN characters c ON cr.target_character_id = c.id
            WHERE cr.character_id = ? AND cr.status = 'active'";
    
    if ($type) {
        $sql .= " AND cr.relationship_type = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("is", $characterId, $type);
    } else {
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $characterId);
    }
    
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get a specific relationship between two characters
 */
function getRelationship($characterId, $targetCharacterId, $type = null) {
    global $db;
    
    $sql = "SELECT * FROM character_relationships 
            WHERE character_id = ? AND target_character_id = ?";
    
    if ($type) {
        $sql .= " AND relationship_type = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("iis", $characterId, $targetCharacterId, $type);
    } else {
        $sql .= " AND status = 'active'";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ii", $characterId, $targetCharacterId);
    }
    
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Start a new relationship (friendship request)
 */
function initiateRelationship($characterId, $targetCharacterId, $type = 'friend') {
    global $db;
    
    // Check if characters exist
    if (!characterExists($characterId) || !characterExists($targetCharacterId)) {
        return [
            'success' => false,
            'message' => 'One or both characters do not exist.'
        ];
    }
    
    // Check if already in a relationship
    $existingRelationship = getRelationship($characterId, $targetCharacterId, $type);
    if ($existingRelationship) {
        return [
            'success' => false,
            'message' => 'A relationship already exists between these characters.'
        ];
    }
    
    // Create the relationship
    $sql = "INSERT INTO character_relationships 
            (character_id, target_character_id, relationship_type, status) 
            VALUES (?, ?, ?, 'pending')";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("iis", $characterId, $targetCharacterId, $type);
    
    if ($stmt->execute()) {
        // Create reciprocal relationship entry
        $sql = "INSERT INTO character_relationships 
                (character_id, target_character_id, relationship_type, status) 
                VALUES (?, ?, ?, 'pending')";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("iis", $targetCharacterId, $characterId, $type);
        $stmt->execute();
        
        return [
            'success' => true,
            'message' => 'Relationship request sent!'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to create relationship: ' . $db->error
        ];
    }
}

/**
 * Accept a relationship request
 */
function acceptRelationship($relationshipId) {
    global $db;
    
    // Get relationship details
    $sql = "SELECT * FROM character_relationships WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $relationshipId);
    $stmt->execute();
    $relationship = $stmt->get_result()->fetch_assoc();
    
    if (!$relationship) {
        return [
            'success' => false,
            'message' => 'Relationship not found.'
        ];
    }
    
    // Find the reciprocal relationship
    $sql = "SELECT id FROM character_relationships 
            WHERE character_id = ? AND target_character_id = ? AND relationship_type = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("iis", $relationship['target_character_id'], $relationship['character_id'], $relationship['relationship_type']);
    $stmt->execute();
    $reciprocal = $stmt->get_result()->fetch_assoc();
    
    // Begin transaction
    $db->begin_transaction();
    
    try {
        // Update both relationships to active
        $sql = "UPDATE character_relationships SET status = 'active' WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $relationshipId);
        $stmt->execute();
        
        if ($reciprocal) {
            $stmt->bind_param("i", $reciprocal['id']);
            $stmt->execute();
        }
        
        // Add a relationship interaction
        $sql = "INSERT INTO relationship_interactions 
                (relationship_id, interaction_type, value_change) 
                VALUES (?, 'established', 10)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $relationshipId);
        $stmt->execute();
        
        $db->commit();
        
        return [
            'success' => true,
            'message' => 'Relationship accepted!'
        ];
    } catch (Exception $e) {
        $db->rollback();
        return [
            'success' => false,
            'message' => 'Failed to accept relationship: ' . $e->getMessage()
        ];
    }
}

/**
 * Decline or end a relationship
 */
function endRelationship($relationshipId) {
    global $db;
    
    // Get relationship details
    $sql = "SELECT * FROM character_relationships WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $relationshipId);
    $stmt->execute();
    $relationship = $stmt->get_result()->fetch_assoc();
    
    if (!$relationship) {
        return [
            'success' => false,
            'message' => 'Relationship not found.'
        ];
    }
    
    // Find the reciprocal relationship
    $sql = "SELECT id FROM character_relationships 
            WHERE character_id = ? AND target_character_id = ? AND relationship_type = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("iis", $relationship['target_character_id'], $relationship['character_id'], $relationship['relationship_type']);
    $stmt->execute();
    $reciprocal = $stmt->get_result()->fetch_assoc();
    
    // Begin transaction
    $db->begin_transaction();
    
    try {
        // Update both relationships to ended
        $sql = "UPDATE character_relationships SET status = 'ended' WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $relationshipId);
        $stmt->execute();
        
        if ($reciprocal) {
            $stmt->bind_param("i", $reciprocal['id']);
            $stmt->execute();
        }
        
        // Add a relationship interaction for record keeping
        $sql = "INSERT INTO relationship_interactions 
                (relationship_id, interaction_type, value_change) 
                VALUES (?, 'ended', 0)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $relationshipId);
        $stmt->execute();
        
        $db->commit();
        
        return [
            'success' => true,
            'message' => 'Relationship ended.'
        ];
    } catch (Exception $e) {
        $db->rollback();
        return [
            'success' => false,
            'message' => 'Failed to end relationship: ' . $e->getMessage()
        ];
    }
}

/**
 * Perform a social interaction with another character
 */
function performSocialInteraction($characterId, $targetCharacterId, $interactionType) {
    global $db;
    
    // Check if both characters exist
    if (!characterExists($characterId) || !characterExists($targetCharacterId)) {
        return [
            'success' => false,
            'message' => 'One or both characters do not exist.'
        ];
    }
    
    // Get interaction details
    $sql = "SELECT * FROM social_interaction_types WHERE name = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $interactionType);
    $stmt->execute();
    $interaction = $stmt->get_result()->fetch_assoc();
    
    if (!$interaction) {
        return [
            'success' => false,
            'message' => 'Invalid interaction type.'
        ];
    }
    
    // Check if there's an active relationship
    $sql = "SELECT * FROM character_relationships 
            WHERE character_id = ? AND target_character_id = ? AND status = 'active'";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $characterId, $targetCharacterId);
    $stmt->execute();
    $relationship = $stmt->get_result()->fetch_assoc();
    
    // If no relationship exists, check if this interaction allows for initial contact
    if (!$relationship && $interaction['requires_relationship_level'] > 0) {
        return [
            'success' => false,
            'message' => 'You need to establish a relationship first.'
        ];
    }
    
    // Check if the interaction has a cooldown
    if ($relationship) {
        $sql = "SELECT * FROM relationship_interactions 
                WHERE relationship_id = ? AND interaction_type = ? 
                AND interaction_date > DATE_SUB(NOW(), INTERVAL ? HOUR)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("isi", $relationship['id'], $interactionType, $interaction['cooldown_hours']);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            return [
                'success' => false,
                'message' => 'You need to wait before doing this interaction again.'
            ];
        }
    }
    
    // Begin transaction
    $db->begin_transaction();
    
    try {
        // If no relationship exists but the interaction is valid, create one
        if (!$relationship && $interaction['requires_relationship_level'] == 0) {
            // For initial interactions, typically start with friendship
            $sql = "INSERT INTO character_relationships 
                    (character_id, target_character_id, relationship_type, relationship_level, status) 
                    VALUES (?, ?, 'friend', 1, 'active')";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ii", $characterId, $targetCharacterId);
            $stmt->execute();
            $relationshipId = $db->insert_id;
            
            // Create reciprocal relationship
            $sql = "INSERT INTO character_relationships 
                    (character_id, target_character_id, relationship_type, relationship_level, status) 
                    VALUES (?, ?, 'friend', 1, 'active')";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ii", $targetCharacterId, $characterId);
            $stmt->execute();
        } else {
            $relationshipId = $relationship['id'];
        }
        
        // Update relationship level if it exists
        if ($relationship) {
            $newLevel = min(100, max(1, $relationship['relationship_level'] + $interaction['relationship_points']));
            
            $sql = "UPDATE character_relationships 
                    SET relationship_level = ?, last_interaction = NOW() 
                    WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ii", $newLevel, $relationship['id']);
            $stmt->execute();
            
            // Update the reciprocal relationship too
            $sql = "UPDATE character_relationships 
                    SET relationship_level = ?, last_interaction = NOW() 
                    WHERE character_id = ? AND target_character_id = ? AND relationship_type = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("iiis", $newLevel, $targetCharacterId, $characterId, $relationship['relationship_type']);
            $stmt->execute();
        }
        
        // Record the interaction
        $sql = "INSERT INTO relationship_interactions 
                (relationship_id, interaction_type, value_change) 
                VALUES (?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("isi", $relationshipId, $interactionType, $interaction['relationship_points']);
        $stmt->execute();
        
        $db->commit();
        
        return [
            'success' => true,
            'message' => 'Interaction successful!',
            'points_gained' => $interaction['relationship_points']
        ];
    } catch (Exception $e) {
        $db->rollback();
        return [
            'success' => false,
            'message' => 'Failed to perform interaction: ' . $e->getMessage()
        ];
    }
}

/**
 * Upgrade a relationship (friendship to dating, dating to engaged, etc.)
 */
function upgradeRelationship($relationshipId, $newType) {
    global $db;
    
    // Get the current relationship
    $sql = "SELECT * FROM character_relationships WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $relationshipId);
    $stmt->execute();
    $relationship = $stmt->get_result()->fetch_assoc();
    
    if (!$relationship) {
        return [
            'success' => false,
            'message' => 'Relationship not found.'
        ];
    }
    
    // Validate the relationship progression
    $validProgressions = [
        'friend' => ['dating', 'best_friend'],
        'dating' => ['engaged'],
        'engaged' => ['married'],
        'best_friend' => ['dating']
    ];
    
    if (!isset($validProgressions[$relationship['relationship_type']]) || 
        !in_array($newType, $validProgressions[$relationship['relationship_type']])) {
        return [
            'success' => false,
            'message' => 'Invalid relationship progression.'
        ];
    }
    
    // Check minimum relationship level requirements
    $minLevels = [
        'best_friend' => 70,
        'dating' => 50,
        'engaged' => 80,
        'married' => 90
    ];
    
    if ($relationship['relationship_level'] < $minLevels[$newType]) {
        return [
            'success' => false,
            'message' => 'Your relationship level is too low for this change.'
        ];
    }
    
    // Find the reciprocal relationship
    $sql = "SELECT id FROM character_relationships 
            WHERE character_id = ? AND target_character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $relationship['target_character_id'], $relationship['character_id']);
    $stmt->execute();
    $reciprocal = $stmt->get_result()->fetch_assoc();
    
    // Begin transaction
    $db->begin_transaction();
    
    try {
        // Update the relationship type
        $sql = "UPDATE character_relationships SET relationship_type = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("si", $newType, $relationshipId);
        $stmt->execute();
        
        // Update the reciprocal relationship
        if ($reciprocal) {
            $stmt->bind_param("si", $newType, $reciprocal['id']);
            $stmt->execute();
        }
        
        // Record the change as an interaction
        $sql = "INSERT INTO relationship_interactions 
                (relationship_id, interaction_type, value_change) 
                VALUES (?, ?, 10)";
        $stmt = $db->prepare($sql);
        $interactionType = 'upgrade_to_' . $newType;
        $stmt->bind_param("is", $relationshipId, $interactionType);
        $stmt->execute();
        
        // If upgrading to married, create marriage record
        if ($newType == 'married') {
            createMarriage($relationship);
        }
        
        // Update character relationship status
        $sql = "UPDATE characters SET relationship_status = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("si", $newType, $relationship['character_id']);
        $stmt->execute();
        
        $stmt->bind_param("si", $newType, $relationship['target_character_id']);
        $stmt->execute();
        
        $db->commit();
        
        $message = 'Relationship upgraded to ' . str_replace('_', ' ', $newType) . '!';
        if ($newType == 'engaged') {
            $message = 'Congratulations on your engagement!';
        } else if ($newType == 'married') {
            $message = 'Congratulations on your marriage!';
        }
        
        return [
            'success' => true,
            'message' => $message
        ];
    } catch (Exception $e) {
        $db->rollback();
        return [
            'success' => false,
            'message' => 'Failed to upgrade relationship: ' . $e->getMessage()
        ];
    }
}

/**
 * Create a marriage record
 */
function createMarriage($relationship) {
    global $db;
    
    // Get game time for marriage date
    $sql = "SELECT current_date FROM game_time LIMIT 1";
    $result = $db->query($sql);
    $gameTime = $result->fetch_assoc();
    $marriageDate = $gameTime['current_date'];
    
    // Create marriage record
    $sql = "INSERT INTO character_marriages 
            (relationship_id, marriage_date, status) 
            VALUES (?, ?, 'active')";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("is", $relationship['id'], $marriageDate);
    return $stmt->execute();
}

/**
 * Plan a wedding event
 */
function planWedding($characterId, $partnerId, $locationId, $date, $isPrivate = false, $maxAttendees = 0) {
    global $db;
    
    // Verify characters are engaged
    $sql = "SELECT * FROM character_relationships 
            WHERE character_id = ? AND target_character_id = ? 
            AND relationship_type = 'engaged' AND status = 'active'";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $characterId, $partnerId);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        return [
            'success' => false,
            'message' => 'You must be engaged before planning a wedding.'
        ];
    }
    
    // Create the wedding event
    $sql = "INSERT INTO social_events 
            (title, description, host_character_id, location_id, event_type, 
            start_date, end_date, status, is_private, max_attendees) 
            VALUES (?, ?, ?, ?, 'wedding', ?, ?, 'planned', ?, ?)";
    
    // Calculate end time (6 hours after start)
    $endDate = date('Y-m-d H:i:s', strtotime($date . ' + 6 hours'));
    
    $stmt = $db->prepare($sql);
    $title = "Wedding of " . getCharacterName($characterId) . " and " . getCharacterName($partnerId);
    $description = "Join us to celebrate our special day!";
    $stmt->bind_param("ssiissii", $title, $description, $characterId, $locationId, $date, $endDate, $isPrivate, $maxAttendees);
    
    if ($stmt->execute()) {
        $eventId = $db->insert_id;
        
        // Add both partners as attendees
        $sql = "INSERT INTO social_event_attendees (event_id, character_id, status) 
                VALUES (?, ?, 'attending')";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ii", $eventId, $characterId);
        $stmt->execute();
        
        $stmt->bind_param("ii", $eventId, $partnerId);
        $stmt->execute();
        
        return [
            'success' => true,
            'message' => 'Wedding planned successfully!',
            'event_id' => $eventId
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to plan wedding: ' . $db->error
        ];
    }
}

/**
 * Helper function to check if character exists
 */
function characterExists($characterId) {
    global $db;
    
    $sql = "SELECT id FROM characters WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    
    return $stmt->get_result()->num_rows > 0;
}

/**
 * Helper function to get character name
 */
function getCharacterName($characterId) {
    global $db;
    
    $sql = "SELECT name FROM characters WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['name'];
    }
    
    return 'Unknown Character';
}

/**
 * Get pending relationship requests for a character
 */
function getPendingRelationshipRequests($characterId) {
    global $db;
    
    $sql = "SELECT cr.*, c.name as sender_name, c.portrait as sender_portrait 
            FROM character_relationships cr
            JOIN characters c ON cr.character_id = c.id
            WHERE cr.target_character_id = ? AND cr.status = 'pending'";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get all potential social interaction targets (other characters)
 */
function getPotentialSocialTargets($characterId, $locationId = null) {
    global $db;
    
    $sql = "SELECT c.id, c.name, c.gender, c.race, c.portrait, c.level 
            FROM characters c
            WHERE c.id != ?";
    
    if ($locationId) {
        $sql .= " AND c.location_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ii", $characterId, $locationId);
    } else {
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $characterId);
    }
    
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get all available social interactions
 */
function getAvailableSocialInteractions($characterId, $targetId) {
    global $db;
    
    // Get the relationship
    $sql = "SELECT * FROM character_relationships 
            WHERE character_id = ? AND target_character_id = ? AND status = 'active'";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $characterId, $targetId);
    $stmt->execute();
    $relationship = $stmt->get_result()->fetch_assoc();
    
    // Get all interaction types
    $sql = "SELECT * FROM social_interaction_types WHERE 1=1";
    
    // If relationship exists, filter by level and type
    if ($relationship) {
        $sql .= " AND (requires_relationship_level <= ? OR requires_relationship_level IS NULL)";
        if ($relationship['relationship_type']) {
            $sql .= " AND (requires_relationship_type = ? OR requires_relationship_type IS NULL)";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("is", $relationship['relationship_level'], $relationship['relationship_type']);
        } else {
            $stmt = $db->prepare($sql);
            $stmt->bind_param("i", $relationship['relationship_level']);
        }
    } else {
        // If no relationship, just get initial interactions
        $sql .= " AND (requires_relationship_level = 0 OR requires_relationship_level IS NULL)";
        $stmt = $db->prepare($sql);
    }
    
    $stmt->execute();
    $interactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // If relationship exists, check cooldowns
    if ($relationship) {
        foreach ($interactions as &$interaction) {
            $sql = "SELECT * FROM relationship_interactions 
                    WHERE relationship_id = ? AND interaction_type = ? 
                    AND interaction_date > DATE_SUB(NOW(), INTERVAL ? HOUR)";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("isi", $relationship['id'], $interaction['name'], $interaction['cooldown_hours']);
            $stmt->execute();
            
            $interaction['on_cooldown'] = ($stmt->get_result()->num_rows > 0);
        }
    }
    
    return $interactions;
}

/**
 * Get family members for a character
 */
function getCharacterFamily($characterId) {
    global $db;
    
    $family = [];
    
    // Get spouse
    $sql = "SELECT cr.*, c.name, c.portrait 
            FROM character_relationships cr
            JOIN characters c ON cr.target_character_id = c.id
            WHERE cr.character_id = ? AND cr.relationship_type = 'married' AND cr.status = 'active'";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    $spouse = $stmt->get_result()->fetch_assoc();
    
    if ($spouse) {
        $family['spouse'] = $spouse;
    }
    
    // Get children
    $sql = "SELECT cc.*, c.name, c.gender, c.portrait, c.age 
            FROM character_children cc
            JOIN characters c ON cc.child_character_id = c.id
            WHERE (cc.parent1_character_id = ? OR cc.parent2_character_id = ?)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $characterId, $characterId);
    $stmt->execute();
    $children = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (!empty($children)) {
        $family['children'] = $children;
    }
    
    // Get parents
    $sql = "SELECT cc.*, c.name, c.gender, c.portrait
            FROM character_children cc
            JOIN characters c ON CASE 
                WHEN cc.parent1_character_id = ? THEN c.id = cc.parent2_character_id
                ELSE c.id = cc.parent1_character_id
            END
            WHERE cc.child_character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $characterId, $characterId);
    $stmt->execute();
    $parents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (!empty($parents)) {
        $family['parents'] = $parents;
    }
    
    return $family;
}

/**
 * Parent-child interaction
 */
function performParentingActivity($parentId, $childId, $activityType) {
    global $db;
    
    // Verify parent-child relationship
    $sql = "SELECT * FROM character_children 
            WHERE child_character_id = ? AND (parent1_character_id = ? OR parent2_character_id = ?)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("iii", $childId, $parentId, $parentId);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        return [
            'success' => false,
            'message' => 'You are not a parent of this child.'
        ];
    }
    
    // Define activity benefits
    $benefits = [
        'play' => [
            'benefit' => 'happiness',
            'skill' => 'parenting'
        ],
        'teach' => [
            'benefit' => 'intelligence',
            'skill' => 'education'
        ],
        'care' => [
            'benefit' => 'health',
            'skill' => 'caregiving'
        ],
        'discipline' => [
            'benefit' => 'responsibility',
            'skill' => 'leadership'
        ],
        'adventure' => [
            'benefit' => 'courage',
            'skill' => 'exploration'
        ]
    ];
    
    if (!isset($benefits[$activityType])) {
        return [
            'success' => false,
            'message' => 'Invalid activity type.'
        ];
    }
    
    // Record the activity
    $sql = "INSERT INTO child_raising_activities 
            (child_id, parent_character_id, activity_type, benefit, skill_gain) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $benefit = $benefits[$activityType]['benefit'];
    $skill = $benefits[$activityType]['skill'];
    $stmt->bind_param("iisss", $childId, $parentId, $activityType, $benefit, $skill);
    
    if ($stmt->execute()) {
        // Potentially update child stats based on activity
        // This would depend on how child stats are tracked
        
        return [
            'success' => true,
            'message' => 'You spent quality time with your child.',
            'benefit' => $benefit,
            'skill_gain' => $skill
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to record activity: ' . $db->error
        ];
    }
}

/**
 * Create a child (both for NPC children and player-controlled siblings)
 */
function createChild($parent1Id, $parent2Id = null, $childName = null, $gender = null, $isNPC = true) {
    global $db;
    
    // Verify parents exist
    if (!characterExists($parent1Id) || ($parent2Id && !characterExists($parent2Id))) {
        return [
            'success' => false,
            'message' => 'One or both parents do not exist.'
        ];
    }
    
    // If no name provided, generate one
    if (!$childName) {
        // Get random name based on gender
        $sql = "SELECT name FROM child_names 
                WHERE gender = ? OR gender = 'neutral'
                ORDER BY RAND() LIMIT 1";
        $stmt = $db->prepare($sql);
        $genderParam = $gender ?: 'neutral';
        $stmt->bind_param("s", $genderParam);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $childName = $result->fetch_assoc()['name'];
        } else {
            $childName = "Child of " . getCharacterName($parent1Id);
        }
    }
    
    // Begin transaction
    $db->begin_transaction();
    
    try {
        // If NPC child, create a minimal character record
        if ($isNPC) {
            // Get parent data for race inheritance
            $sql = "SELECT race, user_id FROM characters WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("i", $parent1Id);
            $stmt->execute();
            $parent = $stmt->get_result()->fetch_assoc();
            
            // Create child character
            $sql = "INSERT INTO characters 
                    (user_id, name, race, gender, age, level, is_active) 
                    VALUES (?, ?, ?, ?, 0, 1, 0)";
            $stmt = $db->prepare($sql);
            $genderValue = $gender ?: 'neutral';
            $stmt->bind_param("isss", $parent['user_id'], $childName, $parent['race'], $genderValue);
            $stmt->execute();
            $childId = $db->insert_id;
            
            // Initialize child stats
            initializeLifeStats($childId);
        } else {
            // For player-controlled sibling, they should already have a character ID
            $childId = $childName; // In this case, childName is actually the ID
        }
        
        // Get current game date
        $sql = "SELECT current_date FROM game_time LIMIT 1";
        $result = $db->query($sql);
        $gameDate = $result->fetch_assoc()['current_date'];
        
        // Calculate growth stage change date (3 months later)
        $nextGrowthDate = date('Y-m-d', strtotime($gameDate . ' + 3 months'));
        
        // Create child record
        $sql = "INSERT INTO character_children 
                (child_character_id, parent1_character_id, parent2_character_id, 
                birth_date, is_npc, growth_stage, next_growth_date) 
                VALUES (?, ?, ?, ?, ?, 'infant', ?)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("iiisss", $childId, $parent1Id, $parent2Id, $gameDate, $isNPC, $nextGrowthDate);
        $stmt->execute();
        
        $db->commit();
        
        return [
            'success' => true,
            'message' => 'Child created successfully!',
            'child_id' => $childId,
            'child_name' => $childName
        ];
    } catch (Exception $e) {
        $db->rollback();
        return [
            'success' => false,
            'message' => 'Failed to create child: ' . $e->getMessage()
        ];
    }
}

/**
 * Process growth stages for all children
 * Should be called by a cron job daily
 */
function processChildrenGrowth() {
    global $db;
    
    // Get current game date
    $sql = "SELECT current_date FROM game_time LIMIT 1";
    $result = $db->query($sql);
    $currentDate = $result->fetch_assoc()['current_date'];
    
    // Get children due for growth
    $sql = "SELECT * FROM character_children 
            WHERE next_growth_date <= ? AND growth_stage != 'adult'";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $currentDate);
    $stmt->execute();
    $children = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $processed = 0;
    
    foreach ($children as $child) {
        // Determine next growth stage
        $nextStage = '';
        switch ($child['growth_stage']) {
            case 'infant':
                $nextStage = 'toddler';
                $ageYears = 3;
                $nextInterval = '2 years';
                break;
            case 'toddler':
                $nextStage = 'child';
                $ageYears = 5;
                $nextInterval = '5 years';
                break;
            case 'child':
                $nextStage = 'teen';
                $ageYears = 10;
                $nextInterval = '5 years';
                break;
            case 'teen':
                $nextStage = 'adult';
                $ageYears = 15;
                $nextInterval = null;
                break;
        }
        
        // Calculate next growth date
        $nextGrowthDate = null;
        if ($nextInterval) {
            $nextGrowthDate = date('Y-m-d', strtotime($currentDate . ' + ' . $nextInterval));
        }
        
        // Update child record
        $sql = "UPDATE character_children 
                SET growth_stage = ?, next_growth_date = ? 
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ssi", $nextStage, $nextGrowthDate, $child['id']);
        
        if ($stmt->execute()) {
            // Update character age
            $sql = "UPDATE characters SET age = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ii", $ageYears, $child['child_character_id']);
            $stmt->execute();
            
            $processed++;
            
            // If child became adult, potentially unlock playable character
            if ($nextStage == 'adult' && $child['is_npc']) {
                // Logic to potentially make child playable
            }
        }
    }
    
    return [
        'success' => true,
        'children_processed' => $processed
    ];
} 