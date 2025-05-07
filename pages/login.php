<?php
// Redirect if already logged in
if (isLoggedIn()) {
    redirect("index.php?page=home");
}

// Check for remember me token
if (authenticateWithRememberToken()) {
    redirect("index.php?page=home");
}

// Process login form
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form inputs
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = __('all_fields_required');
    } else {
        global $db;
        
        // Check if username exists
        $sql = "SELECT id, username, password, role FROM users WHERE username = ? AND is_banned = 0";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Update last login time and set online status
                $sql = "UPDATE users SET last_login = NOW(), is_online = 1, last_activity = NOW() WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                
                // Set remember me cookie if checked
                if ($remember) {
                    $token = generateRandomString(32);
                    $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                    
                    setcookie('remember_token', $token, $expiry, '/');
                    
                    // Save token in database
                    $token_hash = password_hash($token, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET remember_token = ? WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->bind_param("si", $token_hash, $user['id']);
                    $stmt->execute();
                }
                
                // Set flash message
                $_SESSION['flash_message'] = __('login_success') . " " . $user['username'] . "!";
                $_SESSION['flash_type'] = "success";
                
                // Redirect to dashboard
                redirect("index.php?page=characters");
            } else {
                $error = __('login_error');
            }
        } else {
            $error = __('login_error');
        }
    }
}
?>

<div class="login-container">
    <div class="login-image">
        <div class="login-overlay"></div>
        <div class="login-quote">
            <p>"<?php echo __('login_quote'); ?>"</p>
            <span><?php echo __('login_quote_author'); ?></span>
        </div>
    </div>
    
    <div class="login-form-container">
        <div class="login-header">
            <h2><?php echo __('welcome_back'); ?></h2>
            <p><?php echo __('login_desc'); ?></p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form class="login-form" method="POST" action="">
            <div class="form-group">
                <label for="username"><?php echo __('username'); ?></label>
                <div class="input-icon-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="username" name="username" placeholder="<?php echo __('enter_username'); ?>" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password"><?php echo __('password'); ?></label>
                <div class="input-icon-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" placeholder="<?php echo __('enter_password'); ?>" required>
                </div>
            </div>
            
            <div class="form-options">
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember"><?php echo __('remember_me'); ?></label>
                </div>
                
                <a href="?page=forgot_password" class="forgot-password"><?php echo __('forgot_password'); ?></a>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-sign-in-alt"></i> <?php echo __('login'); ?>
            </button>
        </form>
        
        <div class="login-footer">
            <p><?php echo __('no_account'); ?> <a href="?page=register"><?php echo __('register_here'); ?></a></p>
        </div>
    </div>
</div>

<style>
.login-container {
    display: flex;
    max-width: var(--container-width);
    margin: 0 auto;
    background-color: var(--bg-secondary);
    border-radius: var(--border-radius);
    box-shadow: 0 5px 15px var(--shadow);
    overflow: hidden;
}

.login-image {
    flex: 1;
    background-image: url('assets/images/login-bg.jpg');
    background-size: cover;
    background-position: center;
    min-height: 500px;
    position: relative;
    display: flex;
    align-items: flex-end;
}

.login-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.7));
}

.login-quote {
    position: relative;
    z-index: 2;
    padding: 2rem;
    color: white;
}

.login-quote p {
    font-style: italic;
    font-size: 1.8rem;
    margin-bottom: 1rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
}

.login-quote span {
    font-size: 1.4rem;
    opacity: 0.8;
}

.login-form-container {
    flex: 1;
    padding: 4rem;
}

.login-header {
    text-align: center;
    margin-bottom: 3rem;
}

.login-header h2 {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: var(--accent-primary);
}

.login-header p {
    font-size: 1.6rem;
    color: var(--text-secondary);
}

.login-form {
    margin-bottom: 2rem;
}

.form-group {
    margin-bottom: 2rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.8rem;
    font-weight: 600;
}

.input-icon-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 1.5rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--accent-primary);
}

.login-form input[type="text"],
.login-form input[type="password"] {
    width: 100%;
    padding: 1.2rem 1.5rem 1.2rem 4.5rem;
    border: 1px solid var(--border);
    border-radius: var(--border-radius);
    background-color: var(--bg-tertiary);
    color: var(--text-primary);
    font-size: 1.6rem;
    transition: all var(--transition-time);
}

.login-form input:focus {
    outline: none;
    border-color: var(--accent-primary);
    box-shadow: 0 0 0 2px rgba(156, 39, 176, 0.2);
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 3rem;
}

.remember-me {
    display: flex;
    align-items: center;
}

.remember-me input {
    margin-right: 0.8rem;
}

.remember-me label {
    font-size: 1.4rem;
    color: var(--text-secondary);
}

.forgot-password {
    font-size: 1.4rem;
    color: var(--accent-primary);
}

.btn-block {
    width: 100%;
    padding: 1.5rem;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    font-size: 1.6rem;
}

.login-footer {
    text-align: center;
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid var(--border);
}

.login-footer p {
    font-size: 1.4rem;
    color: var(--text-secondary);
}

.login-footer a {
    color: var(--accent-primary);
    font-weight: 600;
}

@media (max-width: 992px) {
    .login-container {
        flex-direction: column;
    }
    
    .login-image {
        min-height: 250px;
    }
}

@media (max-width: 768px) {
    .login-form-container {
        padding: 2rem;
    }
    
    .login-header h2 {
        font-size: 2.4rem;
    }
    
    .form-options {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}
</style> 