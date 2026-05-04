<?php $currentPage = basename($_SERVER['SCRIPT_FILENAME'] ?? '', '.php'); ?>
    <aside class="app-sidebar" id="appSidebar">
      <div class="sidebar-inner">
        <nav class="sidebar-nav" aria-label="Main navigation">
          <a href="./dashboard.php" class="sidebar-nav-item <?= $currentPage === 'dashboard' ? 'sidebar-nav-item--active' : '' ?>" data-tooltip="Dashboard">
            <svg class="sidebar-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <rect x="3" y="3" width="7" height="7" rx="1"></rect>
              <rect x="14" y="3" width="7" height="7" rx="1"></rect>
              <rect x="3" y="14" width="7" height="7" rx="1"></rect>
              <rect x="14" y="14" width="7" height="7" rx="1"></rect>
            </svg>
            <span class="sidebar-nav-label">Dashboard</span>
          </a>

          <a href="./projects.php" class="sidebar-nav-item <?= $currentPage === 'projects' ? 'sidebar-nav-item--active' : '' ?>" data-tooltip="Projects">
            <svg class="sidebar-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
            </svg>
            <span class="sidebar-nav-label">Projects</span>
          </a>

          <a href="./blog.php" class="sidebar-nav-item <?= $currentPage === 'blog' ? 'sidebar-nav-item--active' : '' ?>" data-tooltip="Blog Posts">
            <svg class="sidebar-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="16" y1="13" x2="8" y2="13"></line>
              <line x1="16" y1="17" x2="8" y2="17"></line>
              <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            <span class="sidebar-nav-label">Blog Posts</span>
          </a>

          <a href="./awards.php" class="sidebar-nav-item <?= $currentPage === 'awards' ? 'sidebar-nav-item--active' : '' ?>" data-tooltip="Awards">
            <svg class="sidebar-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="12" cy="8" r="6"></circle>
              <path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"></path>
            </svg>
            <span class="sidebar-nav-label">Awards</span>
          </a>

          <a href="./messages.php" class="sidebar-nav-item <?= $currentPage === 'messages' ? 'sidebar-nav-item--active' : '' ?>" data-tooltip="Messages">
            <svg class="sidebar-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
            <span class="sidebar-nav-label">Messages</span>
          </a>

          <a href="./analytics.php" class="sidebar-nav-item <?= $currentPage === 'analytics' ? 'sidebar-nav-item--active' : '' ?>" data-tooltip="Analytics">
            <svg class="sidebar-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <line x1="18" y1="20" x2="18" y2="10"></line>
              <line x1="12" y1="20" x2="12" y2="4"></line>
              <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
            <span class="sidebar-nav-label">Analytics</span>
          </a>

        </nav>
      </div>

      <button class="sidebar-collapse-btn" id="sidebarToggle" type="button" aria-label="Toggle sidebar" data-tooltip="Collapse sidebar">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
      </button>
    </aside>
