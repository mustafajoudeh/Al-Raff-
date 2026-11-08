import { api } from "../core/api.js";
import { escapeHtml, icon, formatJOD } from "../components/ui.js";

const categoryColors = ["#6B2137", "#27456F", "#5E6B3A", "#3F4752", "#1F5C5A", "#96691F"];
const pick = (item, ...keys) => {
  for (const key of keys) if (item && item[key] !== undefined && item[key] !== null) return item[key];
  return "";
};
const asList = (data) => Array.isArray(data) ? data : Array.isArray(data?.items) ? data.items : [];

function brandMark() {
  return `<svg class="brand-mark" viewBox="0 0 64 74" fill="none" aria-hidden="true">
    <path d="M6 68V32C6 17 19 8 32 1c13 7 26 16 26 31v36M13 68V34C13 22 22 14 32 8c10 6 19 14 19 26v34" stroke="currentColor" stroke-width="2.4"/>
    <g fill="currentColor"><rect x="19" y="38" width="5" height="22" rx="1"/><rect x="26" y="30" width="5" height="30" rx="1"/><rect x="33" y="40" width="5" height="20" rx="1"/><rect x="41" y="36" width="5" height="24" rx="1" transform="rotate(-12 41 60)"/></g>
    <path d="M13 62h38" stroke="currentColor" stroke-width="2"/>
  </svg>`;
}

function categoryCard(category, index) {
  const name = pick(category, "CategoryName", "categoryName") || "تصنيف";
  const description = pick(category, "Description", "description");
  const image = pick(category, "URLImage", "urlImage");
  const safeImage = typeof image === "string" && /^https?:\/\//i.test(image) ? image : "";
  const color = categoryColors[index % categoryColors.length];
  return `<button class="category-card" type="button" data-category="${escapeHtml(name)}" style="--category-color:${color}">
    <span class="category-card-art">${safeImage ? `<img src="${escapeHtml(safeImage)}" alt="" loading="lazy" onerror="this.remove()">` : `<span class="category-glyph">${icon("book-open", 30)}</span>`}</span>
    <span class="category-card-copy"><strong>${escapeHtml(name)}</strong><small>${escapeHtml(description || "اكتشف كتب هذا الرف")}</small></span>
    <span class="category-card-arrow">${icon("arrow", 17)}</span>
  </button>`;
}

function productCard(product, index, categories) {
  const name = pick(product, "ProductName", "productName") || "كتاب";
  const author = pick(product, "Author", "author") || "";
  const price = pick(product, "Price", "price");
  const stock = Number(pick(product, "Stock", "stock"));
  const categoryId = pick(product, "CategoryID", "categoryId");
  const categoryName = pick(product, "CategoryName", "categoryName") || categories.find((c) => String(pick(c, "CategoryID", "categoryId")) === String(categoryId))?.CategoryName || "من مكتبتنا";
  const color = categoryColors[index % categoryColors.length];
  return `<article class="book-card" style="--book-color:${color}">
    <div class="book-card-cover"><span class="book-cover-label">${escapeHtml(categoryName)}</span><span class="book-cover-title">${escapeHtml(name)}</span><span class="book-cover-brand">الرَّف · AL-RAFF</span></div>
    <div class="book-card-info"><span class="book-category">${escapeHtml(categoryName)}</span><h3>${escapeHtml(name)}</h3><p>${escapeHtml(author || "مكتبة الرَّف")}</p>
      <div class="book-card-bottom"><strong>${price !== "" ? formatJOD(price) : "السعر عند الطلب"}</strong><span class="stock-hint ${stock > 0 ? "in-stock" : "out-stock"}">${stock > 0 ? "متوفر" : "غير متوفر"}</span></div>
    </div>
  </article>`;
}

