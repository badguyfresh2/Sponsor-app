<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user = require_auth();
$db = get_db();

// Metrics
$stmt = $db->prepare("SELECT COUNT(*) FROM sponsorships WHERE sponsor_id = ? AND status = 'active'");
$stmt->execute([$user['id']]);
$activeChildren = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE sponsor_id = ? AND status = 'completed'");
$stmt->execute([$user['id']]);
$totalGiven = (float)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM favorites WHERE sponsor_id = ?");
$stmt->execute([$user['id']]);
$favoritesCount = (int)$stmt->fetchColumn();

$page_title = 'My Profile';
$current_page = 'profile';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- Profile Hero -->
<div class="app-card text-center p-5 mb-4">
  <div class="relative w-20 h-20 mx-auto mb-3">
    <img src="<?= e($user['avatar_url'] ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80') ?>" alt="<?= e($user['name']) ?>" class="w-full h-full rounded-full object-cover border-2 border-blue-600 shadow-sm">
    <a href="<?= BASE_URL ?>/sponsor/settings.php" class="absolute bottom-0 right-0 w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs shadow">
      <i class="fa-solid fa-camera"></i>
    </a>
  </div>

  <h2 class="text-base font-black text-slate-900"><?= e($user['name']) ?></h2>
  <div class="text-xs text-slate-500 mt-0.5"><?= e($user['email']) ?></div>
  <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 mt-2 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700">
    <i class="fa-solid fa-award text-blue-600"></i> Verified Sponsor
  </div>
</div>

<!-- Profile Statistics -->
<div class="grid grid-cols-3 gap-2 mb-4">
  <div class="stat-box">
    <div class="stat-value text-blue-600"><?= $activeChildren ?></div>
    <div class="stat-label">Children</div>
  </div>
  <div class="stat-box">
    <div class="stat-value text-emerald-600"><?= $favoritesCount ?></div>
    <div class="stat-label">Saved</div>
  </div>
  <div class="stat-box">
    <div class="stat-value text-slate-800 text-xs truncate font-black mt-0.5"><?= format_currency($totalGiven, 'UGX') ?></div>
    <div class="stat-label">Contributed</div>
  </div>
</div>

<!-- Settings & Navigation Links -->
<div class="app-card p-0 overflow-hidden mb-4">
  <div class="divide-y divide-slate-100 text-xs font-semibold text-slate-800">
    <a href="<?= BASE_URL ?>/sponsor/settings.php" class="flex items-center justify-between p-3.5 hover:bg-slate-50 transition">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
          <i class="fa-regular fa-user"></i>
        </div>
        <span>Account & Personal Info</span>
      </div>
      <i class="fa-solid fa-chevron-right text-slate-400 text-xs"></i>
    </a>

    <a href="<?= BASE_URL ?>/sponsor/favorites.php" class="flex items-center justify-between p-3.5 hover:bg-slate-50 transition">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-500 flex items-center justify-center text-sm">
          <i class="fa-regular fa-heart"></i>
        </div>
        <span>Saved Beneficiaries</span>
      </div>
      <div class="flex items-center gap-1 text-slate-400">
        <span><?= $favoritesCount ?></span>
        <i class="fa-solid fa-chevron-right text-xs ml-1"></i>
      </div>
    </a>

    <a href="<?= BASE_URL ?>/sponsor/transactions.php" class="flex items-center justify-between p-3.5 hover:bg-slate-50 transition">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
          <i class="fa-solid fa-file-invoice-dollar"></i>
        </div>
        <span>Payment History & Receipts</span>
      </div>
      <i class="fa-solid fa-chevron-right text-slate-400 text-xs"></i>
    </a>

    <a href="<?= BASE_URL ?>/sponsor/help.php" class="flex items-center justify-between p-3.5 hover:bg-slate-50 transition">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
          <i class="fa-regular fa-circle-question"></i>
        </div>
        <span>Help & FAQs</span>
      </div>
      <i class="fa-solid fa-chevron-right text-slate-400 text-xs"></i>
    </a>
  </div>
</div>

<!-- Logout CTA -->
<div class="app-card p-0 overflow-hidden mb-6">
  <a href="<?= BASE_URL ?>/auth/logout.php" class="flex items-center justify-between p-3.5 text-xs font-bold text-rose-600 hover:bg-rose-50 transition">
    <div class="flex items-center gap-3">
      <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-sm">
        <i class="fa-solid fa-arrow-right-from-bracket"></i>
      </div>
      <span>Sign Out</span>
    </div>
    <i class="fa-solid fa-chevron-right text-rose-300 text-xs"></i>
  </a>
</div>

<div class="text-center text-[11px] text-slate-400">
  NACMU Portal • v1.0.0 (Production)
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
