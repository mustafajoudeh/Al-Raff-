import { auth } from "../core/auth.js";
import { icon, showToast, escapeHtml } from "../components/ui.js";

function brandMark() {
  return `<svg width="54" height="62" viewBox="0 0 64 74" fill="none" aria-hidden="true"><path d="M6 68V32C6 17 19 8 32 1c13 7 26 16 26 31v36M13 68V34C13 22 22 14 32 8c10 6 19 14 19 26v34" stroke="#D2A862" stroke-width="2.4"/><g fill="currentColor"><rect x="19" y="38" width="5" height="22" rx="1"/><rect x="26" y="30" width="5" height="30" rx="1"/><rect x="33" y="40" width="5" height="20" rx="1"/><rect x="41" y="36" width="5" height="24" rx="1" transform="rotate(-12 41 60)"/></g><path d="M13 62h38" stroke="#D2A862" stroke-width="2"/></svg>`;
}

function authLayout(content) {
  return `<main class="auth-page">
    <section class="auth-art">
      <div class="auth-brand">${brandMark()}<div class="brand-name"><strong>الرَّف</strong><small>AL-RAFF · BOOKS</small></div></div>
      <div class="auth-message"><h1>لكل كتاب مكان، ولكل قارئ حكاية.</h1><p>إدارة هادئة ومنظّمة لمكتبة الرَّف، من تفاصيل الكتب إلى إدارة المستخدمين والتصنيفات.</p></div>
      <div class="auth-art-foot">الرَّف · مكتبة القارئ المتأنّي</div>
    </section>
    <section class="auth-side"><div class="auth-card">${content}</div></section>
  </main>`;
}

export function renderLogin(root, { onLogin }) {
  root.innerHTML = authLayout(`
    <div class="auth-small-logo">${brandMark()}</div>
    <h2>مرحباً بعودتك</h2>
    <p>سجّل الدخول إلى لوحة إدارة الرَّف.</p>
    <form class="auth-form" id="login-form" novalidate>
      <div class="form-field"><label for="login-email">البريد الإلكتروني</label><input class="control" id="login-email" name="email" type="email" autocomplete="username" placeholder="name@example.com" required></div>
      <div class="form-field"><label for="login-password">كلمة المرور</label><div class="password-wrap"><input class="control" id="login-password" name="password" type="password" autocomplete="current-password" placeholder="أدخل كلمة المرور" required><button class="password-toggle" type="button" data-toggle-password="login-password" aria-label="إظهار كلمة المرور">◉</button></div></div>
      <div id="login-error" class="form-error" hidden></div>
      <button class="btn btn-primary" type="submit" id="login-submit">تسجيل الدخول ${icon("arrow")}</button>
    </form>
    <div class="auth-links"><span>ليس لديك حساب؟</span><a href="#/register">عرض صفحة التسجيل</a></div>
    <div class="notice" style="margin-top:20px">دخول لوحة الإدارة مخصّص للحسابات التي تحمل صلاحية <b>Admin</b>.</div>
  `);

  root.querySelectorAll("[data-toggle-password]").forEach((button) => {
    button.addEventListener("click", () => {
      const input = document.getElementById(button.dataset.togglePassword);
      input.type = input.type === "password" ? "text" : "password";
    });
  });

  root.querySelector("#login-form").addEventListener("submit", async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const email = form.email.value.trim();
    const password = form.password.value;
    const errorBox = root.querySelector("#login-error");
    const submit = root.querySelector("#login-submit");
    errorBox.hidden = true;
    if (!email || !form.email.checkValidity() || !password) {
      errorBox.textContent = "أدخل بريداً إلكترونياً صحيحاً وكلمة المرور.";
      errorBox.hidden = false;
      return;
    }
    submit.disabled = true;
    submit.textContent = "جارٍ تسجيل الدخول...";
    try {
      const user = await auth.login(email, password);
      if (user?.role !== "Admin") {
        await auth.logout().catch(() => {});
        throw new Error("تم تسجيل الدخول، لكن هذا الحساب لا يملك صلاحية إدارة المتجر.");
      }
      await onLogin(user);
    } catch (error) {
      errorBox.textContent = error.message || "تعذر تسجيل الدخول.";
      errorBox.hidden = false;
      submit.disabled = false;
      submit.innerHTML = `تسجيل الدخول ${icon("arrow")}`;
    }
  });
}

export function renderRegistration(root) {
  root.innerHTML = authLayout(`
    <div class="auth-small-logo">${brandMark()}</div>
    <h2>إنشاء حساب</h2>
    <p>ابدأ رحلتك مع مكتبة الرَّف.</p>
    <div class="notice warning" style="margin-bottom:18px">التسجيل الذاتي غير مفعّل حالياً؛ لا يوجد في الـ API endpoint باسم <code>/auth/register</code>. لا يمكن إرسال هذا النموذج حتى تتم إضافته في الخادم.</div>
    <form class="auth-form" id="register-form">
      <div class="form-field"><label>الاسم</label><input class="control" name="username" placeholder="الاسم الكامل" disabled></div>
      <div class="form-field"><label>البريد الإلكتروني</label><input class="control" name="email" type="email" placeholder="name@example.com" disabled></div>
      <div class="form-field"><label>رقم الهاتف</label><input class="control" name="phoneNumber" placeholder="07xxxxxxxx" disabled></div>
      <div class="form-field"><label>كلمة المرور</label><input class="control" name="password" type="password" placeholder="اختر كلمة مرور" disabled></div>
      <button class="btn btn-primary" type="button" disabled>التسجيل غير متاح حالياً</button>
    </form>
    <div class="auth-links"><span>لديك حساب بالفعل؟</span><a href="#/login">العودة لتسجيل الدخول</a></div>
  `);
}
