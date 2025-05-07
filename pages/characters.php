<?php
// Redirect if not logged in
if (!isLoggedIn()) {
    redirect("index.php?page=login");
}

// Get user ID
$userId = (int)$_SESSION['user_id'];

// Process character creation
$error = "";
$success = "";

// Handle actions (create, select, delete)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        // Create new character
        if ($_POST['action'] == 'create') {
            $name = sanitize($_POST['name']);
            $race = sanitize($_POST['race']);
            $class = sanitize($_POST['class']);
            
            // Validate input
            if (empty($name) || empty($race) || empty($class)) {
                $error = "All fields are required.";
            } elseif (strlen($name) < 3 || strlen($name) > 20) {
                $error = "Character name must be between 3 and 20 characters.";
            } elseif (!in_array($race, ['human', 'elf', 'dwarf', 'orc'])) {
                $error = "Invalid race selected.";
            } elseif (!in_array($class, ['warrior', 'mage', 'rogue', 'cleric'])) {
                $error = "Invalid class selected.";
            } else {
                global $db;
                
                // Check if character name already exists
                $sql = "SELECT id FROM characters WHERE name = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("s", $name);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error = "Character name already taken. Please choose another.";
                } else {
                    // Calculate starting stats based on race and class
                    $stats = calculateCharacterStats($race, $class, 1);
                    
                    // Insert new character
                    $sql = "INSERT INTO characters (user_id, name, race, class, health, max_health, mana, max_mana, strength, dexterity, intelligence, wisdom, charisma) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($sql);
                    $stmt->bind_param("isssiiidiiiii", 
                        $userId, $name, $race, $class, 
                        $stats['health'], $stats['health'], 
                        $stats['mana'], $stats['mana'], 
                        $stats['strength'], $stats['dexterity'], 
                        $stats['intelligence'], $stats['wisdom'], 
                        $stats['charisma']);
                    
                    if ($stmt->execute()) {
                        $characterId = $db->getLastId();
                        
                        // Give starting equipment
                        $startingItems = [];
                        switch ($class) {
                            case 'warrior':
                                $startingItems = [1, 2]; // Rusty Sword, Wooden Shield
                                break;
                            case 'mage':
                                $startingItems = [4]; // Apprentice Staff
                                break;
                            case 'rogue':
                                $startingItems = [1]; // Rusty Sword
                                break;
                            case 'cleric':
                                $startingItems = [4, 2]; // Apprentice Staff, Wooden Shield
                                break;
                        }
                        
                        // Add starting items to inventory
                        foreach ($startingItems as $itemId) {
                            $sql = "INSERT INTO inventory (character_id, item_id, quantity) VALUES (?, ?, 1)";
                            $stmt = $db->prepare($sql);
                            $stmt->bind_param("ii", $characterId, $itemId);
                            $stmt->execute();
                        }
                        
                        // Give everyone basic health potions
                        $sql = "INSERT INTO inventory (character_id, item_id, quantity) VALUES (?, 3, 3)";
                        $stmt = $db->prepare($sql);
                        $stmt->bind_param("i", $characterId);
                        $stmt->execute();
                        
                        // Add basic clothing/armor
                        $sql = "INSERT INTO inventory (character_id, item_id, quantity) VALUES (?, 5, 1)";
                        $stmt = $db->prepare($sql);
                        $stmt->bind_param("i", $characterId);
                        $stmt->execute();
                        
                        $success = "Character created successfully!";
                    } else {
                        $error = "Failed to create character. Please try again.";
                    }
                }
            }
        }
        // Select character
        else if ($_POST['action'] == 'select' && isset($_POST['character_id'])) {
            $characterId = (int)$_POST['character_id'];
            
            global $db;
            
            // Reset all character active status
            $sql = "UPDATE characters SET is_active = 0 WHERE user_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            
            // Set selected character as active
            $sql = "UPDATE characters SET is_active = 1 WHERE id = ? AND user_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ii", $characterId, $userId);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $success = "Character selected successfully!";
            } else {
                $error = "Failed to select character.";
            }
        }
        // Delete character
        else if ($_POST['action'] == 'delete' && isset($_POST['character_id'])) {
            $characterId = (int)$_POST['character_id'];
            
            global $db;
            
            // Check if character belongs to user
            $sql = "SELECT id FROM characters WHERE id = ? AND user_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ii", $characterId, $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Delete character (cascade will delete inventory, skills, etc.)
                $sql = "DELETE FROM characters WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("i", $characterId);
                
                if ($stmt->execute()) {
                    $success = "Character deleted successfully!";
                } else {
                    $error = "Failed to delete character.";
                }
            } else {
                $error = "Character not found or you don't have permission to delete it.";
            }
        }
    }
}

