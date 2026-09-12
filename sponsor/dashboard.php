<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user = require_auth();
$db = get_db();

// 1. Get statistics for this sponsor
$stmt = $db->prepare("SELECT COUNT(*) FROM sponsorships WHERE sponsor_id = ? AND status = 'active'");
$stmt->execute([$user['id']]);
$activeCount = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE sponsor_id = ? AND status = 'completed'");
$stmt->execute([$user['id']]);
$totalContributions = (float)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(DISTINCT iu.id) 
                      FROM impact_updates iu
                      JOIN sponsorships s ON iu.beneficiary_id = s.beneficiary_id
                      WHERE s.sponsor_id = ?");
$stmt->execute([$user['id']]);
$updatesCount = (int)$stmt->fetchColumn();

// 2. Fetch Active Sponsorships for horizontal carousel
$stmt = $db->prepare("SELECT s.*, b.full_name, b.age, b.location as ben_location, b.photo_url, b.school_grade, b.category,
                             o.org_name,
                             (SELECT title FROM impact_updates iu WHERE iu.beneficiary_id = b.id ORDER BY iu.created_at DESC LIMIT 1) as latest_update_title
                      FROM sponsorships s
                      JOIN beneficiaries b ON s.beneficiary_id = b.id
                      JOIN organizations o ON b.org_id = o.id
                      WHERE s.sponsor_id = ? AND s.status = 'active'
                      ORDER BY s.created_at DESC");
$stmt->execute([$user['id']]);
$activeSponsorships = $stmt->fetchAll();

// 3. Fetch Recent Updates from their sponsored children or overall
$stmt = $db->prepare("SELECT iu.*, b.full_name as ben_name, b.photo_url as ben_photo, o.org_name, o.logo_url as org_logo
                      FROM impact_updates iu
                      JOIN beneficiaries b ON iu.beneficiary_id = b.id
                      JOIN organizations o ON iu.org_id = o.id
                      ORDER BY iu.created_at DESC
                      LIMIT 4");
$stmt->execute();
$recentUpdates = $stmt->fetchAll();

// 4. Fetch Urgent Beneficiaries who need support
$stmt = $db->prepare("SELECT b.*, o.org_name 
                      FROM beneficiaries b
                      JOIN organizations o ON b.org_id = o.id
                      WHERE b.status IN ('available', 'partially_sponsored')
                      ORDER BY b.urgent_flag DESC, b.created_at DESC
                      LIMIT 3");
$stmt->execute();
$urgentBeneficiaries = $stmt->fetchAll();

$page_title = 'Sponsor Dashboard';
$current_page = 'dashboard';
$extra_js = ['dashboard.js'];
require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- Hero Impact Banner Card -->
<div class="app-card bg-gradient-to-br from-blue-700 via-blue-600 to-teal-600 text-white p-5 rounded-2xl border-none shadow-lg mb-5 relative overflow-hidden">
  <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
  <div class="flex items-center justify-between mb-3 relative z-10">
    <span class="text-xs uppercase tracking-wider font-semibold text-blue-100 flex items-center gap-1.5">
      <i class="fa-solid fa-earth-africa"></i> Total Impact Reach
    </span>
    <span class="bg-white/20 text-white text-[11px] font-semibold px-2.5 py-0.5 rounded-full backdrop-blur-sm">Verified</span>
  </div>
  <div class="text-2xl font-black mb-1 relative z-10"><?= format_currency($totalContributions, 'UGX') ?></div>
  <p class="text-xs text-blue-100 mb-4 relative z-10">Your support continues providing schooling, health care, and nutrition.</p>

  <!-- Impact Counter Triplets -->
  <div class="grid grid-cols-3 gap-2 pt-3 border-t border-white/15 relative z-10">
    <div>
      <div class="text-lg font-black leading-tight"><?= $activeCount ?></div>
      <div class="text-[11px] text-blue-100">Children Supported</div>
    </div>
    <div>
      <div class="text-lg font-black leading-tight"><?= $updatesCount ?></div>
      <div class="text-[11px] text-blue-100">Updates Received</div>
    </div>
    <div>
      <div class="text-lg font-black leading-tight">100%</div>
      <div class="text-[11px] text-blue-100">Direct Delivery</div>
    </div>
  </div>
</div>

<!-- Quick Actions Grid -->
<div class="quick-actions-grid mb-6">
  <a href="<?= BASE_URL ?>/sponsor/discover.php" class="quick-action-item">
    <div class="quick-action-icon bg-blue-50 text-blue-600">
      <i class="fa-solid fa-magnifying-glass"></i>
    </div>
    <span class="quick-action-title">Find Child</span>
  </a>

  <a href="<?= BASE_URL ?>/sponsor/sponsorships.php" class="quick-action-item">
    <div class="quick-action-icon bg-emerald-50 text-emerald-600">
      <i class="fa-solid fa-hand-holding-heart"></i>
    </div>
    <span class="quick-action-title">My Support</span>
  </a>

  <a href="<?= BASE_URL ?>/sponsor/updates.php" class="quick-action-item">
    <div class="quick-action-icon bg-amber-50 text-amber-600">
      <i class="fa-solid fa-newspaper"></i>
    </div>
    <span class="quick-action-title">Live Updates</span>
  </a>

  <button type="button" onclick="openModal('quickGiveModal')" class="quick-action-item bg-transparent border-0 cursor-pointer">
    <div class="quick-action-icon bg-teal-50 text-teal-600">
      <i class="fa-solid fa-gift"></i>
    </div>
    <span class="quick-action-title">Quick Give</span>
  </button>
</div>

<!-- Active Sponsorships Section -->
<div class="mb-6">
  <div class="flex items-center justify-between mb-3 px-1">
    <h2 class="text-base font-bold text-slate-900">Your Active Sponsorships</h2>
    <a href="<?= BASE_URL ?>/sponsor/sponsorships.php" class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1">
      See all (<?= count($activeSponsorships) ?>) <i class="fa-solid fa-chevron-right text-[10px]"></i>
    </a>
  </div>

  <?php if (empty($activeSponsorships)): ?>
    <div class="app-card text-center py-6">
      <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3 text-lg">
        <i class="fa-solid fa-hands-holding-child"></i>
      </div>
      <h3 class="font-bold text-slate-800 text-sm">No Active Sponsorships Yet</h3>
      <p class="text-xs text-slate-500 mt-1 mb-4">Choose a child to sponsor and follow their educational milestones.</p>
      <a href="<?= BASE_URL ?>/sponsor/discover.php" class="btn btn-primary btn-sm">Find Someone to Sponsor</a>
    </div>
  <?php else: ?>
    <div class="horizontal-scroll">
      <?php foreach ($activeSponsorships as $sp): ?>
        <div class="horizontal-item">
          <div class="app-card p-4 h-full flex flex-col justify-between hover:shadow-md transition">
            <div>
              <div class="flex items-center gap-3 mb-3">
                <img src="<?= e($sp['photo_url']) ?>" alt="<?= e($sp['full_name']) ?>" class="w-14 h-14 rounded-xl object-cover border border-slate-100 shadow-sm">
                <div>
                  <h3 class="font-bold text-slate-900 text-sm"><?= e($sp['full_name']) ?></h3>
                  <div class="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
                    <i class="fa-solid fa-location-dot text-[10px] text-rose-500"></i> <?= e($sp['ben_location']) ?>
                  </div>
                  <div class="mt-1">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                      <i class="fa-solid fa-circle text-[6px]"></i> Active • <?= ucfirst($sp['frequency']) ?>
                    </span>
                  </div>
                </div>
              </div>

              <div class="bg-slate-50 rounded-lg p-2.5 mb-3 border border-slate-100">
                <div class="text-[11px] font-medium text-slate-500 mb-0.5">Latest Progress Update</div>
                <div class="text-xs font-semibold text-slate-800 line-clamp-1">
                  <?= e($sp['latest_update_title'] ?: 'Enrolled in upcoming school term') ?>
                </div>
              </div>
            </div>

            <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
              <div>
                <div class="text-[10px] text-slate-400 font-medium">Monthly Amount</div>
                <div class="text-xs font-bold text-slate-900"><?= format_currency($sp['amount'], 'UGX') ?></div>
              </div>
              <div class="flex items-center gap-1.5">
                <a href="<?= BASE_URL ?>/sponsor/checkout.php?beneficiary_id=<?= $sp['beneficiary_id'] ?>" class="btn btn-primary btn-sm py-1 px-2.5 text-xs font-bold flex items-center gap-1">
                  <i class="fa-solid fa-heart text-[9px]"></i>
                  <span>Sponsor</span>
                </a>
                <a href="<?= BASE_URL ?>/sponsor/sponsorship-details.php?id=<?= $sp['id'] ?>" class="btn btn-outline btn-sm py-1 px-2 text-xs">
                  <span>Details</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Urgent Needs / Needs a Sponsor -->
<div class="mb-6">
  <div class="flex items-center justify-between mb-3 px-1">
    <div>
      <h2 class="text-base font-bold text-slate-900">Awaiting Sponsors</h2>
      <p class="text-xs text-slate-500">Children with urgent school or medical requirements</p>
    </div>
    <a href="<?= BASE_URL ?>/sponsor/discover.php" class="text-xs font-semibold text-blue-600 hover:text-blue-700">Browse</a>
  </div>

  <div class="space-y-3">
    <?php foreach ($urgentBeneficiaries as $ben): ?>
      <?php $isFav = is_favorite($user['id'], $ben['id']); ?>
      <div class="app-card p-3.5 mb-0 flex items-center justify-between gap-3">
        <a href="<?= BASE_URL ?>/sponsor/beneficiary.php?id=<?= $ben['id'] ?>" class="flex-shrink-0">
          <img src="<?= e($ben['photo_url']) ?>" alt="<?= e($ben['full_name']) ?>" class="w-16 h-16 rounded-xl object-cover hover:opacity-90 transition">
        </a>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-1.5 mb-0.5">
            <a href="<?= BASE_URL ?>/sponsor/beneficiary.php?id=<?= $ben['id'] ?>" class="font-bold text-slate-900 text-sm truncate hover:text-blue-600 transition">
              <?= e($ben['full_name']) ?>
            </a>
            <span class="text-xs text-slate-500 font-medium">(Age <?= $ben['age'] ?>)</span>
          </div>
          <div class="text-xs text-slate-500 truncate mb-1.5 flex items-center gap-1">
            <i class="fa-solid fa-location-dot text-[10px] text-slate-400"></i> <?= e($ben['location']) ?>
          </div>
          <div class="text-xs font-bold text-blue-600"><?= format_currency($ben['monthly_need_amount'], 'UGX') ?> <span class="text-[10px] font-normal text-slate-400">/ mo</span></div>
        </div>
        <div class="flex flex-col items-end gap-2 flex-shrink-0">
          <button type="button" class="favorite-btn-action text-slate-400 hover:text-rose-500 text-base" data-beneficiary-id="<?= $ben['id'] ?>" aria-label="Favorite">
            <i class="<?= $isFav ? 'fa-solid fa-heart text-rose-500' : 'fa-regular fa-heart' ?>"></i>
          </button>
          <a href="<?= BASE_URL ?>/sponsor/checkout.php?beneficiary_id=<?= $ben['id'] ?>" class="btn btn-primary btn-sm text-xs py-1.5 px-3 font-bold flex items-center gap-1 shadow-xs" aria-label="Sponsor <?= e($ben['full_name']) ?>">
            <i class="fa-solid fa-heart text-[10px]"></i>
            <span>Sponsor</span>
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Recent Impact Updates Feed Preview -->
<div class="mb-4">
  <div class="flex items-center justify-between mb-3 px-1">
    <h2 class="text-base font-bold text-slate-900">Recent Milestones</h2>
    <a href="<?= BASE_URL ?>/sponsor/updates.php" class="text-xs font-semibold text-blue-600 hover:text-blue-700">View All</a>
  </div>

  <div class="space-y-3">
    <?php foreach ($recentUpdates as $up): ?>
      <div class="app-card p-4 mb-0">
        <div class="flex items-center justify-between mb-2.5">
          <div class="flex items-center gap-2">
            <img src="<?= e($up['ben_photo']) ?>" alt="<?= e($up['ben_name']) ?>" class="w-8 h-8 rounded-full object-cover">
            <div>
              <div class="text-xs font-bold text-slate-900"><?= e($up['ben_name']) ?></div>
              <div class="text-[10px] text-slate-500"><?= e($up['org_name']) ?></div>
            </div>
          </div>
          <span class="text-[10px] text-slate-400"><?= time_elapsed_string($up['created_at']) ?></span>
        </div>

        <?php if (!empty($up['image_url'])): ?>
          <div class="rounded-xl overflow-hidden mb-2.5 h-36 bg-slate-100">
            <img src="<?= e($up['image_url']) ?>" alt="<?= e($up['title']) ?>" class="w-full h-full object-cover">
          </div>
        <?php endif; ?>

        <h3 class="font-bold text-slate-900 text-sm mb-1"><?= e($up['title']) ?></h3>
        <p class="text-xs text-slate-600 line-clamp-2 mb-3"><?= e($up['content']) ?></p>

        <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs text-slate-500">
          <span class="inline-flex items-center gap-1.5 text-blue-600 font-semibold text-xs">
            <i class="fa-solid fa-award"></i> <?= ucfirst($up['update_type']) ?> Update
          </span>
          <a href="<?= BASE_URL ?>/sponsor/updates.php" class="text-xs font-semibold text-slate-700 hover:text-blue-600">
            Read Full <i class="fa-solid fa-arrow-right text-[10px]"></i>
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Quick Give Modal (Bottom Sheet) -->
<div id="quickGiveModal" class="modal-overlay" onclick="if(event.target === this) closeModal('quickGiveModal')">
  <div class="bottom-sheet">
    <div class="sheet-handle"></div>
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="text-base font-bold text-slate-900">Make an Immediate Donation</h3>
        <p class="text-xs text-slate-500">Give directly to the general child welfare fund</p>
      </div>
      <button type="button" onclick="closeModal('quickGiveModal')" class="text-slate-400 hover:text-slate-600 p-1">
        <i class="fa-solid fa-xmark text-lg"></i>
      </button>
    </div>

    <form action="<?= BASE_URL ?>/sponsor/checkout.php" method="GET">
      <input type="hidden" name="type" value="general">
      <div class="grid grid-cols-3 gap-2 mb-4">
        <button type="button" class="donate-amount-pill p-2.5 text-xs font-bold rounded-xl border border-slate-200 text-slate-700 active" data-amount="50000">UGX 50k</button>
        <button type="button" class="donate-amount-pill p-2.5 text-xs font-bold rounded-xl border border-slate-200 text-slate-700" data-amount="100000">UGX 100k</button>
        <button type="button" class="donate-amount-pill p-2.5 text-xs font-bold rounded-xl border border-slate-200 text-slate-700" data-amount="200000">UGX 200k</button>
      </div>

      <div class="form-group mb-5">
        <label class="form-label">Custom Amount (UGX)</label>
        <div class="input-wrapper">
          <span class="input-icon font-bold text-xs">UGX</span>
          <input type="number" id="customDonateAmount" name="amount" class="form-input has-left-icon" value="50000" min="5000" step="5000">
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block">
        <span>Proceed to Payment</span>
        <i class="fa-solid fa-arrow-right text-xs"></i>
      </button>
    </form>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
