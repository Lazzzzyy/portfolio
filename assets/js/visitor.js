'use strict';

// ── Theme ─────────────────────────────────────────────────────────────────────

const THEME_KEY    = 'portfolio-theme';
const root         = document.documentElement;
const themeBtn     = document.getElementById('visThemeBtn');
const sunIcon      = document.getElementById('visSunIcon');
const moonIcon     = document.getElementById('visMoonIcon');
const profileImg   = document.getElementById('visProfileImg');

function currentTheme() {
  return root.getAttribute('data-theme') || 'light';
}

function applyTheme(theme) {
  root.setAttribute('data-theme', theme);
  if (sunIcon)  sunIcon.style.display  = theme === 'dark'  ? 'block' : 'none';
  if (moonIcon) moonIcon.style.display = theme === 'light' ? 'block' : 'none';
  updateProfileImg(theme);
}

function updateProfileImg(theme) {
  if (!profileImg) return;
  const src = theme === 'dark'
    ? profileImg.dataset.dark
    : profileImg.dataset.light;
  if (src && profileImg.src !== src) {
    profileImg.src = src;
  }
}

function saveTheme(theme) {
  try {
    localStorage.setItem(THEME_KEY, JSON.stringify({ mode: 'manual', theme }));
  } catch (_) {}
  document.cookie = 'portfolio-theme=' + theme + '; path=/; max-age=31536000; SameSite=Lax';
}

if (themeBtn) {
  themeBtn.addEventListener('click', () => {
    const next = currentTheme() === 'light' ? 'dark' : 'light';
    saveTheme(next);
    applyTheme(next);
  });
}

// Restore saved theme; fall back to OS preference for first-time visitors
(function () {
  let theme = null;
  try {
    const raw = localStorage.getItem(THEME_KEY);
    if (raw) {
      const p = JSON.parse(raw);
      if (p && p.mode === 'manual' && (p.theme === 'light' || p.theme === 'dark')) {
        theme = p.theme;
      }
    }
  } catch (_) {}
  if (!theme) {
    theme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)
      ? 'dark' : 'light';
  }
  applyTheme(theme);
}());

// When the browser/OS preference changes, clear any manual override and follow it
if (window.matchMedia) {
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
    var theme = e.matches ? 'dark' : 'light';
    try { localStorage.removeItem(THEME_KEY); } catch (_) {}
    document.cookie = 'portfolio-theme=' + theme + '; path=/; max-age=31536000; SameSite=Lax';
    applyTheme(theme);
  });
}

// Sync across tabs when storage changes
window.addEventListener('storage', e => {
  if (e.key !== THEME_KEY) return;
  if (e.newValue === null) {
    // Another tab cleared the manual override — follow OS preference
    var os = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)
      ? 'dark' : 'light';
    applyTheme(os);
    return;
  }
  try {
    const p = JSON.parse(e.newValue);
    if (p?.mode === 'manual' && (p.theme === 'light' || p.theme === 'dark')) {
      applyTheme(p.theme);
    }
  } catch (_) {}
});

// ── Sticky header scroll-shadow ───────────────────────────────────────────────

const header = document.getElementById('vis-header');
window.addEventListener('scroll', () => {
  if (!header) return;
  header.classList.toggle('vis-header--scrolled', window.scrollY > 20);
}, { passive: true });

// ── Scroll-to-top (logo button) ───────────────────────────────────────────────

const scrollTopBtn = document.getElementById('scrollTopBtn');
if (scrollTopBtn) {
  scrollTopBtn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
}

// ── Active nav link (scroll spy) ──────────────────────────────────────────────

const navLinks = document.querySelectorAll('.vis-nav-link[data-section]');

navLinks.forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();

    // Click animation
    link.classList.remove('vis-nav-link--clicking');
    void link.offsetWidth;
    link.classList.add('vis-nav-link--clicking');
    link.addEventListener('animationend', () => {
      link.classList.remove('vis-nav-link--clicking');
    }, { once: true });

    // Smooth scroll with header offset
    const targetId = link.getAttribute('href').slice(1);
    const target   = document.getElementById(targetId);
    if (!target) return;

    const headerH = header ? header.getBoundingClientRect().height : 70;
    const top     = target.getBoundingClientRect().top + window.scrollY - headerH;

    window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
    history.pushState(null, '', '#' + targetId);
  });
});

const sections = Array.from(navLinks).map(link => {
  const id = link.dataset.section;
  return { link, el: document.getElementById(id) };
}).filter(s => s.el);

function updateActiveNav() {
  const headerH = header ? header.offsetHeight : 70;
  const scrollY = window.scrollY + headerH + 16;

  let current = null;
  for (const { el } of sections) {
    if (el.offsetTop <= scrollY) current = el.id;
  }

  navLinks.forEach(link => {
    link.classList.toggle('vis-nav-link--active', link.dataset.section === current);
  });
}

window.addEventListener('scroll', updateActiveNav, { passive: true });
updateActiveNav();

// ── Intersection Observer: reveal animations ──────────────────────────────────

