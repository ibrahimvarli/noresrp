<?php
/**
 * Career System Functions
 * This file contains functions for managing character careers:
 * - Job application and hiring processes
 * - Work schedules and overtime
 * - Performance evaluation and promotions
 * - Skills and specializations
 */

/**
 * Get all available jobs for a character based on level and location
 */
function getAvailableJobs($characterId) {
    global $db;
    
    // Get character info
    $sql = "SELECT level, location_id FROM characters WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return [];
    }
    
    $character = $result->fetch_assoc();
    
    // Get jobs available at character's level and location
    $sql = "SELECT j.* FROM jobs j
            WHERE j.level_req <= ? 
            AND (j.location_id = ? OR j.location_id IS NULL)
            AND j.is_active = 1
            ORDER BY j.level_req ASC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $character['level'], $character['location_id']);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get current or historical jobs for a character
 */
function getCharacterJobs($characterId, $currentOnly = true) {
    global $db;
    
    $sql = "SELECT cj.*, j.name, j.description, j.category, j.base_salary, j.work_hours, j.exp_gain, j.max_level 
            FROM character_jobs cj
            JOIN jobs j ON cj.job_id = j.id
            WHERE cj.character_id = ?";
    
    if ($currentOnly) {
        $sql .= " AND cj.is_current = 1";
    }
    
    $sql .= " ORDER BY cj.hire_date DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Apply for a job
 */
function applyForJob($characterId, $jobId) {
    global $db;
    
    // Check if job exists and is available
    $sql = "SELECT j.*, b.name as business_name
            FROM jobs j
            LEFT JOIN businesses b ON j.business_id = b.id
            WHERE j.id = ? AND j.is_active = 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $jobId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return [
            'success' => false,
            'message' => 'This job is not available.'
        ];
    }
    
    $job = $result->fetch_assoc();
    
    // Check if character meets requirements
    $sql = "SELECT level, location_id FROM characters WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    $character = $stmt->get_result()->fetch_assoc();
    
    if ($character['level'] < $job['level_req']) {
        return [
            'success' => false,
            'message' => 'You do not meet the level requirement for this job.'
        ];
    }
    
    // Check required skill if any
    if (!empty($job['skill_req'])) {
        $sql = "SELECT level FROM character_skills 
                WHERE character_id = ? AND skill_id = (SELECT id FROM skills WHERE name = ?)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("is", $characterId, $job['skill_req']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0 || $result->fetch_assoc()['level'] < $job['skill_level_req']) {
            return [
                'success' => false,
                'message' => 'You do not have the required skill level for this job.'
            ];
        }
    }
    
    // Check for existing jobs
    $sql = "SELECT COUNT(*) as job_count FROM character_jobs WHERE character_id = ? AND is_current = 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    $jobCount = $stmt->get_result()->fetch_assoc()['job_count'];
    
    // Check if character already has the maximum allowed jobs (3)
    if ($jobCount >= 3) {
        return [
            'success' => false,
            'message' => 'You can only have up to 3 jobs at a time. Quit one of your current jobs first.'
        ];
    }
    
    // Check if character already has this job
    $sql = "SELECT id FROM character_jobs WHERE character_id = ? AND job_id = ? AND is_current = 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $characterId, $jobId);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        return [
            'success' => false,
            'message' => 'You already have this job.'
        ];
    }
    
    // Calculate next payment date (one game week from now)
    $sql = "SELECT DATE_ADD(current_date, INTERVAL 7 DAY) as next_payment FROM game_time LIMIT 1";
    $result = $db->query($sql);
    $nextPayment = $result->fetch_assoc()['next_payment'];
    
    // Add job to character
    $sql = "INSERT INTO character_jobs 
            (character_id, job_id, job_level, experience, hire_date, last_payment, next_payment, is_current) 
            VALUES (?, ?, 1, 0, NOW(), NOW(), ?, 1)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("iis", $characterId, $jobId, $nextPayment);
    
    if ($stmt->execute()) {
        $businessName = !empty($job['business_name']) ? " at " . $job['business_name'] : "";
        return [
            'success' => true,
            'message' => "Congratulations! You've been hired as a " . $job['name'] . $businessName . "."
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Error applying for job: ' . $db->error
        ];
    }
}

/**
 * Quit a job
 */
