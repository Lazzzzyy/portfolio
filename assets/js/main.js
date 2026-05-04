const root = document.documentElement;
const themeToggle = document.getElementById("themeToggle");
const iconThemeSun = document.getElementById("iconThemeSun");
const iconThemeMoon = document.getElementById("iconThemeMoon");
const formNote = document.getElementById("formNote");
const loginForm = document.getElementById("loginForm");
const submitButton = loginForm.querySelector('button[type="submit"]');

const emailInput = document.getElementById("email");
const emailField = document.getElementById("emailField");
const emailError = document.getElementById("emailError");

const passwordInput = document.getElementById("password");
const passwordField = document.getElementById("passwordField");
const passwordError = document.getElementById("passwordError");
const passwordToggle = document.getElementById("passwordToggle");
const iconEye = document.getElementById("iconEye");
const iconEyeOff = document.getElementById("iconEyeOff");
const logoImage = document.getElementById("logoImage");
const logoFallback = document.getElementById("logoFallback");
const profileImage = document.getElementById("profileImage");
const profileFallback = document.getElementById("profileFallback");
const credentialsStep = document.getElementById("credentialsStep");
const otpStep = document.getElementById("otpStep");
const otpEmailHint = document.getElementById("otpEmailHint");
const resendOtpButton = document.getElementById("resendOtpButton");
const changeCredentialsButton = document.getElementById("changeCredentialsButton");
const otpInputs = Array.from(document.querySelectorAll(".otp-input"));
const loadingModal = document.getElementById("loadingModal");
const loadingMessage = document.getElementById("loadingMessage");

const fields = [
  {
    input: emailInput,
    wrapper: emailField,
    error: emailError,
    requiredMessage: "Enter an email",
    formatMessage: "Enter a valid email",
  },
  {
    input: passwordInput,
    wrapper: passwordField,
    error: passwordError,
    requiredMessage: "Enter a password",
  },
];

const LOGIN_API_URL = "./api/authenticate.php";
const VERIFY_OTP_API_URL = "./api/verify-otp.php";
const RESEND_OTP_API_URL = "./api/resend-otp.php";
const OTP_LENGTH = 6;
const OTP_DEFAULT_EXPIRY_SECONDS = 60;

let authStep = "credentials";
let otpSecondsRemaining = 0;
let otpCountdownTimerId = null;
let otpRequestInFlight = false;
let activeLoadingRequests = 0;

const setFormNote = (message, isError = false) => {
  formNote.textContent = message;
  formNote.classList.toggle("form-note--error", isError);
};

const showLoadingModal = (message = "Processing your request...") => {
  if (!loadingModal) {
    return;
  }

  activeLoadingRequests += 1;
  loadingModal.classList.remove("hidden");
  loadingModal.setAttribute("aria-hidden", "false");

  if (loadingMessage) {
    loadingMessage.textContent = message;
  }
};

const hideLoadingModal = () => {
  if (!loadingModal) {
    return;
  }

  activeLoadingRequests = Math.max(0, activeLoadingRequests - 1);

  if (activeLoadingRequests > 0) {
    return;
  }

  loadingModal.classList.add("hidden");
  loadingModal.setAttribute("aria-hidden", "true");

  if (loadingMessage) {
    loadingMessage.textContent = "Processing your request...";
  }
};

const setOtpInputsDisabled = (disabled) => {
  otpInputs.forEach((input) => {
    input.disabled = disabled;
  });
};

const clearOtpInputs = () => {
  otpInputs.forEach((input) => {
    input.value = "";
    input.classList.remove("otp-invalid");
  });
};

const getOtpCode = () => otpInputs.map((input) => input.value).join("");

const focusOtpIndex = (index) => {
  const targetInput = otpInputs[index];
  if (targetInput) {
    targetInput.focus();
    targetInput.select();
  }
};

const stopOtpCountdown = () => {
  if (otpCountdownTimerId !== null) {
    window.clearInterval(otpCountdownTimerId);
    otpCountdownTimerId = null;
  }
};

const renderResendButton = () => {
  if (!resendOtpButton) {
    return;
  }

  if (otpRequestInFlight) {
    resendOtpButton.disabled = true;
    resendOtpButton.textContent = "Sending...";
    return;
  }

  if (otpSecondsRemaining > 0) {
    resendOtpButton.disabled = true;
    resendOtpButton.textContent = `Resend in ${otpSecondsRemaining}s`;
    return;
  }

  resendOtpButton.disabled = false;
  resendOtpButton.textContent = "Resend code";
};

