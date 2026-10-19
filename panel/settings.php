<?php
// settings.php — تنظیمات عمومی مدرسه
require_once '_guard.php';

$s = hn_load('settings.json');
$PANEL_TITLE = 'تنظیمات';
$PANEL_ACTIVE = 'settings';
require '_top.php';
?>
    <div class="bg-white rounded-2xl shadow-sm border max-w-3xl p-8">
        <form id="settings-form" class="space-y-5">
            <div>
                <label class="block mb-1 text-sm font-bold">نام کامل مدرسه</label>
                <input name="school_name" class="w-full p-3 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($s['school_name'] ?? ''); ?>">
            </div>
            <div>
                <label class="block mb-1 text-sm font-bold">نام کوتاه مدرسه</label>
                <input name="school_short_name" class="w-full p-3 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($s['school_short_name'] ?? ''); ?>">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1 text-sm font-bold">تلفن‌ها</label>
                    <input name="phones" dir="ltr" class="w-full p-3 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($s['phones'] ?? ''); ?>">
                </div>
                <div>
                    <label class="block mb-1 text-sm font-bold">ایمیل</label>
                    <input name="email" dir="ltr" class="w-full p-3 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($s['email'] ?? ''); ?>">
                </div>
            </div>
            <div>
                <label class="block mb-1 text-sm font-bold">آدرس</label>
                <input name="address" class="w-full p-3 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($s['address'] ?? ''); ?>">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1 text-sm font-bold">لینک ایتا</label>
                    <input name="eitaa" dir="ltr" class="w-full p-3 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($s['eitaa'] ?? ''); ?>">
                </div>
                <div>
                    <label class="block mb-1 text-sm font-bold">لینک شاد</label>
                    <input name="shad" dir="ltr" class="w-full p-3 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($s['shad'] ?? ''); ?>">
                </div>
            </div>
            <div>
                <label class="block mb-1 text-sm font-bold">متن تیترها (بالای سایت)</label>
                <input name="ticker" class="w-full p-3 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($s['ticker'] ?? ''); ?>">
            </div>
            <div>
                <label class="block mb-1 text-sm font-bold">مقدمه صفحه اصلی (هر پاراگراف در یک خط)</label>
                <textarea name="intro_home" rows="5" class="w-full p-3 border border-gray-300 rounded-lg leading-8"><?php echo htmlspecialchars(implode(PHP_EOL, $s['intro_home'] ?? [])); ?></textarea>
            </div>
            <div>
                <label class="block mb-1 text-sm font-bold">مقدمه صفحه درباره ما (هر پاراگراف در یک خط)</label>
                <textarea name="about_intro" rows="5" class="w-full p-3 border border-gray-300 rounded-lg leading-8"><?php echo htmlspecialchars(implode(PHP_EOL, $s['about_intro'] ?? [])); ?></textarea>
            </div>
            <hr>
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-sm leading-7 text-green-900">
                <b class="flex items-center gap-2"><i class="ph-fill ph-sparkle text-lg"></i>هوش مصنوعی (ابزارهای دبیر)</b>
                با تنظیم این بخش، دبیران می‌توانند در پنل دبیر از «طرح درس هوشمند» و «آزمون‌ساز هوشمند» استفاده کنند.
                آدرس سرویس باید OpenAI-compatible باشد (مانند GapGPT، Groq، OpenAI یا سرویس‌های ایرانی مشابه).
            </div>
            <div>
                <label class="block mb-1 text-sm font-bold">آدرس سرویس هوش مصنوعی (Base URL)</label>
                <input name="ai_api_url" dir="ltr" placeholder="https://api.gapgpt.ir/v1" class="w-full p-3 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($s['ai_api_url'] ?? ''); ?>">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1 text-sm font-bold">کلید API</label>
                    <input name="ai_api_key" dir="ltr" autocomplete="off" placeholder="sk-..." class="w-full p-3 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($s['ai_api_key'] ?? ''); ?>">
                </div>
                <div>
                    <label class="block mb-1 text-sm font-bold">نام مدل</label>
                    <input name="ai_model" dir="ltr" placeholder="gpt-4o-mini یا llama-3.3-70b-versatile" class="w-full p-3 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($s['ai_model'] ?? ''); ?>">
                </div>
            </div>
            <hr>
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm leading-7 text-blue-900">
                <b class="flex items-center gap-2"><i class="ph-fill ph-video-camera text-lg"></i>کلاس‌های مجازی (داناپلاس)</b>
                برای فعال شدن «کلاس‌های مجازی»، آدرس سرویس و کلید اشتراکی داناپلاس را وارد کنید (از پشتیبانی داناپلاس بخواهید).
                کلید اشتراکی فقط سمت سرور ذخیره می‌شود و هرگز به دانش‌آموزان نمایش داده نمی‌شود. آدرس حتماً باید HTTPS باشد.
            </div>
            <div>
                <label class="block mb-1 text-sm font-bold">آدرس API داناپلاس</label>
                <input name="dp_api_address" dir="ltr" autocomplete="off" placeholder="https://example.danaplus.com" class="w-full p-3 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($s['dp_api_address'] ?? ''); ?>">
            </div>
            <div>
                <label class="block mb-1 text-sm font-bold">کلید اشتراکی داناپلاس (Shared Secret)</label>
                <input name="dp_shared_secret" dir="ltr" autocomplete="off" placeholder="shared-secret-..." class="w-full p-3 border border-gray-300 rounded-lg" value="<?php echo htmlspecialchars($s['dp_shared_secret'] ?? ''); ?>">
            </div>
            <div id="settings-msg" class="text-center font-bold h-6 text-sm"></div>
            <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 shadow">ذخیره تنظیمات</button>
        </form>
    </div>

<?php require '_bottom.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('settings-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const msg = document.getElementById('settings-msg');
        msg.textContent = 'در حال ذخیره...';
        msg.className = 'text-center font-bold h-6 text-sm text-gray-500';
        const fd = new FormData(e.target);
        fd.append('action', 'save');
        const r = await PANEL.api('/files/api/settings.php', fd);
        msg.textContent = r.message;
        msg.className = 'text-center font-bold h-6 text-sm ' + (r.success ? 'text-green-600' : 'text-red-600');
    });
});
</script>
