<?php
// includes/config.php
session_start();

// Base URL (i-adjust mo based sa setup mo)
define('BASE_URL', '/WATER%20MONITORING%20SYSTEM');
define('SITE_NAME', 'Water Monitoring System');

// Include database
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/functions.php';
?>