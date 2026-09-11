<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

if (is_logged_in()) {
    redirect(current_user_role() . '/dashboard.php');
}

$role = isset($_GET['role']) && $_GET['role'] === 'organization' ? 'organization' : 'sponsor';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid CSRF token.";
    } else {
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $role_post = in_array($_POST['role'], ['sponsor', 'organization']) ? $_POST['role'] : 'sponsor';

        if (empty($email) || empty($password)) {
            $error = "Please fill in all required fields.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } else {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email is already registered.";
            } else {
                try {
                    $pdo->beginTransaction();
                    
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $status = $role_post === 'organization' ? 'pending' : 'active'; // Orgs might need approval
                    
                    $stmt = $pdo->prepare("INSERT INTO users (email, password, role, status) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$email, $hashed_password, $role_post, $status]);
                    $user_id = $pdo->lastInsertId();

                    if ($role_post === 'sponsor') {
                        $first_name = sanitize_input($_POST['first_name']);
                        $last_name = sanitize_input($_POST['last_name']);
                        $stmt = $pdo->prepare("INSERT INTO sponsor_profiles (user_id, first_name, last_name) VALUES (?, ?, ?)");
                        $stmt->execute([$user_id, $first_name, $last_name]);
                    } elseif ($role_post === 'organization') {
                        $org_name = sanitize_input($_POST['org_name']);
                        $stmt = $pdo->prepare("INSERT INTO organizations (user_id, name) VALUES (?, ?)");
                        $stmt->execute([$user_id, $org_name]);
                    }

                    $pdo->commit();
                    
                    set_flash_message("Registration successful! Please login.", "success");
                    redirect('auth/login.php');
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Registration failed. Please try again.";
                }
            }
        }
    }
}

$page_title = "Register - Sponsorship App";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h3 class="text-center fw-bold text-primary mb-4">
                        Register as <?= ucfirst($role) ?>
                    </h3>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form action="register.php?role=<?= htmlspecialchars($role) ?>" method="POST">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">
                        
                        <?php if ($role === 'sponsor'): ?>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <label for="org_name" class="form-label">Organization Name</label>
                                <input type="text" class="form-control" id="org_name" name="org_name" required>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="8">
                        </div>
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2">Create Account</button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p class="text-muted">Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
