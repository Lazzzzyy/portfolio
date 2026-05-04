<?php

declare(strict_types=1);

require_once __DIR__ . '/config/session.php';
session_start();

$user = $_SESSION['auth_user'] ?? null;

if (!is_array($user)) {
    header('Location: ./index.html');
    exit;
}

$displayName  = htmlspecialchars((string) ($user['full_name'] ?? 'User'), ENT_QUOTES, 'UTF-8');
$displayEmail = htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en" data-theme="light">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Awards — Portfolio</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@500;600;700;800&display=swap"
      rel="stylesheet"
    />

    <script src="./assets/js/theme-init.js"></script>

    <link rel="stylesheet" href="./assets/css/style.css?v=20260320-f" />
    <link rel="stylesheet" href="./assets/css/app.css?v=20260316" />
    <link rel="stylesheet" href="./assets/css/awards.css?v=20260317" />
  </head>
  <body class="dashboard-page">
    <?php include './partials/sidebar.php'; ?>

    <div class="dashboard-body">
      <?php include './partials/header.php'; ?>

      <main class="dashboard-main">
        <div class="dash-content">

          <div class="aw-page-header">
            <div class="aw-page-title-group">
              <h2 class="aw-page-title">Awards &amp; Certificates</h2>
              <span class="aw-page-count" id="awCount"></span>
            </div>
            <button type="button" class="aw-add-btn" id="openAddModal">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
              Add Award
            </button>
          </div>

          <div class="aw-search-wrap">
            <svg class="aw-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="awSearch" class="aw-search-input" placeholder="Search awards..." />
          </div>

          <div class="aw-grid" id="awGrid">
            <div class="aw-grid-state" id="awLoading">
              <svg class="aw-loading-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
              </svg>
              <span>Loading awards...</span>
            </div>
            <div class="aw-grid-state aw-grid-state--hidden" id="awEmpty">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="8" r="6"></circle>
                <path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"></path>
              </svg>
              <span>No awards yet</span>
            </div>
          </div>

        </div>
      </main>
    </div>

    <!-- Add / Edit Modal -->
    <div class="aw-modal-overlay aw-modal-overlay--hidden" id="awModalOverlay" role="dialog" aria-modal="true" aria-labelledby="awModalTitle">
      <div class="aw-modal">
        <div class="aw-modal-header">
          <h3 class="aw-modal-title" id="awModalTitle">Add Award</h3>
          <button type="button" class="aw-modal-close" id="closeModal" aria-label="Close modal">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <form class="aw-modal-form" id="awForm" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="id" id="awId" value="" />

          <div class="aw-form-row">
            <div class="aw-form-group aw-form-group--grow">
              <label class="aw-form-label" for="awTitle">
                Award Title <span class="aw-form-required">*</span>
              </label>
              <input type="text" class="aw-form-input" id="awTitle" name="title" placeholder="e.g. Best Web Application" required />
            </div>
            <div class="aw-form-group">
              <label class="aw-form-label" for="awYear">Year</label>
              <input type="number" class="aw-form-input" id="awYear" name="award_year"
                min="1990" max="2030" placeholder="2024" />
            </div>
          </div>

          <div class="aw-form-group">
            <label class="aw-form-label" for="awOrg">
              Organization / Issuer <span class="aw-form-required">*</span>
            </label>
            <input type="text" class="aw-form-input" id="awOrg" name="organization"
              placeholder="e.g. Association for Computing Machinery" required />
          </div>

          <div class="aw-form-group">
            <label class="aw-form-label" for="awDesc">Description</label>
            <textarea class="aw-form-textarea" id="awDesc" name="description" rows="3"
              placeholder="Brief description of this award or certificate..."></textarea>
          </div>

          <div class="aw-form-group">
            <label class="aw-form-label" for="awImage">Certificate / Badge Image</label>
            <div class="aw-file-wrap">
              <input type="file" class="aw-file-input" id="awImage" name="image" accept="image/*" />
              <label for="awImage" class="aw-file-label">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                  <polyline points="17 8 12 3 7 8"></polyline>
                  <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                <span id="awFileLabel">Choose image</span>
              </label>
            </div>
            <div class="aw-thumb-preview" id="awThumbPreview"></div>
          </div>

          <div class="aw-modal-footer">
            <button type="button" class="aw-btn aw-btn--secondary" id="cancelModal">Cancel</button>
            <button type="submit" class="aw-btn aw-btn--primary" id="awSubmitBtn">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
              </svg>
              Save Award
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div class="aw-modal-overlay aw-modal-overlay--hidden" id="deleteModalOverlay" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
      <div class="aw-modal aw-modal--sm">
        <div class="aw-modal-header">
          <h3 class="aw-modal-title" id="deleteModalTitle">Remove Award</h3>
          <button type="button" class="aw-modal-close" id="closeDeleteModal" aria-label="Close">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        <p class="aw-delete-msg">Are you sure you want to remove <strong id="deleteAwTitle"></strong>? It will be hidden from your list but kept in the database.</p>
        <div class="aw-modal-footer">
          <button type="button" class="aw-btn aw-btn--secondary" id="cancelDelete">Cancel</button>
          <button type="button" class="aw-btn aw-btn--danger" id="confirmDelete">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="3 6 5 6 21 6"></polyline>
              <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
              <path d="M10 11v6M14 11v6"></path>
              <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
            </svg>
            Remove
          </button>
        </div>
      </div>
    </div>

    <script src="./assets/js/theme.js"></script>
    <script src="./assets/js/sidebar.js"></script>
    <script src="./assets/js/header.js?v=20260320"></script>
    <script src="./assets/js/tooltip.js"></script>
    <script src="./assets/js/awards.js"></script>
  </body>
</html>
