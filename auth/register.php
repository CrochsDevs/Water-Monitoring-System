<?php
require_once __DIR__ . '/../includes/config.php';
requireGuest();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    if (empty($full_name) || empty($email) || empty($username) || empty($password)) {
        $errors[] = 'All fields are required.';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'Username or email is already taken.';
        }
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, username, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $full_name, $email, $username, $hashed_password);
        
        if ($stmt->execute()) {
            $success = 'Registration successful! Redirecting to login...';
            header("refresh:3;url=login.php");
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
    
    if (!empty($errors)) {
        $error = implode('<br>', $errors);
    }
}

$page_title = 'Create Account';
include __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card register-card">
        <div class="auth-header">
            <div class="clsu-logo-placeholder">
                <i class="fas fa-seedling"></i>
            </div>
            <h1>Create Account</h1>
            <p>Join our monitoring community</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input 
                    type="text" 
                    id="full_name" 
                    name="full_name" 
                    placeholder="e.g. Juan Dela Cruz"
                    value="<?php echo sanitize($_POST['full_name'] ?? ''); ?>"
                    required
                >
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        placeholder="juan123"
                        value="<?php echo sanitize($_POST['username'] ?? ''); ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="juan@gmail.com"
                        value="<?php echo sanitize($_POST['email'] ?? ''); ?>"
                        required
                    >
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="" required>
                </div>
            </div>
            
            <p class="hint">Ensure your password is at least 6 characters long.</p>
            
            <button type="submit" class="btn btn-primary btn-block">Register Now</button>
            
            <div class="auth-footer">
                <span>Already have an account?</span>
                <a href="login.php">Back to Login</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>