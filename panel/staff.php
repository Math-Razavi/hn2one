<?php
// staff.php — مدیریت کادر آموزشی و اجرایی
require_once '_guard.php';

$PANEL_TITLE = 'کادر آموزشی';
$PANEL_ACTIVE = 'staff';
require '_top.php';
?>
    <div class="bg-white rounded-2xl shadow-sm border max-w-2xl p-6 mb-6">
        <h2 class="text-lg font-extrabold text-gray-800 mb-4" id="form-title">افزودن فرد جدید</h2>
        <form id="staff-form" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <input type="hidden" name="id" value="0">
            <div>
                <label class="block mb-1 text-sm font-bold">نام و نام خانوادگی *</label>
                <input name="name" required class="w-full p-3 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block mb-1 text-sm font-bold">سمت *</label>
                <input name="role" required class="w-full p-3 border border-gray-300 rounded-lg" placeholder="مثال: مدیر مدرسه">
            </div>
            <div>
                <label class="block mb-1 text-sm font-bold">تخصص / درس</label>
                <input name="subject" class="w-full p-3 border border-gray-300 rounded-lg" placeholder="مثال: ریاضی — اختیاری">
            </div>
            <div>
                <label class="block mb-1 text-sm font-bold">عکس (اختیاری، برای به‌روزرسانی)</label>
                <input name="photo" type="file" accept="image/jpeg,image/png,image/webp" class="w-full p-2 border border-gray-300 rounded-lg">
            </div>
            <div id="staff-msg" class="sm:col-span-2 text-center font-bold h-6 text-sm"></div>
            <div class="sm:col-span-2 flex gap-3">
                <button type="button" id="reset-btn" class="bg-gray-200 text-gray-800 px-5 py-3 rounded-lg font-bold hover:bg-gray-300 hidden">انصراف از ویرایش</button>
                <button type="submit" id="submit-btn" class="flex-1 bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 shadow">ذخیره</button>
            </div>
        </form>
    </div>

    <div id="staff-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="col-span-full text-center py-10 text-gray-400">در حال بارگذاری...</div>
    </div>

<?php require '_bottom.php'; ?>
<script>
let staffData = [];

async function loadStaff() {
    const r = await (await fetch('/files/api/content.php?action=get_staff')).json();
    staffData = r.data || [];
    const grid = document.getElementById('staff-grid');
    if (!staffData.length) {
        grid.innerHTML = '<div class="col-span-full text-center py-10 text-gray-400">هنوز فردی ثبت نشده است.</div>';
        return;
    }
    grid.innerHTML = '';
    staffData.forEach(s => {
        const card = document.createElement('div');
        card.className = 'bg-white rounded-2xl border shadow-sm overflow-hidden';
        card.innerHTML = `
            <img src="${esc(s.photo)}" alt="" class="w-full h-48 object-cover bg-gray-100" loading="lazy">
            <div class="p-5 text-center">
                <p class="font-extrabold text-lg">${esc(s.name)}</p>
                <p class="text-green-600 font-bold text-sm mt-1">${esc(s.role)}</p>
                ${s.subject ? `<p class="text-xs text-gray-500 mt-2">تخصص: ${esc(s.subject)}</p>` : ''}
                <div class="flex gap-2 justify-center mt-4">
                    <button onclick="startEdit(${s.id})" class="bg-blue-100 text-blue-700 font-bold px-4 py-2 rounded-lg hover:bg-blue-200 text-xs">ویرایش</button>
                    <button onclick="delStaff(${s.id})" class="bg-red-100 text-red-700 font-bold px-4 py-2 rounded-lg hover:bg-red-200 text-xs">حذف</button>
                </div>
            </div>`;
        grid.appendChild(card);
    });
}

function startEdit(id) {
    const s = staffData.find(x => x.id === id);
    if (!s) return;
    const f = document.getElementById('staff-form');
    f.id.value = s.id;
    f.name.value = s.name || '';
    f.role.value = s.role || '';
    f.subject.value = s.subject || '';
    f.querySelector('input[name="photo"]').value = '';
    document.getElementById('form-title').textContent = 'ویرایش: ' + s.name;
    document.getElementById('reset-btn').classList.remove('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

document.getElementById('reset-btn').addEventListener('click', () => {
    document.getElementById('staff-form').reset();
    document.getElementById('staff-form').id.value = 0;
    document.getElementById('form-title').textContent = 'افزودن فرد جدید';
    document.getElementById('reset-btn').classList.add('hidden');
});

document.getElementById('staff-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = document.getElementById('staff-msg');
    const fd = new FormData(e.target);
    fd.append('action', 'staff_save');
    const r = await PANEL.api('/files/api/admin.php', fd);
    msg.textContent = r.message;
    msg.className = 'sm:col-span-2 text-center font-bold h-6 text-sm ' + (r.success ? 'text-green-600' : 'text-red-600');
    if (r.success) {
        e.target.reset();
        e.target.id.value = 0;
        document.getElementById('form-title').textContent = 'افزودن فرد جدید';
        document.getElementById('reset-btn').classList.add('hidden');
        loadStaff();
    }
});

async function delStaff(id) {
    if (!PANEL.confirmDelete('این فرد حذف شود؟')) return;
    const fd = new FormData();
    fd.append('action', 'staff_delete');
    fd.append('id', id);
    const r = await PANEL.api('/files/api/admin.php', fd);
    PANEL.toast(r.message, !r.success);
    if (r.success) loadStaff();
}

document.addEventListener('DOMContentLoaded', async () => {
    await loadStaff();
    // باز شدن مستقیم فرم ویرایش (از نوار مدیرِ خود سایت: /panel/staff.php?edit=id)
    const eager = new URLSearchParams(location.search).get('edit');
    if (eager) {
        const waiter = setInterval(() => {
            const s = staffData.find(x => String(x.id) === eager);
            if (s) { clearInterval(waiter); startEdit(s.id); }
        }, 120);
        setTimeout(() => clearInterval(waiter), 3000);
    }
});
</script>
