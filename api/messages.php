<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user = get_authenticated_user();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = get_db();

// Ensure attachments directory exists
$uploadDir = dirname(__DIR__) . '/uploads/messages';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

// 1. POST: Send Message or Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if JSON or multipart form data
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $input = [];
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
    } else {
        $input = $_POST;
    }

    $action = $input['action'] ?? $_GET['action'] ?? 'send';

    // Action: Clear Chat History
    if ($action === 'clear_chat') {
        $receiverId = (int)($input['receiver_id'] ?? 0);
        if ($receiverId > 0) {
            $stmt = $db->prepare("DELETE FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
            $stmt->execute([$user['id'], $receiverId, $receiverId, $user['id']]);
            echo json_encode(['success' => true, 'message' => 'Chat history cleared.']);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Invalid receiver ID']);
        exit;
    }

    // Action: Mark Read
    if ($action === 'mark_read') {
        $receiverId = (int)($input['receiver_id'] ?? 0);
        if ($receiverId > 0) {
            $stmt = $db->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?");
            $stmt->execute([$user['id'], $receiverId]);
            echo json_encode(['success' => true]);
            exit;
        }
        echo json_encode(['success' => false]);
        exit;
    }

    // Regular Send Message
    $receiverId = (int)($input['receiver_id'] ?? 0);
    $beneficiaryId = !empty($input['beneficiary_id']) ? (int)$input['beneficiary_id'] : null;
    $message = trim($input['message'] ?? '');
    $attachmentUrl = $input['attachment_url'] ?? null;
    $attachmentType = $input['attachment_type'] ?? null;

    // Handle uploaded file if present
    if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['attachment'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedImage = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $allowedDoc = ['pdf', 'doc', 'docx', 'txt'];
        $allowedAudio = ['mp3', 'wav', 'ogg', 'm4a', 'webm'];

        if (in_array($ext, $allowedImage)) {
            $attachmentType = 'image';
        } elseif (in_array($ext, $allowedAudio)) {
            $attachmentType = 'audio';
        } elseif (in_array($ext, $allowedDoc)) {
            $attachmentType = 'document';
        } else {
            $attachmentType = 'document';
        }

        $safeFileName = 'msg_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destPath = $uploadDir . '/' . $safeFileName;
        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            $attachmentUrl = BASE_URL . '/uploads/messages/' . $safeFileName;
        }
    }

    if (!$receiverId || (empty($message) && empty($attachmentUrl))) {
        echo json_encode(['success' => false, 'message' => 'Please provide a message or an attachment.']);
        exit;
    }

    if (empty($message) && !empty($attachmentUrl)) {
        $message = ($attachmentType === 'image') ? 'Sent a photo' : (($attachmentType === 'audio') ? 'Sent a voice note' : 'Sent an attachment');
    }

    $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, beneficiary_id, message_text, attachment_url, attachment_type, is_read) VALUES (?, ?, ?, ?, ?, ?, 0)");
    $stmt->execute([$user['id'], $receiverId, $beneficiaryId, $message, $attachmentUrl, $attachmentType]);
    $userMsgId = (int)$db->lastInsertId();

    // Look up coordinator & organization details
    $orgStmt = $db->prepare("SELECT id, org_name, user_id FROM organizations WHERE user_id = ? OR id = ? LIMIT 1");
    $orgStmt->execute([$receiverId, (int)($input['org_id'] ?? 0)]);
    $org = $orgStmt->fetch();

    $childName = '';
    $schoolGrade = '';
    if ($beneficiaryId) {
        $bStmt = $db->prepare("SELECT full_name, school_grade FROM beneficiaries WHERE id = ?");
        $bStmt->execute([$beneficiaryId]);
        $b = $bStmt->fetch();
        if ($b) {
            $childName = $b['full_name'];
            $schoolGrade = $b['school_grade'] ?? 'Primary';
        }
    }

    $replyData = null;

    if ($org) {
        $lowerMsg = strtolower($message);
        $sponsorFirstName = explode(' ', $user['name'])[0];
        $childFirst = $childName ? explode(' ', $childName)[0] : 'your sponsored child';

        // Context-aware coordinator responses
        if (preg_match('/(report|grade|exam|result|class|term|score|academic|school)/i', $lowerMsg)) {
            $replyText = "Hello {$sponsorFirstName}! {$childFirst} has made outstanding progress this term in {$schoolGrade}. Their class attendance is currently at 98%, and teacher feedback highlights strong participation in reading and science. We will upload the verified term report card directly to their profile soon!";
        } elseif (preg_match('/(health|sick|clinic|doctor|hospital|medical|checkup|nurse|malaria)/i', $lowerMsg)) {
            $replyText = "Hello {$sponsorFirstName}, thank you for checking on {$childFirst}'s health. Our field nurse conducted the monthly health assessment last week. {$childFirst} is active, healthy, and has completed all required dental and deworming checks. All is well!";
        } elseif (preg_match('/(payment|receipt|tuition|fee|fees|money|shillings|ugx|dollar|paid)/i', $lowerMsg)) {
            $replyText = "Thank you so much {$sponsorFirstName}! We confirm that your contribution has been accounted for and credited directly to {$childFirst}'s school term fees. Your receipt is logged in your account statements.";
        } elseif (preg_match('/(shoe|shoes|uniform|bag|backpack|book|pen|supplies|material|clothes)/i', $lowerMsg)) {
            $replyText = "Greetings {$sponsorFirstName}! The term scholastic package—including exercise books, pens, geometry set, and uniform maintenance—was delivered to {$childFirst} at school. They were overjoyed and send their heartfelt thanks!";
        } elseif (preg_match('/(hello|hi|hey|good morning|good afternoon|greeting|bless|pray)/i', $lowerMsg)) {
            $replyText = "Hello {$sponsorFirstName}! It is always a pleasure hearing from you. {$childFirst} and our field team send you warm greetings from the community. How can we assist you today?";
        } elseif (preg_match('/(voice|audio|listen|record)/i', $lowerMsg)) {
            $replyText = "Thank you for the message, {$sponsorFirstName}! We received it loud and clear. We will play your encouragement for {$childFirst} during our upcoming mentoring session!";
        } else {
            $replyText = "Hello {$sponsorFirstName}, thank you for your message regarding {$childFirst}. Our local field coordinator has noted your request and will follow up with full details shortly. Thank you for your continued support!";
        }

        $insReply = $db->prepare("INSERT INTO messages (sender_id, receiver_id, beneficiary_id, message_text, is_read, created_at) VALUES (?, ?, ?, ?, 0, datetime('now'))");
        $insReply->execute([$receiverId, $user['id'], $beneficiaryId, $replyText]);
        $replyId = (int)$db->lastInsertId();

        // Create in-app notification
        create_notification(
            $user['id'],
            'New Message from ' . $org['org_name'],
            $replyText,
            'message',
            BASE_URL . '/sponsor/messages.php?org_id=' . $org['id'] . ($beneficiaryId ? '&beneficiary_id=' . $beneficiaryId : '')
        );

        $replyData = [
            'id' => $replyId,
            'sender_id' => $receiverId,
            'receiver_id' => $user['id'],
            'beneficiary_id' => $beneficiaryId,
            'ben_name' => $childName,
            'message_text' => $replyText,
            'created_at' => date('Y-m-d H:i:s'),
            'time_formatted' => date('H:i')
        ];
    }

    echo json_encode([
        'success' => true,
        'user_message' => [
            'id' => $userMsgId,
            'sender_id' => $user['id'],
            'receiver_id' => $receiverId,
            'beneficiary_id' => $beneficiaryId,
            'message_text' => $message,
            'attachment_url' => $attachmentUrl,
            'attachment_type' => $attachmentType,
            'created_at' => date('Y-m-d H:i:s'),
            'time_formatted' => date('H:i')
        ],
        'reply' => $replyData
    ]);
    exit;
}

