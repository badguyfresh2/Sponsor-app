<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user = require_auth();
$db = get_db();

$stmt = $db->prepare("SELECT b.*, o.org_name, f.created_at as saved_at
                      FROM favorites f
                      JOIN beneficiaries b ON f.beneficiary_id = b.id
                      JOIN organizations o ON b.org_id = o.id
                      WHERE f.sponsor_id = ?
                      ORDER BY f.created_at DESC");
$stmt->execute([$user['id']]);
$favorites = $stmt->fetchAll();

$page_title = 'Saved Beneficiaries';
$show_back = true;
$back_url = BASE_URL . '/sponsor/dashboard.php';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="mb-4">
  <p class="text-xs text-slate-500">Beneficiaries you have bookmarked for sponsorship or future follow-up.</p>
</div>

<?php if (empty($favorites)): ?>
  <div class="app-card text-center py-10">
    <div class="w-14 h-14 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-3 text-xl">
      <i class="fa-regular fa-heart"></i>
    </div>
    <h3 class="font-bold text-slate-800 mb-1">No Saved Beneficiaries</h3>
    <p class="text-xs text-slate-500 mb-4">Tap the heart icon on any child profile while browsing to save them here.</p>
    <a href="<?= BASE_URL ?>/sponsor/discover.php" class="btn btn-primary btn-sm">Browse Beneficiaries</a>
  </div>
<?php else: ?>
  <div class="space-y-3">
    <?php foreach ($favorites as $ben): ?>
      <div class="app-card p-3.5 mb-0 flex items-center gap-3">
        <img src="<?= e($ben['photo_url']) ?>" alt="<?= e($ben['full_name']) ?>" class="w-16 h-16 rounded-xl object-cover flex-shrink-0">
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-1.5 mb-0.5">
            <h3 class="font-bold text-slate-900 text-sm truncate"><?= e($ben['full_name']) ?></h3>
            <span class="text-xs text-slate-400">(Age <?= $ben['age'] ?>)</span>
          </div>
          <div class="text-xs text-slate-500 truncate mb-1">
            <i class="fa-solid fa-location-dot text-[10px] text-rose-500"></i> <?= e($ben['location']) ?>
          </div>
          <div class="text-xs font-bold text-blue-600">
            <?= format_currency($ben['monthly_need_amount'], 'UGX') ?> <span class="text-[10px] text-slate-400 font-normal">/ mo</span>
          </div>
        </div>
        <div class="flex flex-col items-end gap-2">
          <button type="button" class="favorite-btn-action text-rose-500 text-base" data-beneficiary-id="<?= $ben['id'] ?>" aria-label="Remove Favorite">
            <i class="fa-solid fa-heart"></i>
          </button>
          <a href="<?= BASE_URL ?>/sponsor/checkout.php?beneficiary_id=<?= $ben['id'] ?>" class="btn btn-primary btn-sm text-xs py-1 px-2.5">
            Sponsor
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
