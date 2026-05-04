(() => {
  const sidebar = document.getElementById("appSidebar");
  const toggleBtn = document.getElementById("sidebarToggle");
  const storageKey = "portfolio-sidebar";

  const applyCollapsed = (collapsed) => {
    sidebar.classList.toggle("sidebar--collapsed", collapsed);
    toggleBtn?.setAttribute("data-tooltip", collapsed ? "Expand sidebar" : "Collapse sidebar");
  };

  try {
    const saved = localStorage.getItem(storageKey);
    if (saved === "collapsed") applyCollapsed(true);
  } catch {}

  toggleBtn?.addEventListener("click", () => {
    const next = !sidebar.classList.contains("sidebar--collapsed");
    applyCollapsed(next);
    try {
      localStorage.setItem(storageKey, next ? "collapsed" : "expanded");
    } catch {}
  });

})();
