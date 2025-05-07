<?php
/**
 * Property Management System
 * Core functions for handling property purchase, rent, maintenance and furniture
 */

// Get all available properties for purchase or rent
function getAvailableProperties($location_id = null) {
    global $conn;
    
    $sql = "SELECT p.*, l.name as location_name 
            FROM properties p
            JOIN locations l ON p.location_id = l.id
            WHERE p.status = 'available' AND p.is_active = 1";
    
    if ($location_id) {
        $sql .= " AND p.location_id = " . intval($location_id);
    }
    
    $result = $conn->query($sql);
    $properties = array();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $properties[] = $row;
        }
    }
    
    return $properties;
}

// Get property details by ID
function getPropertyById($property_id) {
    global $conn;
    
    $sql = "SELECT p.*, l.name as location_name 
            FROM properties p
            JOIN locations l ON p.location_id = l.id
            WHERE p.id = " . intval($property_id);
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return false;
}

// Get all properties owned or rented by a character
function getCharacterProperties($character_id) {
    global $conn;
    
    $sql = "SELECT cp.*, p.name, p.description, p.type, p.size, p.rooms, 
                  p.purchase_price, p.rent_price, p.image, p.prestige, p.maintenance_cost,
                  l.name as location_name
            FROM character_properties cp
            JOIN properties p ON cp.property_id = p.id
            JOIN locations l ON p.location_id = l.id
            WHERE cp.character_id = " . intval($character_id) . "
            AND cp.is_active = 1
            ORDER BY cp.purchase_date DESC";
    
    $result = $conn->query($sql);
    $properties = array();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $properties[] = $row;
        }
    }
    
    return $properties;
}

// Purchase a property
function purchaseProperty($character_id, $property_id) {
    global $conn;
    
    // Get property details
    $property = getPropertyById($property_id);
    if (!$property || $property['status'] != 'available') {
        return array('success' => false, 'message' => 'Property is not available');
    }
    
    // Get character details
    $character = getCharacterById($character_id);
    if (!$character) {
        return array('success' => false, 'message' => 'Character not found');
    }
    
    // Check if character has enough gold
    if ($character['gold'] < $property['purchase_price']) {
        return array('success' => false, 'message' => 'Not enough gold');
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update property status
        $sql = "UPDATE properties SET status = 'sold' WHERE id = " . intval($property_id);
        $conn->query($sql);
        
        // Create character property record
        $next_payment = date('Y-m-d H:i:s', strtotime('+30 days'));
        $sql = "INSERT INTO character_properties 
                (character_id, property_id, ownership_type, next_payment) 
                VALUES (" . intval($character_id) . ", " . intval($property_id) . ", 'owned', '$next_payment')";
        $conn->query($sql);
        
        // Deduct gold from character
        $new_gold = $character['gold'] - $property['purchase_price'];
        $sql = "UPDATE characters SET gold = " . intval($new_gold) . " WHERE id = " . intval($character_id);
        $conn->query($sql);
        
        // Log transaction
        logEconomyTransaction($character_id, $property['purchase_price'], 'expense', 'property_purchase', 'Purchase of property: ' . $property['name']);
        
        // Commit transaction
        $conn->commit();
        
        return array('success' => true, 'message' => 'Property purchased successfully');
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return array('success' => false, 'message' => 'Error: ' . $e->getMessage());
    }
}

// Rent a property
function rentProperty($character_id, $property_id) {
    global $conn;
    
    // Get property details
    $property = getPropertyById($property_id);
    if (!$property || $property['status'] != 'available') {
        return array('success' => false, 'message' => 'Property is not available');
    }
    
    // Get character details
    $character = getCharacterById($character_id);
    if (!$character) {
        return array('success' => false, 'message' => 'Character not found');
    }
    
    // Check if character has enough gold for first month rent
    if ($character['gold'] < $property['rent_price']) {
        return array('success' => false, 'message' => 'Not enough gold for first payment');
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update property status
        $sql = "UPDATE properties SET status = 'rented' WHERE id = " . intval($property_id);
        $conn->query($sql);
        
        // Create character property record
        $next_payment = date('Y-m-d H:i:s', strtotime('+7 days'));
        $sql = "INSERT INTO character_properties 
                (character_id, property_id, ownership_type, next_payment) 
                VALUES (" . intval($character_id) . ", " . intval($property_id) . ", 'rented', '$next_payment')";
        $conn->query($sql);
        
        // Deduct gold from character
        $new_gold = $character['gold'] - $property['rent_price'];
        $sql = "UPDATE characters SET gold = " . intval($new_gold) . " WHERE id = " . intval($character_id);
        $conn->query($sql);
        
        // Log transaction
        logEconomyTransaction($character_id, $property['rent_price'], 'expense', 'property_rent', 'First rent payment for: ' . $property['name']);
        
        // Commit transaction
        $conn->commit();
        
        return array('success' => true, 'message' => 'Property rented successfully');
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return array('success' => false, 'message' => 'Error: ' . $e->getMessage());
    }
}

