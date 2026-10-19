<?php
// content.php — محتوای عمومی سایت: پست‌ها، کادر آموزشی، گالری، FAQ، امکانات، اطلاعیه‌ها
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    case 'get_posts': {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = (int)($_GET['per'] ?? 6);
        $posts = hn_load('posts.json');
        $posts = array_values(array_filter($posts, fn($p) => ($p['status'] ?? 'published') === 'published'));
        usort($posts, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));
        $total = count($posts);
        $slice = array_slice($posts, ($page - 1) * $per, $per);
        send_response(['success' => true, 'data' => ['posts' => $slice, 'total' => $total, 'page' => $page, 'pages' => (int)ceil($total / $per)]]);
    }

    case 'get_post': {
        $slug = preg_replace('/[^a-z0-9\-]/u', '', $_GET['slug'] ?? '');
        foreach (hn_load('posts.json') as $p) {
            if ($p['slug'] === $slug && ($p['status'] ?? 'published') === 'published') {
                send_response(['success' => true, 'data' => $p]);
            }
        }
        send_response(['success' => false, 'message' => 'پست یافت نشد.']);
    }

    case 'get_staff':
        send_response(['success' => true, 'data' => hn_load('staff.json')]);

    case 'get_gallery':
        $items = hn_load('gallery.json');
        $section = $_GET['section'] ?? '';
        if ($section !== '') {
            $items = array_values(array_filter($items, fn($g) => $g['section'] === $section));
        }
        send_response(['success' => true, 'data' => $items]);

    case 'get_gallery_sections': {
        $sections = [];
        foreach (hn_load('gallery.json') as $g) {
            if (!in_array($g['section'], $sections)) $sections[] = $g['section'];
        }
        send_response(['success' => true, 'data' => $sections]);
    }

    case 'get_faq':
        send_response(['success' => true, 'data' => hn_load('faq.json')]);

    case 'get_facilities':
        send_response(['success' => true, 'data' => hn_load('facilities.json')]);

    case 'get_announcements':
        send_response(['success' => true, 'data' => hn_load('announcements.json')]);

    case 'submit_message': {
        // فرم ارتباط با ما (بدون نیاز به ورود)
        $name = hn_clean($_POST['name'] ?? '');
        $phone = hn_clean($_POST['phone'] ?? '');
        $text = hn_clean($_POST['message'] ?? '');
        if ($name === '' || $text === '') {
            send_response(['success' => false, 'message' => 'نام و متن پیام الزامی است.']);
        }
        $msgs = hn_load('messages.json');
        $msgs[] = [
            'id' => time(),
            'name' => $name,
            'phone' => $phone,
            'message' => $text,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        hn_save('messages.json', $msgs);
        send_response(['success' => true, 'message' => 'پیام شما با موفقیت ارسال شد. سپاسگزاریم!']);
    }

    case 'submit_registration': {
        // فرم ثبت‌نام دانش‌آموز جدید
        $required = ['name', 'national_code', 'birthdate', 'student_phone', 'parent_phone', 'grade', 'address'];
        foreach ($required as $f) {
            if (trim($_POST[$f] ?? '') === '') {
                send_response(['success' => false, 'message' => 'تکمیل تمام فیلدها الزامی است.']);
            }
        }
        $nc = preg_replace('/[^0-9]/u', '', $_POST['national_code']);
        if (strlen($nc) !== 10) {
            send_response(['success' => false, 'message' => 'کد ملی باید ۱۰ رقم باشد.']);
        }
        $entry = [
            'id' => time(),
            'name' => hn_clean($_POST['name']),
            'national_code' => $nc,
            'birthdate' => hn_clean($_POST['birthdate']),
            'student_phone' => hn_clean($_POST['student_phone']),
            'parent_phone' => hn_clean($_POST['parent_phone']),
            'grade' => hn_clean($_POST['grade']),
            'address' => hn_clean($_POST['address']),
            'photo' => null,
            'status' => 'new',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        // آپلود عکس پرسلنی (اختیاری)
        if (!empty($_FILES['photo']['name']) && is_uploaded_file($_FILES['photo']['tmp_name'] ?? '')) {
            $res = hn_handle_upload($_FILES['photo'], ['jpg', 'jpeg', 'png', 'webp']);
            if ($res['success']) $entry['photo'] = $res['path'];
        }
        $regs = hn_load('registrations.json');
        $regs[] = $entry;
        hn_save('registrations.json', $regs);
        send_response(['success' => true, 'message' => 'فرم ثبت‌نام شما ثبت شد. برای تعیین وقت مراجعه حضوری با مدرسه تماس بگیرید.']);
    }

    default:
        send_response(['success' => false, 'message' => 'action نامعتبر است.']);
}
