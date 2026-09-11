<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

require_role('sponsor');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM beneficiaries WHERE id = ? AND status = 'approved'");
$stmt->execute([$id]);
$beneficiary = $stmt->fetch();

if (!$beneficiary) {
    set_flash_message("Beneficiary not found.", "danger");
    redirect('beneficiaries.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid CSRF token.";
    } else {
        $amount = (float)$_POST['amount'];
        $type = in_array($_POST['type'], ['one-time', 'monthly']) ? $_POST['type'] : 'one-time';
        $sponsor_id = $_SESSION['user_id'];
        
        if ($amount < 5) {
            $error = "Minimum sponsorship amount is $5.00";
        } else {
            // Create sponsorship record
            try {
                $pdo->beginTransaction();
                
                // For a real app, integrate Stripe/Mobile Money here. 
                // We're simulating a successful payment immediately for the sandbox.
                
                $stmt = $pdo->prepare("INSERT INTO sponsorships (sponsor_id, beneficiary_id, type, amount, start_date) VALUES (?, ?, ?, ?, CURDATE())");
                $stmt->execute([$sponsor_id, $id, $type, $amount]);
                $sponsorship_id = $pdo->lastInsertId();
                
                // Record transaction
                $tx_ref = 'TX-' . strtoupper(uniqid());
                $stmt = $pdo->prepare("INSERT INTO transactions (sponsorship_id, amount, payment_method, transaction_reference, status) VALUES (?, ?, 'sandbox', ?, 'completed')");
                $stmt->execute([$sponsorship_id, $amount, $tx_ref]);
                
                // Update beneficiary progress
                $stmt = $pdo->prepare("UPDATE beneficiaries SET current_progress = current_progress + ? WHERE id = ?");
                $stmt->execute([$amount, $id]);
                
                $pdo->commit();
                
                set_flash_message("Thank you! Your sponsorship was successful.", "success");
                redirect('sponsor/dashboard.php');
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Payment processing failed. Please try again.";
            }
        }
    }
}

$page_title = "Checkout - Sponsor " . htmlspecialchars($beneficiary['first_name']);
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="row g-0">
                    <div class="col-md-4 bg-light d-flex align-items-center justify-content-center border-end">
                        <div class="text-center p-4">
                            <?php if ($beneficiary['photo_url']): ?>
                                <img src="<?= base_url('uploads/' . htmlspecialchars($beneficiary['photo_url'])) ?>" class="rounded-circle mb-3 object-fit-cover" width="120" height="120" alt="<?= htmlspecialchars($beneficiary['first_name']) ?>">
                            <?php else: ?>
                                <i class="fas fa-user-circle fa-5x text-secondary mb-3"></i>
                            <?php endif; ?>
                            <h5 class="fw-bold text-primary"><?= htmlspecialchars($beneficiary['first_name']) ?></h5>
                            <p class="text-muted small">Target: <?= format_currency($beneficiary['sponsorship_goal']) ?></p>
                        </div>
                    </div>
                    <div class="col-md-8 p-4 p-md-5">
                        <h3 class="fw-bold mb-4">Complete Sponsorship</h3>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info py-2 small mb-4">
                            <i class="fas fa-info-circle me-1"></i> <strong>Sandbox Mode:</strong> Real payments are bypassed in development. No card required.
                        </div>

                        <form action="sponsor-checkout.php?id=<?= $id ?>" method="POST">
                            <?php csrf_field(); ?>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Sponsorship Type</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check border rounded p-3 flex-fill">
                                        <input class="form-check-input ms-0 me-2" type="radio" name="type" id="type_monthly" value="monthly" checked>
                                        <label class="form-check-label stretched-link" for="type_monthly">
                                            <strong>Monthly</strong><br>
                                            <span class="small text-muted">Ongoing support</span>
                                        </label>
                                    </div>
                                    <div class="form-check border rounded p-3 flex-fill">
                                        <input class="form-check-input ms-0 me-2" type="radio" name="type" id="type_onetime" value="one-time">
                                        <label class="form-check-label stretched-link" for="type_onetime">
                                            <strong>One-time</strong><br>
                                            <span class="small text-muted">Single gift</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Amount (USD)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control form-control-lg" name="amount" min="5" step="1" value="30" required>
                                </div>
                                <div class="form-text">Minimum amount is $5.00</div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm">
                                <i class="fas fa-lock me-2"></i> Confirm Sponsorship
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
