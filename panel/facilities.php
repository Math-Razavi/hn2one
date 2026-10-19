<?php
// facilities.php — مدیریت امکانات مدرسه
require_once '_guard.php';

$PANEL_TITLE = 'امکانات مدرسه';
$PANEL_ACTIVE = 'facilities';
require '_top.php';
?>
    <div class="bg-white rounded-2xl shadow-sm border max-w-xl p-6 mb-6">
        <h2 class="text-lg font-extrabold text-gray-800 mb-4" id="form-title">افزودن امکان جدید</h2>
        <form id="fac-form" class="space-y-4">
            <input type="hidden" name="id" value="0">
            <div>
                <label class="block mb-1 text-sm font-bold">عنوان امکان *</label>
                <input name="title" required class="w-full p-3 border border-gray-300 rounded-lg" placeholder="مثال: کتابخانه">
            </div>
            <div>
                <label class="block mb-1 text-sm font-bold">آیکن Phosphor (اختیاری)</label>
                <input name="icon" dir="ltr" class="w-full p-3 border border-gray-300 rounded-lg font-mono" placeholder="ph-library">
                <p class="text-xs text-gray-400 mt-1">خالی بگذارید تا آیکن ستاره‌ی پیشفرض نمایش داده شود. نمونه: ph-computer, ph-book-open, ph-basketball</p>
            </div>
            <div class="flex gap-3">
                <button type="button" id="reset-btn" class="bg-gray-200 text-gray-800 px-5 py-3 rounded-lg font-bold hover:bg-gray-300 hidden">انصراف</button>
                <button type="submit" class="flex-1 bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 shadow">ذخیره</button>
            </div>
        </form>
    </div>
    <div id="fac-grid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        <div class="col-span-full text-center py-10 text-gray-400">در حال بارگذاری...</div>
    </div>

<?php require '_bottom.php'; ?>
<script>
let facData = [];

async function loadFac() {
    const r = await (await fetch('/files/api/content.php?action=get_facilities')).json();
    facData = r.data || [];
    const grid = document.getElementById('fac-grid');
    if (!facData.length) {
        grid.innerHTML = '<div class="col-span-full text-center py-10 text-gray-400">امکانی ثبت نشده است.</div>';
        return;
    }
    grid.innerHTML = '';
    facData.forEach(f => {
        const card = document.createElement('div');
        card.className = 'bg-white rounded-2xl border p-5 text-center';
        card.innerHTML = `
            <i class="ph-duotone ${esc(f.icon || 'ph-star')} text-4xl text-green-600 mb-2"></i>
            <p class="font-bold text-sm">${esc(f.title)}</p>
            <div class="flex gap-2 justify-center mt-3">
                <button onclick="startEdit(${f.id})" class="bg-blue-100 text-blue-700 font-bold px-3 py-2 rounded-lg hover:bg-blue-200 text-xs">ویرایش</button>
                <button onclick="delFac(${f.id})" class="bg-red-100 text-red-700 font-bold px-3 py-2 rounded-lg hover:bg-red-200 text-xs">حذف</button>
            </div>`;
        grid.appendChild(card);
    });
}

function startEdit(id) {
    const f = facData.find(x => x.id === id);
    if (!f) return;
    const form = document.getElementById('fac-form');
    form.id.value = f.id;
    form.title.value = f.title || '';
    form.icon.value = f.icon || '';
    document.getElementById('form-title').textContent = 'ویرایش امکان';
    document.getElementById('reset-btn').classList.remove('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

document.getElementById('reset-btn').addEventListener('click', () => {
    document.getElementById('fac-form').reset();
    document.getElementById('fac-form').id.value = 0;
    document.getElementById('form-title').textContent = 'افزودن امکان جدید';
    document.getElementById('reset-btn').classList.add('hidden');
});

document.getElementById('fac-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append('action', 'facility_save');
    const r = await PANEL.api('/files/api/admin.php', fd);
    PANEL.toast(r.message, !r.success);
    if (r.success) {
        e.target.reset();
        e.target.id.value = 0;
        document.getElementById('form-title').textContent = 'افزودن امکان جدید';
        document.getElementById('reset-btn').classList.add('hidden');
        loadFac();
    }
});

async function delFac(id) {
    if (!PANEL.confirmDelete('این امکان حذف شود؟')) return;
    const fd = new FormData();
    fd.append('action', 'facility_delete');
    fd.append('id', id);
    const r = await PANEL.api('/files/api/admin.php', fd);
    PANEL.toast(r.message, !r.success);
    if (r.success) loadFac();
}

document.addEventListener('DOMContentLoaded', async () => {
    await loadFac();
    // باز شدن مستقیم فرم ویرایش (از نوار مدیرِ خود سایت: /panel/facilities.php?edit=id)
    const eager = new URLSearchParams(location.search).get('edit');
    if (eager) {
        const waiter = setInterval(() => {
            const f = facData.find(x => String(x.id) === eager);
            if (f) { clearInterval(waiter); startEdit(f.id); }
        }, 120);
        setTimeout(() => clearInterval(waiter), 3000);
    }
});
</script>
