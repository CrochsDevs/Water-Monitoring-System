<?php
$db_config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'water_monitoring'
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