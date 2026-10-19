<?php
// config.php — تنظیمات مشترک بک‌اند (بارگذاری توسط همه APIها و صفحات پنل)
// مسیرها و توابع کمکی سراسری

define('HN_ROOT', dirname(__DIR__, 2));
define('HN_DATA', HN_ROOT . '/files/data');
define('HN_USERS', HN_DATA . '/users');
define('HN_UPLOADS', HN_ROOT . '/files/uploads');

// ---------- توابع کمکی JSON ----------
function hn_load($name, $default = []) {
    $file = HN_DATA . '/' . $name;
    if (!file_exists($file)) return $default;
    $raw = file_get_contents($file);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $default;
}

function hn_save($name, $data) {
    $file = HN_DATA . '/' . $name;
    $tmp = $file . '.tmp';
    if (file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
        return false;
    }
    return rename($tmp, $file);
}

function hn_user_file($code) {
    $code = preg_replace('/[^A-Za-z0-9_-]/u', '', (string)$code);
    return HN_USERS . '/' . $code . '.json';
}

function hn_load_user($code) {
    $file = hn_user_file($code);
    if (!file_exists($file)) return null;
    $u = json_decode(file_get_contents($file), true);
    return is_array($u) ? $u : null;
}

function hn_save_user($code, $user) {
    return hn_save('users/' . $code . '.json', $user);
}

function hn_all_users() {
    $users = [];
    foreach (glob(HN_USERS . '/*.json') ?: [] as $f) {
        $u = json_decode(file_get_contents($f), true);
        if ($u) $users[] = $u;
    }
    return $users;
}

// ---------- پاسخ JSON ----------
function send_response($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ---------- امنیت ----------
function hn_clean($str) {
    return htmlspecialchars(trim((string)$str), ENT_QUOTES, 'UTF-8');
}

function hn_require_login() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_code'])) {
        send_response(['success' => false, 'message' => 'ابتدا وارد شوید.']);
    }
    return $_SESSION['user'];
}

function hn_require_role($roles) {
    $user = hn_require_login();
    if (!in_array($user['role'], (array)$roles, true)) {
        send_response(['success' => false, 'message' => 'دسترسی مجاز نیست.']);
    }
    return $user;
}

function hn_csrf_check() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        send_response(['success' => false, 'message' => 'نشست شما منقضی شده است. صفحه را رفرش کنید.']);
    }
}

function hn_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// ---------- آپلود امن فایل ----------
function hn_handle_upload($file, array $allowedExt, string $subfolder = 'misc'): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'خطا در آپلود فایل.'];
    }
    if (($file['size'] ?? 0) > 20 * 1024 * 1024) {
        return ['success' => false, 'message' => 'حجم فایل بیش از ۲۰ مگابایت است.'];
    }
    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    // تشخیص واقعی نوع فایل (نه فقط پسوند)
    $fi = new finfo(FILEINFO_MIME_TYPE);
    $mime = $fi->file($file['tmp_name']);
    $allowedMimes = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp',
        'pdf' => 'application/pdf', 'zip' => 'application/zip',
    ];
    $okMime = false;
    foreach ($allowedExt as $e) {
        if ($e === 'jpg' || $e === 'jpeg') { if ($mime === 'image/jpeg') $okMime = true; }
        elseif (isset($allowedMimes[$e]) && $allowedMimes[$e] === $mime) $okMime = true;
    }
    if (!in_array($ext, $allowedExt, true) || !$okMime) {
        return ['success' => false, 'message' => 'فرمت فایل مجاز نیست.'];
    }
    $subfolder = preg_replace('/[^a-z0-9\-]/', '', $subfolder);
    $dir = HN_UPLOADS . '/' . $subfolder;
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $name = date('YmdHis') . '-' . bin2hex(random_bytes(5)) . '.' . $ext;
    $dest = $dir . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => false, 'message' => 'خطا در ذخیره فایل.'];
    }
    return ['success' => true, 'path' => '/files/uploads/' . $subfolder . '/' . $name];
}

// تاریخ شمسی
function hn_jdate($timestamp = null) {
    require_once HN_ROOT . '/files/api/jalali.php';
    return jdate_from_ts($timestamp ?: time());
}
