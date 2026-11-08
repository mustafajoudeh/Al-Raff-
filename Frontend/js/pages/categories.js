import { api } from "../core/api.js";
import { escapeHtml, icon, showToast, showModal, closeModal, emptyState } from "../components/ui.js";

let categories = [];
let searchText = "";

function renderRows() {
  const term = searchText.toLowerCase();
  const filtered = categories.filter((category) => [category.CategoryName, category.Description, category.CategoryID].some((v) => String(v ?? "").toLowerCase().includes(term)));
  if (!filtered.length) return `<tr><td colspan="4">${emptyState("لا توجد تصنيفات", "أضف تصنيفاً جديداً أو غيّر عبارة البحث.")}</td></tr>`;
  return filtered.map((category) => `
    <tr>
      <td><div class="book-cell"><div class="category-thumb">${category.URLImage ? `<img src="${escapeHtml(category.URLImage)}" alt="" loading="lazy" onerror="this.remove()">` : icon("folders", 20)}</div><div class="cell-main"><strong>${escapeHtml(category.CategoryName)}</strong><span>#${escapeHtml(category.CategoryID)}</span></div></div></td>
      <td>${escapeHtml(category.Description || "—")}</td>
      <td>${category.URLImage ? `<a href="${escapeHtml(category.URLImage)}" target="_blank" rel="noopener">عرض الصورة</a>` : "—"}</td>
      <td><div class="table-actions"><button class="table-icon" data-action="view" data-id="${category.CategoryID}" title="عرض">${icon("eye", 15)}</button><button class="table-icon" data-action="edit" data-id="${category.CategoryID}" title="تعديل">${icon("edit", 15)}</button><button class="table-icon danger" data-action="delete" data-id="${category.CategoryID}" title="حذف">${icon("trash", 15)}</button></div></td>
    </tr>`).join("");
}

function updateTable(container) {
  container.querySelector("#categories-body").innerHTML = renderRows();
  const visible = categories.filter((c) => [c.CategoryName, c.Description, c.CategoryID].some((v) => String(v ?? "").toLowerCase().includes(searchText.toLowerCase()))).length;
  container.querySelector("#categories-count").textContent = `${visible} تصنيف`;
}

async function loadCategories(container) {
  const response = await api.get("/categories");
  categories = Array.isArray(response.data) ? response.data : [];
  updateTable(container);
  if (categories.some((c) => c.CategoryID == null)) {
    showToast("قائمة التصنيفات لا تحتوي CategoryID. راجع تعديل CategoryDAL الموضح في ملف BACKEND_REQUIRED_PATCHES.md.", "error", 6500);
  }
}

function categoryForm(category = null) {
  return `<form id="category-form" class="form-grid">
    <div class="form-field full"><label for="category-name">اسم التصنيف *</label><input class="control" id="category-name" name="CategoryName" maxlength="100" required value="${escapeHtml(category?.CategoryName || "")}"></div>
    <div class="form-field full"><label for="category-description">الوصف</label><textarea class="control" id="category-description" name="Description" maxlength="255">${escapeHtml(category?.Description || "")}</textarea><span class="hint">حد أقصى 255 حرفاً.</span></div>
    <div class="form-field full"><label for="category-image">رابط صورة التصنيف</label><input class="control" id="category-image" name="URLImage" type="url" value="${escapeHtml(category?.URLImage || "")}" placeholder="https://example.com/category.jpg"><span class="hint">الـ API الحالية تستقبل رابط صورة، ولا توفر رفع ملفات.</span></div>
    <div class="form-error full" id="category-error" hidden></div>
  </form>`;
}

