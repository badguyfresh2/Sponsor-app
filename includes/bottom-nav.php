<?php
$currentPage = $current_page ?? '';
$unreadMsgs = !empty($currentUser) ? get_unread_messages_count($currentUser['id']) : 0;
?>
<nav class="mobile-bottom-nav" aria-label="Bottom Navigation">
  <a href="<?= BASE_URL ?>/sponsor/dashboard.php" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
    <i class="fa-solid fa-house"></i>
    <span>Home</span>
  </a>

  <a href="<?= BASE_URL ?>/sponsor/discover.php" class="nav-item <?= $currentPage === 'discover' ? 'active' : '' ?>">
    <i class="fa-solid fa-compass"></i>
    <span>Discover</span>
  </a>

  <a href="<?= BASE_URL ?>/sponsor/sponsorships.php" class="nav-item <?= $currentPage === 'sponsorships' ? 'active' : '' ?>">
    <i class="fa-solid fa-heart"></i>
    <span>Sponsor</span>
  </a>

  <a href="<?= BASE_URL ?>/sponsor/messages.php" class="nav-item <?= $currentPage === 'messages' ? 'active' : '' ?>">
    <i class="fa-solid fa-comments"></i>
    <span>Messages</span>
    <?php if ($unreadMsgs > 0): ?>
      <span class="nav-item-badge"><?= $unreadMsgs > 9 ? '9+' : $unreadMsgs ?></span>
    <?php endif; ?>
  </a>

  <a href="<?= BASE_URL ?>/sponsor/profile.php" class="nav-item <?= $currentPage === 'profile' ? 'active' : '' ?>">
    <i class="fa-solid fa-user"></i>
    <span>Profile</span>
  </a>
</nav>
