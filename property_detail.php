<?php
include 'inc/header.php';
include 'inc/property.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get active character
$character = getActiveCharacter($_SESSION['user_id']);
if (!$character) {
    header("Location: character_select.php");
    exit();
}

// Check if property ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: property.php");
    exit();
}

$character_property_id = intval($_GET['id']);

// Get property details
$sql = "SELECT cp.*, p.name, p.description, p.type, p.size, p.rooms, 
              p.purchase_price, p.rent_price, p.image, p.prestige, p.maintenance_cost, p.max_furniture,
              l.name as location_name
        FROM character_properties cp
        JOIN properties p ON cp.property_id = p.id
        JOIN locations l ON p.location_id = l.id
        WHERE cp.id = $character_property_id
        AND cp.character_id = {$character['id']}
        AND cp.is_active = 1";

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    $_SESSION['error_message'] = $translator->translate('Property not found or you do not have access to it.');
    header("Location: property.php");
    exit();
}

$property = $result->fetch_assoc();

// Get furniture in property
$furniture = getPropertyFurniture($character_property_id);

// Handle furniture purchase
if (isset($_POST['action']) && $_POST['action'] == 'buy_furniture') {
    $furniture_id = isset($_POST['furniture_id']) ? intval($_POST['furniture_id']) : 0;
    
    $result = purchaseFurniture($character['id'], $furniture_id, $character_property_id);
    
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        $_SESSION['error_message'] = $result['message'];
    }
    
    // Refresh page
    header("Location: property_detail.php?id=$character_property_id");
    exit();
}

// Handle furniture movement
if (isset($_POST['action']) && $_POST['action'] == 'move_furniture') {
    $property_furniture_id = isset($_POST['property_furniture_id']) ? intval($_POST['property_furniture_id']) : 0;
    $position_x = isset($_POST['position_x']) ? intval($_POST['position_x']) : 0;
    $position_y = isset($_POST['position_y']) ? intval($_POST['position_y']) : 0;
    $rotation = isset($_POST['rotation']) ? intval($_POST['rotation']) : 0;
    
    $result = moveFurniture($property_furniture_id, $position_x, $position_y, $rotation);
    
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        $_SESSION['error_message'] = $result['message'];
    }
    
    // Refresh page
    header("Location: property_detail.php?id=$character_property_id");
    exit();
}

// Handle furniture removal
if (isset($_POST['action']) && $_POST['action'] == 'remove_furniture') {
    $property_furniture_id = isset($_POST['property_furniture_id']) ? intval($_POST['property_furniture_id']) : 0;
    
    $result = removeFurniture($property_furniture_id);
    
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        $_SESSION['error_message'] = $result['message'];
    }
    
    // Refresh page
    header("Location: property_detail.php?id=$character_property_id");
    exit();
}

// Get available furniture for purchase
$available_furniture = getAvailableFurniture();

// Get furniture count
$furniture_count = count($furniture);
$max_furniture = $property['max_furniture'];
?>

