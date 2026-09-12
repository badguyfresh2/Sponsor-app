<?php
// Timezone
date_default_timezone_set('Africa/Kampala');

// App constants
define('APP_NAME', 'NACMU');
define('APP_TAGLINE', 'Noah\'s Ark Children\'s Ministry Uganda');
define('APP_VERSION', '1.0.0');
define('DEFAULT_CURRENCY', 'UGX');

// Determine base URL dynamically (works on XAMPP /sponsor-app/ or root /)
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
if (strpos($scriptName, '/sponsor-app/') !== false) {
    define('BASE_URL', '/sponsor-app');
} else {
    define('BASE_URL', '');
}

// Secret key for HMAC token signing and security validation
define('APP_SECRET', 'sponsor_app_secret_key_8f3a9e2c1b7d4e5f6081234');

// Session ID parameter fallback (ensures session survives in strict iframe/3rd-party cookie blocking)
$incomingSid = $_POST['app_sid'] ?? $_GET['app_sid'] ?? $_SERVER['HTTP_X_APP_SID'] ?? null;
if (!empty($incomingSid) && is_string($incomingSid) && preg_match('/^[a-zA-Z0-9,-]{16,128}$/', $incomingSid)) {
    if (session_status() === PHP_SESSION_NONE) {
        session_id($incomingSid);
    }
}

// Session configuration with cross-site iframe compatibility
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', 0);
    ini_set('session.use_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'None');
    ini_set('session.cookie_secure', 1);

    session_set_cookie_params([
        'lifetime' => 86400 * 30, // 30 days
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'None',
    ]);
    session_start();
}

// CSRF Protection with cryptographic HMAC verification
function generate_csrf_token(): string {
    $timestamp = time();
    $hash = hash_hmac('sha256', "csrf:{$timestamp}", APP_SECRET);
    $token = "{$timestamp}:{$hash}";
    $_SESSION['csrf_token'] = $token;
    return $token;
}

function verify_csrf_token(?string $token): bool {
    if (empty($token) || !is_string($token)) {
        return false;
    }

    // 1. Direct match with session token
    if (!empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }

    // 2. Cryptographic HMAC signature fallback (valid for 4 hours, immune to cross-site cookie drops)
    $parts = explode(':', $token, 2);
    if (count($parts) === 2 && ctype_digit($parts[0])) {
        $timestamp = (int)$parts[0];
        $providedHash = $parts[1];
        $now = time();
        // Allow up to 4 hours (14400s) and allow 300s clock skew
        if ($timestamp <= ($now + 300) && ($now - $timestamp) <= 14400) {
            $expectedHash = hash_hmac('sha256', "csrf:{$timestamp}", APP_SECRET);
            if (hash_equals($expectedHash, $providedHash)) {
                $_SESSION['csrf_token'] = $token; // Re-anchor token in active session
                return true;
            }
        }
    }

    return false;
}

// Flash messaging helpers
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

function get_flash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
