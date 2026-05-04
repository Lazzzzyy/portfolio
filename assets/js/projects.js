'use strict';

const PROJECTS_API = './api/projects.php';

const KNOWN_TECHS = [
  'html5', 'html', 'css3', 'css', 'javascript', 'typescript', 'tailwindcss',
  'tailwind', 'bootstrap', 'react native', 'reactnative', 'react', 'vuejs',
  'vue', 'angular', 'sass', 'scss', 'jquery', 'next.js', 'nextjs', 'nuxtjs',
  'svelte', 'alpinejs', 'php', 'laravel', 'node.js', 'nodejs', 'node',
  'express', 'python', 'django', 'flask', 'java', 'spring', 'csharp', 'c#',
  'dotnet', '.net', 'ruby', 'rails', 'golang', 'go', 'rust', 'kotlin',
  'mysql', 'postgresql', 'postgres', 'mongodb', 'mongo', 'sqlite', 'redis',
  'firebase', 'supabase', 'swift', 'flutter', 'dart', 'docker', 'kubernetes',
  'nginx', 'linux', 'bash', 'aws', 'azure', 'github', 'git',
];

const CANONICAL_MAP = {
  'html': 'HTML', 'html5': 'HTML',
  'css': 'CSS', 'css3': 'CSS',
  'js': 'JavaScript', 'javascript': 'JavaScript',
  'ts': 'TypeScript', 'typescript': 'TypeScript',
  'nodejs': 'Node.js', 'node': 'Node.js', 'node.js': 'Node.js',
  'tailwind': 'Tailwind CSS', 'tailwindcss': 'Tailwind CSS',
  'vuejs': 'Vue.js', 'vue': 'Vue.js',
  'next.js': 'Next.js', 'nextjs': 'Next.js',
  'nuxtjs': 'Nuxt.js',
  'reactnative': 'React Native', 'react native': 'React Native',
  'csharp': 'C#', 'c#': 'C#',
  'dotnet': '.NET', '.net': '.NET',
  'postgres': 'PostgreSQL', 'postgresql': 'PostgreSQL',
  'mongo': 'MongoDB', 'mongodb': 'MongoDB',
  'golang': 'Go', 'go': 'Go',
  'mysql': 'MySQL', 'php': 'PHP', 'python': 'Python',
  'laravel': 'Laravel', 'react': 'React', 'angular': 'Angular',
  'svelte': 'Svelte', 'bootstrap': 'Bootstrap',
  'sass': 'Sass', 'scss': 'Sass',
  'jquery': 'jQuery', 'django': 'Django', 'flask': 'Flask',
  'java': 'Java', 'spring': 'Spring', 'ruby': 'Ruby', 'rails': 'Rails',
  'rust': 'Rust', 'kotlin': 'Kotlin', 'swift': 'Swift',
  'flutter': 'Flutter', 'dart': 'Dart', 'sqlite': 'SQLite',
  'redis': 'Redis', 'firebase': 'Firebase', 'supabase': 'Supabase',
  'docker': 'Docker', 'kubernetes': 'Kubernetes', 'nginx': 'Nginx',
  'linux': 'Linux', 'bash': 'Bash', 'aws': 'AWS', 'azure': 'Azure',
  'github': 'GitHub', 'git': 'Git', 'alpinejs': 'Alpine.js',
  'express': 'Express',
};