const startOtpCountdown = (seconds = OTP_DEFAULT_EXPIRY_SECONDS) => {
  stopOtpCountdown();

  const normalizedSeconds = Number.parseInt(String(seconds), 10);
  otpSecondsRemaining = Number.isFinite(normalizedSeconds)
    ? Math.max(0, normalizedSeconds)
    : OTP_DEFAULT_EXPIRY_SECONDS;

  renderResendButton();

  if (otpSecondsRemaining <= 0) {
    return;
  }

  otpCountdownTimerId = window.setInterval(() => {
    otpSecondsRemaining = Math.max(0, otpSecondsRemaining - 1);
    renderResendButton();

    if (otpSecondsRemaining <= 0) {
      stopOtpCountdown();
    }
  }, 1000);
};

const setAuthStep = (step) => {
  authStep = step;
  const isOtpStep = step === "otp";

  if (credentialsStep) {
    credentialsStep.classList.toggle("hidden", isOtpStep);
  }

  if (otpStep) {
    otpStep.classList.toggle("hidden", !isOtpStep);
  }

  if (submitButton) {
    submitButton.disabled = false;
    submitButton.classList.remove("opacity-70", "cursor-not-allowed");
  }
};

const goToCredentialsStep = () => {
  setAuthStep("credentials");
  stopOtpCountdown();
  otpRequestInFlight = false;
  clearOtpInputs();
  setOtpInputsDisabled(false);
  renderResendButton();
  setFormNote("");
};

const goToOtpStep = (emailHint, expiresInSeconds = OTP_DEFAULT_EXPIRY_SECONDS) => {
  setAuthStep("otp");
  clearOtpInputs();
  setOtpInputsDisabled(false);

  if (otpEmailHint) {
    otpEmailHint.textContent = emailHint || emailInput.value.trim();
  }

  startOtpCountdown(expiresInSeconds);
  setFormNote("OTP sent. Enter the code to continue.");
  focusOtpIndex(0);
};

const verifyOtpCode = async () => {
  const otpCode = getOtpCode();
  if (otpCode.length !== OTP_LENGTH || otpRequestInFlight) {
    return;
  }

  otpRequestInFlight = true;
  setOtpInputsDisabled(true);
  renderResendButton();
  setFormNote("Verifying OTP...");
  showLoadingModal("Verifying OTP...");

  let verified = false;

  try {
    const response = await fetch(VERIFY_OTP_API_URL, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },
      body: new URLSearchParams({ otp: otpCode }).toString(),
    });

    const data = await response.json().catch(() => ({}));
    const isSuccess = response.ok && data.success === true;

    if (!isSuccess) {
      setFormNote(data.message || "Invalid or expired OTP.", true);
      clearOtpInputs();

      if (typeof data.seconds_remaining === "number" && data.seconds_remaining > 0) {
        startOtpCountdown(data.seconds_remaining);
      }

      setOtpInputsDisabled(false);
      focusOtpIndex(0);
      return;
    }

    verified = true;
    stopOtpCountdown();
    setFormNote(data.message || "OTP verified. Redirecting...");

    window.setTimeout(() => {
      window.location.href = data.redirect || "./dashboard.php";
    }, 350);
  } catch {
    setFormNote("Unable to verify OTP right now. Please try again.", true);
    clearOtpInputs();
    setOtpInputsDisabled(false);
    focusOtpIndex(0);
  } finally {
    otpRequestInFlight = false;
    hideLoadingModal();

    if (!verified) {
      renderResendButton();
    }
  }
};

const resendOtpCode = async () => {
  if (otpRequestInFlight || otpSecondsRemaining > 0) {
    return;
  }

  otpRequestInFlight = true;
  renderResendButton();
  setFormNote("Sending a new OTP...");
  showLoadingModal("Sending a new OTP...");

  try {
    const response = await fetch(RESEND_OTP_API_URL, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },
      body: "action=resend",
    });

    const data = await response.json().catch(() => ({}));
    const isSuccess = response.ok && data.success === true;

    if (!isSuccess) {
      setFormNote(data.message || "Unable to resend OTP. Please try again.", true);

      if (typeof data.seconds_remaining === "number" && data.seconds_remaining > 0) {
        startOtpCountdown(data.seconds_remaining);
      }

      return;
    }

    clearOtpInputs();
    setOtpInputsDisabled(false);
    startOtpCountdown(data.expires_in || OTP_DEFAULT_EXPIRY_SECONDS);
    setFormNote(data.message || "A new OTP was sent to your email.");
    focusOtpIndex(0);
  } catch {
    setFormNote("Unable to resend OTP right now.", true);
  } finally {
    otpRequestInFlight = false;
    renderResendButton();
    hideLoadingModal();
  }
};

