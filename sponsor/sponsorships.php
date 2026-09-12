<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user = require_auth();
$db = get_db();

$tab = $_GET['tab'] ?? 'active';

$query = "SELECT s.*, b.full_name, b.age, b.location as ben_location, b.photo_url, b.school_grade, o.org_name, o.id as org_id
          FROM sponsorships s
          JOIN beneficiaries b ON s.beneficiary_id = b.id
          JOIN organizations o ON b.org_id = o.id
          WHERE s.sponsor_id = ?";

if ($tab === 'completed') {
    $query .= " AND s.status IN ('completed', 'cancelled')";
} elseif ($tab === 'paused') {
    $query .= " AND s.status = 'paused'";
} else {
    $query .= " AND s.status = 'active'";
}
$query .= " ORDER BY s.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute([$user['id']]);
$sponsorships = $stmt->fetchAll();

// Fetch children awaiting sponsors so users can sponsor directly from this page
$waitingStmt = $db->query("SELECT b.*, o.org_name 
                           FROM beneficiaries b 
                           JOIN organizations o ON b.org_id = o.id 
                           WHERE b.status = 'available' OR b.urgent_flag = 1 
                           ORDER BY b.urgent_flag DESC, b.created_at DESC 
                           LIMIT 4");
$waitingChildren = $waitingStmt->fetchAll();

$page_title = 'My Sponsorships';
$current_page = 'sponsorships';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- Segmented Filter Tabs -->
<div class="segmented-control">
  <a href="<?= BASE_URL ?>/sponsor/sponsorships.php?tab=active" class="segment-btn <?= $tab === 'active' ? 'active' : '' ?>">
    Active (<?= $tab === 'active' ? count($sponsorships) : '•' ?>)
  </a>
  <a href="<?= BASE_URL ?>/sponsor/sponsorships.php?tab=paused" class="segment-btn <?= $tab === 'paused' ? 'active' : '' ?>">
    Paused
  </a>
  <a href="<?= BASE_URL ?>/sponsor/sponsorships.php?tab=completed" class="segment-btn <?= $tab === 'completed' ? 'active' : '' ?>">
    Past
  </a>
</div>

<?php if (empty($sponsorships)): ?>
  <div class="app-card text-center py-8 mb-6">
    <div class="w-14 h-14 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3 text-xl">
      <i class="fa-solid fa-hands-holding-child"></i>
    </div>
    <h3 class="font-bold text-slate-800 mb-1">No <?= ucfirst($tab) ?> Sponsorships</h3>
    <p class="text-xs text-slate-500 max-w-xs mx-auto mb-4">
      <?= $tab === 'active' ? 'You do not have any active children under your care at this moment. Choose a child below to sponsor!' : 'No sponsorships found under this category.' ?>
    </p>
    <a href="<?= BASE_URL ?>/sponsor/discover.php" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-magnifying-glass text-xs"></i>
      <span>Discover All Children</span>
    </a>
  </div>
<?php else: ?>
  <div class="space-y-4 mb-6">
    <?php foreach ($sponsorships as $sp): ?>
      <div class="app-card p-4">
        <div class="flex items-start gap-3 mb-3">
          <a href="<?= BASE_URL ?>/sponsor/beneficiary.php?id=<?= $sp['beneficiary_id'] ?>">
            <img src="<?= e($sp['photo_url']) ?>" alt="<?= e($sp['full_name']) ?>" class="w-16 h-16 rounded-xl object-cover border border-slate-100 shadow-sm flex-shrink-0 hover:opacity-90 transition">
          </a>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-1 mb-0.5">
              <a href="<?= BASE_URL ?>/sponsor/beneficiary.php?id=<?= $sp['beneficiary_id'] ?>" class="font-bold text-slate-900 text-sm truncate hover:text-blue-600 transition">
                <?= e($sp['full_name']) ?>
              </a>
              <?= render_status_badge($sp['status']) ?>
            </div>
            <div class="text-xs text-slate-500 mb-1">
              Age <?= $sp['age'] ?> • <?= e($sp['school_grade'] ?: 'Primary Student') ?>
            </div>
            <div class="text-xs text-slate-500 flex items-center gap-1 truncate">
              <i class="fa-solid fa-building-ngo text-[10px] text-slate-400"></i> <?= e($sp['org_name']) ?>
            </div>
          </div>
        </div>

        <div class="bg-slate-50 rounded-xl p-3 mb-3 grid grid-cols-2 gap-2 text-xs border border-slate-100">
          <div>
            <span class="text-[10px] text-slate-400 font-medium block">Contribution</span>
            <span class="font-bold text-slate-900"><?= format_currency($sp['amount'], 'UGX') ?> <span class="text-[10px] font-normal text-slate-500">/ <?= $sp['frequency'] ?></span></span>
          </div>
          <div>
            <span class="text-[10px] text-slate-400 font-medium block">Total Paid to Date</span>
            <span class="font-bold text-emerald-600"><?= format_currency($sp['total_paid'], 'UGX') ?></span>
          </div>
        </div>

        <div class="flex items-center gap-2 pt-1">
          <a href="<?= BASE_URL ?>/sponsor/checkout.php?beneficiary_id=<?= $sp['beneficiary_id'] ?>" class="btn btn-primary btn-sm flex-1 text-xs py-2 font-bold shadow-xs flex items-center justify-center gap-1.5" aria-label="Sponsor or fund this child">
            <i class="fa-solid fa-heart text-xs text-rose-200"></i>
            <span>Sponsor</span>
          </a>
          <a href="<?= BASE_URL ?>/sponsor/sponsorship-details.php?id=<?= $sp['id'] ?>" class="btn btn-outline btn-sm flex-1 text-xs py-2 flex items-center justify-center gap-1">
            <span>Manage</span>
            <i class="fa-solid fa-chevron-right text-[9px]"></i>
          </a>
          <a href="<?= BASE_URL ?>/sponsor/messages.php?org_id=<?= $sp['org_id'] ?>&beneficiary_id=<?= $sp['beneficiary_id'] ?>" class="btn btn-outline btn-sm px-3 text-xs py-2 flex items-center justify-center" title="Send Message" aria-label="Message Organization">
            <i class="fa-regular fa-comment text-slate-600"></i>
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- Waiting for Sponsors Section -->
<?php if (!empty($waitingChildren)): ?>
  <div class="mt-6 mb-4">
    <div class="flex items-center justify-between mb-3 px-1">
      <div>
        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-1.5">
          <i class="fa-solid fa-hand-holding-heart text-rose-500"></i>
          <span>Children Awaiting Sponsors</span>
        </h2>
        <p class="text-[11px] text-slate-500">More children who urgently need education and medical care</p>
      </div>
      <a href="<?= BASE_URL ?>/sponsor/discover.php" class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-0.5">
        View All <i class="fa-solid fa-chevron-right text-[9px]"></i>
      </a>
    </div>

    <div class="space-y-3">
      <?php foreach ($waitingChildren as $child): ?>
        <?php 
          $isFav = is_favorite($user['id'], $child['id']);
          $percent = $child['monthly_need_amount'] > 0 ? min(100, round(($child['current_supported_amount'] / $child['monthly_need_amount']) * 100)) : 0;
        ?>
        <div class="app-card p-3.5 mb-0 flex items-center gap-3">
          <a href="<?= BASE_URL ?>/sponsor/beneficiary.php?id=<?= $child['id'] ?>" class="flex-shrink-0">
            <img src="<?= e($child['photo_url']) ?>" alt="<?= e($child['full_name']) ?>" class="w-16 h-16 rounded-xl object-cover border border-slate-100 shadow-xs">
          </a>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-1.5 mb-0.5">
              <a href="<?= BASE_URL ?>/sponsor/beneficiary.php?id=<?= $child['id'] ?>" class="font-bold text-slate-900 text-sm truncate hover:text-blue-600 transition">
                <?= e($child['full_name']) ?>
              </a>
              <span class="text-xs text-slate-400 font-medium">(Age <?= $child['age'] ?>)</span>
            </div>
            <div class="text-xs text-slate-500 truncate mb-1 flex items-center gap-1">
              <i class="fa-solid fa-location-dot text-[10px] text-rose-500"></i> <?= e($child['location']) ?> • <?= e($child['org_name']) ?>
            </div>
            <div class="text-xs font-bold text-blue-600">
              <?= format_currency($child['monthly_need_amount'], 'UGX') ?> <span class="text-[10px] text-slate-400 font-normal">/ mo</span>
            </div>
          </div>
          <div class="flex flex-col items-end gap-2 flex-shrink-0">
            <button type="button" class="favorite-btn-action text-slate-300 hover:text-rose-500 text-base transition" data-beneficiary-id="<?= $child['id'] ?>" aria-label="Favorite">
              <i class="<?= $isFav ? 'fa-solid fa-heart text-rose-500' : 'fa-regular fa-heart' ?>"></i>
            </button>
            <a href="<?= BASE_URL ?>/sponsor/checkout.php?beneficiary_id=<?= $child['id'] ?>" class="btn btn-primary btn-sm text-xs py-1.5 px-3 font-bold shadow-xs flex items-center gap-1">
              <i class="fa-solid fa-heart text-[10px]"></i>
              <span>Sponsor</span>
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
