<?php
/**
 * Social System
 * Core functions for handling relationships, marriages, children, and social events
 */

// Get all relationships for a character
function getCharacterRelationships($character_id) {
    global $conn;
    
    $sql = "SELECT cr.*, c.name, c.id as target_id, c.race, c.gender, c.age
            FROM character_relationships cr
            JOIN characters c ON cr.target_character_id = c.id
            WHERE cr.character_id = " . intval($character_id) . "
            AND cr.status = 'active'
            ORDER BY cr.relationship_level DESC";
    
    $result = $conn->query($sql);
    $relationships = array();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $relationships[] = $row;
        }
    }
    
    return $relationships;
}

// Get a specific relationship between two characters
function getRelationship($character_id, $target_character_id, $relationship_type = null) {
    global $conn;
    
    $sql = "SELECT * FROM character_relationships 
            WHERE character_id = " . intval($character_id) . " 
            AND target_character_id = " . intval($target_character_id);
    
    if ($relationship_type) {
        $sql .= " AND relationship_type = '" . $conn->real_escape_string($relationship_type) . "'";
    }
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return false;
}

// Create or update a relationship
function updateRelationship($character_id, $target_character_id, $relationship_type, $level = 1) {
    global $conn;
    
    // Check if relationship already exists
    $existing = getRelationship($character_id, $target_character_id, $relationship_type);
    
    if ($existing) {
        // Update existing relationship
        $sql = "UPDATE character_relationships 
                SET relationship_level = " . intval($level) . ",
                    last_interaction = NOW()
                WHERE id = " . intval($existing['id']);
        
        if ($conn->query($sql)) {
            return array('success' => true, 'message' => 'Relationship updated successfully');
        } else {
            return array('success' => false, 'message' => 'Error updating relationship');
        }
    } else {
        // Create new relationship
        $sql = "INSERT INTO character_relationships 
                (character_id, target_character_id, relationship_type, relationship_level, status) 
                VALUES (" . intval($character_id) . ", " . intval($target_character_id) . ", 
                '" . $conn->real_escape_string($relationship_type) . "', " . intval($level) . ", 'active')";
        
        if ($conn->query($sql)) {
            // Create reciprocal relationship if needed (for friendship/romance)
            if ($relationship_type == 'friend' || $relationship_type == 'dating') {
                $sql = "INSERT INTO character_relationships 
                        (character_id, target_character_id, relationship_type, relationship_level, status) 
                        VALUES (" . intval($target_character_id) . ", " . intval($character_id) . ", 
                        '" . $conn->real_escape_string($relationship_type) . "', " . intval($level) . ", 'active')";
                $conn->query($sql);
            }
            
            return array('success' => true, 'message' => 'Relationship created successfully');
        } else {
            return array('success' => false, 'message' => 'Error creating relationship');
        }
    }
}

// Record a social interaction between characters
function recordInteraction($relationship_id, $interaction_type, $value_change = 0, $location_id = null, $notes = '') {
    global $conn;
    
    $sql = "INSERT INTO relationship_interactions 
            (relationship_id, interaction_type, value_change, location_id, notes) 
            VALUES (" . intval($relationship_id) . ", '" . $conn->real_escape_string($interaction_type) . "', 
            " . intval($value_change) . ", " . ($location_id ? intval($location_id) : 'NULL') . ", 
            '" . $conn->real_escape_string($notes) . "')";
    
    if ($conn->query($sql)) {
        // Update relationship level
        $sql = "UPDATE character_relationships 
                SET relationship_level = relationship_level + " . intval($value_change) . ",
                    last_interaction = NOW()
                WHERE id = " . intval($relationship_id);
        $conn->query($sql);
        
        return array('success' => true, 'message' => 'Interaction recorded successfully');
    } else {
        return array('success' => false, 'message' => 'Error recording interaction');
    }
}

