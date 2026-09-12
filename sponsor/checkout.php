<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user = require_auth();
$db = get_db();

$beneficiaryId = (int)($_GET['beneficiary_id'] ?? $_POST['beneficiary_id'] ?? 0);
$type = $_GET['type'] ?? 'beneficiary';
$error = '';

$beneficiary = null;
if ($beneficiaryId > 0) {
    $stmt = $db->prepare("SELECT b.*, o.org_name FROM beneficiaries b JOIN organizations o ON b.org_id = o.id WHERE b.id = ?");
    $stmt->execute([$beneficiaryId]);
    $beneficiary = $stmt->fetch();
}

$suggestedAmount = $beneficiary ? $beneficiary['monthly_need_amount'] : 50000;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)($_POST['amount'] ?? 0);
    $frequency = $_POST['frequency'] ?? 'monthly';
    $paymentMethod = $_POST['payment_method'] ?? 'MTN Mobile Money';
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf)) {
        $error = 'Security session expired. Please try again.';
    } elseif ($amount < 5000) {
        $error = 'Minimum sponsorship amount is UGX 5,000.';
    } else {
        try {
            $db->beginTransaction();

            $nextPaymentDate = date('Y-m-d', strtotime('+1 month'));
            $sponsorshipId = null;

            if ($beneficiaryId > 0) {
                // Check if already sponsoring
                $check = $db->prepare("SELECT id, total_paid FROM sponsorships WHERE sponsor_id = ? AND beneficiary_id = ? AND status = 'active'");
                $check->execute([$user['id'], $beneficiaryId]);
                $existing = $check->fetch();

                if ($existing) {
                    $sponsorshipId = $existing['id'];
                    $upSp = $db->prepare("UPDATE sponsorships SET amount = ?, frequency = ?, total_paid = total_paid + ?, payment_method = ?, next_payment_date = ? WHERE id = ?");
                    $upSp->execute([$amount, $frequency, $amount, $paymentMethod, $nextPaymentDate, $sponsorshipId]);
                } else {
                    $insSp = $db->prepare("INSERT INTO sponsorships (sponsor_id, beneficiary_id, amount, frequency, start_date, status, auto_renew, payment_method, next_payment_date, total_paid) 
                                          VALUES (?, ?, ?, ?, CURRENT_DATE, 'active', 1, ?, ?, ?)");
                    $insSp->execute([$user['id'], $beneficiaryId, $amount, $frequency, $paymentMethod, $nextPaymentDate, $amount]);
                    $sponsorshipId = $db->lastInsertId();
                }

                // Update beneficiary current support
                $upBen = $db->prepare("UPDATE beneficiaries SET current_supported_amount = current_supported_amount + ?, 
                                       status = CASE WHEN current_supported_amount + ? >= monthly_need_amount THEN 'fully_sponsored' ELSE 'partially_sponsored' END 
                                       WHERE id = ?");
                $upBen->execute([$amount, $amount, $beneficiaryId]);
            }

            // Create Transaction Record
            $txRef = 'TXN-' . date('Ym') . '-' . rand(1000, 9999);
            $rcptNum = 'REC-' . date('Ym') . '-' . rand(100, 999);
            $notes = $beneficiary ? "Sponsorship contribution for " . $beneficiary['full_name'] : "General child welfare sponsorship fund";

            $insTx = $db->prepare("INSERT INTO transactions (sponsorship_id, sponsor_id, amount, currency, payment_method, transaction_ref, receipt_number, status, notes) 
                                   VALUES (?, ?, ?, 'UGX', ?, ?, ?, 'completed', ?)");
            $insTx->execute([$sponsorshipId, $user['id'], $amount, $paymentMethod, $txRef, $rcptNum, $notes]);
            $transId = $db->lastInsertId();

            // Create Notification
            $notifTitle = "Sponsorship Payment Confirmed";
            $notifMsg = "Your contribution of " . format_currency($amount, 'UGX') . " via " . $paymentMethod . " was completed successfully.";
            $insNotif = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link_url, is_read) VALUES (?, ?, ?, 'payment', '/sponsor/transactions.php', 0)");
            $insNotif->execute([$user['id'], $notifTitle, $notifMsg]);

            $db->commit();

            set_flash('success', 'Thank you! Your sponsorship has been processed successfully.');
            if ($sponsorshipId) {
                header("Location: " . BASE_URL . "/sponsor/sponsorship-details.php?id=" . $sponsorshipId);
            } else {
                header("Location: " . BASE_URL . "/sponsor/transactions.php");
            }
            exit;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $error = 'Payment processing error: ' . $e->getMessage();
        }
    }
}

$page_title = 'Confirm Sponsorship';
$show_back = true;
$back_url = $beneficiary ? (BASE_URL . '/sponsor/beneficiary.php?id=' . $beneficiary['id']) : (BASE_URL . '/sponsor/dashboard.php');
$hide_bottom_nav = true;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="space-y-4">
  <?php if (!empty($error)): ?>
    <div class="flash-banner error">
      <i class="fa-solid fa-circle-exclamation"></i>
      <span><?= e($error) ?></span>
    </div>
  <?php endif; ?>

  <!-- Beneficiary Summary Card -->
  <?php if ($beneficiary): ?>
    <div class="app-card p-3.5 flex items-center gap-3">
      <img src="<?= e($beneficiary['photo_url']) ?>" alt="<?= e($beneficiary['full_name']) ?>" class="w-14 h-14 rounded-xl object-cover flex-shrink-0">
      <div class="min-w-0 flex-1">
        <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600">Sponsoring Child</span>
        <h2 class="text-sm font-bold text-slate-900 truncate"><?= e($beneficiary['full_name']) ?></h2>
        <p class="text-xs text-slate-500"><?= e($beneficiary['location']) ?> • Age <?= $beneficiary['age'] ?></p>
      </div>
    </div>
  <?php else: ?>
    <div class="app-card p-3.5 flex items-center gap-3">
      <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg flex-shrink-0">
        <i class="fa-solid fa-gift"></i>
      </div>
      <div>
        <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600">General Support</span>
        <h2 class="text-sm font-bold text-slate-900">Child Welfare & Education Fund</h2>
        <p class="text-xs text-slate-500">Distributed to highest-need schools & clinics</p>
      </div>
    </div>
  <?php endif; ?>

  <!-- Checkout Form -->
  <form method="POST" action="<?= BASE_URL ?>/sponsor/checkout.php" class="space-y-4" id="checkoutForm">
    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
    <input type="hidden" name="app_sid" value="<?= session_id() ?>">
    <input type="hidden" name="beneficiary_id" value="<?= $beneficiaryId ?>">

    <!-- Step 1: Frequency -->
    <div class="app-card">
      <label class="form-label mb-2">Contribution Frequency</label>
      <div class="grid grid-cols-2 gap-2">
        <label class="border border-slate-200 rounded-xl p-3 text-center cursor-pointer has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-600">
          <input type="radio" name="frequency" value="monthly" class="hidden" checked>
          <div class="font-bold text-xs">Monthly Ongoing</div>
          <div class="text-[10px] text-slate-400">Regular school continuity</div>
        </label>
        <label class="border border-slate-200 rounded-xl p-3 text-center cursor-pointer has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-600">
          <input type="radio" name="frequency" value="one_time" class="hidden">
          <div class="font-bold text-xs">One-Time Gift</div>
          <div class="text-[10px] text-slate-400">Immediate assistance</div>
        </label>
      </div>
    </div>

    <!-- Step 2: Amount Selection -->
    <div class="app-card">
      <label class="form-label mb-2">Select Amount (UGX)</label>
      <div class="grid grid-cols-3 gap-2 mb-3">
        <button type="button" class="amount-btn border border-slate-200 rounded-xl py-2 text-xs font-bold text-slate-700 hover:border-blue-600" data-amt="50000">50,000</button>
        <button type="button" class="amount-btn border border-slate-200 rounded-xl py-2 text-xs font-bold text-slate-700 hover:border-blue-600" data-amt="100000">100,000</button>
        <button type="button" class="amount-btn border-2 border-blue-600 bg-blue-50 text-blue-600 rounded-xl py-2 text-xs font-bold" data-amt="<?= $suggestedAmount ?>"><?= number_format($suggestedAmount, 0) ?></button>
      </div>

      <div class="input-wrapper">
        <span class="input-icon text-xs font-bold text-slate-500">UGX</span>
        <input type="number" id="sponsorshipAmountInput" name="amount" class="form-input has-left-icon font-bold text-slate-900" value="<?= $suggestedAmount ?>" min="5000" step="5000" required>
      </div>
    </div>

    <!-- Step 3: Payment Method -->
    <div class="app-card">
      <label class="form-label mb-2">Payment Channel</label>
      <div class="space-y-2">
        <label class="flex items-center justify-between p-3 rounded-xl border border-slate-200 cursor-pointer has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/50">
          <div class="flex items-center gap-3">
            <input type="radio" name="payment_method" value="MTN Mobile Money" class="text-blue-600" checked>
            <div>
              <div class="text-xs font-bold text-slate-900">MTN Mobile Money (MoMo)</div>
              <div class="text-[10px] text-slate-500">Prompt will appear on phone</div>
            </div>
          </div>
          <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded">Instant</span>
        </label>

        <label class="flex items-center justify-between p-3 rounded-xl border border-slate-200 cursor-pointer has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/50">
          <div class="flex items-center gap-3">
            <input type="radio" name="payment_method" value="Airtel Money" class="text-blue-600">
            <div>
              <div class="text-xs font-bold text-slate-900">Airtel Money</div>
              <div class="text-[10px] text-slate-500">Direct wallet debit</div>
            </div>
          </div>
          <span class="text-xs font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded">Instant</span>
        </label>

        <label class="flex items-center justify-between p-3 rounded-xl border border-slate-200 cursor-pointer has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/50">
          <div class="flex items-center gap-3">
            <input type="radio" name="payment_method" value="Visa / Mastercard" class="text-blue-600">
            <div>
              <div class="text-xs font-bold text-slate-900">Debit / Credit Card</div>
              <div class="text-[10px] text-slate-500">Visa, Mastercard, Local Cards</div>
            </div>
          </div>
          <i class="fa-brands fa-cc-visa text-slate-400 text-lg"></i>
        </label>
      </div>

      <div class="mt-3">
        <label class="form-label text-xs">Mobile Money Phone Number</label>
        <div class="input-wrapper">
          <i class="fa-solid fa-phone input-icon"></i>
          <input type="tel" name="phone_number" class="form-input has-left-icon text-xs" placeholder="+256 700 000 000" value="<?= e($user['phone'] ?? '+256 701 445 921') ?>">
        </div>
      </div>
    </div>

    <!-- Security & Sandbox Notice -->
    <div class="bg-emerald-50/80 border border-emerald-200/80 rounded-xl p-3 flex items-start gap-2.5">
      <i class="fa-solid fa-shield-check text-emerald-600 text-sm mt-0.5"></i>
      <div class="text-[11px] text-emerald-800">
        <strong>100% Secure Sandbox Transaction:</strong> Transactions in this demonstration use verified test processing. Receipts and transaction logs are generated instantly.
      </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block shadow-lg">
      <i class="fa-solid fa-lock text-xs"></i>
      <span>Authorize & Confirm Sponsorship</span>
    </button>
  </form>
</div>

<script>
  document.querySelectorAll('.amount-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.amount-btn').forEach(b => {
        b.classList.remove('border-2', 'border-blue-600', 'bg-blue-50', 'text-blue-600');
        b.classList.add('border-slate-200', 'text-slate-700');
      });
      btn.classList.add('border-2', 'border-blue-600', 'bg-blue-50', 'text-blue-600');
      btn.classList.remove('border-slate-200', 'text-slate-700');
      document.getElementById('sponsorshipAmountInput').value = btn.getAttribute('data-amt');
    });
  });
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