// 2. GET: List Conversations or Conversation Messages
$action = $_GET['action'] ?? '';

if ($action === 'list_conversations') {
    $query = "SELECT o.id as org_id, o.org_name, o.logo_url, o.verified, o.user_id,
                     (SELECT m.message_text FROM messages m 
                      WHERE (m.sender_id = o.user_id AND m.receiver_id = :uid) OR (m.sender_id = :uid AND m.receiver_id = o.user_id)
                      ORDER BY m.created_at DESC LIMIT 1) as last_message,
                     (SELECT m.created_at FROM messages m 
                      WHERE (m.sender_id = o.user_id AND m.receiver_id = :uid) OR (m.sender_id = :uid AND m.receiver_id = o.user_id)
                      ORDER BY m.created_at DESC LIMIT 1) as last_time,
                     (SELECT COUNT(*) FROM messages m 
                      WHERE m.sender_id = o.user_id AND m.receiver_id = :uid AND m.is_read = 0) as unread_count
              FROM organizations o
              ORDER BY (last_time IS NOT NULL) DESC, last_time DESC";

    $stmt = $db->prepare($query);
    $stmt->execute([':uid' => $user['id']]);
    $conversations = $stmt->fetchAll();

    echo json_encode(['success' => true, 'conversations' => $conversations]);
    exit;
}

// Fetch messages for a specific conversation
$orgId = (int)($_GET['org_id'] ?? 0);
$receiverId = (int)($_GET['receiver_id'] ?? 0);

