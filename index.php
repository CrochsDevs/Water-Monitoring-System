<?php
// index.php
require_once 'includes/config.php';

if (isLoggedIn()) {
    redirect('pages/dashboard.php');
} else {
    redirect('auth/login.php');
}
?>