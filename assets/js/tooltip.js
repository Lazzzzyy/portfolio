(() => {
  let tip = null;
  let pending = false;

  const create = () => {
    const el = document.createElement("div");
    el.className = "js-tooltip";
    el.setAttribute("role", "tooltip");
    el.setAttribute("aria-hidden", "true");
    document.body.appendChild(el);
    return el;
  };

  const hide = () => {
    pending = false;
    if (tip) tip.style.opacity = "0";
  };

  const show = (target) => {
    const text = target.getAttribute("data-tooltip");
    if (!text) return;

    const sidebar = target.closest(".app-sidebar");
    if (
      sidebar &&
      !sidebar.classList.contains("sidebar--collapsed") &&
      target.classList.contains("sidebar-nav-item")
    ) {
      return;
    }

    if (!tip) tip = create();
    tip.textContent = text;
    tip.style.display = "block";
    tip.style.opacity = "0";
    tip.style.left = "0px";
    tip.style.top = "0px";

    pending = true;
    requestAnimationFrame(() => {
      if (!pending) return;
      const rect = target.getBoundingClientRect();
      const tipRect = tip.getBoundingClientRect();

      let x, y;
      if (sidebar) {
        x = rect.right + 10;
        y = rect.top + rect.height / 2 - tipRect.height / 2;
      } else {
        x = rect.left + rect.width / 2 - tipRect.width / 2;
        y = rect.bottom + 8;
      }

      x = Math.max(4, Math.min(x, window.innerWidth - tipRect.width - 4));
      y = Math.max(4, Math.min(y, window.innerHeight - tipRect.height - 4));

      tip.style.left = `${x}px`;
      tip.style.top = `${y}px`;
      tip.style.opacity = "1";
    });
  };

  document.addEventListener("mouseover", (e) => {
    const target = e.target.closest("[data-tooltip]");
    if (target) {
      show(target);
    } else {
      hide();
    }
  });

  document.addEventListener("mouseout", (e) => {
    if (!e.relatedTarget?.closest("[data-tooltip]")) {
      hide();
    }
  });
})();
