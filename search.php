<?php
// search.php — API جستجوی سایت (صفحات ثابت + پست‌ها)
require_once __DIR__ . '/files/api/config.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (mb_strlen($query, 'UTF-8') < 2) {
    send_response(['status' => 'error', 'message' => 'عبارت جستجو حداقل ۲ کاراکتر باشد']);
}

// صفحات ثابت سایت
$pages = [
    ['title' => 'صفحه اصلی', 'url' => '/home.html', 'category' => 'main', 'keywords' => 'خانه, اصلی, index, شروع'],
    ['title' => 'ورود حساب کاربری', 'url' => '/auth/login.html', 'category' => 'auth', 'keywords' => 'ورود, login, حساب کاربری, دانش آموز'],
    ['title' => 'ببین مدرسه رو!', 'url' => '/gallery.html', 'category' => 'main', 'keywords' => 'گالری, عکس, تصاویر, مدرسه, تور, فضا, امکانات'],
    ['title' => 'کادر آموزشی', 'url' => '/staff.html', 'category' => 'staff', 'keywords' => 'معلمان, دبیران, کادر, مدیر, معاون, معرفی'],
    ['title' => 'درباره ما', 'url' => '/about.html', 'category' => 'info', 'keywords' => 'درباره ما, معرفی مدرسه, about'],
    ['title' => 'ارتباط با ما', 'url' => '/contact.html', 'category' => 'info', 'keywords' => 'تماس, تلفن, آدرس, ایمیل, پیام, ارتباط'],
    ['title' => 'فرم ثبت نام', 'url' => '/register.html', 'category' => 'form', 'keywords' => 'ثبت نام, ثبت‌نام, فرم, پذیرش'],
    ['title' => 'آزمون آنلاین', 'url' => '/azmoon/student.html', 'category' => 'exam', 'keywords' => 'آزمون, سوال, امتحان, تست, quiz'],
    ['title' => 'پروفایل کاربری', 'url' => '/profile.html', 'category' => 'auth', 'keywords' => 'پروفایل, حساب, رمز'],
];

// پست‌ها
$posts = hn_load('posts.json');
$searchablePosts = [];
foreach ($posts as $p) {
    if (($p['status'] ?? '') !== 'published') continue;
    $searchablePosts[] = [
        'title' => $p['title'],
        'url' => '/news/post.html?slug=' . $p['slug'],
        'category' => 'post',
        'keywords' => implode(', ', [$p['category'] ?? '', $p['year'] ?? '', $p['excerpt'] ?? '']),
    ];
}
$pages = array_merge($pages, $searchablePosts);

$queryLower = mb_strtolower($query, 'UTF-8');
$results = [];

foreach ($pages as $page) {
    $score = 0;
    $titleLower = mb_strtolower($page['title'], 'UTF-8');
    $keywordsLower = mb_strtolower($page['keywords'], 'UTF-8');
    $categoryLower = mb_strtolower($page['category'], 'UTF-8');

    if (mb_strpos($titleLower, $queryLower) !== false) $score += 100;
    if ($titleLower === $queryLower) $score += 50;

    foreach (explode(',', $keywordsLower) as $kw) {
        $kw = trim($kw);
        if ($kw === '') continue;
        if (mb_strpos($kw, $queryLower) !== false) $score += 10;
        if ($kw === $queryLower) $score += 20;
    }
    if (mb_strpos($categoryLower, $queryLower) !== false) $score += 15;

    if ($score > 0) {
        $page['_relevance'] = $score;
        $results[] = $page;
    }
}

usort($results, fn($a, $b) => $b['_relevance'] <=> $a['_relevance']);
$results = array_slice($results, 0, 20);

send_response(['status' => 'success', 'results' => $results, 'total' => count($results)]);
