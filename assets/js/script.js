/* ============================================================
   MCU E-Library — Main JavaScript
   ============================================================ */

'use strict';

/* ── Dark Mode ─────────────────────────────────────────────── */
const themeToggle = document.getElementById('themeToggle');
const html = document.documentElement;

function applyTheme(theme) {
  html.setAttribute('data-theme', theme);
  localStorage.setItem('theme', theme);
  if (themeToggle) {
    themeToggle.innerHTML = theme === 'dark'
      ? '<i class="fas fa-sun"></i>'
      : '<i class="fas fa-moon"></i>';
  }
}
applyTheme(localStorage.getItem('theme') || 'light');
if (themeToggle) themeToggle.addEventListener('click', () => {
  applyTheme(html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
});

/* ── Mobile Nav Toggle ─────────────────────────────────────── */
const navToggle  = document.getElementById('navToggle');
const navLinks   = document.getElementById('navLinks');
if (navToggle && navLinks) {
  navToggle.addEventListener('click', () => navLinks.classList.toggle('open'));
  document.addEventListener('click', e => {
    if (!navToggle.contains(e.target) && !navLinks.contains(e.target))
      navLinks.classList.remove('open');
  });
}

/* ── Navbar Scroll Shadow ─────────────────────────────────── */
const navbar = document.getElementById('navbar');
if (navbar) {
  window.addEventListener('scroll', () => {
    navbar.style.boxShadow = window.scrollY > 10
      ? '0 4px 24px rgba(0,0,0,.35)'
      : '0 2px 20px rgba(0,0,0,.3)';
  }, { passive: true });
}

/* ── Auto-dismiss Flash Messages ──────────────────────────── */
const flashMsg = document.getElementById('flashMsg');
if (flashMsg) setTimeout(() => flashMsg.remove(), 5000);

/* ── Password Visibility Toggle ────────────────────────────── */
document.querySelectorAll('.pw-toggle').forEach(btn => {
  btn.addEventListener('click', () => {
    const input = btn.previousElementSibling;
    const isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';
    btn.querySelector('i').className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
  });
});

/* ── Confirm Dialogs ────────────────────────────────────────── */
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', e => {
    if (!confirm(el.dataset.confirm)) e.preventDefault();
  });
});

/* ── Search Input Debounce ──────────────────────────────────── */
function debounce(fn, ms = 300) {
  let timer;
  return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), ms); };
}

const liveSearch = document.getElementById('liveSearch');
if (liveSearch) {
  liveSearch.addEventListener('input', debounce(e => {
    const q = e.target.value.trim();
    if (q.length > 1) {
      const form = liveSearch.closest('form');
      if (form) form.submit();
    }
  }, 500));
}

/* ── Book Filter ────────────────────────────────────────────── */
const categoryFilter = document.getElementById('categoryFilter');
if (categoryFilter) {
  categoryFilter.addEventListener('change', () => {
    categoryFilter.closest('form').submit();
  });
}

/* ── Animated Counter (Stats) ───────────────────────────────── */
function animateCount(el) {
  const target = parseInt(el.dataset.target || el.textContent, 10);
  if (isNaN(target)) return;
  let current = 0;
  const step = Math.ceil(target / 40);
  const timer = setInterval(() => {
    current = Math.min(current + step, target);
    el.textContent = current.toLocaleString();
    if (current >= target) clearInterval(timer);
  }, 30);
}
document.querySelectorAll('.count-up').forEach(el => {
  new IntersectionObserver(entries => {
    if (entries[0].isIntersecting) { animateCount(el); entries[0].target._obs?.disconnect(); }
  }, { threshold: 0.5 }).observe(el);
});

/* ── Availability Badge Color ───────────────────────────────── */
document.querySelectorAll('.avail-qty').forEach(el => {
  const qty = parseInt(el.dataset.qty, 10);
  const badge = el.querySelector('.book-availability');
  if (badge) {
    if (qty <= 0) {
      badge.textContent = '✕ Out of Stock';
      badge.classList.add('avail-no');
    } else {
      badge.textContent = '✓ Available';
      badge.classList.add('avail-yes');
    }
  }
});

/* ── Chatbot UI ─────────────────────────────────────────────── */
const chatForm    = document.getElementById('chatForm');
const chatInput   = document.getElementById('chatInput');
const chatBody    = document.getElementById('chatMessages');

