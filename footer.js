document.addEventListener("DOMContentLoaded", () => {
  fetch("/footer.html")
    .then(response => response.text())
    .then(data => {
      const container = document.getElementById("footer");
      if (!container) return;
      container.innerHTML = data;

      // پر کردن اطلاعات تماس از تنظیمات
      fetch("/files/api/settings.php?action=public")
        .then(r => r.json())
        .then(s => {
          if (!s.success) return;
          const d = s.data;
          const set = (id, val) => { const el = document.getElementById(id); if (el && val) el.textContent = val; };
          set("footer-phones", d.phones);
          set("footer-email", d.email);
          set("footer-address", d.address);
          const eitaa = document.getElementById("footer-eitaa");
          const shad = document.getElementById("footer-shad");
          if (eitaa && d.eitaa) eitaa.href = d.eitaa;
          if (shad && d.shad) shad.href = d.shad;
        })
        .catch(() => {});
    });
});
