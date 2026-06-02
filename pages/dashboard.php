<?php
    // pages/dashboard.php
    require_once __DIR__ . '/../includes/config.php';
    requireLogin();

    $current_user = getCurrentUser($conn);
    $page_title   = 'Dashboard';
    include __DIR__ . '/../includes/header.php';

    $devices = ['RF01', 'RF02', 'RF03'];

    // --- FETCH LATEST READING PER DEVICE ---
    $device_readings = [];
    $latest_ts = 'No data yet';
    foreach ($devices as $did) {
        $res = $conn->query("SELECT water_level_cm, distance_cm, alert, received_at FROM water_level_readings WHERE device_id = '$did' ORDER BY received_at DESC LIMIT 1");
        if ($res && $res->num_rows > 0) {
            $r = $res->fetch_assoc();
            $device_readings[$did] = [
                'level'     => floatval($r['water_level_cm']),
                'distance'  => floatval($r['distance_cm']),
                'alert'     => $r['alert'],
                'updated'   => $r['received_at']
            ];
            if (strtotime($r['received_at']) > strtotime($latest_ts)) {
                $latest_ts = $r['received_at'];
            }
        } else {
            $device_readings[$did] = [
                'level'     => null,
                'distance'  => null,
                'alert'     => null,
                'updated'   => null
            ];
        }
    }

    // --- FETCH 24-HOUR STATS ---
    $stats_all = [];
    foreach ($devices as $did) {
        $s = $conn->query("SELECT 
            AVG(water_level_cm) as avg_level,
            MIN(water_level_cm) as min_level,
            MAX(water_level_cm) as max_level,
            COUNT(*) as readings
            FROM water_level_readings 
            WHERE device_id = '$did' AND received_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        if ($s) {
            $row = $s->fetch_assoc();
            $stats_all[$did] = [
                'avg'  => $row && $row['avg_level'] ? round(floatval($row['avg_level']), 1) : '--',
                'min'  => $row && $row['min_level'] ? round(floatval($row['min_level']), 1) : '--',
                'max'  => $row && $row['max_level'] ? round(floatval($row['max_level']), 1) : '--',
                'cnt'  => $row ? intval($row['readings']) : 0
            ];
        }
    }

    // --- FETCH RECENT ALERTS ---
    $alerts = $conn->query("SELECT device_id, water_level_cm, alert, received_at FROM water_level_readings WHERE alert IS NOT NULL ORDER BY received_at DESC LIMIT 10");
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
                <span style="font-size: 14px; color: #718096; font-weight: 500; margin-left: 10px;">RF01 · RF02 · RF03</span>
            </h2>
            <div style="font-size: 13px; color: #718096;">
                <i class="fas fa-sync-alt"></i> Latest: <?php echo date('M d, Y h:i A', strtotime($latest_ts)); ?>
            </div>
        </div>

        <!-- Device Stats Grid -->
        <div class="stats-grid">
            <?php foreach ($devices as $did): 
                $dr = $device_readings[$did];
                $ds = $stats_all[$did];
                $field_colors = ['RF01' => '#3182ce', 'RF02' => '#38a169', 'RF03' => '#d69e2e'];
                $color = $field_colors[$did];
            ?>
            <div class="stat-card" style="border-top: 4px solid <?php echo $color; ?>;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                    <div>
                        <div style="font-size: 14px; font-weight: 700; color: <?php echo $color; ?>;"><?php echo $did; ?></div>
                        <div style="font-size: 11px; color: #718096; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Rice Field Monitor</div>
                    </div>
                    <div style="font-size: 22px;">💧</div>
                </div>
                <div class="stat-value" style="font-size: 28px;">
                    <?php echo $dr['level'] !== null ? $dr['level'] : '--'; ?> <span class="stat-unit">cm</span>
                </div>
                <div class="stat-label" style="font-size: 11px;">Latest Water Level Retrieved</div>
                <div class="water-level-gauge" style="margin: 8px auto 0;">
                    <?php 
                        $lv = $dr['level'];
                        $pct = $lv !== null ? min(100, ($lv / 30) * 100) : 0;
                        if ($pct < 10 && $lv !== null) $pct = 10;
                    ?>
                    <div class="water-level-fill" style="width: <?php echo $pct; ?>%; background: <?php echo $color; ?>;">
                        <span><?php echo $lv !== null ? $lv . 'cm' : '--'; ?></span>
                    </div>
                </div>
                <div style="display: flex; justify-content: space-between; margin-top: 10px; font-size: 12px; color: #718096;">
                    <span>24h avg: <strong><?php echo $ds['avg']; ?> cm</strong></span>
                    <span>
                        <?php if ($dr['alert']): ?>
                            <span class="alert-badge <?php echo $dr['alert']; ?>" style="font-size: 10px;">
                                <?php echo str_replace('_', ' ', $dr['alert']); ?>
                            </span>
                        <?php else: ?>
                            <span style="color: #38a169;">✓ OK</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div style="font-size: 11px; color: #a0aec0; margin-top: 5px;">
                    <?php echo $dr['updated'] ? date('M d, h:i A', strtotime($dr['updated'])) : 'No data'; ?>
                    · <?php echo $ds['cnt']; ?> readings
                </div>
            </div>
            <?php endforeach; ?>
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
                                <span style="color: #718096; font-size: 11px; margin-left: 6px;">[<?php echo $alert_row['device_id']; ?>]</span>
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
    const DEVICE_COLORS = {
        RF01: { border: '#3182ce', bg: 'rgba(49, 130, 206, 0.08)' },
        RF02: { border: '#38a169', bg: 'rgba(56, 161, 105, 0.08)' },
        RF03: { border: '#d69e2e', bg: 'rgba(214, 158, 46, 0.08)' }
    };
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
                        label: 'RF01',
                        data: [],
                        borderColor: DEVICE_COLORS.RF01.border,
                        backgroundColor: DEVICE_COLORS.RF01.bg,
                        tension: 0.3,
                        fill: false,
                        borderWidth: 2,
                        pointRadius: 2
                    },
                    {
                        label: 'RF02',
                        data: [],
                        borderColor: DEVICE_COLORS.RF02.border,
                        backgroundColor: DEVICE_COLORS.RF02.bg,
                        tension: 0.3,
                        fill: false,
                        borderWidth: 2,
                        pointRadius: 2
                    },
                    {
                        label: 'RF03',
                        data: [],
                        borderColor: DEVICE_COLORS.RF03.border,
                        backgroundColor: DEVICE_COLORS.RF03.bg,
                        tension: 0.3,
                        fill: false,
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

    // Fetch and update chart with all 3 device lines
    async function fetchDashboardData(filter) {
        try {
            const resp = await fetch(HISTORY_API + '?filter=' + filter);
            const data = await resp.json();
            if (data.success) {
                historyChart.data.labels = data.labels;
                historyChart.data.datasets[0].data = data.rf01.map(d => d.avg);
                historyChart.data.datasets[1].data = data.rf02.map(d => d.avg);
                historyChart.data.datasets[2].data = data.rf03.map(d => d.avg);
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
