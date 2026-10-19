/* header.js — تزریق هدر مشترک + فعال‌سازی منو بر اساس صفحه فعلی */
document.addEventListener("DOMContentLoaded", () => {
  const container = document.getElementById("header");
  if (!container) return;

  fetch("/header.html")
    .then(r => r.text())
    .then(html => {
      container.innerHTML = html;
      highlightNav();
      initAuthUI();
      initMobileMenu();
      document.dispatchEvent(new CustomEvent('header:ready'));
    })
    .catch(() => {});
});

function highlightNav() {
  const path = window.location.pathname;
  const key =
    path.includes('/news/') || path.includes('post.html') ? 'news' :
    path.includes('gallery.html') ? 'gallery' :
    path.includes('staff.html') ? 'staff' :
    path.includes('about.html') ? 'about' :
    path.includes('contact.html') ? 'contact' :
    path.includes('register.html') ? 'register' :
    path.includes('/azmoon/') ? 'azmoon' :
    (path.includes('home.html') || path === '/' || path.includes('index.html')) ? 'home' : null;

  document.querySelectorAll('[data-nav]').forEach(a => {
    if (a.dataset.nav === key) {
      a.classList.add('text-green-600');
      if (a.hasAttribute('data-nav')) a.classList.add('ring-2', 'ring-green-200');
    }
  });
}