// Get available social interaction types
function getAvailableInteractions($character_id, $target_character_id) {
    global $conn;
    
    // Get current relationship to determine available interactions
    $relationships = array();
    $result = $conn->query("SELECT * FROM character_relationships 
                          WHERE character_id = " . intval($character_id) . " 
                          AND target_character_id = " . intval($target_character_id) . "
                          AND status = 'active'");
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $relationships[$row['relationship_type']] = $row;
        }
    }
    
    // Get all interaction types
    $sql = "SELECT * FROM social_interaction_types";
    $result = $conn->query($sql);
    $all_interactions = array();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $all_interactions[] = $row;
        }
    }
    
    // Filter based on current relationship
    $available_interactions = array();
    foreach ($all_interactions as $interaction) {
        $required_type = $interaction['requires_relationship_type'];
        $required_level = $interaction['requires_relationship_level'];
        
        // Check if this interaction requires specific relationship type
        if (!empty($required_type) && !isset($relationships[$required_type])) {
            continue;
        }
        
        // Check if relationship level is high enough
        if ($required_level > 0 && isset($relationships[$required_type]) && 
            $relationships[$required_type]['relationship_level'] < $required_level) {
            continue;
        }
        
        // Check cooldown period
        if (isset($relationships[$required_type])) {
            $relationship_id = $relationships[$required_type]['id'];
            $cooldown_hours = $interaction['cooldown_hours'];
            
            $sql = "SELECT id FROM relationship_interactions 
                    WHERE relationship_id = " . intval($relationship_id) . " 
                    AND interaction_type = '" . $conn->real_escape_string($interaction['name']) . "'
                    AND interaction_date > DATE_SUB(NOW(), INTERVAL " . intval($cooldown_hours) . " HOUR)";
            
            $cooldown_check = $conn->query($sql);
            if ($cooldown_check->num_rows > 0) {
                // Interaction is on cooldown
                continue;
            }
        }
        
        $available_interactions[] = $interaction;
    }
    
    return $available_interactions;
}

// Get recent interactions history
function getInteractionHistory($relationship_id, $limit = 10) {
    global $conn;
    
    $sql = "SELECT ri.*, sit.relationship_points, l.name as location_name
            FROM relationship_interactions ri
            LEFT JOIN social_interaction_types sit ON ri.interaction_type = sit.name
            LEFT JOIN locations l ON ri.location_id = l.id
            WHERE ri.relationship_id = " . intval($relationship_id) . "
            ORDER BY ri.interaction_date DESC
            LIMIT " . intval($limit);
    
    $result = $conn->query($sql);
    $interactions = array();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $interactions[] = $row;
        }
    }
    
    return $interactions;
}

// MARRIAGE FUNCTIONS

// Check if character is married
function isMarried($character_id) {
    global $conn;
    
    $sql = "SELECT cr.id FROM character_relationships cr
            LEFT JOIN character_marriages cm ON cr.id = cm.relationship_id
            WHERE cr.character_id = " . intval($character_id) . "
            AND cr.relationship_type = 'married'
            AND cr.status = 'active'
            AND (cm.status IS NULL OR cm.status = 'active')";
    
    $result = $conn->query($sql);
    
    return ($result->num_rows > 0);
}

// Get marriage details
function getMarriageDetails($character_id) {
    global $conn;
    
    $sql = "SELECT cr.*, cm.*, c.name, c.id as spouse_id, c.gender, c.race, c.age,
                   p.name as property_name, l.name as ceremony_location
            FROM character_relationships cr
            JOIN character_marriages cm ON cr.id = cm.relationship_id
            JOIN characters c ON cr.target_character_id = c.id
            LEFT JOIN properties p ON cm.property_id = p.id
            LEFT JOIN locations l ON cm.ceremony_location_id = l.id
            WHERE cr.character_id = " . intval($character_id) . "
            AND cr.relationship_type = 'married'
            AND cr.status = 'active'
            AND cm.status = 'active'";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return false;
}

