(() => {
  const cards = document.querySelectorAll(".dash-stat-card[data-section]");
  const panels = document.querySelectorAll(".dash-chart-panel");
  const recents = document.getElementById("dash-recents");
  let current = null;

  const playEnter = (el) => {
    el.classList.remove("dash-panel-enter");
    void el.offsetWidth;
    el.classList.add("dash-panel-enter");
  };

  const showRecents = () => {
    cards.forEach((c) => c.classList.remove("dash-stat-card--active"));
    panels.forEach((p) => p.classList.add("dash-chart-panel--hidden"));
    if (recents) {
      recents.classList.remove("dash-chart-panel--hidden");
      playEnter(recents);
    }
    current = null;
  };

  const showSection = (sectionId) => {
    if (recents) recents.classList.add("dash-chart-panel--hidden");
    cards.forEach((c) => {
      c.classList.toggle("dash-stat-card--active", c.dataset.section === sectionId);
    });
    panels.forEach((p) => {
      const show = p.id === `panel-${sectionId}`;
      p.classList.toggle("dash-chart-panel--hidden", !show);
      if (show) playEnter(p);
    });
    current = sectionId;
    window.DashCharts?.setActiveSection(sectionId);
    if (sectionId === "skills") window.DashSkills?.init();
  };

  cards.forEach((card) => {
    card.addEventListener("click", () => {
      if (current === card.dataset.section) {
        showRecents();
      } else {
        showSection(card.dataset.section);
      }
    });
  });

  showRecents();
})();
