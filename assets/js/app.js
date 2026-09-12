/**
 * Sponsor Mobile Web App - Core JavaScript
 */

// Toast notification helper
function showToast(message, icon = 'fa-check-circle') {
  let toast = document.getElementById('appToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'appToast';
    toast.className = 'app-toast';
    document.body.appendChild(toast);
  }
  
  toast.innerHTML = `<i class="fa-solid ${icon}"></i> <span>${message}</span>`;
  toast.classList.add('visible');
  
  setTimeout(() => {
    toast.classList.remove('visible');
  }, 3000);
}

// Password visibility toggler
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.input-toggle-pass').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-target');
      const input = document.getElementById(targetId);
      if (input) {
        const isPass = input.type === 'password';
        input.type = isPass ? 'text' : 'password';
        btn.innerHTML = isPass ? '<i class="fa-regular fa-eye-slash"></i>' : '<i class="fa-regular fa-eye"></i>';
      }
    });
  });

  // Favorite toggle listener (works globally on any heart button with data-beneficiary-id)
  document.body.addEventListener('click', async (e) => {
    const favBtn = e.target.closest('.favorite-btn-action');
    if (!favBtn) return;
    e.preventDefault();
    e.stopPropagation();

    const beneficiaryId = favBtn.getAttribute('data-beneficiary-id');
    if (!beneficiaryId) return;

    try {
      favBtn.disabled = true;
      const res = await fetch(`${window.BASE_URL || ''}/api/favorites.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ beneficiary_id: beneficiaryId })
      });
      const data = await res.json();
      
      if (data.success) {
        if (data.is_favorite) {
          favBtn.classList.add('active');
          favBtn.innerHTML = '<i class="fa-solid fa-heart text-rose-500"></i>';
          showToast('Added to your favorites!', 'fa-heart');
        } else {
          favBtn.classList.remove('active');
          favBtn.innerHTML = '<i class="fa-regular fa-heart"></i>';
          showToast('Removed from favorites.', 'fa-circle-check');
        }
      } else {
        showToast(data.message || 'Error updating favorites', 'fa-triangle-exclamation');
      }
    } catch (err) {
      console.error(err);
      showToast('Connection issue. Try again.', 'fa-wifi');
    } finally {
      favBtn.disabled = false;
    }
  });

  // Service Worker Registration
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register(`${window.BASE_URL || ''}/service-worker.js`).catch(() => {});
  }

  // Network offline/online indicator
  window.addEventListener('offline', () => {
    showToast('You are currently offline. Showing cached data.', 'fa-plane');
  });

  window.addEventListener('online', () => {
    showToast('Back online!', 'fa-wifi');
  });

  // Initialize Global Notification Polling & In-App Alerts
  initNotificationEngine();
});

/* ==========================================================
 * REAL-TIME NOTIFICATION & IN-APP ALERT ENGINE
 * ========================================================== */
let lastKnownNotifId = 0;
let notifPollTimer = null;
let audioCtx = null;

function playNotificationChime() {
  try {
    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
    if (!AudioContextClass) return;
    if (!audioCtx) audioCtx = new AudioContextClass();
    if (audioCtx.state === 'suspended') {
      audioCtx.resume().catch(() => {});
    }

    const now = audioCtx.currentTime;
    // Two-tone gentle harmonic chime (C5 -> G5)
    const osc1 = audioCtx.createOscillator();
    const osc2 = audioCtx.createOscillator();
    const gainNode = audioCtx.createGain();

    osc1.type = 'sine';
    osc1.frequency.setValueAtTime(523.25, now); // C5
    osc1.frequency.exponentialRampToValueAtTime(783.99, now + 0.12); // G5

    osc2.type = 'triangle';
    osc2.frequency.setValueAtTime(659.25, now); // E5
    osc2.frequency.exponentialRampToValueAtTime(1046.50, now + 0.12); // C6

    gainNode.gain.setValueAtTime(0.12, now);
    gainNode.gain.exponentialRampToValueAtTime(0.001, now + 0.35);

    osc1.connect(gainNode);
    osc2.connect(gainNode);
    gainNode.connect(audioCtx.destination);

    osc1.start(now);
    osc2.start(now);
    osc1.stop(now + 0.35);
    osc2.stop(now + 0.35);
  } catch (e) {
    // Audio context may be restricted by autoplay policy before user gesture
  }
}

function updateNotificationBadge(count) {
  const notifBtn = document.getElementById('headerNotifBtn') || document.querySelector('.header-notif-btn');
  if (!notifBtn) return;

  let badge = notifBtn.querySelector('.notification-badge');
  const num = parseInt(count, 10) || 0;

  if (num > 0) {
    const text = num > 9 ? '9+' : num;
    if (!badge) {
      badge = document.createElement('span');
      badge.className = 'notification-badge animate-pulse-badge';
      notifBtn.appendChild(badge);
    } else {
      badge.classList.remove('animate-pulse-badge');
      void badge.offsetWidth; // Trigger reflow for animation restart
      badge.classList.add('animate-pulse-badge');
    }
    badge.textContent = text;
  } else if (badge) {
    badge.remove();
  }
}

function showInAppNotification(notif) {
  let banner = document.getElementById('inAppNotifBanner');
  if (!banner) {
    banner = document.createElement('div');
    banner.id = 'inAppNotifBanner';
    banner.className = 'in-app-notif-banner';
    document.body.appendChild(banner);
  }

  let iconClass = 'fa-bell text-blue-600 bg-blue-50';
  if (notif.type === 'payment') iconClass = 'fa-credit-card text-emerald-600 bg-emerald-50';
  else if (notif.type === 'message') iconClass = 'fa-comment-dots text-indigo-600 bg-indigo-50';
  else if (notif.type === 'update') iconClass = 'fa-award text-amber-600 bg-amber-50';

  const iconName = iconClass.split(' ')[0];
  const colorClasses = iconClass.split(' ').slice(1).join(' ');

  banner.innerHTML = `
    <div class="in-app-notif-icon ${colorClasses}">
      <i class="fa-solid ${iconName}"></i>
    </div>
    <div class="in-app-notif-body" id="inAppNotifBody">
      <div class="in-app-notif-title">${escapeHtml(notif.title || 'New Notification')}</div>
      <div class="in-app-notif-text">${escapeHtml(notif.message || '')}</div>
    </div>
    <button type="button" class="in-app-notif-close" id="inAppNotifClose" aria-label="Dismiss">
      <i class="fa-solid fa-xmark"></i>
    </button>
  `;

  // Animate slide down
  requestAnimationFrame(() => {
    banner.classList.add('show');
  });

  // Tap handler to navigate & mark read
  const bodyEl = banner.querySelector('#inAppNotifBody');
  if (bodyEl) {
    bodyEl.onclick = async () => {
      banner.classList.remove('show');
      try {
        await fetch(`${window.BASE_URL || ''}/api/notifications.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'mark_read', notification_id: notif.id })
        });
      } catch (err) {}
      const target = notif.link_url || '/sponsor/notifications.php';
      window.location.href = `${window.BASE_URL || ''}${target}`;
    };
  }

  // Close button
  const closeBtn = banner.querySelector('#inAppNotifClose');
  if (closeBtn) {
    closeBtn.onclick = (e) => {
      e.stopPropagation();
      banner.classList.remove('show');
    };
  }

  // Play sound & haptics
  playNotificationChime();
  if (navigator.vibrate) {
    try { navigator.vibrate([80, 50, 80]); } catch (e) {}
  }

  // Native Web Notification API if granted
  if (window.Notification && Notification.permission === 'granted') {
    try {
      new Notification(notif.title, {
        body: notif.message,
        icon: `${window.BASE_URL || ''}/assets/icons/icon-192.png`
      });
    } catch (e) {}
  }

  // Auto hide after 6 seconds
  clearTimeout(banner._dismissTimer);
  banner._dismissTimer = setTimeout(() => {
    banner.classList.remove('show');
  }, 6000);
}