const TECH_ICON_CLASS = {
  'HTML':         'devicon-html5-plain colored',
  'CSS':          'devicon-css3-plain colored',
  'JavaScript':   'devicon-javascript-plain colored',
  'TypeScript':   'devicon-typescript-plain colored',
  'Tailwind CSS': 'devicon-tailwindcss-plain colored',
  'Bootstrap':    'devicon-bootstrap-plain colored',
  'React':        'devicon-react-original colored',
  'Vue.js':       'devicon-vuejs-plain colored',
  'Angular':      'devicon-angularjs-plain colored',
  'Sass':         'devicon-sass-original colored',
  'jQuery':       'devicon-jquery-plain colored',
  'Next.js':      'devicon-nextjs-original colored',
  'Nuxt.js':      'devicon-nuxtjs-plain colored',
  'Svelte':       'devicon-svelte-plain colored',
  'Alpine.js':    'devicon-alpinejs-original colored',
  'PHP':          'devicon-php-plain colored',
  'Laravel':      'devicon-laravel-plain colored',
  'Node.js':      'devicon-nodejs-plain colored',
  'Express':      'devicon-express-original colored',
  'Python':       'devicon-python-plain colored',
  'Django':       'devicon-django-plain colored',
  'Flask':        'devicon-flask-original colored',
  'Java':         'devicon-java-plain colored',
  'Spring':       'devicon-spring-plain colored',
  'C#':           'devicon-csharp-plain colored',
  '.NET':         'devicon-dot-net-plain colored',
  'Ruby':         'devicon-ruby-plain colored',
  'Rails':        'devicon-rails-plain colored',
  'Go':           'devicon-go-plain colored',
  'Rust':         'devicon-rust-plain colored',
  'Kotlin':       'devicon-kotlin-plain colored',
  'MySQL':        'devicon-mysql-plain colored',
  'PostgreSQL':   'devicon-postgresql-plain colored',
  'MongoDB':      'devicon-mongodb-plain colored',
  'SQLite':       'devicon-sqlite-plain colored',
  'Redis':        'devicon-redis-plain colored',
  'Firebase':     'devicon-firebase-plain colored',
  'Supabase':     'devicon-supabase-plain colored',
  'Swift':        'devicon-swift-plain colored',
  'Flutter':      'devicon-flutter-plain colored',
  'Dart':         'devicon-dart-plain colored',
  'React Native': 'devicon-react-original colored',
  'Git':          'devicon-git-plain colored',
  'GitHub':       'devicon-github-original colored',
  'Docker':       'devicon-docker-plain colored',
  'Kubernetes':   'devicon-kubernetes-plain colored',
  'Nginx':        'devicon-nginx-original colored',
  'Linux':        'devicon-linux-plain colored',
  'Bash':         'devicon-bash-plain colored',
  'AWS':          'devicon-amazonwebservices-original colored',
  'Azure':        'devicon-azure-plain colored',
};

function detectTechs(text) {
  const found = new Map();
  for (const key of KNOWN_TECHS) {
    const escaped = key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const re = new RegExp(`\\b${escaped}\\b`, 'i');
    if (re.test(text)) {
      const name = CANONICAL_MAP[key] || (key.charAt(0).toUpperCase() + key.slice(1));
      found.set(name, true);
    }
  }
  return [...found.keys()];
}

