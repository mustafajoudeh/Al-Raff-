import { icon, escapeHtml } from "./ui.js";

const navItems = [
  { path: "/admin/dashboard", label: "الرئيسية", icon: "dashboard" },
  { path: "/admin/users", label: "المستخدمون", icon: "users" },
  { path: "/admin/products", label: "الكتب والمنتجات", icon: "book" },
  { path: "/admin/categories", label: "التصنيفات", icon: "folders" },
  { path: "/admin/orders", label: "الطلبات", icon: "cart", pending: true },
  { path: "/admin/payments", label: "المدفوعات", icon: "card", pending: true },
];

function logo() {
  return `<svg class="brand-mark" viewBox="0 0 64 74" fill="none" aria-hidden="true">
    <path d="M6 68V32C6 17 19 8 32 1c13 7 26 16 26 31v36M13 68V34C13 22 22 14 32 8c10 6 19 14 19 26v34" stroke="var(--accent)" stroke-width="2.2"/>
    <g fill="currentColor"><rect x="19" y="38" width="5" height="22" rx="1"/><rect x="26" y="30" width="5" height="30" rx="1"/><rect x="33" y="40" width="5" height="20" rx="1"/><rect x="41" y="36" width="5" height="24" rx="1" transform="rotate(-12 41 60)"/></g>
    <path d="M13 62h38" stroke="var(--accent)" stroke-width="2"/>
  </svg>`;
}

export function renderAdminShell({
  user,
  activePath,
  title,
  subtitle,
  content,
}) {
  const links = navItems
    .map(
      (item) => `
    <a class="nav-link ${activePath === item.path ? "active" : ""} ${item.pending ? "disabled-link" : ""}" href="#${item.path}" ${item.pending ? 'data-pending="true"' : ""}>
      ${icon(item.icon)}<span>${item.label}</span>${item.pending ? '<span class="pill pill-neutral">قريباً</span>' : ""}
    </a>`,
    )
    .join("");

  return `
    <div class="page-shell">
      <div class="sidebar-overlay" id="sidebar-overlay" hidden></div>
      <aside class="sidebar" id="sidebar">
        <div class="brand">${logo()}<div class="brand-name"><strong>الرَّف</strong><small>AL-RAFF</small></div></div>
        <nav class="nav-group" aria-label="القائمة الرئيسية">
          <span class="nav-caption">إدارة المتجر</span>${links}
        </nav>
        <div class="sidebar-bottom">
          <div class="sidebar-note"><strong>مكتبة القارئ المتأنّي</strong>إدارة الكتب والتصنيفات والمستخدمين من مكان واحد.</div>
          <button class="nav-link" type="button" id="sidebar-logout">${icon("logout")}<span>تسجيل الخروج</span></button>
        </div>
      </aside>

      <main class="admin-main">
        <header class="topbar">
          <div class="topbar-start">
            <button class="icon-button mobile-menu" type="button" id="mobile-menu" aria-label="فتح القائمة">${icon("menu")}</button>
            <div class="topbar-title"><strong>${escapeHtml(title)}</strong><span>${escapeHtml(subtitle || "لوحة إدارة متجر الرَّف")}</span></div>
          </div>
          <div class="topbar-actions">
            <button class="icon-button" type="button" id="theme-toggle" aria-label="تبديل المظهر">${icon("moon")}</button>
            <div class="user-chip"><span class="avatar">${escapeHtml((user?.username || "م").slice(0, 1).toUpperCase())}</span><div><strong>${escapeHtml(user?.username || "المسؤول")}</strong><small>مسؤول النظام</small></div></div>
          </div>
        </header>
        <section id="page-content">${content || ""}</section>
      </main>
    </div>`;
}

export function bindAdminShell({ onLogout }) {
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("sidebar-overlay");
  const toggleSidebar = (open) => {
    sidebar?.classList.toggle("open", open);
    if (overlay) overlay.hidden = !open;
  };
  document
    .getElementById("mobile-menu")
    ?.addEventListener("click", () => toggleSidebar(true));
  overlay?.addEventListener("click", () => toggleSidebar(false));
  sidebar
    ?.querySelectorAll("a")
    .forEach((link) =>
      link.addEventListener("click", () => toggleSidebar(false)),
    );
  document
    .querySelectorAll("[data-pending='true']")
    .forEach((link) =>
      link.addEventListener("click", (event) => event.preventDefault()),
    );
  document
    .getElementById("sidebar-logout")
    ?.addEventListener("click", onLogout);

  const themeButton = document.getElementById("theme-toggle");
  themeButton?.addEventListener("click", () => {
    const nextTheme =
      document.documentElement.dataset.theme === "dark" ? "light" : "dark";
    document.documentElement.dataset.theme = nextTheme;
    localStorage.setItem("alraff-theme", nextTheme);
    document
      .querySelector('meta[name="theme-color"]')
      ?.setAttribute("content", nextTheme === "dark" ? "#130E11" : "#F6F3EE");
  });
}

export function applySavedTheme() {
  const theme = localStorage.getItem("alraff-theme");
  if (theme === "light" || theme === "dark")
    document.documentElement.dataset.theme = theme;
}
