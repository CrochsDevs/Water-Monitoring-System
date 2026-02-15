<?php
// includes/functions.php - All reusable functions

/**
 * User Authentication Functions
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect('auth/login.php');
    }
}

function requireGuest(): void {
    if (isLoggedIn()) {
        redirect('pages/dashboard.php');
    }
}

/**
 * Redirect Function
 */
function redirect(string $path): void {
    header('Location: ' . BASE_URL . '/' . $path);
    exit();
}

/**
 * Sanitize Input
 */
function sanitize(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate Email
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Get Current User Data
 */
function getCurrentUser($conn): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    
    $stmt = $conn->prepare("SELECT user_id, full_name, email, username, role, last_login FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

/**
 * Display Flash Messages
 */
function displayFlash(string $type, string $message): string {
    $colors = [
        'error' => '#ffebee',
        'success' => '#e8f5e9',
        'info' => '#e3f2fd',
        'warning' => '#fff3e0'
    ];
    
    $textColors = [
        'error' => '#d32f2f',
        'success' => '#2e7d32',
        'info' => '#1976d2',
        'warning' => '#f57c00'
    ];
    
    return sprintf(
        '<div style="background: %s; color: %s; padding: 12px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid %s;">%s</div>',
        $colors[$type],
        $textColors[$type],
        $textColors[$type],
        $message
    );
}

/**
 * Validate Password Strength
 */
function isStrongPassword(string $password): array {
    $errors = [];
    
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}
?>