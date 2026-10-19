<?php
// students.php — مدیریت حساب کاربران (دانش‌آموز/دبیر)
require_once '_guard.php';

$PANEL_TITLE = 'حساب کاربران';
$PANEL_ACTIVE = 'students';
require '_top.php';
?>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
        <!-- ساخت تکی -->
        <div class="bg-white rounded-2xl shadow-sm border p-6">
            <h2 class="text-lg font-extrabold text-gray-800 mb-4 flex items-center gap-2"><i class="ph-duotone ph-user-plus text-xl text-green-600"></i> ساخت حساب تکی</h2>
            <form id="one-form" class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block mb-1 text-sm font-bold">نام و نام خانوادگی *</label>
                    <input name="name" required class="w-full p-3 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block mb-1 text-sm font-bold">کد کاربری (انگلیسی) *</label>
                    <input name="code" dir="ltr" required pattern="[A-Za-z0-9_-]{3,30}" class="w-full p-3 border border-gray-300 rounded-lg font-mono" placeholder="s1405001">
                </div>
                <div>
                    <label class="block mb-1 text-sm font-bold">نقش</label>
                    <select name="role" class="w-full p-3 border border-gray-300 rounded-lg bg-white">
                        <option value="student" selected>دانش‌آموز</option>
                        <option value="teacher">دبیر</option>
                    </select>
                </div>
                <div class="col-span-2" id="class-wrap">
                    <label class="block mb-1 text-sm font-bold">کلاس (برای دانش‌آموز)</label>
                    <select name="class" class="w-full p-3 border border-gray-300 rounded-lg bg-white">
                        <option value="">— بدون کلاس —</option>
                        <option>۷-۱</option><option>۷-۲</option><option>۷-۳</option>
                        <option>۸-۱</option><option>۸-۲</option><option>۸-۳</option>
                        <option>۹-۱</option><option>۹-۲</option><option>۹-۳</option>
                    </select>
                </div>
                <div id="one-msg" class="col-span-2 text-center font-bold h-6 text-sm"></div>
                <button type="submit" class="col-span-2 bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 shadow">ساخت حساب</button>
            </form>
        </div>

        <!-- ساخت گروهی -->
        <div class="bg-white rounded-2xl shadow-sm border p-6">
            <h2 class="text-lg font-extrabold text-gray-800 mb-4 flex items-center gap-2"><i class="ph-duotone ph-users-four text-xl text-green-600"></i> ساخت گروهی</h2>
            <form id="bulk-form" class="space-y-3">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 text-sm font-bold">نقش</label>
                        <select name="role" class="w-full p-3 border border-gray-300 rounded-lg bg-white">
                            <option value="student" selected>دانش‌آموز</option>
                            <option value="teacher">دبیر</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-bold">کلاس پیشفرض</label>
                        <select name="default_class" class="w-full p-3 border border-gray-300 rounded-lg bg-white">
                            <option value="">— —</option>
                            <option>۷-۱</option><option>۷-۲</option><option>۷-۳</option>
                            <option>۸-۱</option><option>۸-۲</option><option>۸-۳</option>
                            <option>۹-۱</option><option>۹-۲</option><option>۹-۳</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block mb-1 text-sm font-bold">هر خط = یک نفر: نام | کد کاربری | کلاس(اختیاری)</label>
                    <textarea name="bulk_data" rows="7" dir="rtl" class="w-full p-3 border border-gray-300 rounded-lg font-mono text-left leading-8"
                              placeholder="علی محمدی | s1406001 | ۸-۱&#10;مهدی رضایی | s1406002">&#10;</textarea>
                </div>
                <div id="bulk-msg" class="text-center font-bold h-6 text-sm"></div>
                <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 shadow">ساخت گروهی</button>
            </form>
            <div id="bulk-result" class="hidden mt-4 bg-green-50 border border-green-200 rounded-xl p-4 max-h-64 overflow-y-auto">
                <p class="font-bold text-green-800 text-sm mb-3">رمزهای اولیه — فقط یک بار نمایش داده می‌شوند. آن‌ها را کپی یا خروجی بگیرید:</p>
                <div id="bulk-result-body" class="space-y-1 font-mono text-xs" dir="ltr"></div>
                <button id="bulk-result-copy" class="mt-3 bg-green-600 text-white px-4 py-2 rounded-lg text-xs font-bold">کپی همه</button>
            </div>
        </div>
    </div>

    <!-- لیست کاربران -->
    <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
        <div class="p-4 border-b flex flex-wrap gap-3 items-center">
            <h2 class="text-lg font-extrabold text-gray-800">کاربران موجود</h2>
            <div class="relative mr-auto">
                <input id="u-search" type="search" placeholder="جستجوی نام یا کد..." class="bg-gray-50 p-2 border border-gray-300 rounded-lg text-sm pr-9" dir="auto">
                <i class="ph-bold ph-magnifying-glass absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
            <select id="u-filter" class="bg-gray-50 p-2 border border-gray-300 rounded-lg text-sm">
                <option value="">همه</option>
                <option value="student">دانش‌آموزان</option>
                <option value="teacher">دبیران</option>
            </select>
            <select id="u-state" class="bg-gray-50 p-2 border border-gray-300 rounded-lg text-sm">
                <option value="">همه وضعیت‌ها</option>
                <option value="active">فعال</option>
                <option value="inactive">غیرفعال</option>
            </select>
            <a href="/files/api/users.php?action=export_csv" class="bg-green-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-green-700 flex items-center gap-1"><i class="ph-duotone ph-download-simple text-lg"></i> CSV</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-right px-4 py-3 font-bold">کد</th>
                        <th class="text-right px-4 py-3 font-bold">نام</th>
                        <th class="text-right px-4 py-3 font-bold">نقش</th>
                        <th class="text-right px-4 py-3 font-bold">کلاس</th>
                        <th class="text-right px-4 py-3 font-bold">وضعیت</th>
                        <th class="text-right px-4 py-3 font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody id="u-tbody" class="divide-y">
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">در حال بارگذاری...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t flex items-center justify-between gap-3">
            <span id="u-count" class="text-xs text-gray-500 font-bold"></span>
            <div class="flex items-center gap-2">
                <button id="u-prev" class="bg-gray-100 px-3 py-2 rounded-lg font-bold text-sm text-gray-600 disabled:opacity-40" disabled>قبلی</button>
                <span id="u-page-info" class="text-sm font-bold"></span>
                <button id="u-next" class="bg-gray-100 px-3 py-2 rounded-lg font-bold text-sm text-gray-600 disabled:opacity-40" disabled>بعدی</button>
            </div>
        </div>
    </div>

    <!-- مودال ویرایش کاربر -->
    <div id="modal-user" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto">
            <div class="p-5 border-b flex items-center justify-between">
                <h3 class="font-extrabold text-lg">ویرایش حساب</h3>
                <button onclick="document.getElementById('modal-user').classList.add('hidden')"><i class="ph-bold ph-x text-2xl text-gray-400"></i></button>
            </div>
            <form id="user-edit-form" class="p-5 space-y-4">
                <input type="hidden" name="code" value="">
                <div>
                    <label class="block mb-1 text-sm font-bold">نام و نام خانوادگی</label>
                    <input name="name" required class="w-full p-3 border border-gray-300 rounded-lg">
                </div>
                <div id="ue-class-wrap">
                    <label class="block mb-1 text-sm font-bold">کلاس</label>
                    <select name="class" class="w-full p-3 border border-gray-300 rounded-lg bg-white">
                        <option value="">— بدون کلاس —</option>
                        <option>۷-۱</option><option>۷-۲</option><option>۷-۳</option>
                        <option>۸-۱</option><option>۸-۲</option><option>۸-۳</option>
                        <option>۹-۱</option><option>۹-۲</option><option>۹-۳</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-1 text-sm font-bold">وضعیت حساب</label>
                    <select name="active" class="w-full p-3 border border-gray-300 rounded-lg bg-white">
                        <option value="1" selected>فعال</option>
                        <option value="0">غیرفعال (بدون امکان ورود و کلاس مجازی)</option>
                    </select>
                </div>
                <div id="user-edit-msg" class="text-center font-bold h-6 text-sm"></div>
                <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 shadow">ذخیره تغییرات</button>
            </form>
        </div>
    </div>

