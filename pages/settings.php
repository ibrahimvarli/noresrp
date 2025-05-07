<?php
// Redirect if not logged in
if (!isLoggedIn()) {
    redirect("index.php?page=login");
}

// Get user data
$userId = (int)$_SESSION['user_id'];
$user = getCurrentUser();

// Process form submission
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_settings'])) {
        // Get form inputs
        $theme = sanitize($_POST['theme']);
        $language = sanitize($_POST['language']);
        
        // Validate input
        if (empty($theme) || empty($language)) {
            $error = __('all_fields_required');
        } else {
            global $db;
            
            // Update user settings
            $sql = "UPDATE users SET theme = ?, language = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ssi", $theme, $language, $userId);
            
            if ($stmt->execute()) {
                // Update session variables
                $_SESSION['theme'] = $theme;
                $_SESSION['language'] = $language;
                
                // Set success message
                $success = __('settings_saved');
                
                // Refresh user data
                $user = getCurrentUser();
            } else {
                $error = "Failed to update settings. Please try again.";
            }
        }
    }
}
?>

<div class="settings-container">
    <div class="page-header">
        <h1><?php echo __('settings'); ?></h1>
        <p><?php echo __('customize_your_experience'); ?></p>
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
    
    <div class="settings-wrapper">
        <form class="settings-form" method="POST" action="">
            <input type="hidden" name="update_settings" value="1">
            
            <div class="setting-group">
                <h3><i class="fas fa-palette"></i> <?php echo __('theme'); ?></h3>
                <div class="theme-selector">
                    <label class="theme-option">
                        <input type="radio" name="theme" value="dark" <?php echo $user['theme'] === 'dark' ? 'checked' : ''; ?>>
                        <div class="theme-preview dark">
                            <div class="theme-label"><?php echo __('dark_theme'); ?></div>
                            <div class="theme-sample">
                                <div class="sample-header"></div>
                                <div class="sample-content"></div>
                            </div>
                        </div>
                    </label>
                    
                    <label class="theme-option">
                        <input type="radio" name="theme" value="light" <?php echo $user['theme'] === 'light' ? 'checked' : ''; ?>>
                        <div class="theme-preview light">
                            <div class="theme-label"><?php echo __('light_theme'); ?></div>
                            <div class="theme-sample">
                                <div class="sample-header"></div>
                                <div class="sample-content"></div>
                            </div>
                        </div>
                    </label>
                </div>
            </div>
            
            <div class="setting-group">
                <h3><i class="fas fa-globe"></i> <?php echo __('language'); ?></h3>
                <div class="language-selector">
                    <?php 
                    $availableLanguages = unserialize(AVAILABLE_LANGUAGES);
                    foreach ($availableLanguages as $code => $name):
                    ?>
                        <label class="language-option">
                            <input type="radio" name="language" value="<?php echo $code; ?>" <?php echo $user['language'] === $code ? 'checked' : ''; ?>>
                            <div class="language-preview">
                                <div class="language-flag"><?php echo $code === 'en' ? '🇬🇧' : '🇹🇷'; ?></div>
                                <div class="language-name"><?php echo $name; ?></div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo __('save'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.settings-container {
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

.settings-wrapper {
    background-color: var(--bg-secondary);
    border-radius: var(--border-radius);
    padding: 3rem;
    box-shadow: 0 5px 15px var(--shadow);
}

.setting-group {
    margin-bottom: 4rem;
}

.setting-group h3 {
    font-size: 2rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.theme-selector, .language-selector {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}

.theme-option, .language-option {
    flex: 1;
    min-width: 200px;
    cursor: pointer;
}

.theme-option input, .language-option input {
    display: none;
}

.theme-preview {
    border-radius: var(--border-radius);
    overflow: hidden;
    border: 2px solid var(--border);
    transition: all var(--transition-time);
}

.theme-option input:checked + .theme-preview {
    border-color: var(--accent-primary);
    box-shadow: 0 0 0 2px var(--accent-primary);
}

.theme-label {
    padding: 1rem;
    text-align: center;
    font-weight: 600;
}

.theme-preview.dark {
    background-color: var(--dark-bg-primary);
    color: var(--dark-text-primary);
}

.theme-preview.dark .theme-label {
    background-color: var(--dark-bg-secondary);
}

.theme-preview.dark .sample-header {
    background-color: var(--dark-bg-secondary);
}

.theme-preview.dark .sample-content {
    background-color: var(--dark-bg-tertiary);
}

.theme-preview.light {
    background-color: var(--light-bg-primary);
    color: var(--light-text-primary);
}

.theme-preview.light .theme-label {
    background-color: var(--light-bg-secondary);
}

.theme-preview.light .sample-header {
    background-color: var(--light-bg-secondary);
}

.theme-preview.light .sample-content {
    background-color: var(--light-bg-tertiary);
}

.theme-sample {
    height: 150px;
}

.sample-header {
    height: 30px;
}

.sample-content {
    height: 120px;
    display: flex;
    flex-direction: column;
    padding: 1rem;
}

.sample-content::before, .sample-content::after {
    content: '';
    height: 10px;
    margin-bottom: 0.5rem;
    border-radius: 5px;
    background-color: rgba(255, 255, 255, 0.1);
}

.sample-content::after {
    width: 70%;
}

.language-preview {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    border-radius: var(--border-radius);
    border: 2px solid var(--border);
    background-color: var(--bg-tertiary);
    transition: all var(--transition-time);
}

.language-option input:checked + .language-preview {
    border-color: var(--accent-primary);
    background-color: rgba(156, 39, 176, 0.1);
}

.language-flag {
    font-size: 2rem;
}

.language-name {
    font-weight: 600;
    font-size: 1.6rem;
}

.form-actions {
    margin-top: 3rem;
    display: flex;
    justify-content: center;
}

.form-actions .btn {
    padding: 1rem 3rem;
    font-size: 1.6rem;
}

@media (max-width: 768px) {
    .settings-wrapper {
        padding: 2rem;
    }
    
    .theme-selector, .language-selector {
        flex-direction: column;
    }
}
</style> 