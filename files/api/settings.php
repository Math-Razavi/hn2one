<?php
// settings.php — تنظیمات عمومی و خصوصی مدرسه
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'public':
        // فقط فیلدهای عمومی
        $s = hn_load('settings.json');
        send_response(['success' => true, 'data' => [
            'school_name' => $s['school_name'] ?? '',
            'school_short_name' => $s['school_short_name'] ?? '',
            'phones' => $s['phones'] ?? '',
            'email' => $s['email'] ?? '',
            'address' => $s['address'] ?? '',
            'eitaa' => $s['eitaa'] ?? '',
            'shad' => $s['shad'] ?? '',
            'logo_header' => $s['logo_header'] ?? '',
            'logo_school' => $s['logo_school'] ?? '',
            'ticker' => $s['ticker'] ?? '',
            'intro_home' => $s['intro_home'] ?? [],
            'about_intro' => $s['about_intro'] ?? [],
            'virtual_school_note' => $s['virtual_school_note'] ?? '',
        ]]);

    case 'save':
        // فقط مدیر
        hn_require_role('admin');
        hn_csrf_check();
        $s = hn_load('settings.json');
        $allowed = ['school_name','school_short_name','phones','email','address','eitaa','shad','logo_header','logo_school','logo_footer','ticker','virtual_school_note'];
        foreach ($allowed as $key) {
            if (isset($_POST[$key])) $s[$key] = hn_clean($_POST[$key]);
        }
        // کلیدهای هوش مصنوعی باید خام ذخیره شوند (در هدر HTTP استفاده می‌شوند)
        foreach (['ai_api_url', 'ai_api_key', 'ai_model'] as $key) {
            if (isset($_POST[$key])) $s[$key] = trim((string)$_POST[$key]);
        }
        // کلیدهای داناپلاس هم خام ذخیره می‌شوند و هرگز به پاسخ عمومی نمی‌روند
        foreach (['dp_api_address', 'dp_shared_secret'] as $key) {
            if (isset($_POST[$key])) $s[$key] = trim((string)$_POST[$key]);
        }
        if (isset($_POST['intro_home'])) {
            $s['intro_home'] = array_values(array_filter(array_map('trim', explode("\n", $_POST['intro_home']))));
        }
        if (isset($_POST['about_intro'])) {
            $s['about_intro'] = array_values(array_filter(array_map('trim', explode("\n", $_POST['about_intro']))));
        }
        hn_save('settings.json', $s);
        send_response(['success' => true, 'message' => 'تنظیمات ذخیره شد.']);

    default:
        send_response(['success' => false, 'message' => 'action نامعتبر است.']);
}
