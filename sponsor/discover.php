<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user = require_auth();
$db = get_db();

// Default query
$stmt = $db->prepare("SELECT b.*, o.org_name 
                      FROM beneficiaries b
                      JOIN organizations o ON b.org_id = o.id
                      ORDER BY b.urgent_flag DESC, b.created_at DESC");
$stmt->execute();
$beneficiaries = $stmt->fetchAll();

$page_title = 'Discover Beneficiaries';
$current_page = 'discover';
$extra_js = ['discover.js'];
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="mb-4">
  <!-- Search Input Bar -->
  <div class="relative mb-3">
    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
    <input type="text" id="discoverSearchInput" class="w-full h-11 bg-white border border-slate-200 rounded-xl pl-10 pr-10 text-sm placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 shadow-sm" placeholder="Search by name, location, school...">
    <button type="button" onclick="openModal('filterModal')" class="absolute right-2 top-1/2 -translate-y-1/2 w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600 hover:bg-slate-200 text-xs" aria-label="Filters">
      <i class="fa-solid fa-sliders"></i>
    </button>
  </div>

  <!-- Filter Chips Scroll -->
  <div class="filter-chips-scroll">
    <button type="button" class="filter-chip active" data-category="all">
      <i class="fa-solid fa-layer-group text-xs"></i> All Needs
    </button>
    <button type="button" class="filter-chip" data-category="Education">
      <i class="fa-solid fa-graduation-cap text-xs"></i> Education
    </button>
    <button type="button" class="filter-chip" data-category="Healthcare">
      <i class="fa-solid fa-heart-pulse text-xs"></i> Healthcare
    </button>
    <button type="button" class="filter-chip" data-category="Nutrition">
      <i class="fa-solid fa-bowl-food text-xs"></i> Nutrition
    </button>
    <button type="button" class="filter-chip" data-category="urgent">
      <i class="fa-solid fa-bell text-xs text-rose-500"></i> Urgent Needs
    </button>
  </div>
</div>

<!-- Beneficiaries List Container -->
<div id="beneficiaryListContainer" class="space-y-4">
  <?php foreach ($beneficiaries as $ben): ?>
    <?php 
      $isFav = is_favorite($user['id'], $ben['id']);
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

        <button type="button" class="favorite-btn-floating favorite-btn-action <?= $isFav ? 'active' : '' ?>" data-beneficiary-id="<?= $ben['id'] ?>" aria-label="Add to favorites">
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

        <!-- Progress bar -->
        <div class="mb-3">
          <div class="flex items-center justify-between text-[11px] text-slate-500 mb-1">
            <span>Sponsorship Goal: <strong class="text-slate-800"><?= format_currency($ben['monthly_need_amount'], 'UGX') ?></strong>/mo</span>
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
  <?php endforeach; ?>
</div>

<!-- Advanced Filter Modal -->
<div id="filterModal" class="modal-overlay" onclick="if(event.target === this) closeModal('filterModal')">
  <div class="bottom-sheet">
    <div class="sheet-handle"></div>
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-base font-bold text-slate-900">Filter Beneficiaries</h3>
      <button type="button" onclick="closeModal('filterModal')" class="text-slate-400 hover:text-slate-600 p-1">
        <i class="fa-solid fa-xmark text-lg"></i>
      </button>
    </div>

    <form id="advancedFilterForm" onsubmit="event.preventDefault(); closeModal('filterModal');">
      <div class="form-group mb-4">
        <label class="form-label">Gender</label>
        <div class="grid grid-cols-3 gap-2">
          <label class="border border-slate-200 rounded-xl p-2 text-center text-xs font-semibold has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-600 cursor-pointer">
            <input type="radio" name="gender" value="" class="hidden" checked> All
          </label>
          <label class="border border-slate-200 rounded-xl p-2 text-center text-xs font-semibold has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-600 cursor-pointer">
            <input type="radio" name="gender" value="Male" class="hidden"> Boys
          </label>
          <label class="border border-slate-200 rounded-xl p-2 text-center text-xs font-semibold has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-600 cursor-pointer">
            <input type="radio" name="gender" value="Female" class="hidden"> Girls
          </label>
        </div>
      </div>

      <div class="form-group mb-5">
        <label class="form-label">Location / Region</label>
        <select name="location" class="form-input text-sm">
          <option value="">All Regions in Uganda</option>
          <option value="Kampala">Kampala & Suburbs</option>
          <option value="Jinja">Jinja / Busoga Region</option>
          <option value="Wakiso">Wakiso District</option>
        </select>
      </div>

      <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
    </form>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
