<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user = require_auth();
$db = get_db();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $error = 'Security session expired. Please resubmit.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $country = trim($_POST['country'] ?? 'Uganda');
        $currency = trim($_POST['currency'] ?? 'UGX');
        $emailNotifs = isset($_POST['email_notifications']) ? 1 : 0;
        $smsNotifs = isset($_POST['sms_notifications']) ? 1 : 0;

        if (empty($name)) {
            $error = 'Name cannot be empty.';
        } else {
            $avatarUrl = $user['avatar_url'] ?? null;

            // Handle avatar upload
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['avatar'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedImage = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (in_array($ext, $allowedImage)) {
                    $uploadDir = dirname(__DIR__) . '/uploads/avatars';
                    if (!is_dir($uploadDir)) {
                        @mkdir($uploadDir, 0777, true);
                    }
                    
                    $safeFileName = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
                    $destPath = $uploadDir . '/' . $safeFileName;
                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        $avatarUrl = BASE_URL . '/uploads/avatars/' . $safeFileName;
                    } else {
                        $error = 'Failed to save uploaded image.';
                    }
                } else {
                    $error = 'Invalid image format. Only JPG, PNG, and WEBP are allowed.';
                }
            }

            if (!$error) {
                // Update user table
                $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, avatar_url = ? WHERE id = ?");
                $stmt->execute([$name, $phone, $avatarUrl, $user['id']]);

                // Update profile
                $stmt = $db->prepare("UPDATE sponsor_profiles SET address = ?, country = ?, preferred_currency = ?, notification_email = ?, notification_sms = ? WHERE user_id = ?");
                $stmt->execute([$address, $country, $currency, $emailNotifs, $smsNotifs, $user['id']]);

                // Refresh session info
                $_SESSION['user_name'] = $name;
                $user['name'] = $name;
                $user['phone'] = $phone;
                $user['address'] = $address;
                $user['country'] = $country;
                $user['preferred_currency'] = $currency;
                $user['notification_email'] = $emailNotifs;
                $user['notification_sms'] = $smsNotifs;
                $user['avatar_url'] = $avatarUrl;

                $success = 'Profile updated successfully!';
            }
        }
    }
}

$page_title = 'Account Settings';
$show_back = true;
$back_url = BASE_URL . '/sponsor/profile.php';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="space-y-4">
  <?php if ($success): ?>
    <div class="flash-banner success">
      <i class="fa-solid fa-circle-check"></i>
      <span><?= e($success) ?></span>
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="flash-banner error">
      <i class="fa-solid fa-circle-exclamation"></i>
      <span><?= e($error) ?></span>
    </div>
  <?php endif; ?>

  <form method="POST" action="<?= BASE_URL ?>/sponsor/settings.php" class="space-y-4" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
    <input type="hidden" name="app_sid" value="<?= session_id() ?>">

    <!-- Personal Info -->
    <div class="app-card">
      <h3 class="card-title text-sm mb-3">Profile Picture</h3>
      <div class="flex items-center gap-4 mb-4">
        <img src="<?= e($user['avatar_url'] ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80') ?>" alt="<?= e($user['name']) ?>" class="w-16 h-16 rounded-full object-cover border-2 border-blue-600 shadow-sm" id="avatarPreview">
        <div class="flex-1">
          <input type="file" name="avatar" id="avatarInput" class="hidden" accept="image/jpeg, image/png, image/webp">
          <label for="avatarInput" class="btn btn-outline btn-sm text-xs cursor-pointer inline-flex items-center gap-1.5">
            <i class="fa-solid fa-camera"></i> Change Photo
          </label>
          <div class="text-[10px] text-slate-500 mt-1">JPG, PNG or WEBP. Max 2MB.</div>
        </div>
      </div>
      
      <h3 class="card-title text-sm mb-3 border-t border-slate-100 pt-3">Personal Details</h3>

      <div class="form-group mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-input" value="<?= e($user['name']) ?>" required>
      </div>

      <div class="form-group mb-3">
        <label class="form-label">Email (Read Only)</label>
        <input type="email" class="form-input bg-slate-100 text-slate-500 cursor-not-allowed" value="<?= e($user['email']) ?>" readonly>
      </div>

      <div class="form-group mb-3">
        <label class="form-label">Phone Number</label>
        <input type="tel" name="phone" class="form-input" value="<?= e($user['phone'] ?? '') ?>">
      </div>

      <div class="form-group mb-3">
        <label class="form-label">Country / Region</label>
        <input type="text" name="country" class="form-input" value="<?= e($user['country'] ?? 'Uganda') ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Address / City</label>
        <input type="text" name="address" class="form-input" value="<?= e($user['address'] ?? '') ?>">
      </div>
    </div>

    <!-- Preferences -->
    <div class="app-card">
      <h3 class="card-title text-sm mb-3">Communication & Preferences</h3>

      <div class="space-y-3 text-xs">
        <label class="flex items-center justify-between cursor-pointer">
          <span class="text-slate-700 font-medium">Email Impact Updates & Letters</span>
          <input type="checkbox" name="email_notifications" value="1" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300" <?= !empty($user['notification_email']) ? 'checked' : '' ?>>
        </label>

        <label class="flex items-center justify-between cursor-pointer">
          <span class="text-slate-700 font-medium">SMS Reminders & Receipts</span>
          <input type="checkbox" name="sms_notifications" value="1" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300" <?= !empty($user['notification_sms']) ? 'checked' : '' ?>>
        </label>
      </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block">
      <i class="fa-solid fa-floppy-disk text-xs"></i>
      <span>Save Changes</span>
    </button>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');

    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
