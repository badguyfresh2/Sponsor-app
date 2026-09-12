<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user = require_auth();
$db = get_db();

// Fetch notifications
$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();

$unreadCount = 0;
$updateCount = 0;
$messageCount = 0;
$paymentCount = 0;

foreach ($notifications as $n) {
    if (!$n['is_read']) $unreadCount++;
    if ($n['type'] === 'update') $updateCount++;
    elseif ($n['type'] === 'message') $messageCount++;
    elseif ($n['type'] === 'payment') $paymentCount++;
}

$page_title = 'Notifications';
$show_back = true;
$back_url = BASE_URL . '/sponsor/dashboard.php';
$extra_js = ['notifications.js'];
require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- Native Device Push Notification Permission Prompt -->
<div id="pushPermissionBanner" class="hidden mb-3.5 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100 rounded-xl p-3 flex items-center justify-between gap-3 shadow-xs">
  <div class="flex items-center gap-2.5 min-w-0">
    <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center text-xs flex-shrink-0">
      <i class="fa-solid fa-bell"></i>
    </div>
    <div class="truncate">
      <div class="text-xs font-bold text-slate-900 leading-tight">Enable Device Push Alerts</div>
      <div class="text-[11px] text-slate-500 truncate">Receive child reports & NGO updates instantly</div>
    </div>
  </div>
  <button type="button" id="enablePushBtn" class="btn btn-primary btn-sm text-[11px] px-2.5 py-1.5 flex-shrink-0 whitespace-nowrap">
    Enable
  </button>
</div>


<!-- Controls & Filters Bar -->
<div class="flex flex-col gap-2.5 mb-3.5">
  <div class="flex items-center justify-between">
    <div class="text-xs font-bold text-slate-900">
      Activity Feed (<span id="totalNotifCount"><?= count($notifications) ?></span>)
    </div>
    <div class="flex items-center gap-3">
      <button type="button" id="markAllNotificationsBtn" class="text-[11px] font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1 cursor-pointer transition">
        <i class="fa-solid fa-check-double"></i> Mark all read
      </button>
      <button type="button" id="clearReadNotificationsBtn" class="text-[11px] font-semibold text-slate-500 hover:text-rose-600 flex items-center gap-1 cursor-pointer transition">
        <i class="fa-regular fa-trash-can"></i> Clear read
      </button>
    </div>
  </div>

  <!-- Filter Chips -->
  <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar">
    <button type="button" class="notif-filter-btn whitespace-nowrap px-3 py-1 text-[11px] font-bold rounded-full bg-blue-600 text-white active transition cursor-pointer" data-filter="all">
      All <span class="opacity-80 ml-0.5" id="chipCountAll"><?= count($notifications) ?></span>
    </button>
    <button type="button" class="notif-filter-btn whitespace-nowrap px-3 py-1 text-[11px] font-bold rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 transition cursor-pointer" data-filter="unread">
      Unread <span class="bg-blue-600 text-white rounded-full px-1.5 py-0.2 text-[9px] ml-0.5" id="chipCountUnread"><?= $unreadCount ?></span>
    </button>
    <button type="button" class="notif-filter-btn whitespace-nowrap px-3 py-1 text-[11px] font-bold rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 transition cursor-pointer" data-filter="update">
      Updates
    </button>
    <button type="button" class="notif-filter-btn whitespace-nowrap px-3 py-1 text-[11px] font-bold rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 transition cursor-pointer" data-filter="message">
      Messages
    </button>
    <button type="button" class="notif-filter-btn whitespace-nowrap px-3 py-1 text-[11px] font-bold rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 transition cursor-pointer" data-filter="payment">
      Payments
    </button>
  </div>
</div>

<!-- Empty State placeholder (hidden if notifications exist) -->
<div id="noNotifsCard" class="app-card text-center py-12 <?= empty($notifications) ? '' : 'hidden' ?>">
  <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-xl">
    <i class="fa-regular fa-bell-slash"></i>
  </div>
  <h3 class="font-bold text-slate-800 mb-1">No Notifications</h3>
  <p class="text-xs text-slate-500 mb-3">You're all caught up! Real updates from your sponsored beneficiaries will appear here.</p>
  <button type="button" class="btn btn-outline btn-sm text-xs py-1.5 sim-notif-btn" data-scenario="academic">
    <i class="fa-solid fa-wand-magic-sparkles text-amber-500 mr-1"></i> Send Test Notification
  </button>
