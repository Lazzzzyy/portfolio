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
    <title>Projects — Portfolio</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@500;600;700;800&display=swap"
      rel="stylesheet"
    />

    <script src="./assets/js/theme-init.js"></script>

    <link rel="stylesheet" href="./assets/css/style.css?v=20260320-f" />
    <link rel="stylesheet" href="./assets/css/app.css?v=20260316" />
    <link rel="stylesheet" href="./assets/css/projects.css?v=20260317" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/devicon@latest/devicon.min.css" />
  </head>
  <body class="dashboard-page">
    <?php include './partials/sidebar.php'; ?>

    <div class="dashboard-body">
      <?php include './partials/header.php'; ?>

      <main class="dashboard-main">
        <div class="dash-content">

          <div class="proj-page-header">
            <div class="proj-page-title-group">
              <h2 class="proj-page-title">Projects</h2>
              <span class="proj-page-count" id="projCount"></span>
            </div>
            <button type="button" class="proj-add-btn" id="openAddModal">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
              Add Project
            </button>
          </div>

          <div class="proj-filters">
            <div class="proj-search-wrap">
              <svg class="proj-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <input type="text" id="projSearch" class="proj-search-input" placeholder="Search projects..." />
            </div>
            <div class="proj-filter-tabs">
              <button type="button" class="proj-filter-tab proj-filter-tab--active" data-filter="all">All</button>
              <button type="button" class="proj-filter-tab" data-filter="published">Published</button>
              <button type="button" class="proj-filter-tab" data-filter="draft">Draft</button>
              <button type="button" class="proj-filter-tab" data-filter="archived">Archived</button>
            </div>
          </div>

          <div class="proj-grid" id="projGrid">
            <div class="proj-grid-state" id="projLoading">
              <svg class="proj-loading-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
              </svg>
              <span>Loading projects...</span>
            </div>
            <div class="proj-grid-state proj-grid-state--hidden" id="projEmpty">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
              </svg>
              <span>No projects found</span>
            </div>
          </div>

        </div>
      </main>
    </div>

    <!-- Add / Edit Modal -->
    <div class="proj-modal-overlay proj-modal-overlay--hidden" id="projModalOverlay" role="dialog" aria-modal="true" aria-labelledby="projModalTitle">
      <div class="proj-modal">
        <div class="proj-modal-header">
          <h3 class="proj-modal-title" id="projModalTitle">Add Project</h3>
          <button type="button" class="proj-modal-close" id="closeModal" aria-label="Close modal">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <form class="proj-modal-form" id="projForm" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="id" id="projId" value="" />

          <div class="proj-form-row">
            <div class="proj-form-group proj-form-group--grow">
              <label class="proj-form-label" for="projTitle">
                Title
                <span class="proj-form-required">*</span>
              </label>
              <input type="text" class="proj-form-input" id="projTitle" name="title" placeholder="e.g. Smart Parking System" required />
            </div>
            <div class="proj-form-group">
              <label class="proj-form-label" for="projCategory">Category</label>
              <select class="proj-form-select" id="projCategory" name="category">
                <option value="Web Systems">Web Systems</option>
                <option value="Research">Research</option>
                <option value="UI/UX">UI/UX</option>
                <option value="Mobile">Mobile</option>
              </select>
            </div>
          </div>

          <div class="proj-form-group">
            <label class="proj-form-label" for="projDesc">
              Description
              <span class="proj-form-hint">— mention technologies used for auto-detection</span>
            </label>
            <textarea class="proj-form-textarea" id="projDesc" name="description" rows="4"
              placeholder="e.g. Built with PHP, MySQL, HTML, CSS, and JavaScript. A web-based smart parking system..."></textarea>
            <span class="proj-char-count" id="projDescCount">0 / 130</span>
            <div class="proj-tech-preview" id="projTechPreview"></div>
          </div>

          <div class="proj-form-row">
            <div class="proj-form-group">
              <label class="proj-form-label" for="projGithub">GitHub URL</label>
              <input type="url" class="proj-form-input" id="projGithub" name="github_url" placeholder="https://github.com/user/repo" />
            </div>
            <div class="proj-form-group">
              <label class="proj-form-label" for="projDemo">Live Demo URL</label>
              <input type="url" class="proj-form-input" id="projDemo" name="demo_url" placeholder="https://yourproject.com" />
            </div>
          </div>

          <div class="proj-form-row">
            <div class="proj-form-group">
              <label class="proj-form-label" for="projStatus">
                Status
                <span class="proj-status-auto-hint" id="projStatusHint"></span>
              </label>
              <select class="proj-form-select" id="projStatus" name="status">
                <option value="draft">Draft</option>
                <option value="published">Published</option>
                <option value="archived">Archived</option>
              </select>
            </div>
            <div class="proj-form-group">
              <label class="proj-form-label" for="projThumbnail">Thumbnail Image</label>
              <div class="proj-file-wrap">
                <input type="file" class="proj-file-input" id="projThumbnail" name="thumbnail" accept="image/*" />
                <label for="projThumbnail" class="proj-file-label">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                  </svg>
                  <span id="projFileLabel">Choose image</span>
                </label>
              </div>
              <div class="proj-thumb-preview" id="projThumbPreview"></div>
            </div>
          </div>

          <div class="proj-form-group">
            <label class="proj-form-label" for="projTechSearch">
              Technologies
              <span class="proj-form-hint">— search &amp; select, or auto-detect from project ZIP</span>
            </label>
            <div class="proj-detect-bar">
              <label class="proj-detect-btn" for="projZipInput" id="projDetectLabel">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                  <polyline points="17 8 12 3 7 8"/>
                  <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                Upload Project ZIP
                <input type="file" id="projZipInput" accept=".zip,application/zip" aria-label="Upload project ZIP for tech detection" />
              </label>
              <span class="proj-detect-status" id="projDetectStatus" hidden></span>
            </div>
            <div class="proj-tag-input-wrap" id="projTagWrap">
              <div class="proj-tag-chips" id="projTagChips"></div>
              <input type="text" class="proj-tag-search" id="projTechSearch"
                     placeholder="Type a technology…" autocomplete="off" />
            </div>
            <div class="proj-tag-dropdown" id="projTagDropdown" hidden></div>
            <input type="hidden" id="projManualTechs" name="technologies" />
          </div>

          <div class="proj-modal-footer">
            <button type="button" class="proj-btn proj-btn--secondary" id="cancelModal">Cancel</button>
            <button type="submit" class="proj-btn proj-btn--primary" id="projSubmitBtn">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
              </svg>
              Save Project
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div class="proj-modal-overlay proj-modal-overlay--hidden" id="deleteModalOverlay" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
      <div class="proj-modal proj-modal--sm">
        <div class="proj-modal-header">
          <h3 class="proj-modal-title" id="deleteModalTitle">Remove Project</h3>
          <button type="button" class="proj-modal-close" id="closeDeleteModal" aria-label="Close modal">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        <p class="proj-delete-msg">Are you sure you want to remove <strong id="deleteProjectTitle"></strong>? It will be hidden from your portfolio but kept in the database.</p>
        <div class="proj-modal-footer">
          <button type="button" class="proj-btn proj-btn--secondary" id="cancelDelete">Cancel</button>
          <button type="button" class="proj-btn proj-btn--danger" id="confirmDelete">
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
    <script src="./assets/js/projects.js"></script>
  </body>
</html>
