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
    /* ========================================
       HISTORY PAGE STYLES
       ======================================== */

    .history-page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .history-page-header h2 {
        color: var(--clsu-green);
        font-size: 24px;
        font-weight: 700;
        margin: 0;
    }

    .history-page-header p {
        color: var(--text-light);
        font-size: 14px;
        margin: 5px 0 0;
    }

    /* Filter Card */
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
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .filter-title i {
        color: var(--clsu-green);
    }

    /* Time Range Buttons */
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

    .time-filter-btn:hover {
        background: #e2e8f0;
        color: #1e293b;
    }

    .time-filter-btn.active {
        background: var(--clsu-green);
        color: white;
        box-shadow: 0 2px 6px rgba(0, 104, 55, 0.3);
    }

    /* Date Picker Row */
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
        color: var(--text-dark);
    }

    .date-picker-row input[type="date"] {
        padding: 8px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 13px;
        font-family: 'Poppins', sans-serif;
        color: var(--text-dark);
        background: #f8fafc;
        transition: border-color 0.2s;
    }

    .date-picker-row input[type="date"]:focus {
        outline: none;
        border-color: var(--clsu-green);
        background: white;
    }

    .apply-date-btn {
        background: var(--clsu-green);
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        font-family: 'Poppins', sans-serif;
        transition: background 0.2s;
    }

    .apply-date-btn:hover {
        background: var(--clsu-green-dark);
    }

    /* Stats Cards Row */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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

    .stat-icon.green { background: rgba(76, 175, 80, 0.1); color: #4CAF50; }
    .stat-icon.blue { background: rgba(49, 130, 206, 0.1); color: #3182ce; }
    .stat-icon.red { background: rgba(229, 62, 62, 0.1); color: #e53e3e; }

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

    /* Chart Card */
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
        color: var(--text-dark);
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

    .legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }

    .legend-dot.green { background: #4CAF50; }
    .legend-dot.blue { background: #3182ce; }
    .legend-dot.red { background: #e53e3e; }

    .chart-container {
        position: relative;
        width: 100%;
        height: 350px;
    }

    /* Individual Barometer Charts Row */
    .barometer-charts-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }

    .barometer-chart-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border: 1px solid #eef2f5;
        padding: 20px;
    }

    .barometer-chart-card h3 {
        margin: 0 0 5px;
        font-size: 14px;
        font-weight: 600;
        color: var(--text-dark);
    }

    .barometer-chart-card .subtitle {
        font-size: 12px;
        color: #718096;
        margin-bottom: 15px;
    }

    .barometer-chart-container {
        position: relative;
        width: 100%;
        height: 200px;
    }

    /* Data Table */
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
        color: var(--text-dark);
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

    .data-table tbody tr:hover {
        background: #f8fafc;
    }

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

    /* Loading State */
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
        border-top-color: var(--clsu-green);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* No Data State */
    .no-data {
        text-align: center;
        padding: 40px;
        color: #718096;
    }

    .no-data i {
        font-size: 40px;
        margin-bottom: 10px;
        display: block;
        color: #cbd5e1;
    }

    /* Responsive */
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

        <!-- Page Header -->
        <div class="history-page-header">
            <div>
                <h2><i class="fas fa-water"></i> Water Level History</h2>
                <p>Historical trends and analysis from all monitoring stations</p>
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
                <div class="stat-icon green">
                    <i class="fas fa-broadcast-tower"></i>
                </div>
                <div class="stat-info">
                    <h4>Station Pressure</h4>
                    <div>
                        <span class="stat-value" id="statStationVal">--</span>
                        <span class="stat-unit">cm</span>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-database"></i>
                </div>
                <div class="stat-info">
                    <h4>Tank Pressure</h4>
                    <div>
                        <span class="stat-value" id="statTankVal">--</span>
                        <span class="stat-unit">cm</span>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="fas fa-water"></i>
                </div>
                <div class="stat-info">
                    <h4>Pipeline Pressure</h4>
                    <div>
                        <span class="stat-value" id="statPipelineVal">--</span>
                        <span class="stat-unit">cm</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Combined History Chart -->
        <div class="chart-card" id="combinedChartCard">
            <div class="chart-card-header">
                <h3><i class="fas fa-chart-line" style="color: var(--clsu-green);"></i> Historical Water Level Trends</h3>
                <div class="chart-legend">
                    <div class="legend-item">
                        <div class="legend-dot green"></div>
                        <span>Station</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot blue"></div>
                        <span>Tank</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot red"></div>
                        <span>Pipeline</span>
                    </div>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="historyChart"></canvas>
            </div>
        </div>

        <!-- Individual Barometer Charts -->
        <div class="barometer-charts-row">
            <div class="barometer-chart-card">
                <h3>Barometer 1</h3>
                <div class="subtitle">Station Pressure</div>
                <div class="barometer-chart-container">
                    <canvas id="chartBarometer1"></canvas>
                </div>
            </div>

            <div class="barometer-chart-card">
                <h3>Barometer 2</h3>
                <div class="subtitle">Tank Pressure</div>
                <div class="barometer-chart-container">
                    <canvas id="chartBarometer2"></canvas>
                </div>
            </div>

            <div class="barometer-chart-card">
                <h3>Barometer 3</h3>
                <div class="subtitle">Pipeline Pressure</div>
                <div class="barometer-chart-container">
                    <canvas id="chartBarometer3"></canvas>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="data-table-card">
            <h3><i class="fas fa-table" style="color: var(--clsu-green);"></i> Detailed Readings</h3>
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
    // ========================================
    // HISTORY PAGE JAVASCRIPT
    // ========================================

    let currentFilter = 'month';
    let historyChart = null;
    let chartB1 = null;
    let chartB2 = null;
    let chartB3 = null;

    const API_URL = '<?php echo BASE_URL; ?>/api/history_data.php';

    const COLORS = {
        green:  { border: '#4CAF50', bg: 'rgba(76, 175, 80, 0.1)' },
        blue:   { border: '#3182ce', bg: 'rgba(49, 130, 206, 0.1)' },
        red:    { border: '#e53e3e', bg: 'rgba(229, 62, 62, 0.1)' }
    };

    document.addEventListener('DOMContentLoaded', function() {
        fetchData('month');
    });

    function setFilter(filter, btn) {
        document.querySelectorAll('.time-filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentFilter = filter;

        const dateRow = document.getElementById('datePickerRow');
        dateRow.style.display = (filter === 'hour' || filter === 'day') ? 'flex' : 'none';

        fetchData(filter);
    }

    function applyDateFilter() {
        fetchData(currentFilter);
    }

    async function fetchData(filter) {
        const dateVal = document.getElementById('dateSelect').value;
        let url = API_URL + '?filter=' + filter;

        if (filter === 'hour' || filter === 'day') {
            url += '&day=' + dateVal;
        }

        document.getElementById('combinedChartCard').style.opacity = '0.6';

        try {
            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                updateStats(data);
                updateHistoryChart(data);
                updateBarometerCharts(data);
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
        document.getElementById('statStationVal').textContent = calcAvg(data.barometer1);
        document.getElementById('statTankVal').textContent = calcAvg(data.barometer2);
        document.getElementById('statPipelineVal').textContent = calcAvg(data.barometer3);
    }

    function updateHistoryChart(data) {
        const ctx = document.getElementById('historyChart').getContext('2d');

        if (historyChart) historyChart.destroy();

        const gradients = createGradients(ctx);

        historyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Station Pressure',
                        data: data.barometer1.map(d => d.avg),
                        borderColor: COLORS.green.border,
                        backgroundColor: gradients.green,
                        tension: 0.3,
                        fill: true,
                        pointRadius: 3,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Tank Pressure',
                        data: data.barometer2.map(d => d.avg),
                        borderColor: COLORS.blue.border,
                        backgroundColor: gradients.blue,
                        tension: 0.3,
                        fill: true,
                        pointRadius: 3,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Pipeline Pressure',
                        data: data.barometer3.map(d => d.avg),
                        borderColor: COLORS.red.border,
                        backgroundColor: gradients.red,
                        tension: 0.3,
                        fill: true,
                        pointRadius: 3,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
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
                                const datasetIdx = context.datasetIndex;
                                const key = ['barometer1', 'barometer2', 'barometer3'][datasetIdx];
                                const d = data[key][idx];
                                return [
                                    'Avg: ' + d.avg + ' cm',
                                    'Min: ' + d.min + ' cm',
                                    'Max: ' + d.max + ' cm'
                                ];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        title: {
                            display: true,
                            text: 'Water Level (cm)',
                            font: { family: 'Poppins', weight: '600' }
                        },
                        grid: { color: 'rgba(0,0,0,0.04)' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Poppins', size: 11 } }
                    }
                }
            }
        });
    }

    function updateBarometerCharts(data) {
        const configs = [
            { id: 'chartBarometer1', data: data.barometer1, color: COLORS.green, label: 'Station' },
            { id: 'chartBarometer2', data: data.barometer2, color: COLORS.blue, label: 'Tank' },
            { id: 'chartBarometer3', data: data.barometer3, color: COLORS.red, label: 'Pipeline' }
        ];

        const charts = [chartB1, chartB2, chartB3];

        configs.forEach((cfg, i) => {
            const ctx = document.getElementById(cfg.id).getContext('2d');
            if (charts[i]) charts[i].destroy();

            charts[i] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: cfg.label,
                        data: cfg.data.map(d => d.avg),
                        borderColor: cfg.color.border,
                        backgroundColor: cfg.color.bg,
                        tension: 0.3,
                        fill: true,
                        pointRadius: 2,
                        pointHoverRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const d = cfg.data[context.dataIndex];
                                    return 'Avg: ' + d.avg + ' cm (Min: ' + d.min + ', Max: ' + d.max + ')';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            display: true,
                            grid: { color: 'rgba(0,0,0,0.04)' },
                            ticks: { font: { size: 10 } }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10 }, maxTicksLimit: 8 }
                        }
                    }
                }
            });
        });

        chartB1 = charts[0];
        chartB2 = charts[1];
        chartB3 = charts[2];
    }

    function updateDataTable(data) {
        const container = document.getElementById('dataTableContainer');

        if (!data.labels || data.labels.length === 0) {
            container.innerHTML = '<div class="no-data"><i class="fas fa-inbox"></i><p>No data available for the selected filter.</p></div>';
            return;
        }

        let html = '<table class="data-table"><thead><tr>' +
            '<th>Period</th>' +
            '<th>Station Avg (cm)</th>' +
            '<th>Station Range</th>' +
            '<th>Tank Avg (cm)</th>' +
            '<th>Tank Range</th>' +
            '<th>Pipeline Avg (cm)</th>' +
            '<th>Pipeline Range</th>' +
            '<th>Status</th>' +
            '</tr></thead><tbody>';

        for (let i = 0; i < data.labels.length; i++) {
            const b1 = data.barometer1[i];
            const b2 = data.barometer2[i];
            const b3 = data.barometer3[i];

            const avgAll = (b1.avg + b2.avg + b3.avg) / 3;
            let statusClass = 'normal';
            let statusText = 'Normal';
            if (avgAll > 120) {
                statusClass = 'critical';
                statusText = 'Critical';
            } else if (avgAll > 110) {
                statusClass = 'warning';
                statusText = 'Warning';
            }

            html += '<tr>' +
                '<td><strong>' + data.labels[i] + '</strong></td>' +
                '<td>' + b1.avg + '</td>' +
                '<td>' + b1.min + ' - ' + b1.max + '</td>' +
                '<td>' + b2.avg + '</td>' +
                '<td>' + b2.min + ' - ' + b2.max + '</td>' +
                '<td>' + b3.avg + '</td>' +
                '<td>' + b3.min + ' - ' + b3.max + '</td>' +
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

    function createGradients(ctx) {
        const h = 350;
        const g1 = ctx.createLinearGradient(0, 0, 0, h);
        g1.addColorStop(0, 'rgba(76, 175, 80, 0.25)');
        g1.addColorStop(1, 'rgba(76, 175, 80, 0.01)');

        const g2 = ctx.createLinearGradient(0, 0, 0, h);
        g2.addColorStop(0, 'rgba(49, 130, 206, 0.25)');
        g2.addColorStop(1, 'rgba(49, 130, 206, 0.01)');

        const g3 = ctx.createLinearGradient(0, 0, 0, h);
        g3.addColorStop(0, 'rgba(229, 62, 62, 0.25)');
        g3.addColorStop(1, 'rgba(229, 62, 62, 0.01)');

        return { green: g1, blue: g2, red: g3 };
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
