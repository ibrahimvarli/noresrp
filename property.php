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

// Get character properties
$owned_properties = getCharacterProperties($character['id']);

// Get available properties in current location
$available_properties = getAvailableProperties($character['location_id']);

// Handle property actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
    $character_property_id = isset($_POST['character_property_id']) ? intval($_POST['character_property_id']) : 0;
    
    switch ($action) {
        case 'purchase':
            $result = purchaseProperty($character['id'], $property_id);
            if ($result['success']) {
                $_SESSION['success_message'] = $result['message'];
            } else {
                $_SESSION['error_message'] = $result['message'];
            }
            break;
            
        case 'rent':
            $result = rentProperty($character['id'], $property_id);
            if ($result['success']) {
                $_SESSION['success_message'] = $result['message'];
            } else {
                $_SESSION['error_message'] = $result['message'];
            }
            break;
            
        case 'pay_rent':
            $result = payPropertyRent($character_property_id);
            if ($result['success']) {
                $_SESSION['success_message'] = $result['message'];
            } else {
                $_SESSION['error_message'] = $result['message'];
            }
            break;
            
        case 'maintain':
            $maintenance_type = isset($_POST['maintenance_type']) ? $_POST['maintenance_type'] : 'regular';
            $result = maintainProperty($character_property_id, $maintenance_type);
            if ($result['success']) {
                $_SESSION['success_message'] = $result['message'];
            } else {
                $_SESSION['error_message'] = $result['message'];
            }
            break;
            
        case 'clean':
            $result = cleanProperty($character_property_id);
            if ($result['success']) {
                $_SESSION['success_message'] = $result['message'];
            } else {
                $_SESSION['error_message'] = $result['message'];
            }
            break;
            
        case 'sell':
            $result = sellProperty($character_property_id);
            if ($result['success']) {
                $_SESSION['success_message'] = $result['message'];
            } else {
                $_SESSION['error_message'] = $result['message'];
            }
            break;
            
        case 'end_rental':
            $result = endRental($character_property_id);
            if ($result['success']) {
                $_SESSION['success_message'] = $result['message'];
            } else {
                $_SESSION['error_message'] = $result['message'];
            }
            break;
    }
    
    // Refresh page after action
    header("Location: property.php");
    exit();
}

