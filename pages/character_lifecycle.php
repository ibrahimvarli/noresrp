<?php
/**
 * Character Lifecycle Page
 * Displays information about a character's aging, life stage, and age-related abilities
 */

// Include necessary files
require_once 'inc/functions.php';
require_once 'inc/character_lifecycle.php';

// Must be logged in
if (!isLoggedIn()) {
    header('Location: index.php?page=login');
    exit;
}

// Ensure active character is selected
if (!isset($_SESSION['active_character'])) {
    header('Location: index.php?page=characters');
    exit;
}

$activeCharacterId = $_SESSION['active_character'];

// Get character data
$character = getCharacter($activeCharacterId);
if (!$character) {
    header('Location: index.php?page=characters');
    exit;
}

// Get game time
$gameTime = getCurrentGameTime();
$currentGameDate = $gameTime['current_date'];

// Calculate next birthday
$nextBirthday = '';
$daysUntilBirthday = '';
if ($character['birthday']) {
    $birthdayMonth = substr($character['birthday'], 5, 2);
    $birthdayDay = substr($character['birthday'], 8, 2);
    $currentYear = substr($currentGameDate, 0, 4);
    
    $birthday = $currentYear . '-' . $birthdayMonth . '-' . $birthdayDay;
    
    if ($birthday < $currentGameDate) {
        // Birthday already passed this year, next birthday is next year
        $birthday = (intval($currentYear) + 1) . '-' . $birthdayMonth . '-' . $birthdayDay;
    }
    
    $nextBirthday = date('F j, Y', strtotime($birthday));
    
    // Calculate days until birthday
    $daysUntilBirthday = ceil((strtotime($birthday) - strtotime($currentGameDate)) / (60 * 60 * 24));
}

// Get life stage
$lifeStage = calculateLifeStage($character['age'], $character['race']);
$formattedLifeStage = formatLifeStage($lifeStage);

// Get allowed activities
$allowedActivities = getAllowedActivities($activeCharacterId);

// Get age modifiers
$ageModifiers = getAgeModifiers($activeCharacterId);

// Get character aging events
$sql = "SELECT * FROM character_aging_events 
        WHERE character_id = " . intval($activeCharacterId) . " 
        ORDER BY event_date DESC LIMIT 10";
$result = $conn->query($sql);
$agingEvents = array();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $agingEvents[] = $row;
    }
}

// Get race-specific aging info
$raceAgingRate = getRaceAgingRate($character['race']);

// Page title
$pageTitle = $character['name'] . "'s Lifecycle";

// Include header
include 'inc/header.php';
?>