function escHtml(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

// ── State ──────────────────────────────────────────────────────────────────

let allProjects    = [];
let currentFilter  = 'all';
let searchQuery    = '';
let pendingDelId   = null;

// ── DOM references ─────────────────────────────────────────────────────────

const projGrid        = document.getElementById('projGrid');
const projCount       = document.getElementById('projCount');
const projLoading     = document.getElementById('projLoading');
const projEmpty       = document.getElementById('projEmpty');
const projSearch      = document.getElementById('projSearch');

const openAddBtn      = document.getElementById('openAddModal');
const modalOverlay    = document.getElementById('projModalOverlay');
const closeModalBtn   = document.getElementById('closeModal');
const cancelModalBtn  = document.getElementById('cancelModal');
const projForm        = document.getElementById('projForm');
const projModalTitle  = document.getElementById('projModalTitle');
const projId          = document.getElementById('projId');
const projTitle       = document.getElementById('projTitle');
const projDesc        = document.getElementById('projDesc');
const projDescCount   = document.getElementById('projDescCount');
const projCategory    = document.getElementById('projCategory');
const projGithub      = document.getElementById('projGithub');
const projDemo        = document.getElementById('projDemo');
const projStatus      = document.getElementById('projStatus');
const projStatusHint  = document.getElementById('projStatusHint');
const projTechPreview = document.getElementById('projTechPreview');
const projThumbnail   = document.getElementById('projThumbnail');
const projFileLabel   = document.getElementById('projFileLabel');
const projThumbPrev   = document.getElementById('projThumbPreview');
const projSubmitBtn   = document.getElementById('projSubmitBtn');
const projManualTechs  = document.getElementById('projManualTechs');
const projTagWrap      = document.getElementById('projTagWrap');
const projTagChips     = document.getElementById('projTagChips');
const projTechSearch   = document.getElementById('projTechSearch');
const projTagDropdown  = document.getElementById('projTagDropdown');

const deleteOverlay   = document.getElementById('deleteModalOverlay');
const closeDeleteBtn  = document.getElementById('closeDeleteModal');
const cancelDeleteBtn = document.getElementById('cancelDelete');
const confirmDelBtn   = document.getElementById('confirmDelete');
const deleteProjTitle = document.getElementById('deleteProjectTitle');

// ── Tech tag picker ───────────────────────────────────────────────────────

const ALL_TECH_NAMES = [...new Set(Object.values(CANONICAL_MAP))].sort();

let selectedTechs = new Set();

function syncHiddenInput() {
  projManualTechs.value = [...selectedTechs].join(',');
}

function renderChips() {
  projTagChips.innerHTML = '';
  selectedTechs.forEach(tech => {
    const chip = document.createElement('span');
    chip.className = 'proj-tag-chip';
    const cls  = TECH_ICON_CLASS[tech];
    const icon = cls ? `<i class="${cls}" aria-hidden="true"></i>` : '';
    chip.innerHTML = `${icon}${escHtml(tech)}<button type="button" class="proj-tag-chip-remove" aria-label="Remove ${escHtml(tech)}">×</button>`;
    chip.querySelector('.proj-tag-chip-remove').addEventListener('click', () => {
      selectedTechs.delete(tech);
      renderChips();
      syncHiddenInput();
    });
    projTagChips.appendChild(chip);
  });
}

function addTech(tech) {
  if (!tech || selectedTechs.has(tech)) return;
  selectedTechs.add(tech);
  renderChips();
  syncHiddenInput();
  projTechSearch.value = '';
  closeTagDropdown();
  projTechSearch.focus();
}

function showTagDropdown(items) {
  if (!items.length) { closeTagDropdown(); return; }
  projTagDropdown.innerHTML = items.map(t => {
    const cls  = TECH_ICON_CLASS[t];
    const icon = cls ? `<i class="${cls}" aria-hidden="true"></i>` : '';
    return `<div class="proj-tag-option" data-tech="${escHtml(t)}">${icon}${escHtml(t)}</div>`;
  }).join('');
  projTagDropdown.hidden = false;
}

function closeTagDropdown() {
  projTagDropdown.hidden = true;
  projTagDropdown.innerHTML = '';
}

function clearTagPicker() {
  selectedTechs.clear();
  renderChips();
  syncHiddenInput();
  if (projTechSearch) projTechSearch.value = '';
  closeTagDropdown();
}

function loadTagPicker(techs) {
  selectedTechs = new Set(Array.isArray(techs) ? techs.filter(Boolean) : []);
  renderChips();
  syncHiddenInput();
  if (projTechSearch) projTechSearch.value = '';
  closeTagDropdown();
}

if (projTechSearch) {
  projTechSearch.addEventListener('input', () => {
    const q = projTechSearch.value.trim().toLowerCase();
    if (!q) { closeTagDropdown(); return; }
    const matches = ALL_TECH_NAMES.filter(t =>
      t.toLowerCase().includes(q) && !selectedTechs.has(t)
    );
    showTagDropdown(matches.slice(0, 12));
  });

  projTechSearch.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeTagDropdown(); return; }
    if (e.key === 'Enter') {
      e.preventDefault();
      const first = projTagDropdown.querySelector('.proj-tag-option');
      if (first) addTech(first.dataset.tech);
    }
  });
}