function quitJob($characterId, $characterJobId) {
    global $db;
    
    // Verify the job belongs to the character
    $sql = "SELECT cj.*, j.name FROM character_jobs cj
            JOIN jobs j ON cj.job_id = j.id
            WHERE cj.id = ? AND cj.character_id = ? AND cj.is_current = 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $characterJobId, $characterId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return [
            'success' => false,
            'message' => 'Job not found.'
        ];
    }
    
    $job = $result->fetch_assoc();
    
    // Update the job as not current
    $sql = "UPDATE character_jobs SET is_current = 0 WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterJobId);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => "You have quit your job as a " . $job['name'] . "."
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Error quitting job: ' . $db->error
        ];
    }
}

/**
 * Perform work shift at job
 */
function performWork($characterId, $characterJobId, $hoursWorked, $workIntensity = 'normal') {
    global $db;
    
    // Check if this is a valid current job
    $sql = "SELECT cj.*, j.name, j.base_salary, j.work_hours, j.exp_gain 
            FROM character_jobs cj
            JOIN jobs j ON cj.job_id = j.id
            WHERE cj.id = ? AND cj.character_id = ? AND cj.is_current = 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $characterJobId, $characterId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return [
            'success' => false,
            'message' => 'Invalid job selected.'
        ];
    }
    
    $job = $result->fetch_assoc();
    
    // Validate work hours
    $standardHours = $job['work_hours'];
    $overtimeHours = max(0, $hoursWorked - $standardHours);
    $regularHours = min($hoursWorked, $standardHours);
    
    // Check character's fatigue level
    $sql = "SELECT fatigue, max_fatigue FROM character_life_stats WHERE character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $characterId);
    $stmt->execute();
    $lifeStats = $stmt->get_result()->fetch_assoc();
    
    if ($lifeStats['fatigue'] >= 70) {
        return [
            'success' => false,
            'message' => 'You are too tired to work effectively. Get some rest first.'
        ];
    }
    
    // Calculate base pay
    $hourlyRate = $job['base_salary'] / $standardHours;
    $regularPay = $hourlyRate * $regularHours;
    $overtimePay = $hourlyRate * $overtimeHours * 1.5; // 50% bonus for overtime
    $totalPay = $regularPay + $overtimePay;
    
    // Apply job level bonus (5% per level)
    $levelBonus = 1 + (($job['job_level'] - 1) * 0.05);
    $totalPay = round($totalPay * $levelBonus);
    
    // Calculate experience gain
    $baseExpGain = $job['exp_gain'] * $hoursWorked;
    
    // Adjust based on work intensity
    $intensityMultiplier = 1.0;
    $fatigueIncrease = $hoursWorked;
    
    switch ($workIntensity) {
        case 'relaxed':
            $intensityMultiplier = 0.7;
            $fatigueIncrease = $hoursWorked * 0.7;
            break;
        case 'normal':
            $intensityMultiplier = 1.0;
            $fatigueIncrease = $hoursWorked;
            break;
        case 'intense':
            $intensityMultiplier = 1.3;
            $fatigueIncrease = $hoursWorked * 1.5;
            break;
        case 'overtime':
            $intensityMultiplier = 1.5;
            $fatigueIncrease = $hoursWorked * 2;
            break;
    }
    
    $totalExpGain = round($baseExpGain * $intensityMultiplier);
    $totalPay = round($totalPay * $intensityMultiplier);
    
    // Update job stats
    $newExp = $job['experience'] + $totalExpGain;
    $newTimesWorked = $job['times_worked'] + 1;
    
    // Check for level up
    $levelUp = false;
    $newLevel = $job['job_level'];
    $expForNextLevel = 100 * pow(2, $newLevel - 1); // Each level requires twice as much exp
    
    if ($newExp >= $expForNextLevel && $newLevel < $job['max_level']) {
        $newLevel++;
        $levelUp = true;
    }
    
    // Begin transaction for all updates
    $db->begin_transaction();
    
    try {
        // Update job record
        $sql = "UPDATE character_jobs 
                SET experience = ?, times_worked = ?, job_level = ?, last_payment = NOW() 
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("iiii", $newExp, $newTimesWorked, $newLevel, $characterJobId);
        $stmt->execute();
        
        // Update character's fatigue
        updateFatigue($characterId, $fatigueIncrease, $workIntensity);
        
        // Update character's money
        $sql = "UPDATE characters SET gold = gold + ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ii", $totalPay, $characterId);
        $stmt->execute();
        
        // Add financial transaction
        $sql = "INSERT INTO economy_transactions 
                (character_id, amount, balance_after, type, category, description, related_entity_type, related_entity_id) 
                SELECT ?, ?, gold, 'income', 'salary', ?, 'job', ? FROM characters WHERE id = ?";
        $stmt = $db->prepare($sql);
        $description = "Salary for working as " . $job['name'];
        $stmt->bind_param("iisii", $characterId, $totalPay, $description, $job['job_id'], $characterId);
        $stmt->execute();
        
        // If leveled up, check for milestone
        if ($levelUp) {
            $sql = "SELECT * FROM career_milestones WHERE job_id = ? AND job_level = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ii", $job['job_id'], $newLevel);
            $stmt->execute();
            $milestone = $stmt->get_result()->fetch_assoc();
            
            if ($milestone) {
                // Grant milestone rewards
                if ($milestone['reward_gold'] > 0) {
                    $sql = "UPDATE characters SET gold = gold + ? WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->bind_param("ii", $milestone['reward_gold'], $characterId);
                    $stmt->execute();
                    
                    // Add transaction record
                    $sql = "INSERT INTO economy_transactions 
                            (character_id, amount, balance_after, type, category, description, related_entity_type, related_entity_id) 
                            SELECT ?, ?, gold, 'income', 'bonus', ?, 'career_milestone', ? FROM characters WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $desc = "Career milestone bonus: " . $milestone['title'];
                    $stmt->bind_param("iisii", $characterId, $milestone['reward_gold'], $desc, $milestone['id'], $characterId);
                    $stmt->execute();
                }
                
                // Character exp reward
                if ($milestone['reward_exp'] > 0) {
                    $sql = "UPDATE characters SET experience = experience + ? WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->bind_param("ii", $milestone['reward_exp'], $characterId);
                    $stmt->execute();
                }
                
                // Item reward
                if ($milestone['reward_item_id']) {
                    $sql = "INSERT INTO inventory (character_id, item_id, quantity) 
                            VALUES (?, ?, 1)
                            ON DUPLICATE KEY UPDATE quantity = quantity + 1";
                    $stmt = $db->prepare($sql);
                    $stmt->bind_param("ii", $characterId, $milestone['reward_item_id']);
                    $stmt->execute();
                }
                
                // Unlock new job if applicable
                if ($milestone['unlock_job_id']) {
                    // Could trigger a notification or automatically give access
                }
            }
        }
        
        $db->commit();
        
        $result = [
            'success' => true,
            'message' => "You worked for $hoursWorked hours and earned $totalPay gold.",
            'earned' => $totalPay,
            'exp_gained' => $totalExpGain
        ];
        
        if ($levelUp) {
            $result['level_up'] = true;
            $result['new_level'] = $newLevel;
            $result['message'] .= " You've been promoted to job level $newLevel!";
            
            if (isset($milestone)) {
                $result['milestone'] = $milestone['title'];
                $result['milestone_description'] = $milestone['description'];
                $result['message'] .= " Achievement: " . $milestone['title'];
            }
        }
        
        return $result;
    } catch (Exception $e) {
        $db->rollback();
        return [
            'success' => false,
            'message' => 'Error while working: ' . $e->getMessage()
        ];
    }
}

