<?php
// router.php — مسیریاب وب‌سرور لوکال (php -S) برای بستن دسترسی مستقیم به داده‌ها
// اجرا: php -S 127.0.0.1:8000 router.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

// مسدود کردن دسترسی مستقیم به داده‌ها و پیکربندی
$blocked = [
    '/files/data/',
    '/files/api/config.php',
    '/files/api/jalali.php',
    '/panel/data',
];
foreach ($blocked as $b) {
    if (strpos($path, $b) === 0) {
        http_response_code(403);
        echo '403 Forbidden';
        return true;
    }
}

if ($path !== '/' && file_exists($file)) {
    return false; // فایل استاتیک/اسکریپت سرو شود
}

if (is_dir($file) && file_exists($file . '/index.html')) {
    return false;
}

http_response_code(404);
readfile(__DIR__ . '/404.html');
return true;
