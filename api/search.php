<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user = get_authenticated_user();
$userId = $user ? $user['id'] : 0;
$db = get_db();

$q = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');

$sql = "SELECT b.*, o.org_name 
        FROM beneficiaries b
        JOIN organizations o ON b.org_id = o.id
        WHERE 1=1";
$params = [];

if (!empty($q)) {
    $sql .= " AND (b.full_name LIKE ? OR b.location LIKE ? OR b.school_grade LIKE ? OR b.story LIKE ?)";
    $term = "%{$q}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($category)) {
    if (strtolower($category) === 'urgent') {
        $sql .= " AND b.urgent_flag = 1";
    } else {
        $sql .= " AND b.category = ?";
        $params[] = $category;
    }
}

$sql .= " ORDER BY b.urgent_flag DESC, b.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$beneficiaries = $stmt->fetchAll();

ob_start();
if (empty($beneficiaries)) {
    ?>
    <div class="app-card text-center py-8">
      <i class="fa-solid fa-user-slash text-4xl text-slate-300 mb-3"></i>
      <p class="font-bold text-slate-700">No beneficiaries found</p>
      <p class="text-xs text-slate-500 mt-1">Try adjusting your keywords or category filters.</p>
    </div>
    <?php
} else {
    foreach ($beneficiaries as $ben) {
        $isFav = $userId ? is_favorite($userId, $ben['id']) : false;
        $percent = $ben['monthly_need_amount'] > 0 ? min(100, round(($ben['current_supported_amount'] / $ben['monthly_need_amount']) * 100)) : 0;
        ?>
        <div class="beneficiary-card">
          <div class="beneficiary-img-wrapper">
            <img src="<?= e($ben['photo_url']) ?>" alt="<?= e($ben['full_name']) ?>" class="beneficiary-img" loading="lazy">
            
            <?php if ($ben['urgent_flag']): ?>
              <div class="urgent-badge-floating">
                <i class="fa-solid fa-triangle-exclamation text-[9px]"></i> Urgent Need
              </div>
            <?php endif; ?>

            <button type="button" class="favorite-btn-floating favorite-btn-action <?= $isFav ? 'active' : '' ?>" data-beneficiary-id="<?= $ben['id'] ?>" aria-label="Favorite">
              <i class="<?= $isFav ? 'fa-solid fa-heart text-rose-500' : 'fa-regular fa-heart' ?>"></i>
            </button>
          </div>

          <div class="beneficiary-content">
            <div class="beneficiary-header-row">
              <h3 class="beneficiary-name"><?= e($ben['full_name']) ?>, <?= $ben['age'] ?></h3>
              <?= render_status_badge($ben['status']) ?>
            </div>

            <div class="beneficiary-location">
              <i class="fa-solid fa-location-dot text-rose-500 text-xs"></i>
              <span><?= e($ben['location']) ?> • <?= e($ben['org_name']) ?></span>
            </div>

            <p class="beneficiary-snippet"><?= e($ben['story']) ?></p>

            <div class="mb-3">
              <div class="flex items-center justify-between text-[11px] text-slate-500 mb-1">
                <span>Goal: <strong class="text-slate-800"><?= format_currency($ben['monthly_need_amount'], 'UGX') ?></strong>/mo</span>
                <span class="font-bold text-blue-600"><?= $percent ?>%</span>
              </div>
              <div class="progress-container">
                <div class="progress-fill" style="width: <?= $percent ?>%;"></div>
              </div>
            </div>

            <div class="beneficiary-footer-row">
              <div>
                <div class="text-[10px] text-slate-400 font-medium">Monthly Requirement</div>
                <div class="text-sm font-black text-slate-900"><?= format_currency($ben['monthly_need_amount'], 'UGX') ?></div>
              </div>
              <div class="flex items-center gap-2">
                <a href="<?= BASE_URL ?>/sponsor/beneficiary.php?id=<?= $ben['id'] ?>" class="btn btn-outline btn-sm">
                  <span>Profile</span>
                </a>
                <a href="<?= BASE_URL ?>/sponsor/checkout.php?beneficiary_id=<?= $ben['id'] ?>" class="btn btn-primary btn-sm">
                  <span>Sponsor</span>
                  <i class="fa-solid fa-heart text-xs"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
        <?php
    }
}
$html = ob_get_clean();

echo json_encode([
    'success' => true,
    'count' => count($beneficiaries),
    'html' => $html
]);
