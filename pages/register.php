<?php
// Redirect if already logged in
if (isLoggedIn()) {
    redirect("index.php?page=home");
}

// Process registration form
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form inputs
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = __('all_fields_required');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = __('invalid_email');
    } elseif ($password !== $confirm_password) {
        $error = __('passwords_not_match');
    } elseif (!isStrongPassword($password)) {
        $error = __('password_strength');
    } else {
        global $db;
        
        // Check if username already exists
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = __('username_taken');
        } else {
            // Check if email already exists
            $sql = "SELECT id FROM users WHERE email = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = __('email_taken');
            } else {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("sss", $username, $email, $password_hash);
                
                if ($stmt->execute()) {
                    $success = __('register_success');
                    
                    // Set flash message
                    $_SESSION['flash_message'] = __('register_success');
                    $_SESSION['flash_type'] = "success";
                    
                    // Redirect to login page after 3 seconds
                    echo "<meta http-equiv='refresh' content='3;url=index.php?page=login'>";
                } else {
                    $error = __('register_error');
                }
            }
        }
    }
}
?>

<div class="register-container">
    <div class="auth-box">
        <div class="auth-header">
            <h2>Join the Adventure</h2>
            <p>Create your account to begin your fantasy journey</p>
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
        
        <form class="auth-form" method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-icon-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="username" name="username" placeholder="Choose a username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-icon-wrapper">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" placeholder="Create a password" required>
                </div>
                <div class="password-strength">
                    <div class="strength-bar"></div>
                </div>
                <small class="password-hint">Password must be at least 8 characters</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-icon-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                </div>
            </div>
            
            <div class="form-group terms-checkbox">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">I agree to the <a href="?page=terms" target="_blank">Terms of Service</a> and <a href="?page=privacy" target="_blank">Privacy Policy</a></label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>
        
        <div class="auth-footer">
            <p>Already have an account? <a href="?page=login">Login here</a></p>
        </div>
    </div>
    
    <div class="register-sidebar">
        <div class="benefit-card">
            <div class="benefit-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <h3>Create Characters</h3>
            <p>Design unique characters with different races and classes</p>
        </div>
        
        <div class="benefit-card">
            <div class="benefit-icon">
                <i class="fas fa-globe-europe"></i>
            </div>
            <h3>Explore the World</h3>
            <p>Discover magical realms, dangerous dungeons, and vibrant cities</p>
        </div>
        
        <div class="benefit-card">
            <div class="benefit-icon">
                <i class="fas fa-users"></i>
            </div>
            <h3>Join the Community</h3>
            <p>Connect with thousands of players in our growing community</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password strength checker
    const passwordInput = document.getElementById('password');
    const strengthBar = document.querySelector('.strength-bar');
    const passwordHint = document.querySelector('.password-hint');
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        
        // Update strength meter
        if (password.length >= 8) {
            strength += 25;
        }
        
        if (password.match(/[A-Z]/)) {
            strength += 25;
        }
        
        if (password.match(/[0-9]/)) {
            strength += 25;
        }
        
        if (password.match(/[^A-Za-z0-9]/)) {
            strength += 25;
        }
        
        // Update strength bar
        strengthBar.style.width = strength + '%';
        
        // Update color based on strength
        if (strength <= 25) {
            strengthBar.style.backgroundColor = '#e74c3c';
            passwordHint.textContent = '<?php echo __("password_very_weak"); ?>';
        } else if (strength <= 50) {
            strengthBar.style.backgroundColor = '#f39c12';
            passwordHint.textContent = '<?php echo __("password_weak"); ?>';
        } else if (strength <= 75) {
            strengthBar.style.backgroundColor = '#3498db';
            passwordHint.textContent = '<?php echo __("password_good"); ?>';
        } else {
            strengthBar.style.backgroundColor = '#2ecc71';
            passwordHint.textContent = '<?php echo __("password_strong"); ?>';
        }
    });
    
    // Password match validation
    const confirmInput = document.getElementById('confirm_password');
    
    confirmInput.addEventListener('input', function() {
        if (this.value !== passwordInput.value) {
            this.setCustomValidity('<?php echo __("passwords_not_match"); ?>');
        } else {
            this.setCustomValidity('');
        }
    });
});
</script>

<style>
.register-container {
    display: flex;
    gap: 4rem;
    max-width: var(--container-width);
    margin: 0 auto;
}

.auth-box {
    flex: 3;
    background-color: var(--bg-secondary);
    border-radius: var(--border-radius);
    padding: 3rem;
    box-shadow: 0 5px 15px var(--shadow);
}

.register-sidebar {
    flex: 2;
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.auth-header {
    text-align: center;
    margin-bottom: 3rem;
}

.auth-header h2 {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: var(--accent-primary);
}

.auth-header p {
    font-size: 1.6rem;
    color: var(--text-secondary);
}

.auth-form {
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

.auth-form input[type="text"],
.auth-form input[type="email"],
.auth-form input[type="password"] {
    width: 100%;
    padding: 1.2rem 1.5rem 1.2rem 4.5rem;
    border: 1px solid var(--border);
    border-radius: var(--border-radius);
    background-color: var(--bg-tertiary);
    color: var(--text-primary);
    font-size: 1.6rem;
    transition: all var(--transition-time);
}

.auth-form input:focus {
    outline: none;
    border-color: var(--accent-primary);
    box-shadow: 0 0 0 2px rgba(156, 39, 176, 0.2);
}

.password-strength {
    height: 4px;
    background-color: var(--border);
    margin-top: 1rem;
    border-radius: 2px;
    overflow: hidden;
}

.strength-bar {
    height: 100%;
    width: 0;
    background-color: #e74c3c;
    transition: all 0.3s ease;
}

.password-hint {
    display: block;
    margin-top: 0.5rem;
    font-size: 1.2rem;
    color: var(--text-secondary);
}

.terms-checkbox {
    display: flex;
    align-items: center;
    margin-bottom: 3rem;
}

.terms-checkbox input {
    margin-right: 1rem;
}

.terms-checkbox label {
    margin: 0;
    font-size: 1.4rem;
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

.auth-footer {
    text-align: center;
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid var(--border);
}

.auth-footer p {
    font-size: 1.4rem;
    color: var(--text-secondary);
}

.auth-footer a {
    color: var(--accent-primary);
    font-weight: 600;
}

.benefit-card {
    background-color: var(--bg-secondary);
    border-radius: var(--border-radius);
    padding: 2.5rem;
    box-shadow: 0 5px 15px var(--shadow);
    transition: transform 0.3s ease;
}

.benefit-card:hover {
    transform: translateY(-5px);
}

.benefit-icon {
    font-size: 3.5rem;
    color: var(--accent-primary);
    margin-bottom: 1.5rem;
}

.benefit-card h3 {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.benefit-card p {
    color: var(--text-secondary);
    font-size: 1.5rem;
}

@media (max-width: 992px) {
    .register-container {
        flex-direction: column;
    }
    
    .register-sidebar {
        flex-direction: row;
        flex-wrap: wrap;
    }
    
    .benefit-card {
        flex: 1 1 300px;
    }
}

@media (max-width: 768px) {
    .auth-box {
        padding: 2rem;
    }
    
    .auth-header h2 {
        font-size: 2.4rem;
    }
}

@media (max-width: 480px) {
    .register-sidebar {
        flex-direction: column;
    }
}
</style> 