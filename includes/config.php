<?php
session_start();

define('BASE_URL', '/WATER%20MONITORING%20SYSTEM');
define('SITE_NAME', 'Water Monitoring System');

require_once __DIR__ . '/../db/db.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// ADD THIS FUNCTION - for login/register pages
function requireGuest() {
    if (isLoggedIn()) {
        redirect('pages/dashboard.php');
    }
}

function redirect($url) {
    header("Location: " . BASE_URL . "/" . ltrim($url, '/'));
    exit();
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('auth/login.php');
    }
}

function getCurrentUser($conn) {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $stmt = $conn->prepare("SELECT user_id, full_name, email, role, created_at FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

global $conn;
?>