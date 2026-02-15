<?php
// auth/logout.php
require_once __DIR__ . '/../includes/config.php';

// If may logout confirmation
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    $_SESSION = array();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <script>
        Swal.fire({
            title: 'Logout?',
            text: 'Are you sure you want to logout?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#006837',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Logout',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'logout.php?confirm=yes';
            } else {
                window.location.href = '../pages/dashboard.php';
            }
        });
    </script>
</body>
</html>