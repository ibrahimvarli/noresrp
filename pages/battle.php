<?php
// Redirect if not logged in
if (!isLoggedIn()) {
    redirect("index.php?page=login");
}

// Get user's active character
$userId = (int)$_SESSION['user_id'];
$character = getActiveCharacter($userId);

// Redirect if no active character
if (!$character) {
    redirect("index.php?page=characters");
}

// Handle battle system
$error = "";
$success = "";
$enemyInfo = null;
$battleResult = null;

// Check if we're in a battle
if (isset($_GET['enemy_id'])) {
    $enemyId = (int)$_GET['enemy_id'];
    
    // Get enemy details
    global $db;
    $sql = "SELECT * FROM enemies WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $enemyId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $enemyInfo = $result->fetch_assoc();
        
        // Process battle if form submitted
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'battle') {
            // Calculate battle result (basic implementation)
            $battleResult = calculateBattleResult($character, $enemyInfo);
            
            // Apply results (experience, items, health changes, etc.)
            if ($battleResult['won']) {
                // Player won the battle
                $gainedExp = $enemyInfo['exp_reward'];
                $gainedGold = $enemyInfo['gold_reward'];
                
                // Update character
                $sql = "UPDATE characters SET 
                    experience = experience + ?, 
                    gold = gold + ?,
                    health = ?
                    WHERE id = ?";
                $stmt = $db->prepare($sql);
                $remainingHealth = max(1, $character['health'] - $battleResult['damage_taken']);
                $stmt->bind_param("iiii", $gainedExp, $gainedGold, $remainingHealth, $character['id']);
                $stmt->execute();
                
                // Check if leveled up
                $newTotalExp = $character['experience'] + $gainedExp;
                $expForNextLevel = getExpForLevel($character['level'] + 1);
                
                if ($newTotalExp >= $expForNextLevel) {
                    // Level up character
                    $sql = "UPDATE characters SET level = level + 1 WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->bind_param("i", $character['id']);
                    $stmt->execute();
                    
                    $battleResult['leveled_up'] = true;
                }
                
                $success = "You won the battle against {$enemyInfo['name']}!";
            } else {
                // Player lost the battle
                $healthLoss = min($character['health'] - 1, $battleResult['damage_taken']);
                
                // Update character (don't kill them completely)
                $sql = "UPDATE characters SET health = health - ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("ii", $healthLoss, $character['id']);
                $stmt->execute();
                
                $error = "You were defeated by {$enemyInfo['name']}!";
            }
            
            // Refresh character data
            $character = getActiveCharacter($userId);
        }
    } else {
        $error = "Enemy not found.";
    }
}
?>