// Create marriage between characters
function createMarriage($character_id, $target_character_id, $ceremony_location_id = null, $property_id = null) {
    global $conn;
    
    // Check if either character is already married
    if (isMarried($character_id) || isMarried($target_character_id)) {
        return array('success' => false, 'message' => 'One or both characters are already married');
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Create relationship records
        $result1 = updateRelationship($character_id, $target_character_id, 'married', 80);
        $result2 = updateRelationship($target_character_id, $character_id, 'married', 80);
        
        if (!$result1['success'] || !$result2['success']) {
            throw new Exception('Error creating relationship records');
        }
        
        // Get relationship IDs
        $rel1 = getRelationship($character_id, $target_character_id, 'married');
        $rel2 = getRelationship($target_character_id, $character_id, 'married');
        
        if (!$rel1 || !$rel2) {
            throw new Exception('Error retrieving relationship records');
        }
        
        // Create marriage record
        $sql = "INSERT INTO character_marriages 
                (relationship_id, marriage_date, ceremony_location_id, property_id, status)
                VALUES (" . intval($rel1['id']) . ", CURDATE(), " . 
                ($ceremony_location_id ? intval($ceremony_location_id) : 'NULL') . ", " .
                ($property_id ? intval($property_id) : 'NULL') . ", 'active')";
        
        if (!$conn->query($sql)) {
            throw new Exception('Error creating primary marriage record');
        }
        
        // Create second marriage record (linked to second relationship)
        $sql = "INSERT INTO character_marriages 
                (relationship_id, marriage_date, ceremony_location_id, property_id, status)
                VALUES (" . intval($rel2['id']) . ", CURDATE(), " . 
                ($ceremony_location_id ? intval($ceremony_location_id) : 'NULL') . ", " .
                ($property_id ? intval($property_id) : 'NULL') . ", 'active')";
        
        if (!$conn->query($sql)) {
            throw new Exception('Error creating secondary marriage record');
        }
        
        // Update characters' relationship status
        $sql = "UPDATE characters SET relationship_status = 'married' 
                WHERE id IN (" . intval($character_id) . ", " . intval($target_character_id) . ")";
        
        if (!$conn->query($sql)) {
            throw new Exception('Error updating character relationship status');
        }
        
        // Commit transaction
        $conn->commit();
        
        return array('success' => true, 'message' => 'Marriage created successfully');
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return array('success' => false, 'message' => 'Error: ' . $e->getMessage());
    }
}

// End a marriage (divorce)
function endMarriage($character_id, $target_character_id) {
    global $conn;
    
    // Get marriage details
    $marriage = getMarriageDetails($character_id);
    
    if (!$marriage) {
        return array('success' => false, 'message' => 'Marriage not found');
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update marriage status
        $sql = "UPDATE character_marriages 
                SET status = 'divorced', divorce_date = CURDATE()
                WHERE relationship_id IN (
                    SELECT id FROM character_relationships
                    WHERE (character_id = " . intval($character_id) . " AND target_character_id = " . intval($target_character_id) . ")
                    OR (character_id = " . intval($target_character_id) . " AND target_character_id = " . intval($character_id) . ")
                )";
        
        if (!$conn->query($sql)) {
            throw new Exception('Error updating marriage records');
        }
        
        // Change relationship type to ex_spouse
        $sql = "UPDATE character_relationships 
                SET relationship_type = 'ex_spouse', relationship_level = relationship_level / 2
                WHERE (character_id = " . intval($character_id) . " AND target_character_id = " . intval($target_character_id) . ")
                OR (character_id = " . intval($target_character_id) . " AND target_character_id = " . intval($character_id) . ")";
        
        if (!$conn->query($sql)) {
            throw new Exception('Error updating relationship records');
        }
        
        // Update characters' relationship status
        $sql = "UPDATE characters SET relationship_status = 'single' 
                WHERE id IN (" . intval($character_id) . ", " . intval($target_character_id) . ")";
        
        if (!$conn->query($sql)) {
            throw new Exception('Error updating character relationship status');
        }
        
        // Commit transaction
        $conn->commit();
        
        return array('success' => true, 'message' => 'Divorce completed successfully');
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return array('success' => false, 'message' => 'Error: ' . $e->getMessage());
    }
}

// CHILDREN FUNCTIONS

// Get all children of a character
function getCharacterChildren($parent_character_id) {
    global $conn;
    
    $sql = "SELECT cc.*, c.name, c.race, c.gender, c.age
            FROM character_children cc
            JOIN characters c ON cc.child_character_id = c.id
            WHERE cc.parent1_character_id = " . intval($parent_character_id) . "
            OR cc.parent2_character_id = " . intval($parent_character_id);
    
    $result = $conn->query($sql);
    $children = array();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $children[] = $row;
        }
    }
    
    return $children;
}

