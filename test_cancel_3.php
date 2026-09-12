<?php
session_start();
$_SESSION['user_id'] = 1;
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'cancel';
$_POST['csrf_token'] = generate_csrf_token();
$_GET['id'] = 1;

require 'sponsor/sponsorship-details.php';
