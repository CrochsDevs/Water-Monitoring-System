<?php
    // includes/sidebar.php
    // This file contains the CLSU-themed sidebar
    // Make sure $current_user is available before including this file
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <h3>Water Monitoring</h3>
        <p>CLSU Dashboard</p>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-user-avatar">
            <?php echo strtoupper(substr($current_user['full_name'] ?? 'U', 0, 1)); ?>
        </div>
        <div class="sidebar-user-info">
            <h4><?php echo sanitize($current_user['full_name'] ?? 'User'); ?></h4>
            <p><?php echo $current_user['email'] ?? ' ?>'; ?></p>
        </div>
    </div>

    <nav class="sidebar-nav">
        <!-- MAIN MENU --> 
        <div class="sidebar-divider">MAIN</div>

        <ul class="sidebar-menu">
            <li class="sidebar-menu-item">
                <a href="<?php echo BASE_URL; ?>/pages/dashboard.php" class="sidebar-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="sidebar-menu-item">
                <a href="#" class="sidebar-menu-link">
                    <i class="fas fa-water"></i>
                    <span>History</span>
                </a>
            </li>

            <!-- <li class="sidebar-menu-item">
                <a href="#" class="sidebar-menu-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>Usage Statistics</span>
                    <span class="menu-badge">12</span>
                </a>
            </li>

            <li class="sidebar-menu-item">
                <a href="#" class="sidebar-menu-link">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Monitoring Points</span>
                </a>
            </li>

            <li class="sidebar-menu-item">
                <a href="#" class="sidebar-menu-link">
                    <i class="fas fa-history"></i>
                    <span>Historical Data</span>
                </a>
            </li>

            <li class="sidebar-menu-item">
                <a href="#" class="sidebar-menu-link">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Alerts</span>
                    <span class="menu-badge warning">3</span>
                </a>
            </li> -->
        </ul>

        <!-- REPORTS SECTION -->
        <!-- <div class="sidebar-divider">REPORTS</div> -->

        <!-- <ul class="sidebar-menu">
            <li class="sidebar-menu-item">
                <a href="#" class="sidebar-menu-link">
                    <i class="fas fa-file-pdf"></i>
                    <span>Daily Reports</span>
                </a>
            </li>

            <li class="sidebar-menu-item">
                <a href="#" class="sidebar-menu-link">
                    <i class="fas fa-file-excel"></i>
                    <span>Export Data</span>
                </a>
            </li>

            <li class="sidebar-menu-item">
                <a href="#" class="sidebar-menu-link">
                    <i class="fas fa-print"></i>
                    <span>Print Records</span>
                </a>
            </li>

            <li class="sidebar-menu-item">
                <a href="#" class="sidebar-menu-link">
                    <i class="fas fa-chart-pie"></i>
                    <span>Analytics</span>
                </a>
            </li>
        </ul> -->

        <!-- ADMIN SECTION (Lalabas lang if admin) -->
        <?php if (isset($current_user['role']) && $current_user['role'] === 'admin'): ?>
        <!-- <div class="sidebar-divider">ADMIN</div> -->

        <!-- <ul class="sidebar-menu">
            <li class="sidebar-menu-item">
                <a href="#" class="sidebar-menu-link">
                    <i class="fas fa-users"></i>
                    <span>User Management</span>
                    <span class="menu-badge warning">5</span>
                </a>
            </li>

            <li class="sidebar-menu-item">
                <a href="#" class="sidebar-menu-link">
                    <i class="fas fa-cogs"></i>
                    <span>System Settings</span>
                </a>
            </li>

            <li class="sidebar-menu-item">
                <a href="#" class="sidebar-menu-link">
                    <i class="fas fa-database"></i>
                    <span>Backup & Restore</span>
                </a>
            </li>

            <li class="sidebar-menu-item">
                <a href="#" class="sidebar-menu-link">
                    <i class="fas fa-shield-alt"></i>
                    <span>Security</span>
                </a>
            </li>

            <li class="sidebar-menu-item">
                <a href="#" class="sidebar-menu-link">
                    <i class="fas fa-server"></i>
                    <span>System Logs</span>
                </a>
            </li>
        </ul> -->
        <?php endif; ?>

        <!-- TOOLS SECTION -->
        <!-- <div class="sidebar-divider">TOOLS</div> -->

        <!-- <ul class="sidebar-menu">
            <li class="sidebar-menu-item">
                <a href="#" class="sidebar-menu-link">
                    <i class="fas fa-calculator"></i>
                    <span>Water Calculator</span>
                </a>
            </li>

            <li class="sidebar-menu-item">
                <a href="#" class="sidebar-menu-link">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Schedule</span>
                </a>
            </li>

            <li class="sidebar-menu-item">
                <a href="#" class="sidebar-menu-link">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                    <span class="menu-badge">2</span>
                </a>
            </li>
        </ul> -->
    </nav>

</aside>