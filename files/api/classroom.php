<?php
// classroom.php — کلاس‌های مجازی (داناپلاس) و سنجش دسترسی دانش‌آموزان
// read-only actions: my_classes (student + حالت پیش‌نمایش مدیر), classes_list / enrollments_list / session_status / dp_status (admin)
// mutating (POST + CSRF): class_save, class_delete, enroll_add, enroll_add_schoolclass,
//   enroll_remove, session_create, session_end, join (student)
// attendeePW / moderatorPW هرگز به خروجی نمی‌روند؛ فقط سمت سرور ذخیره و استفاده می‌شوند.

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/dp_client.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user = hn_require_login();

// ---------- ذخیره‌ها ----------
function cl_classes(): array { return hn_load('classes.json'); }
function cl_enrollments(): array { return hn_load('enrollments.json'); }
function cl_sessions(): array { return hn_load('dp_sessions.json'); }

function cl_save_classes(array $a) { hn_save('classes.json', array_values($a)); }
function cl_save_enrollments(array $a) { hn_save('enrollments.json', array_values($a)); }
function cl_save_sessions(array $a) { hn_save('dp_sessions.json', array_values($a)); }

function cl_find_class(array $classes, string $id): ?array {
    foreach ($classes as $c) if (($c['id'] ?? '') === $id) return $c;
    return null;
}

function cl_active_session(array $sessions, string $classId): ?array {
    $latest = null;
    foreach ($sessions as $s) {
        if (($s['class_id'] ?? '') !== $classId) continue;
        if (($s['status'] ?? '') !== 'active') continue;
        if ($latest === null || ($s['created_at'] ?? 0) >= ($latest['created_at'] ?? 0)) $latest = $s;
    }
    return $latest;
}

function cl_rand_pw(int $len): string {
    $cs = 'abcdefghjkmnpqrstuvwxyz23456789';
    $out = '';
    for ($i = 0; $i < $len; $i++) $out .= $cs[random_int(0, strlen($cs) - 1)];
    return $out;
}

// تبدیل ارقام به فارسی (نسخهٔ محلی؛ pDigits فقط در جاوااسکریپت پنل موجود است)
function cl_fa_digits($n): string {
    return strtr((string)$n, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
}

// دانش‌آموزِ کلاس مجازی: فقط نام‌ها از رکورد کاربر — دور زدنی نیست
function cl_student_join_params(array $user, array $session): array {
    $params = [
        'meetingID'    => (string)$session['meeting_id'],
        'password'     => (string)$session['attendee_pw'],
        'fullName'     => (string)$user['name'],
        'userID'       => (string)$user['code'],
        'joinViaHtml5' => 'true',
    ];
    if ((string)($session['create_time'] ?? '') !== '') $params['createTime'] = (string)$session['create_time'];
    return $params;
}

// ======================================================= read-only
if ($action === 'my_classes') {
    $user = hn_require_login();
    $isPreview = (($_GET['preview'] ?? '0') === '1' && ($user['role'] ?? '') === 'admin');
    if (!$isPreview) hn_require_role(['student']);
    $res = ['success' => true, 'classes' => [], 'preview' => $isPreview, 'dp_ready' => dp_available(), 'transport_error' => ''];
    if (!$isPreview && ($user['active'] ?? true) === false) {
        send_response(['success' => false, 'message' => 'حساب شما غیرفعال شده است. با مدیر مدرسه تماس بگیرید.']);
    }
    if (!$res['dp_ready']) {
        $res['transport_error'] = 'اتصال به کلاس‌های مجازی تنظیم نشده است. با مدیر مدرسه تماس بگیرید.';
        send_response($res);
    }
    $trErr = dp_transport_error();
    if ($trErr !== '') { $res['transport_error'] = $trErr; send_response($res); }

    $classes = cl_classes();
    $sessions = cl_sessions();

    if ($isPreview) {
        // مدیر: همه کلاس‌ها (شامل غیرفعال) فقط به‌نمایش — joinable همیشه false
        usort($classes, fn($a, $b) => ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0));
        $enrolls = cl_enrollments();
        foreach ($classes as $c) {
            $count = 0;
            foreach ($enrolls as $e) if (($e['class_id'] ?? '') === ($c['id'] ?? '')) $count++;
            $s = cl_active_session($sessions, (string)$c['id']);
            $item = [
                'id' => $c['id'],
                'title' => (string)($c['title'] ?? ''),
                'subject' => (string)($c['subject'] ?? ''),
                'teacher' => (string)($c['teacher'] ?? ''),
                'grade' => (string)($c['grade'] ?? ''),
                'status' => ($c['status'] ?? 'active') === 'active' ? 'active' : 'inactive',
                'students' => $count,
                'has_session' => (bool)$s,
                'running' => false,
                'joinable' => false,
            ];
            if ($s) {
                $r = dp_call('isMeetingRunning', ['meetingID' => (string)$s['meeting_id']]);
                $item['running'] = !empty($r['success']) && strtolower(trim((string)($r['data']['running'] ?? 'false'))) === 'true';
            }
            $res['classes'][] = $item;
        }
        send_response($res);
    }

    $code = (string)$user['code'];
    foreach (cl_enrollments() as $e) {
        if (($e['student_code'] ?? '') !== $code) continue;
        $c = cl_find_class($classes, (string)($e['class_id'] ?? ''));
        if (!$c || ($c['status'] ?? 'active') !== 'active') continue;
        $s = cl_active_session($sessions, $c['id']);
        $item = [
            'id' => $c['id'],
            'title' => (string)($c['title'] ?? ''),
            'subject' => (string)($c['subject'] ?? ''),
            'teacher' => (string)($c['teacher'] ?? ''),
            'grade' => (string)($c['grade'] ?? ''),
            'has_session' => (bool)$s,
            'running' => false,
            'joinable' => false,
        ];
        if ($s) {
            $r = dp_call('isMeetingRunning', ['meetingID' => (string)$s['meeting_id']]);
            $item['running'] = !empty($r['success']) && strtolower(trim((string)($r['data']['running'] ?? 'false'))) === 'true';
            $item['joinable'] = true;
        }
        $res['classes'][] = $item;
    }
    send_response($res);
}