</div>

<!-- Notifications List Container -->
<div id="notificationsList" class="space-y-2.5 <?= empty($notifications) ? 'hidden' : '' ?>">
  <?php foreach ($notifications as $n): ?>
    <?php 
      $type = $n['type'] ?? 'system';
      $iconClass = 'fa-bell text-blue-600 bg-blue-50';
      $typeLabel = 'Notice';
      if ($type === 'payment') {
          $iconClass = 'fa-credit-card text-emerald-600 bg-emerald-50';
          $typeLabel = 'Payment';
      } elseif ($type === 'update') {
          $iconClass = 'fa-award text-amber-600 bg-amber-50';
          $typeLabel = 'Child Update';
      } elseif ($type === 'message') {
          $iconClass = 'fa-comment-dots text-indigo-600 bg-indigo-50';
          $typeLabel = 'Message';
      }
      $iconName = explode(' ', $iconClass)[0];
      $colorStyles = implode(' ', array_slice(explode(' ', $iconClass), 1));
      $isUnread = empty($n['is_read']);
    ?>
    <div class="notification-item app-card p-3 mb-0 flex items-start gap-3 transition relative group <?= $isUnread ? 'bg-blue-50/40 border-blue-200' : 'bg-white' ?>"
         id="notif-item-<?= $n['id'] ?>"
         data-id="<?= $n['id'] ?>"
         data-type="<?= e($type) ?>"
         data-unread="<?= $isUnread ? '1' : '0' ?>">
      
      <!-- Icon Container -->
      <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 text-sm <?= $colorStyles ?>">
        <i class="fa-solid <?= $iconName ?>"></i>
      </div>

      <!-- Main Clickable Area -->
      <a href="<?= BASE_URL . ($n['link_url'] ?: '/sponsor/dashboard.php') ?>" 
         class="notif-link flex-1 min-w-0" 
         data-id="<?= $n['id'] ?>">
        <div class="flex items-center justify-between gap-1 mb-0.5">
          <div class="flex items-center gap-1.5 truncate">
            <span class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.2 rounded bg-slate-100 text-slate-600"><?= $typeLabel ?></span>
            <h4 class="text-xs font-bold text-slate-900 truncate"><?= e($n['title']) ?></h4>
          </div>
          <span class="text-[10px] text-slate-400 flex-shrink-0 whitespace-nowrap"><?= time_elapsed_string($n['created_at']) ?></span>
        </div>
        <p class="text-xs text-slate-600 leading-relaxed mb-1"><?= e($n['message']) ?></p>
        <span class="text-[10px] font-semibold text-blue-600 flex items-center gap-1 hover:underline">
          <span>View details</span>
          <i class="fa-solid fa-arrow-right text-[8px]"></i>
        </span>
      </a>

      <!-- Quick Action Buttons -->
      <div class="flex flex-col items-center gap-1.5 flex-shrink-0 ml-1">
        <?php if ($isUnread): ?>
          <button type="button" class="notif-toggle-read-btn w-6 h-6 rounded-full bg-blue-100 hover:bg-blue-200 text-blue-600 flex items-center justify-center text-[10px] transition cursor-pointer" title="Mark as read" data-id="<?= $n['id'] ?>">
            <i class="fa-solid fa-check"></i>
          </button>
        <?php else: ?>
          <button type="button" class="notif-toggle-read-btn w-6 h-6 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center text-[10px] transition cursor-pointer" title="Mark as unread" data-id="<?= $n['id'] ?>">
            <i class="fa-regular fa-envelope"></i>
          </button>
        <?php endif; ?>

        <button type="button" class="notif-delete-btn w-6 h-6 rounded-full hover:bg-rose-50 text-slate-300 hover:text-rose-600 flex items-center justify-center text-[10px] transition cursor-pointer" title="Delete notification" data-id="<?= $n['id'] ?>">
          <i class="fa-regular fa-trash-can"></i>
        </button>
      </div>

    </div>
  <?php endforeach; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
