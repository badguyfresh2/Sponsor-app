<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user = require_auth();
$db = get_db();

$beneficiaryId = (int)($_GET['id'] ?? 0);
if (!$beneficiaryId) {
    header("Location: " . BASE_URL . "/sponsor/discover.php");
    exit;
}

$stmt = $db->prepare("SELECT b.*, o.org_name, o.org_type, o.location as org_loc, o.rating, o.verified, o.logo_url as org_logo, o.id as organization_id
                      FROM beneficiaries b
                      JOIN organizations o ON b.org_id = o.id
                      WHERE b.id = ?");
$stmt->execute([$beneficiaryId]);
$beneficiary = $stmt->fetch();

if (!$beneficiary) {
    header("Location: " . BASE_URL . "/sponsor/discover.php");
    exit;
}

// Fetch needs checklist
$stmt = $db->prepare("SELECT * FROM beneficiary_needs WHERE beneficiary_id = ?");
$stmt->execute([$beneficiaryId]);
$needs = $stmt->fetchAll();

// Fetch updates for this beneficiary
$stmt = $db->prepare("SELECT * FROM impact_updates WHERE beneficiary_id = ? ORDER BY created_at DESC");
$stmt->execute([$beneficiaryId]);
$updates = $stmt->fetchAll();

$isFav = is_favorite($user['id'], $beneficiary['id']);
$percent = $beneficiary['monthly_need_amount'] > 0 ? min(100, round(($beneficiary['current_supported_amount'] / $beneficiary['monthly_need_amount']) * 100)) : 0;

$page_title = $beneficiary['full_name'];
$show_back = true;
$back_url = BASE_URL . '/sponsor/discover.php';
$hide_bottom_nav = true;
$header_action_html = '
<button type="button" class="icon-button favorite-btn-action ' . ($isFav ? 'active' : '') . '" data-beneficiary-id="' . $beneficiary['id'] . '" aria-label="Favorite">
  <i class="' . ($isFav ? 'fa-solid fa-heart text-rose-500' : 'fa-regular fa-heart text-slate-700') . '"></i>
</button>';

require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- Hero Photo Container -->
<div class="-mx-4.5 -mt-4 mb-4 relative">
  <div class="h-64 w-full bg-slate-200 overflow-hidden relative">
    <img src="<?= e($beneficiary['photo_url']) ?>" alt="<?= e($beneficiary['full_name']) ?>" class="w-full h-full object-cover">
    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-black/20"></div>

    <div class="absolute bottom-4 left-4 right-4 text-white">
      <div class="flex items-center gap-2 mb-1">
        <h1 class="text-2xl font-black"><?= e($beneficiary['full_name']) ?></h1>
        <span class="text-sm font-semibold opacity-90">(Age <?= $beneficiary['age'] ?>)</span>
      </div>
      <div class="flex items-center gap-3 text-xs opacity-90">
        <span><i class="fa-solid fa-location-dot mr-1"></i> <?= e($beneficiary['location']) ?></span>
        <span>•</span>
        <span><i class="fa-solid fa-graduation-cap mr-1"></i> <?= e($beneficiary['school_grade'] ?: 'Primary Student') ?></span>
      </div>
    </div>
  </div>
</div>

<!-- Key Highlights Bar -->
<div class="grid grid-cols-3 gap-2 mb-4">
  <div class="stat-box">
    <div class="stat-value text-base"><?= e($beneficiary['gender']) ?></div>
    <div class="stat-label">Gender</div>
  </div>
  <div class="stat-box">
    <div class="stat-value text-base text-blue-600"><?= $beneficiary['age'] ?> yrs</div>
    <div class="stat-label">Age</div>
  </div>
  <div class="stat-box">
    <div class="stat-value text-base text-emerald-600"><?= $percent ?>%</div>
    <div class="stat-label">Funded</div>
  </div>
</div>

<!-- Support Progress & Goal -->
<div class="app-card mb-4">
  <div class="flex items-center justify-between text-xs mb-1.5">
    <span class="text-slate-500 font-medium">Monthly Sponsorship Target</span>
    <span class="font-black text-slate-900"><?= format_currency($beneficiary['monthly_need_amount'], 'UGX') ?>/mo</span>
  </div>
  <div class="progress-container mb-2">
    <div class="progress-fill" style="width: <?= $percent ?>%;"></div>
  </div>
  <div class="flex items-center justify-between text-[11px] text-slate-500">
    <span>Supported: <strong><?= format_currency($beneficiary['current_supported_amount'], 'UGX') ?></strong></span>
    <span><?= render_status_badge($beneficiary['status']) ?></span>
  </div>
</div>

<!-- Story & Dreams -->
<div class="app-card mb-4">
  <h2 class="card-title text-base mb-2">Personal Story</h2>
  <p class="text-xs text-slate-600 leading-relaxed mb-4"><?= nl2br(e($beneficiary['story'])) ?></p>

  <?php if (!empty($beneficiary['dreams'])): ?>
    <div class="bg-amber-50/70 border border-amber-200/80 rounded-xl p-3 flex items-start gap-2.5">
      <div class="w-7 h-7 rounded-lg bg-amber-500 text-white flex items-center justify-center flex-shrink-0 text-xs">
        <i class="fa-solid fa-star"></i>
      </div>
      <div>
        <div class="text-[11px] font-bold uppercase tracking-wider text-amber-800">Child's Dream</div>
        <div class="text-xs font-semibold text-slate-800 mt-0.5"><?= e($beneficiary['dreams']) ?></div>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Itemized Needs Breakdown -->
<?php if (!empty($needs)): ?>
  <div class="app-card mb-4">
    <h2 class="card-title text-base mb-3">Essential Needs Covered</h2>
    <div class="space-y-2.5">
      <?php foreach ($needs as $need): ?>
        <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 border border-slate-100">
          <div class="flex items-center gap-2.5">
            <div class="w-6 h-6 rounded-full <?= $need['is_fulfilled'] ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-200 text-slate-500' ?> flex items-center justify-center text-[10px]">
              <i class="fa-solid <?= $need['is_fulfilled'] ? 'fa-check' : 'fa-circle-notch' ?>"></i>
            </div>
            <div>
              <div class="text-xs font-bold text-slate-800"><?= e($need['need_title']) ?></div>
              <div class="text-[10px] text-slate-400"><?= e($need['need_category']) ?></div>
            </div>
          </div>
          <div class="text-right">
            <div class="text-xs font-bold text-slate-900"><?= format_currency($need['estimated_cost'], 'UGX') ?></div>
            <span class="text-[10px] <?= $need['is_fulfilled'] ? 'text-emerald-600 font-semibold' : 'text-slate-400' ?>">
              <?= $need['is_fulfilled'] ? 'Covered' : 'Pending' ?>
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<!-- Organization Card -->
<div class="app-card mb-6">
  <div class="flex items-center justify-between mb-2">
    <div class="flex items-center gap-3">
      <img src="<?= e($beneficiary['org_logo']) ?>" alt="<?= e($beneficiary['org_name']) ?>" class="w-10 h-10 rounded-xl object-cover border border-slate-100">
      <div>
        <div class="flex items-center gap-1.5">
          <h3 class="text-xs font-bold text-slate-900"><?= e($beneficiary['org_name']) ?></h3>
          <?php if ($beneficiary['verified']): ?>
            <i class="fa-solid fa-circle-check text-blue-600 text-xs" title="Verified NGO"></i>
          <?php endif; ?>
        </div>
        <div class="text-[11px] text-slate-500"><?= e($beneficiary['org_type']) ?></div>
      </div>
    </div>
    <div class="text-right">
      <div class="text-xs font-bold text-slate-800 flex items-center gap-1">
        <i class="fa-solid fa-star text-amber-400 text-[10px]"></i> <?= number_format($beneficiary['rating'], 1) ?>
      </div>
      <div class="text-[10px] text-slate-400">Rating</div>
    </div>
  </div>

  <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
    <span class="text-xs text-slate-500">Supervising Organization</span>
    <a href="<?= BASE_URL ?>/sponsor/messages.php?org_id=<?= $beneficiary['organization_id'] ?>&beneficiary_id=<?= $beneficiary['id'] ?>" class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1">
      <i class="fa-regular fa-comment"></i> Send Inquiry
    </a>
  </div>
</div>

<!-- Sticky Bottom CTA -->
<div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[480px] bg-white/95 backdrop-blur-md border-t border-slate-200 px-4 pt-3 pb-[calc(12px+var(--safe-bottom,0px))] z-40 flex items-center gap-3 shadow-lg">
  <a href="<?= BASE_URL ?>/sponsor/messages.php?org_id=<?= $beneficiary['organization_id'] ?>&beneficiary_id=<?= $beneficiary['id'] ?>" class="btn btn-outline min-h-[44px] px-3.5 rounded-xl border-slate-200 hover:bg-slate-50" aria-label="Message Organization">
    <i class="fa-regular fa-comment-dots text-base text-slate-700"></i>
  </a>
  <a href="<?= BASE_URL ?>/sponsor/checkout.php?beneficiary_id=<?= $beneficiary['id'] ?>" class="btn btn-primary flex-1 min-h-[44px] rounded-xl shadow-md">
    <span>Sponsor <?= e(explode(' ', $beneficiary['full_name'])[0]) ?></span>
    <i class="fa-solid fa-arrow-right text-xs"></i>
  </a>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