if (projTagDropdown) {
  projTagDropdown.addEventListener('mousedown', e => {
    const opt = e.target.closest('.proj-tag-option');
    if (!opt) return;
    e.preventDefault();
    addTech(opt.dataset.tech);
  });
}

if (projTagWrap) {
  projTagWrap.addEventListener('click', () => projTechSearch && projTechSearch.focus());
}

// ── ZIP auto-detect ───────────────────────────────────────────────────────

const projZipInput     = document.getElementById('projZipInput');
const projDetectLabel  = document.getElementById('projDetectLabel');
const projDetectStatus = document.getElementById('projDetectStatus');

function setDetectStatus(msg, type) {
  if (!projDetectStatus) return;
  projDetectStatus.textContent = msg;
  projDetectStatus.className   = 'proj-detect-status proj-detect-status--' + (type || 'info');
  projDetectStatus.hidden      = false;
}

function clearDetectStatus() {
  if (projDetectStatus) {
    projDetectStatus.hidden = true;
    projDetectStatus.textContent = '';
  }
}

if (projZipInput) {
  projZipInput.addEventListener('change', async () => {
    const file = projZipInput.files[0];
    if (!file) return;

    projZipInput.value = '';
    if (projDetectLabel) projDetectLabel.classList.add('proj-detect-btn--loading');
    setDetectStatus('Scanning ZIP…', 'info');

    const formData = new FormData();
    formData.append('project', file);

    try {
      const res  = await fetch('./api/detect-techs.php', { method: 'POST', body: formData });
      const data = await res.json();

      if (!data.success) {
        setDetectStatus(data.message || 'Detection failed.', 'error');
        return;
      }

      const techs = data.technologies || [];
      if (techs.length === 0) {
        setDetectStatus('No known technologies detected in this ZIP.', 'warn');
        return;
      }

      // Merge into tag picker (keep any already selected)
      techs.forEach(t => selectedTechs.add(t));
      renderChips();
      syncHiddenInput();
      setDetectStatus(`Detected ${techs.length} technolog${techs.length === 1 ? 'y' : 'ies'}.`, 'success');
    } catch (_) {
      setDetectStatus('Network error — please try again.', 'error');
    } finally {
      if (projDetectLabel) projDetectLabel.classList.remove('proj-detect-btn--loading');
    }
  });
}

document.addEventListener('click', e => {
  if (projTagWrap && projTagDropdown &&
      !projTagWrap.contains(e.target) && !projTagDropdown.contains(e.target)) {
    closeTagDropdown();
  }
});

// ── Toast ──────────────────────────────────────────────────────────────────

let toastTimer = null;

function showToast(msg, type) {
  let toast = document.getElementById('projToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'projToast';
    document.body.appendChild(toast);
  }
  toast.textContent = msg;
  toast.className = 'proj-toast proj-toast--' + (type || 'success');
  clearTimeout(toastTimer);
  requestAnimationFrame(() => {
    toast.classList.add('proj-toast--visible');
    toastTimer = setTimeout(() => toast.classList.remove('proj-toast--visible'), 3200);
  });
}

// ── Data helpers ───────────────────────────────────────────────────────────

async function loadProjects() {
  projLoading.classList.remove('proj-grid-state--hidden');
  projEmpty.classList.add('proj-grid-state--hidden');
  clearCards();

  try {
    const res  = await fetch(PROJECTS_API);
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Load failed');
    allProjects = data.data || [];
    renderProjects();
  } catch (err) {
    showToast('Could not load projects: ' + err.message, 'error');
  } finally {
    projLoading.classList.add('proj-grid-state--hidden');
  }
}

function clearCards() {
  projGrid.querySelectorAll('.proj-card').forEach(c => c.remove());
}