if ($action === 'classes_list') {
    hn_require_role(['admin']);
    $classes = cl_classes();
    $enrolls = cl_enrollments();
    $sessions = cl_sessions();
    usort($classes, fn($a, $b) => ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0));
    $out = [];
    foreach ($classes as $c) {
        $count = 0;
        foreach ($enrolls as $e) if (($e['class_id'] ?? '') === ($c['id'] ?? '')) $count++;
        $s = cl_active_session($sessions, (string)$c['id']);
        $out[] = [
            'id' => $c['id'],
            'title' => $c['title'] ?? '',
            'subject' => $c['subject'] ?? '',
            'teacher' => $c['teacher'] ?? '',
            'grade' => $c['grade'] ?? '',
            'description' => $c['description'] ?? '',
            'status' => $c['status'] ?? 'active',
            'students' => $count,
            'has_session' => (bool)$s,
            'meeting_id' => $s ? $s['meeting_id'] : null,
            'session_created_fa' => $s ? hn_jdate((int)($s['created_at'] ?? 0)) : null,
        ];
    }
    send_response(['success' => true, 'classes' => $out]);
}

if ($action === 'enrollments_list') {
    hn_require_role(['admin']);
    $classId = hn_clean($_REQUEST['class_id'] ?? '');
    $users = [];
    foreach (hn_all_users() as $u) $users[$u['code']] = $u;
    $out = [];
    foreach (cl_enrollments() as $e) {
        if (($e['class_id'] ?? '') !== $classId) continue;
        $u = $users[$e['student_code']] ?? null;
        $out[] = [
            'student_code' => $e['student_code'],
            'name' => $u ? $u['name'] : '—',
            'school_class' => $u ? ($u['class'] ?? '') : '',
            'active' => $u ? (($u['active'] ?? true) !== false) : false,
            'exists' => (bool)$u,
        ];
    }
    usort($out, fn($a, $b) => strcmp(($a['school_class'] . $a['student_code']), ($b['school_class'] . $b['student_code'])));
    send_response(['success' => true, 'students' => $out]);
}

