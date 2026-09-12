<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$sent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } else {
        $sent = true;
    }
}

$page_title = 'Reset Password';
$is_auth_flow = true;
$hide_header = true;
$hide_bottom_nav = true;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="w-full max-w-sm mx-auto py-6">
  <div class="text-center mb-6">
    <div class="w-14 h-14 rounded-2xl bg-amber-500/10 text-amber-600 mx-auto flex items-center justify-center text-xl mb-3">
      <i class="fa-solid fa-key"></i>
    </div>
    <h1 class="text-2xl font-black text-slate-900">Forgot Password?</h1>
    <p class="text-xs text-slate-500 mt-1">Enter your email and we'll send you recovery instructions.</p>
  </div>

  <?php if ($sent): ?>
    <div class="app-card text-center py-6">
      <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3 text-lg">
        <i class="fa-solid fa-paper-plane"></i>
      </div>
      <h3 class="font-bold text-slate-900 mb-1">Check Your Email</h3>
      <p class="text-xs text-slate-500 mb-4">We sent password reset guidelines to your address.</p>
      <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-primary btn-block btn-sm">Return to Login</a>
    </div>
  <?php else: ?>
    <?php if ($error): ?>
      <div class="flash-banner error mb-4">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= e($error) ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div class="form-group">
        <label class="form-label">Account Email</label>
        <div class="input-wrapper">
          <i class="fa-regular fa-envelope input-icon"></i>
          <input type="email" name="email" class="form-input has-left-icon" placeholder="name@example.com" required>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block">
        <span>Send Reset Link</span>
        <i class="fa-solid fa-arrow-right text-xs"></i>
      </button>

      <div class="text-center pt-2">
        <a href="<?= BASE_URL ?>/auth/login.php" class="text-xs font-semibold text-slate-600 hover:text-blue-600">
          <i class="fa-solid fa-chevron-left mr-1"></i> Back to Sign In
        </a>
      </div>
    </form>
  <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
