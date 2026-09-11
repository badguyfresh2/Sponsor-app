<?php
// includes/footer.php
?>
</main>
<footer class="bg-dark text-light py-5 mt-auto">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h5 class="fw-bold mb-3"><i class="fas fa-hands-holding-child me-2"></i>SPONSOR</h5>
                <p class="text-muted">Change a Life. Sponsor a Future. Connecting generous hearts with verified organizations to support vulnerable children.</p>
            </div>
            <div class="col-md-2 mb-4">
                <h6 class="fw-bold mb-3">Links</h6>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-muted text-decoration-none">About</a></li>
                    <li><a href="#" class="text-muted text-decoration-none">Sponsors</a></li>
                    <li><a href="#" class="text-muted text-decoration-none">Organizations</a></li>
                </ul>
            </div>
            <div class="col-md-2 mb-4">
                <h6 class="fw-bold mb-3">Support</h6>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-muted text-decoration-none">Help Center</a></li>
                    <li><a href="#" class="text-muted text-decoration-none">Privacy Policy</a></li>
                    <li><a href="#" class="text-muted text-decoration-none">Terms</a></li>
                    <li><a href="#" class="text-muted text-decoration-none">Contact</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h6 class="fw-bold mb-3">Connect With Us</h6>
                <div class="d-flex gap-3">
                    <a href="#" class="text-light fs-4"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="text-light fs-4"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-light fs-4"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <div class="text-center text-muted border-top border-secondary pt-4 mt-2">
            &copy; <?= date('Y') ?> Sponsor Web Application. All rights reserved.
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>
</html>