const applyTheme = (theme) => {
  const isDark = theme === "dark";

  root.setAttribute("data-theme", theme);
  themeToggle.setAttribute("aria-label", isDark ? "Switch to light theme" : "Switch to dark theme");

  iconThemeSun.classList.toggle("hidden", !isDark);
  iconThemeMoon.classList.toggle("hidden", isDark);
  updateThemeImages(theme);
};

const THEME_STORAGE_KEY = "portfolio-theme";
const THEME_ASSET_CONFIG_URL = "./assets/data/theme-assets.json";

let themeAssets = {
  logo: "",
  profile: {
    light: "",
    dark: "",
  },
};

const systemThemeQuery = window.matchMedia("(prefers-color-scheme: dark)");
const getSystemTheme = () => (systemThemeQuery.matches ? "dark" : "light");

const isValidTheme = (theme) => theme === "light" || theme === "dark";

const normalizeAssetPath = (value) => {
  if (typeof value !== "string") {
    return "";
  }

  const normalizedValue = value.trim().replace(/\\/g, "/");
  const projectPathMarker = "/portfolio/";
  const markerIndex = normalizedValue.toLowerCase().indexOf(projectPathMarker);

  if (markerIndex >= 0) {
    return `./${normalizedValue.slice(markerIndex + projectPathMarker.length)}`;
  }

  return normalizedValue;
};

const normalizeThemeAssets = (rawAssets) => {
  const normalized = {
    logo: "",
    profile: {
      light: "",
      dark: "",
    },
  };

  if (!rawAssets || typeof rawAssets !== "object") {
    return normalized;
  }

  if (typeof rawAssets.logo === "string") {
    normalized.logo = normalizeAssetPath(rawAssets.logo);
  } else if (typeof rawAssets.logoPath === "string") {
    normalized.logo = normalizeAssetPath(rawAssets.logoPath);
  }

  let profileAssets = {};
  if (rawAssets.profile && typeof rawAssets.profile === "object") {
    profileAssets = rawAssets.profile;
  } else if (rawAssets.profiles && typeof rawAssets.profiles === "object") {
    profileAssets = rawAssets.profiles;
  }

  if (typeof profileAssets.light === "string") {
    normalized.profile.light = normalizeAssetPath(profileAssets.light);
  }

  if (typeof profileAssets.dark === "string") {
    normalized.profile.dark = normalizeAssetPath(profileAssets.dark);
  }

  if (!normalized.profile.light && typeof rawAssets.light === "string") {
    normalized.profile.light = normalizeAssetPath(rawAssets.light);
  }

  if (!normalized.profile.dark && typeof rawAssets.dark === "string") {
    normalized.profile.dark = normalizeAssetPath(rawAssets.dark);
  }

  if (!normalized.profile.light && typeof rawAssets.profileLight === "string") {
    normalized.profile.light = normalizeAssetPath(rawAssets.profileLight);
  }

  if (!normalized.profile.dark && typeof rawAssets.profileDark === "string") {
    normalized.profile.dark = normalizeAssetPath(rawAssets.profileDark);
  }

  return normalized;
};

const setImageSource = (imageElement, fallbackElement, src, altText) => {
  if (!imageElement || !fallbackElement) {
    return;
  }

  const normalizedSource = normalizeAssetPath(src);

  if (!normalizedSource) {
    imageElement.onload = null;
    imageElement.onerror = null;
    imageElement.removeAttribute("src");
    imageElement.classList.remove("image-visible", "image-switching");
    imageElement.classList.add("hidden");
    fallbackElement.classList.remove("hidden");
    return;
  }

  imageElement.alt = altText;

  const currentSource = imageElement.getAttribute("src") || "";
  const hasVisibleImage = !imageElement.classList.contains("hidden") && imageElement.classList.contains("image-visible");

  if (currentSource === normalizedSource && hasVisibleImage) {
    fallbackElement.classList.add("hidden");
    imageElement.classList.remove("image-switching");
    imageElement.classList.add("image-visible");
    return;
  }

  imageElement.classList.remove("image-visible");
  imageElement.classList.add("image-switching");
  if (!hasVisibleImage) {
    fallbackElement.classList.remove("hidden");
  }

  imageElement.onload = () => {
    imageElement.classList.remove("hidden", "image-switching");
    fallbackElement.classList.add("hidden");

    requestAnimationFrame(() => {
      imageElement.classList.add("image-visible");
    });
  };

  imageElement.onerror = () => {
    imageElement.classList.remove("image-visible", "image-switching");
    imageElement.classList.add("hidden");
    fallbackElement.classList.remove("hidden");
  };

  imageElement.src = normalizedSource;
};

