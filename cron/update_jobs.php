<?php
/**
 * Cron script to update job payments and career progress
 * This script should be run daily to process salary payments and career advancements
 */

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Define BASE_PATH as we're running from CLI
define('BASE_PATH', dirname(__DIR__));
chdir(BASE_PATH);

// Load core files
require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/db.php';
require_once BASE_PATH . '/includes/career_system.php';

// Process job payments
$result = processJobPayments();
echo "Jobs processed: " . $result['jobs_processed'] . "\n";
echo "Failures: " . $result['failures'] . "\n";

// Add random job openings
$addJobsResult = addRandomJobOpenings();
if ($addJobsResult['success']) {
    echo "New job openings added: " . $addJobsResult['jobs_added'] . "\n";
} else {
    echo "Error adding new job openings: " . $addJobsResult['message'] . "\n";
}

/**
 * Add random job openings based on game events and economy
 */
function addRandomJobOpenings() {
    global $db;
    
    // Get current game time
    $result = $db->query("SELECT current_date, current_season FROM game_time LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $gameTime = $result->fetch_assoc();
        $currentSeason = $gameTime['current_season'];
        
        // Different job opportunities based on season
        $jobTypes = [];
        $maxJobs = 3; // Number of jobs to add
        
        switch ($currentSeason) {
            case 'spring':
                $jobTypes = ['farmer', 'gardener', 'hunter', 'forester'];
                break;
            case 'summer':
                $jobTypes = ['farmer', 'fisherman', 'adventurer', 'merchant'];
                break;
            case 'autumn':
                $jobTypes = ['harvester', 'hunter', 'craftsman', 'merchant'];
                break;
            case 'winter':
                $jobTypes = ['miner', 'craftsman', 'scholar', 'guard'];
                break;
            default:
                $jobTypes = ['guard', 'craftsman', 'merchant', 'laborer'];
        }
        
        // Get a random selection of inactive jobs matching the season
        $placeholders = implode("','", $jobTypes);
        $sql = "SELECT id FROM jobs 
                WHERE category IN ('{$placeholders}') 
                AND is_active = 0 
                ORDER BY RAND() 
                LIMIT ?";
        
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $maxJobs);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $jobsAdded = 0;
        
        while ($job = $result->fetch_assoc()) {
            // Activate the job
            $sql = "UPDATE jobs SET is_active = 1 WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("i", $job['id']);
            
            if ($stmt->execute()) {
                $jobsAdded++;
            }
        }
        
        return [
            'success' => true,
            'jobs_added' => $jobsAdded
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Could not retrieve game time'
    ];
} 