// Pay rent for a property
function payPropertyRent($character_property_id) {
    global $conn;
    
    // Get character property details
    $sql = "SELECT cp.*, p.rent_price, p.name, c.gold, c.id as character_id 
            FROM character_properties cp
            JOIN properties p ON cp.property_id = p.id
            JOIN characters c ON cp.character_id = c.id
            WHERE cp.id = " . intval($character_property_id);
    
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        return array('success' => false, 'message' => 'Property rental not found');
    }
    
    $property = $result->fetch_assoc();
    
    // Check if character has enough gold
    if ($property['gold'] < $property['rent_price']) {
        return array('success' => false, 'message' => 'Not enough gold');
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Calculate next payment date
        $next_payment = date('Y-m-d H:i:s', strtotime('+' . $property['rent_frequency'] . ' days'));
        
        // Update character property record
        $sql = "UPDATE character_properties 
                SET last_payment = NOW(), next_payment = '$next_payment', rent_due = 0 
                WHERE id = " . intval($character_property_id);
        $conn->query($sql);
        
        // Deduct gold from character
        $new_gold = $property['gold'] - $property['rent_price'];
        $sql = "UPDATE characters SET gold = " . intval($new_gold) . " WHERE id = " . intval($property['character_id']);
        $conn->query($sql);
        
        // Log transaction
        logEconomyTransaction($property['character_id'], $property['rent_price'], 'expense', 'property_rent', 'Rent payment for: ' . $property['name']);
        
        // Commit transaction
        $conn->commit();
        
        return array('success' => true, 'message' => 'Rent paid successfully');
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return array('success' => false, 'message' => 'Error: ' . $e->getMessage());
    }
}

// Maintain a property
function maintainProperty($character_property_id, $maintenance_type) {
    global $conn;
    
    // Get character property details
    $sql = "SELECT cp.*, p.maintenance_cost, p.name, c.gold, c.id as character_id 
            FROM character_properties cp
            JOIN properties p ON cp.property_id = p.id
            JOIN characters c ON cp.character_id = c.id
            WHERE cp.id = " . intval($character_property_id);
    
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        return array('success' => false, 'message' => 'Property not found');
    }
    
    $property = $result->fetch_assoc();
    
    // Set maintenance cost based on type
    $cost = $property['maintenance_cost'];
    $improvement = 10; // Default improvement amount
    $description = "Regular maintenance of the property";
    
    switch ($maintenance_type) {
        case 'repair':
            $cost = $property['maintenance_cost'] * 2;
            $improvement = 25;
            $description = "Repair of structural damage";
            break;
        case 'cleaning':
            $cost = $property['maintenance_cost'] / 2;
            $improvement = 30;
            $description = "Deep cleaning of the property";
            break;
        case 'upgrade':
            $cost = $property['maintenance_cost'] * 5;
            $improvement = 40;
            $description = "Upgrade of property features";
            break;
        case 'security':
            $cost = $property['maintenance_cost'] * 3;
            $improvement = 20;
            $description = "Enhancement of property security";
            break;
    }
    
    // Check if character has enough gold
    if ($property['gold'] < $cost) {
        return array('success' => false, 'message' => 'Not enough gold');
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Create maintenance record
        $sql = "INSERT INTO property_maintenance 
                (character_property_id, maintenance_type, description, cost, condition_improvement, status) 
                VALUES (" . intval($character_property_id) . ", '$maintenance_type', '$description', " . 
                intval($cost) . ", " . intval($improvement) . ", 'completed')";
        $conn->query($sql);
        
        // Update property condition
        $new_condition = min(100, $property['condition_value'] + $improvement);
        $sql = "UPDATE character_properties 
                SET condition_value = " . intval($new_condition) . " 
                WHERE id = " . intval($character_property_id);
        $conn->query($sql);
        
        // Deduct gold from character
        $new_gold = $property['gold'] - $cost;
        $sql = "UPDATE characters SET gold = " . intval($new_gold) . " WHERE id = " . intval($property['character_id']);
        $conn->query($sql);
        
        // Log transaction
        logEconomyTransaction($property['character_id'], $cost, 'expense', 'property_maintenance', 
                        $maintenance_type . ' maintenance for: ' . $property['name']);
        
        // Commit transaction
        $conn->commit();
        
        return array('success' => true, 'message' => 'Maintenance completed successfully');
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return array('success' => false, 'message' => 'Error: ' . $e->getMessage());
    }
}

