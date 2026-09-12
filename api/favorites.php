<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$user = get_authenticated_user();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$beneficiaryId = (int)($input['beneficiary_id'] ?? 0);

if (!$beneficiaryId) {
    echo json_encode(['success' => false, 'message' => 'Invalid beneficiary id']);
    exit;
}

$db = get_db();

// Check if currently favorited
$stmt = $db->prepare("SELECT id FROM favorites WHERE sponsor_id = ? AND beneficiary_id = ?");
$stmt->execute([$user['id'], $beneficiaryId]);
$exists = $stmt->fetch();

if ($exists) {
    $del = $db->prepare("DELETE FROM favorites WHERE id = ?");
    $del->execute([$exists['id']]);
    echo json_encode(['success' => true, 'is_favorite' => false]);
} else {
    $ins = $db->prepare("INSERT INTO favorites (sponsor_id, beneficiary_id) VALUES (?, ?)");
    $ins->execute([$user['id'], $beneficiaryId]);
    echo json_encode(['success' => true, 'is_favorite' => true]);
}