const updateThemeImages = (theme) => {
  const profileSource = theme === "dark" ? themeAssets.profile.dark : themeAssets.profile.light;
  setImageSource(logoImage, logoFallback, themeAssets.logo, "Logo");
  setImageSource(profileImage, profileFallback, profileSource, "Profile photo");
};

const loadThemeAssets = async () => {
  try {
    const response = await fetch(THEME_ASSET_CONFIG_URL, { cache: "no-store" });
    if (!response.ok) {
      return;
    }

    const config = await response.json();
    themeAssets = normalizeThemeAssets(config);
    updateThemeImages(root.getAttribute("data-theme") || getSystemTheme());
  } catch {
    // Keep fallback placeholders when asset config is unavailable.
  }
};

const readThemeSettings = () => {
  const rawSettings = localStorage.getItem(THEME_STORAGE_KEY);

  if (!rawSettings) {
    return { mode: "system" };
  }

  try {
    const parsed = JSON.parse(rawSettings);
    if (parsed.mode === "manual" && isValidTheme(parsed.theme)) {
      return { mode: "manual", theme: parsed.theme };
    }

    if (parsed.mode === "system") {
      return { mode: "system" };
    }
  } catch (error) {
    localStorage.removeItem(THEME_STORAGE_KEY);
  }

  return { mode: "system" };
};

const saveThemeSettings = (settings) => {
  localStorage.setItem(THEME_STORAGE_KEY, JSON.stringify(settings));
};

const resolveTheme = (settings) => (settings.mode === "manual" ? settings.theme : getSystemTheme());

let themeSettings = readThemeSettings();
if (!localStorage.getItem(THEME_STORAGE_KEY)) {
  saveThemeSettings(themeSettings);
}
applyTheme(resolveTheme(themeSettings));
void loadThemeAssets();
setAuthStep("credentials");
renderResendButton();

const applySystemThemeChange = (event) => {
  const nextSystemTheme = event.matches ? "dark" : "light";

  if (themeSettings.mode !== "system") {
    themeSettings = { mode: "system" };
    saveThemeSettings(themeSettings);
  }

  applyTheme(nextSystemTheme);
};

if (typeof systemThemeQuery.addEventListener === "function") {
  systemThemeQuery.addEventListener("change", applySystemThemeChange);
} else if (typeof systemThemeQuery.addListener === "function") {
  systemThemeQuery.addListener(applySystemThemeChange);
}

const syncFloatingState = ({ input, wrapper }) => {
  wrapper.classList.toggle("has-value", input.value.trim().length > 0);
};

const setErrorText = (errorEl, message) => {
  const textEl = errorEl.querySelector("span");
  if (textEl) {
    textEl.textContent = message;
  }
};

const showFieldError = ({ input, wrapper, error }, message) => {
  wrapper.classList.add("field-invalid");
  error.classList.remove("hidden");
  setErrorText(error, message);
  input.setAttribute("aria-invalid", "true");
};

const clearFieldError = ({ input, wrapper, error }) => {
  wrapper.classList.remove("field-invalid");
  error.classList.add("hidden");
  input.setAttribute("aria-invalid", "false");
};

const validateField = (fieldConfig) => {
  const value = fieldConfig.input.value.trim();

  if (!value) {
    showFieldError(fieldConfig, fieldConfig.requiredMessage);
    return false;
  }

  if (fieldConfig.input.type === "email" && !fieldConfig.input.checkValidity()) {
    showFieldError(fieldConfig, fieldConfig.formatMessage);
    return false;
  }

  clearFieldError(fieldConfig);
  return true;
};

fields.forEach((fieldConfig) => {
  syncFloatingState(fieldConfig);

  fieldConfig.input.addEventListener("focus", () => {
    syncFloatingState(fieldConfig);
  });

  fieldConfig.input.addEventListener("input", () => {
    syncFloatingState(fieldConfig);
    if (fieldConfig.wrapper.classList.contains("field-invalid")) {
      validateField(fieldConfig);
    }
  });

  fieldConfig.input.addEventListener("blur", () => {
    syncFloatingState(fieldConfig);
    validateField(fieldConfig);
  });
});

