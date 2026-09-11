<?php
// config/config.php
session_start();

// Application settings
define('APP_NAME', 'Sponsorship Web Application');
define('APP_URL', 'http://localhost/sponsor-app'); // Adjust as needed
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Helper for generating absolute URLs
function base_url($path = '') {
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

// Redirect helper
function redirect($path) {
    header('Location: ' . base_url($path));
    exit;
}
?>
