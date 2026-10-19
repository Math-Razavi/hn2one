<?php
// classes.php — پنل کلاس‌های مجازی (داناپلاس): ساخت کلاس، تخصیص دانش‌آموز، مدیریت جلسه
require_once '_guard.php';

$PANEL_TITLE = 'کلاس‌های مجازی';
$PANEL_ACTIVE = 'classes';
require '_top.php';
?>
    <!-- وضعیت اتصال داناپلاس -->
    <div id="dp-banner" class="hidden rounded-2xl shadow-sm border p-5 mb-6 text-sm font-bold leading-7"></div>

    <div class="flex items-center justify-between gap-4 mb-6">
        <p class="text-gray-600 text-sm">کلاس بسازید، دانش‌آموزان را تخصیص دهید و جلسه را از طریق داناپلاس برگزار کنید. دانش‌آموزان از صفحه «کلاس‌های مجازی» پس از ورود به سایت، با یک کلیک وارد جلسه می‌شوند.</p>
        <button id="btn-new-class" class="shrink-0 bg-green-600 text-white px-5 py-3 rounded-lg font-bold hover:bg-green-700 shadow flex items-center gap-2"><i class="ph-duotone ph-plus-circle text-xl"></i> کلاس جدید</button>
    </div>

    <div id="classes-list" class="grid grid-cols-1 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border p-10 text-center text-gray-400">در حال بارگذاری...</div>
    </div>

    <!-- مودال کلاس -->
    <div id="modal-class" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="p-5 border-b flex items-center justify-between">
                <h3 class="font-extrabold text-lg" id="modal-class-title">کلاس جدید</h3>
                <button onclick="closeModal('modal-class')"><i class="ph-bold ph-x text-2xl text-gray-400"></i></button>
            </div>
            <form id="class-form" class="p-5 space-y-4">
                <input type="hidden" name="id" value="">
                <div>
                    <label class="block mb-1 text-sm font-bold">عنوان کلاس *</label>
                    <input name="title" required class="w-full p-3 border border-gray-300 rounded-lg" placeholder="مثلاً کلاس آنلاین ریاضی پایه نهم">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 text-sm font-bold">درس *</label>
                        <input name="subject" required class="w-full p-3 border border-gray-300 rounded-lg" placeholder="ریاضی">
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-bold">پایه</label>
                        <select name="grade" class="w-full p-3 border border-gray-300 rounded-lg bg-white">
                            <option value="">— —</option>
                            <option>هفتم</option><option>هشتم</option><option>نهم</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block mb-1 text-sm font-bold">دبیر</label>
                    <input name="teacher" class="w-full p-3 border border-gray-300 rounded-lg" placeholder="نام دبیر">
                </div>
                <div>
                    <label class="block mb-1 text-sm font-bold">توضیحات</label>
                    <textarea name="description" rows="2" class="w-full p-3 border border-gray-300 rounded-lg"></textarea>
                </div>
                <div>
                    <label class="block mb-1 text-sm font-bold">وضعیت</label>
                    <select name="status" class="w-full p-3 border border-gray-300 rounded-lg bg-white">
                        <option value="active">فعال</option>
                        <option value="inactive">غیرفعال (برای دانش‌آموزان دیده نمی‌شود)</option>
                    </select>
                </div>
                <div id="class-msg" class="text-center font-bold h-6 text-sm"></div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeModal('modal-class')" class="flex-1 bg-gray-100 py-3 rounded-lg font-bold text-gray-700">انصراف</button>
                    <button type="submit" class="flex-1 bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 shadow">ذخیره</button>
                </div>
            </form>
        </div>
    </div>

    <!-- مودال دانش‌آموزان -->
    <div id="modal-students" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] flex flex-col">
            <div class="p-5 border-b flex items-center justify-between shrink-0">
                <h3 class="font-extrabold text-lg">دسترسی دانش‌آموزان — <span id="students-class-name"></span></h3>
                <button onclick="closeModal('modal-students')"><i class="ph-bold ph-x text-2xl text-gray-400"></i></button>
            </div>
            <div class="p-5 space-y-5 overflow-y-auto">
                <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                    <p class="text-sm font-bold mb-3">اضافه کردن سریع (همه دانش‌آموزان فعالِ یک کلاس مدرسه)</p>
                    <div class="flex gap-3 items-center flex-wrap">
                        <select id="quick-schoolclass" class="p-3 border border-gray-300 rounded-lg bg-white">
                            <option>۷-۱</option><option>۷-۲</option><option>۷-۳</option>
                            <option>۸-۱</option><option>۸-۲</option><option>۸-۳</option>
                            <option>۹-۱</option><option>۹-۲</option><option>۹-۳</option>
                        </select>
                        <button id="quick-add-btn" class="bg-green-600 text-white px-5 py-3 rounded-lg font-bold hover:bg-green-700"><i class="ph-duotone ph-user-plus text-lg"></i> افزودن همه</button>
                        <span id="quick-msg" class="text-sm font-bold text-green-700"></span>
                    </div>
                </div>
                <div>
                    <label class="block mb-1 text-sm font-bold">اضافه کردن با کد کاربری (با ویرگول/خط جدایی کن)</label>
                    <textarea id="bulk-codes" rows="3" dir="ltr" class="w-full p-3 border border-gray-300 rounded-lg font-mono mono text-left leading-8" placeholder="s1405001, s1405002"></textarea>
                    <button id="bulk-add-btn" class="mt-2 bg-blue-600 text-white px-5 py-2.5 rounded-lg font-bold text-sm hover:bg-blue-700">افزودن لیست</button>
                    <div id="bulk-msg" class="mt-2 text-sm font-bold"></div>
                </div>
                <div>
                    <p class="font-extrabold mb-3">دسترسی‌های فعلی <span id="students-count-label" class="text-gray-400 text-sm font-bold"></span></p>
                    <div id="students-list" class="space-y-2"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- مودال جلسه -->
    <div id="modal-session" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="p-5 border-b flex items-center justify-between">
                <h3 class="font-extrabold text-lg">وضعیت جلسه — <span id="session-class-name"></span></h3>
                <button onclick="closeModal('modal-session')"><i class="ph-bold ph-x text-2xl text-gray-400"></i></button>
            </div>
            <div class="p-5 space-y-4">
                <div id="session-box" class="rounded-xl border p-4 leading-8 text-sm"></div>
                <div class="flex flex-wrap gap-3">
                    <button id="session-refresh" class="bg-gray-100 py-3 px-5 rounded-lg font-bold text-gray-700 hover:bg-gray-200"><i class="ph-duotone ph-arrows-clockwise text-lg"></i> بررسی وضعیت</button>
                    <button id="session-create" class="bg-green-600 text-white py-3 px-5 rounded-lg font-bold hover:bg-green-700"><i class="ph-duotone ph-play-circle text-lg"></i> ساخت جلسه</button>
                    <button id="session-end" class="bg-red-600 text-white py-3 px-5 rounded-lg font-bold hover:bg-red-700 hidden"><i class="ph-duotone ph-stop-circle text-lg"></i> پایان جلسه</button>
                </div>
                <div id="session-msg" class="text-center font-bold h-6 text-sm"></div>
            </div>
        </div>
    </div>

