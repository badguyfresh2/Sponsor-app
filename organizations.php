<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$page_title = "Verified Organizations";
require_once __DIR__ . '/includes/header.php';

$stmt = $pdo->query("
    SELECT o.*, 
           (SELECT COUNT(*) FROM beneficiaries WHERE organization_id = o.id AND status = 'approved') as beneficiary_count
    FROM organizations o 
    WHERE o.verification_status = 'verified' 
    ORDER BY o.name ASC
");
$organizations = $stmt->fetchAll();
?>

<div class="container py-5">
    <div class="row mb-5 text-center">
        <div class="col-12">
            <h1 class="fw-bold text-primary mb-3">Verified Organizations</h1>
            <p class="lead text-muted">We partner with trusted organizations to ensure your support reaches those who need it most.</p>
        </div>
    </div>

    <div class="row">
        <?php if (count($organizations) > 0): ?>
            <?php foreach ($organizations as $org): ?>
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100 p-4 text-center">
                        <?php if ($org['logo_url']): ?>
                            <img src="<?= base_url('uploads/' . htmlspecialchars($org['logo_url'])) ?>" class="rounded-circle mb-3 mx-auto" width="100" height="100" style="object-fit: cover;" alt="<?= htmlspecialchars($org['name']) ?>">
                        <?php else: ?>
                            <div class="mx-auto mb-3 text-secondary">
                                <i class="fas fa-building fa-4x"></i>
                            </div>
                        <?php endif; ?>
                        
                        <h4 class="fw-bold text-primary mb-2">
                            <?= htmlspecialchars($org['name']) ?>
                            <i class="fas fa-check-circle text-success fs-6 ms-1" title="Verified"></i>
                        </h4>
                        
                        <p class="text-muted small mb-3">
                            <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($org['location'] ?? 'Location not specified') ?>
                        </p>
                        
                        <p class="text-muted" style="font-size: 0.9rem;"><?= htmlspecialchars(substr($org['description'] ?? 'No description provided.', 0, 120)) ?>...</p>
                        
                        <div class="mt-auto pt-3 border-top">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-light text-dark border"><i class="fas fa-child text-primary me-1"></i> <?= $org['beneficiary_count'] ?> Beneficiaries</span>
                                <a href="#" class="btn btn-sm btn-outline-primary">View Programs</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-building fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No verified organizations found at the moment.</h4>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