if ($action === 'session_status') {
    hn_require_role(['admin']);
    $classId = hn_clean($_REQUEST['class_id'] ?? '');
    $s = cl_active_session(cl_sessions(), $classId);
    if (!$s) send_response(['success' => true, 'has_session' => false, 'running' => false, 'participants' => 0, 'names' => []]);
    $res = ['success' => true, 'has_session' => true, 'running' => false, 'participants' => 0, 'names' => []];
    $r = dp_call('isMeetingRunning', ['meetingID' => $s['meeting_id']]);
    if (!empty($r['success']) && strtolower(trim((string)($r['data']['running'] ?? 'false'))) === 'true') {
        $res['running'] = true;
        $r2 = dp_call('getMeetingInfo', ['meetingID' => $s['meeting_id']]);
        if (!empty($r2['success'])) {
            $res['participants'] = count($r2['data']['attendees'] ?? []);
            $res['names'] = array_map(fn($a) => $a['fullName'], $r2['data']['attendees'] ?? []);
        }
    } elseif (empty($r['success'])) {
        $res['api_error'] = $r['message'] ?? '';
    }
    send_response($res);
}

if ($action === 'dp_status') {
    hn_require_role(['admin']);
    $addr = dp_config()['address'];
    send_response([
        'success' => true,
        'configured' => dp_available(),
        'host' => (string)(parse_url($addr, PHP_URL_HOST) ?? ''),
        'transport_error' => dp_transport_error(),
    ]);
}

// ======================================================= mutating (POST + CSRF)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_response(['success' => false, 'message' => 'درخواست نامعتبر است.']);
}
hn_csrf_check();

if ($action === 'join') {
    hn_require_role(['student']);
    if (($user['active'] ?? true) === false) {
        dp_log('join_denied', 'inactive student ' . $user['code']);
        send_response(['success' => false, 'message' => 'حساب شما غیرفعال شده است. با مدیر مدرسه تماس بگیرید.']);
    }
    $err = dp_transport_error();
    if ($err !== '') send_response(['success' => false, 'message' => $err]);
    // محدودیت فراخوانی مجدد: هر ۵ ثانیه یک بار برای هر کاربر
    if (isset($_SESSION['dp_join_ts']) && (time() - (int)$_SESSION['dp_join_ts']) < 5) {
        send_response(['success' => false, 'message' => 'درخواست‌های پشت سر هم مجاز نیست؛ چند لحظه صبر کنید.']);
    }

    $classId = hn_clean($_POST['class_id'] ?? '');
    $c = cl_find_class(cl_classes(), $classId);
    if (!$c || ($c['status'] ?? 'active') !== 'active') {
        send_response(['success' => false, 'message' => 'این کلاس فعال نیست.']);
    }
    $enrolled = false;
    foreach (cl_enrollments() as $e) {
        if (($e['class_id'] ?? '') === $classId && ($e['student_code'] ?? '') === (string)$user['code']) { $enrolled = true; break; }
    }
    if (!$enrolled) {
        dp_log('join_denied', 'not enrolled ' . $user['code'] . ' class ' . $classId);
        send_response(['success' => false, 'message' => 'شما به این کلاس مجاز نیستید.']);
    }
    $s = cl_active_session(cl_sessions(), $classId);
    if (!$s) {
        send_response(['success' => false, 'message' => 'جلسه فعال نیست. لطفاً بعداً دوباره بررسی کنید.']);
    }

    $url = dp_join_url(cl_student_join_params($user, $s));
    $_SESSION['dp_join_ts'] = time();
    dp_log('student_join', $user['code'] . ' -> ' . $classId); // لینک امضاشده هرگز لاگ نمی‌شود
    send_response(['success' => true, 'url' => $url]);
}

if ($user['role'] !== 'admin') send_response(['success' => false, 'message' => 'دسترسی مجاز نیست.']);

$classes = cl_classes();

if ($action === 'class_save') {
    $id = hn_clean($_POST['id'] ?? '');
    $title = hn_clean($_POST['title'] ?? '');
    $subject = hn_clean($_POST['subject'] ?? '');
    $teacher = hn_clean($_POST['teacher'] ?? '');
    $grade = hn_clean($_POST['grade'] ?? '');
    $description = hn_clean($_POST['description'] ?? '');
    $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
    if ($title === '' || $subject === '') {
        send_response(['success' => false, 'message' => 'عنوان و درس الزامی است.']);
    }
    if ($id !== '') {
        foreach ($classes as $i => $c) {
            if (($c['id'] ?? '') === $id) {
                $classes[$i]['title'] = $title;
                $classes[$i]['subject'] = $subject;
                $classes[$i]['teacher'] = $teacher;
                $classes[$i]['grade'] = $grade;
                $classes[$i]['description'] = $description;
                $classes[$i]['status'] = $status;
                cl_save_classes($classes);
                send_response(['success' => true, 'message' => 'کلاس ویرایش شد.']);
            }
        }
        send_response(['success' => false, 'message' => 'کلاس یافت نشد.']);
    }
    $id = 'c' . time() . bin2hex(random_bytes(3));
    $classes[] = [
        'id' => $id, 'title' => $title, 'subject' => $subject, 'teacher' => $teacher,
        'grade' => $grade, 'description' => $description, 'status' => $status, 'created_at' => time(),
    ];
    cl_save_classes($classes);
    send_response(['success' => true, 'message' => 'کلاس ساخته شد.']);
}