<?php require '_bottom.php'; ?>
<script>
const API = '/files/api/classroom.php';
let ALL_CLASSES = [];
let currentClass = null;

function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

function sessionPill(c) {
    if (!c.has_session) return '<span class="px-2 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-500">بدون جلسه</span>';
    return '<span class="px-2 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">جلسه فعال</span> <span class="text-xs text-gray-400">' + esc(c.session_created_fa || '') + '</span>';
}

async function loadAll() {
    const [cls, dp] = await Promise.all([
        PANEL.api(API, { action: 'classes_list' }),
        PANEL.api(API, { action: 'dp_status' }),
    ]);
    ALL_CLASSES = (cls.classes || []);
    const banner = document.getElementById('dp-banner');
    if (!dp.configured) {
        banner.className = 'rounded-2xl shadow-sm border p-5 mb-6 text-sm font-bold leading-7 bg-amber-50 border-amber-200 text-amber-900';
        banner.innerHTML = '<i class="ph-duotone ph-warning text-xl"></i> اتصال به داناپلاس تنظیم نشده است. ابتدا در <a href="/panel/settings.php" class="underline">تنظیمات</a> «آدرس API داناپلاس» و «کلید اشتراکی» را وارد کنید. تا آن زمان دکمه‌های جلسه کار نمی‌کنند.';
    } else if (dp.transport_error) {
        banner.className = 'rounded-2xl shadow-sm border p-5 mb-6 text-sm font-bold leading-7 bg-amber-50 border-amber-200 text-amber-900';
        banner.innerHTML = '<i class="ph-duotone ph-warning text-xl"></i> ' + esc(dp.transport_error);
    } else {
        banner.className = 'rounded-2xl shadow-sm border p-5 mb-6 text-sm font-bold leading-7 bg-green-50 border-green-200 text-green-800';
        banner.innerHTML = '<i class="ph-duotone ph-wifi-high text-xl"></i> اتصال داناپلاس تنظیم شده است (' + esc(dp.host) + ').';
    }
    renderClasses();
}

