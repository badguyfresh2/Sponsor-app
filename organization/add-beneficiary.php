<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('organization');

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id FROM organizations WHERE user_id = ?");
$stmt->execute([$user_id]);
$org_id = $stmt->fetchColumn();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid CSRF token.";
    } else {
        $first_name = sanitize_input($_POST['first_name']);
        $last_name = sanitize_input($_POST['last_name']);
        $age = (int)$_POST['age'];
        $location = sanitize_input($_POST['location']);
        $story = sanitize_input($_POST['story']);
        $needs = sanitize_input($_POST['needs']);
        $goal = (float)$_POST['sponsorship_goal'];

        if (empty($first_name) || empty($last_name) || empty($story) || $goal <= 0) {
            $error = "Please fill in all required fields properly.";
        } else {
            // Handle image upload (sandbox basic implementation)
            $photo_url = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['photo']['tmp_name'];
                $name = basename($_FILES['photo']['name']);
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png'];
                
                if (in_array($ext, $allowed) && $_FILES['photo']['size'] < MAX_UPLOAD_SIZE) {
                    $new_name = uniqid('ben_') . '.' . $ext;
                    $dest = UPLOAD_DIR . $new_name;
                    if (!is_dir(UPLOAD_DIR)) {
                        mkdir(UPLOAD_DIR, 0755, true);
                    }
                    if (move_uploaded_file($tmp_name, $dest)) {
                        $photo_url = $new_name;
                    }
                }
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO beneficiaries (organization_id, first_name, last_name, age, location, story, needs, photo_url, sponsorship_goal, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$org_id, $first_name, $last_name, $age, $location, $story, $needs, $photo_url, $goal]);
                
                set_flash_message("Beneficiary added successfully. Pending admin approval.", "success");
                redirect('organization/dashboard.php');
            } catch (Exception $e) {
                $error = "Failed to add beneficiary. Please try again.";
            }
        }
    }
}

$page_title = "Add Beneficiary";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">Add New Beneficiary</h2>
                <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form action="add-beneficiary.php" method="POST" enctype="multipart/form-data">
                        <?php csrf_field(); ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name *</label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name *</label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Age *</label>
                                <input type="number" class="form-control" name="age" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Location *</label>
                                <input type="text" class="form-control" name="location" placeholder="City, Country" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Beneficiary Story *</label>
                            <textarea class="form-control" name="story" rows="4" placeholder="Tell the story of why this person needs sponsorship..." required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Specific Needs</label>
                            <textarea class="form-control" name="needs" rows="3" placeholder="E.g., School fees, uniform, medical care..."></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Total Sponsorship Goal (USD) *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="sponsorship_goal" step="0.01" min="10" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Photo</label>
                                <input type="file" class="form-control" name="photo" accept="image/jpeg, image/png">
                                <div class="form-text">Max size: 5MB (JPG, PNG)</div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <button type="submit" class="btn btn-primary btn-lg w-100">Submit Beneficiary for Approval</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
