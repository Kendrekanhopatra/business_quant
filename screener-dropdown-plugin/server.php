<?php
/**
 * Simple PHP Development Server
 * Run with: php -S localhost:8000 server.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = urldecode($uri);

// Serve static files
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false; // Let PHP's built-in server handle static files
}

// Route API requests
if (strpos($uri, '/api.php') !== false) {
    include __DIR__ . '/api.php';
    return true;
}

// Default route - serve the test page
if ($uri === '/' || $uri === '/index.html' || $uri === '/test-page.html') {
    include __DIR__ . '/test-page.html';
    return true;
}

// 404 for other routes
http_response_code(404);
echo '404 Not Found';
return true;
?>
