<?php
// Redirect if not logged in
if (!isLoggedIn()) {
    redirect("index.php?page=login");
}

// Get user data
$userId = (int)$_SESSION['user_id'];
$user = getCurrentUser();

// Initialize variables
$error = "";
$success = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        // Get form inputs
        $email = sanitize($_POST['email']);
        $bio = sanitize($_POST['bio']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate input
        if (empty($email)) {
            $error = __('email_required');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = __('invalid_email');
        } else {
            global $db;
            
            // Check if email is already taken by another user
            $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("si", $email, $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = __('email_taken');
            } else {
                // Start with basic profile update
                $updatePassword = false;
                
                // Check if password update is requested
                if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
                    // All password fields must be provided
                    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                        $error = __('all_password_fields_required');
                    } elseif ($new_password !== $confirm_password) {
                        $error = __('passwords_not_match');
                    } elseif (!isStrongPassword($new_password)) {
                        $error = __('password_strength');
                    } else {
                        // Verify current password
                        if (password_verify($current_password, $user['password'])) {
                            // Password is correct, update it
                            $updatePassword = true;
                        } else {
                            $error = __('current_password_incorrect');
                        }
                    }
                }
                
                // Proceed if there are no errors
                if (empty($error)) {
                    // Update profile information
                    if ($updatePassword) {
                        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $sql = "UPDATE users SET email = ?, bio = ?, password = ? WHERE id = ?";
                        $stmt = $db->prepare($sql);
                        $stmt->bind_param("sssi", $email, $bio, $password_hash, $userId);
                    } else {
                        $sql = "UPDATE users SET email = ?, bio = ? WHERE id = ?";
                        $stmt = $db->prepare($sql);
                        $stmt->bind_param("ssi", $email, $bio, $userId);
                    }
                    
                    if ($stmt->execute()) {
                        $success = __('profile_updated');
                        // Refresh user data
                        $user = getCurrentUser();
                    } else {
                        $error = __('update_failed');
                    }
                }
            }
        }
    }
    
    // Handle avatar upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'assets/images/avatars/';
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 1048576; // 1MB
        
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_file($fileInfo, $_FILES['avatar']['tmp_name']);
        finfo_close($fileInfo);
        
        if (!in_array($fileType, $allowedTypes)) {
            $error = __('invalid_image_type');
        } elseif ($_FILES['avatar']['size'] > $maxSize) {
            $error = __('image_too_large');
        } else {
            // Generate a unique filename
            $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
            $targetFile = $uploadDir . $filename;
            
            // Make sure the upload directory exists
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
                // Update the avatar in the database
                $sql = "UPDATE users SET avatar = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("si", $filename, $userId);
                
                if ($stmt->execute()) {
                    $success = __('avatar_updated');
                    // Refresh user data
                    $user = getCurrentUser();
                } else {
                    $error = __('avatar_update_failed');
                }
            } else {
                $error = __('avatar_upload_failed');
            }
        }
    }
}

// Get user's characters
global $db;
$sql = "SELECT id, name, race, class, level FROM characters WHERE user_id = ? ORDER BY level DESC";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$characters = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get active character
$activeCharacter = getActiveCharacter($userId);
?>

