<?php
// api/history_data.php
// Returns filtered water level history data as JSON

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
requireLogin();

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'week';
$year   = isset($_GET['year'])   ? intval($_GET['year'])  : intval(date('Y'));
$month  = isset($_GET['month'])  ? intval($_GET['month']) : intval(date('n'));
$week   = isset($_GET['week'])   ? intval($_GET['week'])  : 0;
$day    = isset($_GET['day'])    ? $_GET['day']           : date('Y-m-d');

$where  = '';
$group  = '';
$labels = [];
$data1  = [];
$data2  = [];
$data3  = [];

switch ($filter) {
    case 'hour':
        // Specific day, grouped by hour
        $start = $day . ' 00:00:00';
        $end   = $day . ' 23:59:59';
        $where = "WHERE recorded_at BETWEEN '{$start}' AND '{$end}'";
        $group = "GROUP BY HOUR(recorded_at)";
        $label_fn = "HOUR(recorded_at)";
        break;

    case 'day':
        // Last 30 days, grouped by day
        $start = date('Y-m-d', strtotime('-30 days')) . ' 00:00:00';
        $end   = date('Y-m-d') . ' 23:59:59';
        $where = "WHERE recorded_at BETWEEN '{$start}' AND '{$end}'";
        $group = "GROUP BY DATE(recorded_at)";
        $label_fn = "DATE(recorded_at)";
        break;

    case 'week':
        // Last 12 weeks, grouped by week
        $start = date('Y-m-d', strtotime('-84 days')) . ' 00:00:00';
        $end   = date('Y-m-d') . ' 23:59:59';
        $where = "WHERE recorded_at BETWEEN '{$start}' AND '{$end}'";
        $group = "GROUP BY YEARWEEK(recorded_at, 1)";
        $label_fn = "YEARWEEK(recorded_at, 1)";
        break;

    case 'month':
        // Last 12 months, grouped by month
        $start = date('Y-m-d', strtotime('-365 days')) . ' 00:00:00';
        $end   = date('Y-m-d') . ' 23:59:59';
        $where = "WHERE recorded_at BETWEEN '{$start}' AND '{$end}'";
        $group = "GROUP BY YEAR(recorded_at), MONTH(recorded_at)";
        $label_fn = "DATE_FORMAT(recorded_at, '%Y-%m')";
        break;

    case 'year':
        // All years, grouped by year
        $where = "WHERE 1=1";
        $group = "GROUP BY YEAR(recorded_at)";
        $label_fn = "YEAR(recorded_at)";
        break;

    default:
        $start = date('Y-m-d', strtotime('-7 days')) . ' 00:00:00';
        $end   = date('Y-m-d') . ' 23:59:59';
        $where = "WHERE recorded_at BETWEEN '{$start}' AND '{$end}'";
        $group = "GROUP BY YEARWEEK(recorded_at, 1)";
        $label_fn = "YEARWEEK(recorded_at, 1)";
        break;
}

$sql = "SELECT 
            {$label_fn} as label_val,
            AVG(station_pressure) as avg_station,
            AVG(tank_pressure) as avg_tank,
            AVG(pipeline_pressure) as avg_pipeline,
            MIN(station_pressure) as min_station,
            MAX(station_pressure) as max_station,
            MIN(tank_pressure) as min_tank,
            MAX(tank_pressure) as max_tank,
            MIN(pipeline_pressure) as min_pipeline,
            MAX(pipeline_pressure) as max_pipeline,
            COUNT(*) as readings
        FROM water_level_history
        {$where}
        {$group}
        ORDER BY label_val ASC";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['error' => 'Query failed: ' . $conn->error]);
    exit();
}

while ($row = $result->fetch_assoc()) {
    // Format label based on filter type
    switch ($filter) {
        case 'hour':
            $hour = intval($row['label_val']);
            $labels[] = sprintf('%02d:00', $hour);
            break;
        case 'day':
            $labels[] = date('M d', strtotime($row['label_val']));
            break;
        case 'week':
            $yw = $row['label_val'];
            $yw_str = strval($yw);
            $y = substr($yw_str, 0, 4);
            $w = substr($yw_str, 4);
            $labels[] = 'W' . $w;
            break;
        case 'month':
            $labels[] = date('M Y', strtotime($row['label_val'] . '-01'));
            break;
        case 'year':
            $labels[] = $row['label_val'];
            break;
        default:
            $labels[] = $row['label_val'];
    }

    $data1[] = [
        'avg' => round(floatval($row['avg_station']), 2),
        'min' => round(floatval($row['min_station']), 2),
        'max' => round(floatval($row['max_station']), 2)
    ];
    $data2[] = [
        'avg' => round(floatval($row['avg_tank']), 2),
        'min' => round(floatval($row['min_tank']), 2),
        'max' => round(floatval($row['max_tank']), 2)
    ];
    $data3[] = [
        'avg' => round(floatval($row['avg_pipeline']), 2),
        'min' => round(floatval($row['min_pipeline']), 2),
        'max' => round(floatval($row['max_pipeline']), 2)
    ];
}

echo json_encode([
    'success' => true,
    'filter'  => $filter,
    'labels'  => $labels,
    'barometer1' => $data1,
    'barometer2' => $data2,
    'barometer3' => $data3,
    'count'   => count($labels)
]);
