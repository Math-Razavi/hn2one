<?php
// announcements.php — مدیریت اطلاعیه‌های نوار متحرک (افزودن/ویرایش/حذف + ویرایش گروهی)
require_once '_guard.php';

$saved = hn_load('announcements.json');
$PANEL_TITLE = 'اطلاعیه‌ها (نوار متحرک)';
$PANEL_ACTIVE = 'announcements';
require '_top.php';
?>
    <div class="bg-white rounded-2xl shadow-sm border max-w-2xl p-8 mb-6">
        <h2 class="text-lg font-extrabold text-gray-800 mb-4 flex items-center gap-2"><i class="ph-duotone ph-plus-circle text-xl text-green-600"></i> افزودن اطلاعیه</h2>
        <form id="ann-add-form" class="flex gap-3">
            <input id="ann-add-text" class="flex-1 p-3 border border-gray-300 rounded-lg" placeholder="متن اطلاعیه جدید">
            <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-green-700 shadow whitespace-nowrap">افزودن</button>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border max-w-2xl p-8 mb-6">
        <h2 class="text-lg font-extrabold text-gray-800 mb-4 flex items-center gap-2"><i class="ph-duotone ph-megaphone text-xl text-green-600"></i> اطلاعیه‌های موجود <span id="ann-count" class="text-sm text-gray-400 font-normal"></span></h2>
        <p class="text-sm text-gray-500 mb-4">در صفحه اصلی هر بار یکی از این اطلاعیه‌ها به صورت تصادفی در نوار زرد بالای صفحه نمایش داده می‌شود.</p>
        <div id="ann-list" class="space-y-3">
            <div class="text-center py-6 text-gray-400">در حال بارگذاری...</div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border max-w-2xl p-8">
        <details>
            <summary class="text-sm font-extrabold text-gray-600 cursor-pointer select-none flex items-center gap-2"><i class="ph-duotone ph-list-magnifying-glass text-lg"></i> ویرایش گروهی (بازنویسی همه اطلاعیه‌ها به یکجا)</summary>
            <div id="ann-bulk" class="mt-4">
                <textarea name="announcements" rows="8" class="w-full p-4 border border-gray-300 rounded-lg leading-8"
                          placeholder="هر خط یک اطلاعیه"><?php
                    foreach ($saved as $a) echo htmlspecialchars($a['text']) . PHP_EOL;
                ?></textarea>
                <div id="ann-msg" class="text-center font-bold h-6 text-sm mt-2"></div>
                <button id="ann-save-btn" type="button" class="w-full bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 shadow">ذخیره اطلاعیه‌ها</button>
            </div>
        </details>
    </div>

<?php require '_bottom.php'; ?>
<script>
let annData = [];
const bulkBox = document.getElementById('ann-bulk');
const bulkTa = bulkBox.querySelector('textarea');

function syncBulk() {
    bulkTa.value = annData.map(x => x.text).join('\n');
}

function renderList() {
    const list = document.getElementById('ann-list');
    document.getElementById('ann-count').textContent = '(' + pDigits(annData.length) + ' اطلاعیه)';
    if (!annData.length) {
        list.innerHTML = '<div class="text-center py-6 text-gray-400">اطلاعیه‌ای ثبت نشده است.</div>';
        return;
    }
    list.innerHTML = '';
    annData.forEach(a => {
        const row = document.createElement('div');
        row.className = 'flex items-center gap-3 p-3 rounded-xl border bg-gray-50';
        row.innerHTML = `
            <p class="flex-1 leading-7 text-sm font-medium ann-text"></p>
            <button class="ann-edit bg-blue-100 text-blue-700 font-bold px-3 py-2 rounded-lg hover:bg-blue-200 text-xs flex-shrink-0">ویرایش</button>
            <button class="ann-del bg-red-100 text-red-700 font-bold px-3 py-2 rounded-lg hover:bg-red-200 text-xs flex-shrink-0">حذف</button>`;
        row.querySelector('.ann-text').textContent = a.text;
        row.querySelector('.ann-edit').addEventListener('click', () => {
            row.classList.add('bg-yellow-50');
            row.innerHTML = `
                <input class="flex-1 p-2 border border-gray-300 rounded-lg text-sm" value="">
                <button class="ann-save bg-green-600 text-white font-bold px-3 py-2 rounded-lg hover:bg-green-700 text-xs flex-shrink-0">ذخیره</button>
                <button class="ann-cancel bg-gray-200 text-gray-700 font-bold px-3 py-2 rounded-lg hover:bg-gray-300 text-xs flex-shrink-0">انصراف</button>`;
            const input = row.querySelector('input');
            input.value = a.text;
            input.focus();
            row.querySelector('.ann-save').addEventListener('click', async () => {
                const t = input.value.trim();
                if (!t) { PANEL.toast('متن خالی است.', true); return; }
                const fd = new FormData();
                fd.append('action', 'announcements_update');
                fd.append('id', a.id);
                fd.append('text', t);
                const r = await PANEL.api('/files/api/admin.php', fd);
                PANEL.toast(r.message, !r.success);
                if (r.success) loadAnnouncements();
            });
            row.querySelector('.ann-cancel').addEventListener('click', renderList);
        });
        row.querySelector('.ann-del').addEventListener('click', async () => {
            if (!PANEL.confirmDelete('این اطلاعیه حذف شود؟')) return;
            const fd = new FormData();
            fd.append('action', 'announcements_delete');
            fd.append('id', a.id);
            const r = await PANEL.api('/files/api/admin.php', fd);
            PANEL.toast(r.message, !r.success);
            if (r.success) loadAnnouncements();
        });
        list.appendChild(row);
    });
    syncBulk();
}

async function loadAnnouncements() {
    const fd = new FormData();
    fd.append('action', 'announcements_list');
    const r = await PANEL.api('/files/api/admin.php', fd);
    annData = r.data || [];
    renderList();
}

document.getElementById('ann-add-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const input = document.getElementById('ann-add-text');
    const t = input.value.trim();
    if (!t) { PANEL.toast('متن اطلاعیه را بنویسید.', true); return; }
    const fd = new FormData();
    fd.append('action', 'announcements_add');
    fd.append('text', t);
    const r = await PANEL.api('/files/api/admin.php', fd);
    PANEL.toast(r.message, !r.success);
    if (r.success) { input.value = ''; loadAnnouncements(); }
});

document.getElementById('ann-save-btn').addEventListener('click', async () => {
    const msg = document.getElementById('ann-msg');
    const fd = new FormData();
    fd.append('action', 'announcements_save');
    fd.append('announcements', bulkTa.value);
    const r = await PANEL.api('/files/api/admin.php', fd);
    msg.textContent = r.message;
    msg.className = 'text-center font-bold h-6 text-sm mt-2 ' + (r.success ? 'text-green-600' : 'text-red-600');
    if (r.success) loadAnnouncements();
});

document.addEventListener('DOMContentLoaded', loadAnnouncements);
</script>
