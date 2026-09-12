<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

function e(?string $string): string {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

function format_currency(float|int $amount, string $currency = 'UGX'): string {
    if ($currency === 'UGX') {
        return 'UGX ' . number_format($amount, 0);
    }
    return $currency . ' ' . number_format($amount, 2);
}

function time_elapsed_string(string $datetime, bool $full = false): string {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diffWeeks = floor($diff->d / 7);
    $diffDays = $diff->d - ($diffWeeks * 7);

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'min',
        's' => 'sec',
    ];

    $values = [
        'y' => $diff->y,
        'm' => $diff->m,
        'w' => $diffWeeks,
        'd' => $diffDays,
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s,
    ];

    foreach ($string as $k => &$v) {
        if ($values[$k]) {
            $v = $values[$k] . ' ' . $v . ($values[$k] > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

function get_unread_notifications_count(int $userId): int {
    $db = get_db();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function create_notification(int $userId, string $title, string $message, string $type = 'system', string $linkUrl = '', bool $isRead = false): int {
    $db = get_db();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link_url, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?, datetime('now'))");
    $stmt->execute([$userId, $title, $message, $type, $linkUrl, $isRead ? 1 : 0]);
    return (int)$db->lastInsertId();
}

function get_unread_messages_count(int $userId): int {
    $db = get_db();
    $stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function is_favorite(int $sponsorId, int $beneficiaryId): bool {
    $db = get_db();
    $stmt = $db->prepare("SELECT id FROM favorites WHERE sponsor_id = ? AND beneficiary_id = ?");
    $stmt->execute([$sponsorId, $beneficiaryId]);
    return (bool)$stmt->fetch();
}

function render_status_badge(string $status): string {
    $status = strtolower($status);
    $badges = [
        'active' => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'label' => 'Active', 'icon' => 'fa-check-circle'],
        'fully_sponsored' => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'label' => 'Sponsored', 'icon' => 'fa-shield-heart'],
        'partially_sponsored' => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'label' => 'Partial', 'icon' => 'fa-clock'],
        'available' => ['bg' => 'bg-blue-50 text-blue-700 border-blue-200', 'label' => 'Needs Sponsor', 'icon' => 'fa-hand-holding-heart'],
        'completed' => ['bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'label' => 'Completed', 'icon' => 'fa-flag-checkered'],
        'paused' => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'label' => 'Paused', 'icon' => 'fa-pause-circle'],
        'cancelled' => ['bg' => 'bg-rose-50 text-rose-700 border-rose-200', 'label' => 'Cancelled', 'icon' => 'fa-times-circle'],
    ];

    $badge = $badges[$status] ?? ['bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'label' => ucfirst($status), 'icon' => 'fa-circle-info'];

    return sprintf(
        '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border %s"><i class="fa-solid %s text-[10px]"></i> %s</span>',
        $badge['bg'],
        $badge['icon'],
        $badge['label']
    );
}
