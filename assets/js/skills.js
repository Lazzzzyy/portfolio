window.DashSkills = (() => {
  const init = () => {
    const bars = document.querySelectorAll(".skill-progress-bar[data-pct]");

    bars.forEach((bar) => {
      bar.style.width = "0%";
    });

    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        bars.forEach((bar) => {
          bar.style.width = bar.dataset.pct + "%";
        });
      });
    });
  };

  return { init };
})();