const io = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('vis-reveal--visible');
      io.unobserve(entry.target);
    }
  });
}, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

document.querySelectorAll('.vis-reveal').forEach(el => io.observe(el));

// ── Skill progress bars (animate on intersect) ────────────────────────────────

const barIO = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (!entry.isIntersecting) return;
    const bar = entry.target;
    const pct = bar.dataset.pct;
    if (pct) {
      bar.style.setProperty('--bar-pct', pct + '%');
      // Small tick lets the CSS transition play after value is set
      requestAnimationFrame(() => {
        requestAnimationFrame(() => bar.classList.add('vis-bar-animated'));
      });
    }
    barIO.unobserve(bar);
  });
}, { threshold: 0.3 });

document.querySelectorAll('.vis-skill-bar[data-pct]').forEach(bar => barIO.observe(bar));

// ── Lazy image loading ────────────────────────────────────────────────────────

const imgIO = new IntersectionObserver((entries, obs) => {
  entries.forEach(entry => {
    if (!entry.isIntersecting) return;
    const img = entry.target;
    const src = img.dataset.src;
    if (src) {
      img.src = src;
      img.removeAttribute('data-src');
    }
    obs.unobserve(img);
  });
}, { rootMargin: '200px' });

document.querySelectorAll('img[data-src]').forEach(img => imgIO.observe(img));

// ── "See more" description expand ────────────────────────────────────────────

document.addEventListener('click', e => {
  const btn = e.target.closest('.vis-see-more');
  if (!btn) return;
  const wrapper = btn.closest('.vis-desc-more');
  if (!wrapper) return;
  const full = wrapper.dataset.full;
  const p    = wrapper.closest('.vis-card-desc');
  if (p && full) {
    p.textContent = full;
  }
});

// ── Tooltip ───────────────────────────────────────────────────────────────────

let tip     = null;
let tipPend = false;

function getTip() {
  if (!tip) {
    tip = document.querySelector('.js-tooltip') || (() => {
      const el = document.createElement('div');
      el.className = 'js-tooltip';
      el.setAttribute('role', 'tooltip');
      el.setAttribute('aria-hidden', 'true');
      document.body.appendChild(el);
      return el;
    })();
  }
  return tip;
}

function showTip(target) {
  const text = target.getAttribute('data-tooltip');
  if (!text) return;
  const el = getTip();
  el.textContent = text;
  el.style.display = 'block';
  el.style.opacity = '0';
  el.style.left = '0px';
  el.style.top  = '0px';
  tipPend = true;
  requestAnimationFrame(() => {
    if (!tipPend) return;
    const r  = target.getBoundingClientRect();
    const tr = el.getBoundingClientRect();
    let x = r.left + r.width / 2 - tr.width / 2;
    let y = r.bottom + 8;
    x = Math.max(4, Math.min(x, window.innerWidth  - tr.width  - 4));
    y = Math.max(4, Math.min(y, window.innerHeight - tr.height - 4));
    el.style.left = x + 'px';
    el.style.top  = y + 'px';
    el.style.opacity = '1';
  });
}

function hideTip() {
  tipPend = false;
  if (tip) tip.style.opacity = '0';
}

document.addEventListener('mouseover', e => {
  const t = e.target.closest('[data-tooltip]');
  t ? showTip(t) : hideTip();
});

document.addEventListener('mouseout', e => {
  if (!e.relatedTarget?.closest('[data-tooltip]')) hideTip();
});

// ── CV Modal ──────────────────────────────────────────────────────────────────

const cvBtn      = document.getElementById('visCvBtn');
const cvModal    = document.getElementById('visCvModal');
const cvBackdrop = document.getElementById('visCvBackdrop');
const cvClose    = document.getElementById('visCvClose');
const cvIframe   = document.getElementById('visCvIframe');
const cvDownload = document.getElementById('visCvDownload');

function toCvEmbedUrl(url) {
  // Google Drive: /file/d/ID/view  →  /file/d/ID/preview
  const driveFile = url.match(/drive\.google\.com\/file\/d\/([^/?#]+)/);
  if (driveFile) {
    return 'https://drive.google.com/file/d/' + driveFile[1] + '/preview';
  }
  // Google Drive: open?id=ID  →  /file/d/ID/preview
  const driveOpen = url.match(/drive\.google\.com\/open\?id=([^&]+)/);
  if (driveOpen) {
    return 'https://drive.google.com/file/d/' + driveOpen[1] + '/preview';
  }
  // Local or direct PDF — use as-is
  return url;
}

function openCvModal() {
  if (!cvModal || !cvBtn) return;
  const raw = cvBtn.dataset.cvUrl || '';
  if (!raw) return;
  const embedUrl = toCvEmbedUrl(raw);
  cvIframe.src = embedUrl;
  if (cvDownload) {
    cvDownload.href = raw;
  }
  cvModal.hidden = false;
  document.body.style.overflow = 'hidden';
  cvClose && cvClose.focus();
}

function closeCvModal() {
  if (!cvModal) return;
  cvModal.hidden = true;
  cvIframe.src   = '';
  document.body.style.overflow = '';
  cvBtn && cvBtn.focus();
}

if (cvBtn)      cvBtn.addEventListener('click', openCvModal);
if (cvClose)    cvClose.addEventListener('click', closeCvModal);
if (cvBackdrop) cvBackdrop.addEventListener('click', closeCvModal);

document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && cvModal && !cvModal.hidden) closeCvModal();
});

