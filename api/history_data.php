<?php
/**
 * api/history_data.php
 * Returns filtered water level reading history as JSON.
 * Used by the dashboard charts (Chart.js).
 * Now returns data for all 3 devices (RF01, RF02, RF03).
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
requireLogin();

$filter  = isset($_GET['filter']) ? $_GET['filter'] : 'week';
$devices = ['RF01', 'RF02', 'RF03'];

$where_base = '';
$group  = '';
$labels = [];
$all_device_data = [];

switch ($filter) {
    case 'hour':
        $day   = isset($_GET['day']) ? $_GET['day'] : date('Y-m-d');
        $where_base = "AND received_at BETWEEN '{$day} 00:00:00' AND '{$day} 23:59:59'";
        $group  = "GROUP BY DATE_FORMAT(received_at, '%Y-%m-%d %H:00:00')";
        $label_fn = "DATE_FORMAT(received_at, '%Y-%m-%d %H:00:00')";
        break;

    case 'day':
        $where_base = "AND received_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $group  = "GROUP BY DATE(received_at)";
        $label_fn = "DATE(received_at)";
        break;

    case 'week':
        $where_base = "AND received_at >= DATE_SUB(NOW(), INTERVAL 84 DAY)";
        $group  = "GROUP BY YEARWEEK(received_at, 1)";
        $label_fn = "YEARWEEK(received_at, 1)";
        break;

    case 'month':
        $where_base = "AND received_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
        $group  = "GROUP BY YEAR(received_at), MONTH(received_at)";
        $label_fn = "DATE_FORMAT(received_at, '%Y-%m')";
        break;

    case 'year':
        $group  = "GROUP BY YEAR(received_at)";
        $label_fn = "YEAR(received_at)";
        break;

    default:
        $where_base = "AND received_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $group  = "GROUP BY YEARWEEK(received_at, 1)";
        $label_fn = "YEARWEEK(received_at, 1)";
        break;
}

// Fetch data for each device
foreach ($devices as $device) {
    $sql = "SELECT 
                {$label_fn} as label_val,
                AVG(water_level_cm) as avg_level,
                MIN(water_level_cm) as min_level,
                MAX(water_level_cm) as max_level,
                COUNT(*) as readings
            FROM water_level_readings
            WHERE device_id = '" . $conn->real_escape_string($device) . "' {$where_base}
            {$group}
            ORDER BY label_val ASC";

    $result = $conn->query($sql);

    if (!$result) {
        echo json_encode(['error' => 'Query failed: ' . $conn->error]);
        exit();
    }

    $data_points = [];
    while ($row = $result->fetch_assoc()) {
        $data_points[] = [
            'label' => $row['label_val'],
            'avg'   => round(floatval($row['avg_level']), 1),
            'min'   => round(floatval($row['min_level']), 1),
            'max'   => round(floatval($row['max_level']), 1)
        ];
    }
    $all_device_data[$device] = $data_points;
}

// Build labels from the device with the most data points
$label_map = [];
foreach ($all_device_data as $did => $points) {
    foreach ($points as $p) {
        $label_map[$p['label']] = true;
    }
}
$label_keys = array_keys($label_map);
sort($label_keys);

// Format labels based on filter
$labels = [];
foreach ($label_keys as $lk) {
    switch ($filter) {
        case 'hour':
            $labels[] = date('H:i', strtotime($lk));
            break;
        case 'day':
            $labels[] = date('M d', strtotime($lk));
            break;
        case 'week':
            $yw = strval($lk);
            $y = substr($yw, 0, 4);
            $w = substr($yw, 4);
            $labels[] = 'W' . $w;
            break;
        case 'month':
            $labels[] = date('M Y', strtotime($lk . '-01'));
            break;
        case 'year':
            $labels[] = $lk;
            break;
        default:
            $labels[] = $lk;
    }
}

// Build aligned data arrays (one per device, aligned to labels)
$aligned = [];
foreach ($devices as $did) {
    $aligned[$did] = [];
    $points_by_label = [];
    foreach ($all_device_data[$did] as $p) {
        $points_by_label[$p['label']] = $p;
    }
    foreach ($label_keys as $lk) {
        if (isset($points_by_label[$lk])) {
            $aligned[$did][] = $points_by_label[$lk];
        } else {
            $aligned[$did][] = ['avg' => null, 'min' => null, 'max' => null];
        }
    }
}

echo json_encode([
    'success'    => true,
    'filter'     => $filter,
    'devices'    => $devices,
    'labels'     => $labels,
    'rf01'       => $aligned['RF01'],
    'rf02'       => $aligned['RF02'],
    'rf03'       => $aligned['RF03'],
    'count'      => count($labels)
]);