function getFiltered() {
  return allProjects.filter(p => {
    const matchStatus = currentFilter === 'all' || p.status === currentFilter;
    const q           = searchQuery.trim().toLowerCase();
    const matchSearch = !q
      || p.title.toLowerCase().includes(q)
      || (p.description || '').toLowerCase().includes(q);
    return matchStatus && matchSearch;
  });
}

function renderProjects() {
  clearCards();
  const filtered = getFiltered();
  const total    = allProjects.length;

  projCount.textContent = total + ' project' + (total !== 1 ? 's' : '');

  if (filtered.length === 0) {
    projEmpty.classList.remove('proj-grid-state--hidden');
    return;
  }

  projEmpty.classList.add('proj-grid-state--hidden');
  filtered.forEach(proj => projGrid.appendChild(buildCard(proj)));
}

// ── Card builder ───────────────────────────────────────────────────────────

const SVG_EDIT = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>`;
const SVG_TRASH = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>`;
const SVG_IMG   = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>`;
const SVG_GITHUB = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path></svg>`;
const SVG_LINK  = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>`;
const SVG_EYE   = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
const SVG_MSG   = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>`;

const STATUS_ICONS = {
  published: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>`,
  draft:     `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
  archived:  `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>`,
};

function buildCard(proj) {
  const card  = document.createElement('div');
  card.className = 'proj-card';
  card.dataset.id = proj.id;

  const techs      = Array.isArray(proj.technologies) ? proj.technologies : [];
  const techPills  = techs.map(t => {
    const cls  = TECH_ICON_CLASS[t];
    const icon = cls ? `<i class="${cls}" aria-hidden="true"></i>` : '';
    return `<span class="proj-tech-pill">${icon}${escHtml(t)}</span>`;
  }).join('');
  const demoUrl    = proj.project_url || '';
  const statusIcon = STATUS_ICONS[proj.status] || '';

  const imgHtml = proj.thumbnail_url
    ? `<img class="proj-card-image" src="${escHtml(proj.thumbnail_url)}" alt="${escHtml(proj.title)}" loading="lazy" />`
    : `<div class="proj-card-image-placeholder">${SVG_IMG}</div>`;

  const githubHtml = proj.github_url
    ? `<a href="${escHtml(proj.github_url)}" target="_blank" rel="noopener noreferrer" class="proj-card-link">
        ${SVG_GITHUB} GitHub
       </a>`
    : '';

  const demoHtml = demoUrl
    ? `<a href="${escHtml(demoUrl)}" target="_blank" rel="noopener noreferrer" class="proj-card-link">
        ${SVG_LINK} Live Demo
       </a>`
    : '';

  const linksHtml = (githubHtml || demoHtml)
    ? `<div class="proj-card-links">${githubHtml}${demoHtml}</div>`
    : '';

  const techsHtml = techs.length
    ? `<div class="proj-card-techs">${techPills}</div>`
    : '';

  card.innerHTML = `
    <div class="proj-card-actions">
      <button type="button" class="proj-card-action-btn js-edit-btn" data-tooltip="Edit" aria-label="Edit project">
        ${SVG_EDIT}
      </button>
      <button type="button" class="proj-card-action-btn proj-card-action-btn--delete js-delete-btn" data-tooltip="Delete" aria-label="Delete project">
        ${SVG_TRASH}
      </button>
    </div>
    ${imgHtml}
    <div class="proj-card-body">
      <h3 class="proj-card-title">${escHtml(proj.title)}</h3>
      ${techsHtml}
      <span class="proj-card-status proj-card-status--${escHtml(proj.status)}">
        ${statusIcon}
        ${escHtml(proj.status.charAt(0).toUpperCase() + proj.status.slice(1))}
      </span>
      ${linksHtml}
    </div>
    <div class="proj-card-footer">
      <span class="proj-card-meta">
        ${SVG_EYE}
        ${Number(proj.views || 0).toLocaleString()}
      </span>
      <span class="proj-card-meta">
        ${SVG_MSG}
        ${Number(proj.comments_count || 0).toLocaleString()}
      </span>
    </div>`;

  card.querySelector('.js-edit-btn').addEventListener('click', () => openEditModal(proj));
  card.querySelector('.js-delete-btn').addEventListener('click', () => openDeleteModal(proj.id, proj.title));

  return card;
}

