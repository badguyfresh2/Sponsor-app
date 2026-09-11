<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$page_title = "Home - Change a Life. Sponsor a Future.";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero py-5 text-center bg-light">
    <div class="container">
        <h1 class="display-4 fw-bold text-primary">Change a Life. Sponsor a Future.</h1>
        <p class="lead text-muted mb-4">Support verified beneficiaries through trusted organizations and make a lasting impact.</p>
        <div>
            <a href="<?= base_url('beneficiaries.php') ?>" class="btn btn-primary btn-lg me-2">Find Someone to Support</a>
            <a href="#how-it-works" class="btn btn-outline-secondary btn-lg">How It Works</a>
        </div>
    </div>
</section>

<!-- Trust Section -->
<section class="trust-section py-5">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3">
                <h2 class="fw-bold">150+</h2>
                <p class="text-muted">Verified Organizations</p>
            </div>
            <div class="col-md-3">
                <h2 class="fw-bold">5,000+</h2>
                <p class="text-muted">Supported Children</p>
            </div>
            <div class="col-md-3">
                <h2 class="fw-bold">10,000+</h2>
                <p class="text-muted">Active Sponsors</p>
            </div>
            <div class="col-md-3">
                <h2 class="fw-bold">50+</h2>
                <p class="text-muted">Communities Reached</p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section id="how-it-works" class="py-5 bg-light">
    <div class="container text-center">
        <h2 class="mb-5 fw-bold">How It Works</h2>
        <div class="row">
            <div class="col-md-3">
                <div class="card border-0 bg-transparent">
                    <div class="card-body">
                        <i class="fas fa-search fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">1. Discover</h5>
                        <p class="card-text text-muted">Browse through verified beneficiaries who need your support.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 bg-transparent">
                    <div class="card-body">
                        <i class="fas fa-hand-pointer fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">2. Choose</h5>
                        <p class="card-text text-muted">Select a beneficiary and decide on a monthly or one-time sponsorship.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 bg-transparent">
                    <div class="card-body">
                        <i class="fas fa-heart fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">3. Sponsor</h5>
                        <p class="card-text text-muted">Make a secure payment to start changing a life today.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 bg-transparent">
                    <div class="card-body">
                        <i class="fas fa-chart-line fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">4. See the Impact</h5>
                        <p class="card-text text-muted">Receive regular updates and track the progress of your sponsorship.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="cta py-5 text-center text-white bg-primary">
    <div class="container">
        <h2 class="fw-bold mb-3">Your support can become someone’s opportunity.</h2>
        <a href="<?= base_url('auth/register.php?role=sponsor') ?>" class="btn btn-light btn-lg text-primary fw-bold">Become a Sponsor</a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