export async function renderHome(root, { isActive = () => true } = {}) {
  root.innerHTML = `
    <header class="site-header">
      <div class="site-header-inner container">
        <a class="site-brand" href="#/" aria-label="الرَّف - الرئيسية">${brandMark()}<span><strong>الرَّف</strong><small>AL-RAFF · BOOKS</small></span></a>
        <nav class="site-nav" aria-label="التنقل الرئيسي">
          <button type="button" data-scroll="home">الرئيسية</button>
          <button type="button" data-scroll="categories">الأقسام</button>
          <button type="button" data-scroll="catalog">مكتبتنا</button>
        </nav>
        <div class="site-actions"><a class="site-login" href="#/login">تسجيل الدخول</a><a class="btn btn-primary site-register" href="#/register">إنشاء حساب</a></div>
      </div>
    </header>

    <main>
      <section class="home-hero" id="home">
        <div class="home-hero-pattern" aria-hidden="true"></div>
        <div class="container home-hero-inner">
          <div class="home-hero-copy">
            <span class="eyebrow-light">مكتبة القارئ المتأنّي</span>
            <h1>بعض الكتب لا تنتهي<br><em>حين نغلق صفحاتها.</em></h1>
            <p>مكانٌ للكتب التي تستحق أن نمكث معها. تصفّح مختارات من الأدب والفكر والشعر، واختر كتابك التالي على مهل.</p>
            <div class="home-hero-actions"><button class="btn btn-accent btn-large" type="button" data-scroll="catalog">تصفّح الكتب ${icon("arrow", 18)}</button><button class="hero-text-link" type="button" data-scroll="categories">اكتشف رفوفنا <span>←</span></button></div>
            <div class="hero-note"><span class="hero-note-line"></span><span>قراءة هادئة، واختيار يبقى.</span></div>
          </div>
          <div class="hero-book-scene" aria-label="مجموعة كتب على رف">
            <div class="hero-arch"><div class="arch-inner"><div class="hero-shelf-books"><i></i><i></i><i></i><i></i><i></i></div></div></div>
            <div class="hero-floor"></div>
            <span class="scene-caption">A MORE PERSONAL BOOKSHELF</span>
          </div>
        </div>
        <div class="hero-bottom-rule"></div>
      </section>

      <section class="home-intro container">
        <div class="intro-mark">“</div><div><span class="eyebrow">عن الرَّف</span><p>نؤمن أن الكتاب الجيد لا يحتاج إلى استعجال. هنا نرتّب الكتب كما تُرتّب على رفّ قارئٍ يعرف ما يحب، ويترك مساحة دائماً لاكتشاف جديد.</p></div>
      </section>

      <section class="home-section container" id="categories">
        <div class="section-heading"><div><span class="eyebrow">تصفّح كما تحب</span><h2>لكل اهتمامٍ رفّ</h2><p>ابدأ من القسم الأقرب إلى فضولك.</p></div><span class="section-aside">رفوف الرَّف</span></div>
        <div class="category-grid" id="home-categories"><div class="home-loading">جارٍ تحميل التصنيفات...</div></div>
      </section>

      <section class="home-feature-band"><div class="container feature-band-inner"><div><span class="eyebrow-light">اختيارك القادم</span><h2>لا تبحث عن كتابٍ أكثر.<br>ابحث عن كتابك التالي.</h2></div><p>تجوّل بين الرفوف، اقرأ نبذة الكتاب، واترك للصفحات أن تختار وقتها.</p><button class="btn btn-accent" type="button" data-scroll="catalog">اكتشف المكتبة ${icon("arrow", 17)}</button></div></section>

      <section class="home-section container catalog-section" id="catalog">
        <div class="section-heading"><div><span class="eyebrow">من مكتبتنا</span><h2>كتب تستحق مكاناً على رفّك</h2><p>مجموعة الكتب المتاحة في مكتبة الرَّف.</p></div><span class="section-aside" id="catalog-count">— كتاب</span></div>
        <div class="catalog-tools"><label class="catalog-search">${icon("search", 17)}<input id="home-search" type="search" placeholder="ابحث عن كتاب أو مؤلف..." autocomplete="off"></label><select id="home-category-filter" class="catalog-filter"><option value="all">كل التصنيفات</option></select></div>
        <div class="book-grid" id="home-products"><div class="home-loading">جارٍ تحميل الكتب...</div></div>
        <div class="catalog-empty" id="catalog-empty" hidden>لا توجد كتب تطابق بحثك. جرّب كلمة أخرى أو اختر تصنيفاً مختلفاً.</div>
      </section>

      <section class="home-quote container"><div class="quote-rule"></div><p>«خيرُ جليسٍ في الزمانِ كتابُ»</p><span>الرَّف · مكتبة القارئ المتأنّي</span><div class="quote-rule"></div></section>
    </main>

    <footer class="site-footer"><div class="container footer-inner"><a class="site-brand footer-brand" href="#/">${brandMark()}<span><strong>الرَّف</strong><small>AL-RAFF · BOOKS</small></span></a><p>مكتبة القارئ المتأنّي.</p><div class="footer-links"><a href="#/login">دخول المسؤولين</a><a href="#/register">إنشاء حساب</a></div><small class="copyright">© ${new Date().getFullYear()} الرَّف. جميع الحقوق محفوظة.</small></div></footer>
  `;

  const categoryRoot = root.querySelector("#home-categories");
  const productsRoot = root.querySelector("#home-products");
  let categories = [];
  let products = [];

  root.querySelectorAll("[data-scroll]").forEach((button) => button.addEventListener("click", () => {
    root.querySelector(`#${button.dataset.scroll}`)?.scrollIntoView({ behavior: "smooth", block: "start" });
  }));

  const applyFilters = () => {
    const search = root.querySelector("#home-search").value.trim().toLocaleLowerCase("ar");
    const category = root.querySelector("#home-category-filter").value;
    const filtered = products.filter((product) => {
      const name = String(pick(product, "ProductName", "productName")).toLocaleLowerCase("ar");
      const author = String(pick(product, "Author", "author")).toLocaleLowerCase("ar");
      const categoryId = String(pick(product, "CategoryID", "categoryId"));
      const categoryName = String(pick(product, "CategoryName", "categoryName"));
      return (!search || `${name} ${author} ${categoryName}`.includes(search)) && (category === "all" || categoryId === category || categoryName === category);
    });
    productsRoot.innerHTML = filtered.length ? filtered.slice(0, 12).map((product, index) => productCard(product, index, categories)).join("") : "";
    root.querySelector("#catalog-empty").hidden = filtered.length > 0;
    root.querySelector("#catalog-count").textContent = `${filtered.length} كتاب`;
  };

  try {
    const [categoryResult, productResult] = await Promise.allSettled([api.get("/categories"), api.get("/products")]);
    if (!isActive()) return;
    if (categoryResult.status === "fulfilled") categories = asList(categoryResult.value.data);
    if (productResult.status === "fulfilled") products = asList(productResult.value.data);

    if (categories.length) {
      categoryRoot.innerHTML = categories.slice(0, 6).map(categoryCard).join("");
      root.querySelector("#home-category-filter").innerHTML = `<option value="all">كل التصنيفات</option>${categories.map((category) => {
        const id = pick(category, "CategoryID", "categoryId");
        const name = pick(category, "CategoryName", "categoryName");
        return `<option value="${escapeHtml(id || name)}">${escapeHtml(name)}</option>`;
      }).join("")}`;
      categoryRoot.querySelectorAll("[data-category]").forEach((button) => button.addEventListener("click", () => {
        const match = categories.find((category) => String(pick(category, "CategoryName", "categoryName")) === button.dataset.category);
        const id = pick(match, "CategoryID", "categoryId");
        root.querySelector("#home-category-filter").value = String(id || button.dataset.category);
        applyFilters();
        root.querySelector("#catalog").scrollIntoView({ behavior: "smooth" });
      }));
    } else {
      categoryRoot.innerHTML = `<div class="home-empty-note">لا توجد تصنيفات منشورة حالياً.</div>`;
    }

    if (!products.length && productResult.status === "rejected") {
      productsRoot.innerHTML = `<div class="home-empty-note">تعذر تحميل الكتب حالياً. ${escapeHtml(productResult.reason?.message || "تحقق من اتصال الـ API.")}</div>`;
    } else {
      applyFilters();
    }
    if (!categories.length && categoryResult.status === "rejected") {
      categoryRoot.innerHTML = `<div class="home-empty-note">تعذر تحميل التصنيفات حالياً. ${escapeHtml(categoryResult.reason?.message || "تحقق من اتصال الـ API.")}</div>`;
    }
  } catch (error) {
    categoryRoot.innerHTML = `<div class="home-empty-note">${escapeHtml(error.message)}</div>`;
    productsRoot.innerHTML = `<div class="home-empty-note">تعذر تحميل محتوى المكتبة.</div>`;
  }

  root.querySelector("#home-search").addEventListener("input", applyFilters);
  root.querySelector("#home-category-filter").addEventListener("change", applyFilters);
}
