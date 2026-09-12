<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $success = true;
    }
}

$page_title = 'Set New Password';
$is_auth_flow = true;
$hide_header = true;
$hide_bottom_nav = true;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="w-full max-w-sm mx-auto py-6">
  <div class="text-center mb-6">
    <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 mx-auto flex items-center justify-center text-xl mb-3">
      <i class="fa-solid fa-shield-halved"></i>
    </div>
    <h1 class="text-2xl font-black text-slate-900">Set New Password</h1>
    <p class="text-xs text-slate-500 mt-1">Choose a secure password for your sponsor profile.</p>
  </div>

  <?php if ($success): ?>
    <div class="app-card text-center py-6">
      <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3 text-lg">
        <i class="fa-solid fa-check"></i>
      </div>
      <h3 class="font-bold text-slate-900 mb-1">Password Changed!</h3>
      <p class="text-xs text-slate-500 mb-4">You can now sign in with your updated credentials.</p>
      <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-primary btn-block btn-sm">Sign In Now</a>
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
        <label class="form-label">New Password</label>
        <div class="input-wrapper">
          <i class="fa-solid fa-lock input-icon"></i>
          <input type="password" id="newPass" name="password" class="form-input has-left-icon has-right-icon" placeholder="Min. 6 characters" required>
          <button type="button" class="input-toggle-pass" data-target="newPass">
            <i class="fa-regular fa-eye"></i>
          </button>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Confirm New Password</label>
        <div class="input-wrapper">
          <i class="fa-solid fa-lock-open input-icon"></i>
          <input type="password" id="newPassConfirm" name="confirm_password" class="form-input has-left-icon has-right-icon" placeholder="Re-enter password" required>
          <button type="button" class="input-toggle-pass" data-target="newPassConfirm">
            <i class="fa-regular fa-eye"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block">
        <span>Save New Password</span>
        <i class="fa-solid fa-check text-xs"></i>
      </button>
    </form>
  <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
