<?php
// users.php — مدیریت کاربران (فقط مدیر): ساخت دانش‌آموز/دبیر، حذف، ریست رمز، خروجی CSV
require_once __DIR__ . '/config.php';

hn_require_role('admin');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'list': {
        // هش رمز هرگز به خروجی نمی‌رود
        $users = hn_all_users();
        foreach ($users as &$u) {
            unset($u['password']);
        }
        unset($u);
        send_response(['success' => true, 'data' => $users]);
    }

    case 'create_one': {
        hn_csrf_check();
        $name = hn_clean($_POST['name'] ?? '');
        $code = hn_clean($_POST['code'] ?? '');
        $role = $_POST['role'] ?? 'student';
        $class = hn_clean($_POST['class'] ?? '');
        if (!in_array($role, ['student', 'teacher'], true)) {
            send_response(['success' => false, 'message' => 'نقش نامعتبر است.']);
        }
        if ($name === '' || !preg_match('/^[A-Za-z0-9_-]{3,30}$/', $code)) {
            send_response(['success' => false, 'message' => 'نام الزامی است و کد کاربری باید ۳ تا ۳۰ کاراکتر انگلیسی باشد.']);
        }
        if (hn_load_user($code)) {
            send_response(['success' => false, 'message' => "کد کاربری «$code» قبلاً وجود دارد."]);
        }
        $password = bin2hex(random_bytes(3)); // رمز اولیه ۶ کاراکتری
        $user = [
            'code' => $code,
            'name' => $name,
            'role' => $role,
            'class' => $class,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'must_change_password' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'results' => [],
        ];
        hn_save_user($code, $user);
        send_response(['success' => true, 'message' => "کاربر ساخته شد. رمز اولیه: $password", 'user' => ['code' => $code, 'name' => $name, 'role' => $role, 'first_password' => $password]]);
    }

    case 'create_bulk': {
        hn_csrf_check();
        $role = $_POST['role'] ?? 'student';
        $defaultClass = hn_clean($_POST['default_class'] ?? '');
        $lines = preg_split('/\r\n|\r|\n/', $_POST['bulk_data'] ?? '');
        $created = []; $errors = []; $count = 0;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            // فرمت: نام | کد کاربری | کلاس (کلاس اختیاری)
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) < 2) { $errors[] = "$line (فرمت نامعتبر)"; continue; }
            [$name, $code, $class] = [$parts[0], $parts[1], $parts[2] ?? $defaultClass];
            $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $code = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
            if (!preg_match('/^[A-Za-z0-9_-]{3,30}$/', $code)) { $errors[] = "$code (کد نامعتبر)"; continue; }
            if (hn_load_user($code)) { $errors[] = "$code (وجود دارد)"; continue; }
            $password = bin2hex(random_bytes(3));
            hn_save_user($code, [
                'code' => $code, 'name' => $name, 'role' => $role, 'class' => $class,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'must_change_password' => true,
                'created_at' => date('Y-m-d H:i:s'), 'results' => [],
            ]);
            $created[] = ['code' => $code, 'name' => $name, 'class' => $class, 'first_password' => $password];
            $count++;
        }
        hn_save('users_created_log.json', array_merge(hn_load('users_created_log.json'), $created));
        send_response(['success' => true, 'message' => "$count کاربر ساخته شد.", 'created' => $created, 'errors' => $errors]);
    }

    case 'set_active': {
        hn_csrf_check();
        $code = hn_clean($_POST['code'] ?? '');
        $active = (($_POST['active'] ?? '0') === '1');
        $u = hn_load_user($code);
        if (!$u) send_response(['success' => false, 'message' => 'کاربر یافت نشد.']);
        if (($u['role'] ?? '') === 'admin') send_response(['success' => false, 'message' => 'حساب مدیر قابل تغییر نیست.']);
        $u['active'] = $active;
        hn_save_user($code, $u);
        send_response(['success' => true, 'message' => $active ? "حساب $code فعال شد." : "حساب $code غیرفعال شد."]);
    }

    case 'update': {
        hn_csrf_check();
        $code = hn_clean($_POST['code'] ?? '');
        $u = hn_load_user($code);
        if (!$u) send_response(['success' => false, 'message' => 'کاربر یافت نشد.']);
        if (($u['role'] ?? '') === 'admin') send_response(['success' => false, 'message' => 'حساب مدیر از این بخش قابل تغییر نیست.']);
        $name = hn_clean($_POST['name'] ?? '');
        $class = hn_clean($_POST['class'] ?? '');
        if ($name === '') send_response(['success' => false, 'message' => 'نام الزامی است.']);
        if (($u['role'] ?? '') !== 'student') $class = ($u['class'] ?? '');
        $u['name'] = $name;
        $u['class'] = $class;
        if (isset($_POST['active'])) $u['active'] = (($_POST['active'] ?? '1') === '1');
        hn_save_user($code, $u);
        send_response(['success' => true, 'message' => "اطلاعات $code به‌روزرسانی شد."]);
    }

    case 'delete': {
        hn_csrf_check();
        $code = hn_clean($_POST['code'] ?? '');
        if ($code === '') send_response(['success' => false, 'message' => 'کد الزامی است.']);
        $file = hn_user_file($code);
        if (file_exists($file)) unlink($file);
        send_response(['success' => true, 'message' => "کاربر $code حذف شد."]);
    }

    case 'reset_password': {
        hn_csrf_check();
        $code = hn_clean($_POST['code'] ?? '');
        $u = hn_load_user($code);
        if (!$u) send_response(['success' => false, 'message' => 'کاربر یافت نشد.']);
        $new = $_POST['new_password'] ?? bin2hex(random_bytes(3));
        $u['password'] = password_hash($new, PASSWORD_BCRYPT);
        hn_save_user($code, $u);
        send_response(['success' => true, 'message' => "رمز کاربر $code عوض شد: $new", 'first_password' => $new]);
    }

    case 'export_csv': {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="users.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM برای اکسل فارسی
        fputcsv($out, ['کد کاربری', 'نام', 'نقش', 'کلاس', 'تاریخ ساخت']);
        foreach (hn_all_users() as $u) {
            fputcsv($out, [$u['code'], $u['name'], $u['role'], $u['class'] ?? '', $u['created_at'] ?? '']);
        }
        fclose($out);
        exit;
    }

    default:
        send_response(['success' => false, 'message' => 'action نامعتبر است.']);
}
