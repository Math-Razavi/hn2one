/* =========================
   breadcrumb.js — نان‌ریز (مسیر صفحه)
   ========================= */

/* ---------- 1. mapping ----------
   key : {
        label : برچسب فارسی
        link  : آدرس (اختیاری، اگر نباشد از مسیر فعلی استفاده می‌شود)
   } ---------- */
const mapping = {
  'index.html': 'صفحه اصلی',
  'home.html': { label: 'صفحه اصلی', link: '/home.html' },
  'news': 'اخبار و گزارشات',
  'post.html': 'متن خبر',
  'gallery.html': 'ببین مدرسه رو!',
  'staff.html': 'کادر آموزشی',
  'about.html': 'درباره ما',
  'contact.html': 'ارتباط با ما',
  'register.html': 'فرم ثبت‌نام',
  'auth': 'ورود',
  'login.html': 'ورود',
  'profile.html': 'پروفایل کاربری',
  'azmoon': 'آزمون آنلاین',
  'student.html': 'پنل دانش‌آموز',
  'teacher.html': 'پنل دبیر',
  'index.html': ' ',
};

/* ---------- 2. تولید breadcrumb ---------- */
document.addEventListener('DOMContentLoaded', () => {
  const parts = window.location.pathname
    .split('/')
    .filter(Boolean);

  if (!parts.length) return;

  const nav = document.createElement('nav');
  nav.setAttribute('aria-label', 'breadcrumb');
  nav.className = 'breadcrumb-nav';

  const ul = document.createElement('ul');
  ul.className = 'breadcrumb';
  nav.appendChild(ul);

  let accumulatedPath = '/';
  parts.forEach((segment, idx) => {
    accumulatedPath += segment;
    const li = document.createElement('li');
    li.className = 'breadcrumb-item';

    const mapEntry = mapping[segment];
    let label, link;

    if (typeof mapEntry === 'object' && mapEntry !== null) {
      label = mapEntry.label;
      link = mapEntry.link || accumulatedPath;
    } else if (mapEntry !== undefined) {
      label = mapEntry;
      link = accumulatedPath;
    } else {
      label = decodeURIComponent(segment)
        .replace(/\.html?$/, '')
        .replace(/-/g, ' ');
      link = accumulatedPath;
    }

    if (idx === parts.length - 1) {
      li.classList.add('active');
      li.textContent = label;
      li.setAttribute('aria-current', 'page');
    } else {
      const a = document.createElement('a');
      a.href = link;
      a.textContent = label;
      li.appendChild(a);
    }
    ul.appendChild(li);
  });

  const header = document.querySelector('header');
  if (header) {
    header.parentNode.insertBefore(nav, header.nextSibling);
  } else {
    document.body.insertBefore(nav, document.body.firstChild);
  }
});

/* ---------- 3. افزودن استایل CSS ---------- */
(function addStyles() {
  const css = `
  .breadcrumb-nav {
    border-bottom: 1px solid #e5e7eb;
    padding: 0.5rem 2rem;
    margin-bottom: 2rem;
  }
  .breadcrumb {
    list-style: none;
    display: flex;
    flex-wrap: wrap;
    margin: 0;
    padding: 0;
  }
  .breadcrumb-item + .breadcrumb-item::before {
    content: "/";
    margin: 0 0.5rem;
    color: #9ca3af;
  }
  .breadcrumb-item a {
    color: #4c4c4c;
    text-decoration: none;
  }
  .breadcrumb-item a:hover {
    text-decoration: underline;
    color: #16a34a;
  }
  .breadcrumb-item.active {
    color: #4c4c4c;
  }
  @media (max-width: 640px) {
    .breadcrumb-nav { padding: 0.5rem 1rem; }
  }
  `;
  const style = document.createElement('style');
  style.textContent = css;
  document.head.appendChild(style);
})();
