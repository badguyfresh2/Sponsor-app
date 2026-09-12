<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user = require_auth();
$db = get_db();

$filter = $_GET['filter'] ?? 'all';

if ($filter === 'my') {
    $stmt = $db->prepare("SELECT iu.*, b.full_name as ben_name, b.photo_url as ben_photo, b.id as ben_id,
                                 o.org_name, o.logo_url as org_logo, o.id as org_id
                          FROM impact_updates iu
                          JOIN beneficiaries b ON iu.beneficiary_id = b.id
                          JOIN organizations o ON iu.org_id = o.id
                          JOIN sponsorships s ON s.beneficiary_id = b.id
                          WHERE s.sponsor_id = ?
                          ORDER BY iu.created_at DESC");
    $stmt->execute([$user['id']]);
} else {
    $stmt = $db->prepare("SELECT iu.*, b.full_name as ben_name, b.photo_url as ben_photo, b.id as ben_id,
                                 o.org_name, o.logo_url as org_logo, o.id as org_id
                          FROM impact_updates iu
                          JOIN beneficiaries b ON iu.beneficiary_id = b.id
                          JOIN organizations o ON iu.org_id = o.id
                          ORDER BY iu.created_at DESC");
    $stmt->execute();
}
$updates = $stmt->fetchAll();

$page_title = 'Impact Updates';
$current_page = 'dashboard'; // or subview
require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- Filter Tabs -->
<div class="segmented-control mb-4">
  <a href="<?= BASE_URL ?>/sponsor/updates.php?filter=all" class="segment-btn <?= $filter === 'all' ? 'active' : '' ?>">
    Community Feed
  </a>
  <a href="<?= BASE_URL ?>/sponsor/updates.php?filter=my" class="segment-btn <?= $filter === 'my' ? 'active' : '' ?>">
    My Sponsored Children
  </a>
</div>

<?php if (empty($updates)): ?>
  <div class="app-card text-center py-10">
    <div class="w-14 h-14 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3 text-xl">
      <i class="fa-regular fa-newspaper"></i>
    </div>
    <h3 class="font-bold text-slate-800 mb-1">No Updates Available</h3>
    <p class="text-xs text-slate-500 mb-4">Check back soon for new school term letters and photos.</p>
    <a href="<?= BASE_URL ?>/sponsor/discover.php" class="btn btn-primary btn-sm">Browse Beneficiaries</a>
  </div>
<?php else: ?>
  <div class="space-y-4">
    <?php foreach ($updates as $up): ?>
      <article class="app-card p-4">
        <!-- Author row -->
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center gap-2.5">
            <a href="<?= BASE_URL ?>/sponsor/beneficiary.php?id=<?= $up['ben_id'] ?>">
              <img src="<?= e($up['ben_photo']) ?>" alt="<?= e($up['ben_name']) ?>" class="w-10 h-10 rounded-full object-cover border border-slate-100 shadow-sm">
            </a>
            <div>
              <a href="<?= BASE_URL ?>/sponsor/beneficiary.php?id=<?= $up['ben_id'] ?>" class="text-xs font-bold text-slate-900 hover:text-blue-600 block">
                <?= e($up['ben_name']) ?>
              </a>
              <div class="text-[10px] text-slate-500 flex items-center gap-1">
                <span><?= e($up['org_name']) ?></span>
                <span>•</span>
                <span><?= time_elapsed_string($up['created_at']) ?></span>
              </div>
            </div>
          </div>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100">
            <?= ucfirst($up['update_type']) ?>
          </span>
        </div>

        <!-- Featured Media -->
        <?php if (!empty($up['image_url'])): ?>
          <div class="rounded-xl overflow-hidden mb-3 bg-slate-100 border border-slate-100 max-h-56">
            <img src="<?= e($up['image_url']) ?>" alt="<?= e($up['title']) ?>" class="w-full h-full object-cover">
          </div>
        <?php endif; ?>

        <!-- Content -->
        <h3 class="font-black text-slate-900 text-sm mb-1.5"><?= e($up['title']) ?></h3>
        <p class="text-xs text-slate-600 leading-relaxed mb-4"><?= nl2br(e($up['content'])) ?></p>

        <!-- Social Interactions Bar -->
        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
          <button type="button" class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-rose-500 transition" onclick="this.classList.toggle('text-rose-500'); showToast('Cheered this milestone!', 'fa-heart')">
            <i class="fa-regular fa-heart"></i>
            <span>Encourage</span>
          </button>

          <a href="<?= BASE_URL ?>/sponsor/messages.php?org_id=<?= $up['org_id'] ?>&beneficiary_id=<?= $up['ben_id'] ?>" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700">
            <i class="fa-regular fa-comment"></i>
            <span>Reply / Congratulate</span>
          </a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