// Get user's characters
global $db;
$sql = "SELECT * FROM characters WHERE user_id = ? ORDER BY level DESC, name ASC";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$characters = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get active character
$activeCharacter = getActiveCharacter($userId);
?>

<div class="characters-container">
    <div class="page-header">
        <h1>Your Characters</h1>
        <p>Manage your heroes and begin your adventure</p>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <div class="characters-wrapper">
        <div class="character-list">
            <div class="section-header">
                <h2>Your Heroes</h2>
                <?php if (count($characters) < 3): ?>
                    <button class="btn btn-primary" id="create-btn">
                        <i class="fas fa-plus"></i> Create Character
                    </button>
                <?php endif; ?>
            </div>
            
            <?php if (empty($characters)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <h3>No Characters Yet</h3>
                    <p>Create your first character to begin your adventure!</p>
                    <button class="btn btn-primary" id="empty-create-btn">
                        <i class="fas fa-plus"></i> Create Character
                    </button>
                </div>
            <?php else: ?>
                <div class="character-cards">
                    <?php foreach ($characters as $character): ?>
                        <div class="character-card <?php echo $character['is_active'] ? 'active' : ''; ?>">
                            <div class="character-portrait">
                                <img src="assets/images/portraits/<?php echo $character['portrait'] ?: $character['race'] . '_' . $character['class'] . '.jpg'; ?>" alt="<?php echo $character['name']; ?>">
                                <div class="character-level">
                                    <span><?php echo $character['level']; ?></span>
                                </div>
                            </div>
                            
                            <div class="character-info">
                                <h3><?php echo $character['name']; ?></h3>
                                <div class="character-details">
                                    <span class="race <?php echo $character['race']; ?>">
                                        <?php echo ucfirst($character['race']); ?>
                                    </span>
                                    <span class="class <?php echo $character['class']; ?>">
                                        <?php echo ucfirst($character['class']); ?>
                                    </span>
                                </div>
                                
                                <div class="character-stats">
                                    <div class="stat health">
                                        <div class="stat-icon">
                                            <i class="fas fa-heart"></i>
                                        </div>
                                        <div class="stat-bar">
                                            <div class="stat-fill" style="width: <?php echo ($character['health'] / $character['max_health']) * 100; ?>%"></div>
                                        </div>
                                        <div class="stat-value">
                                            <?php echo $character['health']; ?>/<?php echo $character['max_health']; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="stat mana">
                                        <div class="stat-icon">
                                            <i class="fas fa-hat-wizard"></i>
                                        </div>
                                        <div class="stat-bar">
                                            <div class="stat-fill" style="width: <?php echo ($character['mana'] / $character['max_mana']) * 100; ?>%"></div>
                                        </div>
                                        <div class="stat-value">
                                            <?php echo $character['mana']; ?>/<?php echo $character['max_mana']; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="character-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php 
                                    $locationId = $character['location_id'];
                                    $sql = "SELECT name FROM locations WHERE id = ?";
                                    $stmt = $db->prepare($sql);
                                    $stmt->bind_param("i", $locationId);
                                    $stmt->execute();
                                    $location = $stmt->get_result()->fetch_assoc();
                                    echo $location ? $location['name'] : 'Unknown';
                                    ?>
                                </div>
                                
                                <div class="character-actions">
                                    <?php if (!$character['is_active']): ?>
                                        <form method="POST" action="" class="select-form">
                                            <input type="hidden" name="action" value="select">
                                            <input type="hidden" name="character_id" value="<?php echo $character['id']; ?>">
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-check"></i> Select
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <a href="?page=world" class="btn btn-success btn-sm">
                                            <i class="fas fa-play"></i> Play
                                        </a>
                                    <?php endif; ?>
                                    
                                    <form method="POST" action="" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this character? This action cannot be undone.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="character_id" value="<?php echo $character['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Character Creation Form (Hidden by default) -->
        <div class="character-creation" id="character-creation">
            <div class="section-header">
                <h2>Create New Character</h2>
                <button class="btn btn-secondary" id="cancel-btn">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
            
            <form class="creation-form" method="POST" action="">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label for="name">Character Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter character name" required>
                    <small>3-20 characters, letters and spaces only</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="race">Race</label>
                        <select id="race" name="race" required>
                            <option value="" disabled selected>Select race</option>
                            <option value="human">Human</option>
                            <option value="elf">Elf</option>
                            <option value="dwarf">Dwarf</option>
                            <option value="orc">Orc</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="class">Class</label>
                        <select id="class" name="class" required>
                            <option value="" disabled selected>Select class</option>
                            <option value="warrior">Warrior</option>
                            <option value="mage">Mage</option>
                            <option value="rogue">Rogue</option>
                            <option value="cleric">Cleric</option>
                        </select>
                    </div>
                </div>
                
                <div class="character-preview">
                    <div class="preview-portrait">
                        <img src="assets/images/portraits/default.jpg" id="portrait-preview" alt="Character Portrait">
                    </div>
                    
                    <div class="preview-stats" id="character-stats">
                        <div class="stats-message">
                            <p>Select race and class to view starting stats</p>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-user-plus"></i> Create Character
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle character creation form
    const characterList = document.querySelector('.character-list');
    const characterCreation = document.querySelector('.character-creation');
    const createBtn = document.getElementById('create-btn');
    const emptyCreateBtn = document.getElementById('empty-create-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    
    function showCreationForm() {
        characterList.style.display = 'none';
        characterCreation.style.display = 'block';
    }
    
    function hideCreationForm() {
        characterCreation.style.display = 'none';
        characterList.style.display = 'block';
    }
    
    if (createBtn) {
        createBtn.addEventListener('click', showCreationForm);
    }
    
    if (emptyCreateBtn) {
        emptyCreateBtn.addEventListener('click', showCreationForm);
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', hideCreationForm);
    }
    
    // Update portrait preview
    const raceSelect = document.getElementById('race');
    const classSelect = document.getElementById('class');
    const portraitPreview = document.getElementById('portrait-preview');
    
    function updatePortrait() {
        const race = raceSelect.value;
        const characterClass = classSelect.value;
        
        if (race && characterClass) {
            portraitPreview.src = `assets/images/portraits/${race}_${characterClass}.jpg`;
        }
    }
    
    if (raceSelect && classSelect) {
        raceSelect.addEventListener('change', updatePortrait);
        classSelect.addEventListener('change', updatePortrait);
    }
    
    // Character stats preview
    const statsContainer = document.getElementById('character-stats');
    
    function updateStatsPreview() {
        if (!raceSelect || !classSelect || !statsContainer) return;
        
        const race = raceSelect.value;
        const characterClass = classSelect.value;
        
        if (!race || !characterClass) {
            statsContainer.innerHTML = `
                <div class="stats-message">
                    <p>Select race and class to view starting stats</p>
                </div>
            `;
            return;
        }
        
        // Base stats
        let stats = {
            health: 100,
            mana: 50,
            strength: 10,
            dexterity: 10,
            intelligence: 10,
            wisdom: 10,
            charisma: 10
        };
        
        // Apply race bonuses
        switch (race) {
            case 'human':
                stats.strength += 2;
                stats.charisma += 2;
                break;
            case 'elf':
                stats.dexterity += 3;
                stats.intelligence += 1;
                break;
            case 'dwarf':
                stats.strength += 3;
                stats.health += 20;
                break;
            case 'orc':
                stats.strength += 4;
                stats.intelligence -= 1;
                break;
        }
        
        // Apply class bonuses
        switch (characterClass) {
            case 'warrior':
                stats.health += 30;
                stats.strength += 3;
                break;
            case 'mage':
                stats.mana += 30;
                stats.intelligence += 3;
                break;
            case 'rogue':
                stats.dexterity += 3;
                stats.charisma += 1;
                break;
            case 'cleric':
                stats.wisdom += 3;
                stats.mana += 15;
                stats.health += 10;
                break;
        }
        
        // Update stats display
        statsContainer.innerHTML = `
            <div class="stat-row">
                <div class="stat-col">
                    <div class="stat-label">Health</div>
                    <div class="stat-value health">${stats.health}</div>
                </div>
                <div class="stat-col">
                    <div class="stat-label">Mana</div>
                    <div class="stat-value mana">${stats.mana}</div>
                </div>
            </div>
            <div class="stat-row">
                <div class="stat-col">
                    <div class="stat-label">Strength</div>
                    <div class="stat-value">${stats.strength}</div>
                </div>
                <div class="stat-col">
                    <div class="stat-label">Dexterity</div>
                    <div class="stat-value">${stats.dexterity}</div>
                </div>
            </div>
            <div class="stat-row">
                <div class="stat-col">
                    <div class="stat-label">Intelligence</div>
                    <div class="stat-value">${stats.intelligence}</div>
                </div>
                <div class="stat-col">
                    <div class="stat-label">Wisdom</div>
                    <div class="stat-value">${stats.wisdom}</div>
                </div>
            </div>
            <div class="stat-row">
                <div class="stat-col">
                    <div class="stat-label">Charisma</div>
                    <div class="stat-value">${stats.charisma}</div>
                </div>
            </div>
        `;
    }
    
    if (raceSelect && classSelect) {
        raceSelect.addEventListener('change', updateStatsPreview);
        classSelect.addEventListener('change', updateStatsPreview);
        
        // Initialize stats preview
        updateStatsPreview();
    }
});
</script>

