(() => {
  const themeStorageKey = "portfolio-theme";
  let theme = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";

  try {
    const parsed = JSON.parse(localStorage.getItem(themeStorageKey) || "null");
    if (parsed?.mode === "manual" && (parsed.theme === "light" || parsed.theme === "dark")) {
      theme = parsed.theme;
    }
  } catch {
    // Keep system theme when localStorage has invalid JSON.
  }

  document.documentElement.setAttribute("data-theme", theme);
})();
