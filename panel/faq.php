<?php
// faq.php — مدیریت سوالات متداول
require_once '_guard.php';

$PANEL_TITLE = 'سوالات متداول';
$PANEL_ACTIVE = 'faq';
require '_top.php';
?>
    <div class="bg-white rounded-2xl shadow-sm border max-w-2xl p-6 mb-6">
        <h2 class="text-lg font-extrabold text-gray-800 mb-4" id="form-title">افزودن سوال جدید</h2>
        <form id="faq-form" class="space-y-4">
            <input type="hidden" name="id" value="0">
            <div>
                <label class="block mb-1 text-sm font-bold">سوال *</label>
                <input name="q" required class="w-full p-3 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block mb-1 text-sm font-bold">جواب *</label>
                <textarea name="a" rows="4" required class="w-full p-3 border border-gray-300 rounded-lg"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" id="reset-btn" class="bg-gray-200 text-gray-800 px-5 py-3 rounded-lg font-bold hover:bg-gray-300 hidden">انصراف</button>
                <button type="submit" class="flex-1 bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 shadow">ذخیره</button>
            </div>
        </form>
    </div>
    <div id="faq-list" class="space-y-3">
        <div class="text-center py-10 text-gray-400">در حال بارگذاری...</div>
    </div>

<?php require '_bottom.php'; ?>
<script>
let faqData = [];

async function loadFaq() {
    const r = await (await fetch('/files/api/content.php?action=get_faq')).json();
    faqData = r.data || [];
    const list = document.getElementById('faq-list');
    if (!faqData.length) {
        list.innerHTML = '<div class="text-center py-10 text-gray-400">سوالی ثبت نشده است.</div>';
        return;
    }
    list.innerHTML = '';
    faqData.forEach((f, i) => {
        const box = document.createElement('div');
        box.className = 'bg-white rounded-2xl border p-5';
        box.innerHTML = `
            <div class="flex justify-between items-start gap-3">
                <div>
                    <p class="font-bold">${pDigits(i + 1)}. ${esc(f.q)}</p>
                    <p class="text-sm text-gray-600 mt-2 leading-7">${esc(f.a)}</p>
                </div>
                <div class="flex gap-2 flex-shrink-0">
                    <button onclick="startEdit(${f.id})" class="bg-blue-100 text-blue-700 font-bold px-3 py-2 rounded-lg hover:bg-blue-200 text-xs">ویرایش</button>
                    <button onclick="delFaq(${f.id})" class="bg-red-100 text-red-700 font-bold px-3 py-2 rounded-lg hover:bg-red-200 text-xs">حذف</button>
                </div>
            </div>`;
        list.appendChild(box);
    });
}

function startEdit(id) {
    const f = faqData.find(x => x.id === id);
    if (!f) return;
    const form = document.getElementById('faq-form');
    form.id.value = f.id;
    form.q.value = f.q || '';
    form.a.value = f.a || '';
    document.getElementById('form-title').textContent = 'ویرایش سوال';
    document.getElementById('reset-btn').classList.remove('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

document.getElementById('reset-btn').addEventListener('click', () => {
    document.getElementById('faq-form').reset();
    document.getElementById('faq-form').id.value = 0;
    document.getElementById('form-title').textContent = 'افزودن سوال جدید';
    document.getElementById('reset-btn').classList.add('hidden');
});

document.getElementById('faq-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append('action', 'faq_save');
    const r = await PANEL.api('/files/api/admin.php', fd);
    PANEL.toast(r.message, !r.success);
    if (r.success) {
        e.target.reset();
        e.target.id.value = 0;
        document.getElementById('form-title').textContent = 'افزودن سوال جدید';
        document.getElementById('reset-btn').classList.add('hidden');
        loadFaq();
    }
});

async function delFaq(id) {
    if (!PANEL.confirmDelete('این سوال حذف شود؟')) return;
    const fd = new FormData();
    fd.append('action', 'faq_delete');
    fd.append('id', id);
    const r = await PANEL.api('/files/api/admin.php', fd);
    PANEL.toast(r.message, !r.success);
    if (r.success) loadFaq();
}

document.addEventListener('DOMContentLoaded', async () => {
    await loadFaq();
    // باز شدن مستقیم فرم ویرایش (از نوار مدیرِ خود سایت: /panel/faq.php?edit=id)
    const eager = new URLSearchParams(location.search).get('edit');
    if (eager) {
        const waiter = setInterval(() => {
            const f = faqData.find(x => String(x.id) === eager);
            if (f) { clearInterval(waiter); startEdit(f.id); }
        }, 120);
        setTimeout(() => clearInterval(waiter), 3000);
    }
});
</script>
