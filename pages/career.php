<?php
/**
 * Career Management Page
 * Displays job opportunities, current jobs, and career advancement
 */

// Include necessary files
require_once 'includes/career_system.php';

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

// Process actions
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        
        // Apply for job
        if ($_POST['action'] == 'apply_job' && isset($_POST['job_id'])) {
            $jobId = (int)$_POST['job_id'];
            $result = applyForJob($activeCharacterId, $jobId);
            
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
        
        // Quit job
        else if ($_POST['action'] == 'quit_job' && isset($_POST['character_job_id'])) {
            $characterJobId = (int)$_POST['character_job_id'];
            $result = quitJob($activeCharacterId, $characterJobId);
            
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
        
        // Perform work
        else if ($_POST['action'] == 'work' && isset($_POST['character_job_id']) && isset($_POST['hours'])) {
            $characterJobId = (int)$_POST['character_job_id'];
            $hours = (int)$_POST['hours'];
            $intensity = $_POST['intensity'] ?? 'normal';
            
            $result = performWork($activeCharacterId, $characterJobId, $hours, $intensity);
            
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Get character's current jobs
$currentJobs = getCharacterJobs($activeCharacterId, true);

// Get job history
$jobHistory = getCharacterJobs($activeCharacterId, false);

// Get available jobs
$availableJobs = getAvailableJobs($activeCharacterId);

// Get specialized skills
$specializedSkills = getSpecializedSkills($activeCharacterId);

// Page title
$pageTitle = "Career Management";

// Include header
include 'inc/header.php';
?>

<div class="container my-4">
    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-briefcase me-2"></i> <?= $character['name'] ?>'s Career
                    </h5>
                </div>
                <div class="card-body">
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <!-- Career Navigation Tabs -->
                    <ul class="nav nav-tabs" id="careerTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="current-jobs-tab" data-bs-toggle="tab" data-bs-target="#current-jobs" type="button" role="tab">
                                <i class="fas fa-briefcase me-1"></i> Current Jobs
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="job-market-tab" data-bs-toggle="tab" data-bs-target="#job-market" type="button" role="tab">
                                <i class="fas fa-search me-1"></i> Job Market
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="skills-tab" data-bs-toggle="tab" data-bs-target="#skills" type="button" role="tab">
                                <i class="fas fa-tools me-1"></i> Professional Skills
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
                                <i class="fas fa-history me-1"></i> Work History
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content p-3 border border-top-0 rounded-bottom" id="careerTabContent">
                        <!-- Current Jobs Tab -->
                        <div class="tab-pane fade show active" id="current-jobs" role="tabpanel">
                            <h5>Your Current Occupations</h5>
                            
                            <?php if (empty($currentJobs)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> You are currently unemployed. Visit the Job Market tab to find work!
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($currentJobs as $job): ?>
                                        <?php 
                                            $performance = getJobPerformance($activeCharacterId, $job['id']);
                                            $ratingClass = '';
                                            if ($performance['overall_score'] >= 75) $ratingClass = 'text-success';
                                            else if ($performance['overall_score'] >= 50) $ratingClass = 'text-primary';
                                            else if ($performance['overall_score'] >= 25) $ratingClass = 'text-warning';
                                            else $ratingClass = 'text-danger';
                                        ?>
                                        <div class="col-md-6 mb-4">
                                            <div class="card h-100">
                                                <div class="card-header">
                                                    <h5 class="mb-0"><?= $job['name'] ?></h5>
                                                    <span class="badge bg-secondary">Level <?= $job['job_level'] ?></span>
                                                </div>
                                                <div class="card-body">
                                                    <p><?= $job['description'] ?></p>
                                                    
                                                    <div class="mb-3">
                                                        <h6>Performance Rating</h6>
                                                        <div class="progress mb-2">
                                                            <div class="progress-bar bg-<?= ($performance['overall_score'] >= 75) ? 'success' : (($performance['overall_score'] >= 50) ? 'primary' : (($performance['overall_score'] >= 25) ? 'warning' : 'danger')) ?>" 
                                                                 role="progressbar" 
                                                                 style="width: <?= $performance['overall_score'] ?>%" 
                                                                 aria-valuenow="<?= $performance['overall_score'] ?>" 
                                                                 aria-valuemin="0" 
                                                                 aria-valuemax="100">
                                                                <?= $performance['overall_score'] ?>%
                                                            </div>
                                                        </div>
                                                        <p class="small">Rating: <span class="<?= $ratingClass ?>"><?= $performance['rating'] ?></span></p>
                                                    </div>
                                                    
                                                    <div class="row mb-3">
                                                        <div class="col-6">
                                                            <p class="small mb-0"><strong>Experience:</strong> <?= $job['experience'] ?></p>
                                                        </div>
                                                        <div class="col-6">
                                                            <p class="small mb-0"><strong>Days Employed:</strong> <?= $performance['days_employed'] ?></p>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                                        <div>
                                                            <p class="small mb-0"><strong>Base Salary:</strong> <?= $job['base_salary'] ?> gold</p>
                                                            <p class="small mb-0"><strong>Hours:</strong> <?= $job['work_hours'] ?>/day</p>
                                                        </div>
                                                        
                                                        <?php if ($performance['promotion_eligible']): ?>
                                                            <span class="badge bg-success">Eligible for Promotion</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="card-footer">
                                                    <div class="d-flex justify-content-between">
                                                        <!-- Work Form -->
                                                        <div class="dropdown">
                                                            <button class="btn btn-success dropdown-toggle" type="button" id="workDropdown<?= $job['id'] ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="fas fa-briefcase me-1"></i> Work
                                                            </button>
                                                            <div class="dropdown-menu p-3" style="width: 300px;">
                                                                <form method="post">
                                                                    <input type="hidden" name="action" value="work">
                                                                    <input type="hidden" name="character_job_id" value="<?= $job['id'] ?>">
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="hours" class="form-label">Hours to Work</label>
                                                                        <input type="number" class="form-control" id="hours" name="hours" min="1" max="12" value="<?= $job['work_hours'] ?>">
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="intensity" class="form-label">Work Intensity</label>
                                                                        <select class="form-select" id="intensity" name="intensity">
                                                                            <option value="relaxed">Relaxed (Less fatigue, less pay)</option>
                                                                            <option value="normal" selected>Normal</option>
                                                                            <option value="intense">Intense (More fatigue, more pay)</option>
                                                                            <option value="overtime">Overtime (High fatigue, high pay)</option>
                                                                        </select>
                                                                    </div>
                                                                    
                                                                    <button type="submit" class="btn btn-primary">Start Working</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Quit Job Form -->
                                                        <form method="post" onsubmit="return confirm('Are you sure you want to quit this job?');">
                                                            <input type="hidden" name="action" value="quit_job">
                                                            <input type="hidden" name="character_job_id" value="<?= $job['id'] ?>">
                                                            <button type="submit" class="btn btn-danger">
                                                                <i class="fas fa-sign-out-alt me-1"></i> Quit
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Job Market Tab -->
                        <div class="tab-pane fade" id="job-market" role="tabpanel">
                            <h5>Available Jobs</h5>
                            
                            <?php if (empty($availableJobs)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> There are no available jobs matching your qualifications at this time.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Job Title</th>
                                                <th>Category</th>
                                                <th>Salary</th>
                                                <th>Requirements</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($availableJobs as $job): ?>
                                                <?php
                                                    // Check if character already has this job
                                                    $hasJob = false;
                                                    foreach ($currentJobs as $current) {
                                                        if ($current['job_id'] == $job['id']) {
                                                            $hasJob = true;
                                                            break;
                                                        }
                                                    }
                                                ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= $job['name'] ?></strong>
                                                        <div class="small text-muted"><?= mb_substr($job['description'], 0, 50) ?>...</div>
                                                    </td>
                                                    <td><span class="badge bg-info"><?= ucfirst($job['category']) ?></span></td>
                                                    <td><?= $job['base_salary'] ?> gold</td>
                                                    <td>
                                                        <div class="small">
                                                            <div>Level: <?= $job['level_req'] ?></div>
                                                            <?php if ($job['skill_req']): ?>
                                                                <div>Skill: <?= $job['skill_req'] ?> (Level <?= $job['skill_level_req'] ?>)</div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if ($hasJob): ?>
                                                            <button class="btn btn-sm btn-secondary" disabled>Already Employed</button>
                                                        <?php else: ?>
                                                            <form method="post">
                                                                <input type="hidden" name="action" value="apply_job">
                                                                <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-success">Apply</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Professional Skills Tab -->
                        <div class="tab-pane fade" id="skills" role="tabpanel">
                            <h5>Professional Skills</h5>
                            
                            <?php if (empty($specializedSkills)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> You haven't developed any professional skills yet. Skills improve as you work at related jobs.
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($specializedSkills as $category => $skills): ?>
                                        <div class="col-md-6 mb-4">
                                            <div class="card h-100">
                                                <div class="card-header">
                                                    <h5 class="mb-0"><?= ucfirst($category) ?> Skills</h5>
                                                </div>
                                                <div class="card-body">
                                                    <div class="list-group">
                                                        <?php foreach ($skills as $skill): ?>
                                                            <div class="list-group-item">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <div>
                                                                        <h6 class="mb-0"><?= $skill['name'] ?></h6>
                                                                        <p class="small mb-0"><?= $skill['description'] ?></p>
                                                                    </div>
                                                                    <span class="badge bg-primary">Level <?= $skill['level'] ?></span>
                                                                </div>
                                                                <div class="progress mt-2" style="height: 5px;">
                                                                    <?php 
                                                                        $expNeeded = 100 * pow(2, $skill['level'] - 1);
                                                                        $percentage = min(100, ($skill['experience'] / $expNeeded) * 100);
                                                                    ?>
                                                                    <div class="progress-bar" role="progressbar" style="width: <?= $percentage ?>%"></div>
                                                                </div>
                                                                <div class="small text-end mt-1">
                                                                    <?= $skill['experience'] ?>/<?= $expNeeded ?> XP
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="alert alert-secondary mt-3">
                                <h6><i class="fas fa-info-circle me-2"></i> How Skills Work</h6>
                                <p class="small mb-0">Professional skills improve as you work at related jobs. Higher skill levels lead to better job performance, higher pay, and access to advanced positions. Different job categories require different skill sets.</p>
                            </div>
                        </div>
                        
                        <!-- Work History Tab -->
                        <div class="tab-pane fade" id="history" role="tabpanel">
                            <h5>Employment History</h5>
                            
                            <?php if (empty($jobHistory)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> You haven't had any jobs yet.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Job Title</th>
                                                <th>Level Reached</th>
                                                <th>Hired On</th>
                                                <th>Status</th>
                                                <th>Experience Gained</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($jobHistory as $job): ?>
                                                <tr>
                                                    <td><?= $job['name'] ?></td>
                                                    <td><?= $job['job_level'] ?></td>
                                                    <td><?= date('M d, Y', strtotime($job['hire_date'])) ?></td>
                                                    <td>
                                                        <?php if ($job['is_current']): ?>
                                                            <span class="badge bg-success">Current</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Former</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= $job['experience'] ?> XP</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?> 