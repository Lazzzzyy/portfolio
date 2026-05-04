<?php
declare(strict_types=1);
require_once __DIR__ . '/config/session.php';
session_start();
$user = $_SESSION['auth_user'] ?? null;
if (!is_array($user)) { header('Location: ./index.html'); exit; }
$displayName  = htmlspecialchars((string)($user['full_name'] ?? 'User'), ENT_QUOTES, 'UTF-8');
$displayEmail = htmlspecialchars((string)($user['email'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profile — Portfolio</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@500;600;700;800&display=swap" rel="stylesheet" />
  <script src="./assets/js/theme-init.js"></script>
  <link rel="stylesheet" href="./assets/css/style.css?v=20260320-f" />
  <link rel="stylesheet" href="./assets/css/app.css?v=20260317" />
  <link rel="stylesheet" href="./assets/css/profile.css?v=1" />
</head>
<body class="dashboard-page">
  <?php include './partials/sidebar.php'; ?>
  <div class="dashboard-body">
    <?php include './partials/header.php'; ?>
    <main class="dashboard-main">
      <div class="dash-content">

        <div class="pf-page-header">
          <div>
            <h2 class="pf-page-title">Profile</h2>
            <p class="pf-page-sub">Manage your account and public-facing information</p>
          </div>
          <a href="./visitor.php" target="_blank" rel="noopener noreferrer" class="pf-preview-btn" data-tooltip="Preview public portfolio">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
              <polyline points="15 3 21 3 21 9"/>
              <line x1="10" y1="14" x2="21" y2="3"/>
            </svg>
            Preview Portfolio
          </a>
        </div>

        <!-- Account Settings -->
        <section class="pf-card" id="pfAccountCard">
          <div class="pf-card-header">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
            <h3 class="pf-card-title">Account Settings</h3>
          </div>
          <div class="pf-card-body">
            <div class="pf-fields-row">
              <div class="pf-field-group">
                <label class="pf-label" for="pfFullName">Full Name</label>
                <input class="pf-input" type="text" id="pfFullName" name="full_name" maxlength="120" placeholder="Your full name" autocomplete="name" />
              </div>
              <div class="pf-field-group">
                <label class="pf-label" for="pfEmail">Email Address</label>
                <input class="pf-input" type="email" id="pfEmail" name="email" placeholder="your@email.com" autocomplete="email" />
              </div>
            </div>
            <div class="pf-card-actions">
              <button type="button" class="pf-save-btn" id="pfSaveAccount">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                  <polyline points="17 21 17 13 7 13 7 21"/>
                  <polyline points="7 3 7 8 15 8"/>
                </svg>
                Save Account
              </button>
            </div>
          </div>
        </section>

        <!-- Visitor Profile -->
        <section class="pf-card" id="pfPublicCard">
          <div class="pf-card-header">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
            <h3 class="pf-card-title">Visitor-Facing Profile</h3>
          </div>
          <div class="pf-card-body">
            <div class="pf-field-group">
              <label class="pf-label" for="pfName">Display Name <span class="pf-hint">Shown as "Hello, I'm [Name]"</span></label>
              <input class="pf-input" type="text" id="pfName" name="name" maxlength="120" placeholder="e.g. Cristian" />
            </div>
            <div class="pf-field-group">
              <label class="pf-label" for="pfBio">Bio / About</label>
              <textarea class="pf-input pf-textarea" id="pfBio" name="bio" rows="4" maxlength="1000" placeholder="Describe yourself to visitors…"></textarea>
            </div>
            <div class="pf-field-group">
              <label class="pf-label">CV / Résumé <span class="pf-hint">Leave empty to hide the button on the visitor page</span></label>
              <div class="pf-cv-tabs" role="tablist">
                <button type="button" class="pf-cv-tab pf-cv-tab--active" id="pfCvTabLink" role="tab" aria-selected="true" aria-controls="pfCvPanelLink">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                  Link
                </button>
                <button type="button" class="pf-cv-tab" id="pfCvTabFile" role="tab" aria-selected="false" aria-controls="pfCvPanelFile">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                  Upload PDF
                </button>
              </div>
              <div id="pfCvPanelLink" role="tabpanel">
                <input class="pf-input" type="url" id="pfCvUrl" name="cv_url" placeholder="https://drive.google.com/…" />
                <p class="pf-hint pf-hint--block">Google Drive links are automatically converted to embeddable preview URLs.</p>
              </div>
              <div id="pfCvPanelFile" role="tabpanel" hidden>
                <div class="pf-cv-drop" id="pfCvDrop">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 18 15 15"/></svg>
                  <span class="pf-cv-drop-text" id="pfCvDropText">Drag &amp; drop a PDF here, or <span class="pf-cv-browse">browse</span></span>
                  <span class="pf-hint">PDF only · max 10 MB</span>
                  <input type="file" id="pfCvFile" accept=".pdf,application/pdf" aria-label="Upload CV PDF" />
                </div>
                <div class="pf-cv-upload-status" id="pfCvUploadStatus" hidden></div>
              </div>
              <button type="button" class="pf-cv-remove-btn" id="pfRemoveCv" hidden>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                  <path d="M10 11v6"></path>
                  <path d="M14 11v6"></path>
                  <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                </svg>
                Remove CV
              </button>
            </div>
            <div class="pf-card-actions">
              <button type="button" class="pf-save-btn" id="pfSavePublic">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                  <polyline points="17 21 17 13 7 13 7 21"/>
                  <polyline points="7 3 7 8 15 8"/>
                </svg>
                Save Profile
              </button>
            </div>
          </div>
        </section>

        <!-- Social Media Links -->
        <section class="pf-card" id="pfSocialCard">
          <div class="pf-card-header">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>
              <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
              <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
            </svg>
            <h3 class="pf-card-title">Social Media Links</h3>
          </div>
          <div class="pf-card-body">
            <p class="pf-section-note">Visitors can click these icons to reach you. Gmail opens a compose window with your address pre-filled.</p>
            <div class="pf-social-grid">

              <div class="pf-social-row">
                <span class="pf-social-icon pf-social-icon--gmail">
                  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 5.457v13.909c0 .904-.732 1.636-1.636 1.636h-3.819V11.73L12 16.64l-6.545-4.91v9.273H1.636A1.636 1.636 0 0 1 0 19.366V5.457c0-2.023 2.309-3.178 3.927-1.964L5.455 4.64 12 9.548l6.545-4.907 1.528-1.147C21.69 2.28 24 3.434 24 5.457z"/></svg>
                </span>
                <div class="pf-field-group pf-field-group--grow">
                  <label class="pf-label" for="pf-email">Gmail Address <span class="pf-hint">Email address only — visitors will be taken to Gmail compose</span></label>
                  <input class="pf-input" type="email" id="pf-email" name="social_email" placeholder="you@gmail.com" />
                </div>
              </div>

              <div class="pf-social-row">
                <span class="pf-social-icon pf-social-icon--github">
                  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0 1 12 6.844a9.59 9.59 0 0 1 2.504.337c1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.02 10.02 0 0 0 22 12.017C22 6.484 17.522 2 12 2z"/></svg>
                </span>
                <div class="pf-field-group pf-field-group--grow">
                  <label class="pf-label" for="pf-github">GitHub <span class="pf-hint">https://github.com/username</span></label>
                  <input class="pf-input" type="url" id="pf-github" name="social_github" placeholder="https://github.com/username" />
                </div>
              </div>

              <div class="pf-social-row">
                <span class="pf-social-icon pf-social-icon--linkedin">
                  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                </span>
                <div class="pf-field-group pf-field-group--grow">
                  <label class="pf-label" for="pf-linkedin">LinkedIn <span class="pf-hint">https://linkedin.com/in/username</span></label>
                  <input class="pf-input" type="url" id="pf-linkedin" name="social_linkedin" placeholder="https://linkedin.com/in/username" />
                </div>
              </div>

              <div class="pf-social-row">
                <span class="pf-social-icon pf-social-icon--facebook">
                  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </span>
                <div class="pf-field-group pf-field-group--grow">
                  <label class="pf-label" for="pf-facebook">Facebook <span class="pf-hint">https://facebook.com/username</span></label>
                  <input class="pf-input" type="url" id="pf-facebook" name="social_facebook" placeholder="https://facebook.com/username" />
                </div>
              </div>

              <div class="pf-social-row">
                <span class="pf-social-icon pf-social-icon--instagram">
                  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>
                </span>
                <div class="pf-field-group pf-field-group--grow">
                  <label class="pf-label" for="pf-instagram">Instagram <span class="pf-hint">https://instagram.com/username</span></label>
                  <input class="pf-input" type="url" id="pf-instagram" name="social_instagram" placeholder="https://instagram.com/username" />
                </div>
              </div>

              <div class="pf-social-row">
                <span class="pf-social-icon pf-social-icon--twitter">
                  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.747l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </span>
                <div class="pf-field-group pf-field-group--grow">
                  <label class="pf-label" for="pf-twitter">X / Twitter <span class="pf-hint">https://x.com/username</span></label>
                  <input class="pf-input" type="url" id="pf-twitter" name="social_twitter" placeholder="https://x.com/username" />
                </div>
              </div>

              <div class="pf-social-row">
                <span class="pf-social-icon pf-social-icon--telegram">
                  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                </span>
                <div class="pf-field-group pf-field-group--grow">
                  <label class="pf-label" for="pf-telegram">Telegram <span class="pf-hint">https://t.me/username</span></label>
                  <input class="pf-input" type="url" id="pf-telegram" name="social_telegram" placeholder="https://t.me/username" />
                </div>
              </div>

              <div class="pf-social-row">
                <span class="pf-social-icon pf-social-icon--discord">
                  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>
                </span>
                <div class="pf-field-group pf-field-group--grow">
                  <label class="pf-label" for="pf-discord">Discord <span class="pf-hint">https://discord.com/users/your-id</span></label>
                  <input class="pf-input" type="url" id="pf-discord" name="social_discord" placeholder="https://discord.com/users/your-id" />
                </div>
              </div>

              <div class="pf-social-row">
                <span class="pf-social-icon pf-social-icon--viber">
                  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.398.002C8.198-.035 1.895.964.592 7.101c-.544 2.268-.598 4.486-.066 6.776.758 3.29 3.576 5.483 6.858 5.99l.074.012v2.882c0 .019 0 .04.002.062.018.22.144.421.327.532.327.198.743.118.975-.187l1.903-2.434c.344.016.687.024 1.03.024 3.2.037 9.503-.962 10.806-7.099.883-4.208-.032-8.826-4.32-10.965C16.547.602 14.02-.03 11.398.002zm.061 1.72c2.33-.03 4.58.535 5.864 1.176 3.563 1.776 4.389 5.618 3.609 9.19-1.07 5.103-6.302 5.924-9.073 5.899-.381-.001-.763-.016-1.143-.047a.862.862 0 0 0-.672.255l-1.31 1.676v-2.013a.862.862 0 0 0-.731-.85c-2.876-.428-5.274-2.25-5.905-4.98-.468-2.02-.414-3.974.076-5.985C3.34 2.544 8.69 1.753 11.46 1.722z"/></svg>
                </span>
                <div class="pf-field-group pf-field-group--grow">
                  <label class="pf-label" for="pf-viber">Viber <span class="pf-hint">viber://chat?number=%2B63XXXXXXXXXX</span></label>
                  <input class="pf-input" id="pf-viber" name="social_viber" placeholder="viber://chat?number=%2B63XXXXXXXXXX" />
                </div>
              </div>

              <div class="pf-social-row">
                <span class="pf-social-icon pf-social-icon--whatsapp">
                  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                </span>
                <div class="pf-field-group pf-field-group--grow">
                  <label class="pf-label" for="pf-whatsapp">WhatsApp <span class="pf-hint">https://wa.me/63XXXXXXXXXX (no + or dashes)</span></label>
                  <input class="pf-input" type="url" id="pf-whatsapp" name="social_whatsapp" placeholder="https://wa.me/63XXXXXXXXXX" />
                </div>
              </div>

            </div>
            <div class="pf-card-actions">
              <button type="button" class="pf-save-btn" id="pfSaveSocial">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                  <polyline points="17 21 17 13 7 13 7 21"/>
                  <polyline points="7 3 7 8 15 8"/>
                </svg>
                Save Social Links
              </button>
            </div>
          </div>
        </section>

        <!-- Phone Numbers -->
        <section class="pf-card" id="pfPhoneCard">
          <div class="pf-card-header">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.27h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
            <h3 class="pf-card-title">Phone Numbers</h3>
          </div>
          <div class="pf-card-body">
            <p class="pf-section-note">Shown under the social icons in the Contact section.</p>
            <div class="pf-fields-row">
              <div class="pf-field-group">
                <label class="pf-label" for="pfPhoneDito">
                  <svg class="pf-carrier-dot" viewBox="0 0 10 10" fill="none" aria-hidden="true"><circle cx="5" cy="5" r="5" fill="#E02020"/></svg>
                  Dito
                </label>
                <input class="pf-input" type="tel" id="pfPhoneDito" name="phone_discord" placeholder="+63 XXX XXX XXXX" />
              </div>
              <div class="pf-field-group">
                <label class="pf-label" for="pfPhoneTnt">
                  <svg class="pf-carrier-dot" viewBox="0 0 10 10" fill="none" aria-hidden="true"><circle cx="5" cy="5" r="5" fill="#FF6600"/></svg>
                  TNT
                </label>
                <input class="pf-input" type="tel" id="pfPhoneTnt" name="phone_tnt" placeholder="+63 XXX XXX XXXX" />
              </div>
              <div class="pf-field-group">
                <label class="pf-label" for="pfPhoneGlobe">
                  <svg class="pf-carrier-dot" viewBox="0 0 10 10" fill="none" aria-hidden="true"><circle cx="5" cy="5" r="5" fill="#005BAA"/></svg>
                  Globe
                </label>
                <input class="pf-input" type="tel" id="pfPhoneGlobe" name="phone_globe" placeholder="+63 XXX XXX XXXX" />
              </div>
            </div>
            <div class="pf-card-actions">
              <button type="button" class="pf-save-btn" id="pfSavePhones">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                  <polyline points="17 21 17 13 7 13 7 21"/>
                  <polyline points="7 3 7 8 15 8"/>
                </svg>
                Save Phone Numbers
              </button>
            </div>
          </div>
        </section>

      </div>
    </main>
  </div>

  <!-- Remove CV Confirm Modal -->
  <div class="pf-modal-overlay pf-modal-overlay--hidden" id="pfRemoveCvOverlay" role="dialog" aria-modal="true" aria-labelledby="pfRemoveCvModalTitle">
    <div class="pf-modal">
      <div class="pf-modal-header">
        <h3 class="pf-modal-title" id="pfRemoveCvModalTitle">Remove CV</h3>
        <button type="button" class="pf-modal-close" id="pfRemoveCvCancel" aria-label="Close modal">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <p class="pf-modal-msg">Are you sure you want to remove your CV? It will be cleared from your portfolio but the file will remain on the server.</p>
      <div class="pf-modal-footer">
        <button type="button" class="pf-modal-btn pf-modal-btn--secondary" id="pfRemoveCvCancelBtn">Cancel</button>
        <button type="button" class="pf-modal-btn pf-modal-btn--danger" id="pfRemoveCvConfirmBtn">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
            <path d="M10 11v6"></path>
            <path d="M14 11v6"></path>
            <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
          </svg>
          Remove
        </button>
      </div>
    </div>
  </div>

  <div class="pf-toast" id="pfToast" aria-live="polite" aria-atomic="true"></div>

  <script src="./assets/js/theme.js"></script>
  <script src="./assets/js/sidebar.js"></script>
  <script src="./assets/js/header.js?v=20260320"></script>
  <script src="./assets/js/tooltip.js"></script>
  <script src="./assets/js/profile.js?v=1"></script>
</body>
</html>