<?php require '_bottom.php'; ?>
<script>
let users = [];
let uPage = 1;
const U_PAGE_SIZE = 25;

const ROLE_FA = { student: ['دانش‌آموز', 'bg-green-100 text-green-700'], teacher: ['دبیر', 'bg-teal-100 text-teal-700'], admin: ['مدیر', 'bg-red-100 text-red-700'] };

async function loadUsers() {
    const r = await PANEL.api('/files/api/users.php', { action: 'list' });
    users = (r.data || []).filter(u => u.role !== 'admin');
    users.sort((a, b) => (a.role + a.code).localeCompare(b.role + b.code, 'fa'));
    uPage = 1;
    renderUsers();
}

function isActive(u) { return (u.active ?? true) !== false; }

function renderUsers() {
    const filter = document.getElementById('u-filter').value;
    const state = document.getElementById('u-state').value;
    const q = document.getElementById('u-search').value.trim().toLowerCase();
    let list = users.filter(u => (!filter || u.role === filter))
        .filter(u => !state || (state === 'active' ? isActive(u) : !isActive(u)));
    if (q) list = list.filter(u => (u.name || '').toLowerCase().includes(q) || (u.code || '').toLowerCase().includes(q));

    const total = list.length;
    const pages = Math.max(1, Math.ceil(total / U_PAGE_SIZE));
    if (uPage > pages) uPage = pages;
    const slice = list.slice((uPage - 1) * U_PAGE_SIZE, uPage * U_PAGE_SIZE);

    document.getElementById('u-count').textContent = 'مجموع: ' + pDigits(total) + ' نفر (فیلترشده)';
    document.getElementById('u-page-info').textContent = 'صفحه ' + pDigits(uPage) + ' از ' + pDigits(pages);
    document.getElementById('u-prev').disabled = uPage <= 1;
    document.getElementById('u-next').disabled = uPage >= pages;

    const tbody = document.getElementById('u-tbody');
    if (!slice.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">کاربری یافت نشد.</td></tr>';
        return;
    }
    tbody.innerHTML = '';
    slice.forEach(u => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="px-4 py-3 font-mono" dir="ltr">${esc(u.code)}</td>
            <td class="px-4 py-3 font-bold">${esc(u.name)}</td>
            <td class="px-4 py-3"><span class="${ROLE_FA[u.role]?.[1] || ''} px-2 py-1 rounded-full text-xs font-bold">${ROLE_FA[u.role]?.[0] || u.role}</span></td>
            <td class="px-4 py-3">${esc(u.class || '—')}</td>
            <td class="px-4 py-3">${isActive(u)
                ? '<span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-bold">فعال</span>'
                : '<span class="bg-amber-100 text-amber-700 px-2 py-1 rounded-full text-xs font-bold">غیرفعال</span>'}</td>
            <td class="px-4 py-3">
                <div class="flex flex-wrap gap-2">
                    <button onclick="editUser('${esc(u.code)}')" class="bg-blue-100 text-blue-700 font-bold px-3 py-2 rounded-lg hover:bg-blue-200 text-xs">ویرایش</button>
                    <button onclick="toggleActive('${esc(u.code)}', ${isActive(u)})" class="${isActive(u) ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-green-100 text-green-700 hover:bg-green-200'} font-bold px-3 py-2 rounded-lg text-xs">${isActive(u) ? 'غیرفعال‌سازی' : 'فعال‌سازی'}</button>
                    <button onclick="resetPw('${esc(u.code)}')" class="bg-gray-100 text-gray-700 font-bold px-3 py-2 rounded-lg hover:bg-gray-200 text-xs">ریست رمز</button>
                    <button onclick="delUser('${esc(u.code)}', '${esc(u.name)}')" class="bg-red-100 text-red-700 font-bold px-3 py-2 rounded-lg hover:bg-red-200 text-xs">حذف</button>
                </div>
            </td>`;
        tbody.appendChild(tr);
    });
}

async function editUser(code) {
    const u = users.find(x => x.code === code);
    if (!u) return;
    const f = document.getElementById('user-edit-form');
    f.code.value = u.code;
    f.name.value = u.name || '';
    f.class.value = u.class || '';
    f.active.value = isActive(u) ? '1' : '0';
    document.getElementById('ue-class-wrap').style.display = u.role === 'student' ? '' : 'none';
    document.getElementById('user-edit-msg').textContent = '';
    document.getElementById('modal-user').classList.remove('hidden');
}

document.getElementById('user-edit-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append('action', 'update');
    const r = await PANEL.api('/files/api/users.php', fd);
    const msg = document.getElementById('user-edit-msg');
    msg.textContent = r.message;
    msg.className = 'text-center font-bold h-6 text-sm ' + (r.success ? 'text-green-600' : 'text-red-600');
    if (r.success) {
        document.getElementById('modal-user').classList.add('hidden');
        loadUsers();
    }
});

async function toggleActive(code, nowActive) {
    const next = nowActive ? 'غیرفعال' : 'فعال';
    if (!confirm('حساب «' + code + '» ' + next + ' شود؟' + (nowActive ? ' کاربر دیگر نمی‌تواند وارد شود یا در کلاس مجازی شرکت کند.' : ''))) return;
    const r = await PANEL.api('/files/api/users.php', { action: 'set_active', code, active: nowActive ? '0' : '1' });
    PANEL.toast(r.message, !r.success);
    if (r.success) loadUsers();
}

async function resetPw(code) {
    if (!confirm('رمز کاربر ' + code + ' ریست شود؟')) return;
    const r = await PANEL.api('/files/api/users.php', { action: 'reset_password', code });
    PANEL.toast(r.message.includes(':') ? r.message : r.message, !r.success);
    alert(r.message);
}

async function delUser(code, name) {
    if (!confirm('حساب «' + name + '» (' + code + ') حذف شود؟ این عمل برگشت‌پذیر نیست.')) return;
    const r = await PANEL.api('/files/api/users.php', { action: 'delete', code });
    PANEL.toast(r.message, !r.success);
    if (r.success) loadUsers();
}

document.getElementById('u-filter').addEventListener('change', () => { uPage = 1; renderUsers(); });
document.getElementById('u-state').addEventListener('change', () => { uPage = 1; renderUsers(); });
document.getElementById('u-search').addEventListener('input', () => { uPage = 1; renderUsers(); });
document.getElementById('u-prev').addEventListener('click', () => { uPage--; renderUsers(); });
document.getElementById('u-next').addEventListener('click', () => { uPage++; renderUsers(); });

document.getElementById('one-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = document.getElementById('one-msg');
    // کلاس برای دبیر ارسال نشود
    const fd = new FormData(e.target);
    if (fd.get('role') !== 'student') fd.set('class', '');
    const r = await PANEL.api('/files/api/users.php', fd);
    msg.textContent = r.message;
    msg.className = 'col-span-2 text-center font-bold h-6 text-sm ' + (r.success ? 'text-green-600' : 'text-red-600');
    if (r.success) {
        alert(r.message);
        e.target.reset();
        loadUsers();
    }
});

document.getElementById('bulk-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = document.getElementById('bulk-msg');
    const form = e.target;
    const fd = new FormData(form);
    if (fd.get('role') !== 'student') fd.set('default_class', '');
    const r = await PANEL.api('/files/api/users.php', fd);
    msg.textContent = r.message;
    msg.className = 'text-center font-bold h-6 text-sm ' + (r.success ? 'text-green-600' : 'text-red-600');
    const box = document.getElementById('bulk-result');
    if (r.success && (r.created || []).length) {
        const body = document.getElementById('bulk-result-body');
        body.innerHTML = r.created.map(c => `<div><b>${esc(c.code)}</b> | ${esc(c.name)} | pw: <b>${esc(c.first_password)}</b></div>`).join('');
        box.classList.remove('hidden');
        document.getElementById('bulk-result-copy').onclick = () => {
            const txt = r.created.map(c => `${c.code} | ${c.name} | رمز: ${c.first_password}`).join('\n');
            navigator.clipboard.writeText(txt).then(() => PANEL.toast('رمزها کپی شد.'));
        };
    }
    if (r.errors && r.errors.length) { box.classList.remove('hidden'); }
    if (r.success) loadUsers();
});

document.addEventListener('DOMContentLoaded', () => {
    const oneRole = document.querySelector('#one-form [name=role]');
    const classWrap = document.getElementById('class-wrap');
    oneRole.addEventListener('change', () => {
        classWrap.style.display = oneRole.value === 'student' ? '' : 'none';
    });
    loadUsers();
});
</script>