// ── Modal: Add ─────────────────────────────────────────────────────────────

const DESC_LIMIT = 130;

function updateDescCount() {
  if (!projDescCount) return;
  const len = projDesc.value.length;
  projDescCount.textContent = len + ' / ' + DESC_LIMIT;
  projDescCount.classList.toggle('proj-char-count--warn', len > DESC_LIMIT);
}

function openAddModal() {
  projModalTitle.textContent = 'Add Project';
  projForm.reset();
  projId.value               = '';
  projTechPreview.innerHTML  = '';
  projThumbPrev.innerHTML    = '';
  projFileLabel.textContent  = 'Choose image';
  projStatusHint.textContent = '(auto)';
  projStatus.value           = 'draft';
  clearTagPicker();
  clearDetectStatus();
  updateDescCount();
  modalOverlay.classList.remove('proj-modal-overlay--hidden');
  projTitle.focus();
}

// ── Modal: Edit ────────────────────────────────────────────────────────────

function openEditModal(proj) {
  projModalTitle.textContent = 'Edit Project';
  projId.value        = proj.id;
  projTitle.value     = proj.title || '';
  projDesc.value      = proj.description || '';
  updateDescCount();
  projCategory.value  = proj.category || 'Web Systems';
  projGithub.value    = proj.github_url || '';
  projDemo.value      = proj.project_url || '';
  projStatus.value    = proj.status || 'draft';
  projStatusHint.textContent = '';
  loadTagPicker(proj.technologies || []);
  clearDetectStatus();
  projTechPreview.innerHTML = '';

  projThumbPrev.innerHTML = proj.thumbnail_url
    ? `<img src="${escHtml(proj.thumbnail_url)}" alt="Current thumbnail" />`
    : '';
  projFileLabel.textContent = proj.thumbnail_url ? 'Replace image' : 'Choose image';

  modalOverlay.classList.remove('proj-modal-overlay--hidden');
  projTitle.focus();
}

function closeModal() {
  modalOverlay.classList.add('proj-modal-overlay--closing');
  modalOverlay.addEventListener('animationend', () => {
    modalOverlay.classList.remove('proj-modal-overlay--closing');
    modalOverlay.classList.add('proj-modal-overlay--hidden');
  }, { once: true });
}

// ── Modal: Delete ──────────────────────────────────────────────────────────

function openDeleteModal(id, title) {
  pendingDelId = id;
  deleteProjTitle.textContent = title;
  deleteOverlay.classList.remove('proj-modal-overlay--hidden');
}

function closeDeleteModal() {
  deleteOverlay.classList.add('proj-modal-overlay--closing');
  deleteOverlay.addEventListener('animationend', () => {
    deleteOverlay.classList.remove('proj-modal-overlay--closing');
    deleteOverlay.classList.add('proj-modal-overlay--hidden');
  }, { once: true });
  pendingDelId = null;
}

// ── Auto-detect tech preview ───────────────────────────────────────────────

function updateTechPreview() {
  const text     = (projTitle.value || '') + ' ' + (projDesc.value || '');
  const detected = detectTechs(text);

  if (detected.length === 0) {
    projTechPreview.innerHTML = '';
    return;
  }

  projTechPreview.innerHTML =
    '<span class="proj-tech-preview-label">Auto-detected:</span>' +
    detected.map(t => `<span class="proj-tech-pill">${escHtml(t)}</span>`).join('');
}

// ── Auto status suggestion (create-only) ──────────────────────────────────

