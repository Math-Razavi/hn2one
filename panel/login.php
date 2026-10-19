<?php
// login.php — ورود مدیر به پنل
require_once __DIR__ . '/../files/api/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// اگر مدیر قبلاً وارد شده → مستقیم داشبورد
if (!empty($_SESSION['user_code']) && ($_SESSION['user']['role'] ?? '') === 'admin') {
    header('Location: dashboard.php');
    exit;
}

$error = $_SESSION['panel_login_error'] ?? '';
unset($_SESSION['panel_login_error']);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود مدیر | پنل مدیریتی</title>
    <link rel="icon" href="/files/uploads/site/logo-footer.png">
    <script src="/assets/js/tailwindcss.js"></script>
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="stylesheet" href="/assets/phosphor-icons/fill/style.css">
    <style>body { font-family: 'Vazirmatn', sans-serif; }</style>
</head>
<body class="bg-gray-100 text-gray-900 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <img src="/files/uploads/site/logo-header.png" alt="لوگو" class="w-20 mx-auto rounded-xl shadow">
            <h1 class="text-2xl font-extrabold mt-4 text-gray-800">پنل مدیریتی</h1>
            <p class="text-gray-500 mt-1">دبیرستان دوره اول استعدادهای درخشان شهید هاشمی‌نژاد ۲</p>
        </div>

        <form method="post" class="bg-white rounded-2xl shadow-xl p-8 space-y-4">
            <div id="login-error" class="hidden bg-red-50 text-red-700 border border-red-200 rounded-lg px-4 py-3 text-sm font-bold"></div>
            <div>
                <label class="block mb-1 font-semibold text-sm">کد کاربری مدیر</label>
                <input type="text" name="code" dir="ltr" required class="w-full p-3 border border-gray-300 rounded-lg text-center font-mono" placeholder="admin">
            </div>
            <div>
                <label class="block mb-1 font-semibold text-sm">رمز عبور</label>
                <input type="password" name="password" dir="ltr" required class="w-full p-3 border border-gray-300 rounded-lg text-center" autocomplete="current-password">
            </div>
            <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 transition shadow">ورود به پنل</button>
            <p class="text-center text-sm text-gray-400"><a href="/index.html" class="hover:underline">بازگشت به سایت</a></p>
        </form>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const code = form.querySelector('[name="code"]').value.trim();
        const password = form.querySelector('[name="password"]').value;
        const errBox = document.getElementById('login-error');
        const fd = new FormData();
        fd.append('action', 'login');
        fd.append('code', code);
        fd.append('password', password);
        const r = await (await fetch('/files/api/auth.php', { method: 'POST', body: fd })).json();
        if (r.success && (r.user.role === 'admin')) {
            window.location.href = 'dashboard.php';
        } else if (r.success) {
            await fetch('/files/api/auth.php', { method: 'POST', body: new URLSearchParams({ action: 'logout' }) });
            errBox.textContent = 'این حساب مدیر پنل نیست.';
            errBox.classList.remove('hidden');
        } else {
            errBox.textContent = r.message || 'خطا در ورود.';
            errBox.classList.remove('hidden');
        }
    });
});
</script>
</body>
</html>