if ($action === 'class_delete') {
    $id = hn_clean($_POST['id'] ?? '');
    $found = false;
    foreach ($classes as $i => $c) {
        if (($c['id'] ?? '') === $id) { unset($classes[$i]); $found = true; break; }
    }
    if (!$found) send_response(['success' => false, 'message' => 'کلاس یافت نشد.']);
    $enrolls = array_values(array_filter(cl_enrollments(), fn($e) => ($e['class_id'] ?? '') !== $id));
    cl_save_enrollments($enrolls);
    $sessions = cl_sessions();
    foreach ($sessions as $s) {
        if (($s['class_id'] ?? '') === $id && ($s['status'] ?? '') === 'active') {
            dp_call('end', ['meetingID' => $s['meeting_id'], 'password' => $s['moderator_pw']]);
        }
    }
    $sessions = array_values(array_filter($sessions, fn($s) => ($s['class_id'] ?? '') !== $id));
    cl_save_sessions($sessions);
    cl_save_classes($classes);
    send_response(['success' => true, 'message' => 'کلاس و سابقهٔ آن حذف شد.']);
}

if ($action === 'enroll_add') {
    $classId = hn_clean($_POST['class_id'] ?? '');
    if (!cl_find_class($classes, $classId)) send_response(['success' => false, 'message' => 'کلاس یافت نشد.']);
    $raw = (string)($_POST['codes'] ?? '');
    $parts = preg_split('/[\s,،;]+/u', $raw) ?: [];
    $codes = array_values(array_unique(array_filter(array_map(
        fn($x) => preg_replace('/[^A-Za-z0-9_-]/u', '', trim($x)), $parts
    ), fn($x) => $x !== '')));
    if (!$codes) send_response(['success' => false, 'message' => 'کد کاربری وارد نشده است.']);
    $users = [];
    foreach (hn_all_users() as $u) $users[$u['code']] = $u;
    $enrolls = cl_enrollments();
    $existing = [];
    foreach ($enrolls as $e) if (($e['class_id'] ?? '') === $classId) $existing[$e['student_code']] = true;
    $added = 0; $skipped = 0; $invalid = [];
    foreach ($codes as $code) {
        $u = $users[$code] ?? null;
        if (!$u || ($u['role'] ?? '') !== 'student') { $invalid[] = $code; continue; }
        if (isset($existing[$code])) { $skipped++; continue; }
        $enrolls[] = ['class_id' => $classId, 'student_code' => $code, 'created_at' => time()];
        $existing[$code] = true;
        $added++;
    }
    cl_save_enrollments($enrolls);
    $msg = 'تخصیص انجام شد: ' . cl_fa_digits($added) . ' نفر اضافه شد.';
    if ($skipped) $msg .= ' (' . cl_fa_digits($skipped) . ' نفر قبلاً عضو بودند.)';
    send_response(['success' => true, 'message' => $msg, 'added' => $added, 'skipped' => $skipped, 'invalid' => $invalid]);
}

if ($action === 'enroll_add_schoolclass') {
    $classId = hn_clean($_POST['class_id'] ?? '');
    $schoolClass = hn_clean($_POST['school_class'] ?? '');
    if (!cl_find_class($classes, $classId)) send_response(['success' => false, 'message' => 'کلاس یافت نشد.']);
    if ($schoolClass === '') send_response(['success' => false, 'message' => 'کلاس مدرسه انتخاب نشده است.']);
    $enrolls = cl_enrollments();
    $existing = [];
    foreach ($enrolls as $e) if (($e['class_id'] ?? '') === $classId) $existing[$e['student_code']] = true;
    $added = 0; $skipped = 0;
    foreach (hn_all_users() as $u) {
        if (($u['role'] ?? '') !== 'student') continue;
        if (($u['class'] ?? '') !== $schoolClass) continue;
        if (($u['active'] ?? true) === false) continue;
        if (isset($existing[$u['code']])) { $skipped++; continue; }
        $enrolls[] = ['class_id' => $classId, 'student_code' => $u['code'], 'created_at' => time()];
        $existing[$u['code']] = true;
        $added++;
    }
    cl_save_enrollments($enrolls);
    $msg = 'تخصیص انجام شد: ' . cl_fa_digits($added) . ' نفر اضافه شد.';
    if ($skipped) $msg .= ' (' . cl_fa_digits($skipped) . ' نفر قبلاً عضو بودند.)';
    send_response(['success' => true, 'message' => $msg, 'added' => $added, 'skipped' => $skipped]);
}

