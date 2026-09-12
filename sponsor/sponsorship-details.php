<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user = require_auth();
$db = get_db();

$sponsorshipId = (int)($_GET['id'] ?? 0);
if (!$sponsorshipId) {
    header("Location: " . BASE_URL . "/sponsor/sponsorships.php");
    exit;
}

// Handle actions (pause, resume, cancel)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (verify_csrf_token($csrf)) {
        if ($action === 'pause') {
            $stmt = $db->prepare("UPDATE sponsorships SET status = 'paused' WHERE id = ? AND sponsor_id = ?");
            $stmt->execute([$sponsorshipId, $user['id']]);
            set_flash('info', 'Sponsorship paused temporarily.');
        } elseif ($action === 'resume') {
            $checkSp = $db->prepare("SELECT status FROM sponsorships WHERE id = ? AND sponsor_id = ?");
            $checkSp->execute([$sponsorshipId, $user['id']]);
            $currentStatus = $checkSp->fetchColumn();
            
            if ($currentStatus === 'paused') {
                $stmt = $db->prepare("UPDATE sponsorships SET status = 'active' WHERE id = ? AND sponsor_id = ?");
                $stmt->execute([$sponsorshipId, $user['id']]);
                set_flash('success', 'Sponsorship resumed successfully.');
            } else {
                set_flash('error', 'Only paused sponsorships can be resumed.');
            }
        } elseif ($action === 'cancel') {
            $spCheck = $db->prepare("SELECT amount, beneficiary_id, status FROM sponsorships WHERE id = ? AND sponsor_id = ?");
            $spCheck->execute([$sponsorshipId, $user['id']]);
            $spData = $spCheck->fetch();

            if ($spData && $spData['status'] !== 'cancelled') {
                $db->beginTransaction();
                try {
                    $stmt = $db->prepare("UPDATE sponsorships SET status = 'cancelled', end_date = CURRENT_DATE, auto_renew = 0 WHERE id = ? AND sponsor_id = ?");
                    $stmt->execute([$sponsorshipId, $user['id']]);

                    $upBen = $db->prepare("UPDATE beneficiaries SET current_supported_amount = MAX(0, current_supported_amount - ?), 
                                           status = CASE 
                                                WHEN current_supported_amount - ? <= 0 THEN 'available' 
                                                WHEN current_supported_amount - ? < monthly_need_amount THEN 'partially_sponsored' 
                                                ELSE 'fully_sponsored' 
                                           END 
                                           WHERE id = ?");
                    $upBen->execute([$spData['amount'], $spData['amount'], $spData['amount'], $spData['beneficiary_id']]);
                    
                    $db->commit();
                    set_flash('warning', 'Sponsorship has been permanently cancelled.');
                } catch (Exception $e) {
                    $db->rollBack();
                    set_flash('error', 'An error occurred while cancelling the sponsorship.');
                }
            }
        }
        header("Location: " . BASE_URL . "/sponsor/sponsorship-details.php?id=" . $sponsorshipId);
        exit;
    }
}

