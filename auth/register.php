<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (is_authenticated()) {
    header("Location: " . BASE_URL . "/sponsor/dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf)) {
        $error = 'Security session expired. Please submit again.';
    } elseif (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill out all required fields.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Password confirmation does not match.';
    } else {
        $res = register_sponsor($name, $email, $password, $phone);
        if ($res['success']) {
            set_flash('success', 'Welcome to Sponsor! Your account is active.');
            header("Location: " . BASE_URL . "/sponsor/dashboard.php");
            exit;
        } else {
            $error = $res['message'];
        }
    }
}

$page_title = 'Create Sponsor Account';
$is_auth_flow = true;
$hide_header = true;
$hide_bottom_nav = true;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="w-full max-w-sm mx-auto py-4">
  <div class="text-center mb-6">
    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-teal-500 mx-auto flex items-center justify-center text-white shadow-lg shadow-blue-500/20 mb-3">
      <i class="fa-solid fa-user-plus text-xl"></i>
    </div>
    <h1 class="text-2xl font-black tracking-tight text-slate-900">Create Account</h1>
    <p class="text-xs text-slate-500 mt-1">Start your sponsorship journey in seconds.</p>
  </div>

  <?php if (!empty($error)): ?>
    <div class="flash-banner error mb-4">
      <i class="fa-solid fa-circle-exclamation"></i>
      <span><?= e($error) ?></span>
    </div>
  <?php endif; ?>

  <form action="<?= BASE_URL ?>/auth/register.php" method="POST" class="space-y-3.5">
    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
    <input type="hidden" name="app_sid" value="<?= session_id() ?>">

    <div class="form-group">
      <label class="form-label">Full Name</label>
      <div class="input-wrapper">
        <i class="fa-regular fa-user input-icon"></i>
        <input type="text" name="name" class="form-input has-left-icon" placeholder="e.g. David Kigozi" required value="<?= e($_POST['name'] ?? '') ?>">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Email Address</label>
      <div class="input-wrapper">
        <i class="fa-regular fa-envelope input-icon"></i>
        <input type="email" name="email" class="form-input has-left-icon" placeholder="name@example.com" required value="<?= e($_POST['email'] ?? '') ?>">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Phone Number (Optional)</label>
      <div class="input-wrapper">
        <i class="fa-solid fa-phone input-icon"></i>
        <input type="tel" name="phone" class="form-input has-left-icon" placeholder="+256 700 000 000" value="<?= e($_POST['phone'] ?? '') ?>">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Password</label>
      <div class="input-wrapper">
        <i class="fa-solid fa-lock input-icon"></i>
        <input type="password" id="regPassword" name="password" class="form-input has-left-icon has-right-icon" placeholder="Min. 6 characters" required>
        <button type="button" class="input-toggle-pass" data-target="regPassword">
          <i class="fa-regular fa-eye"></i>
        </button>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Confirm Password</label>
      <div class="input-wrapper">
        <i class="fa-solid fa-lock-open input-icon"></i>
        <input type="password" id="regConfirmPassword" name="confirm_password" class="form-input has-left-icon has-right-icon" placeholder="Re-type password" required>
        <button type="button" class="input-toggle-pass" data-target="regConfirmPassword">
          <i class="fa-regular fa-eye"></i>
        </button>
      </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block mt-4">
      <span>Join as Sponsor</span>
      <i class="fa-solid fa-circle-check text-sm"></i>
    </button>
  </form>

  <div class="mt-6 text-center text-xs text-slate-500">
    Already have an account? 
    <a href="<?= BASE_URL ?>/auth/login.php" class="font-bold text-blue-600 hover:underline ml-1">Sign In</a>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