// Clean a property (improves cleanliness)
function cleanProperty($character_property_id) {
    global $conn;
    
    // Get character property details
    $sql = "SELECT cp.* FROM character_properties cp WHERE cp.id = " . intval($character_property_id);
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        return array('success' => false, 'message' => 'Property not found');
    }
    
    $property = $result->fetch_assoc();
    
    // Improve cleanliness
    $new_cleanliness = min(100, $property['cleanliness'] + 25);
    
    $sql = "UPDATE character_properties 
            SET cleanliness = " . intval($new_cleanliness) . " 
            WHERE id = " . intval($character_property_id);
    
    if ($conn->query($sql)) {
        return array('success' => true, 'message' => 'Property cleaned successfully');
    } else {
        return array('success' => false, 'message' => 'Error cleaning property');
    }
}

// Process property degradation (run daily)
function processPropertyDegradation() {
    global $conn;
    
    $sql = "UPDATE character_properties 
            SET cleanliness = GREATEST(0, cleanliness - 2),
                condition_value = GREATEST(0, condition_value - 1)
            WHERE is_active = 1";
    
    $conn->query($sql);
}

// Get all furniture available for purchase
function getAvailableFurniture() {
    global $conn;
    
    $sql = "SELECT * FROM furniture WHERE is_active = 1 ORDER BY purchase_price ASC";
    $result = $conn->query($sql);
    $furniture = array();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $furniture[] = $row;
        }
    }
    
    return $furniture;
}

// Purchase furniture
function purchaseFurniture($character_id, $furniture_id, $character_property_id) {
    global $conn;
    
    // Get furniture details
    $sql = "SELECT * FROM furniture WHERE id = " . intval($furniture_id);
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        return array('success' => false, 'message' => 'Furniture not found');
    }
    
    $furniture = $result->fetch_assoc();
    
    // Get character details
    $character = getCharacterById($character_id);
    if (!$character) {
        return array('success' => false, 'message' => 'Character not found');
    }
    
    // Check if character has enough gold
    if ($character['gold'] < $furniture['purchase_price']) {
        return array('success' => false, 'message' => 'Not enough gold');
    }
    
    // Check if property exists and belongs to character
    $sql = "SELECT cp.*, p.max_furniture 
            FROM character_properties cp
            JOIN properties p ON cp.property_id = p.id
            WHERE cp.id = " . intval($character_property_id) . " 
            AND cp.character_id = " . intval($character_id);
    
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        return array('success' => false, 'message' => 'Property not found or not owned by character');
    }
    
    $property = $result->fetch_assoc();
    
    // Check furniture count limit
    $sql = "SELECT COUNT(*) as furniture_count 
            FROM property_furniture 
            WHERE character_property_id = " . intval($character_property_id) . " 
            AND is_active = 1";
    
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    
    if ($row['furniture_count'] >= $property['max_furniture']) {
        return array('success' => false, 'message' => 'Property already has maximum furniture');
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Add furniture to property
        $sql = "INSERT INTO property_furniture 
                (character_property_id, furniture_id) 
                VALUES (" . intval($character_property_id) . ", " . intval($furniture_id) . ")";
        $conn->query($sql);
        
        // Deduct gold from character
        $new_gold = $character['gold'] - $furniture['purchase_price'];
        $sql = "UPDATE characters SET gold = " . intval($new_gold) . " WHERE id = " . intval($character_id);
        $conn->query($sql);
        
        // Log transaction
        logEconomyTransaction($character_id, $furniture['purchase_price'], 'expense', 'furniture_purchase', 
                        'Purchase of furniture: ' . $furniture['name']);
        
        // Commit transaction
        $conn->commit();
        
        return array('success' => true, 'message' => 'Furniture purchased successfully');
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return array('success' => false, 'message' => 'Error: ' . $e->getMessage());
    }
}

