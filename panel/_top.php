<?php
// _top.php — بالای صفحه پنل: هد + سایدبار (متغیرها: $PANEL_TITLE, $PANEL_ACTIVE)
$PANEL_TITLE = $PANEL_TITLE ?? 'پنل مدیریتی';
$PANEL_ACTIVE = $PANEL_ACTIVE ?? '';

$navItems = [
    'dashboard' => ['آزاد' => false, 'label' => 'داشبورد', 'href' => 'dashboard.php', 'icon' => 'ph-duotone ph-squares-four', 'group' => 'اصلی'],
    'posts' => ['label' => 'اخبار و گزارش‌ها', 'href' => 'posts.php', 'icon' => 'ph-duotone ph-newspaper', 'group' => 'اصلی'],
    'announcements' => ['label' => 'اطلاعیه‌ها (نوار)', 'href' => 'announcements.php', 'icon' => 'ph-duotone ph-megaphone', 'group' => 'اصلی'],
    'classes' => ['label' => 'کلاس‌های مجازی', 'href' => 'classes.php', 'icon' => 'ph-duotone ph-video-camera', 'group' => 'اصلی'],
    'gallery' => ['label' => 'گالری تصاویر', 'href' => 'gallery.php', 'icon' => 'ph-duotone ph-images', 'group' => 'محتوا'],
    'staff' => ['label' => 'کادر آموزشی', 'href' => 'staff.php', 'icon' => 'ph-duotone ph-users-three', 'group' => 'محتوا'],
    'faq' => ['label' => 'سوالات متداول', 'href' => 'faq.php', 'icon' => 'ph-duotone ph-question', 'group' => 'محتوا'],
    'facilities' => ['label' => 'امکانات مدرسه', 'href' => 'facilities.php', 'icon' => 'ph-duotone ph-buildings', 'group' => 'محتوا'],
    'students' => ['label' => 'حساب کاربران', 'href' => 'students.php', 'icon' => 'ph-duotone ph-user-gear', 'group' => 'کاربران'],
    'registrations' => ['label' => 'فرم‌های ثبت‌نام', 'href' => 'registrations.php', 'icon' => 'ph-duotone ph-user-plus', 'group' => 'کاربران'],
    'messages' => ['label' => 'پیام‌های تماس', 'href' => 'messages.php', 'icon' => 'ph-duotone ph-chat-circle-text', 'group' => 'کاربران'],
    'settings' => ['label' => 'تنظیمات', 'href' => 'settings.php', 'icon' => 'ph-duotone ph-gear-six', 'group' => 'سیستم'],
];
$groups = [];
foreach ($navItems as $key => $item) {
    if (!isset($groups[$item['group'] ?? 'اصلی'])) $groups[$item['group'] ?? 'اصلی'] = [];
    $groups[$item['group'] ?? 'اصلی'][$key] = $item;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($CSRF, ENT_QUOTES, 'UTF-8'); ?>">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <title>پنل — <?php echo htmlspecialchars($PANEL_TITLE, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" href="/files/uploads/site/logo-footer.png">
    <script src="/assets/js/tailwindcss.js"></script>
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="stylesheet" href="/assets/phosphor-icons/duotone/style.css">
    <link rel="stylesheet" href="/assets/phosphor-icons/bold/style.css">
    <style>
        body { font-family: 'Vazirmatn', sans-serif; }
        .nav-link.active { background-color: #16a34a; color: #fff; }
        input:focus, select:focus, textarea:focus { outline: 2px solid #86efac; outline-offset: 0; }
    </style>
</head>
<body class="bg-gray-100 text-gray-900">
<!-- سایدبار -->
<aside id="sidebar" class="fixed right-0 top-0 h-full w-64 bg-gray-900 text-gray-300 z-40 transform transition-transform duration-300 translate-x-full lg:translate-x-0 overflow-y-auto">
    <div class="p-5 border-b border-gray-800">
        <div class="flex items-center gap-3">
            <img src="/files/uploads/site/logo-header.png" alt="لوگو" class="w-10 h-10 rounded-lg object-cover bg-white p-1">
            <div>
                <p class="font-bold text-white text-sm">پنل مدیریتی</p>
                <p class="text-xs text-gray-500" id="admin-name"><?php echo htmlspecialchars($PANEL_USER['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
    </div>
    <nav class="p-4 space-y-1">
        <?php foreach ($groups as $groupName => $items):
            if ($groupName === 'سیستم') continue; ?>
            <p class="text-xs text-gray-600 font-bold mt-4 mb-2 px-3"><?php echo htmlspecialchars($groupName); ?></p>
            <?php foreach ($items as $key => $item): ?>
                <a href="<?php echo $item['href']; ?>"
                   class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-800 hover:text-white transition text-sm <?php echo $PANEL_ACTIVE === $key ? 'active' : ''; ?>">
                    <i class="<?php echo $item['icon']; ?> text-xl"></i>
                    <span><?php echo htmlspecialchars($item['label']); ?></span>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>
    <nav class="p-4 pt-0 space-y-1 border-t border-gray-800 mt-4">
        <a href="settings.php"
           class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-800 hover:text-white transition text-sm <?php echo $PANEL_ACTIVE === 'settings' ? 'active' : ''; ?>">
            <i class="ph-duotone ph-gear-six text-xl"></i>
            <span>تنظیمات</span>
        </a>
    </nav>
</aside>

<!-- روکش موبایل -->
<div id="sidebar-overlay" class="hidden fixed inset-0 bg-black/50 z-30 lg:hidden"></div>

<!-- محتوا -->
<div id="main-wrap" class="lg:mr-64 min-h-screen flex flex-col transition-all duration-300">
    <header class="bg-white shadow-sm sticky top-0 z-20">
        <div class="px-4 sm:px-8 h-16 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <button id="sidebar-toggle" class="lg:hidden text-2xl text-gray-700"><i class="ph-duotone ph-list"></i></button>
                <h1 class="font-extrabold text-gray-800"><?php echo htmlspecialchars($PANEL_TITLE); ?></h1>
            </div>
            <div class="flex items-center gap-2">
                <a href="/index.html" target="_blank" class="text-sm font-bold text-gray-600 hover:text-green-600 flex items-center gap-1 px-3 py-2"><i class="ph-duotone ph-globe-hemisphere-west text-xl"></i><span class="hidden sm:inline">مشاهده سایت</span></a>
                <a href="logout.php" class="text-sm font-bold text-red-600 bg-red-50 hover:bg-red-100 px-3 py-2 rounded-lg flex items-center gap-1"><i class="ph-duotone ph-sign-out text-xl"></i><span class="hidden sm:inline">خروج</span></a>
            </div>
        </div>
    </header>

    <main class="p-4 sm:p-8 flex-grow">