async function openForm(container, id = null) {
  let category = null;
  if (id !== null) {
    try { const result = await api.get(`/categories/${id}`); category = result.data; }
    catch (error) { showToast(error.message, "error"); return; }
  }
  const backdrop = showModal({ title: category ? "تعديل التصنيف" : "إضافة تصنيف", body: categoryForm(category), footer: `<button class="btn btn-outline" type="button" data-modal-close>إلغاء</button><button class="btn btn-primary" type="submit" form="category-form">${category ? "حفظ التعديلات" : "إضافة التصنيف"}</button>` });
  backdrop.querySelector("#category-form").addEventListener("submit", async (event) => {
    event.preventDefault(); const form = event.currentTarget; const errorBox = form.querySelector("#category-error"); errorBox.hidden = true;
    if (!form.reportValidity()) return;
    const body = { CategoryName: form.CategoryName.value.trim(), Description: form.Description.value.trim(), URLImage: form.URLImage.value.trim() };
    try {
      if (category) await api.put(`/admin/categories/${id}`, body);
      else await api.post("/admin/categories", body);
      closeModal(); showToast(category ? "تم تحديث التصنيف." : "تمت إضافة التصنيف."); await loadCategories(container);
    } catch (error) { errorBox.textContent = error.message; errorBox.hidden = false; }
  });
}

async function viewCategory(id) {
  try {
    const { data } = await api.get(`/categories/${id}`);
    showModal({ title: "تفاصيل التصنيف", body: `<div class="detail-grid">
      <div class="detail-item"><span>رقم التصنيف</span><strong>#${escapeHtml(data.CategoryID)}</strong></div>
      <div class="detail-item"><span>اسم التصنيف</span><strong>${escapeHtml(data.CategoryName)}</strong></div>
      <div class="detail-item" style="grid-column:1/-1"><span>الوصف</span><strong>${escapeHtml(data.Description || "—")}</strong></div>
      <div class="detail-item" style="grid-column:1/-1"><span>رابط الصورة</span><strong>${escapeHtml(data.URLImage || "—")}</strong></div>
    </div>` });
  } catch (error) { showToast(error.message, "error"); }
}

export async function renderCategories(container) {
  searchText = "";
  container.innerHTML = `<div class="loading-row">جارٍ تحميل التصنيفات...</div>`;
  container.innerHTML = `
    <div class="page-heading"><div><h1>التصنيفات</h1><p>نظّم الكتب ضمن رفوف واضحة تسهّل على القارئ الوصول إليها.</p></div><div class="heading-actions"><button class="btn btn-primary" id="add-category">${icon("plus")} إضافة تصنيف</button></div></div>
    <div class="card toolbar"><div class="search-box">${icon("search")}<input class="control" id="category-search" placeholder="ابحث باسم التصنيف أو وصفه..."></div><span class="muted" id="categories-count">جارٍ التحميل...</span></div>
    <div class="card table-card"><div class="table-scroll"><table><thead><tr><th>التصنيف</th><th>الوصف</th><th>الصورة</th><th>الإجراءات</th></tr></thead><tbody id="categories-body"><tr><td colspan="4" class="loading-row">جارٍ التحميل...</td></tr></tbody></table></div></div>`;
  try { await loadCategories(container); }
  catch (error) { container.querySelector("#categories-body").innerHTML = `<tr><td colspan="4"><div class="empty-state"><strong>تعذر تحميل التصنيفات</strong><span>${escapeHtml(error.message)}</span></div></td></tr>`; }

  container.querySelector("#add-category").addEventListener("click", () => openForm(container));
  container.querySelector("#category-search").addEventListener("input", (event) => { searchText = event.target.value; updateTable(container); });
  container.querySelector("#categories-body").addEventListener("click", async (event) => {
    const button = event.target.closest("button[data-action]"); if (!button) return;
    const id = Number(button.dataset.id); const action = button.dataset.action;
    if (action === "view") return viewCategory(id);
    if (action === "edit") return openForm(container, id);
    if (action === "delete") {
      const category = categories.find((c) => Number(c.CategoryID) === id);
      if (!confirm(`هل تريد حذف التصنيف "${category?.CategoryName || id}"؟ قد يرفض الخادم الحذف إذا كان التصنيف مرتبطاً بمنتجات.`)) return;
      try { await api.delete(`/admin/categories/${id}`); showToast("تم حذف التصنيف."); await loadCategories(container); }
      catch (error) { showToast(error.message, "error"); }
    }
  });
}