<style>
.characters-container {
    max-width: var(--container-width);
    margin: 0 auto;
}

.page-header {
    text-align: center;
    margin-bottom: 4rem;
}

.page-header h1 {
    font-size: 4rem;
    margin-bottom: 1rem;
    color: var(--accent-primary);
}

.page-header p {
    font-size: 1.8rem;
    color: var(--text-secondary);
}

.characters-wrapper {
    position: relative;
    min-height: 500px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 3rem;
}

.section-header h2 {
    font-size: 2.4rem;
    margin: 0;
}

.empty-state {
    text-align: center;
    padding: 5rem 0;
    background-color: var(--bg-secondary);
    border-radius: var(--border-radius);
    box-shadow: 0 5px 15px var(--shadow);
}

.empty-icon {
    font-size: 5rem;
    color: var(--text-secondary);
    margin-bottom: 2rem;
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 2.4rem;
    margin-bottom: 1rem;
}

.empty-state p {
    font-size: 1.6rem;
    color: var(--text-secondary);
    margin-bottom: 3rem;
}

.character-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2rem;
}

.character-card {
    background-color: var(--bg-secondary);
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: 0 5px 15px var(--shadow);
    transition: transform 0.3s ease;
    position: relative;
}

.character-card:hover {
    transform: translateY(-5px);
}

