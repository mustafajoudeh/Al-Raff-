import { api } from "../core/api.js";
import { escapeHtml, icon, showToast, showModal, closeModal, statusLabel, emptyState } from "../components/ui.js";

let allUsers = [];
let currentAdmin = null;
let filters = { search: "", role: "all", status: "all" };

function normalizeUser(user, fallbackRole) {
  return {
    ...user,
    role: user.role?.value ?? user.role ?? fallbackRole,
    userId: Number(user.userId ?? user.UserID),
    PhoneNumber: user.PhoneNumber ?? user.phoneNumber ?? null,
    status: user.status?.value ?? user.status ?? "Active",
  };
}

function userRows(users) {
  if (!users.length) return `<tr><td colspan="6">${emptyState("لا يوجد مستخدمون", "جرّب تغيير البحث أو الفلاتر.")}</td></tr>`;
  return users.map((user) => `
    <tr>
      <td><div class="cell-main"><strong>${escapeHtml(user.username)}</strong><span>#${user.userId}</span></div></td>
      <td>${escapeHtml(user.email)}</td>
      <td>${user.role === "Admin" ? '<span class="pill pill-warning">مسؤول</span>' : '<span class="pill pill-neutral">زبون</span>'}</td>
      <td>${statusLabel(user.status)}</td>
      <td>${escapeHtml(user.PhoneNumber || "—")}</td>
      <td><div class="table-actions">
        <button class="table-icon" data-action="view" data-id="${user.userId}" title="عرض">${icon("eye", 15)}</button>
        <button class="table-icon" data-action="edit" data-id="${user.userId}" title="تعديل">${icon("edit", 15)}</button>
        <button class="table-icon" data-action="password" data-id="${user.userId}" title="تغيير كلمة المرور">${icon("key", 15)}</button>
        ${user.status === "Active" ? `<button class="table-icon" data-action="deactivate" data-id="${user.userId}" title="تعطيل">${icon("ban", 15)}</button>` : `<button class="table-icon" data-action="activate" data-id="${user.userId}" title="تفعيل">${icon("check", 15)}</button>`}
        <button class="table-icon danger" data-action="delete" data-id="${user.userId}" title="حذف" ${user.userId === Number(currentAdmin?.userId) ? "disabled" : ""}>${icon("trash", 15)}</button>
      </div></td>
    </tr>`).join("");
}

function filteredUsers() {
  const search = filters.search.toLowerCase();
  return allUsers.filter((user) => {
    const matchesSearch = !search || [user.username, user.email, user.PhoneNumber, user.userId].some((v) => String(v ?? "").toLowerCase().includes(search));
    return matchesSearch && (filters.role === "all" || user.role === filters.role) && (filters.status === "all" || user.status === filters.status);
  });
}

function paintTable(container) {
  const filtered = filteredUsers();
  container.querySelector("#users-count").textContent = `عرض ${filtered.length} من ${allUsers.length} مستخدم`;
  container.querySelector("#users-body").innerHTML = userRows(filtered);
}

async function reloadUsers(container) {
  const [customersResult, adminsResult] = await Promise.allSettled([api.get("/admin/customers"), api.get("/admin/admins")]);
  if (customersResult.status === "rejected" || adminsResult.status === "rejected") {
    const error = customersResult.status === "rejected" ? customersResult.reason : adminsResult.reason;
    throw error;
  }
  const customers = (customersResult.value.data || []).map((u) => normalizeUser(u, "Customer"));
  const admins = (adminsResult.value.data || []).map((u) => normalizeUser(u, "Admin"));
  const current = currentAdmin ? normalizeUser({ ...currentAdmin, status: currentAdmin.status || "Active" }, "Admin") : null;
  const visibleAdmins = current && !admins.some((u) => u.userId === current.userId) ? [...admins, current] : admins;
  allUsers = [...customers, ...visibleAdmins].sort((a, b) => a.userId - b.userId);
  paintTable(container);
}

