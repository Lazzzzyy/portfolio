(() => {
  const root = document.documentElement;
  const themeStorageKey = "portfolio-theme";
  const systemThemeQuery = window.matchMedia("(prefers-color-scheme: dark)");

  const isValidTheme = (theme) => theme === "light" || theme === "dark";
  const getSystemTheme = () => (systemThemeQuery.matches ? "dark" : "light");

  const readThemeSettings = () => {
    const rawSettings = localStorage.getItem(themeStorageKey);

    if (!rawSettings) {
      return { mode: "system" };
    }

    try {
      const parsed = JSON.parse(rawSettings);
      if (parsed?.mode === "manual" && isValidTheme(parsed.theme)) {
        return { mode: "manual", theme: parsed.theme };
      }

      if (parsed?.mode === "system") {
        return { mode: "system" };
      }
    } catch {
      localStorage.removeItem(themeStorageKey);
    }

    return { mode: "system" };
  };

  const saveThemeSettings = (settings) => {
    localStorage.setItem(themeStorageKey, JSON.stringify(settings));
  };

  const resolveTheme = (settings) => (settings.mode === "manual" ? settings.theme : getSystemTheme());

  let themeSettings = readThemeSettings();
  root.setAttribute("data-theme", resolveTheme(themeSettings));

  const applySystemThemeChange = (event) => {
    if (themeSettings.mode !== "system") {
      themeSettings = { mode: "system" };
      saveThemeSettings(themeSettings);
    }

    root.setAttribute("data-theme", event.matches ? "dark" : "light");
  };

  if (typeof systemThemeQuery.addEventListener === "function") {
    systemThemeQuery.addEventListener("change", applySystemThemeChange);
  } else if (typeof systemThemeQuery.addListener === "function") {
    systemThemeQuery.addListener(applySystemThemeChange);
  }

  window.addEventListener("storage", (event) => {
    if (event.key !== themeStorageKey) {
      return;
    }

    themeSettings = readThemeSettings();
    const resolved = resolveTheme(themeSettings);
    root.setAttribute("data-theme", resolved);
    syncToggleIcons(resolved);
  });

  const syncToggleIcons = (theme) => {
    const sun  = document.getElementById("iconSun");
    const moon = document.getElementById("iconMoon");
    if (!sun || !moon) return;
    sun.style.display  = theme === "dark"  ? "block" : "none";
    moon.style.display = theme === "light" ? "block" : "none";
  };

  const btn = document.getElementById("themeToggle");
  if (btn) {
    syncToggleIcons(resolveTheme(themeSettings));
    btn.addEventListener("click", () => {
      const next = root.getAttribute("data-theme") === "dark" ? "light" : "dark";
      themeSettings = { mode: "manual", theme: next };
      saveThemeSettings(themeSettings);
      root.setAttribute("data-theme", next);
      syncToggleIcons(next);
    });
  }
})();
