<?php
// includes/functions.php

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function format_currency($amount) {
    return '$' . number_format($amount, 2);
}

function get_flash_message() {
    if (isset($_SESSION['flash_msg'])) {
        $msg = $_SESSION['flash_msg'];
        $type = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : 'info';
        unset($_SESSION['flash_msg']);
        unset($_SESSION['flash_type']);
        return ['message' => $msg, 'type' => $type];
    }
    return null;
}

function set_flash_message($msg, $type = 'info') {
    $_SESSION['flash_msg'] = $msg;
    $_SESSION['flash_type'] = $type;
}
?>
