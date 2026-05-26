<?php
    // pages/dashboard.php
    require_once __DIR__ . '/../includes/config.php';
    requireLogin();

    $current_user = getCurrentUser($conn);
    $page_title   = 'Dashboard';
    include __DIR__ . '/../includes/header.php';

    // --- REAL-TIME DATA MULA SA BAROMETERS (Naka-unit na cm) ---
    $station_pressure  = 101; // Kasalukuyang reading sa cm
    $tank_pressure     = 95;  // Kasalukuyang reading sa cm
    $pipeline_pressure = 105; // Kasalukuyang reading sa cm
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* Responsive grid para sa mga Barometer Gauges */
    .barometers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 25px;
        margin-top: 25px;
        margin-bottom: 30px;
    }
    .barometer-card {
        background: #ffffff;
        padding: 25px;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border: 1px solid #eef2f5;
        text-align: center;
        position: relative;
    }
    .barometer-card h3 {
        margin-top: 0;
        margin-bottom: 5px;
        color: #333;
        font-size: 16px;
        font-weight: 600;
    }
    .barometer-location {
        font-size: 12px;
        color: #718096;
        margin-bottom: 15px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .gauge-container {
        position: relative;
        width: 100%;
        height: 180px;
        margin: 0 auto;
    }
    .gauge-value {
        position: absolute;
        bottom: 15px;
        left: 50%;
        transform: translateX(-50%);
        text-align: center;
    }
    .gauge-value .number {
        font-size: 24px;
        font-weight: 700;
        color: #2d3748;
        display: block;
    }
    .gauge-value .unit {
        font-size: 11px;
        color: #718096;
        font-weight: 600;
    }

    /* Estilo para sa Historical Chart Section at Filter Buttons */
    .history-card {
        background: #ffffff;
        padding: 25px;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border: 1px solid #eef2f5;
        margin-top: 25px;
    }
    .history-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 15px;
    }
    .history-card h3 {
        margin: 0;
        color: #333;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .filter-buttons {
        display: flex;
        gap: 8px;
        background: #f1f5f9;
        padding: 6px;
        border-radius: 8px;
    }
    .filter-btn {
        border: 1px solid #cbd5e1;
        background: #ffffff;
        padding: 6px 16px;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        cursor: pointer;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    .filter-btn:hover {
        background: #f8fafc;
        color: #000000;
    }
    .filter-btn.active {
        background: #4CAF50 !important;
        color: #ffffff !important;
        border-color: #4CAF50 !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .history-chart-container {
        position: relative;
        width: 100%;
        height: 320px;
    }
</style>

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
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h2 style="color: #4CAF50;">Dashboard Overview</h2>
            <div style="background: white; padding: 8px 15px; border-radius: 20px; font-size: 13px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <span style="color: #718096;"><i class="fas fa-broadcast-tower"></i> Live & Historical Data</span>
            </div>
        </div>

        <div class="barometers-grid">

            <div class="barometer-card">
                <h3>Barometer 1</h3>
                <div class="barometer-location">Station Pressure</div>
                <div class="gauge-container">
                    <canvas id="barometer1"></canvas>
                    <div class="gauge-value">
                        <span class="number"><?php echo $station_pressure; ?></span>
                        <span class="unit">cm</span>
                    </div>
                </div>
            </div>

            <div class="barometer-card">
                <h3>Barometer 2</h3>
                <div class="barometer-location">Tank Pressure</div>
                <div class="gauge-container">
                    <canvas id="barometer2"></canvas>
                    <div class="gauge-value">
                        <span class="number"><?php echo $tank_pressure; ?></span>
                        <span class="unit">cm</span>
                    </div>
                </div>
            </div>

            <div class="barometer-card">
                <h3>Barometer 3</h3>
                <div class="barometer-location">Pipeline Pressure</div>
                <div class="gauge-container">
                    <canvas id="barometer3"></canvas>
                    <div class="gauge-value">
                        <span class="number"><?php echo $pipeline_pressure; ?></span>
                        <span class="unit">cm</span>
                    </div>
                </div>
            </div>

        </div>

        <div class="history-card">
            <div class="history-header">
                <h3><i class="fas fa-chart-line" style="color: #4CAF50;"></i> Historical Pressure Trends</h3>
                
                <div class="filter-buttons">
                    <button class="filter-btn" onclick="updateFilter('day', this)">Day</button>
                    <button class="filter-btn active" onclick="updateFilter('week', this)">Week</button>
                    <button class="filter-btn" onclick="updateFilter('month', this)">Month</button>
                    <button class="filter-btn" onclick="updateFilter('year', this)">Year</button>
                </div>
            </div>
            
            <div class="history-chart-container">
                <canvas id="historyChart"></canvas>
            </div>
        </div>

    </main>
</div>

<script>
    // --- REALTIME DATA (GAUGES) ---
    const val1 = <?php echo $station_pressure; ?>;
    const val2 = <?php echo $tank_pressure; ?>;
    const val3 = <?php echo $pipeline_pressure; ?>;
    const maxGaugeLimit = 200; 

    // --- DATA STORE PARA SA FILTERS (DAY, WEEK, MONTH, YEAR) ---
    const filterData = {
        day: {
            labels: ["12 AM", "4 AM", "8 AM", "12 PM", "4 PM", "8 PM", "11 PM"],
            b1: [98, 100, 102, 105, 101, 103, 101],
            b2: [92, 94, 96, 99, 95, 96, 95],
            b3: [102, 104, 106, 109, 105, 107, 105]
        },
        week: {
            labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
            b1: [101, 104, 101, 99, 103, 105, 101],
            b2: [95, 97, 96, 93, 95, 98, 95],
            b3: [105, 108, 105, 103, 107, 109, 105]
        },
        month: {
            labels: ["Week 1", "Week 2", "Week 3", "Week 4"],
            b1: [100, 103, 105, 102],
            b2: [94, 96, 98, 95],
            b3: [104, 107, 109, 106]
        },
        year: {
            labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
            b1: [98, 99, 101, 103, 105, 104, 102, 101, 100, 102, 103, 101],
            b2: [92, 93, 95, 96, 98, 97, 96, 95, 94, 95, 96, 95],
            b3: [102, 103, 105, 107, 109, 108, 106, 105, 104, 106, 107, 105]
        }
    };

    // --- INITIALIZE GAUGE GENERATOR ---
    function createBarometerGauge(elementId, value, color) {
        const ctx = document.getElementById(elementId).getContext('2d');
        const currentVal = Math.min(value, maxGaugeLimit);
        const remainingVal = maxGaugeLimit - currentVal;

        return new Chart(ctx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [currentVal, remainingVal],
                    backgroundColor: [color, '#e2e8f0'],
                    borderWidth: 0,
                    borderRadius: [10, 0]
                }]
            },
            options: {
                rotation: -90,
                circumference: 180,
                cutout: '80%',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: { enabled: false },
                    legend: { display: false }
                }
            }
        });
    }

    // Render Gauges
    createBarometerGauge('barometer1', val1, '#4CAF50');
    createBarometerGauge('barometer2', val2, '#3182ce');
    createBarometerGauge('barometer3', val3, '#e53e3e');

    // --- INITIALIZE HISTORICAL LINE CHART ---
    const ctxHistory = document.getElementById('historyChart').getContext('2d');
    let historyChart = new Chart(ctxHistory, {
        type: 'line',
        data: {
            labels: filterData.week.labels, 
            datasets: [
                {
                    label: 'Barometer 1 (Station)',
                    data: filterData.week.b1,
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.04)',
                    tension: 0.2,
                    fill: true
                },
                {
                    label: 'Barometer 2 (Tank)',
                    data: filterData.week.b2,
                    borderColor: '#3182ce',
                    backgroundColor: 'rgba(49, 130, 206, 0.04)',
                    tension: 0.2,
                    fill: true
                },
                {
                    label: 'Barometer 3 (Pipeline)',
                    data: filterData.week.b3,
                    borderColor: '#e53e3e',
                    backgroundColor: 'rgba(229, 62, 62, 0.04)',
                    tension: 0.2,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    title: { display: true, text: 'Level / Pressure (cm)' },
                    beginAtZero: false
                }
            }
        }
    });

    // --- DYNAMIC FILTER HANDLER FUNCTION ---
    function updateFilter(range, buttonElement) {
        // Alisin ang active status sa lahat ng buttons at ibigay sa pinindot
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        buttonElement.classList.add('active');

        // Baguhin ang ipinapakitang data sa graph base sa pinindot
        historyChart.data.labels = filterData[range].labels;
        historyChart.data.datasets[0].data = filterData[range].b1;
        historyChart.data.datasets[1].data = filterData[range].b2;
        historyChart.data.datasets[2].data = filterData[range].b3;
        
        // I-render/animate muli ang chart gamit ang bagong data
        historyChart.update();
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>