'use strict';

const PROFILE_API = './api/profile.php';

// ── DOM refs ────────────────────────────────────────────────────────────────

const pfFullName     = document.getElementById('pfFullName');
const pfEmail        = document.getElementById('pfEmail');
const pfSaveAccount  = document.getElementById('pfSaveAccount');

const pfName         = document.getElementById('pfName');
const pfBio          = document.getElementById('pfBio');
const pfCvUrl        = document.getElementById('pfCvUrl');
const pfSavePublic   = document.getElementById('pfSavePublic');

const pfSocialEmail  = document.getElementById('pf-email');
const pfGithub       = document.getElementById('pf-github');
const pfLinkedin     = document.getElementById('pf-linkedin');
const pfFacebook     = document.getElementById('pf-facebook');
const pfInstagram    = document.getElementById('pf-instagram');
const pfTwitter      = document.getElementById('pf-twitter');
const pfTelegram     = document.getElementById('pf-telegram');
const pfDiscord      = document.getElementById('pf-discord');
const pfViber        = document.getElementById('pf-viber');
const pfWhatsapp     = document.getElementById('pf-whatsapp');
const pfSaveSocial   = document.getElementById('pfSaveSocial');

const pfPhoneDito    = document.getElementById('pfPhoneDito');
const pfPhoneTnt     = document.getElementById('pfPhoneTnt');
const pfPhoneGlobe   = document.getElementById('pfPhoneGlobe');
const pfSavePhones   = document.getElementById('pfSavePhones');

const pfToast        = document.getElementById('pfToast');

const pfCvTabLink      = document.getElementById('pfCvTabLink');
const pfCvTabFile      = document.getElementById('pfCvTabFile');
const pfCvPanelLink    = document.getElementById('pfCvPanelLink');
const pfCvPanelFile    = document.getElementById('pfCvPanelFile');
const pfCvDrop         = document.getElementById('pfCvDrop');
const pfCvFile         = document.getElementById('pfCvFile');
const pfCvDropText     = document.getElementById('pfCvDropText');
const pfCvUploadStatus = document.getElementById('pfCvUploadStatus');
const pfRemoveCv           = document.getElementById('pfRemoveCv');
const pfRemoveCvOverlay    = document.getElementById('pfRemoveCvOverlay');
const pfRemoveCvCancel     = document.getElementById('pfRemoveCvCancel');
const pfRemoveCvCancelBtn  = document.getElementById('pfRemoveCvCancelBtn');
const pfRemoveCvConfirmBtn = document.getElementById('pfRemoveCvConfirmBtn');

// ── Toast ────────────────────────────────────────────────────────────────────

let toastTimer = null;

function showToast(msg, type) {
  if (toastTimer) clearTimeout(toastTimer);
  pfToast.textContent = msg;
  pfToast.className   = 'pf-toast pf-toast--' + (type || 'success');
  pfToast.classList.add('pf-toast--visible');
  toastTimer = setTimeout(() => {
    pfToast.classList.remove('pf-toast--visible');
  }, 3200);
}

// ── Button loading state ─────────────────────────────────────────────────────

function setBusy(btn, busy) {
  btn.disabled = busy;
  btn.classList.toggle('pf-save-btn--loading', busy);
}

// ── Load profile data ────────────────────────────────────────────────────────

async function loadProfile() {
  try {
    const res  = await fetch(PROFILE_API);
    const json = await res.json();
    if (!json.success) return;

    const d = json.data || {};

    // Account fields from session (prefixed to avoid collision with portfolio_entries keys)
    pfFullName.value = d._account_name  || '';
    pfEmail.value    = d._account_email || '';

    // Public profile
    pfName.value  = d.name  || d.about_name  || '';
    pfBio.value   = d.bio   || d.about_bio   || '';
    pfCvUrl.value = d.cv_url || d.about_cv_url || '';
    if (pfRemoveCv) pfRemoveCv.hidden = !(d.cv_url || d.about_cv_url);

    // Social links (stored as JSON blob under key 'social_links')
    const s = (typeof d.social_links === 'object' && d.social_links) ? d.social_links : {};
    pfSocialEmail.value = s.email     || d.email         || d.contact_email   || '';
    pfGithub.value      = s.github    || d.github        || d.github_url      || '';
    pfLinkedin.value    = s.linkedin  || d.linkedin      || d.linkedin_url    || '';
    pfFacebook.value    = s.facebook  || d.facebook      || d.facebook_url    || '';
    pfInstagram.value   = s.instagram || d.instagram     || d.instagram_url   || '';
    pfTwitter.value     = s.twitter   || d.twitter       || d.twitter_url     || '';
    pfTelegram.value    = s.telegram  || d.telegram      || d.telegram_url    || '';
    pfDiscord.value     = s.discord   || d.discord       || d.discord_url     || '';
    pfViber.value       = s.viber     || d.viber         || d.viber_url       || '';
    pfWhatsapp.value    = s.whatsapp  || d.whatsapp      || d.whatsapp_url    || '';

    // Phone numbers
    pfPhoneDito.value  = d.phone_discord || d.phone_1 || '';
    pfPhoneTnt.value   = d.phone_tnt     || d.phone_2 || '';
    pfPhoneGlobe.value = d.phone_globe   || d.phone_3 || '';

  } catch (err) {
    console.error('Failed to load profile:', err);
  }
}