/**
 * Get job performance rating for a character
 */
function getJobPerformance($characterId, $characterJobId) {
    global $db;
    
    $sql = "SELECT cj.*, j.name, j.category 
            FROM character_jobs cj
            JOIN jobs j ON cj.job_id = j.id
            WHERE cj.id = ? AND cj.character_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $characterJobId, $characterId);
    $stmt->execute();
    $job = $stmt->get_result()->fetch_assoc();
    
    if (!$job) {
        return [
            'success' => false,
            'message' => 'Job not found.'
        ];
    }
    
    // Calculate performance metrics
    $totalDays = max(1, ceil((strtotime('now') - strtotime($job['hire_date'])) / (60*60*24)));
    $daysPerWorkCycle = 7; // Assuming weekly work cycle
    $workCycles = ceil($totalDays / $daysPerWorkCycle);
    $expectedWorkTimes = $workCycles;
    $performanceRatio = $job['times_worked'] / $expectedWorkTimes;
    
    // Get character skills related to this job
    $relevantSkills = getRelevantJobSkills($characterId, $job['job_id']);
    
    // Calculate overall performance score (0-100)
    $baseScore = 50;
    $attendanceScore = min(100, $performanceRatio * 100);
    $skillScore = !empty($relevantSkills) ? $relevantSkills['avg_skill_level'] * 10 : 0;
    
    $overallScore = min(100, $baseScore * 0.3 + $attendanceScore * 0.4 + $skillScore * 0.3);
    
    // Determine rating text
    $ratingText = '';
    if ($overallScore >= 90) {
        $ratingText = 'Exceptional';
    } elseif ($overallScore >= 75) {
        $ratingText = 'Above Average';
    } elseif ($overallScore >= 50) {
        $ratingText = 'Average';
    } elseif ($overallScore >= 25) {
        $ratingText = 'Below Average';
    } else {
        $ratingText = 'Poor';
    }
    
    // Determine promotion eligibility
    $eligibleForPromotion = ($overallScore >= 80 && $job['job_level'] < $job['max_level']);
    
    return [
        'success' => true,
        'job_name' => $job['name'],
        'job_level' => $job['job_level'],
        'days_employed' => $totalDays,
        'times_worked' => $job['times_worked'],
        'expected_work_times' => $expectedWorkTimes,
        'attendance_ratio' => $performanceRatio,
        'overall_score' => $overallScore,
        'rating' => $ratingText,
        'promotion_eligible' => $eligibleForPromotion,
        'relevant_skills' => $relevantSkills
    ];
}

