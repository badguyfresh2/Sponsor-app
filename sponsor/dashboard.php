<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('sponsor');

$page_title = "Sponsor Dashboard";
require_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'];

// Get sponsor profile
$stmt = $pdo->prepare("SELECT * FROM sponsor_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

// Get active sponsorships count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM sponsorships WHERE sponsor_id = ? AND status = 'active'");
$stmt->execute([$user_id]);
$active_sponsorships = $stmt->fetchColumn();

// Get total contributions
$stmt = $pdo->prepare("
    SELECT SUM(t.amount) 
    FROM transactions t 
    JOIN sponsorships s ON t.sponsorship_id = s.id 
    WHERE s.sponsor_id = ? AND t.status = 'completed'
");
$stmt->execute([$user_id]);
$total_contributed = $stmt->fetchColumn() ?: 0.00;

?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col">
            <h2 class="fw-bold">Welcome back, <?= htmlspecialchars($profile['first_name'] ?? 'Sponsor') ?>!</h2>
            <p class="text-muted">Here's the impact you are making.</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-white border-0 shadow-sm p-4 text-center">
                <i class="fas fa-child fa-3x text-primary mb-3"></i>
                <h3 class="fw-bold"><?= $active_sponsorships ?></h3>
                <p class="text-muted mb-0">Active Sponsorships</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-white border-0 shadow-sm p-4 text-center">
                <i class="fas fa-hand-holding-heart fa-3x text-success mb-3"></i>
                <h3 class="fw-bold"><?= format_currency($total_contributed) ?></h3>
                <p class="text-muted mb-0">Total Contributed</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-white border-0 shadow-sm p-4 text-center">
                <i class="fas fa-bell fa-3x text-warning mb-3"></i>
                <h3 class="fw-bold">0</h3>
                <p class="text-muted mb-0">New Updates</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    <span>My Sponsorships</span>
                    <a href="<?= base_url('beneficiaries.php') ?>" class="btn btn-sm btn-primary">Find Someone to Sponsor</a>
                </div>
                <div class="card-body">
                    <p class="text-muted text-center py-4">You have not sponsored anyone yet. Start changing a life today!</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="list-group shadow-sm">
                <a href="#" class="list-group-item list-group-item-action"><i class="fas fa-user-circle me-2"></i> My Profile</a>
                <a href="#" class="list-group-item list-group-item-action"><i class="fas fa-history me-2"></i> Payment History</a>
                <a href="#" class="list-group-item list-group-item-action"><i class="fas fa-envelope me-2"></i> Messages</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