function renderClasses() {
    const wrap = document.getElementById('classes-list');
    if (!ALL_CLASSES.length) {
        wrap.innerHTML = '<div class="bg-white rounded-2xl shadow-sm border p-10 text-center text-gray-400">هنوز کلاسی نساخته‌اید. با دکمه «کلاس جدید» شروع کنید.</div>';
        return;
    }
    wrap.innerHTML = '';
    ALL_CLASSES.forEach(c => {
        const card = document.createElement('div');
        card.className = 'bg-white rounded-2xl shadow-sm border p-6';
        card.innerHTML = `
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h3 class="text-lg font-extrabold text-gray-800">${esc(c.title)}</h3>
                        <span class="px-2 py-1 rounded-full text-xs font-bold bg-teal-100 text-teal-700">${esc(c.subject)}</span>
                        ${c.grade ? `<span class="px-2 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700">${esc(c.grade)}</span>` : ''}
                        <span class="px-2 py-1 rounded-full text-xs font-bold ${c.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}">${c.status === 'active' ? 'فعال' : 'غیرفعال'}</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">
                        ${c.teacher ? 'دبیر: <b>' + esc(c.teacher) + '</b> — ' : ''}
                        ${pDigits(c.students)} دانش‌آموز مجاز — ${sessionPill(c)}
                    </p>
                    ${c.description ? `<p class="text-sm text-gray-400 mt-1">${esc(c.description)}</p>` : ''}
                </div>
                <div class="flex flex-wrap gap-2">
                    <button onclick="openStudents('${c.id}')" class="bg-green-100 text-green-700 font-bold px-4 py-2.5 rounded-lg hover:bg-green-200 text-sm flex items-center gap-1"><i class="ph-duotone ph-users-three text-lg"></i> دانش‌آموزان</button>
                    <button onclick="openSession('${c.id}')" class="bg-blue-100 text-blue-700 font-bold px-4 py-2.5 rounded-lg hover:bg-blue-200 text-sm flex items-center gap-1"><i class="ph-duotone ph-video-camera text-lg"></i> جلسه</button>
                    <button onclick="openEdit('${c.id}')" class="bg-gray-100 text-gray-700 font-bold px-4 py-2.5 rounded-lg hover:bg-gray-200 text-sm flex items-center gap-1"><i class="ph-duotone ph-pencil-simple text-lg"></i> ویرایش</button>
                    <button onclick="delClass('${c.id}', '${esc(c.title)}')" class="bg-red-100 text-red-700 font-bold px-4 py-2.5 rounded-lg hover:bg-red-200 text-sm flex items-center gap-1"><i class="ph-duotone ph-trash text-lg"></i> حذف</button>
                </div>
            </div>`;
        wrap.appendChild(card);
    });
}

