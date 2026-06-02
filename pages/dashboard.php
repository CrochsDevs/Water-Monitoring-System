<?php
    // pages/dashboard.php
    require_once __DIR__ . '/../includes/config.php';
    requireLogin();

    $current_user = getCurrentUser($conn);
    $page_title   = 'Dashboard';
    include __DIR__ . '/../includes/header.php';

    // --- FETCH LATEST READING ---
    $latest = $conn->query("SELECT water_level_cm, distance_cm, battery_v, signal, alert, received_at FROM water_level_readings ORDER BY received_at DESC LIMIT 1");
    if ($latest && $latest->num_rows > 0) {
        $lr = $latest->fetch_assoc();
        $current_water_level = floatval($lr['water_level_cm']);
        $current_distance    = floatval($lr['distance_cm']);
        $current_battery     = floatval($lr['battery_v']);
        $current_signal      = intval($lr['signal']);
        $current_alert       = $lr['alert'];
        $last_update         = $lr['received_at'];
    } else {
        // Fallback
        $current_water_level = 12.5;
        $current_distance    = 187.5;
        $current_battery     = 12.4;
        $current_signal      = 18;
        $current_alert       = null;
        $last_update         = 'No data yet';
    }

    // --- FETCH 24-HOUR STATS ---
    $stats = $conn->query("SELECT 
        AVG(water_level_cm) as avg_level,
        MIN(water_level_cm) as min_level,
        MAX(water_level_cm) as max_level,
        AVG(battery_v) as avg_battery,
        COUNT(*) as total_readings
        FROM water_level_readings 
        WHERE received_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    
    $stats_row = $stats->fetch_assoc();
    $avg_level_24h  = $stats_row ? round(floatval($stats_row['avg_level']), 1) : $current_water_level;
    $min_level_24h  = $stats_row ? round(floatval($stats_row['min_level']), 1) : 0;
    $max_level_24h  = $stats_row ? round(floatval($stats_row['max_level']), 1) : $current_water_level;
    $avg_batt_24h   = $stats_row ? round(floatval($stats_row['avg_battery']), 1) : $current_battery;
    $total_readings = $stats_row ? intval($stats_row['total_readings']) : 0;

    // --- FETCH RECENT ALERTS ---
    $alerts = $conn->query("SELECT water_level_cm, alert, received_at FROM water_level_readings WHERE alert IS NOT NULL ORDER BY received_at DESC LIMIT 5");
?>

<style>
    /* Dashboard specific styles */
    .dashboard-container {
        display: flex;
        min-height: calc(100vh - 60px);
    }
    
    .main-content {
        flex: 1;
        padding: 25px;
        background: #f8fafc;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }

    .stat-card {
        background: #ffffff;
        padding: 22px;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border: 1px solid #eef2f5;
        text-align: center;
    }

    .stat-card .stat-icon {
        font-size: 28px;
        margin-bottom: 8px;
    }

    .stat-card .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #2d3748;
        line-height: 1.2;
    }

    .stat-card .stat-label {
        font-size: 12px;
        color: #718096;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        margin-top: 4px;
    }

    .stat-card .stat-unit {
        font-size: 14px;
        color: #718096;
        font-weight: 500;
    }

    .stat-card.water-level { border-top: 4px solid #3182ce; }
    .stat-card.battery { border-top: 4px solid #38a169; }
    .stat-card.signal { border-top: 4px solid #d69e2e; }
    .stat-card.alerts { border-top: 4px solid #e53e3e; }

    .alert-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .alert-badge.high_water { background: #fed7d7; color: #c53030; }
    .alert-badge.low_water { background: #fefcbf; color: #975a16; }
    .alert-badge.sensor_error { background: #e2e8f0; color: #4a5568; }
    .alert-badge.low_battery { background: #fed7d7; color: #9b2c2c; }

    .water-level-gauge {
        position: relative;
        width: 100%;
        max-width: 300px;
        height: 30px;
        background: #e2e8f0;
        border-radius: 15px;
        overflow: hidden;
        margin: 15px auto;
    }

    .water-level-fill {
        height: 100%;
        background: linear-gradient(90deg, #3182ce, #63b3ed);
        border-radius: 15px;
        transition: width 1s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 30px;
    }

    .water-level-fill span {
        color: white;
        font-size: 12px;
        font-weight: 700;
    }

    .alert-list {
        list-style: none;
        padding: 0;
        margin: 0;
        text-align: left;
    }

    .alert-list li {
        padding: 8px 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .alert-list li:last-child {
        border-bottom: none;
    }

    .no-alerts {
        color: #718096;
        font-size: 13px;
        font-style: italic;
    }

    /* Charts */
    .chart-card {
        background: #ffffff;
        padding: 25px;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border: 1px solid #eef2f5;
        margin-bottom: 25px;
    }

    .chart-card h3 {
        margin: 0 0 20px;
        font-size: 15px;
        font-weight: 600;
        color: #2d3748;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .chart-container {
        position: relative;
        width: 100%;
        height: 300px;
    }

    .filter-buttons {
        display: flex;
        gap: 6px;
        background: #f1f5f9;
        padding: 5px;
        border-radius: 8px;
        margin-left: auto;
    }

    .filter-btn {
        border: none;
        background: transparent;
        padding: 6px 14px;
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        cursor: pointer;
        border-radius: 6px;
        transition: all 0.2s;
    }

    .filter-btn:hover {
        background: #e2e8f0;
    }

    .filter-btn.active {
        background: #4CAF50;
        color: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<nav class="navbar">
    <div class="nav-left">
        <div class="logo-container">
            <div class="clsu-logo">CLSU</div>
            <div class="institution-info">
                <span class="institution-name">CENTRAL LUZON STATE UNIVERSITY</span>
                <span class="institution-campus">Science City of Muñoz, Nueva Ecija</span>
                <span class="system-name">Rice Field Water Monitoring System</span>
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
                    <a href="account/profile.php" class="dropdown-item">
                        <i class="fas fa-user-circle"></i>
                        My Profile
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
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 10px;">
            <h2 style="color: #2d3748; margin: 0;">
                <i class="fas fa-water" style="color: #3182ce;"></i> 
                Water Level Monitor
            </h2>
            <div style="font-size: 13px; color: #718096;">
                <i class="fas fa-sync-alt"></i> Last update: <?php echo date('M d, Y h:i A', strtotime($last_update)); ?>
                <span style="margin-left: 10px; background: #edf2f7; padding: 4px 10px; border-radius: 12px;">
                    Device: RF01
                </span>
                <span style="margin-left: 10px; background: #edf2f7; padding: 4px 10px; border-radius: 12px;">
                    Mode: USB Serial
                </span>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card water-level">
                <div class="stat-icon">💧</div>
                <div class="stat-value"><?php echo $current_water_level; ?> <span class="stat-unit">cm</span></div>
                <div class="stat-label">Current Water Level</div>
                <div class="water-level-gauge">
                    <?php 
                        $pct = min(100, ($current_water_level / 30) * 100);
                        if ($pct < 10) $pct = 10; // min width for visibility
                    ?>
                    <div class="water-level-fill" style="width: <?php echo $pct; ?>%;">
                        <span><?php echo $current_water_level; ?>cm</span>
                    </div>
                </div>
            </div>

            <div class="stat-card battery">
                <div class="stat-icon">🔋</div>
                <div class="stat-value"><?php echo $current_battery; ?> <span class="stat-unit">V</span></div>
                <div class="stat-label">Battery Voltage</div>
                <div style="font-size: 12px; color: #718096; margin-top: 5px;">
                    24h avg: <?php echo $avg_batt_24h; ?>V
                </div>
            </div>

            <div class="stat-card signal">
                <div class="stat-icon">📶</div>
                <div class="stat-value"><?php echo $current_signal; ?> <span class="stat-unit">/31</span></div>
                <div class="stat-label">Signal Strength</div>
                <div style="font-size: 12px; color: #718096; margin-top: 5px;">
                    <?php echo $total_readings; ?> readings (24h)
                </div>
            </div>

            <div class="stat-card alerts">
                <div class="stat-icon">🔔</div>
                <div class="stat-value" style="font-size: 24px;">
                    <?php if ($current_alert): ?>
                        <span class="alert-badge <?php echo $current_alert; ?>">
                            <?php echo str_replace('_', ' ', $current_alert); ?>
                        </span>
                    <?php else: ?>
                        <span style="color: #38a169;">✓ All Good</span>
                    <?php endif; ?>
                </div>
                <div class="stat-label">Latest Alert</div>
            </div>
        </div>

        <!-- 24h Statistics Row -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 25px;">
            <div style="background: white; padding: 15px; border-radius: 12px; border: 1px solid #eef2f5; text-align: center;">
                <div style="font-size: 11px; color: #718096; text-transform: uppercase; font-weight: 600;">24h Avg Level</div>
                <div style="font-size: 20px; font-weight: 700; color: #2d3748;"><?php echo $avg_level_24h; ?> cm</div>
            </div>
            <div style="background: white; padding: 15px; border-radius: 12px; border: 1px solid #eef2f5; text-align: center;">
                <div style="font-size: 11px; color: #718096; text-transform: uppercase; font-weight: 600;">24h Min</div>
                <div style="font-size: 20px; font-weight: 700; color: #2d3748;"><?php echo $min_level_24h; ?> cm</div>
            </div>
            <div style="background: white; padding: 15px; border-radius: 12px; border: 1px solid #eef2f5; text-align: center;">
                <div style="font-size: 11px; color: #718096; text-transform: uppercase; font-weight: 600;">24h Max</div>
                <div style="font-size: 20px; font-weight: 700; color: #2d3748;"><?php echo $max_level_24h; ?> cm</div>
            </div>
            <div style="background: white; padding: 15px; border-radius: 12px; border: 1px solid #eef2f5; text-align: center;">
                <div style="font-size: 11px; color: #718096; text-transform: uppercase; font-weight: 600;">Field Depth</div>
                <div style="font-size: 20px; font-weight: 700; color: #2d3748;">200 cm</div>
            </div>
        </div>

        <!-- History Chart -->
        <div class="chart-card">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <h3><i class="fas fa-chart-line" style="color: #4CAF50;"></i> Water Level History</h3>
                <div class="filter-buttons">
                    <button class="filter-btn" onclick="updateFilter('day', this)">Day</button>
                    <button class="filter-btn active" onclick="updateFilter('week', this)">Week</button>
                    <button class="filter-btn" onclick="updateFilter('month', this)">Month</button>
                    <button class="filter-btn" onclick="updateFilter('year', this)">Year</button>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="historyChart"></canvas>
            </div>
        </div>

        <!-- Recent Alerts -->
        <div class="chart-card">
            <h3><i class="fas fa-bell" style="color: #e53e3e;"></i> Recent Alerts</h3>
            <?php if ($alerts && $alerts->num_rows > 0): ?>
                <ul class="alert-list">
                    <?php while ($alert_row = $alerts->fetch_assoc()): ?>
                        <li>
                            <span>
                                <span class="alert-badge <?php echo $alert_row['alert']; ?>">
                                    <?php echo str_replace('_', ' ', $alert_row['alert']); ?>
                                </span>
                                <strong><?php echo $alert_row['water_level_cm']; ?> cm</strong>
                            </span>
                            <span style="color: #718096; font-size: 12px;">
                                <?php echo date('M d, h:i A', strtotime($alert_row['received_at'])); ?>
                            </span>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p class="no-alerts">No alerts in the past 24 hours — system is normal.</p>
            <?php endif; ?>
        </div>

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const HISTORY_API = '<?php echo BASE_URL; ?>/api/history_data.php';
    let historyChart = null;

    // Initialize chart
    function initChart() {
        const ctx = document.getElementById('historyChart').getContext('2d');
        historyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Water Level (cm)',
                        data: [],
                        borderColor: '#3182ce',
                        backgroundColor: 'rgba(49, 130, 206, 0.08)',
                        tension: 0.3,
                        fill: true,
                        borderWidth: 2,
                        pointRadius: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { usePointStyle: true, padding: 20 }
                    }
                },
                scales: {
                    y: {
                        title: { display: true, text: 'Water Level (cm)', font: { size: 12 } },
                        beginAtZero: false,
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // Fetch and update chart
    async function fetchDashboardData(filter) {
        try {
            const resp = await fetch(HISTORY_API + '?filter=' + filter);
            const data = await resp.json();
            if (data.success) {
                historyChart.data.labels = data.labels;
                historyChart.data.datasets[0].data = data.water_level.map(d => d.avg);
                historyChart.update();
            }
        } catch (e) {
            console.error('Failed to fetch:', e);
        }
    }

    // Filter handler
    function updateFilter(range, btn) {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        fetchDashboardData(range);
    }

    // Init
    document.addEventListener('DOMContentLoaded', function() {
        initChart();
        fetchDashboardData('week');
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
