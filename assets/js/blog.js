'use strict';

const BLOG_API     = './api/blog.php';
const PROJECTS_API = './api/projects.php';

function escHtml(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function formatDate(str) {
  if (!str) return '';
  const d = new Date(str);
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function slugify(text) {
  return text.toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/[\s-]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

// ── State ──────────────────────────────────────────────────────────────────

let allPosts      = [];
let allProjects   = [];
let currentFilter = 'all';
let searchQuery   = '';
let pendingDelId  = null;
let slugTouched   = false;

// ── DOM refs ───────────────────────────────────────────────────────────────

const blogGrid       = document.getElementById('blogGrid');
const blogCount      = document.getElementById('blogCount');
const blogLoading    = document.getElementById('blogLoading');
const blogEmpty      = document.getElementById('blogEmpty');
const blogSearch     = document.getElementById('blogSearch');

const openAddBtn     = document.getElementById('openAddModal');
const modalOverlay   = document.getElementById('blogModalOverlay');
const closeModalBtn  = document.getElementById('closeModal');
const cancelModalBtn = document.getElementById('cancelModal');
const blogForm       = document.getElementById('blogForm');
const blogModalTitle = document.getElementById('blogModalTitle');
const blogId         = document.getElementById('blogId');
const blogTitle      = document.getElementById('blogTitle');
const blogSlug       = document.getElementById('blogSlug');
const blogCategory   = document.getElementById('blogCategory');
const blogContent    = document.getElementById('blogContent');
const blogStatus      = document.getElementById('blogStatus');
const blogStatusLabel = document.getElementById('blogStatusLabel');
const blogStatusIcon  = document.getElementById('blogStatusIcon');
const blogProject     = document.getElementById('blogProject');
const blogThumbnail   = document.getElementById('blogThumbnail');
const blogFileLabel   = document.getElementById('blogFileLabel');
const blogThumbPrev   = document.getElementById('blogThumbPreview');
const blogSubmitBtn   = document.getElementById('blogSubmitBtn');

const deleteOverlay  = document.getElementById('deleteModalOverlay');
const closeDelBtn    = document.getElementById('closeDeleteModal');
const cancelDelBtn   = document.getElementById('cancelDelete');
const confirmDelBtn  = document.getElementById('confirmDelete');
const deletePostTitle = document.getElementById('deletePostTitle');

// ── Toast ──────────────────────────────────────────────────────────────────

let toastTimer = null;

function showToast(msg, type) {
  let toast = document.getElementById('blogToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'blogToast';
    document.body.appendChild(toast);
  }
  toast.textContent = msg;
  toast.className = 'blog-toast blog-toast--' + (type || 'success');
  clearTimeout(toastTimer);
  requestAnimationFrame(() => {
    toast.classList.add('blog-toast--visible');
    toastTimer = setTimeout(() => toast.classList.remove('blog-toast--visible'), 3200);
  });
}

// ── Data loading ───────────────────────────────────────────────────────────

async function loadPosts() {
  blogLoading.classList.remove('blog-grid-state--hidden');
  blogEmpty.classList.add('blog-grid-state--hidden');
  clearCards();

  try {
    const res  = await fetch(BLOG_API);
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Load failed');
    allPosts = data.data || [];
    renderPosts();
  } catch (err) {
    showToast('Could not load posts: ' + err.message, 'error');
  } finally {
    blogLoading.classList.add('blog-grid-state--hidden');
  }
}

async function loadProjectsDropdown() {
  try {
    const res  = await fetch(PROJECTS_API);
    const data = await res.json();
    if (!data.success) return;
    allProjects = data.data || [];
    rebuildProjectDropdown();
  } catch (_) {}
}

function rebuildProjectDropdown(selectedId) {
  blogProject.innerHTML = '<option value="">— None —</option>';
  allProjects.forEach(p => {
    const opt = document.createElement('option');
    opt.value = p.id;
    opt.textContent = p.title;
    if (String(p.id) === String(selectedId)) opt.selected = true;
    blogProject.appendChild(opt);
  });
}

function clearCards() {
  blogGrid.querySelectorAll('.blog-card').forEach(c => c.remove());
}

function getFiltered() {
  return allPosts.filter(p => {
    const matchStatus = currentFilter === 'all' || p.status === currentFilter;
    const q           = searchQuery.trim().toLowerCase();
    const matchSearch = !q
      || p.title.toLowerCase().includes(q)
      || (p.category || '').toLowerCase().includes(q);
    return matchStatus && matchSearch;
  });
}

function renderPosts() {
  clearCards();
  const filtered = getFiltered();
  const total    = allPosts.length;

  blogCount.textContent = total + ' post' + (total !== 1 ? 's' : '');

  if (filtered.length === 0) {
    blogEmpty.classList.remove('blog-grid-state--hidden');
    return;
  }

  blogEmpty.classList.add('blog-grid-state--hidden');
  filtered.forEach(post => blogGrid.appendChild(buildCard(post)));
}

// ── Card builder ───────────────────────────────────────────────────────────

const SVG_EDIT  = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>`;
const SVG_TRASH = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>`;
const SVG_IMG   = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>`;
const SVG_EYE   = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
const SVG_LINK  = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>`;

const STATUS_ICONS = {
  published: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>`,
  draft:     `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
  archived:  `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>`,
};

function buildCard(post) {
  const card = document.createElement('div');
  card.className = 'blog-card';
  card.dataset.id = post.id;

  const statusIcon  = STATUS_ICONS[post.status] || '';
  const displayDate = formatDate(post.published_at || post.created_at);

  const imgHtml = post.thumbnail_url
    ? `<img class="blog-card-image" src="${escHtml(post.thumbnail_url)}" alt="${escHtml(post.title)}" loading="lazy" />`
    : `<div class="blog-card-image-placeholder">${SVG_IMG}</div>`;

  const linkedHtml = post.project_title
    ? `<span class="blog-card-linked-project">${SVG_LINK} ${escHtml(post.project_title)}</span>`
    : '';

  card.innerHTML = `
    <div class="blog-card-actions">
      <button type="button" class="blog-card-action-btn js-edit-btn" aria-label="Edit post">${SVG_EDIT}</button>
      <button type="button" class="blog-card-action-btn blog-card-action-btn--delete js-del-btn" aria-label="Delete post">${SVG_TRASH}</button>
    </div>
    ${imgHtml}
    <div class="blog-card-body">
      <span class="blog-card-category">${escHtml(post.category || 'General')}</span>
      <h3 class="blog-card-title">${escHtml(post.title)}</h3>
      <span class="blog-card-status blog-card-status--${escHtml(post.status)}">
        ${statusIcon}
        ${escHtml(post.status.charAt(0).toUpperCase() + post.status.slice(1))}
      </span>
      ${linkedHtml}
    </div>
    <div class="blog-card-footer">
      <span class="blog-card-meta">${SVG_EYE} ${Number(post.views || 0).toLocaleString()} views</span>
      <span class="blog-card-date">${escHtml(displayDate)}</span>
    </div>`;

  card.querySelector('.js-edit-btn').addEventListener('click', () => openEditModal(post));
  card.querySelector('.js-del-btn').addEventListener('click', () => openDeleteModal(post.id, post.title));

  return card;
}

// ── Modals ─────────────────────────────────────────────────────────────────

const SVG_AUTO_PUBLISHED = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>`;
const SVG_AUTO_DRAFT     = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;

function updateAutoStatus() {
  const hasContent = blogContent.value.trim().length > 0;
  const status     = hasContent ? 'published' : 'draft';
  blogStatus.value = status;
  if (blogStatusLabel) blogStatusLabel.textContent = hasContent ? 'Published' : 'Draft';
  if (blogStatusIcon)  blogStatusIcon.innerHTML    = hasContent ? SVG_AUTO_PUBLISHED : SVG_AUTO_DRAFT;
  const badge = document.getElementById('blogStatusBadge');
  if (badge) {
    badge.dataset.status = status;
  }
}

function openAddModal() {
  blogModalTitle.textContent = 'New Post';
  blogForm.reset();
  blogId.value              = '';
  slugTouched               = false;
  blogThumbPrev.innerHTML   = '';
  blogFileLabel.textContent = 'Choose cover image';
  updateAutoStatus();
  rebuildProjectDropdown();
  modalOverlay.classList.remove('blog-modal-overlay--hidden');
  blogTitle.focus();
}

function openEditModal(post) {
  blogModalTitle.textContent = 'Edit Post';
  blogId.value        = post.id;
  blogTitle.value     = post.title    || '';
  blogSlug.value      = post.slug     || '';
  blogCategory.value  = post.category || 'General';
  blogContent.value   = post.content  || '';
  slugTouched         = true;

  updateAutoStatus();

  blogThumbPrev.innerHTML = post.thumbnail_url
    ? `<img src="${escHtml(post.thumbnail_url)}" alt="Current cover" />`
    : '';
  blogFileLabel.textContent = post.thumbnail_url ? 'Replace cover image' : 'Choose cover image';

  rebuildProjectDropdown(post.project_id);
  modalOverlay.classList.remove('blog-modal-overlay--hidden');
  blogTitle.focus();
}

function closeModal() {
  modalOverlay.classList.add('blog-modal-overlay--closing');
  modalOverlay.addEventListener('animationend', () => {
    modalOverlay.classList.remove('blog-modal-overlay--closing');
    modalOverlay.classList.add('blog-modal-overlay--hidden');
  }, { once: true });
}

function openDeleteModal(id, title) {
  pendingDelId = id;
  deletePostTitle.textContent = title;
  deleteOverlay.classList.remove('blog-modal-overlay--hidden');
}

function closeDeleteModal() {
  deleteOverlay.classList.add('blog-modal-overlay--closing');
  deleteOverlay.addEventListener('animationend', () => {
    deleteOverlay.classList.remove('blog-modal-overlay--closing');
    deleteOverlay.classList.add('blog-modal-overlay--hidden');
  }, { once: true });
  pendingDelId = null;
}

// ── Auto-slug from title ───────────────────────────────────────────────────

blogTitle.addEventListener('input', () => {
  if (!slugTouched) {
    blogSlug.value = slugify(blogTitle.value);
  }
});

blogSlug.addEventListener('input', () => {
  slugTouched = blogSlug.value.trim() !== '';
});

// ── Auto-status on content change ─────────────────────────────────────────

blogContent.addEventListener('input', updateAutoStatus);

// ── File input preview ─────────────────────────────────────────────────────

blogThumbnail.addEventListener('change', () => {
  const file = blogThumbnail.files[0];
  if (!file) return;
  blogFileLabel.textContent = file.name;
  const reader = new FileReader();
  reader.onload = e => {
    blogThumbPrev.innerHTML = `<img src="${e.target.result}" alt="Preview" />`;
  };
  reader.readAsDataURL(file);
});

// ── Filter tabs ────────────────────────────────────────────────────────────

document.querySelectorAll('.blog-filter-tab').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.blog-filter-tab')
      .forEach(b => b.classList.remove('blog-filter-tab--active'));
    btn.classList.add('blog-filter-tab--active');
    currentFilter = btn.dataset.filter;
    renderPosts();
  });
});

// ── Search ─────────────────────────────────────────────────────────────────

blogSearch.addEventListener('input', () => {
  searchQuery = blogSearch.value;
  renderPosts();
});

// ── Modal listeners ────────────────────────────────────────────────────────

openAddBtn.addEventListener('click', openAddModal);
closeModalBtn.addEventListener('click', closeModal);
cancelModalBtn.addEventListener('click', closeModal);
modalOverlay.addEventListener('click', e => {
  if (e.target === modalOverlay) closeModal();
});

closeDelBtn.addEventListener('click', closeDeleteModal);
cancelDelBtn.addEventListener('click', closeDeleteModal);
deleteOverlay.addEventListener('click', e => {
  if (e.target === deleteOverlay) closeDeleteModal();
});

document.addEventListener('keydown', e => {
  if (e.key !== 'Escape') return;
  if (!modalOverlay.classList.contains('blog-modal-overlay--hidden'))  closeModal();
  if (!deleteOverlay.classList.contains('blog-modal-overlay--hidden')) closeDeleteModal();
});

// ── Delete ─────────────────────────────────────────────────────────────────

confirmDelBtn.addEventListener('click', async () => {
  if (!pendingDelId) return;
  confirmDelBtn.disabled = true;

  try {
    const res  = await fetch(`${BLOG_API}?id=${pendingDelId}`, { method: 'DELETE' });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Delete failed');

    allPosts = allPosts.filter(p => String(p.id) !== String(pendingDelId));
    renderPosts();
    showToast('Post removed successfully');
    closeDeleteModal();
  } catch (err) {
    showToast(err.message, 'error');
  } finally {
    confirmDelBtn.disabled = false;
  }
});

// ── Save (create / update) ─────────────────────────────────────────────────

blogForm.addEventListener('submit', async e => {
  e.preventDefault();

  if (!blogTitle.value.trim()) {
    blogTitle.focus();
    showToast('Post title is required', 'error');
    return;
  }

  const originalLabel = blogSubmitBtn.innerHTML;
  blogSubmitBtn.disabled   = true;
  blogSubmitBtn.textContent = 'Saving…';

  try {
    const res  = await fetch(BLOG_API, { method: 'POST', body: new FormData(blogForm) });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Save failed');

    showToast(data.message || 'Post saved');
    closeModal();
    await loadPosts();
  } catch (err) {
    showToast(err.message, 'error');
  } finally {
    blogSubmitBtn.disabled  = false;
    blogSubmitBtn.innerHTML = originalLabel;
  }
});

// ── Init ───────────────────────────────────────────────────────────────────

loadPosts();
loadProjectsDropdown();