<div class="profile-container">
    <div class="page-header">
        <h1><?php echo __('my_profile'); ?></h1>
        <p><?php echo __('manage_your_account'); ?></p>
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
    
    <div class="profile-grid">
        <div class="profile-sidebar">
            <div class="profile-card">
                <div class="profile-header">
                    <div class="avatar-container">
                        <img src="assets/images/avatars/<?php echo $user['avatar']; ?>" alt="<?php echo $user['username']; ?>" class="avatar-img">
                        <div class="avatar-overlay">
                            <form id="avatar-form" method="POST" action="" enctype="multipart/form-data">
                                <input type="file" id="avatar-upload" name="avatar" accept="image/jpeg, image/png, image/gif" style="display: none;">
                                <label for="avatar-upload" class="avatar-edit-btn">
                                    <i class="fas fa-camera"></i>
                                </label>
                            </form>
                        </div>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo $user['username']; ?></h2>
                        <p class="role"><?php echo ucfirst($user['role']); ?></p>
                        <div class="online-status <?php echo ($user['is_online'] ? 'online' : 'offline'); ?>">
                            <span class="status-dot"></span>
                            <?php echo ($user['is_online'] ? __('online') : __('offline')); ?>
                        </div>
                    </div>
                </div>
                
                <div class="profile-details">
                    <div class="detail-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h4><?php echo __('member_since'); ?></h4>
                            <p><?php echo formatDate($user['created_at'], 'd M Y'); ?></p>
                        </div>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-sign-in-alt"></i>
                        <div>
                            <h4><?php echo __('last_login'); ?></h4>
                            <p><?php echo formatDate($user['last_login'], 'd M Y, H:i'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="character-section">
                <h3><i class="fas fa-users"></i> <?php echo __('my_characters'); ?></h3>
                <?php if (count($characters) > 0): ?>
                <div class="character-list">
                    <?php foreach ($characters as $character): ?>
                    <div class="character-item <?php echo ($activeCharacter && $activeCharacter['id'] == $character['id']) ? 'active' : ''; ?>">
                        <div class="character-icon">
                            <span class="race-icon race-<?php echo $character['race']; ?>"></span>
                            <span class="class-icon class-<?php echo $character['class']; ?>"></span>
                        </div>
                        <div class="character-info">
                            <h4><?php echo $character['name']; ?></h4>
                            <p>
                                <span><?php echo __(strtolower($character['race'])); ?></span> •
                                <span><?php echo __(strtolower($character['class'])); ?></span> •
                                <?php echo __('level'); ?> <?php echo $character['level']; ?>
                            </p>
                        </div>
                        <?php if ($activeCharacter && $activeCharacter['id'] == $character['id']): ?>
                        <div class="active-badge" title="<?php echo __('active_character'); ?>">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="no-characters">
                    <p><?php echo __('no_characters'); ?></p>
                    <a href="?page=characters" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> <?php echo __('create_character'); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="profile-main">
            <div class="profile-section">
                <h3><i class="fas fa-user-edit"></i> <?php echo __('edit_profile'); ?></h3>
                <form class="profile-form" method="POST" action="">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-group">
                        <label for="username"><?php echo __('username'); ?></label>
                        <input type="text" id="username" value="<?php echo $user['username']; ?>" disabled>
                        <small><?php echo __('username_cant_change'); ?></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><?php echo __('email'); ?></label>
                        <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio"><?php echo __('bio'); ?></label>
                        <textarea id="bio" name="bio" rows="4"><?php echo $user['bio']; ?></textarea>
                    </div>
                    
                    <div class="password-section">
                        <h4><?php echo __('change_password'); ?></h4>
                        <p class="section-hint"><?php echo __('leave_blank_no_change'); ?></p>
                        
                        <div class="form-group">
                            <label for="current_password"><?php echo __('current_password'); ?></label>
                            <input type="password" id="current_password" name="current_password">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password"><?php echo __('new_password'); ?></label>
                            <input type="password" id="new_password" name="new_password">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password"><?php echo __('confirm_password'); ?></label>
                            <input type="password" id="confirm_password" name="confirm_password">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?php echo __('save_changes'); ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="profile-section account-info">
                <h3><i class="fas fa-shield-alt"></i> <?php echo __('account_security'); ?></h3>
                <div class="security-items">
                    <div class="security-item">
                        <div class="security-icon">
                            <i class="fas fa-key"></i>
                        </div>
                        <div class="security-info">
                            <h4><?php echo __('password_strength'); ?></h4>
                            <div class="strength-meter">
                                <div class="strength-bar" style="width: 80%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="security-item">
                        <div class="security-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div class="security-info">
                            <h4><?php echo __('account_settings'); ?></h4>
                            <a href="?page=settings" class="btn btn-tertiary btn-sm"><?php echo __('manage_settings'); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle avatar upload
    const avatarUpload = document.getElementById('avatar-upload');
    const avatarForm = document.getElementById('avatar-form');
    
    avatarUpload.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            avatarForm.submit();
        }
    });
});
</script>

<style>
.profile-container {
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

.profile-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 3rem;
}

.profile-card {
    background-color: var(--bg-secondary);
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: 0 5px 15px var(--shadow);
    margin-bottom: 3rem;
}

.profile-header {
    padding: 3rem;
    text-align: center;
    position: relative;
}

.avatar-container {
    position: relative;
    width: 150px;
    height: 150px;
    margin: 0 auto 2rem;
    border-radius: 50%;
    overflow: hidden;
    box-shadow: 0 3px 10px var(--shadow);
}

.avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0, 0, 0, 0.5);
    padding: 0.8rem 0;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.avatar-container:hover .avatar-overlay {
    opacity: 1;
}

.avatar-edit-btn {
    color: white;
    cursor: pointer;
    font-size: 1.8rem;
}

.profile-info h2 {
    font-size: 2.4rem;
    margin-bottom: 0.5rem;
}

.profile-info .role {
    font-size: 1.6rem;
    color: var(--accent-primary);
    margin-bottom: 1rem;
}

