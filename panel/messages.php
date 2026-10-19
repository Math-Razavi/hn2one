<?php
// messages.php — پیام‌های فرم تماس با ما
require_once '_guard.php';

$PANEL_TITLE = 'پیام‌های تماس با ما';
$PANEL_ACTIVE = 'messages';
require '_top.php';
?>
    <div id="msg-list" class="space-y-3">
        <div class="text-center py-10 text-gray-400">در حال بارگذاری...</div>
    </div>

<?php require '_bottom.php'; ?>
<script>
async function loadMsgs() {
    const r = await PANEL.api('/files/api/admin.php', { action: 'messages_list' });
    const list = document.getElementById('msg-list');
    const items = (r.data || []).slice().reverse();
    if (!items.length) {
        list.innerHTML = '<div class="text-center py-10 text-gray-400">پیامی دریافت نشده است.</div>';
        return;
    }
    list.innerHTML = '';
    items.forEach(m => {
        const box = document.createElement('div');
        box.className = 'bg-white rounded-2xl border p-5';
        box.innerHTML = `
            <div class="flex flex-wrap justify-between items-start gap-3">
                <div>
                    <p class="font-extrabold">${esc(m.name)} ${m.phone ? `<span class="text-xs font-normal text-gray-500" dir="ltr">(${esc(m.phone)})</span>` : ''}</p>
                    <p class="text-sm text-gray-700 mt-3 leading-7">${esc(m.message)}</p>
                    <p class="text-xs text-gray-400 mt-3">${jDate(m.created_at)} — IP: <span dir="ltr">${esc(m.ip || '—')}</span></p>
                </div>
                <button onclick="delMsg(${m.id})" class="bg-red-100 text-red-700 font-bold px-3 py-2 rounded-lg hover:bg-red-200 text-xs">حذف</button>
            </div>`;
        list.appendChild(box);
    });
}

async function delMsg(id) {
    if (!PANEL.confirmDelete('این پیام حذف شود؟')) return;
    const fd = new FormData();
    fd.append('action', 'message_delete');
    fd.append('id', id);
    const r = await PANEL.api('/files/api/admin.php', fd);
    PANEL.toast(r.message, !r.success);
    if (r.success) loadMsgs();
}

document.addEventListener('DOMContentLoaded', loadMsgs);
</script>
