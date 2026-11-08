import { api } from "../core/api.js";
import { escapeHtml, icon, showToast, showModal, closeModal, formatJOD, emptyState } from "../components/ui.js";

let products = [];
let categories = [];
let filters = { search: "", category: "all", stock: "all" };

const val = (item, upper, lower) => item?.[upper] ?? item?.[lower] ?? "";

function filteredProducts() {
  const search = filters.search.toLowerCase();
  return products.filter((p) => {
    const categoryId = String(val(p, "CategoryID", "categoryId"));
    const stock = Number(val(p, "Stock", "stock"));
    const matchesSearch = !search || [val(p,"ProductName","productName"), val(p,"Author","author"), val(p,"ISBN","isbn"), val(p,"ProductID","productId")].some((v) => String(v).toLowerCase().includes(search));
    const matchesCategory = filters.category === "all" || categoryId === filters.category;
    const matchesStock = filters.stock === "all" || (filters.stock === "out" ? stock <= 0 : filters.stock === "low" ? stock > 0 && stock <= 5 : stock > 5);
    return matchesSearch && matchesCategory && matchesStock;
  });
}

function rowsHtml(items) {
  if (!items.length) return `<tr><td colspan="7">${emptyState("لا توجد منتجات", "أضف كتاباً جديداً أو غيّر البحث والفلاتر.")}</td></tr>`;
  return items.map((p) => {
    const id = Number(val(p, "ProductID", "productId"));
    const stock = Number(val(p, "Stock", "stock"));
    const status = stock <= 0 ? '<span class="pill pill-danger">نفد المخزون</span>' : stock <= 5 ? '<span class="pill pill-warning">مخزون منخفض</span>' : '<span class="pill pill-success">متوفر</span>';
    const category = categories.find((c) => String(c.CategoryID) === String(val(p,"CategoryID","categoryId")));
    return `<tr>
      <td><div class="book-cell"><div class="book-cover">ر</div><div class="cell-main"><strong>${escapeHtml(val(p,"ProductName","productName"))}</strong><span>#${id || "—"}</span></div></div></td>
      <td>${escapeHtml(val(p,"Author","author") || "—")}</td>
      <td>${escapeHtml(category?.CategoryName || val(p,"CategoryName","categoryName") || "—")}</td>
      <td><b>${formatJOD(val(p,"Price","price"))}</b></td>
      <td>${stock}</td><td>${status}</td>
      <td><div class="table-actions"><button class="table-icon" data-action="view" data-id="${id}" title="عرض">${icon("eye",15)}</button><button class="table-icon" data-action="edit" data-id="${id}" title="تعديل">${icon("edit",15)}</button><button class="table-icon danger" data-action="delete" data-id="${id}" title="حذف">${icon("trash",15)}</button></div></td>
    </tr>`;
  }).join("");
}

function paint(container) {
  const items = filteredProducts();
  container.querySelector("#products-body").innerHTML = rowsHtml(items);
  container.querySelector("#products-count").textContent = `عرض ${items.length} من ${products.length} منتج`;
}

async function loadData(container) {
  const [productResponse, categoryResponse] = await Promise.all([api.get("/products"), api.get("/categories")]);
  products = Array.isArray(productResponse.data) ? productResponse.data : [];
  categories = Array.isArray(categoryResponse.data) ? categoryResponse.data : [];
  paint(container);
  if (products.some((p) => val(p,"ProductID","productId") === "" || val(p,"CategoryID","categoryId") === "")) {
    showToast("قائمة المنتجات لا تحتوي ProductID أو CategoryID. طبّق تعديل ProductDAL وProductListDTO في ملف Backend Required Patches.", "error", 7000);
  }
}

