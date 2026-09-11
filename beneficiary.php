<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT b.*, o.name as org_name, o.description as org_desc, o.verification_status
    FROM beneficiaries b 
    JOIN organizations o ON b.organization_id = o.id 
    WHERE b.id = ? AND b.status = 'approved'
");
$stmt->execute([$id]);
$beneficiary = $stmt->fetch();

if (!$beneficiary) {
    set_flash_message("Beneficiary not found or not available.", "danger");
    redirect('beneficiaries.php');
}

$page_title = "Sponsor " . htmlspecialchars($beneficiary['first_name']);
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row bg-white rounded shadow-sm overflow-hidden mb-5">
        <div class="col-md-5 p-0 bg-light d-flex align-items-center justify-content-center" style="min-height: 400px;">
            <?php if ($beneficiary['photo_url']): ?>
                <img src="<?= base_url('uploads/' . htmlspecialchars($beneficiary['photo_url'])) ?>" class="img-fluid w-100 h-100" style="object-fit: cover;" alt="<?= htmlspecialchars($beneficiary['first_name']) ?>">
            <?php else: ?>
                <i class="fas fa-user-circle fa-8x text-secondary"></i>
            <?php endif; ?>
        </div>
        <div class="col-md-7 p-5">
            <h1 class="fw-bold text-primary mb-2"><?= htmlspecialchars($beneficiary['first_name'] . ' ' . $beneficiary['last_name']) ?></h1>
            <div class="d-flex align-items-center mb-4 text-muted">
                <span class="me-3"><i class="fas fa-calendar-alt me-2"></i> <?= $beneficiary['age'] ?> years old</span>
                <span class="me-3"><i class="fas fa-map-marker-alt me-2"></i> <?= htmlspecialchars($beneficiary['location']) ?></span>
            </div>
            
            <div class="mb-4">
                <h5 class="fw-bold border-bottom pb-2 mb-3">About</h5>
                <p style="line-height: 1.8;"><?= nl2br(htmlspecialchars($beneficiary['story'])) ?></p>
            </div>

            <?php if ($beneficiary['needs']): ?>
                <div class="mb-4">
                    <h5 class="fw-bold border-bottom pb-2 mb-3">Specific Needs</h5>
                    <p style="line-height: 1.8;"><?= nl2br(htmlspecialchars($beneficiary['needs'])) ?></p>
                </div>
            <?php endif; ?>

            <div class="bg-light p-4 rounded mb-4">
                <h6 class="fw-bold text-uppercase text-muted mb-3">Sponsorship Progress</h6>
                <div class="d-flex justify-content-between mb-2 fw-bold">
                    <span class="text-success"><?= format_currency($beneficiary['current_progress']) ?> Raised</span>
                    <span>Goal: <?= format_currency($beneficiary['sponsorship_goal']) ?></span>
                </div>
                <div class="progress" style="height: 12px;">
                    <?php $pct = min(100, ($beneficiary['current_progress'] / max(1, $beneficiary['sponsorship_goal'])) * 100); ?>
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pct ?>%"></div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <a href="<?= base_url('sponsor-checkout.php?id=' . $beneficiary['id']) ?>" class="btn btn-primary btn-lg fw-bold shadow-sm">
                    <i class="fas fa-heart me-2"></i> Sponsor <?= htmlspecialchars($beneficiary['first_name']) ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Organization Info -->
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card border-0 shadow-sm text-center p-4">
                <h5 class="text-muted mb-3">Supported by</h5>
                <h4 class="fw-bold text-primary mb-2"><?= htmlspecialchars($beneficiary['org_name']) ?>
                    <?php if ($beneficiary['verification_status'] === 'verified'): ?>
                        <i class="fas fa-check-circle text-success ms-2" title="Verified Organization"></i>
                    <?php endif; ?>
                </h4>
                <p class="text-muted"><?= htmlspecialchars(substr($beneficiary['org_desc'], 0, 150)) ?>...</p>
                <div>
                    <a href="#" class="btn btn-sm btn-outline-secondary">View Organization</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