// ── Save helpers ─────────────────────────────────────────────────────────────

async function postProfile(payload) {
  const res  = await fetch(PROFILE_API, {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify(payload),
  });
  return res.json();
}

// ── Save account ─────────────────────────────────────────────────────────────

pfSaveAccount.addEventListener('click', async () => {
  const fullName = pfFullName.value.trim();
  const email    = pfEmail.value.trim();

  if (!fullName || !email) {
    showToast('Name and email are required', 'error');
    return;
  }

  setBusy(pfSaveAccount, true);
  try {
    const json = await postProfile({ action: 'account', full_name: fullName, email });
    if (json.success) {
      showToast('Account updated', 'success');
    } else {
      showToast(json.message || 'Could not save account', 'error');
    }
  } catch (err) {
    showToast('Network error — please try again', 'error');
  } finally {
    setBusy(pfSaveAccount, false);
  }
});

// ── Save public profile ──────────────────────────────────────────────────────

pfSavePublic.addEventListener('click', async () => {
  setBusy(pfSavePublic, true);
  try {
    const json = await postProfile({
      action:  'public',
      name:    pfName.value.trim(),
      bio:     pfBio.value.trim(),
      cv_url:  pfCvUrl.value.trim(),
    });
    if (json.success) {
      showToast('Visitor profile saved', 'success');
    } else {
      showToast(json.message || 'Could not save profile', 'error');
    }
  } catch (err) {
    showToast('Network error — please try again', 'error');
  } finally {
    setBusy(pfSavePublic, false);
  }
});

// ── Save social links ────────────────────────────────────────────────────────

pfSaveSocial.addEventListener('click', async () => {
  setBusy(pfSaveSocial, true);
  try {
    const json = await postProfile({
      action: 'public',
      social_links: {
        email:     pfSocialEmail.value.trim(),
        github:    pfGithub.value.trim(),
        linkedin:  pfLinkedin.value.trim(),
        facebook:  pfFacebook.value.trim(),
        instagram: pfInstagram.value.trim(),
        twitter:   pfTwitter.value.trim(),
        telegram:  pfTelegram.value.trim(),
        discord:   pfDiscord.value.trim(),
        viber:     pfViber.value.trim(),
        whatsapp:  pfWhatsapp.value.trim(),
      },
    });
    if (json.success) {
      showToast('Social links saved', 'success');
    } else {
      showToast(json.message || 'Could not save social links', 'error');
    }
  } catch (err) {
    showToast('Network error — please try again', 'error');
  } finally {
    setBusy(pfSaveSocial, false);
  }
});

// ── Save phone numbers ───────────────────────────────────────────────────────

pfSavePhones.addEventListener('click', async () => {
  setBusy(pfSavePhones, true);
  try {
    const json = await postProfile({
      action:       'public',
      phone_discord: pfPhoneDito.value.trim(),
      phone_tnt:     pfPhoneTnt.value.trim(),
      phone_globe:   pfPhoneGlobe.value.trim(),
    });
    if (json.success) {
      showToast('Phone numbers saved', 'success');
    } else {
      showToast(json.message || 'Could not save phone numbers', 'error');
    }
  } catch (err) {
    showToast('Network error — please try again', 'error');
  } finally {
    setBusy(pfSavePhones, false);
  }
});

// ── Remove CV (soft-delete: clears cv_url in DB, file stays on disk) ──────────

function openRemoveCvModal() {
  if (pfRemoveCvOverlay) pfRemoveCvOverlay.classList.remove('pf-modal-overlay--hidden');
}

function closeRemoveCvModal() {
  if (!pfRemoveCvOverlay) return;
  pfRemoveCvOverlay.classList.add('pf-modal-overlay--closing');
  pfRemoveCvOverlay.addEventListener('animationend', () => {
    pfRemoveCvOverlay.classList.remove('pf-modal-overlay--closing');
    pfRemoveCvOverlay.classList.add('pf-modal-overlay--hidden');
  }, { once: true });
}

