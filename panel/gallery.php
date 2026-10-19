<?php
// gallery.php — مدیریت گالری تصاویر
require_once '_guard.php';

$PANEL_TITLE = 'گالری تصاویر';
$PANEL_ACTIVE = 'gallery';
require '_top.php';
?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- افزودن -->
        <div class="bg-white rounded-2xl shadow-sm border p-6 space-y-4 h-fit">
            <h2 class="text-lg font-extrabold text-gray-800 flex items-center gap-2"><i class="ph-duotone ph-plus-circle text-xl text-green-600"></i> افزودن عکس</h2>
            <div>
                <label class="block mb-1 text-sm font-bold">سکشن (مثل سردرب و ورودی مدرسه)</label>
                <input id="g-section" list="g-sections" class="w-full p-3 border border-gray-300 rounded-lg" placeholder="بنویس یا از لیست انتخاب کن">
                <datalist id="g-sections"></datalist>
            </div>
            <div>
                <label class="block mb-1 text-sm font-bold">عکس‌ها (چندگانه)</label>
                <input id="g-files" type="file" multiple accept="image/jpeg,image/png,image/webp"
                       class="w-full p-2 border border-gray-300 rounded-lg file:mr-3 file:px-4 file:py-1 file:rounded-lg file:border-0 file:bg-green-100 file:text-green-800 file:font-bold">
            </div>
            <button id="g-add-btn" class="w-full bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 shadow">افزودن</button>
        </div>

        <!-- لیست -->
        <div class="lg:col-span-2 space-y-4">
            <div class="flex flex-wrap justify-between items-center gap-3">
                <h2 class="text-lg font-extrabold text-gray-800 flex items-center gap-2"><i class="ph-duotone ph-images text-xl text-green-600"></i> عکس‌های موجود <span id="g-count" class="text-sm text-gray-400 font-normal"></span></h2>
                <select id="g-filter" class="bg-white p-2 border border-gray-300 rounded-lg text-sm"><option value="">همه سکشن‌ها</option></select>
            </div>
            <div id="g-grid" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <div class="col-span-full text-center py-10 text-gray-400">در حال بارگذاری...</div>
            </div>
        </div>
    </div>

<div id="lightbox" class="hidden fixed inset-0 z-[100] bg-black/85 items-center justify-center p-4 cursor-zoom-out">
    <img id="lightbox-img" class="max-w-full max-h-full rounded-xl object-contain" alt="">
</div>

<?php require '_bottom.php'; ?>
<script>
let sections = [];

async function loadSections() {
    const r = await (await fetch('/files/api/content.php?action=get_gallery_sections')).json();
    sections = r.data || [];
    const dl = document.getElementById('g-sections');
    dl.innerHTML = sections.map(s => `<option value="${esc(s)}">`).join('');
    const filter = document.getElementById('g-filter');
    filter.innerHTML = '<option value="">همه سکشن‌ها</option>' + sections.map(s => `<option value="${esc(s)}">${esc(s)}</option>`).join('');
}

async function loadGallery() {
    const filter = document.getElementById('g-filter').value;
    const url = filter ? '/files/api/content.php?action=get_gallery&section=' + encodeURIComponent(filter) : '/files/api/content.php?action=get_gallery';
    const r = await (await fetch(url)).json();
    const grid = document.getElementById('g-grid');
    document.getElementById('g-count').textContent = '(' + pDigits((r.data || []).length) + ' عکس)';
    if (!(r.data || []).length) {
        grid.innerHTML = '<div class="col-span-full text-center py-10 text-gray-400">عکسی نیست.</div>';
        return;
    }
    grid.innerHTML = '';
    r.data.forEach(g => {
        const box = document.createElement('div');
        box.className = 'relative group';
        box.innerHTML = `<img src="${esc(g.src)}" alt="" class="w-full h-36 object-cover rounded-xl border cursor-zoom-in" loading="lazy">
            <div class="absolute top-2 left-2 flex gap-1 opacity-0 group-hover:opacity-100 transition">
                <button data-edit="${g.id}" class="bg-blue-600 text-white rounded-lg px-2 py-1 text-xs font-bold">ویرایش</button>
                <button data-del="${g.id}" class="bg-red-600 text-white rounded-lg px-2 py-1 text-xs font-bold">حذف</button>
            </div>
            <div class="mt-1 text-center text-[11px] text-gray-500 truncate">${esc(g.section)}</div>`;
        box.querySelector('img').addEventListener('click', () => openLightbox(g.src));
        box.querySelector('[data-del]').addEventListener('click', async () => {
            if (!PANEL.confirmDelete('این عکس حذف شود؟')) return;
            const fd = new FormData();
            fd.append('action', 'gallery_delete');
            fd.append('id', g.id);
            const res = await PANEL.api('/files/api/admin.php', fd);
            PANEL.toast(res.message, !res.success);
            if (res.success) loadGallery();
        });
        box.querySelector('[data-edit]').addEventListener('click', async () => {
            const section = prompt('نام سکشن جدید (مثل: سردرب و ورودی مدرسه):', g.section || '');
            if (section === null) return;
            if (!section.trim()) { PANEL.toast('نام سکشن الزامی است.', true); return; }
            const fd = new FormData();
            fd.append('action', 'gallery_update');
            fd.append('id', g.id);
            fd.append('section', section.trim());
            const res = await PANEL.api('/files/api/admin.php', fd);
            PANEL.toast(res.message, !res.success);
            if (res.success) { await loadSections(); loadGallery(); }
        });
        grid.appendChild(box);
    });
}

function openLightbox(src) {
    const lb = document.getElementById('lightbox');
    document.getElementById('lightbox-img').src = src;
    lb.classList.remove('hidden');
    lb.classList.add('flex');
}
document.getElementById('lightbox').addEventListener('click', () => {
    const lb = document.getElementById('lightbox');
    lb.classList.add('hidden'); lb.classList.remove('flex');
});

document.getElementById('g-add-btn').addEventListener('click', async () => {
    const section = document.getElementById('g-section').value.trim();
    const files = document.getElementById('g-files').files;
    if (!section) { PANEL.toast('سکشن را مشخص کنید.', true); return; }
    if (!files.length) { PANEL.toast('حداقل یک عکس انتخاب کنید.', true); return; }
    const btn = document.getElementById('g-add-btn');
    btn.disabled = true; btn.textContent = 'در حال افزودن...';
    const fd = new FormData();
    fd.append('action', 'gallery_add_batch');
    fd.append('section', section);
    Array.from(files).forEach(f => fd.append('images', f));
    const res = await PANEL.api('/files/api/admin.php', fd);
    btn.disabled = false; btn.textContent = 'افزودن';
    PANEL.toast(res.message, !res.success);
    if (res.success) {
        document.getElementById('g-files').value = '';
        await loadSections();
        loadGallery();
    }
});

document.getElementById('g-filter').addEventListener('change', loadGallery);

document.addEventListener('DOMContentLoaded', async () => {
    await loadSections();
    loadGallery();
});
</script>
