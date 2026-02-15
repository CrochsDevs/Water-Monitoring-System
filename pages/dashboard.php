<?php
// pages/dashboard.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$current_user = getCurrentUser($conn);
$page_title = 'Dashboard';
include __DIR__ . '/../includes/header.php';
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
        <!-- User Dropdown -->
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
                    <a href="#" class="dropdown-item">
                        <i class="fas fa-user-circle"></i>
                        My Profile
                    </a>
                    <a href="#" class="dropdown-item">
                        <i class="fas fa-cog"></i>
                        Account Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="dropdown-item logout">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<div class="dashboard-container">
    <!-- INCLUDE SIDEBAR -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- Page Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h2 style="color: var(--clsu-green);">Dashboard Overview</h2>
            <div style="background: white; padding: 8px 15px; border-radius: 20px; font-size: 13px;">
            </div>
        </div>

        <!-- Welcome Card -->
        <div class="welcome-card" style="margin-bottom: 30px;">
            <div class="welcome-icon">💧</div>
            <h1>WELCOME, <?php echo strtoupper(sanitize($current_user['full_name'])); ?>!</h1>
            <p>Water Monitoring System - CLSU</p>


            <p style="color: var(--text-light);">You are logged in as <strong
                    style="color: var(--clsu-green); text-transform: uppercase;"><?php echo $current_user['role']; ?></strong>
            </p>
        </div>

    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>