/* site.js — منطق مشترک صفحات عمومی: وضعیت ورود، نوار اطلاعیه، ابزارها */
const SITE = {
  apiAuth: '/files/api/auth.php',
  apiContent: '/files/api/content.php',
  apiSettings: '/files/api/settings.php',
};

function esc(str) {
  const d = document.createElement('div');
  d.textContent = str == null ? '' : String(str);
  return d.innerHTML;
}

function jDate(iso) {
  try {
    return new Date(iso).toLocaleDateString('fa-IR', { year: 'numeric', month: 'long', day: 'numeric' });
  } catch (e) { return iso; }
}

/* ---------- وضعیت ورود در هدر ---------- */
async function initAuthUI() {
  const loginBtn = document.getElementById('login-btn');
  const userMenu = document.getElementById('user-menu');
  const usernameSpan = document.getElementById('header-username');
  const logoutBtn = document.getElementById('logout-btn');
  if (!loginBtn || !userMenu) return;

  const setLoggedOut = () => {
    loginBtn.classList.remove('hidden');
    userMenu.classList.add('hidden');
  };
  const setLoggedIn = (user) => {
    loginBtn.classList.add('hidden');
    userMenu.classList.remove('hidden');
    usernameSpan.textContent = user.name;
    localStorage.setItem('userName', user.name);
    localStorage.setItem('userRole', user.role);
  };

  try {
    const res = await fetch(SITE.apiAuth, { method: 'POST', body: new URLSearchParams({ action: 'me' }) });
    const result = await res.json();
    if (result.success) {
      setLoggedIn(result.data);
      localStorage.setItem('userCode', result.data.code);
      localStorage.setItem('userRole', result.data.role);
      if (result.data.class) localStorage.setItem('userClass', result.data.class); else localStorage.removeItem('userClass');
      if (result.data.csrf_token) localStorage.setItem('csrf_token', result.data.csrf_token);
    } else {
      setLoggedOut();
    }
  } catch (e) {
    setLoggedOut();
  }

  logoutBtn?.addEventListener('click', async () => {
    await fetch(SITE.apiAuth, { method: 'POST', body: new URLSearchParams({ action: 'logout' }) });
    localStorage.clear();
    window.location.href = '/index.html';
  });
}

/* ---------- منوی موبایل ---------- */
function initMobileMenu() {
  const btn = document.getElementById('mobile-menu-btn');
  const menu = document.getElementById('mobile-menu');
  if (!btn || !menu) return;
  btn.addEventListener('click', () => menu.classList.toggle('hidden'));
  menu.querySelectorAll('a').forEach(a => a.addEventListener('click', () => menu.classList.add('hidden')));
}

/* ---------- نوار اطلاعیه متحرک ---------- */
async function initTicker() {
  const el = document.getElementById('ticker-text');
  if (!el) return;
  let text = '';
  try {
    const res = await fetch(SITE.apiContent + '?action=get_announcements');
    const result = await res.json();
    const items = (result.data || []).map(x => x.text);
    if (items.length) {
      text = items[Math.floor(Math.random() * items.length)];
    }
  } catch (e) { /* ignore */ }
  if (!text) {
    try {
      const res2 = await fetch(SITE.apiSettings + '?action=public');
      const s = await res2.json();
      text = s.success ? s.data.ticker : '';
    } catch (e) { /* ignore */ }
  }
  if (!text) {
    el.closest('section')?.classList.add('hidden');
    return;
  }
  el.textContent = text;
}

/* ---------- FAQ آکاردئون ---------- */
function bindAccordion() {
  document.querySelectorAll('[data-accordion] button[data-q]').forEach(btn => {
    btn.addEventListener('click', () => {
      const panel = btn.parentElement.querySelector('[data-a]');
      panel.classList.toggle('hidden');
      btn.querySelector('.ph-caret-down')?.classList.toggle('rotate-180');
    });
  });
}

/* ---------- لایت‌باکس تصاویر ---------- */
function openLightbox(src) {
  const lb = document.getElementById('lightbox');
  const img = document.getElementById('lightbox-img');
  if (!lb || !img) return;
  img.src = src;
  lb.classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}
function initLightbox() {
  const lb = document.getElementById('lightbox');
  if (!lb) return;
  lb.addEventListener('click', () => { lb.classList.add('hidden'); document.body.style.overflow = ''; });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') { lb.classList.add('hidden'); document.body.style.overflow = ''; }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initTicker();
  bindAccordion();
  initLightbox();
});