$stmt = $db->prepare("SELECT s.*, b.id as ben_id, b.full_name, b.age, b.location as ben_loc, b.photo_url, b.school_grade, b.category,
                             o.org_name, o.id as org_id, o.logo_url as org_logo
                      FROM sponsorships s
                      JOIN beneficiaries b ON s.beneficiary_id = b.id
                      JOIN organizations o ON b.org_id = o.id
                      WHERE s.id = ? AND s.sponsor_id = ?");
$stmt->execute([$sponsorshipId, $user['id']]);
$sp = $stmt->fetch();

if (!$sp) {
    header("Location: " . BASE_URL . "/sponsor/sponsorships.php");
    exit;
}

// Transactions for this sponsorship
$stmt = $db->prepare("SELECT * FROM transactions WHERE sponsorship_id = ? ORDER BY created_at DESC");
$stmt->execute([$sponsorshipId]);
$transactions = $stmt->fetchAll();

// Updates for this child
$stmt = $db->prepare("SELECT * FROM impact_updates WHERE beneficiary_id = ? ORDER BY created_at DESC");
$stmt->execute([$sp['ben_id']]);
$updates = $stmt->fetchAll();

$page_title = 'Manage Sponsorship';
$show_back = true;
$back_url = BASE_URL . '/sponsor/sponsorships.php';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- Child Summary Header -->
<div class="app-card p-4 mb-4 flex items-center gap-3.5">
  <img src="<?= e($sp['photo_url']) ?>" alt="<?= e($sp['full_name']) ?>" class="w-16 h-16 rounded-2xl object-cover shadow-sm">
  <div class="min-w-0 flex-1">
    <div class="flex items-center justify-between">
      <h2 class="font-black text-slate-900 text-base truncate"><?= e($sp['full_name']) ?></h2>
      <?= render_status_badge($sp['status']) ?>
    </div>
    <div class="text-xs text-slate-500 mt-0.5">Age <?= $sp['age'] ?> • <?= e($sp['ben_loc']) ?></div>
    <div class="text-xs text-blue-600 font-semibold mt-1">Supervised by <?= e($sp['org_name']) ?></div>
  </div>
</div>

<!-- Plan Metrics & Next Renewal -->
<div class="app-card mb-4">
  <h3 class="card-title text-sm mb-3">Sponsorship Overview</h3>
  <div class="grid grid-cols-2 gap-3 text-xs mb-4">
    <div class="bg-slate-50 p-2.5 rounded-xl">
      <span class="text-[10px] text-slate-400 block font-medium">Committed Amount</span>
      <strong class="text-sm text-slate-900"><?= format_currency($sp['amount'], 'UGX') ?></strong>
      <span class="text-[10px] text-slate-500 block">per <?= $sp['frequency'] ?></span>
    </div>
    <div class="bg-slate-50 p-2.5 rounded-xl">
      <span class="text-[10px] text-slate-400 block font-medium">Total Contributed</span>
      <strong class="text-sm text-emerald-600"><?= format_currency($sp['total_paid'], 'UGX') ?></strong>
      <span class="text-[10px] text-slate-500 block">since <?= date('M Y', strtotime($sp['start_date'])) ?></span>
    </div>
  </div>

  <div class="space-y-2 text-xs text-slate-600 border-t border-slate-100 pt-3">
    <div class="flex items-center justify-between">
      <span>Payment Method</span>
      <span class="font-bold text-slate-800"><?= e($sp['payment_method']) ?></span>
    </div>
    <div class="flex items-center justify-between">
      <span>Next Scheduled Contribution</span>
      <span class="font-bold text-slate-800"><?= $sp['next_payment_date'] ? date('d M Y', strtotime($sp['next_payment_date'])) : 'N/A' ?></span>
    </div>
    <div class="flex items-center justify-between">
      <span>Auto Renewal</span>
      <span class="text-emerald-600 font-bold"><?= $sp['auto_renew'] ? 'Enabled' : 'Manual' ?></span>
    </div>
  </div>
</div>

<!-- Management Action Sheet Trigger -->
<div class="app-card mb-4">
  <h3 class="card-title text-sm mb-3">Management Options</h3>
  <div class="space-y-2">
    <a href="<?= BASE_URL ?>/sponsor/messages.php?org_id=<?= $sp['org_id'] ?>&beneficiary_id=<?= $sp['ben_id'] ?>" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 border border-slate-100 text-xs font-semibold text-slate-800">
      <div class="flex items-center gap-2.5">
        <i class="fa-regular fa-comment-dots text-blue-600 text-sm"></i>
        <span>Message Organization Coordinator</span>
      </div>
      <i class="fa-solid fa-chevron-right text-slate-400 text-[10px]"></i>
    </a>

    <?php if ($sp['status'] !== 'cancelled'): ?>
    <a href="<?= BASE_URL ?>/sponsor/checkout.php?beneficiary_id=<?= $sp['ben_id'] ?>" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 border border-slate-100 text-xs font-semibold text-slate-800">
      <div class="flex items-center gap-2.5">
        <i class="fa-solid fa-circle-plus text-emerald-600 text-sm"></i>
        <span>Make Extra Gift Contribution</span>
      </div>
      <i class="fa-solid fa-chevron-right text-slate-400 text-[10px]"></i>
    </a>

    <button type="button" onclick="openModal('manageStatusModal')" class="w-full flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 border border-slate-100 text-xs font-semibold text-slate-800">
      <div class="flex items-center gap-2.5">
        <i class="fa-solid fa-gear text-slate-600 text-sm"></i>
        <span>Pause or Cancel Sponsorship</span>
      </div>
      <i class="fa-solid fa-chevron-right text-slate-400 text-[10px]"></i>
    </button>
    <?php endif; ?>
  </div>
</div>

<!-- Impact Milestones for this child -->
<div class="mb-4">
  <div class="flex items-center justify-between mb-2 px-1">
    <h3 class="font-bold text-slate-900 text-sm">Updates & Letters (<?= count($updates) ?>)</h3>
    <a href="<?= BASE_URL ?>/sponsor/updates.php" class="text-xs font-semibold text-blue-600">Feed</a>
  </div>

  <?php if (empty($updates)): ?>
    <div class="app-card text-center py-4 text-xs text-slate-500">
      No updates posted for this child yet.
    </div>
  <?php else: ?>
    <div class="space-y-3">
      <?php foreach ($updates as $up): ?>
        <div class="app-card p-3.5 mb-0">
          <div class="flex items-center justify-between mb-1.5">
            <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600"><?= ucfirst($up['update_type']) ?></span>
            <span class="text-[10px] text-slate-400"><?= date('d M Y', strtotime($up['created_at'])) ?></span>
          </div>
          <h4 class="font-bold text-slate-900 text-xs mb-1"><?= e($up['title']) ?></h4>
          <p class="text-xs text-slate-600 line-clamp-2"><?= e($up['content']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Payment History for this sponsorship -->
<div class="mb-6">
  <h3 class="font-bold text-slate-900 text-sm mb-2 px-1">Payment History</h3>
  <div class="space-y-2">
    <?php foreach ($transactions as $tx): ?>
      <div class="app-card p-3 mb-0 flex items-center justify-between">
        <div>
          <div class="text-xs font-bold text-slate-900"><?= format_currency($tx['amount'], 'UGX') ?></div>
          <div class="text-[10px] text-slate-400"><?= date('d M Y', strtotime($tx['created_at'])) ?> • <?= e($tx['payment_method']) ?></div>
        </div>
        <a href="<?= BASE_URL ?>/sponsor/receipt.php?id=<?= $tx['id'] ?>" class="btn btn-outline btn-sm text-[11px] py-1 px-2.5">
          Receipt
        </a>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php if ($sp['status'] !== 'cancelled'): ?>
<!-- Status Manage Modal (Pause / Cancel) -->
<div id="manageStatusModal" class="modal-overlay" onclick="if(event.target === this) closeModal('manageStatusModal')">
  <div class="bottom-sheet">
    <div class="sheet-handle"></div>
    <h3 class="text-base font-bold text-slate-900 mb-1">Manage Status</h3>
    <p class="text-xs text-slate-500 mb-4">You can pause your payments without forfeiting your connection.</p>

    <form method="POST" action="<?= BASE_URL ?>/sponsor/sponsorship-details.php?id=<?= $sp['id'] ?>" class="space-y-3">
      <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
      <input type="hidden" name="app_sid" value="<?= session_id() ?>">

      <?php if ($sp['status'] === 'active'): ?>
        <input type="hidden" name="action" value="pause">
        <button type="submit" class="btn btn-outline btn-block text-amber-600 border-amber-300 hover:bg-amber-50">
          <i class="fa-solid fa-pause"></i> Pause Monthly Giving
        </button>
      <?php elseif ($sp['status'] === 'paused'): ?>
        <input type="hidden" name="action" value="resume">
        <button type="submit" class="btn btn-primary btn-block">
          <i class="fa-solid fa-play"></i> Resume Sponsorship
        </button>
      <?php endif; ?>
    </form>

    <form method="POST" action="<?= BASE_URL ?>/sponsor/sponsorship-details.php?id=<?= $sp['id'] ?>" class="mt-3" onsubmit="return confirm('Are you sure you want to cancel this sponsorship?');">
      <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
      <input type="hidden" name="app_sid" value="<?= session_id() ?>">
      <input type="hidden" name="action" value="cancel">
      <button type="submit" class="w-full text-center text-xs font-semibold text-rose-600 hover:underline p-2">
        Cancel Sponsorship Permanently
      </button>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