/**
 * Get skills relevant to a job
 */
function getRelevantJobSkills($characterId, $jobId) {
    global $db;
    
    // Get job category
    $sql = "SELECT category FROM jobs WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $jobId);
    $stmt->execute();
    $job = $stmt->get_result()->fetch_assoc();
    
    if (!$job) {
        return null;
    }
    
    // Define skills relevant to each job category
    $relevantSkillTypes = [];
    
    switch ($job['category']) {
        case 'craftsman':
            $relevantSkillTypes = ['crafting', 'blacksmithing', 'carpentry', 'alchemy'];
            break;
        case 'merchant':
            $relevantSkillTypes = ['bargaining', 'persuasion', 'appraisal'];
            break;
        case 'adventurer':
            $relevantSkillTypes = ['survival', 'combat', 'navigation', 'tracking'];
            break;
        case 'scholar':
            $relevantSkillTypes = ['research', 'history', 'magic', 'languages'];
            break;
        case 'nobility':
            $relevantSkillTypes = ['leadership', 'etiquette', 'politics'];
            break;
        case 'military':
            $relevantSkillTypes = ['combat', 'tactics', 'leadership'];
            break;
        case 'criminal':
            $relevantSkillTypes = ['stealth', 'lockpicking', 'deception'];
            break;
    }
    
    if (empty($relevantSkillTypes)) {
        return null;
    }
    
    // Get character's relevant skills
    $placeholders = implode(',', array_fill(0, count($relevantSkillTypes), '?'));
    $sql = "SELECT cs.*, s.name, s.description 
            FROM character_skills cs
            JOIN skills s ON cs.skill_id = s.id
            WHERE cs.character_id = ? AND s.skill_type IN ($placeholders)";
    
    $types = "i" . str_repeat("s", count($relevantSkillTypes));
    $params = array_merge([$characterId], $relevantSkillTypes);
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $skills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Calculate average skill level
    $totalLevel = 0;
    foreach ($skills as $skill) {
        $totalLevel += $skill['level'];
    }
    
    $avgLevel = empty($skills) ? 0 : $totalLevel / count($skills);
    
    return [
        'skills' => $skills,
        'avg_skill_level' => $avgLevel,
        'total_skills' => count($skills)
    ];
}

/**
 * Process job performance updates and payments across all characters
 * Called periodically by cron job
 */