// Get all furniture in a property
function getPropertyFurniture($character_property_id) {
    global $conn;
    
    $sql = "SELECT pf.*, f.name, f.description, f.type, f.style, f.image, 
                  f.comfort, f.aesthetics, f.functionality
            FROM property_furniture pf
            JOIN furniture f ON pf.furniture_id = f.id
            WHERE pf.character_property_id = " . intval($character_property_id) . "
            AND pf.is_active = 1
            ORDER BY pf.id ASC";
    
    $result = $conn->query($sql);
    $furniture = array();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $furniture[] = $row;
        }
    }
    
    return $furniture;
}

// Move furniture in a property
function moveFurniture($property_furniture_id, $position_x, $position_y, $rotation) {
    global $conn;
    
    $sql = "UPDATE property_furniture 
            SET position_x = " . intval($position_x) . ",
                position_y = " . intval($position_y) . ",
                rotation = " . intval($rotation) . "
            WHERE id = " . intval($property_furniture_id);
    
    if ($conn->query($sql)) {
        return array('success' => true, 'message' => 'Furniture moved successfully');
    } else {
        return array('success' => false, 'message' => 'Error moving furniture');
    }
}

// Remove furniture from a property
function removeFurniture($property_furniture_id) {
    global $conn;
    
    $sql = "UPDATE property_furniture 
            SET is_active = 0
            WHERE id = " . intval($property_furniture_id);
    
    if ($conn->query($sql)) {
        return array('success' => true, 'message' => 'Furniture removed successfully');
    } else {
        return array('success' => false, 'message' => 'Error removing furniture');
    }
}

// Sell a property
function sellProperty($character_property_id) {
    global $conn;
    
    // Get property details
    $sql = "SELECT cp.*, p.purchase_price, p.name, c.id as character_id 
            FROM character_properties cp
            JOIN properties p ON cp.property_id = p.id
            JOIN characters c ON cp.character_id = c.id
            WHERE cp.id = " . intval($character_property_id) . "
            AND cp.ownership_type = 'owned'";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        return array('success' => false, 'message' => 'Owned property not found');
    }
    
    $property = $result->fetch_assoc();
    
    // Calculate sale price (75% of purchase price)
    $sale_price = floor($property['purchase_price'] * 0.75);
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update property status
        $sql = "UPDATE properties SET status = 'available' WHERE id = " . intval($property['property_id']);
        $conn->query($sql);
        
        // Deactivate character property record
        $sql = "UPDATE character_properties SET is_active = 0 WHERE id = " . intval($character_property_id);
        $conn->query($sql);
        
        // Add gold to character
        $sql = "UPDATE characters SET gold = gold + " . intval($sale_price) . " WHERE id = " . intval($property['character_id']);
        $conn->query($sql);
        
        // Log transaction
        logEconomyTransaction($property['character_id'], $sale_price, 'income', 'property_sale', 
                        'Sale of property: ' . $property['name']);
        
        // Commit transaction
        $conn->commit();
        
        return array('success' => true, 'message' => 'Property sold successfully for ' . $sale_price . ' gold');
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return array('success' => false, 'message' => 'Error: ' . $e->getMessage());
    }
}

// End a property rental
function endRental($character_property_id) {
    global $conn;
    
    // Get property details
    $sql = "SELECT cp.*, p.name 
            FROM character_properties cp
            JOIN properties p ON cp.property_id = p.id
            WHERE cp.id = " . intval($character_property_id) . "
            AND cp.ownership_type = 'rented'";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        return array('success' => false, 'message' => 'Rented property not found');
    }
    
    $property = $result->fetch_assoc();
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update property status
        $sql = "UPDATE properties SET status = 'available' WHERE id = " . intval($property['property_id']);
        $conn->query($sql);
        
        // Deactivate character property record
        $sql = "UPDATE character_properties SET is_active = 0 WHERE id = " . intval($character_property_id);
        $conn->query($sql);
        
        // Commit transaction
        $conn->commit();
        
        return array('success' => true, 'message' => 'Rental ended successfully');
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return array('success' => false, 'message' => 'Error: ' . $e->getMessage());
    }
}

// Helper function to log economy transactions
function logEconomyTransaction($character_id, $amount, $type, $category, $description) {
    global $conn;
    
    // Get current balance
    $sql = "SELECT gold FROM characters WHERE id = " . intval($character_id);
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $balance = $row['gold'];
    
    $sql = "INSERT INTO economy_transactions 
            (character_id, amount, balance_after, type, category, description) 
            VALUES (" . intval($character_id) . ", " . intval($amount) . ", " . 
            intval($balance) . ", '$type', '$category', '$description')";
    
    $conn->query($sql);
} 