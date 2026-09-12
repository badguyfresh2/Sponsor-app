<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user = require_auth();
$db = get_db();

$txId = (int)($_GET['id'] ?? 0);
if (!$txId) {
    header("Location: " . BASE_URL . "/sponsor/transactions.php");
    exit;
}

$stmt = $db->prepare("SELECT t.*, b.full_name as ben_name, b.location as ben_loc, o.org_name, o.verified as org_verified
                      FROM transactions t
                      LEFT JOIN sponsorships s ON t.sponsorship_id = s.id
                      LEFT JOIN beneficiaries b ON s.beneficiary_id = b.id
                      LEFT JOIN organizations o ON b.org_id = o.id
                      WHERE t.id = ? AND t.sponsor_id = ?");
$stmt->execute([$txId, $user['id']]);
$tx = $stmt->fetch();

if (!$tx) {
    header("Location: " . BASE_URL . "/sponsor/transactions.php");
    exit;
}

$page_title = 'Official Receipt';
$show_back = true;
$back_url = BASE_URL . '/sponsor/transactions.php';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="space-y-4">
  <!-- Printable Receipt Container -->
  <div class="app-card border-slate-200 p-5 bg-white relative overflow-hidden shadow-md">
    <!-- Watermark / Stamp -->
    <div class="absolute right-4 top-4 border-2 border-emerald-500/40 text-emerald-600 rounded-lg px-2.5 py-1 text-[11px] font-black tracking-widest uppercase rotate-6 pointer-events-none select-none">
      <i class="fa-solid fa-stamp mr-1"></i> VERIFIED
    </div>

    <!-- Header info -->
    <div class="mb-5 pb-4 border-b border-dashed border-slate-200">
      <div class="flex items-center gap-2 mb-2">
        <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-sm">
          <i class="fa-solid fa-hand-holding-heart"></i>
        </div>
        <span class="font-black text-slate-900 text-sm tracking-tight"><?= APP_NAME ?></span>
      </div>
      <div class="text-[11px] text-slate-400">Electronic Sponsorship Contribution Voucher</div>
      <div class="text-xs font-mono font-bold text-slate-800 mt-1">Receipt: <?= e($tx['receipt_number']) ?></div>
    </div>

    <!-- Details Grid -->
    <div class="space-y-3 text-xs mb-5">
      <div class="flex items-center justify-between">
        <span class="text-slate-500">Date & Time</span>
        <span class="font-semibold text-slate-900"><?= date('d M Y, H:i', strtotime($tx['created_at'])) ?></span>
      </div>

      <div class="flex items-center justify-between">
        <span class="text-slate-500">Transaction Ref</span>
        <span class="font-mono text-slate-700"><?= e($tx['transaction_ref']) ?></span>
      </div>

      <div class="flex items-center justify-between">
        <span class="text-slate-500">Payment Channel</span>
        <span class="font-semibold text-slate-900"><?= e($tx['payment_method']) ?></span>
      </div>

      <div class="flex items-center justify-between">
        <span class="text-slate-500">Sponsor Name</span>
        <span class="font-semibold text-slate-900"><?= e($user['name']) ?></span>
      </div>

      <div class="flex items-center justify-between">
        <span class="text-slate-500">Beneficiary / Fund</span>
        <span class="font-bold text-blue-600"><?= e($tx['ben_name'] ?: 'General Welfare Fund') ?></span>
      </div>

      <?php if (!empty($tx['org_name'])): ?>
        <div class="flex items-center justify-between">
          <span class="text-slate-500">Supervising NGO</span>
          <span class="font-semibold text-slate-900"><?= e($tx['org_name']) ?></span>
        </div>
      <?php endif; ?>
    </div>

    <!-- Amount Breakdown -->
    <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100 mb-5">
      <div class="flex items-center justify-between text-xs text-slate-500 mb-1.5">
        <span>Subtotal</span>
        <span><?= format_currency($tx['amount'], $tx['currency']) ?></span>
      </div>
      <div class="flex items-center justify-between text-xs text-slate-500 mb-2">
        <span>Processing Fee (Covered by Platform)</span>
        <span class="text-emerald-600 font-semibold">UGX 0</span>
      </div>
      <div class="pt-2 border-t border-slate-200 flex items-center justify-between">
        <span class="text-xs font-bold text-slate-900">Total Paid</span>
        <span class="text-base font-black text-slate-900"><?= format_currency($tx['amount'], $tx['currency']) ?></span>
      </div>
    </div>

    <div class="text-[11px] text-slate-400 text-center leading-relaxed">
      Thank you for your generous contribution. 100% of these funds are directed towards education, nutrition, and medical support.
    </div>
  </div>

  <!-- Actions -->
  <div class="flex items-center gap-2">
    <button type="button" onclick="window.print()" class="btn btn-outline flex-1">
      <i class="fa-solid fa-print"></i>
      <span>Print / PDF</span>
    </button>
    <button type="button" onclick="if(navigator.share){navigator.share({title:'Sponsorship Receipt', text:'My sponsorship contribution receipt: <?= $tx['receipt_number'] ?>', url:window.location.href});} else {showToast('Receipt link copied!','fa-copy');}" class="btn btn-primary flex-1">
      <i class="fa-solid fa-share-nodes"></i>
      <span>Share</span>
    </button>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