<div class="battle-container">
    <div class="page-header">
        <h1>Battle</h1>
        <p>Test your skills against enemies</p>
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
    
    <div class="battle-content">
        <?php if ($enemyInfo): ?>
            <!-- Enemy battle view -->
            <div class="battle-screen">
                <div class="battle-participants">
                    <div class="battle-character">
                        <div class="battle-portrait">
                            <img src="assets/images/portraits/<?php echo $character['portrait'] ?: $character['race'] . '_' . $character['class'] . '.jpg'; ?>" alt="<?php echo $character['name']; ?>">
                        </div>
                        <div class="battle-stats">
                            <div class="battle-name"><?php echo $character['name']; ?></div>
                            <div class="health-bar">
                                <div class="health-fill" style="width: <?php echo ($character['health'] / $character['max_health']) * 100; ?>%"></div>
                            </div>
                            <div class="health-text"><?php echo $character['health']; ?>/<?php echo $character['max_health']; ?></div>
                        </div>
                    </div>
                    
                    <div class="battle-vs">VS</div>
                    
                    <div class="battle-enemy">
                        <div class="battle-portrait">
                            <img src="assets/images/enemies/<?php echo $enemyInfo['image'] ?: 'default.jpg'; ?>" alt="<?php echo $enemyInfo['name']; ?>">
                        </div>
                        <div class="battle-stats">
                            <div class="battle-name"><?php echo $enemyInfo['name']; ?></div>
                            <div class="enemy-level">Level <?php echo $enemyInfo['level']; ?></div>
                        </div>
                    </div>
                </div>
                
                <?php if ($battleResult): ?>
                    <!-- Battle results display -->
                    <div class="battle-results">
                        <h3><?php echo $battleResult['won'] ? 'Victory!' : 'Defeat!'; ?></h3>
                        
                        <div class="result-details">
                            <?php if ($battleResult['won']): ?>
                                <div class="result-item">
                                    <span class="result-label">Experience gained:</span>
                                    <span class="result-value"><?php echo $enemyInfo['exp_reward']; ?> XP</span>
                                </div>
                                <div class="result-item">
                                    <span class="result-label">Gold earned:</span>
                                    <span class="result-value"><?php echo $enemyInfo['gold_reward']; ?> Gold</span>
                                </div>
                                <?php if (isset($battleResult['leveled_up']) && $battleResult['leveled_up']): ?>
                                    <div class="level-up-notice">
                                        <i class="fas fa-arrow-up"></i> Level up! You are now level <?php echo $character['level'] + 1; ?>!
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="result-item">
                                    <span class="result-label">Health lost:</span>
                                    <span class="result-value"><?php echo $battleResult['damage_taken']; ?> HP</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="battle-actions">
                            <a href="index.php?page=world" class="btn btn-primary">Return to World</a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Battle form -->
                    <div class="battle-description">
                        <p><?php echo $enemyInfo['description']; ?></p>
                        
                        <div class="enemy-details">
                            <div class="enemy-stat">
                                <span class="stat-label">Strength:</span>
                                <span class="stat-value"><?php echo $enemyInfo['strength']; ?></span>
                            </div>
                            <div class="enemy-stat">
                                <span class="stat-label">Defense:</span>
                                <span class="stat-value"><?php echo $enemyInfo['defense']; ?></span>
                            </div>
                            <div class="enemy-stat">
                                <span class="stat-label">Reward:</span>
                                <span class="stat-value"><?php echo $enemyInfo['exp_reward']; ?> XP, <?php echo $enemyInfo['gold_reward']; ?> Gold</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="battle-actions">
                        <form method="POST" action="" class="battle-form">
                            <input type="hidden" name="action" value="battle">
                            <button type="submit" class="btn btn-danger">Attack</button>
                        </form>
                        <a href="index.php?page=world" class="btn btn-secondary">Flee</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Enemy selection -->
            <div class="enemy-selection">
                <h2>Choose an Enemy to Battle</h2>
                
                <div class="enemy-list">
                    <?php
                    // Get enemies appropriate for character level
                    $characterLevel = $character['level'];
                    $minLevel = max(1, $characterLevel - 2);
                    $maxLevel = $characterLevel + 3;
                    
                    $sql = "SELECT * FROM enemies WHERE level BETWEEN ? AND ? ORDER BY level ASC";
                    $stmt = $db->prepare($sql);
                    $stmt->bind_param("ii", $minLevel, $maxLevel);
                    $stmt->execute();
                    $enemies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    
                    if (empty($enemies)): ?>
                        <div class="no-enemies">
                            <p>No suitable enemies found for your level. Please check back later.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($enemies as $enemy): ?>
                            <div class="enemy-card">
                                <div class="enemy-portrait">
                                    <img src="assets/images/enemies/<?php echo $enemy['image'] ?: 'default.jpg'; ?>" alt="<?php echo $enemy['name']; ?>">
                                    <div class="enemy-level">
                                        <span>Level <?php echo $enemy['level']; ?></span>
                                    </div>
                                </div>
                                
                                <div class="enemy-info">
                                    <h3><?php echo $enemy['name']; ?></h3>
                                    
                                    <div class="enemy-description">
                                        <p><?php echo $enemy['description']; ?></p>
                                    </div>
                                    
                                    <div class="enemy-details">
                                        <div class="enemy-stat">
                                            <span class="stat-label">Strength:</span>
                                            <span class="stat-value"><?php echo $enemy['strength']; ?></span>
                                        </div>
                                        <div class="enemy-stat">
                                            <span class="stat-label">Defense:</span>
                                            <span class="stat-value"><?php echo $enemy['defense']; ?></span>
                                        </div>
                                        <div class="enemy-stat">
                                            <span class="stat-label">Reward:</span>
                                            <span class="stat-value"><?php echo $enemy['exp_reward']; ?> XP, <?php echo $enemy['gold_reward']; ?> Gold</span>
                                        </div>
                                    </div>
                                    
                                    <div class="enemy-actions">
                                        <a href="index.php?page=battle&enemy_id=<?php echo $enemy['id']; ?>" class="btn btn-danger">
                                            <i class="fas fa-sword"></i> Battle
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.battle-container {
    max-width: var(--container-width);
    margin: 0 auto;
}

.page-header {
    text-align: center;
    margin-bottom: 3rem;
}

.page-header h1 {
    font-size: 3.6rem;
    color: var(--accent-primary);
}

