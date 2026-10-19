/* admin-bar.js — نوار مدیریت در خود سایت؛ فقط بعد از ورودِ مدیر نمایش داده می‌شود و برای بقیه مخفی است */
(function () {
  const S = {
    admin: null,          // مدیر فعلی
    pagePost: null,       // پستِ همین صفحه (فقط در post.html)
    bar: null,
  };

  function isAdmin() { return S.admin !== null; }
  // پیشفرض: حالت ویرایش روشن است تا مدیر بفهمه دکمه‌ها روی کارت‌ها هست
  function editMode() { return localStorage.getItem('hn_edit_mode') !== '0'; }

  function injectCss() {
    const st = document.createElement('style');
    st.id = 'admin-bar-css';
    st.textContent = `
      #hn-admin-bar { position: fixed; bottom: 1rem; left: 1rem; z-index: 90; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 1rem; box-shadow: 0 12px 30px rgba(0,0,0,.15); padding: .65rem .8rem; display: flex; gap: .45rem; align-items: center; flex-wrap: wrap; font-family: 'Vazirmatn', sans-serif; }
      #hn-admin-bar .ab-title { font-size: .72rem; color: #16a34a; font-weight: 800; padding: 0 .25rem; white-space: nowrap; }
      #hn-admin-bar .ab-btn { display: inline-flex; align-items: center; gap: .3rem; font-size: .8rem; font-weight: 700; padding: .45rem .75rem; border-radius: .6rem; cursor: pointer; border: none; transition: all .15s; white-space: nowrap; text-decoration: none; }
      #hn-admin-bar .ab-green { background: #16a34a; color: #fff; } #hn-admin-bar .ab-green:hover { background: #15803d; }
      #hn-admin-bar .ab-gray { background: #f1f5f9; color: #334155; } #hn-admin-bar .ab-gray:hover { background: #e2e8f0; }
      #hn-admin-bar .ab-orange { background: #ffedd5; color: #c2410c; } #hn-admin-bar .ab-orange:hover { background: #fed7aa; }
      #hn-admin-bar i { font-size: 1.05rem; }
      .hn-chips { position: absolute; top: .5rem; left: .5rem; z-index: 20; display: flex; gap: .3rem; }
      .hn-chip { font-family: 'Vazirmatn', sans-serif; font-size: .72rem; font-weight: 700; padding: .3rem .55rem; border-radius: .5rem; border: none; cursor: pointer; box-shadow: 0 3px 10px rgba(0,0,0,.18); }
      .hn-chip-edit { background: #2563eb; color: #fff; }
      .hn-chip-pub  { background: #16a34a; color: #fff; }
      .hn-chip-unpub{ background: #f59e0b; color: #fff; }
      .hn-chip-del  { background: #ef4444; color: #fff; }
      .data-post-card, .data-admin-card { position: relative; }
      #hn-admin-dropdown { border-bottom: 1px solid #f3f4f6; }`;
    document.head.appendChild(st);
  }

  function toast(msg, isError = false) {
    const t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = `position:fixed;bottom:5.5rem;left:1rem;z-index:95;background:${isError ? '#dc2626' : '#16a34a'};color:#fff;padding:.7rem 1.2rem;border-radius:.6rem;font-family:'Vazirmatn',sans-serif;font-weight:700;box-shadow:0 8px 20px rgba(0,0,0,.2);`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
  }

  function api(action, data) {
    const fd = new FormData();
    fd.append('action', action);
    Object.entries(data || {}).forEach(([k, v]) => fd.append(k, v));
    fd.append('csrf_token', localStorage.getItem('csrf_token') || '');
    return fetch('/files/api/admin.php', { method: 'POST', body: fd }).then(r => r.json());
  }

  async function checkAdmin() {
    try {
      const r = await fetch('/files/api/auth.php', { method: 'POST', body: new URLSearchParams({ action: 'me' }) });
      const j = await r.json();
      if (j.success && j.data.role === 'admin') S.admin = j.data;
    } catch (e) { /* مخفیه برای همه */ }
  }

  async function checkPagePost() {
    const slug = new URLSearchParams(location.search).get('slug');
    if (!slug) return;
    try {
      const r = await fetch('/files/api/content.php?action=get_post&slug=' + encodeURIComponent(slug));
      const j = await r.json();
      if (j.success) S.pagePost = j.data;
    } catch (e) { /* ignore */ }
  }

  /* دکمه «پنل مدیریت» داخل منوی هدر (فقط برای admin) */
  function addHeaderMenuItem() {
    const menu = document.querySelector('#user-menu > div');
    if (!menu || document.getElementById('hn-admin-dropdown')) return;
    const a = document.createElement('a');
    a.id = 'hn-admin-dropdown';
    a.href = '/panel/dashboard.php';
    a.className = 'block px-4 py-3 text-gray-700 hover:bg-gray-100 font-bold';
    a.innerHTML = '<i class="ph-fill ph-shield-check text-green-600"></i> پنل مدیریت';
    menu.insertBefore(a, menu.firstChild);
  }

  function buildBar() {
    if (S.bar || !isAdmin()) return;
    const bar = document.createElement('div');
    bar.id = 'hn-admin-bar';

    let inner = `<span class="ab-title">🛡 حالت مدیر</span>
      <a class="ab-btn ab-green" href="/panel/dashboard.php"><i class="ph-fill ph-gear"></i>پنل مدیریت</a>
      <button class="ab-btn ab-green" id="ab-new-post"><i class="ph-fill ph-plus-circle"></i>خبر جدید</button>
      <button class="ab-btn ${editMode() ? 'ab-orange' : 'ab-gray'}" id="ab-edit-mode"><i class="ph-fill ${editMode() ? 'ph-pencil-simple-slash' : 'ph-pencil-simple'}"></i>حالت ویرایش: ${editMode() ? 'روشن' : 'خاموش'}</button>`;
    if (S.pagePost) {
      inner += `
      <span style="width:1px;height:1.6rem;background:#e2e8f0"></span>
      <a class="ab-btn ab-gray" href="/panel/posts.php?edit=${encodeURIComponent(S.pagePost.slug)}"><i class="ph-fill ph-pencil-simple"></i>ویرایش این خبر</a>
      <button class="ab-btn ab-gray" id="ab-toggle-pub"><i class="ph-fill ${S.pagePost.status === 'published' ? 'ph-eye-slash' : 'ph-eye'}"></i>${S.pagePost.status === 'published' ? 'لغو انتشار' : 'انتشار'}</button>
      <button class="ab-btn ab-orange" id="ab-del-post"><i class="ph-fill ph-trash"></i>حذف این خبر</button>`;
    }
    bar.innerHTML = inner;
    document.body.appendChild(bar);
    S.bar = bar;

    document.getElementById('ab-new-post').addEventListener('click', () => window.open('/panel/post_create.php', '_blank'));
    document.getElementById('ab-edit-mode').addEventListener('click', () => {
      localStorage.setItem('hn_edit_mode', editMode() ? '0' : '1');
      refreshChips();
      const btn = document.getElementById('ab-edit-mode');
      btn.innerHTML = `<i class="ph-fill ${editMode() ? 'ph-pencil-simple-slash' : 'ph-pencil-simple'}"></i>حالت ویرایش: ${editMode() ? 'روشن' : 'خاموش'}`;
      btn.className = `ab-btn ${editMode() ? 'ab-orange' : 'ab-gray'}`;
      toast(editMode() ? 'حالت ویرایش روشن شد — روی اخبار دکمه‌ها را می‌بینید.' : 'حالت ویرایش خاموش شد.');
    });

    if (S.pagePost) {
      document.getElementById('ab-del-post').addEventListener('click', async () => {
        if (!confirm('این خبر همراه عکس‌هایش برای همیشه حذف شود؟')) return;
        const r = await api('delete_post', { id: S.pagePost.id });
        toast(r.message || 'حذف شد.', !r.success);
        if (r.success) location.href = '/news/';
      });
      document.getElementById('ab-toggle-pub').addEventListener('click', async () => {
        const next = S.pagePost.status === 'published' ? 'pending' : 'published';
        if (next === 'published' && !confirm('این خبر برای همه قابل رویت خواهد بود. انتشار؟')) return;
        const r = await api('set_post_status', { id: S.pagePost.id, status: next });
        toast(r.message || 'انجام شد.', !r.success);
        if (r.success) setTimeout(() => location.reload(), 700);
      });
    }
  }

  /* دکمه‌های درج در کارت‌های خبر */
  function chip(btns, cls, icon, label, handler) {
    const b = document.createElement('button');
    b.className = 'hn-chip ' + cls;
    b.type = 'button';
    b.innerHTML = `<i class="ph-fill ${icon}"></i> ${label}`;
    b.addEventListener('click', async (e) => { e.preventDefault(); e.stopPropagation(); await handler(); });
    btns.appendChild(b);
  }

  /* بخش‌های محتوا دیگر: کادر آموزشی، سوالات متداول، امکانات، گالری */
  const SECTION_CONTROLS = [
    { sel: '[data-staff-id]',    key: 'staffId',   delAction: 'staff_delete',    editUrl: id => '/panel/staff.php?edit=' + id },
    { sel: '[data-faq-id]',      key: 'faqId',     delAction: 'faq_delete',      editUrl: id => '/panel/faq.php?edit=' + id },
    { sel: '[data-fac-id]',      key: 'facId',     delAction: 'facility_delete', editUrl: id => '/panel/facilities.php?edit=' + id },
    { sel: '[data-gallery-id]',  key: 'galleryId', delAction: 'gallery_delete',  editUrl: () => '/panel/gallery.php' },
  ];

  async function decorate() {
    if (!isAdmin() || !editMode()) return;
    for (const card of document.querySelectorAll('[data-post-slug]')) {
      if (card.querySelector('.hn-chips')) continue;
      card.classList.add('data-post-card');
      const chips = document.createElement('div');
      chips.className = 'hn-chips';
      const post = { id: card.dataset.postId, slug: card.dataset.postSlug };
      chip(chips, 'hn-chip-edit', 'ph-pencil-simple', 'ویرایش', () => window.open('/panel/posts.php?edit=' + encodeURIComponent(post.slug), '_blank'));
      chip(chips, 'hn-chip-del', 'ph-trash', 'حذف', async () => {
        if (!confirm('این خبر برای همیشه حذف شود؟')) return;
        const r = await api('delete_post', { id: post.id });
        toast(r.message || 'حذف شد.', !r.success);
        if (r.success) location.reload();
      });
      card.appendChild(chips);
    }
    for (const ctl of SECTION_CONTROLS) {
      for (const el of document.querySelectorAll(ctl.sel)) {
        if (el.querySelector('.hn-chips')) continue;
        el.classList.add('data-admin-card');
        const chips = document.createElement('div');
        chips.className = 'hn-chips';
        const id = el.dataset[ctl.key];
        chip(chips, 'hn-chip-edit', 'ph-pencil-simple', 'ویرایش', () => window.open(ctl.editUrl(id), '_blank'));
        chip(chips, 'hn-chip-del', 'ph-trash', 'حذف', async () => {
          if (!confirm('این مورد برای همیشه حذف شود؟')) return;
          const r = await api(ctl.delAction, { id: id });
          toast(r.message || 'حذف شد.', !r.success);
          if (r.success) location.reload();
        });
        el.appendChild(chips);
      }
    }
  }

  function refreshChips() {
    if (editMode()) { decorate(); }
    else { document.querySelectorAll('.hn-chips').forEach(el => el.remove()); }
  }

  /* کارت‌های خبر با فچ بعد از لود صفحه رندر می‌شوند؛ با تغییر DOM دکمه‌ها را به‌روز نگاه می‌داریم */
  let decoTimer = null;
  function watchPosts() {
    const mo = new MutationObserver(() => {
      if (!isAdmin() || !editMode()) return;
      if (decoTimer) return;
      decoTimer = setTimeout(() => { decoTimer = null; decorate(); }, 120);
    });
    mo.observe(document.body, { childList: true, subtree: true });
  }

  async function init() {
    injectCss();
    await Promise.all([checkAdmin(), checkPagePost()]);
    if (!isAdmin()) return; // برای کاربران عادی هیچ‌چیز رندر نمی‌شود
    addHeaderMenuItem();
    buildBar();
    watchPosts();
    await decorate();
    window.addEventListener('pageshow', () => refreshChips());
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