function productForm(product = null) {
  const options = categories.map((c) => `<option value="${escapeHtml(c.CategoryID)}" ${String(c.CategoryID) === String(val(product,"CategoryID","categoryId")) ? "selected" : ""}>${escapeHtml(c.CategoryName)}</option>`).join("");
  return `<form id="product-form" class="form-grid">
    <div class="form-field full"><label for="product-name">اسم الكتاب *</label><input class="control" id="product-name" name="ProductName" maxlength="100" required value="${escapeHtml(val(product,"ProductName","productName"))}"></div>
    <div class="form-field"><label for="product-author">المؤلف *</label><input class="control" id="product-author" name="Author" maxlength="50" required value="${escapeHtml(val(product,"Author","author"))}"></div>
    <div class="form-field"><label for="product-category">التصنيف *</label><select class="control" id="product-category" name="CategoryID" required><option value="">اختر التصنيف</option>${options}</select></div>
    <div class="form-field"><label for="product-price">السعر (JOD) *</label><input class="control" id="product-price" name="Price" type="number" min="0.001" step="0.001" required value="${escapeHtml(val(product,"Price","price"))}"></div>
    <div class="form-field"><label for="product-stock">الكمية المتوفرة *</label><input class="control" id="product-stock" name="Stock" type="number" min="0" step="1" required value="${escapeHtml(val(product,"Stock","stock") ?? 0)}"></div>
    <div class="form-field"><label for="product-pages">عدد الصفحات *</label><input class="control" id="product-pages" name="Pages" type="number" min="1" step="1" required value="${escapeHtml(val(product,"Pages","pages") || "1")}"></div>
    <div class="form-field"><label for="product-isbn">ISBN</label><input class="control" id="product-isbn" name="ISBN" maxlength="13" value="${escapeHtml(val(product,"ISBN","isbn"))}"></div>
    <div class="form-field"><label for="product-publisher">دار النشر</label><input class="control" id="product-publisher" name="Publisher" maxlength="50" value="${escapeHtml(val(product,"Publisher","publisher"))}"></div>
    <div class="form-field"><label for="product-year">سنة النشر</label><input class="control" id="product-year" name="PublishedYear" type="number" min="1" step="1" value="${escapeHtml(val(product,"PublishedYear","publishedYear"))}"></div>
    <div class="form-field full"><label for="product-description">الوصف</label><textarea class="control" id="product-description" name="Description" maxlength="255">${escapeHtml(val(product,"Description","description"))}</textarea><span class="hint">حد أقصى 255 حرفاً.</span></div>
    <div class="form-error full" id="product-error" hidden></div>
  </form>`;
}

async function openForm(container, id = null) {
  if (!categories.length) { showToast("أضف تصنيفاً أولاً قبل إضافة المنتجات.", "error"); return; }
  let product = null;
  if (id !== null) {
    try { const result = await api.get(`/products/${id}`); product = result.data; }
    catch (error) { showToast(error.message, "error"); return; }
  }
  const backdrop = showModal({ title: product ? "تعديل بيانات الكتاب" : "إضافة كتاب جديد", body: productForm(product), wide: true, footer: `<button class="btn btn-outline" type="button" data-modal-close>إلغاء</button><button class="btn btn-primary" type="submit" form="product-form">${product ? "حفظ التعديلات" : "إضافة الكتاب"}</button>` });
  backdrop.querySelector("#product-form").addEventListener("submit", async (event) => {
    event.preventDefault(); const form = event.currentTarget; const errorBox = form.querySelector("#product-error"); errorBox.hidden = true;
    if (!form.reportValidity()) return;
    const body = {
      CategoryID: Number(form.CategoryID.value), ProductName: form.ProductName.value.trim(),
      Price: form.Price.value, Stock: Number(form.Stock.value), Author: form.Author.value.trim(),
      Pages: Number(form.Pages.value), Description: form.Description.value.trim() || null,
      ISBN: form.ISBN.value.trim() || null, Publisher: form.Publisher.value.trim() || null,
      PublishedYear: form.PublishedYear.value ? Number(form.PublishedYear.value) : null,
    };
    try {
      if (product) await api.put(`/admin/products/${id}`, body);
      else await api.post("/admin/products", body);
      closeModal(); showToast(product ? "تم تحديث بيانات الكتاب." : "تمت إضافة الكتاب."); await loadData(container);
    } catch (error) { errorBox.textContent = error.message; errorBox.hidden = false; }
  });
}