.battle-screen {
    background-color: var(--bg-secondary);
    border-radius: var(--border-radius);
    padding: 3rem;
    box-shadow: 0 5px 15px var(--shadow);
    margin-bottom: 3rem;
}

.battle-participants {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 3rem;
}

.battle-character, .battle-enemy {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    max-width: 40%;
}

.battle-vs {
    font-size: 3rem;
    font-weight: 700;
    color: var(--accent-primary);
}

.battle-portrait {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid var(--accent-primary);
    margin-bottom: 1.5rem;
}

.battle-portrait img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.battle-stats {
    text-align: center;
    width: 100%;
}

.battle-name {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.health-bar {
    height: 10px;
    background-color: var(--bg-tertiary);
    border-radius: 5px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.health-fill {
    height: 100%;
    background-color: var(--health-color);
    border-radius: 5px;
}

.health-text {
    font-size: 1.4rem;
    color: var(--health-color);
    font-weight: 600;
}

.enemy-level {
    font-size: 1.4rem;
    color: var(--text-secondary);
    margin-bottom: 1rem;
}

.battle-description {
    margin-bottom: 3rem;
    padding: 2rem;
    background-color: var(--bg-tertiary);
    border-radius: var(--border-radius);
}

.enemy-details {
    display: flex;
    flex-wrap: wrap;
    gap: 2rem;
    margin-top: 2rem;
}

.enemy-stat {
    background-color: var(--bg-primary);
    padding: 1rem 1.5rem;
    border-radius: var(--border-radius);
}

.stat-label {
    font-weight: 600;
    margin-right: 0.5rem;
}

.battle-actions {
    display: flex;
    justify-content: center;
    gap: 2rem;
}

.battle-results {
    text-align: center;
    padding: 2rem;
    background-color: var(--bg-tertiary);
    border-radius: var(--border-radius);
    margin-bottom: 3rem;
}

.battle-results h3 {
    font-size: 2.8rem;
    margin-bottom: 2rem;
}

.result-details {
    margin-bottom: 3rem;
}

.result-item {
    margin-bottom: 1rem;
    font-size: 1.6rem;
}

.result-label {
    font-weight: 600;
    margin-right: 1rem;
}

.level-up-notice {
    margin-top: 2rem;
    font-size: 2rem;
    color: var(--success);
    font-weight: 700;
}

/* Enemy Selection Styles */
.enemy-selection h2 {
    margin-bottom: 3rem;
    text-align: center;
}

.enemy-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 2rem;
}

.enemy-card {
    background-color: var(--bg-secondary);
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: 0 5px 15px var(--shadow);
    transition: transform 0.3s ease;
}

.enemy-card:hover {
    transform: translateY(-5px);
}

.enemy-portrait {
    position: relative;
    height: 180px;
}

.enemy-portrait img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.enemy-level {
    position: absolute;
    bottom: 10px;
    right: 10px;
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-size: 1.2rem;
    font-weight: 600;
}

.enemy-info {
    padding: 2rem;
}

.enemy-info h3 {
    margin-bottom: 1rem;
    font-size: 2.2rem;
}

.enemy-description {
    margin-bottom: 2rem;
    color: var(--text-secondary);
}

.enemy-actions {
    margin-top: 2rem;
    text-align: center;
}

.no-enemies {
    grid-column: 1 / -1;
    text-align: center;
    padding: 5rem;
    background-color: var(--bg-tertiary);
    border-radius: var(--border-radius);
}

/* Helper function implementation */
function calculateBattleResult($character, $enemy) {
    // Basic battle calculation (can be expanded for more complex systems)
    $characterAttack = $character['strength'] + rand(1, 10);
    $enemyDefense = $enemy['defense'] + rand(1, 5);
    
    $enemyAttack = $enemy['strength'] + rand(1, 10);
    $characterDefense = ($character['dexterity'] / 2) + rand(1, 5);
    
    $damageToEnemy = max(1, $characterAttack - $enemyDefense);
    $damageToCharacter = max(1, $enemyAttack - $characterDefense);
    
    // Determine the winner (simplified)
    $characterEffective = $character['health'] / $damageToCharacter;
    $enemyEffective = $enemy['health'] / $damageToEnemy;
    
    $won = $characterEffective >= $enemyEffective;
    
    return [
        'won' => $won,
        'damage_dealt' => $damageToEnemy,
        'damage_taken' => $won ? rand(1, $damageToCharacter) : $damageToCharacter,
    ];
}
</style> 