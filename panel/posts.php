<?php
// posts.php — مدیریت اخبار و گزارش‌ها (لیست + تایید + ویرایش + حذف)
require_once '_guard.php';

$PANEL_TITLE = 'اخبار و گزارش‌ها';
$PANEL_ACTIVE = 'posts';
require '_top.php';
?>
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <p class="text-gray-500 text-sm">اخبار منتشرشده ممکن است توسط همه بازدید کنندگان سایت دیده شود. گزارش‌های دبیران باید اول توسط شما تایید و منتشر شوند.</p>
        <a href="post_create.php" class="bg-green-600 text-white px-5 py-3 rounded-lg font-bold hover:bg-green-700 flex items-center gap-2 shadow">
            <i class="ph-duotone ph-plus text-lg"></i> خبر جدید
        </a>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-right px-4 py-3 font-bold">عکس</th>
                    <th class="text-right px-4 py-3 font-bold">عنوان</th>
                    <th class="text-right px-4 py-3 font-bold">تاریخ</th>
                    <th class="text-right px-4 py-3 font-bold">وضعیت</th>
                    <th class="text-right px-4 py-3 font-bold">عملیات</th>
                </tr>
            </thead>
            <tbody id="posts-tbody" class="divide-y">
                <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">در حال بارگذاری...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- مودال ویرایش -->
    <div id="edit-modal" class="hidden fixed inset-0 bg-black/60 z-50 items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-8" onclick="event.stopPropagation()">
            <h2 class="text-xl font-extrabold mb-6">ویرایش خبر</h2>
            <form id="edit-form" class="space-y-4">
                <input type="hidden" name="id">
                <div>
                    <label class="block mb-1 text-sm font-bold">عنوان</label>
                    <input name="title" required class="w-full p-3 border border-gray-300 rounded-lg">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 text-sm font-bold">دسته‌بندی</label>
                        <input name="category" class="w-full p-3 border border-gray-300 rounded-lg" placeholder="گزارش فعالیت">
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-bold">سال تحصیلی</label>
                        <input name="year" class="w-full p-3 border border-gray-300 rounded-lg" placeholder="سال تحصیلی ۴۰۶-۱۴۰۵">
                    </div>
                </div>
                <div>
                    <label class="block mb-1 text-sm font-bold">متن (پاراگراف‌ها را با اینتر جدا کنید)</label>
                    <textarea name="body" rows="8" class="w-full p-3 border border-gray-300 rounded-lg"></textarea>
                </div>
                <div>
                    <label class="block mb-1 text-sm font-bold">افزودن عکس جدید (اختیاری)</label>
                    <input name="image" type="file" accept="image/jpeg,image/png,image/webp" class="w-full p-2 border border-gray-300 rounded-lg">
                </div>
                <div id="edit-error" class="text-center text-red-600 font-bold h-6 text-sm"></div>
                <div class="flex gap-3">
                    <button type="button" id="edit-cancel" class="flex-1 bg-gray-200 text-gray-800 py-3 rounded-lg font-bold hover:bg-gray-300">انصراف</button>
                    <button type="submit" class="flex-1 bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700">ثبت تغییرات</button>
                </div>
            </form>
        </div>
    </div>