function processJobPayments() {
    global $db;
    
    // Get current game date
    $sql = "SELECT current_date FROM game_time LIMIT 1";
    $result = $db->query($sql);
    $currentDate = $result->fetch_assoc()['current_date'];
    
    // Get all active jobs due for payment
    $sql = "SELECT cj.*, c.gold as character_gold, j.name as job_name, j.base_salary
            FROM character_jobs cj
            JOIN characters c ON cj.character_id = c.id
            JOIN jobs j ON cj.job_id = j.id
            WHERE cj.is_current = 1 
            AND cj.next_payment <= ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $currentDate);
    $stmt->execute();
    $jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $processed = 0;
    $failures = 0;
    
    foreach ($jobs as $job) {
        // Calculate base pay (modified by job level)
        $levelBonus = 1 + (($job['job_level'] - 1) * 0.05);
        $payment = round($job['base_salary'] * $levelBonus);
        
        // Set next payment date (7 days from now)
        $nextPayment = date('Y-m-d', strtotime($currentDate . ' + 7 days'));
        
        // Update job record
        $sql = "UPDATE character_jobs 
                SET last_payment = ?, next_payment = ? 
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ssi", $currentDate, $nextPayment, $job['id']);
        
        if (!$stmt->execute()) {
            $failures++;
            continue;
        }
        
        // Update character's money
        $sql = "UPDATE characters SET gold = gold + ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ii", $payment, $job['character_id']);
        
        if (!$stmt->execute()) {
            $failures++;
            continue;
        }
        
        // Add financial transaction record
        $newBalance = $job['character_gold'] + $payment;
        $sql = "INSERT INTO economy_transactions 
                (character_id, amount, balance_after, type, category, description, related_entity_type, related_entity_id) 
                VALUES (?, ?, ?, 'income', 'salary', ?, 'job', ?)";
        $stmt = $db->prepare($sql);
        $description = "Periodic salary payment as " . $job['job_name'];
        $stmt->bind_param("iiisi", $job['character_id'], $payment, $newBalance, $description, $job['job_id']);
        
        if ($stmt->execute()) {
            $processed++;
        } else {
            $failures++;
        }
    }
    
    return [
        'success' => true,
        'jobs_processed' => $processed,
        'failures' => $failures
    ];
}

/**
 * Get specialized skills for a character by job category
 */
function getSpecializedSkills($characterId, $category = null) {
    global $db;
    
    $sql = "SELECT cs.*, s.name, s.description, s.skill_type 
            FROM character_skills cs
            JOIN skills s ON cs.skill_id = s.id
            WHERE cs.character_id = ?";
            
    if ($category) {
        $sql .= " AND s.skill_type = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("is", $characterId, $category);
    } else {
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $characterId);
    }
    
    $stmt->execute();
    $skills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Group by skill type
    $groupedSkills = [];
    foreach ($skills as $skill) {
        if (!isset($groupedSkills[$skill['skill_type']])) {
            $groupedSkills[$skill['skill_type']] = [];
        }
        $groupedSkills[$skill['skill_type']][] = $skill;
    }
    
    return $groupedSkills;
}

/**
 * Improve a job-related skill through work
 */
function improveWorkSkill($characterId, $skillId, $experiencePoints) {
    global $db;
    
    // Check if character has this skill
    $sql = "SELECT cs.* FROM character_skills cs
            JOIN skills s ON cs.skill_id = s.id
            WHERE cs.character_id = ? AND cs.skill_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $characterId, $skillId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Character doesn't have this skill yet, add it
        $sql = "INSERT INTO character_skills (character_id, skill_id, level, experience) 
                VALUES (?, ?, 1, ?)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("iii", $characterId, $skillId, $experiencePoints);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'You learned a new skill!',
                'level' => 1,
                'is_new' => true
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to learn new skill.'
            ];
        }
    } else {
        // Update existing skill
        $skill = $result->fetch_assoc();
        $newExp = $skill['experience'] + $experiencePoints;
        $newLevel = $skill['level'];
        $leveledUp = false;
        
        // Check if skill should level up (100 exp per level, doubled each level)
        $expNeeded = 100 * pow(2, $skill['level'] - 1);
        
        if ($newExp >= $expNeeded) {
            $newLevel++;
            $leveledUp = true;
        }
        
        // Update skill
        $sql = "UPDATE character_skills SET experience = ?, level = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("iii", $newExp, $newLevel, $skill['id']);
        
        if ($stmt->execute()) {
            $result = [
                'success' => true,
                'message' => 'Skill improved',
                'experience_gained' => $experiencePoints,
                'new_experience' => $newExp,
                'level' => $newLevel
            ];
            
            if ($leveledUp) {
                $result['level_up'] = true;
                $result['message'] = 'Skill improved to level ' . $newLevel . '!';
            }
            
            return $result;
        } else {
            return [
                'success' => false,
                'message' => 'Failed to improve skill.'
            ];
        }
    }
} 