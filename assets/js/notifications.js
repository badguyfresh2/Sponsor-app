/**
 * Real-Time Notifications Hub Script
 */

document.addEventListener('DOMContentLoaded', () => {
  const markAllBtn = document.getElementById('markAllNotificationsBtn');
  const clearReadBtn = document.getElementById('clearReadNotificationsBtn');
  const filterTabs = document.querySelectorAll('.notif-filter-btn');
  const notifsList = document.getElementById('notificationsList');
  const noNotifsCard = document.getElementById('noNotifsCard');
  const pushBanner = document.getElementById('pushPermissionBanner');
  const enablePushBtn = document.getElementById('enablePushBtn');

  // 1. Device Push Notifications Permission Banner
  if (pushBanner && ('Notification' in window)) {
    if (Notification.permission === 'default') {
      pushBanner.classList.remove('hidden');
    }
    if (enablePushBtn) {
      enablePushBtn.addEventListener('click', async () => {
        try {
          const perm = await Notification.requestPermission();
          if (perm === 'granted') {
            pushBanner.classList.add('hidden');
            showToast('Device push notifications enabled!', 'fa-circle-check');
            new Notification('Push Notifications Active', {
              body: 'You will receive immediate alerts for your sponsored children.',
              icon: `${window.BASE_URL || ''}/assets/icons/icon-192.png`
            });
          } else {
            showToast('Permission not granted', 'fa-info-circle');
            pushBanner.classList.add('hidden');
          }
        } catch (e) {
          console.error(e);
        }
      });
    }
  }

  // 2. Mark All as Read
  if (markAllBtn) {
    markAllBtn.addEventListener('click', async () => {
      try {
        const res = await fetch(`${window.BASE_URL || ''}/api/notifications.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'mark_all_read' })
        });
        const data = await res.json();
        if (data.success) {
          document.querySelectorAll('.notification-item').forEach(item => {
            item.setAttribute('data-unread', '0');
            item.classList.remove('bg-blue-50/40', 'border-blue-200');
            item.classList.add('bg-white');

            const toggleBtn = item.querySelector('.notif-toggle-read-btn');
            if (toggleBtn) {
              toggleBtn.className = 'notif-toggle-read-btn w-6 h-6 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center text-[10px] transition cursor-pointer';
              toggleBtn.title = 'Mark as unread';
              toggleBtn.innerHTML = '<i class="fa-regular fa-envelope"></i>';
            }
          });

          updateCounts();
          if (window.NotificationSystem) {
            window.NotificationSystem.updateBadge(0);
          }
          showToast('All notifications marked as read', 'fa-check-double');
        }
      } catch (err) {
        console.error(err);
      }
    });
  }

  // 3. Clear All Read Notifications
  if (clearReadBtn) {
    clearReadBtn.addEventListener('click', async () => {
      const readItems = document.querySelectorAll('.notification-item[data-unread="0"]');
      if (readItems.length === 0) {
        showToast('No read notifications to clear', 'fa-info-circle');
        return;
      }

      try {
        const res = await fetch(`${window.BASE_URL || ''}/api/notifications.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'clear_all_read' })
        });
        const data = await res.json();
        if (data.success) {
          readItems.forEach(item => {
            item.style.transition = 'all 0.25s ease';
            item.style.opacity = '0';
            item.style.transform = 'scale(0.95)';
            setTimeout(() => item.remove(), 250);
          });

          setTimeout(() => {
            updateCounts();
            checkEmptyState();
            showToast('Cleared read notifications', 'fa-trash-can');
          }, 270);
        }
      } catch (err) {
        console.error(err);
      }
    });
  }

  // 4. Filtering Tabs
  filterTabs.forEach(tab => {
    tab.addEventListener('click', (e) => {
      e.preventDefault();
      filterTabs.forEach(t => {
        t.classList.remove('active', 'bg-blue-600', 'text-white');
        t.classList.add('bg-slate-100', 'text-slate-600');
      });
      tab.classList.remove('bg-slate-100', 'text-slate-600');
      tab.classList.add('active', 'bg-blue-600', 'text-white');

      const filter = tab.getAttribute('data-filter');
      const items = document.querySelectorAll('.notification-item');

      items.forEach(item => {
        const isUnread = item.getAttribute('data-unread') === '1';
        const type = item.getAttribute('data-type');

        if (filter === 'all') {
          item.style.display = 'flex';
        } else if (filter === 'unread') {
          item.style.display = isUnread ? 'flex' : 'none';
        } else {
          item.style.display = type === filter ? 'flex' : 'none';
        }
      });
    });
  });

  // 5. Delegation for item clicks, toggle read, and delete
  if (notifsList) {
    notifsList.addEventListener('click', async (e) => {
      // Toggle read button
      const toggleBtn = e.target.closest('.notif-toggle-read-btn');
      if (toggleBtn) {
        e.preventDefault();
        e.stopPropagation();
        const id = toggleBtn.getAttribute('data-id');
        const item = document.getElementById(`notif-item-${id}`);
        if (!item) return;

        const isCurrentlyUnread = item.getAttribute('data-unread') === '1';
        const newAction = isCurrentlyUnread ? 'mark_read' : 'mark_unread';

        try {
          const res = await fetch(`${window.BASE_URL || ''}/api/notifications.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: newAction, notification_id: id })
          });
          const data = await res.json();
          if (data.success) {
            if (isCurrentlyUnread) {
              item.setAttribute('data-unread', '0');
              item.classList.remove('bg-blue-50/40', 'border-blue-200');
              item.classList.add('bg-white');
              toggleBtn.className = 'notif-toggle-read-btn w-6 h-6 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center text-[10px] transition cursor-pointer';
              toggleBtn.title = 'Mark as unread';
              toggleBtn.innerHTML = '<i class="fa-regular fa-envelope"></i>';
            } else {
              item.setAttribute('data-unread', '1');
              item.classList.add('bg-blue-50/40', 'border-blue-200');
              item.classList.remove('bg-white');
              toggleBtn.className = 'notif-toggle-read-btn w-6 h-6 rounded-full bg-blue-100 hover:bg-blue-200 text-blue-600 flex items-center justify-center text-[10px] transition cursor-pointer';
              toggleBtn.title = 'Mark as read';
              toggleBtn.innerHTML = '<i class="fa-solid fa-check"></i>';
            }

            if (window.NotificationSystem && typeof data.unread_count !== 'undefined') {
              window.NotificationSystem.updateBadge(data.unread_count);
            }
            updateCounts();
          }
        } catch (err) {
          console.error(err);
        }
        return;
      }

      // Delete button
      const delBtn = e.target.closest('.notif-delete-btn');
      if (delBtn) {
        e.preventDefault();
        e.stopPropagation();
        const id = delBtn.getAttribute('data-id');
        const item = document.getElementById(`notif-item-${id}`);
        if (!item) return;

        try {
          const res = await fetch(`${window.BASE_URL || ''}/api/notifications.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', notification_id: id })
          });
          const data = await res.json();
          if (data.success) {
            item.style.transition = 'all 0.2s ease';
            item.style.opacity = '0';
            item.style.transform = 'translateX(20px)';
            setTimeout(() => {
              item.remove();
              updateCounts();
              checkEmptyState();
            }, 200);

            if (window.NotificationSystem && typeof data.unread_count !== 'undefined') {
              window.NotificationSystem.updateBadge(data.unread_count);
            }
            showToast('Notification deleted', 'fa-trash-can');
          }
        } catch (err) {
          console.error(err);
        }
        return;
      }

      // Main notif link click: mark read before navigating
      const link = e.target.closest('.notif-link');
      if (link) {
        const id = link.getAttribute('data-id');
        const item = document.getElementById(`notif-item-${id}`);
        if (item && item.getAttribute('data-unread') === '1') {
          // Asynchronously trigger mark_read so DB updates instantly
          fetch(`${window.BASE_URL || ''}/api/notifications.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_read', notification_id: id })
          }).catch(() => {});
        }
      }
    });
  }



  // 7. Render dynamic new card into DOM
  function renderNewNotification(n) {
    if (!notifsList) return;
    notifsList.classList.remove('hidden');
    if (noNotifsCard) noNotifsCard.classList.add('hidden');

    let iconClass = 'fa-bell text-blue-600 bg-blue-50';
    let typeLabel = 'Notice';
    if (n.type === 'payment') {
      iconClass = 'fa-credit-card text-emerald-600 bg-emerald-50';
      typeLabel = 'Payment';
    } else if (n.type === 'update') {
      iconClass = 'fa-award text-amber-600 bg-amber-50';
      typeLabel = 'Child Update';
    } else if (n.type === 'message') {
      iconClass = 'fa-comment-dots text-indigo-600 bg-indigo-50';
      typeLabel = 'Message';
    }

    const iconName = iconClass.split(' ')[0];
    const colorStyles = iconClass.split(' ').slice(1).join(' ');

    const card = document.createElement('div');
    card.id = `notif-item-${n.id}`;
    card.className = 'notification-item app-card p-3 mb-0 flex items-start gap-3 transition relative group bg-blue-50/40 border-blue-200';
    card.setAttribute('data-id', n.id);
    card.setAttribute('data-type', n.type || 'system');
    card.setAttribute('data-unread', '1');
    card.style.animation = 'messageIn 0.3s cubic-bezier(0.16, 1, 0.3, 1)';

    card.innerHTML = `
      <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 text-sm ${colorStyles}">
        <i class="fa-solid ${iconName}"></i>
      </div>

      <a href="${window.BASE_URL || ''}${n.link_url || '/sponsor/dashboard.php'}" 
         class="notif-link flex-1 min-w-0" 
         data-id="${n.id}">
        <div class="flex items-center justify-between gap-1 mb-0.5">
          <div class="flex items-center gap-1.5 truncate">
            <span class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.2 rounded bg-slate-100 text-slate-600">${typeLabel}</span>
            <h4 class="text-xs font-bold text-slate-900 truncate">${escapeHtml(n.title)}</h4>
          </div>
          <span class="text-[10px] text-slate-400 flex-shrink-0 whitespace-nowrap">${n.time_elapsed || 'just now'}</span>
        </div>
        <p class="text-xs text-slate-600 leading-relaxed mb-1">${escapeHtml(n.message)}</p>
        <span class="text-[10px] font-semibold text-blue-600 flex items-center gap-1 hover:underline">
          <span>View details</span>
          <i class="fa-solid fa-arrow-right text-[8px]"></i>
        </span>
      </a>

      <div class="flex flex-col items-center gap-1.5 flex-shrink-0 ml-1">
        <button type="button" class="notif-toggle-read-btn w-6 h-6 rounded-full bg-blue-100 hover:bg-blue-200 text-blue-600 flex items-center justify-center text-[10px] transition cursor-pointer" title="Mark as read" data-id="${n.id}">
          <i class="fa-solid fa-check"></i>
        </button>
        <button type="button" class="notif-delete-btn w-6 h-6 rounded-full hover:bg-rose-50 text-slate-300 hover:text-rose-600 flex items-center justify-center text-[10px] transition cursor-pointer" title="Delete notification" data-id="${n.id}">
          <i class="fa-regular fa-trash-can"></i>
        </button>
      </div>
    `;

    notifsList.insertBefore(card, notifsList.firstChild);
  }

  // 8. Listen to global background notification arrivals
  window.addEventListener('app:new_notification', (e) => {
    if (e.detail) {
      renderNewNotification(e.detail);
      updateCounts();
      checkEmptyState();
    }
  });

  // Helper: Update counts across UI
  function updateCounts() {
    const total = document.querySelectorAll('.notification-item').length;
    const unread = document.querySelectorAll('.notification-item[data-unread="1"]').length;

    const totalEl = document.getElementById('totalNotifCount');
    if (totalEl) totalEl.textContent = total;

    const chipAll = document.getElementById('chipCountAll');
    if (chipAll) chipAll.textContent = total;

    const chipUnread = document.getElementById('chipCountUnread');
    if (chipUnread) chipUnread.textContent = unread;
  }

  // Helper: Check if empty
  function checkEmptyState() {
    const count = document.querySelectorAll('.notification-item').length;
    if (count === 0) {
      if (notifsList) notifsList.classList.add('hidden');
      if (noNotifsCard) noNotifsCard.classList.remove('hidden');
    } else {
      if (notifsList) notifsList.classList.remove('hidden');
      if (noNotifsCard) noNotifsCard.classList.add('hidden');
    }
  }

  function escapeHtml(str) {
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }
});