async function viewProduct(id) {
  try {
    const { data: p } = await api.get(`/products/${id}`);
    const category = categories.find((c) => String(c.CategoryID) === String(val(p,"CategoryID","categoryId")));
    showModal({ title: "تفاصيل الكتاب", body: `<div class="detail-grid">
      <div class="detail-item"><span>رقم الكتاب</span><strong>#${escapeHtml(val(p,"ProductID","productId"))}</strong></div>
      <div class="detail-item"><span>اسم الكتاب</span><strong>${escapeHtml(val(p,"ProductName","productName"))}</strong></div>
      <div class="detail-item"><span>المؤلف</span><strong>${escapeHtml(val(p,"Author","author") || "—")}</strong></div>
      <div class="detail-item"><span>التصنيف</span><strong>${escapeHtml(category?.CategoryName || "—")}</strong></div>
      <div class="detail-item"><span>السعر</span><strong>${formatJOD(val(p,"Price","price"))}</strong></div>
      <div class="detail-item"><span>المخزون</span><strong>${escapeHtml(val(p,"Stock","stock"))}</strong></div>
      <div class="detail-item"><span>عدد الصفحات</span><strong>${escapeHtml(val(p,"Pages","pages") || "—")}</strong></div>
      <div class="detail-item"><span>ISBN</span><strong>${escapeHtml(val(p,"ISBN","isbn") || "—")}</strong></div>
      <div class="detail-item"><span>دار النشر</span><strong>${escapeHtml(val(p,"Publisher","publisher") || "—")}</strong></div>
      <div class="detail-item"><span>سنة النشر</span><strong>${escapeHtml(val(p,"PublishedYear","publishedYear") || "—")}</strong></div>
      <div class="detail-item" style="grid-column:1/-1"><span>الوصف</span><strong>${escapeHtml(val(p,"Description","description") || "—")}</strong></div>
    </div>` });
  } catch (error) { showToast(error.message, "error"); }
}

export async function renderProducts(container) {
  filters = { search: "", category: "all", stock: "all" };
  container.innerHTML = `<div class="loading-row">جارٍ تحميل المنتجات...</div>`;
  container.innerHTML = `
    <div class="page-heading"><div><h1>الكتب والمنتجات</h1><p>إدارة بيانات الكتب والأسعار والكميات والتصنيفات.</p></div><div class="heading-actions"><button class="btn btn-primary" id="add-product">${icon("plus")} إضافة كتاب</button></div></div>
    <div class="card toolbar"><div class="search-box">${icon("search")}<input class="control" id="product-search" placeholder="ابحث باسم الكتاب أو المؤلف أو ISBN..."></div>
      <select class="control filter-control" id="product-category-filter"><option value="all">كل التصنيفات</option></select>
      <select class="control filter-control" id="product-stock-filter"><option value="all">كل حالات المخزون</option><option value="available">متوفر</option><option value="low">مخزون منخفض</option><option value="out">نفد المخزون</option></select>
      <span class="muted" id="products-count">جارٍ التحميل...</span></div>
    <div class="card table-card"><div class="table-scroll"><table><thead><tr><th>الكتاب</th><th>المؤلف</th><th>التصنيف</th><th>السعر</th><th>الكمية</th><th>الحالة</th><th>الإجراءات</th></tr></thead><tbody id="products-body"><tr><td colspan="7" class="loading-row">جارٍ التحميل...</td></tr></tbody></table></div></div>`;
  try {
    await loadData(container);
    const select = container.querySelector("#product-category-filter");
    select.innerHTML = `<option value="all">كل التصنيفات</option>${categories.map((c) => `<option value="${escapeHtml(c.CategoryID)}">${escapeHtml(c.CategoryName)}</option>`).join("")}`;
  } catch (error) { container.querySelector("#products-body").innerHTML = `<tr><td colspan="7"><div class="empty-state"><strong>تعذر تحميل المنتجات أو التصنيفات</strong><span>${escapeHtml(error.message)}</span></div></td></tr>`; }

  container.querySelector("#add-product").addEventListener("click", () => openForm(container));
  container.querySelector("#product-search").addEventListener("input", (event) => { filters.search = event.target.value; paint(container); });
  container.querySelector("#product-category-filter").addEventListener("change", (event) => { filters.category = event.target.value; paint(container); });
  container.querySelector("#product-stock-filter").addEventListener("change", (event) => { filters.stock = event.target.value; paint(container); });
  container.querySelector("#products-body").addEventListener("click", async (event) => {
    const button = event.target.closest("button[data-action]"); if (!button) return;
    const id = Number(button.dataset.id); const action = button.dataset.action;
    if (!id) return showToast("المنتج لا يحتوي ProductID صالحاً. راجع Backend Required Patches.", "error");
    if (action === "view") return viewProduct(id);
    if (action === "edit") return openForm(container, id);
    if (action === "delete") {
      const product = products.find((p) => Number(val(p,"ProductID","productId")) === id);
      if (!confirm(`هل تريد حذف الكتاب "${val(product,"ProductName","productName")}"؟`)) return;
      try { await api.delete(`/admin/products/${id}`); showToast("تم حذف الكتاب."); await loadData(container); }
      catch (error) { showToast(error.message, "error"); }
    }
  });
}
