<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (is_authenticated()) {
    header("Location: " . BASE_URL . "/sponsor/dashboard.php");
    exit;
}

$error = '';
$redirect = $_GET['redirect'] ?? (BASE_URL . '/sponsor/dashboard.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';
    $remember = !empty($_POST['remember']);

    if (!verify_csrf_token($csrf)) {
        $error = 'Security session expired. Please try submitting again.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please provide both your email and password.';
    } else {
        $res = login_user($email, $password, $remember);
        if ($res['success']) {
            header("Location: " . $redirect);
            exit;
        } else {
            $error = $res['message'];
        }
    }
}

$page_title = 'Sign In';
$is_auth_flow = true;
$hide_header = true;
$hide_bottom_nav = true;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="w-full max-w-sm mx-auto py-6">
  <!-- Brand Header -->
  <div class="text-center mb-8">
    <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-blue-600 to-teal-500 mx-auto flex items-center justify-center text-white shadow-lg shadow-blue-500/25 mb-4">
      <i class="fa-solid fa-hand-holding-heart text-2xl"></i>
    </div>
    <h1 class="text-2xl font-black tracking-tight text-slate-900">Welcome Back</h1>
    <p class="text-sm text-slate-500 mt-1">Continue making a difference today.</p>
  </div>

  <?php if (!empty($error)): ?>
    <div class="flash-banner error mb-5">
      <i class="fa-solid fa-circle-exclamation"></i>
      <span><?= e($error) ?></span>
    </div>
  <?php endif; ?>

  <!-- Demo Account Quick-Fill Card -->
  <div class="bg-blue-50/70 border border-blue-200/80 rounded-xl p-3.5 mb-6 text-left">
    <div class="flex items-center justify-between mb-1.5">
      <span class="text-xs font-bold uppercase tracking-wider text-blue-800 flex items-center gap-1.5">
        <i class="fa-solid fa-wand-magic-sparkles text-blue-600"></i> Demo Credentials
      </span>
      <span class="text-[11px] font-semibold text-blue-600">Tap to Fill</span>
    </div>
    <button type="button" id="fillDemoSponsor" class="w-full text-left bg-white border border-blue-200 hover:border-blue-400 p-2.5 rounded-lg flex items-center justify-between transition group">
      <div>
        <div class="text-xs font-bold text-slate-800">David Kigozi (Active Sponsor)</div>
        <div class="text-[11px] text-slate-500 font-mono">sponsor@example.com / sponsor123</div>
      </div>
      <i class="fa-solid fa-arrow-right text-xs text-blue-600 opacity-60 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all"></i>
    </button>
  </div>

  <!-- Login Form -->
  <form action="<?= BASE_URL ?>/auth/login.php?redirect=<?= urlencode($redirect) ?>" method="POST" class="space-y-4" id="loginForm">
    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
    <input type="hidden" name="app_sid" value="<?= session_id() ?>">

    <div class="form-group">
      <label for="emailInput" class="form-label">Email Address</label>
      <div class="input-wrapper">
        <i class="fa-regular fa-envelope input-icon"></i>
        <input type="email" id="emailInput" name="email" class="form-input has-left-icon" placeholder="name@example.com" required value="<?= e($_POST['email'] ?? '') ?>" autocomplete="email">
      </div>
    </div>

    <div class="form-group">
      <div class="flex items-center justify-between mb-1.5">
        <label for="passwordInput" class="form-label mb-0">Password</label>
        <a href="<?= BASE_URL ?>/auth/forgot-password.php" class="text-xs font-semibold text-blue-600 hover:text-blue-700">Forgot?</a>
      </div>
      <div class="input-wrapper">
        <i class="fa-solid fa-lock input-icon"></i>
        <input type="password" id="passwordInput" name="password" class="form-input has-left-icon has-right-icon" placeholder="••••••••" required autocomplete="current-password">
        <button type="button" class="input-toggle-pass" data-target="passwordInput" aria-label="Toggle password visibility">
          <i class="fa-regular fa-eye"></i>
        </button>
      </div>
    </div>

    <div class="flex items-center justify-between pt-1">
      <label class="inline-flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="remember" value="1" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
        <span class="text-xs text-slate-600 font-medium">Remember on this device</span>
      </label>
    </div>

    <button type="submit" id="submitBtn" class="btn btn-primary btn-block shadow-md hover:shadow-lg mt-2">
      <span>Sign In</span>
      <i class="fa-solid fa-arrow-right-to-bracket text-sm"></i>
    </button>
  </form>

  <div class="mt-8 text-center text-xs text-slate-500">
    Don't have a sponsor account? 
    <a href="<?= BASE_URL ?>/auth/register.php" class="font-bold text-blue-600 hover:underline ml-1">Create One Now</a>
  </div>
</div>

<script>
  document.getElementById('fillDemoSponsor').addEventListener('click', () => {
    document.getElementById('emailInput').value = 'sponsor@example.com';
    document.getElementById('passwordInput').value = 'sponsor123';
  });
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