themeToggle.addEventListener("click", () => {
  const nextTheme = root.getAttribute("data-theme") === "light" ? "dark" : "light";
  themeSettings = { mode: "manual", theme: nextTheme };
  saveThemeSettings(themeSettings);
  applyTheme(nextTheme);
});

passwordToggle.addEventListener("click", () => {
  const showPassword = passwordInput.type === "password";

  passwordInput.type = showPassword ? "text" : "password";
  iconEye.classList.toggle("hidden", showPassword);
  iconEyeOff.classList.toggle("hidden", !showPassword);
  passwordToggle.setAttribute("aria-label", showPassword ? "Hide password" : "Show password");
});

otpInputs.forEach((input, index) => {
  input.addEventListener("input", () => {
    const digitsOnly = input.value.replace(/\D/g, "");
    input.value = digitsOnly.slice(-1);
    input.classList.remove("otp-invalid");

    if (input.value && index < otpInputs.length - 1) {
      focusOtpIndex(index + 1);
    }

    if (getOtpCode().length === OTP_LENGTH) {
      void verifyOtpCode();
    }
  });

  input.addEventListener("keydown", (event) => {
    if (event.key.length === 1 && !/[0-9]/.test(event.key)) {
      event.preventDefault();
      return;
    }

    if (event.key === "Backspace" && input.value === "" && index > 0) {
      event.preventDefault();
      otpInputs[index - 1].value = "";
      focusOtpIndex(index - 1);
      return;
    }

    if (event.key === "ArrowLeft" && index > 0) {
      event.preventDefault();
      focusOtpIndex(index - 1);
      return;
    }

    if (event.key === "ArrowRight" && index < otpInputs.length - 1) {
      event.preventDefault();
      focusOtpIndex(index + 1);
    }
  });

  input.addEventListener("paste", (event) => {
    event.preventDefault();
  });

  input.addEventListener("copy", (event) => {
    event.preventDefault();
  });

  input.addEventListener("cut", (event) => {
    event.preventDefault();
  });
});

if (resendOtpButton) {
  resendOtpButton.addEventListener("click", () => {
    void resendOtpCode();
  });
}

if (changeCredentialsButton) {
  changeCredentialsButton.addEventListener("click", () => {
    goToCredentialsStep();
    emailInput.focus();
  });
}

loginForm.addEventListener("submit", async (event) => {
  event.preventDefault();

  if (authStep !== "credentials") {
    return;
  }

  const firstInvalidField = fields.find((fieldConfig) => !validateField(fieldConfig));

  if (firstInvalidField) {
    setFormNote("");
    firstInvalidField.input.focus();
    return;
  }

  const payload = {
    email: emailInput.value.trim(),
    password: passwordInput.value,
  };

  if (submitButton) {
    submitButton.disabled = true;
    submitButton.classList.add("opacity-70", "cursor-not-allowed");
  }

  setFormNote("Signing in...");
  showLoadingModal("Checking credentials...");

  const loginAbort = new AbortController();
  const loginTimeoutId = window.setTimeout(() => loginAbort.abort(), 20000);

  try {
    const response = await fetch(LOGIN_API_URL, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },
      body: new URLSearchParams(payload).toString(),
      signal: loginAbort.signal,
    });

    const data = await response.json().catch(() => ({}));
    const isSuccess = response.ok && data.success === true;

    if (data.requires_otp === true) {
      goToOtpStep(data.email_hint || payload.email, data.expires_in || OTP_DEFAULT_EXPIRY_SECONDS);

      if (!isSuccess && data.message) {
        setFormNote(data.message, true);
      }

      return;
    }

    if (!isSuccess) {
      setFormNote(data.message || "Unable to log in. Please check your credentials.", true);
      return;
    }

    setFormNote(data.message || "Login successful. Redirecting...");

    window.setTimeout(() => {
      window.location.href = data.redirect || "./dashboard.php";
    }, 450);
  } catch (err) {
    if (err && err.name === "AbortError") {
      setFormNote("Login timed out. The server took too long to respond. Please try again.", true);
    } else {
      setFormNote("Unable to connect to server. Check your PHP/MySQL setup.", true);
    }
  } finally {
    window.clearTimeout(loginTimeoutId);

    if (submitButton) {
      submitButton.disabled = false;
      submitButton.classList.remove("opacity-70", "cursor-not-allowed");
    }

    hideLoadingModal();
  }
});
