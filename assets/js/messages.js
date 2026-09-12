/**
 * Real-time Mobile Chat & Messaging Engine
 */

document.addEventListener('DOMContentLoaded', () => {
  // ==========================================
  // Web Audio Chimes (No external files needed)
  // ==========================================
  let audioCtx = null;
  function getAudioContext() {
    if (!audioCtx) {
      const AudioContext = window.AudioContext || window.webkitAudioContext;
      if (AudioContext) {
        audioCtx = new AudioContext();
      }
    }
    if (audioCtx && audioCtx.state === 'suspended') {
      audioCtx.resume();
    }
    return audioCtx;
  }

  function playSendSound() {
    try {
      const ctx = getAudioContext();
      if (!ctx) return;
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(440, ctx.currentTime);
      osc.frequency.exponentialRampToValueAtTime(660, ctx.currentTime + 0.12);
      gain.gain.setValueAtTime(0.08, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.14);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start();
      osc.stop(ctx.currentTime + 0.14);
    } catch (e) {}
  }

  function playReceiveSound() {
    try {
      const ctx = getAudioContext();
      if (!ctx) return;
      const now = ctx.currentTime;
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(784, now); // G5
      osc.frequency.setValueAtTime(1046.5, now + 0.08); // C6
      gain.gain.setValueAtTime(0.1, now);
      gain.gain.exponentialRampToValueAtTime(0.001, now + 0.22);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start();
      osc.stop(now + 0.22);
    } catch (e) {}
  }

  // Voice note play/pause handler
  window.playVoiceNote = function(btn) {
    const icon = btn.querySelector('i');
    if (!icon) return;
    const isPlaying = icon.classList.contains('fa-pause');
    if (isPlaying) {
      icon.className = 'fa-solid fa-play text-[10px]';
    } else {
      icon.className = 'fa-solid fa-pause text-[10px]';
      playReceiveSound();
      setTimeout(() => {
        icon.className = 'fa-solid fa-play text-[10px]';
      }, 3000);
    }
  };

  // ==========================================
  // Modal Utilities
  // ==========================================
  function openModal(modalId) {
    const m = document.getElementById(modalId);
    if (m) m.classList.add('active');
  }

  function closeModal(modalId) {
    const m = document.getElementById(modalId);
    if (m) m.classList.remove('active');
  }

  document.querySelectorAll('.closeModalBtn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const modal = e.target.closest('.modal-overlay');
      if (modal) modal.classList.remove('active');
    });
  });

  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        overlay.classList.remove('active');
      }
    });
  });

  // ==========================================
  // 1. Single Thread Chat View Logic
  // ==========================================
  const chatForm = document.getElementById('chatForm');
  const chatInput = document.getElementById('chatInput');
  const messagesBox = document.getElementById('chatMessagesBox');
  const typingIndicator = document.getElementById('typingIndicator');
  const fileAttachmentInput = document.getElementById('fileAttachmentInput');
  const attachFileBtn = document.getElementById('attachFileBtn');
  const attachmentPreviewBar = document.getElementById('attachmentPreviewBar');
  const previewImage = document.getElementById('previewImage');
  const previewDocInfo = document.getElementById('previewDocInfo');
  const previewDocName = document.getElementById('previewDocName');
  const removeAttachmentBtn = document.getElementById('removeAttachmentBtn');
  const voiceNoteBtn = document.getElementById('voiceNoteBtn');
  const voiceModal = document.getElementById('voiceModal');
  const sendVoiceBtn = document.getElementById('sendVoiceBtn');
  const cancelVoiceBtn = document.getElementById('cancelVoiceBtn');
  const voiceTimer = document.getElementById('voiceTimer');

  let selectedFile = null;
  let voiceTimerInterval = null;
  let isSending = false;

  function scrollToBottom(smooth = true) {
    if (messagesBox) {
      messagesBox.scrollTo({
        top: messagesBox.scrollHeight,
        behavior: smooth ? 'smooth' : 'auto'
      });
    }
  }

  if (messagesBox) {
    scrollToBottom(false);
  }

  // Quick Chips selection
  document.querySelectorAll('.chat-quick-chip').forEach(chip => {
    chip.addEventListener('click', () => {
      const text = chip.getAttribute('data-text');
      if (chatInput && text) {
        chatInput.value = text;
        chatInput.focus();
      }
    });
  });

  // Chat Options Dropdown
  const chatOptionsBtn = document.getElementById('chatOptionsBtn');
  const chatOptionsMenu = document.getElementById('chatOptionsMenu');
  if (chatOptionsBtn && chatOptionsMenu) {
    chatOptionsBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      chatOptionsMenu.classList.toggle('hidden');
    });

    document.addEventListener('click', () => {
      chatOptionsMenu.classList.add('hidden');
    });
  }

  // Switch Child modal triggers
  const openChildSelectorBtn = document.getElementById('openChildSelectorBtn');
  const openChildSelectorMenuBtn = document.getElementById('openChildSelectorMenuBtn');
  if (openChildSelectorBtn) {
    openChildSelectorBtn.addEventListener('click', () => openModal('childSelectorModal'));
  }
  if (openChildSelectorMenuBtn) {
    openChildSelectorMenuBtn.addEventListener('click', () => {
      if (chatOptionsMenu) chatOptionsMenu.classList.add('hidden');
      openModal('childSelectorModal');
    });
  }

  // Clear Chat history action
  const clearChatBtn = document.getElementById('clearChatBtn');
  if (clearChatBtn && chatForm) {
    clearChatBtn.addEventListener('click', async () => {
      if (chatOptionsMenu) chatOptionsMenu.classList.add('hidden');
      if (!confirm('Are you sure you want to clear your chat history with this organization?')) {
        return;
      }

      const receiverId = chatForm.getAttribute('data-receiver-id');
      try {
        const res = await fetch(`${window.BASE_URL || ''}/api/messages.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'clear_chat',
            receiver_id: receiverId
          })
        });
        const data = await res.json();
        if (data.success) {
          if (messagesBox) {
            messagesBox.innerHTML = `
              <div class="text-center my-3">
                <span class="bg-slate-100 text-slate-500 text-[10px] px-3 py-1 rounded-full font-medium inline-block">
                  <i class="fa-solid fa-lock text-[9px] text-slate-400 mr-1"></i> Official end-to-end communication with verified NGO coordinator
                </span>
              </div>
            `;
          }
          if (typeof showToast === 'function') {
            showToast('Chat history cleared', 'fa-trash-can');
          }
        }
      } catch (e) {
        console.error(e);
      }
    });
  }

  // File Attachment Handling
  if (attachFileBtn && fileAttachmentInput) {
    attachFileBtn.addEventListener('click', () => {
      fileAttachmentInput.click();
    });

    fileAttachmentInput.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (!file) return;

      selectedFile = file;
      if (attachmentPreviewBar) {
        attachmentPreviewBar.classList.remove('hidden');
        if (file.type.startsWith('image/')) {
          const reader = new FileReader();
          reader.onload = (re) => {
            if (previewImage) {
              previewImage.src = re.target.result;
              previewImage.classList.remove('hidden');
            }
            if (previewDocInfo) previewDocInfo.classList.add('hidden');
          };
          reader.readAsDataURL(file);
        } else {
          if (previewImage) previewImage.classList.add('hidden');
          if (previewDocInfo && previewDocName) {
            previewDocName.textContent = file.name;
            previewDocInfo.classList.remove('hidden');
          }
        }
      }
      chatInput.focus();
    });
  }

  if (removeAttachmentBtn) {
    removeAttachmentBtn.addEventListener('click', () => {
      selectedFile = null;
      if (fileAttachmentInput) fileAttachmentInput.value = '';
      if (attachmentPreviewBar) attachmentPreviewBar.classList.add('hidden');
    });
  }



  if (sendVoiceBtn && chatForm) {
    sendVoiceBtn.addEventListener('click', async () => {
      clearInterval(voiceTimerInterval);
      closeModal('voiceModal');

      const receiverId = chatForm.getAttribute('data-receiver-id');
      const beneficiaryId = chatForm.getAttribute('data-beneficiary-id');

      // Optimistic Voice Note Bubble
      const now = new Date();
      const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      const tempBubble = document.createElement('div');
      tempBubble.className = 'message-bubble outgoing';
      tempBubble.innerHTML = `
        <div class="message-attachment">
          <div class="voice-note-player">
            <button type="button" class="voice-play-btn" onclick="playVoiceNote(this)">
              <i class="fa-solid fa-play text-[10px]"></i>
            </button>
            <div class="voice-waveform">
              <div class="voice-wave-bar h-2"></div>
              <div class="voice-wave-bar h-4"></div>
              <div class="voice-wave-bar h-3"></div>
              <div class="voice-wave-bar h-5"></div>
              <div class="voice-wave-bar h-2"></div>
            </div>
            <span class="text-[10px] font-mono opacity-80">0:08</span>
          </div>
        </div>
        <div>Voice encouragement note</div>
        <span class="message-time">${timeStr} <i class="fa-solid fa-check text-blue-300 text-[9px] ml-1"></i></span>
      `;

      if (messagesBox) {
        messagesBox.appendChild(tempBubble);
        scrollToBottom();
      }

      playSendSound();

      // Show typing indicator
      if (typingIndicator) {
        typingIndicator.classList.remove('hidden');
        scrollToBottom();
      }

      try {
        const res = await fetch(`${window.BASE_URL || ''}/api/messages.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            receiver_id: receiverId,
            beneficiary_id: beneficiaryId,
            message: 'Sent a voice note',
            attachment_type: 'audio',
            attachment_url: 'voice_note'
          })
        });
        const data = await res.json();

        // Reveal reply after realistic delay
        setTimeout(() => {
          if (typingIndicator) typingIndicator.classList.add('hidden');
          if (data.reply && messagesBox) {
            appendIncomingBubble(data.reply);
            playReceiveSound();
            scrollToBottom();
          }
        }, 1200);
      } catch (err) {
        if (typingIndicator) typingIndicator.classList.add('hidden');
      }
    });
  }

  // Regular Chat Form Submit
  if (chatForm && chatInput && messagesBox) {
    chatForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (isSending) return;

      const text = chatInput.value.trim();
      const receiverId = chatForm.getAttribute('data-receiver-id');
      const beneficiaryId = chatForm.getAttribute('data-beneficiary-id');
      const orgId = chatForm.getAttribute('data-org-id');

      if (!text && !selectedFile) return;

      isSending = true;
      playSendSound();

      // Optimistic Bubble
      const now = new Date();
      const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      const tempBubble = document.createElement('div');
      tempBubble.className = 'message-bubble outgoing';

      let attachHtml = '';
      if (selectedFile) {
        if (selectedFile.type.startsWith('image/')) {
          const imgUrl = URL.createObjectURL(selectedFile);
          attachHtml = `<div class="message-attachment"><img src="${imgUrl}" class="rounded-xl object-cover"></div>`;
        } else {
          attachHtml = `<div class="message-attachment"><div class="message-attachment-doc"><i class="fa-solid fa-file text-sm mr-1"></i> ${escapeHtml(selectedFile.name)}</div></div>`;
        }
      }

      tempBubble.innerHTML = `
        ${attachHtml}
        <div>${escapeHtml(text || 'Sent an attachment')}</div>
        <span class="message-time">${timeStr} <i class="fa-solid fa-check text-blue-300 text-[9px] ml-1"></i></span>
      `;

      messagesBox.appendChild(tempBubble);
      scrollToBottom();

      // Reset input bar
      const stagedFile = selectedFile;
      selectedFile = null;
      chatInput.value = '';
      if (fileAttachmentInput) fileAttachmentInput.value = '';
      if (attachmentPreviewBar) attachmentPreviewBar.classList.add('hidden');

      // Show typing indicator
      if (typingIndicator) {
        typingIndicator.classList.remove('hidden');
        scrollToBottom();
      }

      try {
        let res;
        if (stagedFile) {
          const fd = new FormData();
          fd.append('receiver_id', receiverId);
          fd.append('org_id', orgId || '');
          fd.append('beneficiary_id', beneficiaryId || '');
          fd.append('message', text);
          fd.append('attachment', stagedFile);

          res = await fetch(`${window.BASE_URL || ''}/api/messages.php`, {
            method: 'POST',
            body: fd
          });
        } else {
          res = await fetch(`${window.BASE_URL || ''}/api/messages.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              receiver_id: receiverId,
              org_id: orgId,
              beneficiary_id: beneficiaryId,
              message: text
            })
          });
        }

        const data = await res.json();
        isSending = false;

        // Realistic typing response delay
        setTimeout(() => {
          if (typingIndicator) typingIndicator.classList.add('hidden');
          if (data.reply && messagesBox) {
            appendIncomingBubble(data.reply);
            playReceiveSound();
            scrollToBottom();
          }
        }, 1200);

        if (!data.success && typeof showToast === 'function') {
          showToast(data.message || 'Failed to send message', 'fa-triangle-exclamation');
        }
      } catch (err) {
        console.error(err);
        isSending = false;
        if (typingIndicator) typingIndicator.classList.add('hidden');
        if (typeof showToast === 'function') {
          showToast('Network error sending message', 'fa-wifi');
        }
      }
    });

    // Helper to append incoming bubble
    function appendIncomingBubble(reply) {
      const bubble = document.createElement('div');
      bubble.className = 'message-bubble incoming';
      bubble.setAttribute('data-message-id', reply.id);

      let benTag = '';
      if (reply.ben_name) {
        benTag = `<div class="text-[10px] font-bold text-blue-600 mb-0.5 flex items-center gap-1">
          <i class="fa-solid fa-child-reaching text-[10px]"></i> Regarding ${escapeHtml(reply.ben_name)}
        </div>`;
      }

      bubble.innerHTML = `
        ${benTag}
        <div>${escapeHtml(reply.message_text).replace(/\n/g, '<br>')}</div>
        <span class="message-time">${reply.time_formatted || 'Just now'}</span>
      `;

      messagesBox.appendChild(bubble);
    }

    // Auto-poll messages in chat view every 4 seconds
    const orgId = chatForm.getAttribute('data-org-id');
    const receiverId = chatForm.getAttribute('data-receiver-id');
    if (orgId || receiverId) {
      const pollUrl = `${window.BASE_URL || ''}/api/messages.php?` + (orgId ? `org_id=${encodeURIComponent(orgId)}` : `receiver_id=${encodeURIComponent(receiverId)}`);
      
      setInterval(async () => {
        // Skip poll if user is actively sending
        if (isSending) return;

        try {
          const res = await fetch(pollUrl);
          const data = await res.json();
          if (data.success && data.html) {
            const currentBubbleCount = messagesBox.querySelectorAll('.message-bubble:not(.typing-bubble)').length;
            if (data.count > currentBubbleCount) {
              messagesBox.innerHTML = data.html;
              playReceiveSound();
              scrollToBottom();
            }
          }
        } catch (e) {}
      }, 4500);
    }
  }

  // ==========================================
  // 2. Conversation List Screen Logic
  // ==========================================
  const conversationSearchInput = document.getElementById('conversationSearchInput');
  const convFilterChips = document.getElementById('convFilterChips');
  const conversationsContainer = document.getElementById('conversationsContainer');
  const openNewChatModalBtn = document.getElementById('openNewChatModalBtn');
  const openNewChatEmptyBtn = document.getElementById('openNewChatEmptyBtn');

  if (openNewChatModalBtn) {
    openNewChatModalBtn.addEventListener('click', () => openModal('newChatModal'));
  }
  if (openNewChatEmptyBtn) {
    openNewChatEmptyBtn.addEventListener('click', () => openModal('newChatModal'));
  }

  // Search Filter
  if (conversationSearchInput && conversationsContainer) {
    conversationSearchInput.addEventListener('input', (e) => {
      const q = e.target.value.toLowerCase().trim();
      filterConversations();
    });
  }

  // Filter Chips (All, Unread, Sponsored)
  if (convFilterChips) {
    convFilterChips.querySelectorAll('.filter-chip').forEach(chip => {
      chip.addEventListener('click', () => {
        convFilterChips.querySelectorAll('.filter-chip').forEach(c => {
          c.classList.remove('active', 'bg-blue-600', 'text-white');
          c.classList.add('bg-slate-100', 'text-slate-600');
        });
        chip.classList.add('active', 'bg-blue-600', 'text-white');
        chip.classList.remove('bg-slate-100', 'text-slate-600');
        filterConversations();
      });
    });
  }

  function filterConversations() {
    if (!conversationsContainer) return;
    const q = (conversationSearchInput ? conversationSearchInput.value : '').toLowerCase().trim();
    const activeChip = convFilterChips ? convFilterChips.querySelector('.filter-chip.active') : null;
    const filterType = activeChip ? activeChip.getAttribute('data-filter') : 'all';

    const items = conversationsContainer.querySelectorAll('.conversation-item');
    items.forEach(item => {
      const orgName = item.getAttribute('data-org-name') || '';
      const lastMsg = item.getAttribute('data-last-message') || '';
      const isUnread = item.getAttribute('data-unread') === '1';

      let matchesSearch = !q || orgName.includes(q) || lastMsg.includes(q);
      let matchesFilter = true;

      if (filterType === 'unread') {
        matchesFilter = isUnread;
      }

      if (matchesSearch && matchesFilter) {
        item.style.display = 'flex';
      } else {
        item.style.display = 'none';
      }
    });
  }

  function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.innerText = str;
    return div.innerHTML;
  }
});
