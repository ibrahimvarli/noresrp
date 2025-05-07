<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Make sure we have a database connection
if (!isset($db)) {
    die("Database connection not available");
}

// Define the basic starter items
$starterItems = [
    [1, 'Rusty Sword', 'weapon', 'A worn but serviceable sword.', 5, 0, 5],
    [2, 'Wooden Shield', 'shield', 'A simple wooden shield for basic protection.', 3, 0, 5],
    [3, 'Health Potion', 'potion', 'A small vial of red liquid that restores 25 health.', 5, 0, 5],
    [4, 'Apprentice Staff', 'weapon', 'A basic magical staff for beginners.', 5, 0, 5],
    [5, 'Novice Robe', 'armor', 'Simple cloth robe providing minimal protection.', 2, 0, 5]
];

// Add items to the database
foreach ($starterItems as $item) {
    list($id, $name, $type, $description, $value, $level_req, $rarity) = $item;
    
    // Check if item already exists
    $checkSql = "SELECT id FROM items WHERE id = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows == 0) {
        // Item doesn't exist, add it
        $sql = "INSERT INTO items (id, name, type, description, value, level_req, rarity) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("isssiis", $id, $name, $type, $description, $value, $level_req, $rarity);
        
        if ($stmt->execute()) {
            echo "Added item: $name (ID: $id)<br>";
        } else {
            echo "Failed to add item: $name. Error: " . $stmt->error . "<br>";
        }
    } else {
        echo "Item with ID $id already exists.<br>";
    }
}

echo "<p>Item setup complete. <a href='index.php?page=characters'>Return to characters page</a></p>";
?> 