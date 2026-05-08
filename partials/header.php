    <header class="dashboard-header">
      <div class="app-header-shell">
        <div class="app-header-spacer" aria-hidden="true"></div>

        <div class="app-header-brand">
          <span class="app-header-logo-badge">
            <img src="./assets/images/logo.png" class="app-header-logo-image" alt="Logo" />
          </span>
        </div>

        <div class="app-header-actions">
          <div class="notif-dropdown" id="notifDropdown">
            <button type="button" class="header-icon-btn" id="notifBtn" aria-label="Notifications" aria-haspopup="true" aria-expanded="false" data-tooltip="Notifications">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path d="M12 4.1a4 4 0 0 0-4 4v2.5c0 .8-.3 1.5-.8 2l-1.4 1.7h12.4l-1.4-1.7a3.2 3.2 0 0 1-.8-2V8a4 4 0 0 0-4-3.9Z"></path>
                <path d="M9.3 16.2a2.7 2.7 0 0 0 5.4 0"></path>
              </svg>
            </button>

            <div class="notif-dropdown__menu" id="notifMenu" role="menu" aria-label="Notifications">
              <div class="notif-dropdown__header">
                <span class="notif-dropdown__title">Notifications</span>
              </div>
              <div class="notif-dropdown__body">
                <div class="notif-dropdown__empty">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                  </svg>
                  <span>No new notifications</span>
                </div>
              </div>
            </div>
          </div>

          <div class="settings-dropdown" id="settingsDropdown">
            <button type="button" class="header-icon-btn" id="settingsBtn" aria-label="Settings" aria-haspopup="true" aria-expanded="false" data-tooltip="Settings">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 26 26" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
              </svg>
            </button>

            <div class="settings-dropdown__menu" id="settingsMenu" role="menu" aria-label="Settings menu">
              <a href="./profile" class="settings-dropdown__item" role="menuitem">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Profile
              </a>
              <button type="button" class="settings-dropdown__item" id="logoutBtn" role="menuitem">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                  <polyline points="16 17 21 12 16 7"></polyline>
                  <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Logout
              </button>
            </div>
          </div>
        </div>
      </div>
    </header>

    <div class="logout-modal" id="logoutModal" role="dialog" aria-modal="true" aria-labelledby="logoutModalTitle" aria-hidden="true">
      <div class="logout-modal__backdrop" id="logoutModalBackdrop"></div>
      <div class="logout-modal__dialog">
        <div class="logout-modal__icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <polyline points="16 17 21 12 16 7"></polyline>
            <line x1="21" y1="12" x2="9" y2="12"></line>
          </svg>
        </div>
        <h2 class="logout-modal__title" id="logoutModalTitle">Log out?</h2>
        <p class="logout-modal__message">Are you sure you want to log out of your account?</p>
        <div class="logout-modal__actions">
          <button type="button" class="logout-modal__btn logout-modal__btn--cancel" id="logoutCancelBtn">Cancel</button>
          <a href="./logout" class="logout-modal__btn logout-modal__btn--confirm">Log Out</a>
        </div>
      </div>
    </div>