if ($action === 'enroll_remove') {
    $classId = hn_clean($_POST['class_id'] ?? '');
    $code = preg_replace('/[^A-Za-z0-9_-]/u', '', (string)($_POST['student_code'] ?? ''));
    $enrolls = cl_enrollments();
    $new = array_values(array_filter($enrolls, fn($e) =>
        !(($e['class_id'] ?? '') === $classId && ($e['student_code'] ?? '') === $code)));
    if (count($new) === count($enrolls)) send_response(['success' => false, 'message' => 'این دانش‌آموز در کلاس نیست.']);
    cl_save_enrollments($new);
    send_response(['success' => true, 'message' => 'دسترسی این دانش‌آموز حذف شد.']);
}

if ($action === 'session_create') {
    $classId = hn_clean($_POST['class_id'] ?? '');
    $c = cl_find_class($classes, $classId);
    if (!$c) send_response(['success' => false, 'message' => 'کلاس یافت نشد.']);
    $sessions = cl_sessions();
    if (cl_active_session($sessions, $classId)) {
        send_response(['success' => false, 'message' => 'برای این کلاس جلسهٔ فعالی وجود دارد؛ ابتدا جلسهٔ فعلی را پایان دهید.']);
    }
    $meetingId = 'cl-' . $classId . '-' . bin2hex(random_bytes(8));
    $attendeePw = cl_rand_pw(8);
    $moderatorPw = cl_rand_pw(10);
    $r = dp_call('create', [
        'name'        => (string)($c['title'] ?? 'کلاس'),
        'meetingID'   => $meetingId,
        'attendeePW'  => $attendeePw,
        'moderatorPW' => $moderatorPw,
    ]);
    if (empty($r['success'])) {
        send_response(['success' => false, 'message' => 'ساخت جلسه در داناپلاس ناموفق بود: ' . ($r['message'] ?? '')]);
    }
    $sessions[] = [
        'id' => 's' . time() . bin2hex(random_bytes(4)),
        'class_id' => $classId,
        'meeting_id' => $meetingId,
        'create_time' => trim((string)($r['data']['createTime'] ?? '')),
        'attendee_pw' => $attendeePw,
        'moderator_pw' => $moderatorPw,
        'status' => 'active',
        'created_at' => time(),
    ];
    cl_save_sessions($sessions);
    dp_log('session_create', $classId . ' ' . $meetingId);
    send_response(['success' => true, 'message' => 'جلسه ساخته شد.']);
}

if ($action === 'session_end') {
    $classId = hn_clean($_POST['class_id'] ?? '');
    $sessions = cl_sessions();
    $s = cl_active_session($sessions, $classId);
    if (!$s) send_response(['success' => false, 'message' => 'جلسهٔ فعالی برای این کلاس نیست.']);
    $r = dp_call('end', ['meetingID' => $s['meeting_id'], 'password' => $s['moderator_pw']]);
    foreach ($sessions as $i => $x) {
        if (($x['id'] ?? '') === ($s['id'] ?? '')) {
            $sessions[$i]['status'] = 'ended';
            $sessions[$i]['ended_at'] = time();
        }
    }
    cl_save_sessions($sessions);
    dp_log('session_end', $classId . ' ' . $s['meeting_id'] . ($r['success'] ? '' : ' (api: ' . ($r['message'] ?? '') . ')'));
    if (!empty($r['success'])) send_response(['success' => true, 'message' => 'جلسه پایان یافت.']);
    // اگر جلسه در سرویس از قبل نبود، باز هم سابقه محلی پایان یافته علامت می‌خورد
    send_response(['success' => true, 'message' => 'جلسه در سرویس فعال نبود؛ سابقهٔ محلی پایان یافت.']);
}

send_response(['success' => false, 'message' => 'اکشن نامشخص.']);