if (pfRemoveCv) {
  pfRemoveCv.addEventListener('click', openRemoveCvModal);
}

if (pfRemoveCvCancel)    pfRemoveCvCancel.addEventListener('click', closeRemoveCvModal);
if (pfRemoveCvCancelBtn) pfRemoveCvCancelBtn.addEventListener('click', closeRemoveCvModal);
if (pfRemoveCvOverlay) {
  pfRemoveCvOverlay.addEventListener('click', e => {
    if (e.target === pfRemoveCvOverlay) closeRemoveCvModal();
  });
}

if (pfRemoveCvConfirmBtn) {
  pfRemoveCvConfirmBtn.addEventListener('click', async () => {
    setBusy(pfRemoveCvConfirmBtn, true);
    try {
      const json = await postProfile({ action: 'public', cv_url: '' });
      if (json.success) {
        pfCvUrl.value = '';
        if (pfRemoveCv) pfRemoveCv.hidden = true;
        showToast('CV removed', 'success');
        closeRemoveCvModal();
      } else {
        showToast(json.message || 'Could not remove CV', 'error');
      }
    } catch (err) {
      showToast('Network error — please try again', 'error');
    } finally {
      setBusy(pfRemoveCvConfirmBtn, false);
    }
  });
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && pfRemoveCvOverlay &&
      !pfRemoveCvOverlay.classList.contains('pf-modal-overlay--hidden')) {
    closeRemoveCvModal();
  }
});

// ── CV tab switching ─────────────────────────────────────────────────────────

function switchCvTab(tab) {
  const isLink = tab === 'link';
  if (pfCvTabLink)   { pfCvTabLink.classList.toggle('pf-cv-tab--active', isLink);  pfCvTabLink.setAttribute('aria-selected', String(isLink)); }
  if (pfCvTabFile)   { pfCvTabFile.classList.toggle('pf-cv-tab--active', !isLink); pfCvTabFile.setAttribute('aria-selected', String(!isLink)); }
  if (pfCvPanelLink) pfCvPanelLink.hidden = !isLink;
  if (pfCvPanelFile) pfCvPanelFile.hidden = isLink;
}

if (pfCvTabLink) pfCvTabLink.addEventListener('click', () => switchCvTab('link'));
if (pfCvTabFile) pfCvTabFile.addEventListener('click', () => switchCvTab('file'));

// ── CV file upload (drag-and-drop + browse) ───────────────────────────────────

function setUploadStatus(msg, type) {
  if (!pfCvUploadStatus) return;
  pfCvUploadStatus.textContent = msg;
  pfCvUploadStatus.className   = 'pf-cv-upload-status pf-cv-upload-status--' + (type || 'info');
  pfCvUploadStatus.hidden      = false;
}

async function uploadCvFile(file) {
  if (!file) return;
  if (file.type !== 'application/pdf') {
    setUploadStatus('Only PDF files are accepted.', 'error');
    return;
  }
  if (file.size > 10 * 1024 * 1024) {
    setUploadStatus('File exceeds the 10 MB limit.', 'error');
    return;
  }

  if (pfCvDropText) pfCvDropText.textContent = file.name;
  setUploadStatus('Uploading…', 'info');

  const formData = new FormData();
  formData.append('cv', file);

  try {
    const res  = await fetch('./api/upload-cv.php', { method: 'POST', body: formData });
    const json = await res.json();
    if (json.success) {
      setUploadStatus('CV uploaded successfully.', 'success');
      pfCvUrl.value = json.cv_url || '';
      showToast('CV uploaded and saved', 'success');
    } else {
      setUploadStatus(json.message || 'Upload failed.', 'error');
    }
  } catch (err) {
    setUploadStatus('Network error — please try again.', 'error');
  }
}

if (pfCvFile) {
  pfCvFile.addEventListener('change', () => {
    if (pfCvFile.files[0]) uploadCvFile(pfCvFile.files[0]);
  });
}

if (pfCvDrop) {
  pfCvDrop.addEventListener('click', () => pfCvFile && pfCvFile.click());
  pfCvDrop.addEventListener('dragover', e => { e.preventDefault(); pfCvDrop.classList.add('pf-cv-drop--over'); });
  pfCvDrop.addEventListener('dragleave', () => pfCvDrop.classList.remove('pf-cv-drop--over'));
  pfCvDrop.addEventListener('drop', e => {
    e.preventDefault();
    pfCvDrop.classList.remove('pf-cv-drop--over');
    const file = e.dataTransfer?.files[0];
    if (file) uploadCvFile(file);
  });
}

// ── Init ─────────────────────────────────────────────────────────────────────

loadProfile();
