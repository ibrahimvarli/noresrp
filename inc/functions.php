// Get location by ID
function getLocationById($location_id) {
    global $conn;
    
    $sql = "SELECT * FROM locations WHERE id = " . intval($location_id);
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return false;
} 