<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (is_authenticated()) {
    header("Location: " . BASE_URL . "/sponsor/dashboard.php");
} else {
    header("Location: " . BASE_URL . "/auth/login.php");
}
exit;
