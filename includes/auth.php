<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

function is_authenticated(): bool {
    return !empty($_SESSION['user_id']);
}

function get_authenticated_user(): ?array {
    if (!is_authenticated()) {
        return null;
    }

    $db = get_db();
    $stmt = $db->prepare("SELECT u.*, sp.bio, sp.location as sponsor_location, sp.address, sp.country, sp.preferred_currency, sp.monthly_budget, sp.anonymous_mode, sp.notification_email, sp.notification_sms 
                         FROM users u 
                         LEFT JOIN sponsor_profiles sp ON u.id = sp.user_id 
                         WHERE u.id = ? AND u.status = 'active'");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        logout_user();
        return null;
    }

    return $user;
}

function require_auth(): array {
    if (!is_authenticated()) {
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        header("Location: " . BASE_URL . "/auth/login.php?redirect=" . urlencode($currentUri));
        exit;
    }

    $user = get_authenticated_user();
    if (!$user) {
        header("Location: " . BASE_URL . "/auth/login.php");
        exit;
    }

    return $user;
}

function login_user(string $email, string $password, bool $remember = false): array {
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([trim(strtolower($email))]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'message' => 'Invalid email address or password.'];
    }

    if ($user['status'] !== 'active') {
        return ['success' => false, 'message' => 'Your account is currently suspended or pending verification.'];
    }

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Invalid email address or password.'];
    }

    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['logged_in_at'] = time();

    // Ensure sponsor profile exists if sponsor
    if ($user['role'] === 'sponsor') {
        $pStmt = $db->prepare("SELECT id FROM sponsor_profiles WHERE user_id = ?");
        $pStmt->execute([$user['id']]);
        if (!$pStmt->fetch()) {
            $ins = $db->prepare("INSERT INTO sponsor_profiles (user_id, preferred_currency, monthly_budget) VALUES (?, 'UGX', 0.00)");
            $ins->execute([$user['id']]);
        }
    }

    return ['success' => true, 'user' => $user];
}

function register_sponsor(string $name, string $email, string $password, ?string $phone = null): array {
    $db = get_db();
    $email = trim(strtolower($email));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
    }

    $chk = $db->prepare("SELECT id FROM users WHERE email = ?");
    $chk->execute([$email]);
    if ($chk->fetch()) {
        return ['success' => false, 'message' => 'An account with this email already exists.'];
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $defaultAvatar = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80';

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, role, phone, avatar_url, status) VALUES (?, ?, ?, 'sponsor', ?, ?, 'active')");
        $stmt->execute([$name, $email, $passwordHash, $phone, $defaultAvatar]);
        $newUserId = $db->lastInsertId();

        $pStmt = $db->prepare("INSERT INTO sponsor_profiles (user_id, preferred_currency, monthly_budget, notification_email, notification_sms) VALUES (?, 'UGX', 0.00, 1, 1)");
        $pStmt->execute([$newUserId]);

        // Add welcome notification
        $notif = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link_url, is_read) VALUES (?, 'Welcome to Sponsor!', 'Explore vulnerable children seeking educational and health sponsorships today.', 'system', '/sponsor/discover.php', 0)");
        $notif->execute([$newUserId]);

        $db->commit();

        return login_user($email, $password);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        return ['success' => false, 'message' => 'Registration error: ' . $e->getMessage()];
    }
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
