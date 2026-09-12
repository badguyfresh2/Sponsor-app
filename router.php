<?php
// Router for PHP built-in server
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve static files directly if they exist
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Default index routing
if ($uri === '/' || $uri === '') {
    require __DIR__ . '/index.php';
    exit;
}

// If file exists with .php extension
if (file_exists(__DIR__ . $uri . '.php')) {
    require __DIR__ . $uri . '.php';
    exit;
}

// 404 handler
http_response_code(404);
echo "404 Not Found";