// ── Contact form ──────────────────────────────────────────────────────────────

const form       = document.getElementById('visContactForm');
const statusEl   = document.getElementById('cfStatus');
const submitBtn  = document.getElementById('cfSubmitBtn');

function setStatus(msg, type) {
  if (!statusEl) return;
  statusEl.textContent = msg;
  statusEl.className   = 'vis-form-status vis-form-status--' + type;
  statusEl.hidden      = false;
}

function clearStatus() {
  if (!statusEl) return;
  statusEl.hidden    = true;
  statusEl.textContent = '';
  statusEl.className = 'vis-form-status';
}

function markError(input) {
  input.classList.add('vis-input--error');
  input.addEventListener('input', () => input.classList.remove('vis-input--error'), { once: true });
}

if (form) {
  form.addEventListener('submit', async e => {
    e.preventDefault();
    clearStatus();

    const data = {
      name:     form.querySelector('#cf-name').value.trim(),
      email:    form.querySelector('#cf-email').value.trim(),
      category: form.querySelector('#cf-category').value,
      subject:  form.querySelector('#cf-subject').value.trim(),
      message:  form.querySelector('#cf-message').value.trim(),
    };

    // Basic client-side validation
    let hasError = false;
    if (!data.name)     { markError(form.querySelector('#cf-name'));     hasError = true; }
    if (!data.email)    { markError(form.querySelector('#cf-email'));    hasError = true; }
    if (!data.category) { markError(form.querySelector('#cf-category')); hasError = true; }
    if (!data.subject)  { markError(form.querySelector('#cf-subject'));  hasError = true; }
    if (!data.message)  { markError(form.querySelector('#cf-message'));  hasError = true; }

    if (hasError) {
      setStatus('Please fill in all required fields.', 'error');
      return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = 'Sending…';

    try {
      const res  = await fetch('./api/contact-submit.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      });
      const json = await res.json();

      if (json.success) {
        setStatus('Your message has been sent successfully. I\'ll get back to you soon!', 'success');
        form.reset();
      } else {
        const msgs = json.errors ? json.errors.join(' ') : (json.message || 'Something went wrong.');
        setStatus(msgs, 'error');
      }
    } catch (_) {
      setStatus('Unable to send message. Please try again later.', 'error');
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Send Message';
    }
  });
}

// ── Dynamic profile refresh (social links + phones) ───────────────────────────

async function refreshProfile() {
  try {
    const res  = await fetch('./api/public-profile.php');
    const data = await res.json();
    if (!data.success) return;

    const s = data.social || {};

    document.querySelectorAll('[data-social]').forEach(el => {
      const key = el.dataset.social;
      let raw = s[key] || '';
      let url = '';

      if (key === 'email') {
        url = raw ? 'https://mail.google.com/mail/?view=cm&fs=1&to=' + encodeURIComponent(raw) : '';
      } else {
        url = raw;
      }

      if (url) {
        el.href = url;
        el.setAttribute('target', '_blank');
        el.setAttribute('rel', 'noopener noreferrer');
        el.removeAttribute('aria-hidden');
        el.removeAttribute('tabindex');
        el.classList.remove('vis-social--empty');
      } else {
        el.href = '#';
        el.removeAttribute('target');
        el.removeAttribute('rel');
        el.setAttribute('aria-hidden', 'true');
        el.setAttribute('tabindex', '-1');
        el.classList.add('vis-social--empty');
      }
    });

    const p = data.phones || {};

    const phonesWrap   = document.getElementById('visPhonesWrap');
    const rowDito      = document.getElementById('visPhoneRowDito');
    const rowTnt       = document.getElementById('visPhoneRowTnt');
    const rowGlobe     = document.getElementById('visPhoneRowGlobe');
    const spanDito     = document.getElementById('visPhoneDito');
    const spanTnt      = document.getElementById('visPhoneTnt');
    const spanGlobe    = document.getElementById('visPhoneGlobe');

    if (spanDito)  spanDito.textContent  = p.dito  || '';
    if (spanTnt)   spanTnt.textContent   = p.tnt   || '';
    if (spanGlobe) spanGlobe.textContent = p.globe || '';

    if (rowDito)  rowDito.hidden  = !p.dito;
    if (rowTnt)   rowTnt.hidden   = !p.tnt;
    if (rowGlobe) rowGlobe.hidden = !p.globe;
    if (phonesWrap) phonesWrap.hidden = !p.dito && !p.tnt && !p.globe;

  } catch (_) {}
}

refreshProfile();

document.addEventListener('visibilitychange', () => {
  if (document.visibilityState === 'visible') refreshProfile();
});
