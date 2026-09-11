<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

if (is_logged_in()) {
    redirect(current_user_role() . '/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid CSRF token.";
    } else {
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $error = "Please enter both email and password.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] === 'suspended') {
                    $error = "Your account has been suspended. Please contact support.";
                } else {
                    login_user($user);
                    set_flash_message("Welcome back!", "success");
                    redirect($user['role'] . '/dashboard.php');
                }
            } else {
                $error = "Invalid email or password.";
            }
        }
    }
}

$page_title = "Login - Sponsorship App";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h3 class="text-center fw-bold text-primary mb-4">Login</h3>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form action="login.php" method="POST">
                        <?php csrf_field(); ?>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2">Login</button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p class="text-muted">Don't have an account?</p>
                        <a href="register.php?role=sponsor" class="btn btn-outline-primary btn-sm">Register as Sponsor</a>
                        <a href="register.php?role=organization" class="btn btn-outline-secondary btn-sm">Register Organization</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
