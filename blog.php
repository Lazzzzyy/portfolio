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
    <title>Blog Posts — Portfolio</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@500;600;700;800&display=swap"
      rel="stylesheet"
    />

    <script src="./assets/js/theme-init.js"></script>

    <link rel="stylesheet" href="./assets/css/style.css?v=20260320-f" />
    <link rel="stylesheet" href="./assets/css/app.css?v=20260316" />
    <link rel="stylesheet" href="./assets/css/blog.css?v=20260316" />
  </head>
  <body class="dashboard-page">
    <?php include './partials/sidebar.php'; ?>

    <div class="dashboard-body">
      <?php include './partials/header.php'; ?>

      <main class="dashboard-main">
        <div class="dash-content">

          <div class="blog-page-header">
            <div class="blog-page-title-group">
              <h2 class="blog-page-title">Blog Posts</h2>
              <span class="blog-page-count" id="blogCount"></span>
            </div>
            <button type="button" class="blog-add-btn" id="openAddModal">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
              New Post
            </button>
          </div>

          <div class="blog-filters">
            <div class="blog-search-wrap">
              <svg class="blog-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <input type="text" id="blogSearch" class="blog-search-input" placeholder="Search posts..." />
            </div>
            <div class="blog-filter-tabs">
              <button type="button" class="blog-filter-tab blog-filter-tab--active" data-filter="all">All</button>
              <button type="button" class="blog-filter-tab" data-filter="published">Published</button>
              <button type="button" class="blog-filter-tab" data-filter="draft">Draft</button>
              <button type="button" class="blog-filter-tab" data-filter="archived">Archived</button>
            </div>
          </div>

          <div class="blog-grid" id="blogGrid">
            <div class="blog-grid-state" id="blogLoading">
              <svg class="blog-loading-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
              </svg>
              <span>Loading posts...</span>
            </div>
            <div class="blog-grid-state blog-grid-state--hidden" id="blogEmpty">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
              </svg>
              <span>No posts found</span>
            </div>
          </div>

        </div>
      </main>
    </div>

    <!-- Add / Edit Modal -->
    <div class="blog-modal-overlay blog-modal-overlay--hidden" id="blogModalOverlay" role="dialog" aria-modal="true" aria-labelledby="blogModalTitle">
      <div class="blog-modal">
        <div class="blog-modal-header">
          <h3 class="blog-modal-title" id="blogModalTitle">New Post</h3>
          <button type="button" class="blog-modal-close" id="closeModal" aria-label="Close modal">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <form class="blog-modal-form" id="blogForm" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="id" id="blogId" value="" />

          <div class="blog-form-row">
            <div class="blog-form-group blog-form-group--grow">
              <label class="blog-form-label" for="blogTitle">
                Title <span class="blog-form-required">*</span>
              </label>
              <input type="text" class="blog-form-input" id="blogTitle" name="title" placeholder="e.g. How I Built the Smart Parking System" required />
            </div>
            <div class="blog-form-group">
              <label class="blog-form-label" for="blogCategory">Category</label>
              <select class="blog-form-select" id="blogCategory" name="category">
                <option value="General">General</option>
                <option value="Tutorial">Tutorial</option>
                <option value="Case Study">Case Study</option>
                <option value="Project Showcase">Project Showcase</option>
                <option value="Tips &amp; Tricks">Tips &amp; Tricks</option>
                <option value="Research">Research</option>
                <option value="News">News</option>
              </select>
            </div>
          </div>

          <div class="blog-form-group">
            <label class="blog-form-label" for="blogSlug">
              Slug
              <span class="blog-form-hint">— auto-generated from title, editable</span>
            </label>
            <div class="blog-slug-wrap">
              <span class="blog-slug-prefix">post/</span>
              <input type="text" class="blog-form-input blog-slug-input" id="blogSlug" name="slug" placeholder="auto-generated" />
            </div>
          </div>

          <div class="blog-form-group">
            <label class="blog-form-label" for="blogContent">Content</label>
            <textarea class="blog-form-textarea" id="blogContent" name="content" rows="8"
              placeholder="Write your full blog post here..."></textarea>
          </div>

          <div class="blog-form-group">
            <label class="blog-form-label">Status</label>
            <input type="hidden" name="status" id="blogStatus" value="draft" />
            <div class="blog-status-auto" id="blogStatusBadge">
              <span class="blog-status-auto-icon" id="blogStatusIcon"></span>
              <span class="blog-status-auto-label" id="blogStatusLabel">Draft</span>
              <span class="blog-status-auto-hint">— auto-detected from content</span>
            </div>
          </div>

          <div class="blog-form-group">
            <label class="blog-form-label" for="blogProject">Linked Project</label>
            <select class="blog-form-select" id="blogProject" name="project_id">
              <option value="">— None —</option>
            </select>
          </div>

          <div class="blog-form-group">
            <label class="blog-form-label" for="blogThumbnail">Cover Image</label>
            <div class="blog-file-wrap">
              <input type="file" class="blog-file-input" id="blogThumbnail" name="thumbnail" accept="image/*" />
              <label for="blogThumbnail" class="blog-file-label">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                  <polyline points="17 8 12 3 7 8"></polyline>
                  <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                <span id="blogFileLabel">Choose cover image</span>
              </label>
            </div>
            <div class="blog-thumb-preview" id="blogThumbPreview"></div>
          </div>

          <div class="blog-modal-footer">
            <button type="button" class="blog-btn blog-btn--secondary" id="cancelModal">Cancel</button>
            <button type="submit" class="blog-btn blog-btn--primary" id="blogSubmitBtn">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
              </svg>
              Save Post
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div class="blog-modal-overlay blog-modal-overlay--hidden" id="deleteModalOverlay" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
      <div class="blog-modal blog-modal--sm">
        <div class="blog-modal-header">
          <h3 class="blog-modal-title" id="deleteModalTitle">Remove Post</h3>
          <button type="button" class="blog-modal-close" id="closeDeleteModal" aria-label="Close">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        <p class="blog-delete-msg">Are you sure you want to remove <strong id="deletePostTitle"></strong>? It will be hidden from your list but kept in the database.</p>
        <div class="blog-modal-footer">
          <button type="button" class="blog-btn blog-btn--secondary" id="cancelDelete">Cancel</button>
          <button type="button" class="blog-btn blog-btn--danger" id="confirmDelete">
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
    <script src="./assets/js/blog.js"></script>
  </body>
</html>