function openEdit(id) {
    const c = ALL_CLASSES.find(x => x.id === id);
    if (!c) return;
    const f = document.getElementById('class-form');
    f.id.value = id;
    f.title.value = c.title || '';
    f.subject.value = c.subject || '';
    f.grade.value = c.grade || '';
    f.teacher.value = c.teacher || '';
    f.description.value = c.description || '';
    f.status.value = c.status || 'active';
    document.getElementById('modal-class-title').textContent = 'ویرایش کلاس';
    document.getElementById('class-msg').textContent = '';
    openModal('modal-class');
}
function openEdit_nullEdit() {
    const f = document.getElementById('class-form');
    f.reset(); f.id.value = '';
    document.getElementById('modal-class-title').textContent = 'کلاس جدید';
    document.getElementById('class-msg').textContent = '';
    openModal('modal-class');
}
async function delClass(id, title) {
    if (!confirm('کلاس «' + title + '» به همراه تمام دسترسی‌ها و سابقه جلساتش حذف شود؟ این عمل برگشت‌پذیر نیست.')) return;
    const r = await PANEL.api(API, { action: 'class_delete', id });
    PANEL.toast(r.message, !r.success);
    if (r.success) loadAll();
}

async function openStudents(id) {
    const c = ALL_CLASSES.find(x => x.id === id);
    if (!c) return;
    currentClass = c;
    document.getElementById('students-class-name').textContent = c.title;
    document.getElementById('bulk-msg').textContent = '';
    document.getElementById('bulk-codes').value = '';
    await loadEnrollments();
    openModal('modal-students');
}
async function loadEnrollments() {
    const r = await PANEL.api(API, { action: 'enrollments_list', class_id: currentClass.id });
    const wrap = document.getElementById('students-list');
    const st = r.students || [];
    document.getElementById('students-count-label').textContent = '(' + pDigits(st.length) + ' نفر)';
    if (!st.length) {
        wrap.innerHTML = '<div class="text-sm text-gray-400 py-4 text-center border rounded-xl">هنوز دسترسی‌ای مشخص نشده است.</div>';
        return;
    }
    wrap.innerHTML = '';
    st.forEach(s => {
        const row = document.createElement('div');
        row.className = 'flex items-center justify-between gap-3 bg-gray-50 border rounded-xl px-4 py-2.5';
        row.innerHTML = `
            <div class="flex items-center gap-3 flex-wrap">
                <span class="font-bold text-sm">${esc(s.name)}</span>
                <span class="font-mono text-xs text-gray-500" dir="ltr">${esc(s.student_code)}</span>
                ${s.school_class ? `<span class="px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700">${esc(s.school_class)}</span>` : ''}
                ${s.active ? '' : '<span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">حساب غیرفعال</span>'}
                ${s.exists ? '' : '<span class="px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">حساب حذف شده</span>'}
            </div>
            <button title="حذف دسترسی" class="text-red-600 hover:bg-red-100 rounded-lg px-2 py-1"><i class="ph-bold ph-x text-lg"></i></button>`;
        row.querySelector('button').onclick = async () => {
            const res = await PANEL.api(API, { action: 'enroll_remove', class_id: currentClass.id, student_code: s.student_code });
            if (res.success) loadEnrollments(); else PANEL.toast(res.message, true);
        };
        wrap.appendChild(row);
    });
}
document.getElementById('quick-add-btn').addEventListener('click', async () => {
    const sc = document.getElementById('quick-schoolclass').value;
    const r = await PANEL.api(API, { action: 'enroll_add_schoolclass', class_id: currentClass.id, school_class: sc });
    document.getElementById('quick-msg').textContent = r.success ? r.message : r.message;
    document.getElementById('quick-msg').className = 'text-sm font-bold ' + (r.success ? 'text-green-700' : 'text-red-600');
    if (r.success) { loadEnrollments(); loadAll(); }
});
document.getElementById('bulk-add-btn').addEventListener('click', async () => {
    const codes = document.getElementById('bulk-codes').value;
    if (!codes.trim()) return;
    const r = await PANEL.api(API, { action: 'enroll_add', class_id: currentClass.id, codes });
    const msg = document.getElementById('bulk-msg');
    let html = esc(r.message);
    if ((r.invalid || []).length) html += ' — کدهای نامعتبر: <span class="font-mono" dir="ltr">' + esc(r.invalid.join(', ')) + '</span>';
    msg.innerHTML = html;
    msg.className = 'mt-2 text-sm font-bold ' + (r.success ? 'text-green-700' : 'text-red-600');
    if (r.success) { document.getElementById('bulk-codes').value = ''; loadEnrollments(); loadAll(); }
});