function userForm(user = null) {
  const editing = Boolean(user);
  const self = editing && user.userId === Number(currentAdmin?.userId);
  return `
    <form id="user-form" class="form-grid">
      <div class="form-field"><label for="user-username">اسم المستخدم *</label><input class="control" id="user-username" name="username" maxlength="50" required value="${escapeHtml(user?.username || "")}"></div>
      <div class="form-field"><label for="user-email">البريد الإلكتروني *</label><input class="control" id="user-email" name="email" type="email" required value="${escapeHtml(user?.email || "")}"></div>
      <div class="form-field"><label for="user-phone">رقم الهاتف</label><input class="control" id="user-phone" name="phoneNumber" value="${escapeHtml(user?.PhoneNumber || "")}"></div>
      <div class="form-field"><label for="user-role">نوع الحساب *</label><select class="control" id="user-role" name="role" required ${self ? "disabled" : ""}>
        <option value="Customer" ${user?.role === "Customer" ? "selected" : ""}>زبون</option><option value="Admin" ${user?.role === "Admin" ? "selected" : ""}>مسؤول</option></select></div>
      <div class="form-field"><label for="user-status">الحالة *</label><select class="control" id="user-status" name="status" required ${self ? "disabled" : ""}>
        <option value="Active" ${!user || user.status === "Active" ? "selected" : ""}>نشط</option><option value="InActive" ${user?.status === "InActive" ? "selected" : ""}>غير نشط</option></select></div>
      ${editing ? "" : `<div class="form-field"><label for="user-password">كلمة المرور *</label><input class="control" id="user-password" name="password" type="password" minlength="8" required autocomplete="new-password"></div>`}
      ${self ? `<div class="form-field full"><div class="notice">لا يمكنك تغيير صلاحية أو حالة حسابك الحالي. يمكنك تعديل الاسم والبريد والهاتف.</div></div>` : ""}
      <div class="form-error full" id="user-form-error" hidden></div>
    </form>`;
}

async function openUserView(id) {
  try {
    const { data } = await api.get(`/admin/users/${id}`);
    const user = normalizeUser(data, data?.role);
    showModal({ title: "بيانات المستخدم", body: `<div class="detail-grid">
      <div class="detail-item"><span>رقم المستخدم</span><strong>#${user.userId}</strong></div>
      <div class="detail-item"><span>اسم المستخدم</span><strong>${escapeHtml(user.username)}</strong></div>
      <div class="detail-item"><span>البريد الإلكتروني</span><strong>${escapeHtml(user.email)}</strong></div>
      <div class="detail-item"><span>رقم الهاتف</span><strong>${escapeHtml(user.PhoneNumber || "غير مسجل")}</strong></div>
      <div class="detail-item"><span>نوع الحساب</span><strong>${user.role === "Admin" ? "مسؤول" : "زبون"}</strong></div>
      <div class="detail-item"><span>الحالة</span><strong>${user.status === "Active" ? "نشط" : "غير نشط"}</strong></div>
    </div>` });
  } catch (error) { showToast(error.message, "error"); }
}

async function openUserForm(container, id = null) {
  let user = null;
  if (id !== null) {
    try { const response = await api.get(`/admin/users/${id}`); user = normalizeUser(response.data, response.data?.role); }
    catch (error) { showToast(error.message, "error"); return; }
  }
  const backdrop = showModal({
    title: user ? "تعديل بيانات المستخدم" : "إضافة مستخدم جديد",
    body: userForm(user),
    footer: `<button class="btn btn-outline" type="button" data-modal-close>إلغاء</button><button class="btn btn-primary" type="submit" form="user-form">${user ? "حفظ التعديلات" : "إنشاء المستخدم"}</button>`,
  });
  backdrop.querySelector("#user-form").addEventListener("submit", async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const errorBox = form.querySelector("#user-form-error");
    errorBox.hidden = true;
    const body = {
      username: form.username.value.trim(), email: form.email.value.trim(),
      phoneNumber: form.phoneNumber.value.trim(),
    };
    if (!user) {
      body.role = form.role.value;
      body.status = form.status.value;
      body.password = form.password.value;
    } else if (user.userId !== Number(currentAdmin?.userId)) {
      body.role = form.role.value;
      body.status = form.status.value;
    }
    try {
      if (!form.reportValidity()) return;
      if (user) await api.put(`/admin/users/${user.userId}`, body);
      else await api.post("/admin/users", body);
      closeModal();
      showToast(user ? "تم تحديث بيانات المستخدم." : "تم إنشاء المستخدم.");
      await reloadUsers(container);
    } catch (error) { errorBox.textContent = error.message; errorBox.hidden = false; }
  });
}