// Get page from query string
$page = isset($_GET['page']) ? $_GET['page'] : 'main';
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-home"></i> <?php echo $translator->translate('Property System'); ?></h2>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <a class="nav-link <?php echo $page == 'main' ? 'active' : ''; ?>" href="property.php">
                        <i class="fas fa-home"></i> <?php echo $translator->translate('My Properties'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page == 'market' ? 'active' : ''; ?>" href="property.php?page=market">
                        <i class="fas fa-store"></i> <?php echo $translator->translate('Property Market'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page == 'furniture' ? 'active' : ''; ?>" href="property.php?page=furniture">
                        <i class="fas fa-couch"></i> <?php echo $translator->translate('Furniture'); ?>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="card-body">
            <?php if ($page == 'main'): ?>
                <!-- My Properties Page -->
                <h3 class="card-title"><?php echo $translator->translate('My Properties'); ?></h3>
                
                <?php if (empty($owned_properties)): ?>
                    <div class="alert alert-info">
                        <?php echo $translator->translate('You do not own or rent any properties. Visit the Property Market to find a home!'); ?>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($owned_properties as $property): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5><?php echo htmlspecialchars($property['name']); ?></h5>
                                        <span class="badge <?php echo $property['ownership_type'] == 'owned' ? 'bg-success' : 'bg-primary'; ?>">
                                            <?php echo $property['ownership_type'] == 'owned' ? $translator->translate('Owned') : $translator->translate('Rented'); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <img src="img/properties/<?php echo $property['image']; ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($property['name']); ?>">
                                            </div>
                                            <div class="col-md-8">
                                                <p><strong><?php echo $translator->translate('Location'); ?>:</strong> <?php echo htmlspecialchars($property['location_name']); ?></p>
                                                <p><strong><?php echo $translator->translate('Type'); ?>:</strong> <?php echo ucfirst($property['type']); ?></p>
                                                <p><strong><?php echo $translator->translate('Size'); ?>:</strong> <?php echo $property['size']; ?> m²</p>
                                                <p><strong><?php echo $translator->translate('Rooms'); ?>:</strong> <?php echo $property['rooms']; ?></p>
                                                
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
                                                
                                                <?php if ($property['ownership_type'] == 'rented'): ?>
                                                    <p><strong><?php echo $translator->translate('Next Rent Due'); ?>:</strong> 
                                                       <?php echo date('Y-m-d', strtotime($property['next_payment'])); ?>
                                                       (<?php echo $property['rent_price']; ?> <?php echo $translator->translate('gold'); ?>)
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <h6><?php echo $translator->translate('Actions'); ?></h6>
                                            <div class="btn-group">
                                                <a href="property_detail.php?id=<?php echo $property['id']; ?>" class="btn btn-primary">
                                                    <i class="fas fa-door-open"></i> <?php echo $translator->translate('Enter'); ?>
                                                </a>
                                                
                                                <!-- Clean button -->
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="clean">
                                                    <input type="hidden" name="character_property_id" value="<?php echo $property['id']; ?>">
                                                    <button type="submit" class="btn btn-info">
                                                        <i class="fas fa-broom"></i> <?php echo $translator->translate('Clean'); ?>
                                                    </button>
                                                </form>
                                                
                                                <!-- Maintain button with dropdown -->
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-warning dropdown-toggle" data-bs-toggle="dropdown">
                                                        <i class="fas fa-tools"></i> <?php echo $translator->translate('Maintain'); ?>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <form method="post">
                                                            <input type="hidden" name="action" value="maintain">
                                                            <input type="hidden" name="character_property_id" value="<?php echo $property['id']; ?>">
                                                            <input type="hidden" name="maintenance_type" value="regular">
                                                            <button type="submit" class="dropdown-item">
                                                                <?php echo $translator->translate('Regular Maintenance'); ?> (<?php echo $property['maintenance_cost']; ?> <?php echo $translator->translate('gold'); ?>)
                                                            </button>
                                                        </form>
                                                        <form method="post">
                                                            <input type="hidden" name="action" value="maintain">
                                                            <input type="hidden" name="character_property_id" value="<?php echo $property['id']; ?>">
                                                            <input type="hidden" name="maintenance_type" value="repair">
                                                            <button type="submit" class="dropdown-item">
                                                                <?php echo $translator->translate('Repair Damage'); ?> (<?php echo $property['maintenance_cost'] * 2; ?> <?php echo $translator->translate('gold'); ?>)
                                                            </button>
                                                        </form>
                                                        <form method="post">
                                                            <input type="hidden" name="action" value="maintain">
                                                            <input type="hidden" name="character_property_id" value="<?php echo $property['id']; ?>">
                                                            <input type="hidden" name="maintenance_type" value="upgrade">
                                                            <button type="submit" class="dropdown-item">
                                                                <?php echo $translator->translate('Upgrade Property'); ?> (<?php echo $property['maintenance_cost'] * 5; ?> <?php echo $translator->translate('gold'); ?>)
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                                
                                                <?php if ($property['ownership_type'] == 'rented'): ?>
                                                    <!-- Pay rent button -->
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="action" value="pay_rent">
                                                        <input type="hidden" name="character_property_id" value="<?php echo $property['id']; ?>">
                                                        <button type="submit" class="btn btn-success">
                                                            <i class="fas fa-money-bill"></i> <?php echo $translator->translate('Pay Rent'); ?> (<?php echo $property['rent_price']; ?> <?php echo $translator->translate('gold'); ?>)
                                                        </button>
                                                    </form>
                                                    
                                                    <!-- End rental button -->
                                                    <form method="post" class="d-inline" onsubmit="return confirm('<?php echo $translator->translate('Are you sure you want to end this rental?'); ?>');">
                                                        <input type="hidden" name="action" value="end_rental">
                                                        <input type="hidden" name="character_property_id" value="<?php echo $property['id']; ?>">
                                                        <button type="submit" class="btn btn-danger">
                                                            <i class="fas fa-door-closed"></i> <?php echo $translator->translate('End Rental'); ?>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <!-- Sell property button -->
                                                    <form method="post" class="d-inline" onsubmit="return confirm('<?php echo $translator->translate('Are you sure you want to sell this property?'); ?>');">
                                                        <input type="hidden" name="action" value="sell">
                                                        <input type="hidden" name="character_property_id" value="<?php echo $property['id']; ?>">
                                                        <button type="submit" class="btn btn-danger">
                                                            <i class="fas fa-money-bill"></i> <?php echo $translator->translate('Sell'); ?> (<?php echo floor($property['purchase_price'] * 0.75); ?> <?php echo $translator->translate('gold'); ?>)
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($page == 'market'): ?>
                <!-- Property Market Page -->
                <h3 class="card-title"><?php echo $translator->translate('Property Market in'); ?> <?php echo getLocationName($character['location_id']); ?></h3>
                
                <?php if (empty($available_properties)): ?>
                    <div class="alert alert-info">
                        <?php echo $translator->translate('There are no properties available in this location.'); ?>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($available_properties as $property): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5><?php echo htmlspecialchars($property['name']); ?></h5>
                                    </div>
                                    
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <img src="img/properties/<?php echo $property['image']; ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($property['name']); ?>">
                                            </div>
                                            <div class="col-md-8">
                                                <p><?php echo htmlspecialchars($property['description']); ?></p>
                                                <p><strong><?php echo $translator->translate('Type'); ?>:</strong> <?php echo ucfirst($property['type']); ?></p>
                                                <p><strong><?php echo $translator->translate('Size'); ?>:</strong> <?php echo $property['size']; ?> m²</p>
                                                <p><strong><?php echo $translator->translate('Rooms'); ?>:</strong> <?php echo $property['rooms']; ?></p>
                                                <p><strong><?php echo $translator->translate('Prestige'); ?>:</strong> <?php echo $property['prestige']; ?></p>
                                                <p><strong><?php echo $translator->translate('Purchase Price'); ?>:</strong> <?php echo $property['purchase_price']; ?> <?php echo $translator->translate('gold'); ?></p>
                                                <p><strong><?php echo $translator->translate('Rent Price'); ?>:</strong> <?php echo $property['rent_price']; ?> <?php echo $translator->translate('gold'); ?> <?php echo $translator->translate('per week'); ?></p>
                                                <p><strong><?php echo $translator->translate('Maintenance Cost'); ?>:</strong> <?php echo $property['maintenance_cost']; ?> <?php echo $translator->translate('gold'); ?> <?php echo $translator->translate('per day'); ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <?php if ($character['gold'] >= $property['purchase_price']): ?>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="purchase">
                                                    <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="fas fa-money-bill"></i> <?php echo $translator->translate('Purchase'); ?> (<?php echo $property['purchase_price']; ?> <?php echo $translator->translate('gold'); ?>)
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-secondary" disabled>
                                                    <i class="fas fa-money-bill"></i> <?php echo $translator->translate('Purchase'); ?> (<?php echo $translator->translate('Not enough gold'); ?>)
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($character['gold'] >= $property['rent_price']): ?>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="rent">
                                                    <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-key"></i> <?php echo $translator->translate('Rent'); ?> (<?php echo $property['rent_price']; ?> <?php echo $translator->translate('gold'); ?>)
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-secondary" disabled>
                                                    <i class="fas fa-key"></i> <?php echo $translator->translate('Rent'); ?> (<?php echo $translator->translate('Not enough gold'); ?>)
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($page == 'furniture'): ?>
                <!-- Furniture Page -->
                <div class="alert alert-info">
                    <?php echo $translator->translate('Please enter a property to view and manage furniture.'); ?>
                </div>
                
                <div class="text-center mb-4">
                    <a href="property.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> <?php echo $translator->translate('Back to My Properties'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

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