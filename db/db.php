<?php
// db/db.php - Pure database connection only
// Uses Docker environment variables, falls back to dev defaults
$db_config = [
    'host'     => getenv('DB_HOST')     ?: 'localhost',
    'username' => getenv('DB_USER')     ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'database' => getenv('DB_NAME')     ?: 'water_monitoring'
];

$conn = new mysqli(
    $db_config['host'], 
    $db_config['username'], 
    $db_config['password'], 
    $db_config['database']
);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed']));
}

$conn->set_charset("utf8");
?>  