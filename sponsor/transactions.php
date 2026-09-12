<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user = require_auth();
$db = get_db();

// Total lifetime contributions
$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE sponsor_id = ? AND status = 'completed'");
$stmt->execute([$user['id']]);
$totalContributed = (float)$stmt->fetchColumn();

// Fetch transactions
$stmt = $db->prepare("SELECT t.*, b.full_name as ben_name, b.photo_url as ben_photo
                      FROM transactions t
                      LEFT JOIN sponsorships s ON t.sponsorship_id = s.id
                      LEFT JOIN beneficiaries b ON s.beneficiary_id = b.id
                      WHERE t.sponsor_id = ?
                      ORDER BY t.created_at DESC");
$stmt->execute([$user['id']]);
$transactions = $stmt->fetchAll();

$page_title = 'Payment History';
$show_back = true;
$back_url = BASE_URL . '/sponsor/profile.php';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- Total Paid Summary -->
<div class="app-card bg-slate-900 text-white p-4 mb-4">
  <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Lifetime Giving</span>
  <div class="text-2xl font-black text-white mt-0.5"><?= format_currency($totalContributed, 'UGX') ?></div>
  <div class="text-xs text-slate-400 mt-1 flex items-center gap-1">
    <i class="fa-solid fa-circle-check text-emerald-400 text-xs"></i> All transactions verified & audited
  </div>
</div>

<div class="flex items-center justify-between mb-3 px-1">
  <h3 class="font-bold text-slate-900 text-sm">Statements & Receipts (<?= count($transactions) ?>)</h3>
</div>

<?php if (empty($transactions)): ?>
  <div class="app-card text-center py-10">
    <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-xl">
      <i class="fa-solid fa-receipt"></i>
    </div>
    <h3 class="font-bold text-slate-800 mb-1">No Transactions Found</h3>
    <p class="text-xs text-slate-500 mb-4">You have not completed any payments yet.</p>
    <a href="<?= BASE_URL ?>/sponsor/discover.php" class="btn btn-primary btn-sm">Find Someone to Sponsor</a>
  </div>
<?php else: ?>
  <div class="space-y-2.5">
    <?php foreach ($transactions as $tx): ?>
      <div class="app-card p-3.5 mb-0 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm flex-shrink-0">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
          </div>
          <div class="min-w-0">
            <div class="text-xs font-bold text-slate-900 truncate">
              <?= e($tx['ben_name'] ? 'Sponsorship: ' . $tx['ben_name'] : 'General Child Support') ?>
            </div>
            <div class="text-[10px] text-slate-500 mt-0.5">
              <?= date('d M Y, H:i', strtotime($tx['created_at'])) ?> • <?= e($tx['payment_method']) ?>
            </div>
            <div class="text-[10px] font-mono text-slate-400">Ref: <?= e($tx['transaction_ref']) ?></div>
          </div>
        </div>

        <div class="text-right flex-shrink-0">
          <div class="text-xs font-black text-slate-900"><?= format_currency($tx['amount'], $tx['currency']) ?></div>
          <a href="<?= BASE_URL ?>/sponsor/receipt.php?id=<?= $tx['id'] ?>" class="text-[11px] font-bold text-blue-600 hover:text-blue-700 block mt-1">
            Receipt <i class="fa-solid fa-chevron-right text-[8px]"></i>
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
