<?php
// pages/history.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$current_user = getCurrentUser($conn);
$page_title   = 'Water Level History';
include __DIR__ . '/../includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .history-page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }
    .history-page-header h2 {
        color: #2d3748;
        font-size: 24px;
        font-weight: 700;
        margin: 0;
    }
    .history-page-header p {
        color: #718096;
        font-size: 14px;
        margin: 5px 0 0;
    }
    .filter-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border: 1px solid #eef2f5;
        padding: 20px 25px;
        margin-bottom: 25px;
    }
    .filter-card-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    .filter-title {
        font-size: 14px;
        font-weight: 600;
        color: #2d3748;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .filter-title i { color: #3182ce; }
    .time-filter-group {
        display: flex;
        gap: 6px;
        background: #f1f5f9;
        padding: 5px;
        border-radius: 10px;
    }
    .time-filter-btn {
        border: none;
        background: transparent;
        padding: 8px 18px;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        cursor: pointer;
        border-radius: 8px;
        transition: all 0.2s ease;
        font-family: 'Poppins', sans-serif;
    }
    .time-filter-btn:hover { background: #e2e8f0; color: #1e293b; }
    .time-filter-btn.active {
        background: #3182ce;
        color: white;
        box-shadow: 0 2px 6px rgba(49, 130, 206, 0.3);
    }
    .date-picker-row {
        display: none;
        gap: 12px;
        align-items: center;
        margin-top: 15px;
        flex-wrap: wrap;
    }
    .date-picker-row label {
        font-size: 13px;
        font-weight: 600;
        color: #2d3748;
    }
    .date-picker-row input[type="date"] {
        padding: 8px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 13px;
        font-family: 'Poppins', sans-serif;
        color: #2d3748;
        background: #f8fafc;
    }
    .apply-date-btn {
        background: #3182ce;
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        font-family: 'Poppins', sans-serif;
    }
    .apply-date-btn:hover { background: #2c5282; }
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }
    .stat-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border: 1px solid #eef2f5;
        padding: 20px 22px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .stat-icon.blue { background: rgba(49, 130, 206, 0.1); color: #3182ce; }
    .stat-icon.green { background: rgba(56, 161, 105, 0.1); color: #38a169; }
    .stat-icon.purple { background: rgba(128, 90, 213, 0.1); color: #805ad5; }
    .stat-info h4 {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #718096;
        margin: 0 0 4px;
        font-weight: 600;
    }
    .stat-info .stat-value {
        font-size: 22px;
        font-weight: 700;
        color: #2d3748;
        line-height: 1;
    }
    .stat-info .stat-unit {
        font-size: 11px;
        color: #718096;
        font-weight: 500;
        margin-left: 2px;
    }
    .chart-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border: 1px solid #eef2f5;
        padding: 25px;
        margin-bottom: 25px;
    }
    .chart-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 15px;
    }
    .chart-card-header h3 {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
        color: #2d3748;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .chart-legend {
        display: flex;
        gap: 15px;
    }
    .legend-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #718096;
        font-weight: 500;
    }
    .legend-dot { width: 10px; height: 10px; border-radius: 50%; }
    .legend-dot.blue { background: #3182ce; }
    .legend-dot.green { background: #38a169; }
    .chart-container {
        position: relative;
        width: 100%;
        height: 350px;
    }
    .chart-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }
    .mini-chart-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border: 1px solid #eef2f5;
        padding: 20px;
    }
    .mini-chart-card h3 {
        margin: 0 0 5px;
        font-size: 14px;
        font-weight: 600;
        color: #2d3748;
    }
    .mini-chart-card .subtitle {
        font-size: 12px;
        color: #718096;
        margin-bottom: 15px;
    }
    .mini-chart-container {
        position: relative;
        width: 100%;
        height: 200px;
    }
    .data-table-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border: 1px solid #eef2f5;
        padding: 25px;
        overflow-x: auto;
    }
    .data-table-card h3 {
        margin: 0 0 15px;
        font-size: 15px;
        font-weight: 600;
        color: #2d3748;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    .data-table thead th {
        background: #f8fafc;
        padding: 12px 15px;
        text-align: left;
        font-weight: 600;
        color: #475569;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }
    .data-table tbody td {
        padding: 10px 15px;
        border-bottom: 1px solid #f1f5f9;
        color: #2d3748;
        white-space: nowrap;
    }
    .data-table tbody tr:hover { background: #f8fafc; }
    .level-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .level-badge.normal { background: #e8f5e9; color: #2e7d32; }
    .level-badge.warning { background: #fff3e0; color: #e65100; }
    .level-badge.critical { background: #ffebee; color: #c62828; }
    .loading-overlay {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px;
        color: #718096;
        font-size: 14px;
        gap: 10px;
    }
    .loading-spinner {
        width: 24px;
        height: 24px;
        border: 3px solid #e2e8f0;
        border-top-color: #3182ce;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .no-data {
        text-align: center;
        padding: 40px;
        color: #718096;
    }
    .no-data i { font-size: 40px; margin-bottom: 10px; display: block; color: #cbd5e1; }
    @media (max-width: 768px) {
        .time-filter-group { flex-wrap: wrap; }
        .time-filter-btn { padding: 6px 12px; font-size: 12px; }
        .date-picker-row { flex-direction: column; align-items: flex-start; }
        .chart-container { height: 250px; }
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

        <div class="history-page-header">
            <div>
                <h2><i class="fas fa-water" style="color: #3182ce;"></i> Water Level History</h2>
                <p>Historical trends from device <strong>RF01</strong></p>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="filter-card">
            <div class="filter-card-top">
                <div class="filter-title">
                    <i class="fas fa-filter"></i> Filter by Time Range
                </div>
                <div class="time-filter-group">
                    <button class="time-filter-btn" onclick="setFilter('hour', this)">Hour</button>
                    <button class="time-filter-btn" onclick="setFilter('day', this)">Day</button>
                    <button class="time-filter-btn" onclick="setFilter('week', this)">Week</button>
                    <button class="time-filter-btn active" onclick="setFilter('month', this)">Month</button>
                    <button class="time-filter-btn" onclick="setFilter('year', this)">Year</button>
                </div>
            </div>

            <div class="date-picker-row" id="datePickerRow">
                <label>Select Date:</label>
                <input type="date" id="dateSelect" value="<?php echo date('Y-m-d'); ?>">
                <button class="apply-date-btn" onclick="applyDateFilter()">
                    <i class="fas fa-search"></i> Apply
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-water"></i></div>
                <div class="stat-info">
                    <h4>Avg Water Level</h4>
                    <div>
                        <span class="stat-value" id="statLevelAvg">--</span>
                        <span class="stat-unit">cm</span>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-battery-three-quarters"></i></div>
                <div class="stat-info">
                    <h4>Avg Battery</h4>
                    <div>
                        <span class="stat-value" id="statBattAvg">--</span>
                        <span class="stat-unit">V</span>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-chart-bar"></i></div>
                <div class="stat-info">
                    <h4>Total Readings</h4>
                    <div>
                        <span class="stat-value" id="statCount">--</span>
                        <span class="stat-unit">records</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Combined History Chart -->
        <div class="chart-card" id="combinedChartCard">
            <div class="chart-card-header">
                <h3><i class="fas fa-chart-line" style="color: #3182ce;"></i> Water Level Trends</h3>
                <div class="chart-legend">
                    <div class="legend-item"><div class="legend-dot blue"></div><span>Water Level</span></div>
                    <div class="legend-item"><div class="legend-dot green"></div><span>Battery Voltage</span></div>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="historyChart"></canvas>
            </div>
        </div>

        <!-- Individual Charts -->
        <div class="chart-row">
            <div class="mini-chart-card">
                <h3>Water Level</h3>
                <div class="subtitle">Min / Avg / Max (cm)</div>
                <div class="mini-chart-container">
                    <canvas id="chartLevel"></canvas>
                </div>
            </div>

            <div class="mini-chart-card">
                <h3>Battery Voltage</h3>
                <div class="subtitle">Avg voltage over time</div>
                <div class="mini-chart-container">
                    <canvas id="chartBattery"></canvas>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="data-table-card">
            <h3><i class="fas fa-table" style="color: #3182ce;"></i> Detailed Readings</h3>
            <div id="dataTableContainer">
                <div class="loading-overlay">
                    <div class="loading-spinner"></div>
                    Loading data...
                </div>
            </div>
        </div>

    </main>
</div>

<script>
    let currentFilter = 'month';
    let historyChart = null;
    let chartLevel = null;
    let chartBattery = null;

    const API_URL = '<?php echo BASE_URL; ?>/api/history_data.php';

    document.addEventListener('DOMContentLoaded', function() {
        fetchData('month');
    });

    function setFilter(filter, btn) {
        document.querySelectorAll('.time-filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentFilter = filter;

        const dateRow = document.getElementById('datePickerRow');
        dateRow.style.display = (filter === 'hour') ? 'flex' : 'none';

        fetchData(filter);
    }

    function applyDateFilter() {
        fetchData(currentFilter);
    }

    async function fetchData(filter) {
        const dateVal = document.getElementById('dateSelect').value;
        let url = API_URL + '?filter=' + filter;

        if (filter === 'hour') {
            url += '&day=' + dateVal;
        }

        document.getElementById('combinedChartCard').style.opacity = '0.6';

        try {
            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                updateStats(data);
                updateHistoryChart(data);
                updateMiniCharts(data);
                updateDataTable(data);
            } else {
                showNoData(data.error || 'Failed to load data');
            }
        } catch (error) {
            console.error('Fetch error:', error);
            showNoData('Connection error. Please try again.');
        }

        document.getElementById('combinedChartCard').style.opacity = '1';
    }

    function updateStats(data) {
        const calcAvg = (arr) => {
            if (!arr || arr.length === 0) return '--';
            const sum = arr.reduce((a, b) => a + b.avg, 0);
            return (sum / arr.length).toFixed(1);
        };
        const calcBattAvg = (arr) => {
            if (!arr || arr.length === 0) return '--';
            const sum = arr.reduce((a, b) => a + b.avg, 0);
            return (sum / arr.length).toFixed(2);
        };

        document.getElementById('statLevelAvg').textContent = calcAvg(data.water_level);
        document.getElementById('statBattAvg').textContent = calcBattAvg(data.battery);
        document.getElementById('statCount').textContent = data.labels ? data.labels.length : 0;
    }

    function updateHistoryChart(data) {
        const ctx = document.getElementById('historyChart').getContext('2d');
        if (historyChart) historyChart.destroy();

        // Create gradients
        const h = 350;
        const gradLevel = ctx.createLinearGradient(0, 0, 0, h);
        gradLevel.addColorStop(0, 'rgba(49, 130, 206, 0.25)');
        gradLevel.addColorStop(1, 'rgba(49, 130, 206, 0.01)');

        const gradBatt = ctx.createLinearGradient(0, 0, 0, h);
        gradBatt.addColorStop(0, 'rgba(56, 161, 105, 0.25)');
        gradBatt.addColorStop(1, 'rgba(56, 161, 105, 0.01)');

        historyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Water Level (cm)',
                        data: data.water_level.map(d => d.avg),
                        borderColor: '#3182ce',
                        backgroundColor: gradLevel,
                        tension: 0.3,
                        fill: true,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Battery (V)',
                        data: data.battery.map(d => d.avg),
                        borderColor: '#38a169',
                        backgroundColor: gradBatt,
                        tension: 0.3,
                        fill: true,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleFont: { family: 'Poppins', size: 13 },
                        bodyFont: { family: 'Poppins', size: 12 },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const idx = context.dataIndex;
                                if (context.datasetIndex === 0) {
                                    const d = data.water_level[idx];
                                    return [
                                        'Avg: ' + d.avg + ' cm',
                                        'Min: ' + d.min + ' cm',
                                        'Max: ' + d.max + ' cm'
                                    ];
                                } else {
                                    const d = data.battery[idx];
                                    return [
                                        'Avg: ' + d.avg + ' V',
                                        'Min: ' + d.min + ' V'
                                    ];
                                }
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Water Level (cm)',
                            font: { family: 'Poppins', weight: '600' }
                        },
                        grid: { color: 'rgba(0,0,0,0.04)' }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Battery (V)',
                            font: { family: 'Poppins', weight: '600' }
                        },
                        grid: { drawOnChartArea: false },
                        min: 10,
                        max: 14
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Poppins', size: 11 } }
                    }
                }
            }
        });
    }

    function updateMiniCharts(data) {
        const ctx1 = document.getElementById('chartLevel').getContext('2d');
        const ctx2 = document.getElementById('chartBattery').getContext('2d');

        if (chartLevel) chartLevel.destroy();
        if (chartBattery) chartBattery.destroy();

        chartLevel = new Chart(ctx1, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Water Level',
                    data: data.water_level.map(d => d.avg),
                    borderColor: '#3182ce',
                    backgroundColor: 'rgba(49, 130, 206, 0.1)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 2,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { display: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 } } },
                    x: { display: false }
                }
            }
        });

        chartBattery = new Chart(ctx2, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Battery',
                    data: data.battery.map(d => d.avg),
                    borderColor: '#38a169',
                    backgroundColor: 'rgba(56, 161, 105, 0.1)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 2,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { display: true, min: 10, max: 14, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 } } },
                    x: { display: false }
                }
            }
        });
    }

    function updateDataTable(data) {
        const container = document.getElementById('dataTableContainer');

        if (!data.labels || data.labels.length === 0) {
            container.innerHTML = '<div class="no-data"><i class="fas fa-inbox"></i><p>No data available for the selected filter.</p></div>';
            return;
        }

        let html = '<table class="data-table"><thead><tr>' +
            '<th>Period</th>' +
            '<th>Avg Level (cm)</th>' +
            '<th>Level Range</th>' +
            '<th>Avg Battery (V)</th>' +
            '<th>Readings</th>' +
            '<th>Status</th>' +
            '</tr></thead><tbody>';

        for (let i = 0; i < data.labels.length; i++) {
            const wl = data.water_level[i];
            const batt = data.battery[i];

            let statusClass = 'normal';
            let statusText = 'Normal';
            if (wl.avg > 18) {
                statusClass = 'critical';
                statusText = 'High Water';
            } else if (wl.avg < 3) {
                statusClass = 'warning';
                statusText = 'Low Water';
            }

            html += '<tr>' +
                '<td><strong>' + data.labels[i] + '</strong></td>' +
                '<td>' + wl.avg + '</td>' +
                '<td>' + wl.min + ' - ' + wl.max + '</td>' +
                '<td>' + batt.avg + '</td>' +
                '<td>' + data.water_level.length + '</td>' +
                '<td><span class="level-badge ' + statusClass + '">' + statusText + '</span></td>' +
                '</tr>';
        }

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    function showNoData(message) {
        document.getElementById('dataTableContainer').innerHTML =
            '<div class="no-data"><i class="fas fa-exclamation-circle"></i><p>' + message + '</p></div>';
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