// Register a new child for character
function registerChild($parent1_id, $parent2_id = null, $child_id = null, $is_adopted = false) {
    global $conn;
    
    // If no child_id is provided, we'll create a new NPC child character
    if (!$child_id) {
        // Get parent details to determine race, etc.
        $parent = getCharacterById($parent1_id);
        if (!$parent) {
            return array('success' => false, 'message' => 'Parent character not found');
        }
        
        // Generate random child details
        $gender = ['male', 'female'][rand(0, 1)];
        $race = $parent['race']; // Inherit from parent
        
        // Get a random name based on gender and race
        $name = generateChildName($gender, $race);
        
        // Create child character
        $sql = "INSERT INTO characters 
                (user_id, name, race, gender, age, level, is_active, relationship_status) 
                VALUES (" . intval($parent['user_id']) . ", '" . $conn->real_escape_string($name) . "', 
                '" . $conn->real_escape_string($race) . "', '" . $conn->real_escape_string($gender) . "', 
                0, 1, 0, 'child')";
        
        if (!$conn->query($sql)) {
            return array('success' => false, 'message' => 'Error creating child character');
        }
        
        $child_id = $conn->insert_id;
    }
    
    // Register child relationship
    $sql = "INSERT INTO character_children 
            (child_character_id, parent1_character_id, parent2_character_id, birth_date, 
             adopted, adoption_date, is_npc, growth_stage, next_growth_date) 
            VALUES (" . intval($child_id) . ", " . intval($parent1_id) . ", " . 
            ($parent2_id ? intval($parent2_id) : 'NULL') . ", " .
            ($is_adopted ? 'NULL' : 'CURDATE()') . ", " .
            ($is_adopted ? '1' : '0') . ", " .
            ($is_adopted ? 'CURDATE()' : 'NULL') . ", " .
            ($child_id ? '0' : '1') . ", 'infant', DATE_ADD(CURDATE(), INTERVAL 30 DAY))";
    
    if ($conn->query($sql)) {
        // Create parent-child relationship records
        updateRelationship($parent1_id, $child_id, 'parent_child', 90);
        updateRelationship($child_id, $parent1_id, 'parent_child', 90);
        
        if ($parent2_id) {
            updateRelationship($parent2_id, $child_id, 'parent_child', 90);
            updateRelationship($child_id, $parent2_id, 'parent_child', 90);
        }
        
        return array('success' => true, 'message' => 'Child registered successfully', 'child_id' => $child_id);
    } else {
        return array('success' => false, 'message' => 'Error registering child');
    }
}

// Record child-raising activity
function recordChildActivity($child_id, $parent_character_id, $activity_type, $benefit = null, $skill_gain = null) {
    global $conn;
    
    $sql = "INSERT INTO child_raising_activities 
            (child_id, parent_character_id, activity_type, benefit, skill_gain) 
            VALUES (" . intval($child_id) . ", " . intval($parent_character_id) . ", 
            '" . $conn->real_escape_string($activity_type) . "', " .
            ($benefit ? "'" . $conn->real_escape_string($benefit) . "'" : 'NULL') . ", " .
            ($skill_gain ? "'" . $conn->real_escape_string($skill_gain) . "'" : 'NULL') . ")";
    
    if ($conn->query($sql)) {
        // Update child character based on activity type
        // This could modify skills, attributes, etc.
        
        return array('success' => true, 'message' => 'Activity recorded successfully');
    } else {
        return array('success' => false, 'message' => 'Error recording activity');
    }
}

// SOCIAL EVENTS FUNCTIONS

// Get upcoming social events
function getUpcomingSocialEvents($character_id = null, $limit = 10) {
    global $conn;
    
    $sql = "SELECT se.*, c.name as host_name, l.name as location_name
            FROM social_events se
            JOIN characters c ON se.host_character_id = c.id
            JOIN locations l ON se.location_id = l.id
            WHERE se.start_date > NOW() AND se.status = 'planned'";
    
    if ($character_id) {
        // Include events the character is invited to
        $sql .= " AND (se.is_private = 0 OR se.host_character_id = " . intval($character_id) . " 
                 OR EXISTS (SELECT 1 FROM social_event_attendees WHERE event_id = se.id AND character_id = " . intval($character_id) . "))";
    } else {
        // Only public events if no character ID specified
        $sql .= " AND se.is_private = 0";
    }
    
    $sql .= " ORDER BY se.start_date ASC LIMIT " . intval($limit);
    
    $result = $conn->query($sql);
    $events = array();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
    }
    
    return $events;
}

