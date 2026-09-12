<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$currentUser = get_authenticated_user();
$unreadNotifs = $currentUser ? get_unread_notifications_count($currentUser['id']) : 0;
$pageTitle = $page_title ?? 'NACMU Portal';
$showBack = $show_back ?? false;
$backUrl = $back_url ?? (BASE_URL . '/sponsor/dashboard.php');
$flash = get_flash();

// Time-based greeting
$hour = (int)date('H');
if ($hour < 12) {
    $greeting = "Good morning";
} elseif ($hour < 17) {
    $greeting = "Good afternoon";
} else {
    $greeting = "Good evening";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta name="theme-color" content="#2563EB">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <title><?= e($pageTitle) ?> | <?= APP_NAME ?></title>

  <!-- PWA Manifest -->
  <link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563EB'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/></svg>">

  <!-- Tailwind CSS CDN for swift, accurate utility styling -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: {
              50: '#EFF6FF',
              500: '#2563EB',
              600: '#1D4ED8',
              700: '#1E40AF',
            },
            tealbrand: {
              50: '#F0FDFA',
              500: '#0EA5A4',
              600: '#0F766E'
            }
          }
        }
      }
    }
  </script>

  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <!-- Custom App Styles -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/mobile.css">

  <script>
    window.BASE_URL = "<?= BASE_URL ?>";
    window.APP_SID = "<?= session_id() ?>";
    window.CSRF_TOKEN = "<?= generate_csrf_token() ?>";
  </script>
</head>
<body>
<div class="mobile-app-container">

  <?php if (!empty($currentUser) && empty($hide_header)): ?>
  <!-- Top App Bar -->
  <header class="mobile-header">
    <?php if ($showBack): ?>
      <div class="flex items-center gap-3">
        <a href="<?= e($backUrl) ?>" class="icon-button" aria-label="Go Back">
          <i class="fa-solid fa-arrow-left text-slate-700"></i>
        </a>
        <h1 class="text-base font-bold text-slate-900 truncate max-w-[200px]"><?= e($pageTitle) ?></h1>
      </div>
    <?php else: ?>
      <a href="<?= BASE_URL ?>/sponsor/profile.php" class="header-user-badge">
        <img src="<?= e($currentUser['avatar_url'] ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80') ?>" alt="<?= e($currentUser['name']) ?>" class="header-avatar">
        <div>
          <div class="header-greeting-title"><?= $greeting ?></div>
          <div class="header-user-name"><?= e(explode(' ', $currentUser['name'])[0]) ?></div>
        </div>
      </a>
    <?php endif; ?>

    <div class="header-actions">
      <?php if (!empty($header_action_html)): ?>
        <?= $header_action_html ?>
      <?php endif; ?>
      <a href="<?= BASE_URL ?>/sponsor/notifications.php" id="headerNotifBtn" class="icon-button header-notif-btn relative" aria-label="Notifications">
        <i class="fa-regular fa-bell text-slate-700 text-lg"></i>
        <?php if ($unreadNotifs > 0): ?>
          <span class="notification-badge"><?= $unreadNotifs > 9 ? '9+' : $unreadNotifs ?></span>
        <?php endif; ?>
      </a>
    </div>
  </header>
  <?php endif; ?>

  <main class="mobile-content <?= !empty($is_auth_flow) ? 'auth-flow' : '' ?> <?= !empty($is_chat_view) ? 'chat-view-mode' : '' ?>">
    <?php if ($flash): ?>
      <div class="flash-banner <?= e($flash['type']) ?>">
        <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : ($flash['type'] === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-info') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
    <?php endif; ?>
