import { icon, escapeHtml } from "../components/ui.js";

export function renderComingSoon(container, section) {
  const isOrders = section === "orders";
  const title = isOrders ? "الطلبات" : "المدفوعات";
  const description = isOrders
    ? "ستتوفر هنا متابعة طلبات العملاء وحالات تجهيزها وشحنها بعد اكتمال وحدة الطلبات في الخادم."
    : "ستتوفر هنا تفاصيل عمليات الدفع وسجلاتها بعد اكتمال وحدة المدفوعات في الخادم.";
  container.innerHTML = `
    <div class="page-heading"><div><h1>${title}</h1><p>إدارة ${title} في متجر الرَّف.</p></div></div>
    <section class="card coming"><div><div class="coming-icon">${icon(isOrders ? "cart" : "card", 32)}</div><span class="pill pill-warning">غير جاهزة حالياً</span><h2>نعمل على تجهيز هذه الصفحة</h2><p>${escapeHtml(description)}</p><p class="muted" style="font-size:.78rem;margin-top:13px">لن نعرض بيانات تجريبية أو عمليات غير متاحة حالياً.</p></div></section>`;
}