.character-card.active {
    border: 2px solid var(--accent-primary);
}

.character-card.active::before {
    content: 'Active';
    position: absolute;
    top: 1rem;
    right: 1rem;
    background-color: var(--accent-primary);
    color: white;
    padding: 0.3rem 1rem;
    border-radius: 2rem;
    font-size: 1.2rem;
    font-weight: 600;
    z-index: 2;
}

.character-portrait {
    position: relative;
    height: 200px;
}

.character-portrait img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.character-level {
    position: absolute;
    bottom: -15px;
    left: 20px;
    width: 40px;
    height: 40px;
    background-color: var(--accent-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.8rem;
    border: 3px solid var(--bg-secondary);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.character-info {
    padding: 2rem;
    padding-top: 2.5rem;
}

.character-info h3 {
    font-size: 2.2rem;
    margin-bottom: 1rem;
}

.character-details {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
}

.character-details span {
    padding: 0.3rem 1rem;
    border-radius: 2rem;
    font-size: 1.2rem;
    font-weight: 600;
}

.race {
    background-color: rgba(0, 0, 0, 0.1);
}

.race.human {
    color: var(--human-color);
    background-color: rgba(52, 152, 219, 0.1);
}

.race.elf {
    color: var(--elf-color);
    background-color: rgba(46, 204, 113, 0.1);
}

.race.dwarf {
    color: var(--dwarf-color);
    background-color: rgba(230, 126, 34, 0.1);
}

.race.orc {
    color: var(--orc-color);
    background-color: rgba(231, 76, 60, 0.1);
}

.class {
    background-color: rgba(0, 0, 0, 0.1);
}

.class.warrior {
    color: var(--warrior-color);
    background-color: rgba(231, 76, 60, 0.1);
}

.class.mage {
    color: var(--mage-color);
    background-color: rgba(52, 152, 219, 0.1);
}

.class.rogue {
    color: var(--rogue-color);
    background-color: rgba(241, 196, 15, 0.1);
}

.class.cleric {
    color: var(--cleric-color);
    background-color: rgba(46, 204, 113, 0.1);
}

.character-stats {
    margin-bottom: 2rem;
}

.stat {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
}

.stat-icon {
    width: 20px;
    text-align: center;
    margin-right: 1rem;
}

.stat.health .stat-icon {
    color: var(--health-color);
}

.stat.mana .stat-icon {
    color: var(--mana-color);
}

.stat-bar {
    flex: 1;
    height: 8px;
    background-color: var(--bg-tertiary);
    border-radius: 4px;
    overflow: hidden;
    margin-right: 1rem;
}

.stat-fill {
    height: 100%;
    border-radius: 4px;
}

.stat.health .stat-fill {
    background-color: var(--health-color);
}

.stat.mana .stat-fill {
    background-color: var(--mana-color);
}

.stat-value {
    font-size: 1.2rem;
    font-weight: 600;
    min-width: 60px;
    text-align: right;
}

.character-location {
    display: flex;
    align-items: center;
    margin-bottom: 2rem;
    font-size: 1.4rem;
    color: var(--text-secondary);
}

.character-location i {
    margin-right: 0.8rem;
    color: var(--accent-primary);
}

.character-actions {
    display: flex;
    gap: 1rem;
}

.character-actions .btn {
    flex: 1;
}

/* Character Creation */
.character-creation {
    display: none;
    background-color: var(--bg-secondary);
    border-radius: var(--border-radius);
    padding: 3rem;
    box-shadow: 0 5px 15px var(--shadow);
}

.creation-form {
    max-width: 600px;
    margin: 0 auto;
}

.form-group {
    margin-bottom: 2rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.8rem;
    font-weight: 600;
}

.form-group small {
    display: block;
    margin-top: 0.5rem;
    font-size: 1.2rem;
    color: var(--text-secondary);
}

.form-row {
    display: flex;
    gap: 2rem;
}

.form-row .form-group {
    flex: 1;
}

.creation-form input[type="text"],
.creation-form select {
    width: 100%;
    padding: 1.2rem 1.5rem;
    border: 1px solid var(--border);
    border-radius: var(--border-radius);
    background-color: var(--bg-tertiary);
    color: var(--text-primary);
    font-size: 1.6rem;
    transition: all var(--transition-time);
}

.creation-form input:focus,
.creation-form select:focus {
    outline: none;
    border-color: var(--accent-primary);
    box-shadow: 0 0 0 2px rgba(156, 39, 176, 0.2);
}

.character-preview {
    display: flex;
    gap: 3rem;
    margin-bottom: 3rem;
    background-color: var(--bg-tertiary);
    border-radius: var(--border-radius);
    padding: 2rem;
}

.preview-portrait {
    flex: 1;
    max-width: 200px;
}

.preview-portrait img {
    width: 100%;
    height: auto;
    border-radius: var(--border-radius);
    box-shadow: 0 2px 10px var(--shadow);
}

.preview-stats {
    flex: 2;
}

.stats-message {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stats-message p {
    font-style: italic;
    color: var(--text-secondary);
}

.stat-row {
    display: flex;
    gap: 2rem;
    margin-bottom: 1.5rem;
}

.stat-col {
    flex: 1;
}

.stat-label {
    font-size: 1.4rem;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 600;
}

.stat-value.health {
    color: var(--health-color);
}

.stat-value.mana {
    color: var(--mana-color);
}

@media (max-width: 768px) {
    .character-preview {
        flex-direction: column;
    }
    
    .preview-portrait {
        max-width: 150px;
        margin: 0 auto;
    }
    
    .form-row {
        flex-direction: column;
        gap: 0;
    }
}
</style> 