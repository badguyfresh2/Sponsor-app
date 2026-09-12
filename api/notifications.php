<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user = get_authenticated_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = get_db();

// Handle GET requests (polling, click-to-redirect, listing)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'poll';

    // Click redirect action: mark notification read and navigate to link
    if ($action === 'click') {
        $notifId = (int)($_GET['id'] ?? 0);
        if ($notifId > 0) {
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$notifId, $user['id']]);

            $stmt = $db->prepare("SELECT link_url FROM notifications WHERE id = ? AND user_id = ?");
            $stmt->execute([$notifId, $user['id']]);
            $notif = $stmt->fetch();
            $targetUrl = !empty($notif['link_url']) ? $notif['link_url'] : '/sponsor/dashboard.php';

            // If browser clicked normal link, redirect
            if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html') !== false) {
                header("Location: " . BASE_URL . $targetUrl);
                exit;
            }

            echo json_encode([
                'success' => true,
                'redirect' => BASE_URL . $targetUrl,
                'unread_count' => get_unread_notifications_count($user['id'])
            ]);
            exit;
        }
    }

    // Polling action: returns unread count and any new notifications
    if ($action === 'poll') {
        $sinceId = (int)($_GET['since_id'] ?? 0);
        $unreadCount = get_unread_notifications_count($user['id']);

        $newNotifs = [];
        if ($sinceId > 0) {
            $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND id > ? ORDER BY id DESC LIMIT 10");
            $stmt->execute([$user['id'], $sinceId]);
            $rows = $stmt->fetchAll();
            foreach ($rows as $row) {
                $row['time_elapsed'] = time_elapsed_string($row['created_at']);
                $newNotifs[] = $row;
            }
        }

        $latestStmt = $db->prepare("SELECT MAX(id) FROM notifications WHERE user_id = ?");
        $latestStmt->execute([$user['id']]);
        $latestId = (int)$latestStmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'unread_count' => $unreadCount,
            'latest_id' => $latestId,
            'new_notifications' => $newNotifs
        ]);
        exit;
    }

    // List action: fetch recent notifications
    $limit = (int)($_GET['limit'] ?? 20);
    $filter = $_GET['filter'] ?? 'all';
    $sql = "SELECT * FROM notifications WHERE user_id = ? " . ($filter === 'unread' ? "AND is_read = 0 " : "") . "ORDER BY created_at DESC LIMIT ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$user['id'], $limit]);
    $notifications = $stmt->fetchAll();

    foreach ($notifications as &$n) {
        $n['time_elapsed'] = time_elapsed_string($n['created_at']);
    }

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => get_unread_notifications_count($user['id'])
    ]);
    exit;
}

// Handle POST actions
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? '';

// 1. Mark all notifications as read
if ($action === 'mark_all_read') {
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    echo json_encode([
        'success' => true,
        'unread_count' => 0
    ]);
    exit;
}

// 2. Mark single notification as read
if ($action === 'mark_read') {
    $notifId = (int)($input['notification_id'] ?? 0);
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notifId, $user['id']]);
    echo json_encode([
        'success' => true,
        'unread_count' => get_unread_notifications_count($user['id'])
    ]);
    exit;
}

// 3. Mark single notification as unread (toggle)
if ($action === 'mark_unread') {
    $notifId = (int)($input['notification_id'] ?? 0);
    $stmt = $db->prepare("UPDATE notifications SET is_read = 0 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notifId, $user['id']]);
    echo json_encode([
        'success' => true,
        'unread_count' => get_unread_notifications_count($user['id'])
    ]);
    exit;
}

// 4. Delete single notification
if ($action === 'delete') {
    $notifId = (int)($input['notification_id'] ?? 0);
    $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notifId, $user['id']]);
    echo json_encode([
        'success' => true,
        'unread_count' => get_unread_notifications_count($user['id'])
    ]);
    exit;
}

// 5. Clear all read notifications
if ($action === 'clear_all_read') {
    $stmt = $db->prepare("DELETE FROM notifications WHERE user_id = ? AND is_read = 1");
    $stmt->execute([$user['id']]);
    echo json_encode([
        'success' => true,
        'unread_count' => get_unread_notifications_count($user['id'])
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
