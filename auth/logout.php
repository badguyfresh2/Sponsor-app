<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

logout_user();
set_flash('success', 'You have been signed out safely.');
header("Location: " . BASE_URL . "/auth/login.php");
exit;
