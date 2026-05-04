'use strict';

const AWARDS_API = './api/awards.php';

function escHtml(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

// ── State ──────────────────────────────────────────────────────────────────

let allAwards    = [];
let searchQuery  = '';
let pendingDelId = null;

// ── DOM refs ───────────────────────────────────────────────────────────────

const awGrid        = document.getElementById('awGrid');
const awCount       = document.getElementById('awCount');
const awLoading     = document.getElementById('awLoading');
const awEmpty       = document.getElementById('awEmpty');
const awSearch      = document.getElementById('awSearch');

const openAddBtn    = document.getElementById('openAddModal');
const modalOverlay  = document.getElementById('awModalOverlay');
const closeModalBtn = document.getElementById('closeModal');
const cancelModal   = document.getElementById('cancelModal');
const awForm        = document.getElementById('awForm');
const awModalTitle  = document.getElementById('awModalTitle');
const awId          = document.getElementById('awId');
const awTitle       = document.getElementById('awTitle');
const awYear        = document.getElementById('awYear');
const awOrg         = document.getElementById('awOrg');
const awDesc        = document.getElementById('awDesc');
const awImage       = document.getElementById('awImage');
const awFileLabel   = document.getElementById('awFileLabel');
const awThumbPrev   = document.getElementById('awThumbPreview');
const awSubmitBtn   = document.getElementById('awSubmitBtn');

const deleteOverlay = document.getElementById('deleteModalOverlay');
const closeDelBtn   = document.getElementById('closeDeleteModal');
const cancelDelBtn  = document.getElementById('cancelDelete');
const confirmDelBtn = document.getElementById('confirmDelete');
const deleteAwTitle = document.getElementById('deleteAwTitle');

// ── Toast ──────────────────────────────────────────────────────────────────

let toastTimer = null;

function showToast(msg, type) {
  let toast = document.getElementById('awToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'awToast';
    document.body.appendChild(toast);
  }
  toast.textContent = msg;
  toast.className = 'aw-toast aw-toast--' + (type || 'success');
  clearTimeout(toastTimer);
  requestAnimationFrame(() => {
    toast.classList.add('aw-toast--visible');
    toastTimer = setTimeout(() => toast.classList.remove('aw-toast--visible'), 3200);
  });
}

// ── Data ───────────────────────────────────────────────────────────────────

async function loadAwards() {
  awLoading.classList.remove('aw-grid-state--hidden');
  awEmpty.classList.add('aw-grid-state--hidden');
  clearCards();

  try {
    const res  = await fetch(AWARDS_API);
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Load failed');
    allAwards = data.data || [];
    renderAwards();
  } catch (err) {
    showToast('Could not load awards: ' + err.message, 'error');
  } finally {
    awLoading.classList.add('aw-grid-state--hidden');
  }
}

function clearCards() {
  awGrid.querySelectorAll('.aw-card').forEach(c => c.remove());
}

function getFiltered() {
  const q = searchQuery.trim().toLowerCase();
  if (!q) return allAwards;
  return allAwards.filter(a =>
    a.title.toLowerCase().includes(q) ||
    a.organization.toLowerCase().includes(q) ||
    String(a.award_year).includes(q)
  );
}

function renderAwards() {
  clearCards();
  const filtered = getFiltered();
  const total    = allAwards.length;

  awCount.textContent = total + ' award' + (total !== 1 ? 's' : '');

  if (filtered.length === 0) {
    awEmpty.classList.remove('aw-grid-state--hidden');
    return;
  }

  awEmpty.classList.add('aw-grid-state--hidden');
  filtered.forEach(a => awGrid.appendChild(buildCard(a)));
}

// ── Card builder ───────────────────────────────────────────────────────────

const SVG_EDIT   = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>`;
const SVG_TRASH  = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>`;
const SVG_IMG    = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>`;
const SVG_ORG    = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>`;
const SVG_HANDLE = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="8" y1="18" x2="16" y2="18"/></svg>`;

function buildCard(award) {
  const card = document.createElement('div');
  card.className  = 'aw-card';
  card.dataset.id = award.id;
  card.draggable  = true;

  const imgHtml = award.image_url
    ? `<img class="aw-card-image" src="${escHtml(award.image_url)}" alt="${escHtml(award.title)}" loading="lazy" />`
    : `<div class="aw-card-image-placeholder">${SVG_IMG}</div>`;

  card.innerHTML = `
    <div class="aw-drag-handle" title="Drag to reorder">${SVG_HANDLE}</div>
    <div class="aw-card-actions">
      <button type="button" class="aw-card-action-btn js-edit-btn" aria-label="Edit award">${SVG_EDIT}</button>
      <button type="button" class="aw-card-action-btn aw-card-action-btn--delete js-del-btn" aria-label="Delete award">${SVG_TRASH}</button>
    </div>
    ${imgHtml}
    <div class="aw-card-body">
      <span class="aw-card-year">${escHtml(String(award.award_year))}</span>
      <h3 class="aw-card-title">${escHtml(award.title)}</h3>
      <span class="aw-card-org">${SVG_ORG} ${escHtml(award.organization)}</span>
      ${award.description ? `<p class="aw-card-desc">${escHtml(award.description)}</p>` : ''}
    </div>`;

  card.querySelector('.js-edit-btn').addEventListener('click', () => openEditModal(award));
  card.querySelector('.js-del-btn').addEventListener('click', () => openDeleteModal(award.id, award.title));

  attachDragListeners(card);

  return card;
}

// ── Modals ─────────────────────────────────────────────────────────────────

// ── Drag-and-drop reorder ──────────────────────────────────────────────────

let dragSrcCard = null;

function attachDragListeners(card) {
  card.addEventListener('dragstart', e => {
    dragSrcCard = card;
    card.classList.add('aw-card--dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', card.dataset.id);
  });

  card.addEventListener('dragend', () => {
    dragSrcCard = null;
    card.classList.remove('aw-card--dragging');
    awGrid.querySelectorAll('.aw-card').forEach(c => c.classList.remove('aw-card--drag-over'));
  });

  card.addEventListener('dragover', e => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    if (card !== dragSrcCard) {
      awGrid.querySelectorAll('.aw-card').forEach(c => c.classList.remove('aw-card--drag-over'));
      card.classList.add('aw-card--drag-over');
    }
  });

  card.addEventListener('dragleave', () => {
    card.classList.remove('aw-card--drag-over');
  });

  card.addEventListener('drop', e => {
    e.preventDefault();
    if (!dragSrcCard || dragSrcCard === card) return;

    const cards  = [...awGrid.querySelectorAll('.aw-card')];
    const srcIdx = cards.indexOf(dragSrcCard);
    const dstIdx = cards.indexOf(card);

    if (srcIdx < dstIdx) {
      awGrid.insertBefore(dragSrcCard, card.nextSibling);
    } else {
      awGrid.insertBefore(dragSrcCard, card);
    }

    card.classList.remove('aw-card--drag-over');
    saveOrder();
  });
}

async function saveOrder() {
  const cards = [...awGrid.querySelectorAll('.aw-card')];
  const order = cards.map((c, i) => ({ id: Number(c.dataset.id), sort_order: i }));

  try {
    const res  = await fetch(AWARDS_API, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ order }),
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message);

    allAwards.sort((a, b) => {
      const ai = order.findIndex(o => o.id === Number(a.id));
      const bi = order.findIndex(o => o.id === Number(b.id));
      return ai - bi;
    });
  } catch (err) {
    showToast('Could not save order: ' + err.message, 'error');
  }
}

function openAddModal() {
  awModalTitle.textContent  = 'Add Award';
  awForm.reset();
  awId.value               = '';
  awYear.value             = new Date().getFullYear();
  awThumbPrev.innerHTML    = '';
  awFileLabel.textContent  = 'Choose image';
  modalOverlay.classList.remove('aw-modal-overlay--hidden');
  awTitle.focus();
}

function openEditModal(award) {
  awModalTitle.textContent = 'Edit Award';
  awId.value    = award.id;
  awTitle.value = award.title        || '';
  awYear.value  = award.award_year   || new Date().getFullYear();
  awOrg.value   = award.organization || '';
  awDesc.value  = award.description  || '';
  awThumbPrev.innerHTML = award.image_url
    ? `<img src="${escHtml(award.image_url)}" alt="Current image" />`
    : '';
  awFileLabel.textContent = award.image_url ? 'Replace image' : 'Choose image';

  modalOverlay.classList.remove('aw-modal-overlay--hidden');
  awTitle.focus();
}

function closeModal() {
  modalOverlay.classList.add('aw-modal-overlay--closing');
  modalOverlay.addEventListener('animationend', () => {
    modalOverlay.classList.remove('aw-modal-overlay--closing');
    modalOverlay.classList.add('aw-modal-overlay--hidden');
  }, { once: true });
}

function openDeleteModal(id, title) {
  pendingDelId = id;
  deleteAwTitle.textContent = title;
  deleteOverlay.classList.remove('aw-modal-overlay--hidden');
}

function closeDeleteModal() {
  deleteOverlay.classList.add('aw-modal-overlay--closing');
  deleteOverlay.addEventListener('animationend', () => {
    deleteOverlay.classList.remove('aw-modal-overlay--closing');
    deleteOverlay.classList.add('aw-modal-overlay--hidden');
  }, { once: true });
  pendingDelId = null;
}

// ── File preview ───────────────────────────────────────────────────────────

awImage.addEventListener('change', () => {
  const file = awImage.files[0];
  if (!file) return;
  awFileLabel.textContent = file.name;
  const reader = new FileReader();
  reader.onload = e => {
    awThumbPrev.innerHTML = `<img src="${e.target.result}" alt="Preview" />`;
  };
  reader.readAsDataURL(file);
});

// ── Search ─────────────────────────────────────────────────────────────────

awSearch.addEventListener('input', () => {
  searchQuery = awSearch.value;
  renderAwards();
});

// ── Listeners ─────────────────────────────────────────────────────────────

openAddBtn.addEventListener('click', openAddModal);
closeModalBtn.addEventListener('click', closeModal);
cancelModal.addEventListener('click', closeModal);
modalOverlay.addEventListener('click', e => { if (e.target === modalOverlay) closeModal(); });

closeDelBtn.addEventListener('click', closeDeleteModal);
cancelDelBtn.addEventListener('click', closeDeleteModal);
deleteOverlay.addEventListener('click', e => { if (e.target === deleteOverlay) closeDeleteModal(); });

document.addEventListener('keydown', e => {
  if (e.key !== 'Escape') return;
  if (!modalOverlay.classList.contains('aw-modal-overlay--hidden'))  closeModal();
  if (!deleteOverlay.classList.contains('aw-modal-overlay--hidden')) closeDeleteModal();
});

// ── Delete ─────────────────────────────────────────────────────────────────

confirmDelBtn.addEventListener('click', async () => {
  if (!pendingDelId) return;
  confirmDelBtn.disabled = true;

  try {
    const res  = await fetch(`${AWARDS_API}?id=${pendingDelId}`, { method: 'DELETE' });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Delete failed');

    allAwards = allAwards.filter(a => String(a.id) !== String(pendingDelId));
    renderAwards();
    showToast('Award removed successfully');
    closeDeleteModal();
  } catch (err) {
    showToast(err.message, 'error');
  } finally {
    confirmDelBtn.disabled = false;
  }
});

// ── Save ───────────────────────────────────────────────────────────────────

awForm.addEventListener('submit', async e => {
  e.preventDefault();

  if (!awTitle.value.trim()) { awTitle.focus(); showToast('Title is required', 'error'); return; }
  if (!awOrg.value.trim())   { awOrg.focus();   showToast('Organization is required', 'error'); return; }

  const originalLabel = awSubmitBtn.innerHTML;
  awSubmitBtn.disabled   = true;
  awSubmitBtn.textContent = 'Saving…';

  try {
    const res  = await fetch(AWARDS_API, { method: 'POST', body: new FormData(awForm) });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Save failed');

    showToast(data.message || 'Award saved');
    closeModal();
    await loadAwards();
  } catch (err) {
    showToast(err.message, 'error');
  } finally {
    awSubmitBtn.disabled  = false;
    awSubmitBtn.innerHTML = originalLabel;
  }
});

// ── Init ───────────────────────────────────────────────────────────────────

loadAwards();