<div class="container mt-4">
    <h2 class="mb-3"><i class="fas fa-home"></i> <?php echo htmlspecialchars($property['name']); ?></h2>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><?php echo $translator->translate('Property Information'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <img src="img/properties/<?php echo $property['image']; ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($property['name']); ?>">
                        </div>
                        <div class="col-md-6">
                            <p><strong><?php echo $translator->translate('Location'); ?>:</strong> <?php echo htmlspecialchars($property['location_name']); ?></p>
                            <p><strong><?php echo $translator->translate('Type'); ?>:</strong> <?php echo ucfirst($property['type']); ?></p>
                            <p><strong><?php echo $translator->translate('Size'); ?>:</strong> <?php echo $property['size']; ?> m²</p>
                            <p><strong><?php echo $translator->translate('Rooms'); ?>:</strong> <?php echo $property['rooms']; ?></p>
                            <p><strong><?php echo $translator->translate('Prestige'); ?>:</strong> <?php echo $property['prestige']; ?></p>
                            <p><strong><?php echo $translator->translate('Furniture'); ?>:</strong> <?php echo $furniture_count; ?>/<?php echo $max_furniture; ?></p>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <p><?php echo htmlspecialchars($property['description']); ?></p>
                    </div>
                    
                    <!-- Property Status Bars -->
                    <div class="mb-2">
                        <label><?php echo $translator->translate('Condition'); ?>: <?php echo $property['condition_value']; ?>%</label>
                        <div class="progress">
                            <div class="progress-bar <?php echo getConditionClass($property['condition_value']); ?>" 
                                 role="progressbar" style="width: <?php echo $property['condition_value']; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label><?php echo $translator->translate('Cleanliness'); ?>: <?php echo $property['cleanliness']; ?>%</label>
                        <div class="progress">
                            <div class="progress-bar <?php echo getCleanlinessClass($property['cleanliness']); ?>" 
                                 role="progressbar" style="width: <?php echo $property['cleanliness']; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label><?php echo $translator->translate('Security'); ?>: <?php echo $property['security']; ?>%</label>
                        <div class="progress">
                            <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $property['security']; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h6><?php echo $translator->translate('Quick Actions'); ?></h6>
                        <div class="btn-group">
                            <!-- Clean button -->
                            <form method="post" action="property.php">
                                <input type="hidden" name="action" value="clean">
                                <input type="hidden" name="character_property_id" value="<?php echo $property['id']; ?>">
                                <button type="submit" class="btn btn-info">
                                    <i class="fas fa-broom"></i> <?php echo $translator->translate('Clean'); ?>
                                </button>
                            </form>
                            
                            <!-- Maintain dropdown -->
                            <div class="btn-group">
                                <button type="button" class="btn btn-warning dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-tools"></i> <?php echo $translator->translate('Maintain'); ?>
                                </button>
                                <div class="dropdown-menu">
                                    <form method="post" action="property.php">
                                        <input type="hidden" name="action" value="maintain">
                                        <input type="hidden" name="character_property_id" value="<?php echo $property['id']; ?>">
                                        <input type="hidden" name="maintenance_type" value="regular">
                                        <button type="submit" class="dropdown-item">
                                            <?php echo $translator->translate('Regular Maintenance'); ?> (<?php echo $property['maintenance_cost']; ?> <?php echo $translator->translate('gold'); ?>)
                                        </button>
                                    </form>
                                    <form method="post" action="property.php">
                                        <input type="hidden" name="action" value="maintain">
                                        <input type="hidden" name="character_property_id" value="<?php echo $property['id']; ?>">
                                        <input type="hidden" name="maintenance_type" value="repair">
                                        <button type="submit" class="dropdown-item">
                                            <?php echo $translator->translate('Repair Damage'); ?> (<?php echo $property['maintenance_cost'] * 2; ?> <?php echo $translator->translate('gold'); ?>)
                                        </button>
                                    </form>
                                    <form method="post" action="property.php">
                                        <input type="hidden" name="action" value="maintain">
                                        <input type="hidden" name="character_property_id" value="<?php echo $property['id']; ?>">
                                        <input type="hidden" name="maintenance_type" value="security">
                                        <button type="submit" class="dropdown-item">
                                            <?php echo $translator->translate('Upgrade Security'); ?> (<?php echo $property['maintenance_cost'] * 3; ?> <?php echo $translator->translate('gold'); ?>)
                                        </button>
                                    </form>
                                    <form method="post" action="property.php">
                                        <input type="hidden" name="action" value="maintain">
                                        <input type="hidden" name="character_property_id" value="<?php echo $property['id']; ?>">
                                        <input type="hidden" name="maintenance_type" value="upgrade">
                                        <button type="submit" class="dropdown-item">
                                            <?php echo $translator->translate('Upgrade Property'); ?> (<?php echo $property['maintenance_cost'] * 5; ?> <?php echo $translator->translate('gold'); ?>)
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <a href="property.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> <?php echo $translator->translate('Back to Properties'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><?php echo $translator->translate('Furniture'); ?> (<?php echo $furniture_count; ?>/<?php echo $max_furniture; ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($furniture)): ?>
                        <div class="alert alert-info">
                            <?php echo $translator->translate('This property has no furniture yet. Add some furniture to make it more comfortable!'); ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th><?php echo $translator->translate('Item'); ?></th>
                                        <th><?php echo $translator->translate('Type'); ?></th>
                                        <th><?php echo $translator->translate('Style'); ?></th>
                                        <th><?php echo $translator->translate('Condition'); ?></th>
                                        <th><?php echo $translator->translate('Actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($furniture as $item): ?>
                                        <tr>
                                            <td>
                                                <img src="img/furniture/<?php echo $item['image']; ?>" class="img-fluid rounded me-2" style="width: 30px; height: 30px;" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </td>
                                            <td><?php echo ucfirst($item['type']); ?></td>
                                            <td><?php echo ucfirst($item['style']); ?></td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar <?php echo getConditionClass($item['condition_value']); ?>" 
                                                         role="progressbar" style="width: <?php echo $item['condition_value']; ?>%">
                                                        <?php echo $item['condition_value']; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#moveModal<?php echo $item['id']; ?>">
                                                    <i class="fas fa-arrows-alt"></i>
                                                </button>
                                                <form method="post" class="d-inline" onsubmit="return confirm('<?php echo $translator->translate('Are you sure you want to remove this furniture?'); ?>');">
                                                    <input type="hidden" name="action" value="remove_furniture">
                                                    <input type="hidden" name="property_furniture_id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                
                                                <!-- Move Furniture Modal -->
                                                <div class="modal fade" id="moveModal<?php echo $item['id']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title"><?php echo $translator->translate('Move'); ?> <?php echo htmlspecialchars($item['name']); ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form method="post">
                                                                    <input type="hidden" name="action" value="move_furniture">
                                                                    <input type="hidden" name="property_furniture_id" value="<?php echo $item['id']; ?>">
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="position_x" class="form-label"><?php echo $translator->translate('Position X'); ?></label>
                                                                        <input type="number" class="form-control" id="position_x" name="position_x" value="<?php echo $item['position_x']; ?>" min="0" max="100">
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="position_y" class="form-label"><?php echo $translator->translate('Position Y'); ?></label>
                                                                        <input type="number" class="form-control" id="position_y" name="position_y" value="<?php echo $item['position_y']; ?>" min="0" max="100">
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="rotation" class="form-label"><?php echo $translator->translate('Rotation'); ?></label>
                                                                        <input type="number" class="form-control" id="rotation" name="rotation" value="<?php echo $item['rotation']; ?>" min="0" max="359" step="45">
                                                                    </div>
                                                                    
                                                                    <button type="submit" class="btn btn-primary"><?php echo $translator->translate('Save Changes'); ?></button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($furniture_count < $max_furniture): ?>
                        <button type="button" class="btn btn-success mt-3" data-bs-toggle="modal" data-bs-target="#buyFurnitureModal">
                            <i class="fas fa-plus"></i> <?php echo $translator->translate('Buy Furniture'); ?>
                        </button>
                    <?php else: ?>
                        <div class="alert alert-warning mt-3">
                            <?php echo $translator->translate('This property has reached its maximum furniture capacity.'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Property Layout Visual Representation -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><?php echo $translator->translate('Property Layout'); ?></h5>
        </div>
        <div class="card-body">
            <div class="property-layout" style="position: relative; width: 100%; height: 400px; border: 1px solid #ccc; background-color: #f5f5f5;">
                <?php foreach ($furniture as $item): ?>
                    <div class="furniture-item" style="position: absolute; top: <?php echo $item['position_y']; ?>%; left: <?php echo $item['position_x']; ?>%; transform: rotate(<?php echo $item['rotation']; ?>deg);">
                        <img src="img/furniture/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 50px; height: 50px;">
                        <span class="furniture-tooltip"><?php echo htmlspecialchars($item['name']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-muted mt-2 small">
                <?php echo $translator->translate('Note: This is a simplified representation. Use the move function to adjust furniture placement.'); ?>
            </div>
        </div>
    </div>
</div>

<!-- Buy Furniture Modal -->
<div class="modal fade" id="buyFurnitureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo $translator->translate('Buy Furniture'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-primary active filter-btn" data-filter="all"><?php echo $translator->translate('All'); ?></button>
                            <button type="button" class="btn btn-outline-primary filter-btn" data-filter="bed"><?php echo $translator->translate('Beds'); ?></button>
                            <button type="button" class="btn btn-outline-primary filter-btn" data-filter="seating"><?php echo $translator->translate('Seating'); ?></button>
                            <button type="button" class="btn btn-outline-primary filter-btn" data-filter="storage"><?php echo $translator->translate('Storage'); ?></button>
                            <button type="button" class="btn btn-outline-primary filter-btn" data-filter="table"><?php echo $translator->translate('Tables'); ?></button>
                            <button type="button" class="btn btn-outline-primary filter-btn" data-filter="decor"><?php echo $translator->translate('Decor'); ?></button>
                            <button type="button" class="btn btn-outline-primary filter-btn" data-filter="lighting"><?php echo $translator->translate('Lighting'); ?></button>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th><?php echo $translator->translate('Item'); ?></th>
                                <th><?php echo $translator->translate('Type'); ?></th>
                                <th><?php echo $translator->translate('Style'); ?></th>
                                <th><?php echo $translator->translate('Size'); ?></th>
                                <th><?php echo $translator->translate('Price'); ?></th>
                                <th><?php echo $translator->translate('Stats'); ?></th>
                                <th><?php echo $translator->translate('Action'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($available_furniture as $item): ?>
                                <tr class="furniture-row" data-type="<?php echo $item['type']; ?>">
                                    <td>
                                        <img src="img/furniture/<?php echo $item['image']; ?>" class="img-fluid rounded me-2" style="width: 30px; height: 30px;" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </td>
                                    <td><?php echo ucfirst($item['type']); ?></td>
                                    <td><?php echo ucfirst($item['style']); ?></td>
                                    <td><?php echo $item['size']; ?> units</td>
                                    <td><?php echo $item['purchase_price']; ?> <?php echo $translator->translate('gold'); ?></td>
                                    <td>
                                        <?php if ($item['comfort'] > 0): ?>
                                            <span class="badge bg-primary"><?php echo $translator->translate('Comfort'); ?>: <?php echo $item['comfort']; ?></span>
                                        <?php endif; ?>
                                        
                                        <?php if ($item['aesthetics'] > 0): ?>
                                            <span class="badge bg-success"><?php echo $translator->translate('Aesthetics'); ?>: <?php echo $item['aesthetics']; ?></span>
                                        <?php endif; ?>
                                        
                                        <?php if ($item['functionality'] > 0): ?>
                                            <span class="badge bg-info"><?php echo $translator->translate('Function'); ?>: <?php echo $item['functionality']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($character['gold'] >= $item['purchase_price']): ?>
                                            <form method="post">
                                                <input type="hidden" name="action" value="buy_furniture">
                                                <input type="hidden" name="furniture_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fas fa-shopping-cart"></i> <?php echo $translator->translate('Buy'); ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled>
                                                <i class="fas fa-coins"></i> <?php echo $translator->translate('Not enough gold'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Furniture filter
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const furnitureRows = document.querySelectorAll('.furniture-row');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            const filterValue = this.getAttribute('data-filter');
            
            furnitureRows.forEach(row => {
                if (filterValue === 'all' || row.getAttribute('data-type') === filterValue) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
});
</script>

<?php
// Helper functions
function getConditionClass($value) {
    if ($value >= 75) return 'bg-success';
    if ($value >= 40) return 'bg-warning';
    return 'bg-danger';
}

function getCleanlinessClass($value) {
    if ($value >= 75) return 'bg-success';
    if ($value >= 40) return 'bg-warning';
    return 'bg-danger';
}

include 'inc/footer.php';
?> 