<?php require '_bottom.php'; ?>
<script>
async function loadPosts() {
    const tbody = document.getElementById('posts-tbody');
    try {
        const fd = new FormData();
        fd.append('action', 'list_posts');
        const r = await PANEL.api('/files/api/admin.php', fd);
        tbody.innerHTML = '';
        if (!r.data.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">هیچ خبری ثبت نشده است.</td></tr>';
            return;
        }
        r.data.sort((a, b) => new Date(b.created_at) - new Date(a.created_at)).forEach(p => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="px-4 py-3">${p.image ? `<img src="${esc(p.image)}" class="w-16 h-12 object-cover rounded-lg" alt="">` : '<span class="text-xs text-gray-300">—</span>'}</td>
                <td class="px-4 py-3 font-bold max-w-xs">
                    <p class="truncate">${esc(p.title)}</p>
                    <p class="text-xs text-gray-400 mt-1">سازنده: ${esc(p.author || '—')} — آیدی: ${p.id}</p>
                </td>
                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">${jDate(p.created_at)}</td>
                <td class="px-4 py-3">${p.status === 'published'
                    ? '<span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-bold">منتشر شده</span>'
                    : '<span class="bg-orange-100 text-orange-700 px-2 py-1 rounded-full text-xs font-bold">در انتظار</span>'}</td>
                <td class="px-4 py-3">
                    <div class="flex flex-wrap gap-2">
                        ${p.status === 'published'
                            ? `<button onclick="setStatus(${p.id}, 'pending')" class="bg-orange-100 text-orange-700 font-bold px-3 py-2 rounded-lg hover:bg-orange-200 text-xs">لغو انتشار</button>`
                            : `<button onclick="setStatus(${p.id}, 'published')" class="bg-green-100 text-green-700 font-bold px-3 py-2 rounded-lg hover:bg-green-200 text-xs">انتشار</button>`}
                        <button onclick="openEdit(${p.id})" class="bg-blue-100 text-blue-700 font-bold px-3 py-2 rounded-lg hover:bg-blue-200 text-xs">ویرایش</button>
                        <a href="/news/post.html?slug=${encoded(p.slug)}" target="_blank" class="bg-gray-100 text-gray-700 font-bold px-3 py-2 rounded-lg hover:bg-gray-200 text-xs">نمایش</a>
                        <button onclick="delPost(${p.id})" class="bg-red-100 text-red-700 font-bold px-3 py-2 rounded-lg hover:bg-red-200 text-xs">حذف</button>
                    </div>
                </td>`;
            tbody.appendChild(tr);
        });
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-10 text-center text-red-500">خطا در بارگذاری.</td></tr>';
    }
}
function encoded(s) { return encodeURIComponent(s); }

async function setStatus(id, status) {
    if (status === 'published' && !confirm('انتشار این خبر صحیح است؟')) return;
    if (status === 'pending' && !confirm('انتشار این خبر لغو شود؟')) return;
    const fd = new FormData();
    fd.append('action', 'set_post_status');
    fd.append('id', id);
    fd.append('status', status);
    const r = await PANEL.api('/files/api/admin.php', fd);
    PANEL.toast(r.message, !r.success);
    if (r.success) loadPosts();
}

async function delPost(id) {
    if (!PANEL.confirmDelete('این خبر برای همیشه حذف می‌شود!')) return;
    const fd = new FormData();
    fd.append('action', 'delete_post');
    fd.append('id', id);
    const r = await PANEL.api('/files/api/admin.php', fd);
    PANEL.toast(r.message, !r.success);
    if (r.success) loadPosts();
}

let allPosts = [];
async function openEdit(id) {
    const p = allPosts.find(x => x.id === id);
    if (!p) return;
    const f = document.getElementById('edit-form');
    f.id.value = p.id;
    f.title.value = p.title || '';
    f.category.value = p.category || '';
    f.year.value = p.year || '';
    f.body.value = p.body || '';
    const m = document.getElementById('edit-modal');
    m.classList.remove('hidden'); m.classList.add('flex');
}
document.getElementById('edit-cancel').addEventListener('click', () => {
    const m = document.getElementById('edit-modal');
    m.classList.add('hidden'); m.classList.remove('flex');
});
document.getElementById('edit-modal').addEventListener('click', (e) => {
    if (e.target.id === 'edit-modal') { e.target.classList.add('hidden'); e.target.classList.remove('flex'); }
});
document.getElementById('edit-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errBox = document.getElementById('edit-error');
    const fd = new FormData(e.target);
    fd.set('action', 'update_post');
    const r = await PANEL.api('/files/api/admin.php', fd);
    errBox.textContent = r.message;
    errBox.className = 'text-center font-bold h-6 text-sm ' + (r.success ? 'text-green-600' : 'text-red-600');
    if (r.success) setTimeout(() => {
        const m = document.getElementById('edit-modal');
        m.classList.add('hidden'); m.classList.remove('flex');
        loadPosts();
    }, 900);
});

document.addEventListener('DOMContentLoaded', async () => {
    try {
        const r = await PANEL.api('/files/api/admin.php', { action: 'list_posts' });
        allPosts = r.data || [];
    } catch (e) { allPosts = []; }
    loadPosts();
    // باز شدن مستقیم مودال ویرایش (از نوار مدیرِ خود سایت: /panel/posts.php?edit=slug)
    const eager = new URLSearchParams(location.search).get('edit');
    if (eager) {
        const waiter = setInterval(() => {
            const p = allPosts.find(x => x.slug === eager) || allPosts.find(x => String(x.id) === eager);
            if (p) { clearInterval(waiter); openEdit(p.id); }
        }, 120);
        setTimeout(() => clearInterval(waiter), 3000);
    }
});
</script>
