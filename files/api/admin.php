<?php
// admin.php — مدیریت محتوا: پست‌ها (مدیر/دبیر)، گالری، کادر، FAQ، امکانات، اطلاعیه‌ها، فرم‌ها (فقط مدیر)
require_once __DIR__ . '/config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// بولک اکشن‌هایی که مدیر لازم دارند | دبیر فقط create_post
$adminOnlyActions = ['list_posts','update_post','delete_post','set_post_status','gallery_add','gallery_add_batch','gallery_delete','gallery_update',
                     'staff_save','staff_delete','faq_save','faq_delete','facility_save','facility_delete',
                     'announcements_save','announcements_list','announcements_add','announcements_update','announcements_delete','registrations_list','registration_delete','registration_set_status','registrations_export',
                     'messages_list','message_delete'];

if ($action === 'create_post') {
    hn_require_role(['admin', 'teacher']);
} else {
    hn_require_role('admin');
}

// اکشن‌های CONTENT-TYPE خاص: مدیریت فایل‌های JSON با قفل
function crs_save($name, $arr) { return hn_save($name, $arr); }

switch ($action) {

    // ================= پست‌ها =================
    case 'create_post': {
        hn_csrf_check();
        $user = hn_require_login();
        $title = hn_clean($_POST['title'] ?? '');
        $category = hn_clean($_POST['category'] ?? 'گزارش فعالیت');
        $year = hn_clean($_POST['year'] ?? '');
        $body = hn_clean($_POST['body'] ?? '');
        if ($title === '' || $body === '') send_response(['success' => false, 'message' => 'عنوان و متن الزامی است.']);
        $posts = hn_load('posts.json');

        $image = null; $gallery = [];
        if (!empty($_FILES['image']['name'][0]) || !empty($_FILES['image']['name'])) {
            $files = $_FILES['image'];
            // چند فایل ممکن است آپلود شود
            if (is_array($files['name'])) {
                foreach ($files['name'] as $i => $n) {
                    if (($files['error'][$i] ?? 1) !== 0) continue;
                    $f = ['name' => $files['name'][$i], 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
                    $res = hn_handle_upload($f, ['jpg','jpeg','png','webp'], 'news');
                    if ($res['success']) { $gallery[] = $res['path']; }
                }
            } else {
                $res = hn_handle_upload($files, ['jpg','jpeg','png','webp'], 'news');
                if ($res['success']) $gallery[] = $res['path'];
            }
        }
        $image = $gallery[0] ?? null;

        $posts[] = [
            'id' => time(),
            'slug' => 'p' . time() . '-' . bin2hex(random_bytes(2)),
            'title' => $title,
            'category' => $category,
            'year' => $year,
            'excerpt' => mb_substr(strip_tags($body), 0, 160),
            'body' => $body,
            'image' => $image,
            'gallery' => $gallery,
            'status' => $user['role'] === 'admin' ? 'published' : 'pending',
            'author' => $user['name'],
            'created_at' => date('Y-m-d H:i:s'),
        ];
        crs_save('posts.json', $posts);
        send_response(['success' => true, 'message' => $user['role'] === 'admin' ? 'پست منتشر شد.' : 'پست ثبت شد و در انتظار تایید مدیر است.']);
    }

    case 'list_posts':
        send_response(['success' => true, 'data' => hn_load('posts.json')]);

    case 'update_post': {
        hn_csrf_check();
        $id = (int)($_POST['id'] ?? 0);
        $posts = hn_load('posts.json');
        foreach ($posts as &$p) {
            if ((int)$p['id'] === $id) {
                if (isset($_POST['title'])) $p['title'] = hn_clean($_POST['title']);
                if (isset($_POST['category'])) $p['category'] = hn_clean($_POST['category']);
                if (isset($_POST['year'])) $p['year'] = hn_clean($_POST['year']);
                if (isset($_POST['body'])) { $p['body'] = hn_clean($_POST['body']); $p['excerpt'] = mb_substr(strip_tags($_POST['body']), 0, 160); }
                // تصویر جدید (اختیاری)
                if (!empty($_FILES['image']['name'])) {
                    $res = hn_handle_upload($_FILES['image'], ['jpg','jpeg','png','webp'], 'news');
                    if ($res['success']) { $p['image'] = $res['path']; $p['gallery'][] = $res['path']; }
                }
                break;
            }
        }
        unset($p);
        crs_save('posts.json', $posts);
        send_response(['success' => true, 'message' => 'پست ویرایش شد.']);
    }

    case 'delete_post': {
        hn_csrf_check();
        $id = (int)($_POST['id'] ?? 0);
        $posts = hn_load('posts.json');
        $posts = array_values(array_filter($posts, fn($p) => (int)$p['id'] !== $id));
        crs_save('posts.json', $posts);
        send_response(['success' => true, 'message' => 'پست حذف شد.']);
    }

    case 'set_post_status': {
        hn_csrf_check();
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] === 'published' ? 'published' : 'pending';
        $posts = hn_load('posts.json');
        foreach ($posts as &$p) {
            if ((int)$p['id'] === $id) $p['status'] = $status;
        }
        unset($p);
        crs_save('posts.json', $posts);
        send_response(['success' => true, 'message' => 'وضعیت پست تغییر کرد.']);
    }

    // ================= گالری =================
    case 'gallery_add': {
        hn_csrf_check();
        $section = hn_clean($_POST['section'] ?? 'عکس‌های مدرسه');
        if (empty($_FILES['image']['name'])) send_response(['success' => false, 'message' => 'تصویری انتخاب نشده.']);
        $res = hn_handle_upload($_FILES['image'], ['jpg','jpeg','png','webp'], 'gallery');
        if (!$res['success']) send_response($res);
        $items = hn_load('gallery.json');
        $items[] = ['id' => time(), 'src' => $res['path'], 'section' => $section];
        crs_save('gallery.json', $items);
        send_response(['success' => true, 'message' => 'عکس اضافه شد.']);
    }

    case 'gallery_add_batch': {
        hn_csrf_check();
        $section = hn_clean($_POST['section'] ?? 'عکس‌های مدرسه');
        $files = $_FILES['images'] ?? null;
        if (!$files || empty($files['name'][0])) send_response(['success' => false, 'message' => 'تصویری انتخاب نشده.']);
        $added = 0;
        foreach ($files['name'] as $i => $n) {
            if (($files['error'][$i] ?? 1) !== 0) continue;
            $f = ['name' => $n, 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
            $res = hn_handle_upload($f, ['jpg','jpeg','png','webp'], 'gallery');
            if ($res['success']) {
                $items = hn_load('gallery.json');
                $items[] = ['id' => time() + $added, 'src' => $res['path'], 'section' => $section];
                crs_save('gallery.json', $items);
                $added++;
            }
        }
        send_response(['success' => true, 'message' => "$added عکس اضافه شد."]);
    }

    case 'gallery_update': {
        hn_csrf_check();
        $id = (int)($_POST['id'] ?? 0);
        $section = hn_clean($_POST['section'] ?? '');
        if ($section === '') send_response(['success' => false, 'message' => 'نام سکشن الزامی است.']);
        $items = hn_load('gallery.json');
        $found = false;
        foreach ($items as &$g) {
            if ((int)$g['id'] === $id) { $g['section'] = $section; $found = true; }
        }
        unset($g);
        if (!$found) send_response(['success' => false, 'message' => 'عکس یافت نشد.']);
        crs_save('gallery.json', $items);
        send_response(['success' => true, 'message' => 'سکشن عکس به‌روزرسانی شد.']);
    }

    case 'gallery_delete': {
        hn_csrf_check();
        $id = (int)($_POST['id'] ?? 0);
        $items = hn_load('gallery.json');
        foreach ($items as $k => $g) {
            if ((int)$g['id'] === $id) {
                $path = HN_ROOT . $g['src'];
                if (strpos(realpath(dirname($path)), realpath(HN_UPLOADS)) === 0 && is_file($path)) @unlink($path);
                unset($items[$k]);
            }
        }
        crs_save('gallery.json', array_values($items));
        send_response(['success' => true, 'message' => 'عکس حذف شد.']);
    }

    // ================= کادر آموزشی =================
    case 'staff_save': {
        hn_csrf_check();
        $id = (int)($_POST['id'] ?? 0);
        $staff = hn_load('staff.json');
        $entry = $id ? null : ['id' => time() % 1000000000];
        if ($id) {
            foreach ($staff as $s) { if ((int)$s['id'] === $id) $entry = $s; }
        }
        if (!$entry) send_response(['success' => false, 'message' => 'فرد یافت نشد.']);
        $entry['name'] = hn_clean($_POST['name'] ?? '');
        $entry['role'] = hn_clean($_POST['role'] ?? '');
        $entry['subject'] = hn_clean($_POST['subject'] ?? '');
        if (!empty($_FILES['photo']['name'])) {
            $res = hn_handle_upload($_FILES['photo'], ['jpg','jpeg','png','webp'], 'staff');
            if ($res['success']) $entry['photo'] = $res['path'];
        }
        if ($entry['name'] === '') send_response(['success' => false, 'message' => 'نام الزامی است.']);
        $found = false;
        foreach ($staff as &$s) { if ((int)$s['id'] === (int)$entry['id']) { $s = $entry; $found = true; } }
        unset($s);
        if (!$found) $staff[] = $entry;
        crs_save('staff.json', $staff);
        send_response(['success' => true, 'message' => 'ذخیره شد.']);
    }

    case 'staff_delete': {
        hn_csrf_check();
        $id = (int)($_POST['id'] ?? 0);
        $staff = hn_load('staff.json');
        $staff = array_values(array_filter($staff, fn($s) => (int)$s['id'] !== $id));
        crs_save('staff.json', $staff);
        send_response(['success' => true, 'message' => 'حذف شد.']);
    }

    // ================= FAQ =================
    case 'faq_save': {
        hn_csrf_check();
        $id = (int)($_POST['id'] ?? 0);
        $faq = hn_load('faq.json');
        if ($id) {
            foreach ($faq as &$f) {
                if ((int)$f['id'] === $id) { $f['q'] = hn_clean($_POST['q'] ?? ''); $f['a'] = hn_clean($_POST['a'] ?? ''); }
            }
            unset($f);
        } else {
            $faq[] = ['id' => time(), 'q' => hn_clean($_POST['q'] ?? ''), 'a' => hn_clean($_POST['a'] ?? '')];
        }
        crs_save('faq.json', $faq);
        send_response(['success' => true, 'message' => 'ذخیره شد.']);
    }

    case 'faq_delete': {
        hn_csrf_check();
        $id = (int)($_POST['id'] ?? 0);
        $faq = hn_load('faq.json');
        $faq = array_values(array_filter($faq, fn($f) => (int)$f['id'] !== $id));
        crs_save('faq.json', $faq);
        send_response(['success' => true, 'message' => 'حذف شد.']);
    }

    // ================= امکانات =================
    case 'facility_save': {
        hn_csrf_check();
        $id = (int)($_POST['id'] ?? 0);
        $items = hn_load('facilities.json');
        if ($id) {
            foreach ($items as &$f) {
                if ((int)$f['id'] === $id) { $f['title'] = hn_clean($_POST['title'] ?? ''); $f['icon'] = hn_clean($_POST['icon'] ?? 'ph-star'); }
            }
            unset($f);
        } else {
            $items[] = ['id' => time(), 'title' => hn_clean($_POST['title'] ?? ''), 'icon' => hn_clean($_POST['icon'] ?? 'ph-star')];
        }
        crs_save('facilities.json', $items);
        send_response(['success' => true, 'message' => 'ذخیره شد.']);
    }

    case 'facility_delete': {
        hn_csrf_check();
        $id = (int)($_POST['id'] ?? 0);
        $items = hn_load('facilities.json');
        $items = array_values(array_filter($items, fn($f) => (int)$f['id'] !== $id));
        crs_save('facilities.json', $items);
        send_response(['success' => true, 'message' => 'حذف شد.']);
    }

    // ================= اطلاعیه‌ها (نوار متحرک) =================
    case 'announcements_save': {
        hn_csrf_check();
        $lines = preg_split('/\r\n|\r|\n/', $_POST['announcements'] ?? '');
        $items = array_values(array_filter(array_map(function ($l) {
            $l = trim($l);
            return $l === '' ? null : ['id' => crc32($l), 'text' => hn_clean($l)];
        }, $lines)));
        crs_save('announcements.json', $items);
        send_response(['success' => true, 'message' => 'اطلاعیه‌ها ذخیره شدند.']);
    }

    case 'announcements_list':
        send_response(['success' => true, 'data' => hn_load('announcements.json')]);

    case 'announcements_add': {
        hn_csrf_check();
        $text = hn_clean($_POST['text'] ?? '');
        if ($text === '') send_response(['success' => false, 'message' => 'متن اطلاعیه خالی است.']);
        $items = hn_load('announcements.json');
        $items[] = ['id' => crc32($text), 'text' => $text];
        crs_save('announcements.json', $items);
        send_response(['success' => true, 'message' => 'اطلاعیه اضافه شد.']);
    }

    case 'announcements_update': {
        hn_csrf_check();
        $id = (int)($_POST['id'] ?? 0);
        $text = hn_clean($_POST['text'] ?? '');
        if ($text === '') send_response(['success' => false, 'message' => 'متن اطلاعیه خالی است.']);
        $items = hn_load('announcements.json');
        $found = false;
        foreach ($items as &$a) {
            if ((int)($a['id'] ?? 0) === $id) { $a['text'] = $text; $found = true; }
        }
        unset($a);
        if (!$found) send_response(['success' => false, 'message' => 'اطلاعیه یافت نشد.']);
        crs_save('announcements.json', $items);
        send_response(['success' => true, 'message' => 'اطلاعیه ویرایش شد.']);
    }

    case 'announcements_delete': {
        hn_csrf_check();
        $id = (int)($_POST['id'] ?? 0);
        $items = hn_load('announcements.json');
        $before = count($items);
        $items = array_values(array_filter($items, fn($a) => (int)($a['id'] ?? 0) !== $id));
        if (count($items) === $before) send_response(['success' => false, 'message' => 'اطلاعیه یافت نشد.']);
        crs_save('announcements.json', $items);
        send_response(['success' => true, 'message' => 'اطلاعیه حذف شد.']);
    }

    // ================= فرم ثبت‌نام =================
    case 'registrations_list':
        send_response(['success' => true, 'data' => hn_load('registrations.json')]);

    case 'registration_delete': {
        hn_csrf_check();
        $id = (int)($_POST['id'] ?? 0);
        $regs = hn_load('registrations.json');
        $regs = array_values(array_filter($regs, fn($r) => (int)$r['id'] !== $id));
        crs_save('registrations.json', $regs);
        send_response(['success' => true, 'message' => 'حذف شد.']);
    }

    case 'registrations_export': {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="registrations.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['نام', 'کد ملی', 'تاریخ تولد', 'موبایل دانش‌آموز', 'موبایل ولی', 'پایه', 'آدرس', 'عکس', 'تاریخ ثبت']);
        foreach (hn_load('registrations.json') as $r) {
            fputcsv($out, [$r['name'], $r['national_code'], $r['birthdate'], $r['student_phone'], $r['parent_phone'], $r['grade'], $r['address'], $r['photo'] ?? '', $r['created_at']]);
        }
        fclose($out);
        exit;
    }

    // ================= پیام‌های تماس =================
    case 'messages_list':
        send_response(['success' => true, 'data' => hn_load('messages.json')]);

    case 'message_delete': {
        hn_csrf_check();
        $id = (int)($_POST['id'] ?? 0);
        $msgs = hn_load('messages.json');
        $msgs = array_values(array_filter($msgs, fn($m) => (int)$m['id'] !== $id));
        crs_save('messages.json', $msgs);
        send_response(['success' => true, 'message' => 'حذف شد.']);
    }

    default:
        send_response(['success' => false, 'message' => 'action نامعتبر است.']);
}