function escapeHtml(str) {
  return String(str || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

async function checkNotifications() {
  if (document.hidden) return; // Don't spam when backgrounded
  try {
    const res = await fetch(`${window.BASE_URL || ''}/api/notifications.php?action=poll&since_id=${lastKnownNotifId}`);
    if (!res.ok) return;
    const data = await res.json();
    if (!data.success) return;

    if (data.latest_id) {
      // First run: just initialize lastKnownNotifId
      if (lastKnownNotifId === 0) {
        lastKnownNotifId = data.latest_id;
      } else if (data.latest_id > lastKnownNotifId) {
        lastKnownNotifId = data.latest_id;
      }
    }

    updateNotificationBadge(data.unread_count);

    // If new notifications received
    if (data.new_notifications && data.new_notifications.length > 0) {
      data.new_notifications.forEach(n => {
        showInAppNotification(n);
        window.dispatchEvent(new CustomEvent('app:new_notification', { detail: n }));
      });
    }
  } catch (err) {
    // Silent fail for polling
  }
}

function initNotificationEngine() {
  // Check immediately on page load
  checkNotifications();
  // Poll every 9 seconds
  if (!notifPollTimer) {
    notifPollTimer = setInterval(checkNotifications, 9000);
  }
}

// Global Notification API
window.NotificationSystem = {
  updateBadge: updateNotificationBadge,
  checkNow: checkNotifications,
  playChime: playNotificationChime,
  showBanner: showInAppNotification,
  requestPermission: async () => {
    if ('Notification' in window) {
      const permission = await Notification.requestPermission();
      return permission === 'granted';
    }
    return false;
  }
};

// Modal helpers
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }
}
