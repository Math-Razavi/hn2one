/* _panel.js — منطق مشترک پنل: csrf، fetch، toast، سایدبار موبایل */
(function () {
  window.PANEL = {
    csrf() {
      return document.querySelector('meta[name="csrf-token"]')?.content || '';
    },
    async api(url, data = {}, method = 'POST') {
      const fd = data instanceof FormData ? data : new FormData();
      if (!(data instanceof FormData)) {
        Object.entries(data).forEach(([k, v]) => {
          if (v !== undefined && v !== null && typeof v !== 'object') fd.append(k, v);
          else if (typeof v === 'object') fd.append(k, JSON.stringify(v));
        });
      }
      if (!fd.has('csrf_token')) fd.append('csrf_token', PANEL.csrf());
      const res = await fetch(url, { method, body: fd });
      return res.json();
    },
    toast(text, isError = false) {
      const el = document.createElement('div');
      el.className = 'px-5 py-3 rounded-lg shadow-lg font-bold text-white text-sm flex items-center gap-2 ' + (isError ? 'bg-red-600' : 'bg-green-600');
      el.innerHTML = `<i class="ph-bold ${isError ? 'ph-warning-circle' : 'ph-check-circle'} text-xl"></i><span></span>`;
      el.querySelector('span').textContent = text;
      document.getElementById('toast-container')?.appendChild(el);
      setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .4s'; }, 3500);
      setTimeout(() => el.remove(), 4000);
    },
    jDate(iso) {
      try { return new Date(iso).toLocaleDateString('fa-IR', { year: 'numeric', month: 'long', day: 'numeric' }); }
      catch (e) { return iso || ''; }
    },
    esc(str) {
      const d = document.createElement('div');
      d.textContent = str == null ? '' : String(str);
      return d.innerHTML;
    },
    sidebar() {
      const sb = document.getElementById('sidebar');
      const ov = document.getElementById('sidebar-overlay');
      const btn = document.getElementById('sidebar-toggle');
      if (!btn || !sb) return;
      btn.addEventListener('click', () => {
        sb.classList.toggle('translate-x-full');
        ov.classList.toggle('hidden');
      });
      ov?.addEventListener('click', () => {
        sb.classList.add('translate-x-full');
        ov.classList.add('hidden');
      });
    },
    // تأیید حذف با پیام فارسی
    confirmDelete(msg = 'آیا مطمئن هستید؟') {
      return confirm(msg);
    },
  };
})();

function initPanelSidebar() {
  PANEL.sidebar();
}

function pDigits(s) {
  return String(s ?? '').replace(/[0-9]/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
}

// خلاصه‌های سراسری (صفحات پنل آن‌ها را به صورت خالی صدا می‌زنند)
window.esc = PANEL.esc;
window.jDate = PANEL.jDate;
