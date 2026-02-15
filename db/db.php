<?php
$host = 'mysql-3c67946d-water-monitoring-system.a.aivencloud.com';
$port = '25803';
$dbname = 'defaultdb';
$username = 'avnadmin';
$password = 'AVNS_iaWyjt_7-Jxc0FpIDzG';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_SSL_CA => null,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            PDO::MYSQL_ATTR_SSL_CIPHER => 'REQUIRED'
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>