.online-status {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1.5rem;
    border-radius: 2rem;
    font-size: 1.4rem;
}

.online-status.online {
    background-color: rgba(46, 204, 113, 0.1);
    color: #2ecc71;
}

.online-status.offline {
    background-color: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
}

.status-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.online .status-dot {
    background-color: #2ecc71;
}

.offline .status-dot {
    background-color: #e74c3c;
}

.profile-details {
    background-color: var(--bg-tertiary);
    padding: 2rem;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1rem 0;
    border-bottom: 1px solid var(--border);
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-item i {
    font-size: 2rem;
    color: var(--accent-primary);
}

.detail-item h4 {
    font-size: 1.4rem;
    margin-bottom: 0.4rem;
    color: var(--text-secondary);
}

.detail-item p {
    font-size: 1.6rem;
}

.character-section {
    background-color: var(--bg-secondary);
    border-radius: var(--border-radius);
    padding: 2rem;
    box-shadow: 0 5px 15px var(--shadow);
}

.character-section h3 {
    font-size: 2rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.character-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.character-item {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    background-color: var(--bg-tertiary);
    border-radius: var(--border-radius);
    transition: all var(--transition-time);
    position: relative;
}

.character-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 10px var(--shadow);
}

.character-item.active {
    border: 2px solid var(--accent-primary);
    background-color: rgba(156, 39, 176, 0.1);
}

.character-icon {
    position: relative;
    width: 50px;
    height: 50px;
    margin-right: 1.5rem;
}

.race-icon, .class-icon {
    display: block;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--accent-secondary);
    position: absolute;
}

.race-icon {
    top: 0;
    left: 0;
    z-index: 1;
}

.class-icon {
    bottom: 0;
    right: 0;
    z-index: 2;
}

.character-info h4 {
    font-size: 1.8rem;
    margin-bottom: 0.5rem;
}

.character-info p {
    font-size: 1.4rem;
    color: var(--text-secondary);
}

.active-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    color: var(--accent-primary);
    font-size: 1.8rem;
}

.no-characters {
    text-align: center;
    padding: 3rem 0;
}

.no-characters p {
    margin-bottom: 2rem;
    color: var(--text-secondary);
}

.profile-section {
    background-color: var(--bg-secondary);
    border-radius: var(--border-radius);
    padding: 3rem;
    box-shadow: 0 5px 15px var(--shadow);
    margin-bottom: 3rem;
}

.profile-section:last-child {
    margin-bottom: 0;
}

.profile-section h3 {
    font-size: 2.2rem;
    margin-bottom: 2.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.profile-form .form-group {
    margin-bottom: 2.5rem;
}

.profile-form label {
    display: block;
    margin-bottom: 0.8rem;
    font-weight: 600;
}

.profile-form input,
.profile-form textarea {
    width: 100%;
    padding: 1.2rem 1.5rem;
    border: 1px solid var(--border);
    border-radius: var(--border-radius);
    background-color: var(--bg-tertiary);
    color: var(--text-primary);
    font-size: 1.6rem;
    transition: all var(--transition-time);
}

.profile-form input:disabled {
    background-color: rgba(0, 0, 0, 0.05);
    cursor: not-allowed;
}

.profile-form input:focus,
.profile-form textarea:focus {
    outline: none;
    border-color: var(--accent-primary);
    box-shadow: 0 0 0 2px rgba(156, 39, 176, 0.2);
}

.profile-form small {
    display: block;
    margin-top: 0.8rem;
    font-size: 1.3rem;
    color: var(--text-tertiary);
}

.password-section {
    margin: 3rem 0;
    padding-top: 2rem;
    border-top: 1px solid var(--border);
}

.password-section h4 {
    font-size: 1.8rem;
    margin-bottom: 1rem;
}

.section-hint {
    margin-bottom: 2rem;
    font-size: 1.4rem;
    color: var(--text-secondary);
}

.form-actions {
    margin-top: 3rem;
}

.security-items {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.security-item {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.security-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: rgba(156, 39, 176, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: var(--accent-primary);
}

.security-info {
    flex: 1;
}

.security-info h4 {
    font-size: 1.8rem;
    margin-bottom: 1rem;
}

.strength-meter {
    height: 6px;
    background-color: var(--bg-tertiary);
    border-radius: 3px;
    overflow: hidden;
}

.strength-bar {
    height: 100%;
    background-color: var(--accent-primary);
}

@media (max-width: 992px) {
    .profile-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .profile-section {
        padding: 2rem;
    }
    
    .avatar-container {
        width: 100px;
        height: 100px;
    }
}
</style> 