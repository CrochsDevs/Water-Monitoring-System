<?php
// db/setup_history_table.php
// Run this file ONCE to create the water_level_history table and seed sample data

require_once __DIR__ . '/db.php';

// Create the water_level_history table
$sql = "CREATE TABLE IF NOT EXISTS water_level_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    station_pressure DECIMAL(8,2) NOT NULL COMMENT 'Barometer 1 - Station Pressure in cm',
    tank_pressure DECIMAL(8,2) NOT NULL COMMENT 'Barometer 2 - Tank Pressure in cm',
    pipeline_pressure DECIMAL(8,2) NOT NULL COMMENT 'Barometer 3 - Pipeline Pressure in cm',
    recorded_at DATETIME NOT NULL,
    INDEX idx_recorded_at (recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "Table 'water_level_history' created successfully.<br>";
} else {
    die("Error creating table: " . $conn->error);
}

// Check if data already exists
$check = $conn->query("SELECT COUNT(*) as cnt FROM water_level_history");
$row = $check->fetch_assoc();
if ($row['cnt'] > 0) {
    echo "Data already exists ({$row['cnt']} rows). Skipping seed.<br>";
    echo "<a href='../index.php'>Go to Dashboard</a>";
    exit();
}

// Seed sample data for the past 365 days
$base_values = [
    'station'  => 101,
    'tank'     => 95,
    'pipeline' => 105
];

$now = new DateTime();
$insert_sql = "INSERT INTO water_level_history (station_pressure, tank_pressure, pipeline_pressure, recorded_at) VALUES ";
$values = [];

// Generate data points: every hour for the past 365 days
for ($i = 365; $i >= 0; $i--) {
    for ($h = 0; $h < 24; $h += 4) { // Every 4 hours to keep data manageable
        $date = clone $now;
        $date->modify("-{$i} days");
        $date->setTime($h, 0, 0);

        // Add realistic variation based on time of day and season
        $day_of_year = (int)$date->format('z');
        $hour = $h;

        // Seasonal variation (sinusoidal)
        $seasonal = sin(2 * M_PI * $day_of_year / 365) * 5;
        // Daily variation
        $daily = sin(2 * M_PI * $hour / 24) * 3;
        // Random noise
        $noise = mt_rand(-200, 200) / 100;

        $station  = round($base_values['station']  + $seasonal + $daily + $noise, 2);
        $tank     = round($base_values['tank']     + $seasonal * 0.8 + $daily * 0.7 + $noise * 0.9, 2);
        $pipeline = round($base_values['pipeline'] + $seasonal * 1.1 + $daily * 0.9 + $noise * 1.1, 2);

        // Ensure no negative values
        $station  = max(0, $station);
        $tank     = max(0, $tank);
        $pipeline = max(0, $pipeline);

        $date_str = $date->format('Y-m-d H:i:s');
        $values[] = "({$station}, {$tank}, {$pipeline}, '{$date_str}')";
    }
}

// Insert in batches of 500
$chunks = array_chunk($values, 500);
foreach ($chunks as $chunk) {
    $query = $insert_sql . implode(', ', $chunk);
    if ($conn->query($query) !== TRUE) {
        echo "Error inserting batch: " . $conn->error . "<br>";
    }
}

$total = count($values);
echo "Seeded {$total} records successfully.<br>";
echo "<a href='../index.php'>Go to Dashboard</a>";

$conn->close();
