<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$page_title = "Find a Child to Sponsor";
require_once __DIR__ . '/includes/header.php';

// Fetch approved beneficiaries (placeholder logic)
$stmt = $pdo->query("
    SELECT b.*, o.name as org_name 
    FROM beneficiaries b 
    JOIN organizations o ON b.organization_id = o.id 
    WHERE b.status = 'approved' 
    ORDER BY b.created_at DESC 
    LIMIT 12
");
$beneficiaries = $stmt->fetchAll();
?>

<div class="container py-5">
    <div class="row mb-5">
        <div class="col-12 text-center">
            <h1 class="fw-bold text-primary mb-3">Find Someone to Support</h1>
            <p class="lead text-muted">Browse through profiles of children and individuals who need your help.</p>
        </div>
    </div>
    
    <!-- Search and Filter Bar -->
    <div class="row mb-5 justify-content-center">
        <div class="col-md-10">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="beneficiaries.php" method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="q" placeholder="Search by name or keyword...">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="location">
                                <option value="">Any Location</option>
                                <option value="Uganda">Uganda</option>
                                <option value="Kenya">Kenya</option>
                                <option value="Nigeria">Nigeria</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="age_group">
                                <option value="">Any Age</option>
                                <option value="0-5">0 - 5 years</option>
                                <option value="6-12">6 - 12 years</option>
                                <option value="13-18">13 - 18 years</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Grid -->
    <div class="row">
        <?php if (count($beneficiaries) > 0): ?>
            <?php foreach ($beneficiaries as $b): ?>
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100 overflow-hidden">
                        <div class="bg-light" style="height: 250px; display: flex; align-items: center; justify-content: center;">
                            <?php if ($b['photo_url']): ?>
                                <img src="<?= base_url('uploads/' . htmlspecialchars($b['photo_url'])) ?>" class="w-100 h-100" style="object-fit: cover;" alt="<?= htmlspecialchars($b['first_name']) ?>">
                            <?php else: ?>
                                <i class="fas fa-user-circle fa-5x text-secondary"></i>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title fw-bold text-primary"><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?>, <?= $b['age'] ?></h5>
                            <p class="text-muted small mb-2"><i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($b['location']) ?></p>
                            <p class="text-muted small mb-3"><i class="fas fa-building me-1"></i> <?= htmlspecialchars($b['org_name']) ?></p>
                            
                            <p class="card-text text-truncate" style="max-height: 4.5em; overflow: hidden;"><?= htmlspecialchars(substr($b['story'], 0, 100)) ?>...</p>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span><?= format_currency($b['current_progress']) ?> raised</span>
                                    <span>Goal: <?= format_currency($b['sponsorship_goal']) ?></span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <?php $pct = min(100, ($b['current_progress'] / max(1, $b['sponsorship_goal'])) * 100); ?>
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pct ?>%"></div>
                                </div>
                            </div>
                            
                            <a href="beneficiary.php?id=<?= $b['id'] ?>" class="btn btn-outline-primary w-100">View Profile & Sponsor</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-search fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No beneficiaries found matching your criteria.</h4>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
