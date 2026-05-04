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
$displayEmail = htmlspecialchars((string) ($user['email']    ?? ''),      ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en" data-theme="light">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Messages — Portfolio</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@500;600;700;800&display=swap"
      rel="stylesheet"
    />

    <script src="./assets/js/theme-init.js"></script>

    <link rel="stylesheet" href="./assets/css/style.css?v=20260320-f" />
    <link rel="stylesheet" href="./assets/css/app.css?v=20260316" />
    <link rel="stylesheet" href="./assets/css/messages.css?v=20260316" />
  </head>
  <body class="dashboard-page">
    <?php include './partials/sidebar.php'; ?>

    <div class="dashboard-body">
      <?php include './partials/header.php'; ?>

      <main class="dashboard-main">
        <div class="dash-content">

          <div class="msg-page-header">
            <div class="msg-page-title-group">
              <h2 class="msg-page-title">Inbox</h2>
              <span class="msg-page-count" id="msgPageCount"></span>
            </div>
          </div>

          <div class="msg-toolbar">
            <div class="msg-filter-tabs" id="msgFilterTabs">
              <button type="button" class="msg-filter-tab msg-filter-tab--active" data-status="all">
                All <span class="msg-tab-count" id="tabCountAll"></span>
              </button>
              <button type="button" class="msg-filter-tab" data-status="unread">
                Unread <span class="msg-tab-count msg-tab-count--unread" id="tabCountUnread"></span>
              </button>
              <button type="button" class="msg-filter-tab" data-status="read">
                Read <span class="msg-tab-count" id="tabCountRead"></span>
              </button>
              <button type="button" class="msg-filter-tab" data-status="archived">
                Archived <span class="msg-tab-count" id="tabCountArchived"></span>
              </button>
            </div>

            <div class="msg-search-wrap">
              <svg class="msg-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <input type="text" id="msgSearch" class="msg-search-input" placeholder="Search messages…" />
            </div>
          </div>

          <div class="msg-table-wrap">
            <table class="msg-table" id="msgTable">
              <thead>
                <tr>
                  <th class="msg-col-status">Status</th>
                  <th class="msg-col-sender">Sender</th>
                  <th class="msg-col-email">Email</th>
                  <th class="msg-col-category">Category</th>
                  <th class="msg-col-subject">Subject</th>
                  <th class="msg-col-date">Date</th>
                </tr>
              </thead>
              <tbody id="msgTableBody">
                <tr>
                  <td colspan="6" class="msg-table-state">
                    <div class="msg-table-state-inner">
                      <svg class="msg-loading-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                      </svg>
                      <span>Loading messages…</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="msg-pagination" id="msgPagination"></div>

        </div>
      </main>
    </div>

    <!-- View Message Modal -->
    <div class="msg-modal-overlay msg-modal-overlay--hidden" id="msgViewOverlay" role="dialog" aria-modal="true" aria-labelledby="msgModalTitle">
      <div class="msg-modal">
        <div class="msg-modal-header">
          <h3 class="msg-modal-title" id="msgModalTitle">Message</h3>
          <button type="button" class="msg-modal-close" id="closeMsgModal" aria-label="Close modal">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <div class="msg-modal-body" id="msgModalBody"></div>

        <div class="msg-modal-footer">
          <button type="button" class="msg-btn msg-btn--secondary" id="msgArchiveBtn">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="21 8 21 21 3 21 3 8"></polyline>
              <rect x="1" y="3" width="22" height="5"></rect>
              <line x1="10" y1="12" x2="14" y2="12"></line>
            </svg>
            Archive
          </button>
          <div class="msg-modal-footer-spacer"></div>
          <a href="#" class="msg-btn msg-btn--primary" id="msgReplyBtn" target="_blank" rel="noopener">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="9 17 4 12 9 7"></polyline>
              <path d="M20 18v-2a4 4 0 0 0-4-4H4"></path>
            </svg>
            Reply via Email
          </a>
          <button type="button" class="msg-btn msg-btn--danger" id="msgDeleteBtn">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="3 6 5 6 21 6"></polyline>
              <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
              <path d="M10 11v6M14 11v6"></path>
              <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
            </svg>
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div class="msg-modal-overlay msg-modal-overlay--hidden" id="msgDeleteOverlay" role="dialog" aria-modal="true" aria-labelledby="msgDeleteTitle">
      <div class="msg-modal msg-modal--sm">
        <div class="msg-modal-header">
          <h3 class="msg-modal-title" id="msgDeleteTitle">Remove Message</h3>
          <button type="button" class="msg-modal-close" id="closeDeleteModal" aria-label="Close">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        <p class="msg-delete-msg">Are you sure you want to remove this message? It will be hidden from your inbox but kept in the database.</p>
        <div class="msg-modal-footer">
          <button type="button" class="msg-btn msg-btn--secondary" id="cancelDeleteMsg">Cancel</button>
          <div class="msg-modal-footer-spacer"></div>
          <button type="button" class="msg-btn msg-btn--danger" id="confirmDeleteMsg">
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
    <script src="./assets/js/messages.js"></script>
  </body>
</html>
