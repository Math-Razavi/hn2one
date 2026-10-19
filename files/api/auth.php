<?php
// auth.php — ورود، خروج، پروفایل (دانش‌آموز / دبیر / مدیر)
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!file_exists(HN_USERS)) mkdir(HN_USERS, 0775, true);

$action = $_POST['action'] ?? '';

// محدودیت تلاش ورود (rate limit ساده)
// فایل وضعیت: {'count': n, 'until': ts, 'last': ts}
function hn_login_state_file() {
    return sys_get_temp_dir() . '/hn_login_' . md5($_SERVER['REMOTE_ADDR'] ?? 'x');
}

function hn_login_throttle_load() {
    $state = json_decode(@file_get_contents(hn_login_state_file()), true);
    return is_array($state) ? $state : ['count' => 0, 'until' => 0, 'last' => 0];
}

function hn_login_throttle_save($state) {
    file_put_contents(hn_login_state_file(), json_encode($state, JSON_PRETTY_PRINT), LOCK_EX);
}

function hn_login_throttle_ok() {
    $state = hn_login_throttle_load();
    if ($state['until'] > time()) return false;
    if ($state['until'] > 0) {
        // دوره محدودیت تمام شده → ریست کامل
        hn_login_throttle_save(['count' => 0, 'until' => 0, 'last' => time()]);
    }
    return true;
}

function hn_login_throttle_fail() {
    $state = hn_login_throttle_load();
    $state['count']++;
    $state['last'] = time();
    if ($state['count'] >= 5) {
        $state['until'] = time() + 300;
        $state['count'] = 0;
    }
    hn_login_throttle_save($state);
}

function hn_login_throttle_reset() {
    @unlink(hn_login_state_file());
}

switch ($action) {

    case 'login': {
        if (!hn_login_throttle_ok()) {
            send_response(['success' => false, 'message' => 'تلاش‌های ناموفق زیاد است. ۵ دقیقه صبر کنید.']);
        }
        $code = preg_replace('/[^A-Za-z0-9_-]/u', '', trim($_POST['code'] ?? ''));
        $password = $_POST['password'] ?? '';
        $user = hn_load_user($code);
        if (!$user || !password_verify($password, $user['password'])) {
            hn_login_throttle_fail();
            send_response(['success' => false, 'message' => 'کد کاربری یا رمز عبور اشتباه است.']);
        }
        if (($user['active'] ?? true) === false) {
            send_response(['success' => false, 'message' => 'حساب شما غیرفعال شده است. برای فعال‌سازی با مدیر مدرسه تماس بگیرید.']);
        }
        $_SESSION['user_code'] = $user['code'];
        $_SESSION['user'] = [
            'code' => $user['code'],
            'name' => $user['name'],
            'role' => $user['role'],
            'class' => $user['class'] ?? null,
        ];
        hn_login_throttle_reset();
        send_response(['success' => true, 'message' => 'ورود موفقیت‌آمیز بود.', 'user' => [
            'code' => $user['code'],
            'name' => $user['name'],
            'role' => $user['role'],
            'class' => $user['class'] ?? null,
            'csrf_token' => hn_csrf_token(),
        ]]);
    }

    case 'logout':
        session_destroy();
        send_response(['success' => true, 'message' => 'خروج انجام شد.']);

    case 'me': {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['user_code'])) {
            send_response(['success' => false]);
        }
        $u = hn_load_user($_SESSION['user_code']);
        if (!$u) { session_destroy(); send_response(['success' => false]); }
        unset($u['password']);
        $u['csrf_token'] = hn_csrf_token();
        send_response(['success' => true, 'data' => $u]);
    }

    case 'change_password': {
        $user = hn_require_login();
        hn_csrf_check();
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        if (strlen($new) < 6) send_response(['success' => false, 'message' => 'رمز جدید باید حداقل ۶ کاراکتر باشد.']);
        $full = hn_load_user($user['code']);
        if (!password_verify($current, $full['password'])) {
            send_response(['success' => false, 'message' => 'رمز فعلی اشتباه است.']);
        }
        $full['password'] = password_hash($new, PASSWORD_BCRYPT);
        hn_save_user($user['code'], $full);
        send_response(['success' => true, 'message' => 'رمز عبور با موفقیت تغییر کرد.']);
    }

    default:
        send_response(['success' => false, 'message' => 'action نامعتبر است.']);
}
