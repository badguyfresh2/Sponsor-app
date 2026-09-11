<?php
// includes/header.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : APP_NAME ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="<?= base_url() ?>">
            <i class="fas fa-hands-holding-child me-2"></i>SPONSOR
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-toggle="target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= base_url() ?>">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= base_url('beneficiaries.php') ?>">Find a Child</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= base_url('organizations.php') ?>">Organizations</a></li>
            </ul>
            <ul class="navbar-nav">
                <?php if (is_logged_in()): ?>
                    <li class="nav-item">
                        <a class="nav-link fw-bold" href="<?= base_url(current_user_role() . '/dashboard.php') ?>">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-danger ms-2" href="<?= base_url('auth/logout.php') ?>">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('auth/login.php') ?>">Login</a></li>
                    <li class="nav-item"><a class="btn btn-primary ms-2" href="<?= base_url('auth/register.php?role=sponsor') ?>">Become a Sponsor</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="min-vh-100">
    <?php
    $flash = get_flash_message();
    if ($flash):
    ?>
    <div class="container mt-3">
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php endif; ?>
