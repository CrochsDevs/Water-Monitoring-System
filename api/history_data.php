<?php
/**
 * api/history_data.php
 * Returns filtered water level reading history as JSON.
 * Used by the dashboard charts (Chart.js).
 *
 * This replaces the old barometer-pressure approach with
 * actual water level data from the Arduino sensors.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
requireLogin();

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'week';
$device = isset($_GET['device']) ? trim($_GET['device']) : 'RF01';

$where  = "WHERE device_id = '" . $conn->real_escape_string($device) . "'";
$group  = '';
$labels = [];
$data_level = [];
$data_batt  = [];

switch ($filter) {
    case 'hour':
        $day   = isset($_GET['day']) ? $_GET['day'] : date('Y-m-d');
        $where .= " AND received_at BETWEEN '{$day} 00:00:00' AND '{$day} 23:59:59'";
        $group  = "GROUP BY HOUR(received_at)";
        $label_fn = "HOUR(received_at)";
        break;

    case 'day':
        $where .= " AND received_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $group  = "GROUP BY DATE(received_at)";
        $label_fn = "DATE(received_at)";
        break;

    case 'week':
        $where .= " AND received_at >= DATE_SUB(NOW(), INTERVAL 84 DAY)";
        $group  = "GROUP BY YEARWEEK(received_at, 1)";
        $label_fn = "YEARWEEK(received_at, 1)";
        break;

    case 'month':
        $where .= " AND received_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
        $group  = "GROUP BY YEAR(received_at), MONTH(received_at)";
        $label_fn = "DATE_FORMAT(received_at, '%Y-%m')";
        break;

    case 'year':
        $group  = "GROUP BY YEAR(received_at)";
        $label_fn = "YEAR(received_at)";
        break;

    default:
        $where .= " AND received_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $group  = "GROUP BY YEARWEEK(received_at, 1)";
        $label_fn = "YEARWEEK(received_at, 1)";
        break;
}

$sql = "SELECT 
            {$label_fn} as label_val,
            AVG(water_level_cm) as avg_level,
            MIN(water_level_cm) as min_level,
            MAX(water_level_cm) as max_level,
            AVG(battery_v) as avg_battery,
            MIN(battery_v) as min_battery,
            COUNT(*) as readings
        FROM water_level_readings
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
            $labels[] = sprintf('%02d:00', intval($row['label_val']));
            break;
        case 'day':
            $labels[] = date('M d', strtotime($row['label_val']));
            break;
        case 'week':
            $yw = strval($row['label_val']);
            $y = substr($yw, 0, 4);
            $w = substr($yw, 4);
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

    $data_level[] = [
        'avg' => round(floatval($row['avg_level']), 1),
        'min' => round(floatval($row['min_level']), 1),
        'max' => round(floatval($row['max_level']), 1)
    ];
    $data_batt[] = [
        'avg' => round(floatval($row['avg_battery']), 2),
        'min' => round(floatval($row['min_battery']), 2)
    ];
}

echo json_encode([
    'success'    => true,
    'filter'     => $filter,
    'device'     => $device,
    'labels'     => $labels,
    'water_level' => $data_level,
    'battery'    => $data_batt,
    'count'      => count($labels)
]);
