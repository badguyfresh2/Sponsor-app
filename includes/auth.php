<?php
// includes/auth.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_user_role() {
    return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
}

function require_login() {
    if (!is_logged_in()) {
        redirect('auth/login.php');
    }
}

function require_role($role) {
    require_login();
    if (current_user_role() !== $role) {
        http_response_code(403);
        die("403 Forbidden - You do not have permission to access this page.");
    }
}

function login_user($user) {
    session_regenerate_id(true); // Prevent session fixation
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_email'] = $user['email'];
}

function logout_user() {
    session_unset();
    session_destroy();
}
?>
