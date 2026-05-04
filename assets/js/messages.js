'use strict';

const MSGS_API = './api/messages.php';

function escHtml(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function catSlug(cat) {
  return String(cat).toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
}

// ── State ──────────────────────────────────────────────────────────────────

let currentStatus  = 'all';
let currentSearch  = '';
let currentPage    = 1;
const perPage      = 20;
let totalMessages  = 0;
let currentMsg     = null;
let pendingDelId   = null;
let searchTimer    = null;

// ── DOM refs ───────────────────────────────────────────────────────────────

const msgTableBody   = document.getElementById('msgTableBody');
const msgLoadingRow  = document.getElementById('msgLoadingRow');
const msgPageCount   = document.getElementById('msgPageCount');
const msgFilterTabs  = document.getElementById('msgFilterTabs');
const msgSearch      = document.getElementById('msgSearch');
const msgPagination  = document.getElementById('msgPagination');

const tabCountAll      = document.getElementById('tabCountAll');
const tabCountUnread   = document.getElementById('tabCountUnread');
const tabCountRead     = document.getElementById('tabCountRead');
const tabCountArchived = document.getElementById('tabCountArchived');

const viewOverlay    = document.getElementById('msgViewOverlay');
const closeMsgBtn    = document.getElementById('closeMsgModal');
const msgModalTitle  = document.getElementById('msgModalTitle');
const msgModalBody   = document.getElementById('msgModalBody');
const msgArchiveBtn  = document.getElementById('msgArchiveBtn');
const msgReplyBtn    = document.getElementById('msgReplyBtn');
const msgDeleteBtn   = document.getElementById('msgDeleteBtn');

const deleteOverlay  = document.getElementById('msgDeleteOverlay');
const closeDelBtn    = document.getElementById('closeDeleteModal');
const cancelDelBtn   = document.getElementById('cancelDeleteMsg');
const confirmDelBtn  = document.getElementById('confirmDeleteMsg');

// ── Toast ──────────────────────────────────────────────────────────────────

let toastTimer = null;

function showToast(msg, type) {
  let toast = document.getElementById('msgToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'msgToast';
    document.body.appendChild(toast);
  }
  toast.textContent = msg;
  toast.className = 'msg-toast msg-toast--' + (type || 'success');
  clearTimeout(toastTimer);
  requestAnimationFrame(() => {
    toast.classList.add('msg-toast--visible');
    toastTimer = setTimeout(() => toast.classList.remove('msg-toast--visible'), 3200);
  });
}

// ── Load data ──────────────────────────────────────────────────────────────

async function loadMessages() {
  showLoadingRow();

  const params = new URLSearchParams({
    status:   currentStatus,
    search:   currentSearch,
    page:     currentPage,
    per_page: perPage,
  });

  try {
    const res  = await fetch(`${MSGS_API}?${params}`);
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Load failed');

    totalMessages = data.total;
    renderRows(data.data);
    updateCounts(data.counts);
    renderPagination(data.total, data.page, data.perPage);
  } catch (err) {
    showErrorRow('Could not load messages: ' + err.message);
  }
}

function showLoadingRow() {
  clearRows();
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td colspan="6" class="msg-table-state">
      <div class="msg-table-state-inner">
        <svg class="msg-loading-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
        </svg>
        <span>Loading messages…</span>
      </div>
    </td>`;
  msgTableBody.appendChild(tr);
}

function showErrorRow(msg) {
  clearRows();
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td colspan="6" class="msg-table-state">
      <div class="msg-table-state-inner">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <span>${escHtml(msg)}</span>
      </div>
    </td>`;
  msgTableBody.appendChild(tr);
}

function showEmptyRow() {
  clearRows();
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td colspan="6" class="msg-table-state">
      <div class="msg-table-state-inner">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        <span>No messages found</span>
      </div>
    </td>`;
  msgTableBody.appendChild(tr);
}

function clearRows() {
  msgTableBody.innerHTML = '';
}

// ── Render rows ────────────────────────────────────────────────────────────

function renderRows(rows) {
  clearRows();

  if (!rows || rows.length === 0) {
    showEmptyRow();
    updatePageCount(0);
    return;
  }

  updatePageCount(totalMessages);

  rows.forEach(msg => {
    const tr = document.createElement('tr');
    tr.dataset.id = msg.id;
    if (msg.status === 'unread') tr.classList.add('msg-row--unread');

    const slug = catSlug(msg.category);

    tr.innerHTML = `
      <td><span class="msg-status-pill msg-status-pill--${escHtml(msg.status)}">${escHtml(msg.status)}</span></td>
      <td class="msg-cell-sender" title="${escHtml(msg.name)}">${escHtml(msg.name)}</td>
      <td class="msg-cell-email" title="${escHtml(msg.email)}">${escHtml(msg.email)}</td>
      <td><span class="msg-cat-badge msg-cat-badge--${escHtml(slug)}" title="${escHtml(msg.category)}">${escHtml(msg.category)}</span></td>
      <td class="msg-cell-subject" title="${escHtml(msg.subject)}">${escHtml(msg.subject)}</td>
      <td title="${escHtml(msg.date)}">${escHtml(msg.date)}</td>`;

    tr.addEventListener('click', () => openViewModal(msg));
    msgTableBody.appendChild(tr);
  });
}

function updatePageCount(total) {
  msgPageCount.textContent = total + ' message' + (total !== 1 ? 's' : '');
}

function updateCounts(counts) {
  const total    = counts.total    ?? 0;
  const unread   = counts.unread   ?? 0;
  const read     = counts.read     ?? 0;
  const archived = counts.archived ?? 0;

  tabCountAll.textContent      = total;
  tabCountUnread.textContent   = unread;
  tabCountRead.textContent     = read;
  tabCountArchived.textContent = archived;
}

// ── Pagination ─────────────────────────────────────────────────────────────

function renderPagination(total, page, per) {
  msgPagination.innerHTML = '';
  const totalPages = Math.ceil(total / per);
  if (totalPages <= 1) return;

  const SVG_PREV = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"></polyline></svg>`;
  const SVG_NEXT = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"></polyline></svg>`;

  const prevBtn = document.createElement('button');
  prevBtn.type      = 'button';
  prevBtn.className = 'msg-page-btn';
  prevBtn.innerHTML = SVG_PREV;
  prevBtn.setAttribute('aria-label', 'Previous page');
  prevBtn.disabled  = page <= 1;
  prevBtn.addEventListener('click', () => { currentPage--; loadMessages(); });
  msgPagination.appendChild(prevBtn);

  const maxVisible = 5;
  let startPage = Math.max(1, page - Math.floor(maxVisible / 2));
  let endPage   = Math.min(totalPages, startPage + maxVisible - 1);
  if (endPage - startPage < maxVisible - 1) {
    startPage = Math.max(1, endPage - maxVisible + 1);
  }

  for (let p = startPage; p <= endPage; p++) {
    const btn = document.createElement('button');
    btn.type      = 'button';
    btn.className = 'msg-page-btn' + (p === page ? ' msg-page-btn--active' : '');
    btn.textContent = p;
    const pageNum = p;
    btn.addEventListener('click', () => { currentPage = pageNum; loadMessages(); });
    msgPagination.appendChild(btn);
  }

  const nextBtn = document.createElement('button');
  nextBtn.type      = 'button';
  nextBtn.className = 'msg-page-btn';
  nextBtn.innerHTML = SVG_NEXT;
  nextBtn.setAttribute('aria-label', 'Next page');
  nextBtn.disabled  = page >= totalPages;
  nextBtn.addEventListener('click', () => { currentPage++; loadMessages(); });
  msgPagination.appendChild(nextBtn);
}

// ── View Modal ─────────────────────────────────────────────────────────────

async function openViewModal(msg) {
  currentMsg = msg;

  msgModalTitle.textContent = 'Message';

  const slug = catSlug(msg.category);

  const archiveBtnText = msg.status === 'archived' ? 'Unarchive' : 'Archive';
  const archiveBtnIcon = msg.status === 'archived'
    ? `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="21 8 21 21 3 21 3 8"></polyline><rect x="1" y="3" width="22" height="5"></rect><line x1="10" y1="12" x2="14" y2="12"></line></svg>`
    : `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="21 8 21 21 3 21 3 8"></polyline><rect x="1" y="3" width="22" height="5"></rect><line x1="10" y1="12" x2="14" y2="12"></line></svg>`;

  msgArchiveBtn.innerHTML = archiveBtnIcon + archiveBtnText;

  const mailtoSubject = encodeURIComponent('Re: ' + msg.subject);
  const mailtoHref    = `mailto:${encodeURIComponent(msg.email)}?subject=${mailtoSubject}`;
  msgReplyBtn.href    = mailtoHref;

  msgModalBody.innerHTML = `
    <div class="msg-view-meta">
      <span class="msg-view-label">From</span>
      <span class="msg-view-value">${escHtml(msg.name)}</span>

      <span class="msg-view-label">Email</span>
      <a href="${mailtoHref}" class="msg-view-email-link" title="Open mail client to reply">${escHtml(msg.email)}</a>

      <span class="msg-view-label">Category</span>
      <span class="msg-cat-badge msg-cat-badge--${escHtml(slug)}">${escHtml(msg.category)}</span>

      <span class="msg-view-label">Date</span>
      <span class="msg-view-value">${escHtml(msg.date)}</span>

      <span class="msg-view-label">Status</span>
      <span class="msg-status-pill msg-status-pill--${escHtml(msg.status)} js-modal-status-pill">${escHtml(msg.status)}</span>
    </div>

    <div class="msg-view-divider"></div>

    <div class="msg-view-subject">${escHtml(msg.subject)}</div>
    <div class="msg-view-message">${escHtml(msg.message)}</div>`;

  viewOverlay.classList.remove('msg-modal-overlay--hidden');

  if (msg.status === 'unread') {
    await markStatus(msg.id, 'read', false);
    currentMsg.status = 'read';
    updateRowStatus(msg.id, 'read');
    const pill = msgModalBody.querySelector('.js-modal-status-pill');
    if (pill) {
      pill.className = 'msg-status-pill msg-status-pill--read js-modal-status-pill';
      pill.textContent = 'read';
    }
    msgArchiveBtn.innerHTML = archiveBtnIcon + 'Archive';
  }
}

function closeViewModal() {
  viewOverlay.classList.add('msg-modal-overlay--closing');
  viewOverlay.addEventListener('animationend', () => {
    viewOverlay.classList.remove('msg-modal-overlay--closing');
    viewOverlay.classList.add('msg-modal-overlay--hidden');
  }, { once: true });
}

function updateRowStatus(id, status) {
  const tr = msgTableBody.querySelector(`tr[data-id="${id}"]`);
  if (!tr) return;

  tr.classList.toggle('msg-row--unread', status === 'unread');

  const pill = tr.querySelector('.msg-status-pill');
  if (pill) {
    pill.className   = `msg-status-pill msg-status-pill--${status}`;
    pill.textContent = status;
  }
}

// ── Status update ──────────────────────────────────────────────────────────

async function markStatus(id, status, showFeedback) {
  try {
    const res  = await fetch(MSGS_API, {
      method:  'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ id, status }),
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Update failed');
    if (showFeedback) showToast('Message ' + status);
    return true;
  } catch (err) {
    if (showFeedback) showToast(err.message, 'error');
    return false;
  }
}

// ── Archive button in modal ────────────────────────────────────────────────

msgArchiveBtn.addEventListener('click', async () => {
  if (!currentMsg) return;

  const newStatus = currentMsg.status === 'archived' ? 'read' : 'archived';
  const ok = await markStatus(currentMsg.id, newStatus, true);
  if (!ok) return;

  currentMsg.status = newStatus;
  updateRowStatus(currentMsg.id, newStatus);

  const pill = msgModalBody.querySelector('.js-modal-status-pill');
  if (pill) {
    pill.className   = `msg-status-pill msg-status-pill--${newStatus} js-modal-status-pill`;
    pill.textContent = newStatus;
  }

  const label = newStatus === 'archived' ? 'Unarchive' : 'Archive';
  const SVG_ARCH = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="21 8 21 21 3 21 3 8"></polyline><rect x="1" y="3" width="22" height="5"></rect><line x1="10" y1="12" x2="14" y2="12"></line></svg>`;
  msgArchiveBtn.innerHTML = SVG_ARCH + label;

  reloadCounts();
});

async function reloadCounts() {
  try {
    const res  = await fetch(`${MSGS_API}?status=all&per_page=1&page=1`);
    const data = await res.json();
    if (data.success) updateCounts(data.counts);
  } catch (_) {}
}

// ── Delete flow ────────────────────────────────────────────────────────────

msgDeleteBtn.addEventListener('click', () => {
  if (!currentMsg) return;
  pendingDelId = currentMsg.id;
  closeViewModal();
  viewOverlay.addEventListener('animationend', () => {
    deleteOverlay.classList.remove('msg-modal-overlay--hidden');
  }, { once: true });
});

function closeDeleteModal() {
  deleteOverlay.classList.add('msg-modal-overlay--closing');
  deleteOverlay.addEventListener('animationend', () => {
    deleteOverlay.classList.remove('msg-modal-overlay--closing');
    deleteOverlay.classList.add('msg-modal-overlay--hidden');
  }, { once: true });
  pendingDelId = null;
}

confirmDelBtn.addEventListener('click', async () => {
  if (!pendingDelId) return;
  confirmDelBtn.disabled = true;

  try {
    const res  = await fetch(`${MSGS_API}?id=${pendingDelId}`, { method: 'DELETE' });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Delete failed');

    showToast('Message removed');
    closeDeleteModal();
    currentPage = 1;
    await loadMessages();
  } catch (err) {
    showToast(err.message, 'error');
  } finally {
    confirmDelBtn.disabled = false;
  }
});

// ── Filter tabs ────────────────────────────────────────────────────────────

msgFilterTabs.addEventListener('click', e => {
  const btn = e.target.closest('.msg-filter-tab');
  if (!btn) return;

  msgFilterTabs.querySelectorAll('.msg-filter-tab').forEach(t => t.classList.remove('msg-filter-tab--active'));
  btn.classList.add('msg-filter-tab--active');

  currentStatus = btn.dataset.status;
  currentPage   = 1;
  loadMessages();
});

// ── Search ─────────────────────────────────────────────────────────────────

msgSearch.addEventListener('input', () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    currentSearch = msgSearch.value.trim();
    currentPage   = 1;
    loadMessages();
  }, 350);
});

// ── Modal close listeners ──────────────────────────────────────────────────

closeMsgBtn.addEventListener('click', closeViewModal);
viewOverlay.addEventListener('click', e => { if (e.target === viewOverlay) closeViewModal(); });

closeDelBtn.addEventListener('click', closeDeleteModal);
cancelDelBtn.addEventListener('click', closeDeleteModal);
deleteOverlay.addEventListener('click', e => { if (e.target === deleteOverlay) closeDeleteModal(); });

document.addEventListener('keydown', e => {
  if (e.key !== 'Escape') return;
  if (!deleteOverlay.classList.contains('msg-modal-overlay--hidden')) { closeDeleteModal(); return; }
  if (!viewOverlay.classList.contains('msg-modal-overlay--hidden'))   { closeViewModal(); }
});

// ── Init ───────────────────────────────────────────────────────────────────

loadMessages();