// Create a social event
function createSocialEvent($host_id, $title, $description, $location_id, $property_id, $event_type, $start_date, $end_date, $is_private = 0, $max_attendees = 0) {
    global $conn;
    
    $sql = "INSERT INTO social_events 
            (title, description, host_character_id, location_id, property_id, event_type, 
             start_date, end_date, status, is_private, max_attendees) 
            VALUES ('" . $conn->real_escape_string($title) . "', '" . $conn->real_escape_string($description) . "', 
            " . intval($host_id) . ", " . intval($location_id) . ", " . 
            ($property_id ? intval($property_id) : 'NULL') . ", '" . $conn->real_escape_string($event_type) . "', 
            '" . $conn->real_escape_string($start_date) . "', '" . $conn->real_escape_string($end_date) . "', 
            'planned', " . ($is_private ? '1' : '0') . ", " . intval($max_attendees) . ")";
    
    if ($conn->query($sql)) {
        $event_id = $conn->insert_id;
        
        // Automatically add host as attending
        $sql = "INSERT INTO social_event_attendees 
                (event_id, character_id, status) 
                VALUES (" . intval($event_id) . ", " . intval($host_id) . ", 'attending')";
        $conn->query($sql);
        
        return array('success' => true, 'message' => 'Event created successfully', 'event_id' => $event_id);
    } else {
        return array('success' => false, 'message' => 'Error creating event');
    }
}

// Invite character to event
function inviteToEvent($event_id, $character_id) {
    global $conn;
    
    // Check if already invited
    $sql = "SELECT id FROM social_event_attendees 
            WHERE event_id = " . intval($event_id) . " 
            AND character_id = " . intval($character_id);
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return array('success' => false, 'message' => 'Character already invited to this event');
    }
    
    $sql = "INSERT INTO social_event_attendees 
            (event_id, character_id, status) 
            VALUES (" . intval($event_id) . ", " . intval($character_id) . ", 'invited')";
    
    if ($conn->query($sql)) {
        return array('success' => true, 'message' => 'Invitation sent successfully');
    } else {
        return array('success' => false, 'message' => 'Error sending invitation');
    }
}

// Respond to event invitation
function respondToEventInvite($event_attendee_id, $status) {
    global $conn;
    
    if (!in_array($status, ['attending', 'declined', 'maybe'])) {
        return array('success' => false, 'message' => 'Invalid response status');
    }
    
    $sql = "UPDATE social_event_attendees 
            SET status = '" . $conn->real_escape_string($status) . "', 
                response_at = NOW() 
            WHERE id = " . intval($event_attendee_id);
    
    if ($conn->query($sql)) {
        return array('success' => true, 'message' => 'Response recorded successfully');
    } else {
        return array('success' => false, 'message' => 'Error recording response');
    }
}

