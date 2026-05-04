(() => {
  const settingsBtn = document.getElementById("settingsBtn");
  const settingsMenu = document.getElementById("settingsMenu");
  const notifBtn = document.getElementById("notifBtn");
  const notifMenu = document.getElementById("notifMenu");
  const logoutModal = document.getElementById("logoutModal");
  const logoutCancelBtn = document.getElementById("logoutCancelBtn");
  const logoutModalBackdrop = document.getElementById("logoutModalBackdrop");

  const openLogoutModal = () => {
    if (!logoutModal) return;
    logoutModal.classList.add("logout-modal--open");
    logoutModal.setAttribute("aria-hidden", "false");
    if (logoutCancelBtn) logoutCancelBtn.focus();
  };

  const closeLogoutModal = () => {
    if (!logoutModal) return;
    logoutModal.classList.remove("logout-modal--open");
    logoutModal.setAttribute("aria-hidden", "true");
  };

  const makeDropdown = (btn, menu, openClass) => {
    if (!btn || !menu) return null;

    document.body.appendChild(menu);

    const positionMenu = () => {
      const rect = btn.getBoundingClientRect();
      menu.style.top = `${rect.bottom + 6}px`;
      menu.style.right = `${window.innerWidth - rect.right}px`;
    };

    const isOpen = () => menu.classList.contains(openClass);

    const open = () => {
      positionMenu();
      menu.classList.add(openClass);
      btn.setAttribute("aria-expanded", "true");
    };

    const close = () => {
      menu.classList.remove(openClass);
      btn.setAttribute("aria-expanded", "false");
    };

    window.addEventListener("resize", () => {
      if (isOpen()) positionMenu();
    });

    return { btn, menu, isOpen, open, close };
  };

  const settings = makeDropdown(settingsBtn, settingsMenu, "settings-dropdown__menu--open");
  const notif = makeDropdown(notifBtn, notifMenu, "notif-dropdown__menu--open");

  const logoutBtn = settingsMenu ? settingsMenu.querySelector("#logoutBtn") : null;
  if (logoutBtn) {
    logoutBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      if (settings) settings.close();
      openLogoutModal();
    });
  }

  if (settings) {
    settingsBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      if (settings.isOpen()) {
        settings.close();
      } else {
        if (notif) notif.close();
        settings.open();
      }
    });
  }

  if (notif) {
    notifBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      if (notif.isOpen()) {
        notif.close();
      } else {
        if (settings) settings.close();
        notif.open();
      }
    });
  }

  document.addEventListener("click", (e) => {
    if (settings && !settingsBtn.contains(e.target) && !settingsMenu.contains(e.target)) {
      settings.close();
    }
    if (notif && !notifBtn.contains(e.target) && !notifMenu.contains(e.target)) {
      notif.close();
    }
    if (logoutModal && !logoutModal.contains(e.target)) {
      closeLogoutModal();
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      if (settings && settings.isOpen()) { settings.close(); settingsBtn.focus(); }
      if (notif && notif.isOpen()) { notif.close(); notifBtn.focus(); }
      closeLogoutModal();
    }
  });

  if (logoutCancelBtn) {
    logoutCancelBtn.addEventListener("click", closeLogoutModal);
  }

  if (logoutModalBackdrop) {
    logoutModalBackdrop.addEventListener("click", closeLogoutModal);
  }
})();
