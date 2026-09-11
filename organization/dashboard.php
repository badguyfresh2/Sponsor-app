<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('organization');

$page_title = "Organization Dashboard";
require_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'];

// Get org profile
$stmt = $pdo->prepare("SELECT * FROM organizations WHERE user_id = ?");
$stmt->execute([$user_id]);
$org = $stmt->fetch();

if (!$org) {
    die("Organization profile not found.");
}

// Get stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM beneficiaries WHERE organization_id = ?");
$stmt->execute([$org['id']]);
$total_beneficiaries = $stmt->fetchColumn();

?>

<div class="container py-5">
    <?php if ($org['verification_status'] === 'pending'): ?>
        <div class="alert alert-warning mb-4">
            <i class="fas fa-exclamation-triangle me-2"></i> Your organization is pending admin verification. Some features may be restricted until approved.
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><?= htmlspecialchars($org['name']) ?> Dashboard</h2>
        <a href="add-beneficiary.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i> Add Beneficiary</a>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-white border-0 shadow-sm p-4 text-center">
                <i class="fas fa-child fa-3x text-info mb-3"></i>
                <h3 class="fw-bold"><?= $total_beneficiaries ?></h3>
                <p class="text-muted mb-0">Total Beneficiaries</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-white border-0 shadow-sm p-4 text-center">
                <i class="fas fa-users fa-3x text-success mb-3"></i>
                <h3 class="fw-bold">0</h3>
                <p class="text-muted mb-0">Active Sponsors</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-white border-0 shadow-sm p-4 text-center">
                <i class="fas fa-hand-holding-dollar fa-3x text-warning mb-3"></i>
                <h3 class="fw-bold">$0.00</h3>
                <p class="text-muted mb-0">Support Received</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    <span>Recent Beneficiaries</span>
                    <a href="beneficiaries.php" class="btn btn-sm btn-outline-secondary">View All</a>
                </div>
                <div class="card-body text-center py-5">
                    <?php if ($total_beneficiaries > 0): ?>
                        <p class="text-muted">List of beneficiaries will appear here.</p>
                    <?php else: ?>
                        <p class="text-muted">You haven't added any beneficiaries yet.</p>
                        <a href="add-beneficiary.php" class="btn btn-primary mt-2">Add First Beneficiary</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="list-group shadow-sm">
                <a href="#" class="list-group-item list-group-item-action"><i class="fas fa-building me-2"></i> Organization Profile</a>
                <a href="#" class="list-group-item list-group-item-action"><i class="fas fa-bullhorn me-2"></i> Post Updates</a>
                <a href="#" class="list-group-item list-group-item-action"><i class="fas fa-envelope me-2"></i> Messages</a>
                <a href="#" class="list-group-item list-group-item-action"><i class="fas fa-chart-bar me-2"></i> Reports</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
