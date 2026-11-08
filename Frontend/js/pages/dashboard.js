import { api } from "../core/api.js";
import { LOW_STOCK_LIMIT } from "../core/config.js";
import { escapeHtml, icon, formatJOD } from "../components/ui.js";

async function load(path) {
  try { const response = await api.get(path); return { items: Array.isArray(response.data) ? response.data : [], ok: true }; }
  catch (error) { return { items: [], ok: false, error }; }
}

export async function renderDashboard(container, currentUser) {
  container.innerHTML = `<div class="loading-row"><span class="spinner" style="display:inline-block;vertical-align:middle;margin-left:8px"></span> جارٍ تحميل بيانات لوحة التحكم...</div>`;
  const [customers, admins, products, categories] = await Promise.all([
    load("/admin/customers"), load("/admin/admins"), load("/products"), load("/categories"),
  ]);

  const knownUsers = new Map();
  if (customers.ok && admins.ok) {
    [...customers.items, ...admins.items, currentUser].filter(Boolean).forEach((user) => {
      const id = user.userId ?? user.UserID;
      if (id !== undefined && id !== null) knownUsers.set(String(id), user);
    });
  }
  const userCount = customers.ok && admins.ok ? knownUsers.size : null;
  const productCount = products.ok ? products.items.length : null;
  const categoryCount = categories.ok ? categories.items.length : null;
  const lowStock = products.ok ? products.items.filter((p) => Number(p.Stock ?? p.stock ?? 0) <= LOW_STOCK_LIMIT).length : null;

  const metrics = [
    { label: "إجمالي المستخدمين", value: userCount ?? "—", hint: userCount === null ? "تعذر تحميل المستخدمين" : "يشمل حساب المسؤول الحالي", icon: "users" },
    { label: "الكتب والمنتجات", value: productCount ?? "—", hint: products.ok ? "إجمالي المنتجات المسجلة" : "تعذر تحميل المنتجات", icon: "book" },
    { label: "التصنيفات", value: categoryCount ?? "—", hint: categories.ok ? "التصنيفات المتاحة في المتجر" : "تعذر تحميل التصنيفات", icon: "folders" },
    { label: "مخزون منخفض", value: lowStock ?? "—", hint: `كمية المخزون ≤ ${LOW_STOCK_LIMIT}`, icon: "package" },
  ];

  const categoryMap = new Map(categories.items.map((c) => [String(c.CategoryID ?? c.categoryId ?? ""), c.CategoryName ?? c.categoryName ?? "تصنيف"]));
  const categoryCounts = new Map();
  if (products.ok) {
    products.items.forEach((p) => {
      const key = String(p.CategoryID ?? p.categoryId ?? "");
      const label = categoryMap.get(key) || p.CategoryName || p.categoryName || "غير مصنف";
      categoryCounts.set(label, (categoryCounts.get(label) || 0) + 1);
    });
  }
  const chartItems = [...categoryCounts.entries()].sort((a, b) => b[1] - a[1]).slice(0, 6);
  const chartMax = Math.max(1, ...chartItems.map((x) => x[1]));
  const categoryChart = products.ok && categories.ok
    ? chartItems.length ? chartItems.map(([label, count]) => `<div class="chart-bar-row"><span class="chart-bar-label" title="${escapeHtml(label)}">${escapeHtml(label)}</span><div class="bar-track"><div class="bar-fill" style="width:${(count / chartMax) * 100}%"></div></div><b class="chart-count">${count}</b></div>`).join("") : `<div class="empty-state"><strong>لا توجد منتجات بعد</strong><span>ستظهر هنا أعداد الكتب في كل تصنيف.</span></div>`
    : `<div class="notice warning">تعذر تحميل المنتجات أو التصنيفات. تأكد من تفعيل مسارات API وإرجاع CategoryID وProductID ضمن المنتج وCategoryID ضمن التصنيف.</div>`;

  let available = 0, low = 0, out = 0;
  if (products.ok) {
    products.items.forEach((product) => {
      const stock = Number(product.Stock ?? product.stock ?? 0);
      if (stock <= 0) out++;
      else if (stock <= LOW_STOCK_LIMIT) low++;
      else available++;
    });
  }
  const totalStockProducts = available + low + out;
  const availablePercent = totalStockProducts ? (available / totalStockProducts) * 100 : 0;
  const lowPercent = totalStockProducts ? ((available + low) / totalStockProducts) * 100 : 0;
  const stockVisual = products.ok ? `
    <div class="stock-chart"><div class="donut" style="--available:${availablePercent}%;--low:${lowPercent}%"><div class="donut-center"><strong>${totalStockProducts}</strong><span>إجمالي الكتب</span></div></div>
      <div class="legend">
        <div class="legend-item"><i class="legend-dot" style="background:var(--success)"></i><span>متوفر</span><b>${available}</b></div>
        <div class="legend-item"><i class="legend-dot" style="background:var(--accent)"></i><span>مخزون منخفض</span><b>${low}</b></div>
        <div class="legend-item"><i class="legend-dot" style="background:var(--danger)"></i><span>نفد المخزون</span><b>${out}</b></div>
      </div></div>` : `<div class="notice warning">بيانات المخزون غير متاحة من الخادم حالياً.</div>`;

  const recentProducts = products.ok ? [...products.items].slice(0, 5).map((p) => `
    <div class="quick-row"><span>${escapeHtml(p.ProductName ?? p.productName ?? "منتج بدون اسم")}</span><b>${formatJOD(p.Price ?? p.price)}</b></div>`).join("") : `<div class="empty-state">تعذر تحميل المنتجات.</div>`;

  container.innerHTML = `
    <div class="page-heading"><div><h1>نظرة عامة</h1><p>ملخص سريع عن حالة مكتبة الرَّف ومخزونها.</p></div><div class="heading-actions"><span class="pill pill-neutral">مرحباً، ${escapeHtml(currentUser?.username || "المسؤول")}</span></div></div>
    <div class="stats-grid">${metrics.map((item) => `<article class="card stat-card"><div class="stat-top"><span class="stat-label">${item.label}</span><span class="stat-icon">${icon(item.icon, 21)}</span></div><strong class="stat-number">${item.value}</strong><span class="stat-foot">${item.hint}</span></article>`).join("")}</div>
    <div class="dashboard-grid">
      <section class="card card-pad"><div class="panel-title"><h2>الكتب حسب التصنيف</h2><span>توزيع المنتجات الحالية</span></div><div class="chart-bars">${categoryChart}</div></section>
      <section class="card card-pad"><div class="panel-title"><h2>حالة المخزون</h2><span>حسب عدد المنتجات</span></div>${stockVisual}</section>
      <section class="card card-pad"><div class="panel-title"><h2>المنتجات في القائمة</h2><span>عينة من أول 5 سجلات</span></div><div class="quick-list">${recentProducts}</div></section>
      <section class="card card-pad"><div class="panel-title"><h2>تنبيهات الإدارة</h2><span>معلومات تشغيلية</span></div>
        <div class="quick-list">
          <div class="quick-row"><span>الطلبات</span><span class="pill pill-neutral">غير جاهزة</span></div>
          <div class="quick-row"><span>المدفوعات</span><span class="pill pill-neutral">غير جاهزة</span></div>
          <div class="quick-row"><span>العملة المعتمدة</span><b>JOD</b></div>
          <div class="quick-row"><span>حد المخزون المنخفض</span><b>${LOW_STOCK_LIMIT} وحدات</b></div>
        </div>
      </section>
    </div>
    ${[customers, admins, products, categories].some((x) => !x.ok) ? `<div class="notice warning" style="margin-top:16px">بعض الإحصائيات غير متاحة لأن واحداً أو أكثر من مسارات API لم يستجب. الأرقام غير المكتملة تظهر كشرطة (—)، ولا نستخدم بيانات تجريبية.</div>` : ""}
  `;
}