async function changePassword(container, id) {
  const backdrop = showModal({ title: "تغيير كلمة المرور", body: `<form id="password-form" class="form-grid"><div class="form-field full"><label for="new-password">كلمة المرور الجديدة *</label><input class="control" id="new-password" name="password" type="password" minlength="8" autocomplete="new-password" required></div><div class="form-error full" id="password-error" hidden></div></form>`, footer: `<button class="btn btn-outline" type="button" data-modal-close>إلغاء</button><button class="btn btn-primary" type="submit" form="password-form">حفظ كلمة المرور</button>` });
  backdrop.querySelector("#password-form").addEventListener("submit", async (event) => {
    event.preventDefault(); const form = event.currentTarget; const error = form.querySelector("#password-error"); error.hidden = true;
    if (!form.reportValidity()) return;
    try { await api.patch(`/admin/users/${id}/password`, { password: form.password.value }); closeModal(); showToast("تم تغيير كلمة المرور."); }
    catch (e) { error.textContent = e.message; error.hidden = false; }
  });
}

export async function renderUsers(container, user) {
  currentAdmin = user;
  filters = { search: "", role: "all", status: "all" };
  container.innerHTML = `<div class="loading-row">جارٍ تحميل المستخدمين...</div>`;
  container.innerHTML = `
    <div class="page-heading"><div><h1>المستخدمون</h1><p>إدارة حسابات الزبائن والمسؤولين وصلاحياتهم.</p></div><div class="heading-actions"><button class="btn btn-primary" id="add-user">${icon("plus")} إضافة مستخدم</button></div></div>
    <div class="card toolbar">
      <div class="search-box">${icon("search")}<input class="control" id="user-search" placeholder="ابحث بالاسم أو البريد أو رقم المستخدم..."></div>
      <select class="control filter-control" id="user-role-filter"><option value="all">كل أنواع الحسابات</option><option value="Customer">الزبائن</option><option value="Admin">المسؤولون</option></select>
      <select class="control filter-control" id="user-status-filter"><option value="all">كل الحالات</option><option value="Active">نشط</option><option value="InActive">غير نشط</option></select>
      <span class="muted" id="users-count">جارٍ التحميل...</span>
    </div>
    <div class="card table-card"><div class="table-scroll"><table><thead><tr><th>المستخدم</th><th>البريد الإلكتروني</th><th>نوع الحساب</th><th>الحالة</th><th>الهاتف</th><th>الإجراءات</th></tr></thead><tbody id="users-body"><tr><td colspan="6" class="loading-row">جارٍ التحميل...</td></tr></tbody></table></div></div>
  `;
  try { await reloadUsers(container); }
  catch (error) { container.querySelector("#users-body").innerHTML = `<tr><td colspan="6"><div class="empty-state"><strong>تعذر تحميل المستخدمين</strong><span>${escapeHtml(error.message)}</span></div></td></tr>`; }

  container.querySelector("#add-user").addEventListener("click", () => openUserForm(container));
  container.querySelector("#user-search").addEventListener("input", (event) => { filters.search = event.target.value; paintTable(container); });
  container.querySelector("#user-role-filter").addEventListener("change", (event) => { filters.role = event.target.value; paintTable(container); });
  container.querySelector("#user-status-filter").addEventListener("change", (event) => { filters.status = event.target.value; paintTable(container); });

  container.querySelector("#users-body").addEventListener("click", async (event) => {
    const button = event.target.closest("button[data-action]"); if (!button) return;
    const id = Number(button.dataset.id); const action = button.dataset.action;
    const target = allUsers.find((u) => u.userId === id);
    if (action === "view") return openUserView(id);
    if (action === "edit") return openUserForm(container, id);
    if (action === "password") return changePassword(container, id);
    if (action === "delete") {
      if (id === Number(currentAdmin?.userId)) return showToast("لا يمكنك حذف حسابك الحالي.", "error");
      if (!confirm(`هل أنت متأكد من حذف المستخدم ${target?.username || `#${id}`}؟`)) return;
      try { await api.delete(`/admin/users/${id}`); showToast("تم حذف المستخدم."); await reloadUsers(container); }
      catch (error) { showToast(error.message, "error"); }
    }
    if (action === "activate" || action === "deactivate") {
      const isActivate = action === "activate";
      if (!isActivate && id === Number(currentAdmin?.userId)) return showToast("لا يمكنك تعطيل حسابك الحالي.", "error");
      try { await api.patch(`/admin/users/${id}/${isActivate ? "activate" : "deactivate"}`, {}); showToast(isActivate ? "تم تفعيل الحساب." : "تم تعطيل الحساب."); await reloadUsers(container); }
      catch (error) { showToast(error.message, "error"); }
    }
  });
}