if (chatForm && chatInput && chatBody) {

  // Suggestion pills
  document.querySelectorAll('.suggestion-pill').forEach(pill => {
    pill.addEventListener('click', () => {
      chatInput.value = pill.textContent;
      chatInput.focus();
      chatForm.dispatchEvent(new Event('submit'));
    });
  });

  chatForm.addEventListener('submit', async e => {
    e.preventDefault();
    const msg = chatInput.value.trim();
    if (!msg) return;

    appendMessage('user', msg);
    chatInput.value = '';
    chatInput.disabled = true;

    const typingId = appendTyping();

    try {
      const res = await fetch('/chatbot.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'message=' + encodeURIComponent(msg) + '&ajax=1'
      });
      const data = await res.json();
      removeTyping(typingId);
      appendMessage('bot', data.reply || 'Sorry, I could not process that.');
    } catch {
      removeTyping(typingId);
      appendMessage('bot', 'Connection error. Please try again.');
    }

    chatInput.disabled = false;
    chatInput.focus();
  });

  function appendMessage(role, text) {
    const div = document.createElement('div');
    div.className = 'message ' + role;
    div.innerHTML = `
      <div class="msg-avatar"><i class="fas fa-${role === 'bot' ? 'robot' : 'user'}"></i></div>
      <div class="msg-bubble">${escapeHtml(text)}</div>`;
    chatBody.appendChild(div);
    chatBody.scrollTop = chatBody.scrollHeight;
  }

  function appendTyping() {
    const id = 'typing-' + Date.now();
    const div = document.createElement('div');
    div.className = 'message bot'; div.id = id;
    div.innerHTML = `<div class="msg-avatar"><i class="fas fa-robot"></i></div>
      <div class="msg-bubble"><div class="typing-indicator"><span></span><span></span><span></span></div></div>`;
    chatBody.appendChild(div);
    chatBody.scrollTop = chatBody.scrollHeight;
    return id;
  }

  function removeTyping(id) {
    document.getElementById(id)?.remove();
  }
}

/* ── Recommendation Fetch ──────────────────────────────────── */
const recommendBtn = document.getElementById('getRecommendations');
if (recommendBtn) {
  recommendBtn.addEventListener('click', async () => {
    const bookId = recommendBtn.dataset.bookId;
    const container = document.getElementById('recommendationsContainer');
    container.innerHTML = '<div class="spinner"></div>';

    try {
      const res = await fetch('/recommend.php?book_id=' + bookId + '&ajax=1');
      const data = await res.json();
      container.innerHTML = data.html || '<p class="text-muted">No recommendations found.</p>';
    } catch {
      container.innerHTML = '<p class="text-danger">Failed to load recommendations.</p>';
    }
  });
}

/* ── AI Summary Fetch ───────────────────────────────────────── */
const summaryBtn = document.getElementById('getSummary');
if (summaryBtn) {
  summaryBtn.addEventListener('click', async () => {
    const bookId = summaryBtn.dataset.bookId;
    const container = document.getElementById('summaryContainer');
    summaryBtn.disabled = true;
    summaryBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating…';
    container.innerHTML = '<div class="spinner"></div>';

    try {
      const res = await fetch('/summary.php?book_id=' + bookId + '&ajax=1');
      const data = await res.json();
      container.innerHTML = `<div class="ai-summary-box">${data.summary || 'No summary available.'}</div>`;
    } catch {
      container.innerHTML = '<p class="text-danger">Failed to generate summary.</p>';
    }
    summaryBtn.disabled = false;
    summaryBtn.innerHTML = '<i class="fas fa-magic"></i> AI Summary';
  });
}

/* ── Fine Preview ───────────────────────────────────────────── */
document.querySelectorAll('.fine-preview').forEach(el => {
  const dueDate  = new Date(el.dataset.due);
  const today    = new Date();
  today.setHours(0, 0, 0, 0);
  if (today > dueDate) {
    const days = Math.round((today - dueDate) / 86400000);
    el.textContent = `₹${(days * 2).toFixed(2)} (${days} day${days > 1 ? 's' : ''} overdue)`;
    el.classList.add('text-danger');
  } else {
    el.textContent = '₹0.00';
    el.classList.add('text-success');
  }
});

/* ── Image Preview for file inputs ─────────────────────────── */
document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
  input.addEventListener('change', e => {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = ev => {
      const img = document.getElementById(input.dataset.preview);
      if (img) img.src = ev.target.result;
    };
    reader.readAsDataURL(file);
  });
});

/* ── Smooth Scroll for anchor links ────────────────────────── */
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const target = document.querySelector(a.getAttribute('href'));
    if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
  });
});

/* ── Utility ────────────────────────────────────────────────── */
function escapeHtml(str) {
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Auto-refresh notification badge ───────────────────────── */
async function refreshNotifBadge() {
  try {
    const res = await fetch('/dashboard.php?notif_count=1');
    const data = await res.json();
    const badge = document.querySelector('.notif-bell .badge');
    if (data.count > 0) {
      if (badge) badge.textContent = data.count;
      else {
        const bell = document.querySelector('.notif-bell');
        if (bell) {
          const span = document.createElement('span');
          span.className = 'badge';
          span.textContent = data.count;
          bell.appendChild(span);
        }
      }
    } else {
      badge?.remove();
    }
  } catch { /* silent */ }
}
if (document.querySelector('.notif-bell')) {
  setInterval(refreshNotifBadge, 60000);
}
