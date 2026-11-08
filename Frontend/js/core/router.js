export function getCurrentPath() {
  const hash = window.location.hash.replace(/^#/, "");
  const path = (hash.split("?")[0] || "/").trim();
  if (!path || path === "") return "/";
  return path.startsWith("/") ? path : `/${path}`;
}

export function navigate(path, { replace = false } = {}) {
  const normalized = path.startsWith("/") ? path : `/${path}`;
  if (replace) {
    window.history.replaceState(
      null,
      "",
      `${window.location.pathname}${window.location.search}#${normalized}`,
    );
    window.dispatchEvent(new Event("hashchange"));
    return;
  }
  if (getCurrentPath() === normalized) {
    window.dispatchEvent(new Event("hashchange"));
  } else {
    window.location.hash = normalized;
  }
}

export function onRouteChange(callback) {
  window.addEventListener("hashchange", callback);
  return () => window.removeEventListener("hashchange", callback);
}
