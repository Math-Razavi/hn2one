<?php
// _guard.php — محافظت از صفحات پنل (فقط مدیر)
require_once __DIR__ . '/../files/api/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_code'])) {
    header('Location: login.php');
    exit;
}

$PANEL_USER = $_SESSION['user'];
if (($PANEL_USER['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><title>دسترسی مجاز نیست</title>';
    echo '<script src="/assets/js/tailwindcss.js"></script><link rel="stylesheet" href="/assets/css/fonts.css">';
    echo '<style>body{font-family:Vazirmatn,sans-serif}</style></head>';
    echo '<body class="bg-gray-100 flex items-center justify-center min-h-screen"><div class="bg-white p-10 rounded-2xl shadow-xl text-center">';
    echo '<div class="text-6xl mb-4">&#128274;</div><h1 class="text-2xl font-bold text-gray-800 mb-2">دسترسی مجاز نیست</h1>';
    echo '<p class="text-gray-500 mb-6">این بخش فقط برای مدیر سایت است.</p>';
    echo '<a href="/index.html" class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold">بازگشت به سایت</a></div></body></html>';
    exit;
}

$CSRF = hn_csrf_token();

// اعداد فارسی برای خروجی PHP صفحات پنل
function pDigits($value) {
    return strtr((string)$value, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
}