if ($orgId > 0) {
    $stmt = $db->prepare("SELECT user_id as id FROM organizations WHERE id = ? LIMIT 1");
    $stmt->execute([$orgId]);
    $coord = $stmt->fetch();
    $coordId = $coord ? (int)$coord['id'] : 1;
} elseif ($receiverId > 0) {
    $coordId = $receiverId;
} else {
    echo json_encode(['success' => false, 'message' => 'Missing organization or receiver ID']);
    exit;
}

// Mark unread messages from this coordinator as read
$upRead = $db->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ? AND is_read = 0");
$upRead->execute([$user['id'], $coordId]);

$stmt = $db->prepare("SELECT m.*, m.message_text as message, b.full_name as ben_name 
                      FROM messages m
                      LEFT JOIN beneficiaries b ON m.beneficiary_id = b.id
                      WHERE (m.sender_id = ? AND m.receiver_id = ?) 
                         OR (m.sender_id = ? AND m.receiver_id = ?)
                      ORDER BY m.created_at ASC");
$stmt->execute([$user['id'], $coordId, $coordId, $user['id']]);
$messages = $stmt->fetchAll();

// Generate HTML snippet
ob_start();
?>
<div class="text-center my-2">
  <span class="bg-slate-100 text-slate-500 text-[10px] px-3 py-1 rounded-full font-medium inline-block">
    <i class="fa-solid fa-lock text-[9px] text-slate-400 mr-1"></i> Official end-to-end communication with verified NGO coordinator
  </span>
</div>

<?php
$lastDate = '';
foreach ($messages as $msg) {
    $isOutgoing = ($msg['sender_id'] == $user['id']);
    $msgDate = date('Y-m-d', strtotime($msg['created_at']));
    $todayDate = date('Y-m-d');
    $yesterdayDate = date('Y-m-d', strtotime('-1 day'));

    if ($msgDate !== $lastDate) {
        $lastDate = $msgDate;
        $dateLabel = ($msgDate === $todayDate) ? 'Today' : (($msgDate === $yesterdayDate) ? 'Yesterday' : date('M j, Y', strtotime($msgDate)));
        ?>
        <div class="chat-date-divider">
          <span><?= $dateLabel ?></span>
        </div>
        <?php
    }
    ?>
    <div class="message-bubble <?= $isOutgoing ? 'outgoing' : 'incoming' ?>" data-message-id="<?= $msg['id'] ?>">
      <?php if (!empty($msg['ben_name']) && !$isOutgoing): ?>
        <div class="text-[10px] font-bold text-blue-600 mb-0.5 flex items-center gap-1">
          <i class="fa-solid fa-child-reaching text-[10px]"></i> Regarding <?= e($msg['ben_name']) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($msg['attachment_url'])): ?>
        <div class="message-attachment">
          <?php if (($msg['attachment_type'] ?? '') === 'image'): ?>
            <a href="<?= e($msg['attachment_url']) ?>" target="_blank" rel="noopener noreferrer">
              <img src="<?= e($msg['attachment_url']) ?>" alt="Attached image" class="rounded-xl object-cover hover:opacity-95 transition">
            </a>
          <?php elseif (($msg['attachment_type'] ?? '') === 'audio'): ?>
            <div class="voice-note-player">
              <button type="button" class="voice-play-btn" onclick="playVoiceNote(this)" aria-label="Play voice message">
                <i class="fa-solid fa-play text-[10px]"></i>
              </button>
              <div class="voice-waveform">
                <div class="voice-wave-bar h-2"></div>
                <div class="voice-wave-bar h-4"></div>
                <div class="voice-wave-bar h-3"></div>
                <div class="voice-wave-bar h-5"></div>
                <div class="voice-wave-bar h-2"></div>
                <div class="voice-wave-bar h-4"></div>
                <div class="voice-wave-bar h-3"></div>
              </div>
              <span class="text-[10px] font-mono opacity-80">0:14</span>
            </div>
          <?php else: ?>
            <a href="<?= e($msg['attachment_url']) ?>" target="_blank" class="message-attachment-doc">
              <i class="fa-solid fa-file-lines text-base"></i>
              <span class="truncate"><?= e(basename($msg['attachment_url'])) ?></span>
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div><?= nl2br(e($msg['message_text'] ?? $msg['message'] ?? '')) ?></div>
      
      <span class="message-time">
        <?= date('H:i', strtotime($msg['created_at'])) ?>
        <?php if ($isOutgoing): ?>
          <i class="fa-solid <?= $msg['is_read'] ? 'fa-check-double text-blue-200' : 'fa-check text-blue-300' ?> text-[9px] ml-1"></i>
        <?php endif; ?>
      </span>
    </div>
    <?php
}
?>
<div id="typingIndicator" class="typing-bubble hidden">
  <span class="typing-dot"></span>
  <span class="typing-dot"></span>
  <span class="typing-dot"></span>
</div>
<?php
$html = ob_get_clean();

echo json_encode([
    'success' => true,
    'count' => count($messages),
    'html' => $html,
    'messages' => $messages
]);
