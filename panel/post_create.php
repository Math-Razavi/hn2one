<?php
// post_create.php — انتشار خبر جدید (تصاویر چندگانه)
require_once '_guard.php';

$PANEL_TITLE = 'خبر جدید';
$PANEL_ACTIVE = 'posts';
require '_top.php';
?>
    <div class="bg-white rounded-2xl shadow-sm border max-w-3xl p-8">
        <form id="create-form" class="space-y-5">
            <div>
                <label class="block mb-1 text-sm font-bold">عنوان خبر <span class="text-red-500">*</span></label>
                <input name="title" required class="w-full p-3 border border-gray-300 rounded-lg" placeholder="عنوان کامل خبر">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                <label class="block mb-1 text-sm font-bold">متن خبر <span class="text-red-500">*</span></label>
                <p class="text-xs text-gray-400 mb-2">هر پاراگراف را با ENTER جدا کنید.</p>
                <textarea name="body" rows="10" required class="w-full p-3 border border-gray-300 rounded-lg" placeholder="متن کامل خبر..."></textarea>
            </div>
            <div>
                <label class="block mb-1 text-sm font-bold">عکس‌های خبر (چندگانه)</label>
                <p class="text-xs text-gray-400 mb-2">عکس اول به عنوان کاور در فهرست اخبار استفاده می‌شود (JPG/PNG/WebP، حداکثر ۲۰MB هر عکس).</p>
                <input name="image" type="file" multiple accept="image/jpeg,image/png,image/webp"
                       class="w-full p-2 border border-gray-300 rounded-lg file:mr-3 file:px-4 file:py-1 file:rounded-lg file:border-0 file:bg-green-100 file:text-green-800 file:font-bold">
            </div>
            <div id="create-msg" class="text-center font-bold h-6 text-sm"></div>
            <div class="flex gap-3">
                <a href="posts.php" class="flex-1 text-center bg-gray-200 text-gray-800 py-3 rounded-lg font-bold hover:bg-gray-300">انصراف</a>
                <button type="submit" class="flex-1 bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 shadow">انتشار خبر</button>
            </div>
        </form>
    </div>

<?php require '_bottom.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('create-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const msg = document.getElementById('create-msg');
        const btn = e.target.querySelector('button[type=submit]');
        btn.disabled = true;
        btn.textContent = 'در حال ارسال...';
        msg.textContent = '';
        const fd = new FormData(e.target);
        // تعداد فایل‌های input توسط PHP خودکار به ساختار آرایه‌ای تبدیل می‌شود
        const r = await PANEL.api('/files/api/admin.php', fd);
        btn.disabled = false;
        btn.textContent = 'انتشار خبر';
        msg.textContent = r.message;
        msg.className = 'text-center font-bold h-6 text-sm ' + (r.success ? 'text-green-600' : 'text-red-600');
        if (r.success) setTimeout(() => window.location.href = 'posts.php', 1200);
    });
});
</script>
