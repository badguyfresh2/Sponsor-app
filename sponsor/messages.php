<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user = require_auth();
$db = get_db();

$activeOrgId = (int)($_GET['org_id'] ?? 0);
$beneficiaryId = (int)($_GET['beneficiary_id'] ?? 0);

if ($activeOrgId > 0) {
    // ==========================================
    // 1. Single Conversation Thread View
    // ==========================================
    $stmt = $db->prepare("SELECT o.*, o.user_id as coordinator_user_id 
                          FROM organizations o 
                          WHERE o.id = ?");
    $stmt->execute([$activeOrgId]);
    $activeOrg = $stmt->fetch();

    if (!$activeOrg) {
        header("Location: " . BASE_URL . "/sponsor/messages.php");
        exit;
    }

    $coordinatorId = (int)($activeOrg['coordinator_user_id'] ?? $activeOrg['user_id'] ?? 1);

    // Mark unread messages from this coordinator as read
    $upRead = $db->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?");
    $upRead->execute([$user['id'], $coordinatorId]);

    // Fetch children sponsored by this user under this organization
    $spChildStmt = $db->prepare("SELECT b.id, b.full_name, b.photo_url, b.school_grade 
                                 FROM sponsorships s 
                                 JOIN beneficiaries b ON s.beneficiary_id = b.id 
                                 WHERE s.sponsor_id = ? AND b.org_id = ? AND s.status = 'active'");
    $spChildStmt->execute([$user['id'], $activeOrgId]);
    $userSponsoredInOrg = $spChildStmt->fetchAll();

    // Default beneficiary if not set and user sponsors exactly one child in this org
    if ($beneficiaryId === 0 && !empty($userSponsoredInOrg)) {
        $beneficiaryId = (int)$userSponsoredInOrg[0]['id'];
    }

    // Load active child details if selected
    $refChild = null;
    if ($beneficiaryId > 0) {
        $stmt = $db->prepare("SELECT id, full_name, photo_url, school_grade, age FROM beneficiaries WHERE id = ?");
        $stmt->execute([$beneficiaryId]);
        $refChild = $stmt->fetch();
    }

    // Fetch conversation messages
    $stmt = $db->prepare("SELECT m.*, m.message_text as message, b.full_name as ben_name 
                          FROM messages m
                          LEFT JOIN beneficiaries b ON m.beneficiary_id = b.id
                          WHERE (m.sender_id = ? AND m.receiver_id = ?) 
                             OR (m.sender_id = ? AND m.receiver_id = ?)
                          ORDER BY m.created_at ASC");
    $stmt->execute([$user['id'], $coordinatorId, $coordinatorId, $user['id']]);
    $chatMessages = $stmt->fetchAll();

    $page_title = $activeOrg['org_name'];
    $show_back = true;
    $back_url = BASE_URL . '/sponsor/messages.php';
    $hide_bottom_nav = true;
    $is_chat_view = true;
    $extra_js = ['messages.js'];
    require_once dirname(__DIR__) . '/includes/header.php';
    ?>

    <!-- Conversation Header Bar -->
    <div class="chat-header-ribbon border-b border-slate-200">
      <div class="flex items-center gap-2.5 min-w-0 flex-1">
        <div class="relative flex-shrink-0">
          <img src="<?= e($activeOrg['logo_url']) ?>" alt="<?= e($activeOrg['org_name']) ?>" class="w-9 h-9 rounded-full object-cover border border-slate-200">
          <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-500 border-2 border-white rounded-full" title="Online"></span>
        </div>
        <div class="truncate flex-1">
          <div class="flex items-center gap-1.5">
            <span class="text-xs font-bold text-slate-900 truncate"><?= e($activeOrg['org_name']) ?></span>
            <?php if ($activeOrg['verified']): ?>
              <i class="fa-solid fa-circle-check text-blue-600 text-[11px]" title="Verified NGO"></i>
            <?php endif; ?>
          </div>
          <div class="text-[10px] text-emerald-600 font-semibold flex items-center gap-1">
            <span>Field Coordinator Online</span>
          </div>
        </div>
      </div>

      <!-- Child Context Badge & Quick Actions Menu -->
      <div class="flex items-center gap-1.5 flex-shrink-0">
        <?php if ($refChild): ?>
          <button type="button" id="openChildSelectorBtn" class="flex items-center gap-1.5 bg-blue-50 border border-blue-200 hover:bg-blue-100 rounded-lg px-2 py-1 text-[11px] font-semibold text-blue-700 transition" title="Switch Referenced Child">
            <img src="<?= e($refChild['photo_url']) ?>" alt="<?= e($refChild['full_name']) ?>" class="w-4 h-4 rounded-full object-cover">
            <span class="truncate max-w-[70px]"><?= e(explode(' ', $refChild['full_name'])[0]) ?></span>
            <i class="fa-solid fa-chevron-down text-[9px] text-blue-500"></i>
          </button>
        <?php elseif (!empty($userSponsoredInOrg)): ?>
          <button type="button" id="openChildSelectorBtn" class="btn btn-outline btn-sm text-[10px] py-1 px-2 border-dashed border-blue-300 text-blue-600">
            <i class="fa-solid fa-plus text-[9px]"></i> Link Child
          </button>
        <?php endif; ?>

        <!-- Dropdown Menu Trigger -->
        <div class="relative">
          <button type="button" id="chatOptionsBtn" class="w-8 h-8 rounded-full flex items-center justify-center text-slate-500 hover:bg-slate-100 transition" aria-label="Chat Options">
            <i class="fa-solid fa-ellipsis-vertical text-xs"></i>
          </button>
          
          <div id="chatOptionsMenu" class="hidden absolute right-0 mt-1 w-44 bg-white rounded-xl shadow-lg border border-slate-200 py-1.5 z-50 text-xs">
            <?php if ($refChild): ?>
              <a href="<?= BASE_URL ?>/sponsor/beneficiary.php?id=<?= $refChild['id'] ?>" class="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50">
                <i class="fa-solid fa-user text-blue-600 w-4"></i> View <?= e(explode(' ', $refChild['full_name'])[0]) ?>'s Profile
              </a>
            <?php endif; ?>
            <button type="button" id="openChildSelectorMenuBtn" class="w-full text-left flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50">
              <i class="fa-solid fa-arrows-rotate text-emerald-600 w-4"></i> Switch Child
            </button>
            <button type="button" id="clearChatBtn" class="w-full text-left flex items-center gap-2 px-3 py-2 text-rose-600 hover:bg-rose-50 border-t border-slate-100 mt-1">
              <i class="fa-solid fa-trash-can text-rose-500 w-4"></i> Clear Chat History
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Active Child Banner (if selected) -->
    <?php if ($refChild): ?>
      <div class="bg-blue-50/70 border-b border-blue-100 px-3.5 py-1.5 flex items-center justify-between text-[11px] text-blue-900">
        <div class="flex items-center gap-1.5 truncate">
          <i class="fa-solid fa-circle-info text-blue-500 text-xs"></i>
          <span>Chatting regarding <strong><?= e($refChild['full_name']) ?></strong> (<?= e($refChild['school_grade'] ?? 'Student') ?>)</span>
        </div>
        <a href="<?= BASE_URL ?>/sponsor/beneficiary.php?id=<?= $refChild['id'] ?>" class="font-bold text-blue-600 hover:underline flex-shrink-0">View Profile &rarr;</a>
      </div>
    <?php endif; ?>

    <!-- Messages Display Box -->
    <div id="chatMessagesBox" class="chat-messages-container">
      <div class="text-center my-2">
        <span class="bg-slate-100 text-slate-500 text-[10px] px-3 py-1 rounded-full font-medium inline-block">
          <i class="fa-solid fa-lock text-[9px] text-slate-400 mr-1"></i> Official end-to-end communication with verified NGO coordinator
        </span>
      </div>

      <?php 
      $lastDate = '';
      foreach ($chatMessages as $msg): 
          $isOutgoing = ($msg['sender_id'] == $user['id']);
          $msgDate = date('Y-m-d', strtotime($msg['created_at']));
          $todayDate = date('Y-m-d');
          $yesterdayDate = date('Y-m-d', strtotime('-1 day'));

          if ($msgDate !== $lastDate) {
              $lastDate = $msgDate;
              $dateLabel = ($msgDate === $todayDate) ? 'Today' : (($msgDate === $yesterdayDate) ? 'Yesterday' : date('M j, Y', strtotime($msgDate)));
              echo '<div class="chat-date-divider"><span>' . e($dateLabel) . '</span></div>';
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
                  <img src="<?= e($msg['attachment_url']) ?>" alt="Photo attachment" class="rounded-xl object-cover hover:opacity-95 transition">
                </a>
              <?php elseif (($msg['attachment_type'] ?? '') === 'audio'): ?>
                <div class="voice-note-player">
                  <button type="button" class="voice-play-btn" onclick="playVoiceNote(this)" aria-label="Play voice note">
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
                  <span class="text-[10px] font-mono opacity-80">0:12</span>
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
      <?php endforeach; ?>

      <!-- Animated Typing Indicator (Dynamic) -->
      <div id="typingIndicator" class="typing-bubble hidden">
        <span class="typing-dot"></span>
        <span class="typing-dot"></span>
        <span class="typing-dot"></span>
      </div>
    </div>

    <!-- Quick Inquiry Suggestions Strip -->
    <div class="chat-quick-chips">
      <button type="button" class="chat-quick-chip" data-text="Can you please share Brian's latest school term progress report?">
        <i class="fa-solid fa-graduation-cap text-blue-500"></i> Term Report
      </button>
      <button type="button" class="chat-quick-chip" data-text="How was the recent clinic health checkup and well-being?">
        <i class="fa-solid fa-heart-pulse text-rose-500"></i> Health Status
      </button>
      <button type="button" class="chat-quick-chip" data-text="Did Brian receive the school books and scholastic materials?">
        <i class="fa-solid fa-book-open text-amber-500"></i> Scholastic Supplies
      </button>
      <button type="button" class="chat-quick-chip" data-text="Please send my warmest greetings and blessings to the child!">
        <i class="fa-solid fa-hand-holding-heart text-emerald-500"></i> Send Blessing
      </button>
      <button type="button" class="chat-quick-chip" data-text="Confirming my recent monthly sponsorship contribution payment.">
        <i class="fa-solid fa-receipt text-indigo-500"></i> Confirm Payment
      </button>
    </div>

    <!-- Staged Attachment Preview (Hidden by default) -->
    <div id="attachmentPreviewBar" class="chat-attachment-preview hidden">
      <img id="previewImage" src="" alt="Preview" class="preview-thumb hidden">
      <div id="previewDocInfo" class="text-xs text-slate-700 truncate font-medium flex-1 hidden">
        <i class="fa-solid fa-paperclip text-blue-600 mr-1"></i> <span id="previewDocName"></span>
      </div>
      <button type="button" id="removeAttachmentBtn" class="text-slate-400 hover:text-rose-500 text-xs px-2 py-1" aria-label="Remove attachment">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <!-- Pinned Bottom Chat Input Bar -->
    <div class="chat-input-bar">
      <form id="chatForm" class="chat-input-wrapper" data-receiver-id="<?= $coordinatorId ?>" data-org-id="<?= $activeOrgId ?>" data-beneficiary-id="<?= $beneficiaryId ?>">
        
        <!-- File / Photo Attachment Button -->
        <input type="file" id="fileAttachmentInput" class="hidden" accept="image/*,.pdf,.doc,.docx">
        <button type="button" id="attachFileBtn" class="chat-attach-btn" title="Attach photo or document" aria-label="Attach File">
          <i class="fa-solid fa-paperclip text-sm"></i>
        </button>



        <!-- Message Text Input Pill -->
        <div class="chat-input-pill flex-1">
          <input type="text" id="chatInput" class="chat-input-field" placeholder="Type an inquiry or message..." autocomplete="off" required>
        </div>

        <!-- Send Button -->
        <button type="submit" id="chatSendBtn" class="chat-send-btn" aria-label="Send message">
          <i class="fa-solid fa-paper-plane text-xs"></i>
        </button>
      </form>
    </div>

    <!-- Modal: Switch Referenced Child -->
    <div id="childSelectorModal" class="modal-overlay">
      <div class="bottom-sheet">
        <div class="sheet-handle"></div>
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-bold text-slate-900">Select Child for this Message</h3>
          <button type="button" class="closeModalBtn text-slate-400 hover:text-slate-700 text-sm">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
        
        <div class="space-y-2 mb-4">
          <a href="<?= BASE_URL ?>/sponsor/messages.php?org_id=<?= $activeOrgId ?>&beneficiary_id=0" class="p-3 rounded-xl border <?= $beneficiaryId === 0 ? 'border-blue-600 bg-blue-50/50' : 'border-slate-200 hover:bg-slate-50' ?> flex items-center justify-between transition">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 text-sm">
                <i class="fa-solid fa-building"></i>
              </div>
              <div>
                <div class="text-xs font-bold text-slate-900">General Inquiry</div>
                <div class="text-[11px] text-slate-500">Not specific to a single child</div>
              </div>
            </div>
            <?php if ($beneficiaryId === 0): ?>
              <i class="fa-solid fa-circle-check text-blue-600"></i>
            <?php endif; ?>
          </a>

          <?php foreach ($userSponsoredInOrg as $ch): ?>
            <a href="<?= BASE_URL ?>/sponsor/messages.php?org_id=<?= $activeOrgId ?>&beneficiary_id=<?= $ch['id'] ?>" class="p-3 rounded-xl border <?= $beneficiaryId === (int)$ch['id'] ? 'border-blue-600 bg-blue-50/50' : 'border-slate-200 hover:bg-slate-50' ?> flex items-center justify-between transition">
              <div class="flex items-center gap-2.5">
                <img src="<?= e($ch['photo_url']) ?>" alt="<?= e($ch['full_name']) ?>" class="w-9 h-9 rounded-full object-cover">
                <div>
                  <div class="text-xs font-bold text-slate-900"><?= e($ch['full_name']) ?></div>
                  <div class="text-[11px] text-slate-500"><?= e($ch['school_grade'] ?? 'Sponsored Child') ?></div>
                </div>
              </div>
              <?php if ($beneficiaryId === (int)$ch['id']): ?>
                <i class="fa-solid fa-circle-check text-blue-600"></i>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>



    <?php
} else {
    // ==========================================
    // 2. Conversation List View
    // ==========================================
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

    // Fetch user's sponsored children with their organization
    $spQuery = "SELECT b.id as ben_id, b.full_name as ben_name, b.photo_url as ben_photo, b.school_grade,
                       o.id as org_id, o.org_name
                FROM sponsorships s
                JOIN beneficiaries b ON s.beneficiary_id = b.id
                JOIN organizations o ON b.org_id = o.id
                WHERE s.sponsor_id = ? AND s.status = 'active'";
    $spStmt = $db->prepare($spQuery);
    $spStmt->execute([$user['id']]);
    $mySponsoredChildren = $spStmt->fetchAll();

    $allOrgsStmt = $db->query("SELECT id, org_name, logo_url, verified FROM organizations ORDER BY org_name ASC");
    $allOrganizations = $allOrgsStmt->fetchAll();

    $page_title = 'Messages';
    $current_page = 'messages';
    $extra_js = ['messages.js'];
    require_once dirname(__DIR__) . '/includes/header.php';
    ?>

    <!-- Header Section with Action -->
    <div class="flex items-center justify-between mb-3.5">
      <div>
        <h2 class="text-lg font-black tracking-tight text-slate-900">Messages</h2>
        <p class="text-xs text-slate-500">Direct coordinator communication & updates</p>
      </div>
      <button type="button" id="openNewChatModalBtn" class="btn btn-primary btn-sm text-xs font-bold flex items-center gap-1.5 shadow-xs py-2 px-3">
        <i class="fa-solid fa-pen-to-square text-xs"></i>
        <span>New Inquiry</span>
      </button>
    </div>

    <!-- Search Input -->
    <div class="search-bar-wrapper mb-3.5">
      <i class="fa-solid fa-magnifying-glass search-icon text-slate-400"></i>
      <input type="search" id="conversationSearchInput" class="search-input" placeholder="Search conversations by NGO or child..." autocomplete="off">
    </div>

    <!-- Filter Filter Chips -->
    <div class="flex items-center gap-2 mb-4 overflow-x-auto pb-1" id="convFilterChips">
      <button type="button" class="filter-chip active text-xs py-1 px-3 rounded-full font-bold bg-blue-600 text-white transition" data-filter="all">All Chats</button>
      <button type="button" class="filter-chip text-xs py-1 px-3 rounded-full font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition" data-filter="unread">Unread</button>
      <button type="button" class="filter-chip text-xs py-1 px-3 rounded-full font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition" data-filter="sponsored">My Sponsored</button>
    </div>

    <!-- Conversation Cards List -->
    <div id="conversationsContainer" class="space-y-2.5">
      <?php if (empty($conversations)): ?>
        <div class="app-card text-center py-10">
          <div class="w-14 h-14 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3 text-xl">
            <i class="fa-regular fa-comment-dots"></i>
          </div>
          <h3 class="font-bold text-slate-800 mb-1">No Messages Yet</h3>
          <p class="text-xs text-slate-500 max-w-xs mx-auto mb-4">Message supervising field coordinators directly to receive updates on your sponsored children.</p>
          <button type="button" id="openNewChatEmptyBtn" class="btn btn-primary btn-sm">Start a Conversation</button>
        </div>
      <?php else: ?>
        <?php foreach ($conversations as $conv): ?>
          <a href="<?= BASE_URL ?>/sponsor/messages.php?org_id=<?= $conv['org_id'] ?>" 
             class="conversation-item app-card p-3.5 mb-0 flex items-center gap-3 hover:border-blue-400 hover:shadow-sm transition block" 
             data-org-name="<?= strtolower(e($conv['org_name'])) ?>" 
             data-unread="<?= $conv['unread_count'] > 0 ? '1' : '0' ?>" 
             data-last-message="<?= strtolower(e($conv['last_message'] ?? '')) ?>">
            
            <div class="relative flex-shrink-0">
              <img src="<?= e($conv['logo_url']) ?>" alt="<?= e($conv['org_name']) ?>" class="w-12 h-12 rounded-2xl object-cover border border-slate-100">
              <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-emerald-500 border-2 border-white rounded-full"></span>
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between mb-1">
                <div class="flex items-center gap-1.5 truncate">
                  <h3 class="text-xs font-bold text-slate-900 truncate"><?= e($conv['org_name']) ?></h3>
                  <?php if ($conv['verified']): ?>
                    <i class="fa-solid fa-circle-check text-blue-600 text-[10px]" title="Verified NGO"></i>
                  <?php endif; ?>
                </div>
                <span class="text-[10px] text-slate-400 flex-shrink-0">
                  <?= $conv['last_time'] ? time_elapsed_string($conv['last_time']) : 'Just now' ?>
                </span>
              </div>
              
              <p class="text-xs text-slate-500 truncate <?= $conv['unread_count'] > 0 ? 'font-bold text-slate-900' : '' ?>">
                <?= e($conv['last_message'] ?: 'Tap to start conversation with field coordinator...') ?>
              </p>
            </div>

            <?php if ($conv['unread_count'] > 0): ?>
              <span class="w-5 h-5 rounded-full bg-blue-600 text-white text-[10px] font-bold flex items-center justify-center flex-shrink-0 shadow-xs">
                <?= $conv['unread_count'] ?>
              </span>
            <?php else: ?>
              <i class="fa-solid fa-chevron-right text-slate-300 text-xs flex-shrink-0"></i>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Quick Modal: New Inquiry / Conversation Picker -->
    <div id="newChatModal" class="modal-overlay">
      <div class="bottom-sheet">
        <div class="sheet-handle"></div>
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-sm font-bold text-slate-900">Start New Inquiry</h3>
            <p class="text-xs text-slate-500">Select an organization or sponsored child</p>
          </div>
          <button type="button" class="closeModalBtn text-slate-400 hover:text-slate-700 text-sm">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <?php if (!empty($mySponsoredChildren)): ?>
          <div class="mb-4">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">My Sponsored Children</div>
            <div class="space-y-2">
              <?php foreach ($mySponsoredChildren as $msc): ?>
                <a href="<?= BASE_URL ?>/sponsor/messages.php?org_id=<?= $msc['org_id'] ?>&beneficiary_id=<?= $msc['ben_id'] ?>" class="p-2.5 rounded-xl border border-slate-200 hover:border-blue-500 hover:bg-blue-50/50 flex items-center justify-between transition">
                  <div class="flex items-center gap-2.5">
                    <img src="<?= e($msc['ben_photo']) ?>" alt="<?= e($msc['ben_name']) ?>" class="w-10 h-10 rounded-xl object-cover">
                    <div>
                      <div class="text-xs font-bold text-slate-900"><?= e($msc['ben_name']) ?></div>
                      <div class="text-[11px] text-slate-500"><?= e($msc['org_name']) ?> &bull; <?= e($msc['school_grade'] ?? 'Student') ?></div>
                    </div>
                  </div>
                  <span class="btn btn-primary btn-sm text-[10px] py-1 px-2.5">Message</span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <div>
          <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Partner Organizations</div>
          <div class="space-y-2">
            <?php foreach ($allOrganizations as $orgItem): ?>
              <a href="<?= BASE_URL ?>/sponsor/messages.php?org_id=<?= $orgItem['id'] ?>" class="p-2.5 rounded-xl border border-slate-200 hover:border-blue-500 hover:bg-blue-50/50 flex items-center justify-between transition">
                <div class="flex items-center gap-2.5">
                  <img src="<?= e($orgItem['logo_url']) ?>" alt="<?= e($orgItem['org_name']) ?>" class="w-10 h-10 rounded-xl object-cover">
                  <div>
                    <div class="text-xs font-bold text-slate-900 flex items-center gap-1">
                      <span><?= e($orgItem['org_name']) ?></span>
                      <?php if ($orgItem['verified']): ?>
                        <i class="fa-solid fa-circle-check text-blue-600 text-[10px]"></i>
                      <?php endif; ?>
                    </div>
                    <div class="text-[11px] text-emerald-600 font-medium">Field Coordinators Available</div>
                  </div>
                </div>
                <i class="fa-solid fa-chevron-right text-slate-300 text-xs"></i>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <?php
}

require_once dirname(__DIR__) . '/includes/footer.php';
?>
