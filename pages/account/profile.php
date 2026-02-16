<?php
// pages/account/profile.php
require_once __DIR__ . '/../../includes/config.php';
requireLogin();

$current_user = getCurrentUser($conn);
$page_title = 'My Profile';
$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        
        $errors = [];
        
        if (empty($full_name)) {
            $errors[] = "Full name is required";
        }
        
        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        $check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $check_email->bind_param("si", $email, $_SESSION['user_id']);
        $check_email->execute();
        if ($check_email->get_result()->num_rows > 0) {
            $errors[] = "Email already exists";
        }
        
        if (empty($errors)) {
            $update = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE user_id = ?");
            $update->bind_param("ssi", $full_name, $email, $_SESSION['user_id']);
            
            if ($update->execute()) {
                $success_message = "Profile updated successfully!";
                $current_user = getCurrentUser($conn);
            } else {
                $error_message = "Failed to update profile";
            }
        } else {
            $error_message = implode("<br>", $errors);
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        $errors = [];
        
        $check = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $check->bind_param("i", $_SESSION['user_id']);
        $check->execute();
        $result = $check->get_result();
        $user = $result->fetch_assoc();
        
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        }
        
        if (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
        
        if (empty($errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $update->bind_param("si", $hashed_password, $_SESSION['user_id']);
            
            if ($update->execute()) {
                $success_message = "Password changed successfully!";
            } else {
                $error_message = "Failed to change password";
            }
        } else {
            $error_message = implode("<br>", $errors);
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<nav class="navbar">
    <div class="nav-left">
        <div class="logo-container">
            <div class="clsu-logo">CLSU</div>
            <div class="institution-info">
                <span class="institution-name">CENTRAL LUZON STATE UNIVERSITY</span>
                <span class="institution-campus">Science City of Muñoz, Nueva Ecija</span>
                <span class="system-name">Water Monitoring System</span>
            </div>
        </div>
    </div>

    <div class="nav-right">
        <div class="user-dropdown">
            <div class="dropdown-trigger">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($current_user['full_name'], 0, 1)); ?>
                </div>
                <div class="user-info-small">
                    <span class="user-name-small"><?php echo sanitize($current_user['full_name']); ?></span>
                    <span class="user-role-small"><?php echo $current_user['role']; ?></span>
                </div>
                <i class="fas fa-chevron-down dropdown-arrow"></i>
            </div>

            <div class="dropdown-menu">
                <div class="dropdown-header">
                    <div class="dropdown-avatar">
                        <?php echo strtoupper(substr($current_user['full_name'], 0, 1)); ?>
                    </div>
                    <div class="dropdown-user-info">
                        <h4><?php echo sanitize($current_user['full_name']); ?></h4>
                        <p><?php echo $current_user['email']; ?></p>
                    </div>
                </div>

                <div class="dropdown-items">
                    <a href="profile.php" style="padding: 12px 20px; display: flex; align-items: center; gap: 12px; color: #2c3e50; text-decoration: none; background: #f0f9f0; border-left: 3px solid #006837;">
                        <i class="fas fa-user-circle" style="width: 20px; color: #006837;"></i>
                        My Profile
                    </a>
                    <div style="height: 1px; background: #eee; margin: 8px 0;"></div>
                    <a href="<?php echo BASE_URL; ?>/auth/logout.php" style="padding: 12px 20px; display: flex; align-items: center; gap: 12px; color: #e74c3c; text-decoration: none;">
                        <i class="fas fa-sign-out-alt" style="width: 20px; color: #e74c3c;"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<div class="dashboard-container">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content" style="flex: 1; padding: 30px; background: #f9f9f9;">
        <!-- Page Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h2 style="color: #006837; font-size: 24px; font-weight: 600; margin: 0;">My Profile</h2>
            <div style="background: white; padding: 8px 15px; border-radius: 20px; font-size: 13px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <i class="fas fa-calendar-alt" style="color: #006837; margin-right: 5px;"></i>
                <?php echo date('F d, Y'); ?>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($success_message): ?>
        <div style="margin-bottom: 20px; background: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 10px; border-left: 4px solid #2e7d32; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-check-circle" style="font-size: 18px;"></i> <?php echo $success_message; ?>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div style="margin-bottom: 20px; background: #ffebee; color: #c62828; padding: 15px; border-radius: 10px; border-left: 4px solid #c62828; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-exclamation-circle" style="font-size: 18px;"></i> <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <!-- Profile Grid -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
            <!-- Profile Information Card -->
            <div style="background: white; border-radius: 15px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                <!-- Profile Header -->
                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #f0f0f0;">
                    <div style="width: 80px; height: 80px; background: #006837; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 32px; font-weight: bold; border: 3px solid #fdb813; box-shadow: 0 5px 15px rgba(0,104,55,0.2);">
                        <?php echo strtoupper(substr($current_user['full_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h3 style="color: #006837; font-size: 24px; margin: 0 0 5px 0;"><?php echo sanitize($current_user['full_name']); ?></h3>
                        <p style="color: #666; margin: 5px 0; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-envelope" style="color: #fdb813; width: 18px;"></i> <?php echo sanitize($current_user['email']); ?>
                        </p>
                        <p style="color: #666; margin: 5px 0; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-user-tag" style="color: #fdb813; width: 18px;"></i> Role: 
                            <span style="color: #006837; font-weight: 600; text-transform: uppercase; background: rgba(0,104,55,0.1); padding: 3px 10px; border-radius: 20px; font-size: 12px;"><?php echo $current_user['role']; ?></span>
                        </p>
                    </div>
                </div>

                <!-- Edit Profile Form -->
                <form method="POST" action="">
                    <h4 style="color: #006837; margin: 0 0 20px 0; padding-bottom: 10px; border-bottom: 2px solid #eee; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-edit"></i> Edit Profile Information
                    </h4>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; color: #006837; font-weight: 600; font-size: 14px;">
                            <i class="fas fa-user" style="margin-right: 5px; color: #fdb813;"></i> Full Name
                        </label>
                        <input type="text" name="full_name" value="<?php echo sanitize($current_user['full_name']); ?>" 
                               style="width: 100%; padding: 12px 15px; border: 2px solid #eee; border-radius: 10px; font-size: 14px; font-family: 'Poppins', sans-serif; box-sizing: border-box;">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; color: #006837; font-weight: 600; font-size: 14px;">
                            <i class="fas fa-envelope" style="margin-right: 5px; color: #fdb813;"></i> Email Address
                        </label>
                        <input type="email" name="email" value="<?php echo sanitize($current_user['email']); ?>" 
                               style="width: 100%; padding: 12px 15px; border: 2px solid #eee; border-radius: 10px; font-size: 14px; font-family: 'Poppins', sans-serif; box-sizing: border-box;">
                    </div>

                    <button type="submit" name="update_profile" 
                            style="width: 100%; background: #006837; color: white; border: none; padding: 14px; border-radius: 10px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; font-family: 'Poppins', sans-serif;">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>

            <!-- Change Password Card -->
            <div style="background: white; border-radius: 15px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                <h4 style="color: #006837; margin: 0 0 20px 0; padding-bottom: 10px; border-bottom: 2px solid #eee; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-lock"></i> Change Password
                </h4>

                <form method="POST" action="">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; color: #006837; font-weight: 600; font-size: 14px;">
                            <i class="fas fa-key" style="margin-right: 5px; color: #fdb813;"></i> Current Password
                        </label>
                        <input type="password" name="current_password" required 
                               style="width: 100%; padding: 12px 15px; border: 2px solid #eee; border-radius: 10px; font-size: 14px; font-family: 'Poppins', sans-serif; box-sizing: border-box;">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; color: #006837; font-weight: 600; font-size: 14px;">
                            <i class="fas fa-lock" style="margin-right: 5px; color: #fdb813;"></i> New Password
                        </label>
                        <input type="password" name="new_password" required minlength="6" 
                               style="width: 100%; padding: 12px 15px; border: 2px solid #eee; border-radius: 10px; font-size: 14px; font-family: 'Poppins', sans-serif; box-sizing: border-box;">
                        <small style="display: block; color: #888; font-size: 12px; margin-top: 5px;">Minimum of 6 characters</small>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; color: #006837; font-weight: 600; font-size: 14px;">
                            <i class="fas fa-check-circle" style="margin-right: 5px; color: #fdb813;"></i> Confirm New Password
                        </label>
                        <input type="password" name="confirm_password" required minlength="6" 
                               style="width: 100%; padding: 12px 15px; border: 2px solid #eee; border-radius: 10px; font-size: 14px; font-family: 'Poppins', sans-serif; box-sizing: border-box;">
                    </div>

                    <button type="submit" name="change_password" 
                            style="width: 100%; background: #fdb813; color: #004d29; border: none; padding: 14px; border-radius: 10px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; font-family: 'Poppins', sans-serif;">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
            </div>
        </div>

        <!-- Responsive Styles -->
        <style>
            @media (max-width: 768px) {
                .main-content {
                    padding: 20px !important;
                }
                div[style*="grid-template-columns: 1fr 1fr"] {
                    grid-template-columns: 1fr !important;
                }
                div[style*="display: flex; align-items: center; gap: 20px;"] {
                    flex-direction: column !important;
                    text-align: center !important;
                }
                div[style*="display: flex; justify-content: space-between;"] {
                    flex-direction: column !important;
                    gap: 10px !important;
                    text-align: center !important;
                }
            }
        </style>
    </main>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>