function updateStatusSuggestion() {
  if (projId.value) {
    projStatusHint.textContent = '';
    return;
  }
  const hasDemoUrl = projDemo.value.trim() !== '';
  const suggested  = hasDemoUrl ? 'published' : 'draft';
  projStatus.value           = suggested;
  projStatusHint.textContent = hasDemoUrl ? '(auto: published)' : '(auto: draft)';
}

// ── File input preview ─────────────────────────────────────────────────────

projThumbnail.addEventListener('change', () => {
  const file = projThumbnail.files[0];
  if (!file) return;
  projFileLabel.textContent = file.name;
  const reader = new FileReader();
  reader.onload = e => {
    projThumbPrev.innerHTML = `<img src="${e.target.result}" alt="Preview" />`;
  };
  reader.readAsDataURL(file);
});

// ── Filter tabs ────────────────────────────────────────────────────────────

document.querySelectorAll('.proj-filter-tab').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.proj-filter-tab')
      .forEach(b => b.classList.remove('proj-filter-tab--active'));
    btn.classList.add('proj-filter-tab--active');
    currentFilter = btn.dataset.filter;
    renderProjects();
  });
});

// ── Search ─────────────────────────────────────────────────────────────────

projSearch.addEventListener('input', () => {
  searchQuery = projSearch.value;
  renderProjects();
});

// ── Input event listeners ──────────────────────────────────────────────────

projTitle.addEventListener('input', updateTechPreview);
projDesc.addEventListener('input', updateTechPreview);
projDesc.addEventListener('input', updateDescCount);
projDemo.addEventListener('input', updateStatusSuggestion);

// ── Modal open/close listeners ─────────────────────────────────────────────

openAddBtn.addEventListener('click', openAddModal);
closeModalBtn.addEventListener('click', closeModal);
cancelModalBtn.addEventListener('click', closeModal);
modalOverlay.addEventListener('click', e => {
  if (e.target === modalOverlay) closeModal();
});

closeDeleteBtn.addEventListener('click', closeDeleteModal);
cancelDeleteBtn.addEventListener('click', closeDeleteModal);
deleteOverlay.addEventListener('click', e => {
  if (e.target === deleteOverlay) closeDeleteModal();
});

// ── Delete confirm ─────────────────────────────────────────────────────────

confirmDelBtn.addEventListener('click', async () => {
  if (!pendingDelId) return;
  confirmDelBtn.disabled = true;

  try {
    const res  = await fetch(`${PROJECTS_API}?id=${pendingDelId}`, { method: 'DELETE' });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Delete failed');

    allProjects = allProjects.filter(p => String(p.id) !== String(pendingDelId));
    renderProjects();
    showToast('Project removed successfully');
    closeDeleteModal();
  } catch (err) {
    showToast(err.message, 'error');
  } finally {
    confirmDelBtn.disabled = false;
  }
});

// ── Form submit (create / update) ──────────────────────────────────────────

projForm.addEventListener('submit', async e => {
  e.preventDefault();

  if (!projTitle.value.trim()) {
    projTitle.focus();
    showToast('Project title is required', 'error');
    return;
  }

  const originalLabel = projSubmitBtn.innerHTML;
  projSubmitBtn.disabled   = true;
  projSubmitBtn.textContent = 'Saving…';

  try {
    const res  = await fetch(PROJECTS_API, { method: 'POST', body: new FormData(projForm) });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Save failed');

    showToast(data.message || 'Project saved');
    closeModal();
    await loadProjects();
  } catch (err) {
    showToast(err.message, 'error');
  } finally {
    projSubmitBtn.disabled  = false;
    projSubmitBtn.innerHTML = originalLabel;
  }
});

// ── Escape key closes modals ───────────────────────────────────────────────

document.addEventListener('keydown', e => {
  if (e.key !== 'Escape') return;
  if (!modalOverlay.classList.contains('proj-modal-overlay--hidden'))  closeModal();
  if (!deleteOverlay.classList.contains('proj-modal-overlay--hidden')) closeDeleteModal();
});

// ── Init ───────────────────────────────────────────────────────────────────

loadProjects();
