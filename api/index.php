<?php
// api/index.php - Vercel entry point

// Get the requested path
$requestUri = $_SERVER['REQUEST_URI'];
$requestUri = strtok($requestUri, '?'); // Remove query string

// Serve static files directly
$staticExtensions = ['css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'ico', 'svg', 'woff', 'woff2', 'ttf', 'eot'];
$extension = pathinfo($requestUri, PATHINFO_EXTENSION);

if (in_array($extension, $staticExtensions)) {
    $filePath = __DIR__ . '/..' . $requestUri;
    if (file_exists($filePath)) {
        // Set proper content type
        switch ($extension) {
            case 'css':
                header('Content-Type: text/css');
                break;
            case 'js':
                header('Content-Type: application/javascript');
                break;
            case 'jpg':
            case 'jpeg':
                header('Content-Type: image/jpeg');
                break;
            case 'png':
                header('Content-Type: image/png');
                break;
            case 'svg':
                header('Content-Type: image/svg+xml');
                break;
        }
        readfile($filePath);
        exit;
    }
}

// Handle PHP routes
if ($requestUri == '/' || $requestUri == '') {
    require __DIR__ . '/../index.php';
} elseif (strpos($requestUri, '/auth/') === 0 || 
          strpos($requestUri, '/pages/') === 0) {
    $phpFile = __DIR__ . '/..' . $requestUri;
    if (file_exists($phpFile) && pathinfo($phpFile, PATHINFO_EXTENSION) == 'php') {
        require $phpFile;
    } else {
        http_response_code(404);
        echo "Page not found";
    }
} else {
    require __DIR__ . '/../index.php';
}
?>