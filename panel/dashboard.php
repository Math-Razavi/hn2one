<?php
// dashboard.php — آمار و لینک‌های سریع
require_once '_guard.php';

$posts = hn_load('posts.json');
$pending = array_filter($posts, fn($p) => ($p['status'] ?? '') === 'pending');
$published = array_filter($posts, fn($p) => ($p['status'] ?? '') === 'published');
$users = hn_all_users();
$students = array_filter($users, fn($u) => ($u['role'] ?? '') === 'student');
$teachers = array_filter($users, fn($u) => ($u['role'] ?? '') === 'teacher');
$registrations = hn_load('registrations.json');
$messages = hn_load('messages.json');
$gallery = hn_load('gallery.json');
$exams = hn_load('exams.json');
$staff = hn_load('staff.json');

$stats = [
    ['icon' => 'ph-newspaper', 'label' => 'اخبار منتشرشده', 'count' => count($published), 'href' => 'posts.php', 'color' => 'bg-green-100 text-green-700'],
    ['icon' => 'ph-hourglass', 'label' => 'در انتظار تایید', 'count' => count($pending), 'href' => 'posts.php', 'color' => 'bg-orange-100 text-orange-700'],
    ['icon' => 'ph-user-gear', 'label' => 'حساب کاربران', 'count' => count($users), 'href' => 'students.php', 'color' => 'bg-blue-100 text-blue-700'],
    ['icon' => 'ph-user-plus', 'label' => 'فرم‌های ثبت‌نام', 'count' => count($registrations), 'href' => 'registrations.php', 'color' => 'bg-purple-100 text-purple-700'],
    ['icon' => 'ph-chat-circle-text', 'label' => 'پیام‌های تماس', 'count' => count($messages), 'href' => 'messages.php', 'color' => 'bg-pink-100 text-pink-700'],
    ['icon' => 'ph-images', 'label' => 'عکس‌های گالری', 'count' => count($gallery), 'href' => 'gallery.php', 'color' => 'bg-yellow-100 text-yellow-700'],
    ['icon' => 'ph-exam', 'label' => 'آزمون‌های آنلاین', 'count' => count($exams), 'href' => '/azmoon/teacher.html', 'color' => 'bg-teal-100 text-teal-700'],
    ['icon' => 'ph-users-three', 'label' => 'کادر آموزشی', 'count' => count($staff), 'href' => 'staff.php', 'color' => 'bg-indigo-100 text-indigo-700'],
];

// آخرین فرم‌های ثبت‌نام ثبت نشده (new)
$newRegs = array_filter($registrations, fn($r) => ($r['status'] ?? 'new') === 'new');

$pendingPosts = array_values(array_filter($posts, fn($p) => ($p['status'] ?? '') === 'pending'));

$PANEL_TITLE = 'داشبورد';
$PANEL_ACTIVE = 'dashboard';
require '_top.php';
?>
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 mb-8">
        <?php foreach ($stats as $s): ?>
            <a href="<?php echo $s['href']; ?>" class="bg-white rounded-2xl shadow-sm border p-5 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-3 <?php echo $s['color']; ?>">
                    <i class="ph-duotone <?php echo $s['icon']; ?> text-2xl"></i>
                </div>
                <p class="text-3xl font-black text-gray-800"><?php echo pDigits($s['count']); ?></p>
                <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($s['label']); ?></p>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- اخبار در انتظار تایید -->
        <div class="bg-white rounded-2xl shadow-sm border p-6">
            <h2 class="text-lg font-extrabold text-gray-800 mb-4 flex items-center gap-2">
                <i class="ph-duotone ph-hourglass text-xl text-orange-500"></i>
                گزارش‌های در انتظار تایید (از دبیران)
            </h2>
            <div class="space-y-3">
                <?php if (!$pendingPosts): ?>
                    <p class="text-sm text-gray-400 text-center py-4">در حال حاضر چیزی در انتظار تایید نیست.</p>
                <?php endif; ?>
                <?php foreach (array_slice($pendingPosts, 0, 5) as $p): ?>
                    <div class="flex items-center justify-between gap-3 bg-gray-50 rounded-xl p-3 border">
                        <div>
                            <p class="font-bold text-sm"><?php echo htmlspecialchars($p['title']); ?></p>
                            <p class="text-xs text-gray-500 mt-1">سازنده: <?php echo htmlspecialchars($p['author'] ?? '—'); ?></p>
                        </div>
                        <div class="flex gap-2 flex-shrink-0">
                            <button onclick="approvePost(<?php echo (int)$p['id']; ?>)" class="bg-green-600 text-white text-xs font-bold px-3 py-2 rounded-lg hover:bg-green-700">تایید و انتشار</button>
                            <button onclick="deletePost(<?php echo (int)$p['id']; ?>)" class="bg-red-100 text-red-700 text-xs font-bold px-3 py-2 rounded-lg hover:bg-red-200">حذف</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- آخرین فرم‌های ثبت‌نام -->
        <div class="bg-white rounded-2xl shadow-sm border p-6">
            <h2 class="text-lg font-extrabold text-gray-800 mb-4 flex items-center gap-2">
                <i class="ph-duotone ph-user-plus text-xl text-purple-500"></i>
                آخرین فرم‌های ثبت‌نام
            </h2>
            <div class="space-y-3">
                <?php if (!$registrations): ?>
                    <p class="text-sm text-gray-400 text-center py-4">هنوز فرمی ثبت نشده است.</p>
                <?php endif; ?>
                <?php foreach (array_slice(array_reverse($registrations), 0, 5) as $r): ?>
                    <div class="flex items-center justify-between gap-3 bg-gray-50 rounded-xl p-3 border">
                        <div>
                            <p class="font-bold text-sm"><?php echo htmlspecialchars($r['name']); ?> <span class="text-xs font-normal text-gray-500">(<?php echo htmlspecialchars($r['grade']); ?>)</span></p>
                            <p class="text-xs text-gray-500 mt-1" dir="rtl"><?php echo htmlspecialchars($r['student_phone']); ?></p>
                        </div>
                        <a href="registrations.php" class="text-xs font-bold text-green-600 bg-green-50 px-3 py-2 rounded-lg hover:bg-green-100">مشاهده</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

<?php require '_bottom.php'; ?>
<script>
const CSRF = '<?php echo htmlspecialchars($CSRF, ENT_QUOTES, "UTF-8"); ?>';
function approvePost(id) {
    if (!confirm('تایید و انتشار این گزارش صحیح است؟')) return;
    const fd = new FormData();
    fd.append('action', 'set_post_status');
    fd.append('id', id);
    fd.append('status', 'published');
    fd.append('csrf_token', CSRF);
    fetch('/files/api/admin.php', { method: 'POST', body: fd }).then(r => r.json()).then(r => {
        PANEL.toast(r.message, !r.success);
        if (r.success) setTimeout(() => location.reload(), 900);
    });
}
function deletePost(id) {
    if (!confirm('این گزارش حذف شود؟')) return;
    const fd = new FormData();
    fd.append('action', 'delete_post');
    fd.append('id', id);
    fd.append('csrf_token', CSRF);
    fetch('/files/api/admin.php', { method: 'POST', body: fd }).then(r => r.json()).then(r => {
        PANEL.toast(r.message, !r.success);
        if (r.success) setTimeout(() => location.reload(), 900);
    });
}
</script>
