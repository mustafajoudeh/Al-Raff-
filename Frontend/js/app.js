import { auth } from "./core/auth.js";
import { getCurrentPath, navigate, onRouteChange } from "./core/router.js";
import {
  renderAdminShell,
  bindAdminShell,
  applySavedTheme,
} from "./components/Layout.js";
import { renderHome } from "./pages/home.js";
import { renderLogin, renderRegistration } from "./pages/auth.js";
import { renderDashboard } from "./pages/dashboard.js";
import { renderUsers } from "./pages/users.js";
import { renderProducts } from "./pages/products.js";
import { renderCategories } from "./pages/categories.js";
import { renderComingSoon } from "./pages/placeholders.js";
import { showToast } from "./components/ui.js";

const app = document.getElementById("app");
const state = { user: null, authChecked: false, renderToken: 0 };

async function ensureAdmin() {
  if (state.authChecked && state.user?.role === "Admin") return state.user;
  try {
    const user = await auth.me();
    if (user?.role !== "Admin")
      throw new Error("هذا الحساب لا يملك صلاحية المسؤول.");
    state.user = user;
    state.authChecked = true;
    return user;
  } catch {
    state.user = null;
    state.authChecked = false;
    navigate("/login", { replace: true });
    return null;
  }
}

async function handleLogout() {
  try {
    await auth.logout();
    state.user = null;
    state.authChecked = false;
    showToast("تم تسجيل الخروج.");
    navigate("/", { replace: true });
  } catch (error) {
    showToast(error.message || "تعذر تسجيل الخروج.", "error");
  }
}

async function render() {
  const token = ++state.renderToken;
  const path = getCurrentPath();

  // Public landing page is the default route. Login is a separate page.
  if (path === "/") {
    document.title = "الرَّف | مكتبة القارئ المتأنّي";
    await renderHome(app, {
      isActive: () => token === state.renderToken && getCurrentPath() === "/",
    });
    return;
  }

  if (path === "/login") {
    document.title = "تسجيل الدخول | الرَّف";
    renderLogin(app, {
      onLogin: async (user) => {
        state.user = user;
        state.authChecked = true;
        navigate("/admin/dashboard", { replace: true });
      },
    });
    return;
  }

  if (path === "/register") {
    document.title = "إنشاء حساب | الرَّف";
    renderRegistration(app);
    return;
  }

  if (!path.startsWith("/admin/")) {
    navigate("/", { replace: true });
    return;
  }

  const user = await ensureAdmin();
  if (token !== state.renderToken || !user) return;

  const routes = {
    "/admin/dashboard": {
      title: "لوحة التحكم",
      subtitle: "ملخص أداء متجر الرَّف",
      render: (root) => renderDashboard(root, user),
    },
    "/admin/users": {
      title: "المستخدمون",
      subtitle: "إدارة الحسابات والصلاحيات",
      render: (root) => renderUsers(root, user),
    },
    "/admin/products": {
      title: "الكتب والمنتجات",
      subtitle: "إدارة الكتب والمخزون والأسعار",
      render: renderProducts,
    },
    "/admin/categories": {
      title: "التصنيفات",
      subtitle: "تنظيم رفوف المكتبة",
      render: renderCategories,
    },
    "/admin/orders": {
      title: "الطلبات",
      subtitle: "متابعة طلبات المتجر",
      render: (root) => renderComingSoon(root, "orders"),
    },
    "/admin/payments": {
      title: "المدفوعات",
      subtitle: "متابعة المدفوعات",
      render: (root) => renderComingSoon(root, "payments"),
    },
  };

  const route = routes[path];
  if (!route) {
    navigate("/admin/dashboard", { replace: true });
    return;
  }

  document.title = `${route.title} | الرَّف`;
  app.innerHTML = renderAdminShell({
    user,
    activePath: path,
    title: route.title,
    subtitle: route.subtitle,
  });
  bindAdminShell({ onLogout: handleLogout });
  await route.render(document.getElementById("page-content"));
}

applySavedTheme();
onRouteChange(render);
render();