// Get event details including attendees
function getEventDetails($event_id) {
    global $conn;
    
    $sql = "SELECT se.*, c.name as host_name, l.name as location_name, p.name as property_name 
            FROM social_events se
            JOIN characters c ON se.host_character_id = c.id
            JOIN locations l ON se.location_id = l.id
            LEFT JOIN properties p ON se.property_id = p.id
            WHERE se.id = " . intval($event_id);
    
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        return false;
    }
    
    $event = $result->fetch_assoc();
    
    // Get attendees
    $sql = "SELECT sea.*, c.name, c.gender, c.race 
            FROM social_event_attendees sea
            JOIN characters c ON sea.character_id = c.id
            WHERE sea.event_id = " . intval($event_id) . "
            ORDER BY sea.status, sea.invited_at";
    
    $result = $conn->query($sql);
    $attendees = array();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $attendees[] = $row;
        }
    }
    
    $event['attendees'] = $attendees;
    
    return $event;
}

// MESSAGING FUNCTIONS

// Send a message between characters
function sendMessage($sender_id, $receiver_id, $message) {
    global $conn;
    
    $sql = "INSERT INTO character_messages 
            (sender_id, receiver_id, message) 
            VALUES (" . intval($sender_id) . ", " . intval($receiver_id) . ", 
            '" . $conn->real_escape_string($message) . "')";
    
    if ($conn->query($sql)) {
        return array('success' => true, 'message' => 'Message sent successfully');
    } else {
        return array('success' => false, 'message' => 'Error sending message');
    }
}

// Get conversation between two characters
function getConversation($character1_id, $character2_id, $limit = 20) {
    global $conn;
    
    $sql = "SELECT cm.*, c1.name as sender_name, c2.name as receiver_name
            FROM character_messages cm
            JOIN characters c1 ON cm.sender_id = c1.id
            JOIN characters c2 ON cm.receiver_id = c2.id
            WHERE (cm.sender_id = " . intval($character1_id) . " AND cm.receiver_id = " . intval($character2_id) . "
            AND cm.is_deleted_by_sender = 0)
            OR (cm.sender_id = " . intval($character2_id) . " AND cm.receiver_id = " . intval($character1_id) . "
            AND cm.is_deleted_by_receiver = 0)
            ORDER BY cm.sent_at DESC
            LIMIT " . intval($limit);
    
    $result = $conn->query($sql);
    $messages = array();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
    }
    
    // Mark as read
    $sql = "UPDATE character_messages 
            SET is_read = 1, read_at = NOW()
            WHERE sender_id = " . intval($character2_id) . " 
            AND receiver_id = " . intval($character1_id) . "
            AND is_read = 0";
    $conn->query($sql);
    
    return $messages;
}

// Get count of unread messages
function getUnreadMessageCount($character_id) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count
            FROM character_messages
            WHERE receiver_id = " . intval($character_id) . "
            AND is_read = 0
            AND is_deleted_by_receiver = 0";
    
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

// HELPER FUNCTIONS

// Generate a random name for a child
function generateChildName($gender, $race) {
    global $conn;
    
    $sql = "SELECT name FROM child_names
            WHERE gender IN ('" . $conn->real_escape_string($gender) . "', 'neutral')
            AND (race = '" . $conn->real_escape_string($race) . "' OR race = 'all')
            ORDER BY RAND()
            LIMIT 1";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['name'];
    }
    
    // Fallback names if none found in database
    $male_names = ['John', 'David', 'Michael', 'Robert'];
    $female_names = ['Mary', 'Sarah', 'Elizabeth', 'Jennifer'];
    $neutral_names = ['Alex', 'Jordan', 'Taylor', 'Riley'];
    
    if ($gender == 'male') {
        return $male_names[array_rand($male_names)];
    } else if ($gender == 'female') {
        return $female_names[array_rand($female_names)];
    } else {
        return $neutral_names[array_rand($neutral_names)];
    }
}

// Get character by ID
function getCharacterById($character_id) {
    global $conn;
    
    $sql = "SELECT * FROM characters WHERE id = " . intval($character_id);
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return false;
} 