<div class="container my-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Character Overview</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <?php if ($character['portrait']): ?>
                            <img src="<?= htmlspecialchars($character['portrait']) ?>" alt="<?= htmlspecialchars($character['name']) ?>" class="img-fluid portrait-img rounded-circle mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                            <div class="default-portrait rounded-circle mb-2" style="width: 150px; height: 150px; margin: 0 auto; background-color: #6c757d; display: flex; justify-content: center; align-items: center;">
                                <span style="font-size: 48px; color: white;"><?= htmlspecialchars(substr($character['name'], 0, 1)) ?></span>
                            </div>
                        <?php endif; ?>
                        <h4><?= htmlspecialchars($character['name']) ?></h4>
                        <div class="text-muted mb-2">
                            Level <?= $character['level'] ?> <?= ucfirst($character['race']) ?> <?= ucfirst($character['class']) ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <strong>Age:</strong>
                            <span><?= $character['age'] ?> years</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <strong>Life Stage:</strong>
                            <span><?= $formattedLifeStage ?></span>
                        </div>
                        <?php if ($character['birthday']): ?>
                            <div class="d-flex justify-content-between mb-1">
                                <strong>Birthday:</strong>
                                <span><?= date('F j', strtotime($character['birthday'])) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <strong>Next Birthday:</strong>
                                <span><?= $nextBirthday ?> (<?= $daysUntilBirthday ?> days)</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="progress mb-3" title="Age progression within life stage">
                        <?php
                        // Calculate age progression percentage within the current life stage
                        $ageProgressPercentage = 0;
                        switch ($character['race']) {
                            case 'elf':
                                switch ($lifeStage) {
                                    case 'infant': $ageProgressPercentage = ($character['age'] / 15) * 100; break;
                                    case 'child': $ageProgressPercentage = (($character['age'] - 15) / 35) * 100; break;
                                    case 'teenager': $ageProgressPercentage = (($character['age'] - 50) / 50) * 100; break;
                                    case 'young_adult': $ageProgressPercentage = (($character['age'] - 100) / 100) * 100; break;
                                    case 'adult': $ageProgressPercentage = (($character['age'] - 200) / 200) * 100; break;
                                    case 'middle_aged': $ageProgressPercentage = (($character['age'] - 400) / 200) * 100; break;
                                    case 'elder': $ageProgressPercentage = (($character['age'] - 600) / 200) * 100; break;
                                    case 'venerable': $ageProgressPercentage = 100; break;
                                }
                                break;
                                
                            case 'dwarf':
                                switch ($lifeStage) {
                                    case 'infant': $ageProgressPercentage = ($character['age'] / 5) * 100; break;
                                    case 'child': $ageProgressPercentage = (($character['age'] - 5) / 15) * 100; break;
                                    case 'teenager': $ageProgressPercentage = (($character['age'] - 20) / 10) * 100; break;
                                    case 'young_adult': $ageProgressPercentage = (($character['age'] - 30) / 20) * 100; break;
                                    case 'adult': $ageProgressPercentage = (($character['age'] - 50) / 100) * 100; break;
                                    case 'middle_aged': $ageProgressPercentage = (($character['age'] - 150) / 100) * 100; break;
                                    case 'elder': $ageProgressPercentage = (($character['age'] - 250) / 50) * 100; break;
                                    case 'venerable': $ageProgressPercentage = 100; break;
                                }
                                break;
                                
                            case 'orc':
                                switch ($lifeStage) {
                                    case 'infant': $ageProgressPercentage = ($character['age'] / 2) * 100; break;
                                    case 'child': $ageProgressPercentage = (($character['age'] - 2) / 6) * 100; break;
                                    case 'teenager': $ageProgressPercentage = (($character['age'] - 8) / 5) * 100; break;
                                    case 'young_adult': $ageProgressPercentage = (($character['age'] - 13) / 7) * 100; break;
                                    case 'adult': $ageProgressPercentage = (($character['age'] - 20) / 15) * 100; break;
                                    case 'middle_aged': $ageProgressPercentage = (($character['age'] - 35) / 10) * 100; break;
                                    case 'elder': $ageProgressPercentage = (($character['age'] - 45) / 10) * 100; break;
                                    case 'venerable': $ageProgressPercentage = 100; break;
                                }
                                break;
                                
                            case 'human':
                            default:
                                switch ($lifeStage) {
                                    case 'infant': $ageProgressPercentage = ($character['age'] / 3) * 100; break;
                                    case 'child': $ageProgressPercentage = (($character['age'] - 3) / 9) * 100; break;
                                    case 'teenager': $ageProgressPercentage = (($character['age'] - 12) / 6) * 100; break;
                                    case 'young_adult': $ageProgressPercentage = (($character['age'] - 18) / 12) * 100; break;
                                    case 'adult': $ageProgressPercentage = (($character['age'] - 30) / 20) * 100; break;
                                    case 'middle_aged': $ageProgressPercentage = (($character['age'] - 50) / 20) * 100; break;
                                    case 'elder': $ageProgressPercentage = (($character['age'] - 70) / 20) * 100; break;
                                    case 'venerable': $ageProgressPercentage = 100; break;
                                }
                                break;
                        }
                        $ageProgressPercentage = min(100, max(0, $ageProgressPercentage));
                        ?>
                        <div class="progress-bar" role="progressbar" style="width: <?= $ageProgressPercentage ?>%;" 
                             aria-valuenow="<?= $ageProgressPercentage ?>" aria-valuemin="0" aria-valuemax="100">
                            <?= round($ageProgressPercentage) ?>%
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <small>
                            <?= ucfirst($character['race']) ?>s age at <?= $raceAgingRate ?>x the rate of humans, with significant attribute changes occurring approximately every <?= $raceAgingRate ?> years.
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Age Effects & Capabilities</h5>
                </div>
                <div class="card-body">
                    <h6>Age-Based Attribute Modifiers</h6>
                    <div class="row mb-3">
                        <?php foreach ($ageModifiers as $attribute => $modifier): ?>
                            <div class="col-md-4 mb-2">
                                <div class="d-flex justify-content-between">
                                    <span><?= ucfirst($attribute) ?>:</span>
                                    <span class="<?= $modifier > 0 ? 'text-success' : ($modifier < 0 ? 'text-danger' : '') ?>">
                                        <?= $modifier > 0 ? '+' . $modifier : $modifier ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <h6>Allowed Activities</h6>
                    <p class="text-muted mb-3"><?= $allowedActivities['activity_description'] ?></p>
                    
                    <div class="row mb-3">
                        <div class="col-md-4 mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-<?= $allowedActivities['can_work'] ? 'check text-success' : 'times text-danger' ?> me-2"></i>
                                <span>Can Work</span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-<?= $allowedActivities['can_marry'] ? 'check text-success' : 'times text-danger' ?> me-2"></i>
                                <span>Can Marry</span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-<?= $allowedActivities['can_own_property'] ? 'check text-success' : 'times text-danger' ?> me-2"></i>
                                <span>Can Own Property</span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-<?= $allowedActivities['can_have_children'] ? 'check text-success' : 'times text-danger' ?> me-2"></i>
                                <span>Can Have Children</span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-<?= $allowedActivities['can_fight'] ? 'check text-success' : 'times text-danger' ?> me-2"></i>
                                <span>Can Fight</span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-<?= $allowedActivities['can_vote'] ? 'check text-success' : 'times text-danger' ?> me-2"></i>
                                <span>Can Vote</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Recent Life Events</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($agingEvents)): ?>
                        <p class="text-muted">No recent life events recorded.</p>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($agingEvents as $index => $event): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker">
                                        <?php 
                                        switch ($event['event_type']) {
                                            case 'birthday':
                                                echo '<i class="fas fa-birthday-cake"></i>';
                                                break;
                                            case 'attribute_change':
                                                echo '<i class="fas fa-chart-line"></i>';
                                                break;
                                            case 'major_event':
                                                echo '<i class="fas fa-star"></i>';
                                                break;
                                            default:
                                                echo '<i class="fas fa-circle"></i>';
                                        }
                                        ?>
                                    </div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">
                                            <?php 
                                            switch ($event['event_type']) {
                                                case 'birthday':
                                                    echo 'Birthday';
                                                    break;
                                                case 'attribute_change':
                                                    echo 'Attribute Changes';
                                                    break;
                                                case 'major_event':
                                                    echo 'Life Stage Change';
                                                    break;
                                                default:
                                                    echo ucfirst($event['event_type']);
                                            }
                                            ?>
                                        </h6>
                                        <p class="text-muted mb-0">
                                            <small><?= date('F j, Y', strtotime($event['event_date'])) ?></small>
                                        </p>
                                        <p class="mb-1"><?= htmlspecialchars($event['description']) ?></p>
                                        
                                        <?php if ($event['stat_changes']): ?>
                                            <?php $changes = json_decode($event['stat_changes'], true); ?>
                                            <?php if (is_array($changes) && !empty($changes)): ?>
                                                <div class="stat-changes mt-1">
                                                    <?php foreach ($changes as $stat => $change): ?>
                                                        <span class="badge bg-<?= $change > 0 ? 'success' : 'danger' ?> me-1">
                                                            <?= ucfirst($stat) ?> <?= $change > 0 ? '+' . $change : $change ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding: 0;
    list-style: none;
}

.timeline-item {
    position: relative;
    margin-bottom: 15px;
    display: flex;
}

.timeline-marker {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: #f8f9fa;
    border: 2px solid #0d6efd;
    flex-shrink: 0;
    margin-right: 15px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: #0d6efd;
}

.timeline-content {
    flex-grow: 1;
    padding-bottom: 15px;
    border-bottom: 1px solid #dee2e6;
}

.timeline-item:last-child .timeline-content {
    border-bottom: none;
    padding-bottom: 0;
}

.stat-changes {
    font-size: 0.85rem;
}
</style>

<?php include 'inc/footer.php'; ?> 