<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

logout_user();
set_flash_message("You have been successfully logged out.", "success");
redirect('index.php');
?>
