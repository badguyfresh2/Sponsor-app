<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$page_title = "Admin Dashboard";
require_once __DIR__ . '/../includes/header.php';

// Fetch quick stats
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'organizations' => $pdo->query("SELECT COUNT(*) FROM organizations")->fetchColumn(),
    'beneficiaries' => $pdo->query("SELECT COUNT(*) FROM beneficiaries")->fetchColumn(),
    'active_sponsorships' => $pdo->query("SELECT COUNT(*) FROM sponsorships WHERE status='active'")->fetchColumn()
];

// Fetch recent pending organizations
$pending_orgs = $pdo->query("SELECT o.*, u.email FROM organizations o JOIN users u ON o.user_id = u.id WHERE o.verification_status = 'pending' LIMIT 5")->fetchAll();
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Admin Dashboard</h2>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white text-center p-3 border-0 shadow-sm">
                <h3><?= $stats['users'] ?></h3>
                <p class="mb-0">Total Users</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white text-center p-3 border-0 shadow-sm">
                <h3><?= $stats['organizations'] ?></h3>
                <p class="mb-0">Organizations</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white text-center p-3 border-0 shadow-sm">
                <h3><?= $stats['beneficiaries'] ?></h3>
                <p class="mb-0">Beneficiaries</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark text-center p-3 border-0 shadow-sm">
                <h3><?= $stats['active_sponsorships'] ?></h3>
                <p class="mb-0">Active Sponsorships</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">
                    Pending Organizations
                </div>
                <div class="card-body">
                    <?php if (count($pending_orgs) > 0): ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Date Applied</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_orgs as $org): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($org['name']) ?></td>
                                        <td><?= htmlspecialchars($org['email']) ?></td>
                                        <td><?= date('M d, Y', strtotime($org['created_at'])) ?></td>
                                        <td>
                                            <a href="#" class="btn btn-sm btn-primary">Review</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No pending organizations to review.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="list-group shadow-sm">
                <a href="#" class="list-group-item list-group-item-action"><i class="fas fa-users me-2"></i> Manage Users</a>
                <a href="#" class="list-group-item list-group-item-action"><i class="fas fa-building me-2"></i> Manage Organizations</a>
                <a href="#" class="list-group-item list-group-item-action"><i class="fas fa-child me-2"></i> Manage Beneficiaries</a>
                <a href="#" class="list-group-item list-group-item-action"><i class="fas fa-hand-holding-dollar me-2"></i> Manage Sponsorships</a>
                <a href="#" class="list-group-item list-group-item-action"><i class="fas fa-cog me-2"></i> Platform Settings</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
