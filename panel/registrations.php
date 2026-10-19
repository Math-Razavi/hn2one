<?php
// registrations.php — فرم‌های ثبت‌نام دریافتی
require_once '_guard.php';

$PANEL_TITLE = 'فرم‌های ثبت‌نام';
$PANEL_ACTIVE = 'registrations';
require '_top.php';
?>
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <p class="text-gray-500 text-sm">فرم‌های ثبت‌نامی که اولیا/دانش‌آموزان در سایت پر کرده‌اند.</p>
        <div class="flex gap-2">
            <a href="/files/api/admin.php?action=registrations_export" class="bg-green-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-green-700 flex items-center gap-1"><i class="ph-duotone ph-download-simple text-lg"></i> دانلود CSV</a>
        </div>
    </div>
    <div id="reg-list" class="space-y-3">
        <div class="text-center py-10 text-gray-400">در حال بارگذاری...</div>
    </div>

<?php require '_bottom.php'; ?>
<script>
async function loadRegs() {
    const r = await PANEL.api('/files/api/admin.php', { action: 'registrations_list' });
    const list = document.getElementById('reg-list');
    const items = (r.data || []).slice().reverse();
    if (!items.length) {
        list.innerHTML = '<div class="text-center py-10 text-gray-400">فرمی ثبت نشده است.</div>';
        return;
    }
    list.innerHTML = '';
    items.forEach(reg => {
        const box = document.createElement('div');
        box.className = 'bg-white rounded-2xl border p-5 flex flex-wrap justify-between gap-4 items-start';
        box.innerHTML = `
            <div class="flex gap-4">
                ${reg.photo
                    ? `<a href="${esc(reg.photo)}" target="_blank"><img src="${esc(reg.photo)}" class="w-16 h-16 rounded-xl object-cover border" alt="عکس"></a>`
                    : `<div class="w-16 h-16 bg-gray-100 rounded-xl flex items-center justify-center"><i class="ph-duotone ph-user text-3xl text-gray-300"></i></div>`}
                <div>
                    <p class="font-extrabold">${esc(reg.name)} <span class="text-xs font-normal bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full ml-1">پایه ${esc(reg.grade)}</span></p>
                    <div class="text-xs text-gray-500 mt-2 grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-1">
                        <span>کد ملی: <b dir="ltr">${esc(reg.national_code)}</b></span>
                        <span>تاریخ تولد: <b>${esc(reg.birthdate)}</b></span>
                        <span>موبایل دانش‌آموز: <b dir="ltr">${esc(reg.student_phone)}</b></span>
                        <span>موبایل ولی: <b dir="ltr">${esc(reg.parent_phone)}</b></span>
                        <span>تاریخ ثبت: <b>${jDate(reg.created_at)}</b></span>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">آدرس: ${esc(reg.address)}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="https://maps.google.com/?q=${encodeURIComponent(reg.address)}" target="_blank" class="text-xs font-bold bg-blue-50 text-blue-700 px-3 py-2 rounded-lg hover:bg-blue-100">مسیر</a>
                <button onclick="delReg(${reg.id})" class="bg-red-100 text-red-700 font-bold px-3 py-2 rounded-lg hover:bg-red-200 text-xs">حذف</button>
            </div>`;
        list.appendChild(box);
    });
}

async function delReg(id) {
    if (!PANEL.confirmDelete('این فرم حذف شود؟')) return;
    const fd = new FormData();
    fd.append('action', 'registration_delete');
    fd.append('id', id);
    const r = await PANEL.api('/files/api/admin.php', fd);
    PANEL.toast(r.message, !r.success);
    if (r.success) loadRegs();
}

document.addEventListener('DOMContentLoaded', loadRegs);
</script>
