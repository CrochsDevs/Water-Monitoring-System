<?php
// db/seed_data.php - Seed sample water level history data
require_once __DIR__ . '/db.php';

// Check if data already exists
$check = $conn->query("SELECT COUNT(*) as cnt FROM water_level_history");
$row = $check->fetch_assoc();
if ($row['cnt'] > 0) {
    echo "Data already exists ({$row['cnt']} rows). Clear first to re-seed.\n";
    $conn->close();
    exit();
}

$base_values = [
    'station'  => 101,
    'tank'     => 95,
    'pipeline' => 105
];

$now = new DateTime();
$values = [];
$batch = [];

// Generate data: every 4 hours for the past 365 days = ~2190 rows
for ($i = 365; $i >= 0; $i--) {
    for ($h = 0; $h < 24; $h += 4) {
        $date = clone $now;
        $date->modify("-{$i} days");
        $date->setTime($h, 0, 0);

        $day_of_year = (int)$date->format('z');
        $hour = $h;

        // Seasonal variation
        $seasonal = sin(2 * M_PI * $day_of_year / 365) * 5;
        // Daily variation
        $daily = sin(2 * M_PI * $hour / 24) * 3;
        // Random noise
        $noise = mt_rand(-200, 200) / 100;

        $station  = round(max(0, $base_values['station']  + $seasonal + $daily + $noise), 2);
        $tank     = round(max(0, $base_values['tank']     + $seasonal * 0.8 + $daily * 0.7 + $noise * 0.9), 2);
        $pipeline = round(max(0, $base_values['pipeline'] + $seasonal * 1.1 + $daily * 0.9 + $noise * 1.1), 2);

        $date_str = $conn->real_escape_string($date->format('Y-m-d H:i:s'));
        $batch[] = "({$station}, {$tank}, {$pipeline}, '{$date_str}')";

        // Insert in batches of 500
        if (count($batch) >= 500) {
            $sql = "INSERT INTO water_level_history (station_pressure, tank_pressure, pipeline_pressure, recorded_at) VALUES " . implode(', ', $batch);
            $conn->query($sql);
            $values = array_merge($values, $batch);
            $batch = [];
        }
    }
}

// Insert remaining
if (count($batch) > 0) {
    $sql = "INSERT INTO water_level_history (station_pressure, tank_pressure, pipeline_pressure, recorded_at) VALUES " . implode(', ', $batch);
    $conn->query($sql);
    $values = array_merge($values, $batch);
}

$total = count($values);
echo "Seeded {$total} records successfully.\n";

// Verify
$verify = $conn->query("SELECT COUNT(*) as cnt, MIN(recorded_at) as earliest, MAX(recorded_at) as latest FROM water_level_history");
$v = $verify->fetch_assoc();
echo "Total rows: {$v['cnt']}\n";
echo "Earliest: {$v['earliest']}\n";
echo "Latest: {$v['latest']}\n";

$conn->close();