async function openSession(id) {
    const c = ALL_CLASSES.find(x => x.id === id);
    if (!c) return;
    currentClass = c;
    document.getElementById('session-class-name').textContent = c.title;
    document.getElementById('session-msg').textContent = '';
    openModal('modal-session');
    refreshSession();
}
async function refreshSession() {
    const box = document.getElementById('session-box');
    box.innerHTML = '<p class="text-gray-400">در حال بررسی...</p>';
    const r = await PANEL.api(API, { action: 'session_status', class_id: currentClass.id });
    const btnEnd = document.getElementById('session-end');
    if (!r.has_session) {
        box.className = 'rounded-xl border p-4 leading-8 text-sm bg-gray-50 text-gray-600';
        box.innerHTML = 'برای این کلاس جلسه‌ای ساخته نشده است. با دکمه «ساخت جلسه» یک نشست داناپلاس ایجاد کنید؛ سپس دانش‌آموزان مجاز می‌توانند از صفحه کلاس‌های مجازی وارد شوند.';
        btnEnd.classList.add('hidden');
        return;
    }
    btnEnd.classList.remove('hidden');
    if (r.running) {
        box.className = 'rounded-xl border p-4 leading-8 text-sm bg-green-50 border-green-200 text-green-900';
        box.innerHTML = `<b>جلسه در حال برگزاری است.</b><br>شرکت‌کنندگان فعلی: ${pDigits(r.participants)} نفر` +
            (r.names && r.names.length ? '<div class="mt-2 flex flex-wrap gap-2">' + r.names.map(n => '<span class="px-2 py-0.5 rounded-full text-xs bg-white text-gray-700">' + esc(n) + '</span>').join('') + '</div>' : '');
    } else {
        box.className = 'rounded-xl border p-4 leading-8 text-sm bg-blue-50 border-blue-200 text-blue-900';
        box.innerHTML = 'جلسه ساخته شده و آماده ورود است (هنوز شروع نشده). دانش‌آموزان با کلیک روی «ورود به کلاس» وارد می‌شوند و جلسه بلافاصله شروع می‌شود.' +
            (r.api_error ? '<div class="text-amber-700 mt-2">نکته: ' + esc(r.api_error) + '</div>' : '');
    }
}
document.getElementById('session-refresh').addEventListener('click', refreshSession);
document.getElementById('session-create').addEventListener('click', async () => {
    if (!confirm('جلسه جدید ساخته شود؟')) return;
    const r = await PANEL.api(API, { action: 'session_create', class_id: currentClass.id });
    const msg = document.getElementById('session-msg');
    msg.textContent = r.message;
    msg.className = 'text-center font-bold h-6 text-sm ' + (r.success ? 'text-green-600' : 'text-red-600');
    if (r.success) { refreshSession(); loadAll(); }
});
document.getElementById('session-end').addEventListener('click', async () => {
    if (!confirm('جلسه برای همه پایان داده شود؟')) return;
    const r = await PANEL.api(API, { action: 'session_end', class_id: currentClass.id });
    PANEL.toast(r.message, !r.success);
    document.getElementById('session-msg').textContent = r.message;
    refreshSession(); loadAll();
});

document.getElementById('btn-new-class').addEventListener('click', openEdit_nullEdit);
document.getElementById('class-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append('action', 'class_save');
    const r = await PANEL.api(API, fd);
    const msg = document.getElementById('class-msg');
    msg.textContent = r.message;
    msg.className = 'text-center font-bold h-6 text-sm ' + (r.success ? 'text-green-600' : 'text-red-600');
    if (r.success) { closeModal('modal-class'); loadAll(); }
});

document.addEventListener('DOMContentLoaded', loadAll);
</script>
