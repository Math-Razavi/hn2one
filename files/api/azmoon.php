<?php
// azmoon.php — ماژول آزمون آنلاین (دبیر: ساخت آزمون / دانش‌آموز: شرکت در آزمون)
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ---------- دبیر ----------
    case 'get_exams': {
        $user = hn_require_role(['teacher', 'admin']);
        $exams = hn_load('exams.json');
        if ($user['role'] === 'teacher') {
            $exams = array_values(array_filter($exams, fn($e) => ($e['teacher_code'] ?? '') === $user['code']));
        }
        usort($exams, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));
        send_response(['success' => true, 'data' => $exams]);
    }

    case 'create_exam': {
        $user = hn_require_role(['teacher', 'admin']);
        hn_csrf_check();
        $title = hn_clean($_POST['title'] ?? '');
        $class = hn_clean($_POST['class'] ?? 'all');
        if ($title === '') send_response(['success' => false, 'message' => 'عنوان آزمون الزامی است.']);
        $keys = json_decode($_POST['keys'] ?? '', true);
        if (!is_array($keys) || count($keys) < 1) {
            send_response(['success' => false, 'message' => 'کلید سوالات نامعتبر است.']);
        }
        foreach ($keys as $k) {
            if (!in_array((int)$k, [1, 2, 3, 4], true)) {
                send_response(['success' => false, 'message' => 'کلید هر سوال باید یکی از گزینه‌های ۱ تا ۴ باشد.']);
            }
        }
        $pdf = hn_handle_upload($_FILES['pdf'] ?? [], ['pdf'], 'exams');
        if (!$pdf['success']) send_response(['success' => false, 'message' => $pdf['message']]);
        $keyPath = null;
        if (!empty($_FILES['answer_key']['name']) && is_uploaded_file($_FILES['answer_key']['tmp_name'] ?? '')) {
            $res = hn_handle_upload($_FILES['answer_key'], ['pdf'], 'exams');
            if ($res['success']) $keyPath = $res['path'];
        }
        $exams = hn_load('exams.json');
        $exams[] = [
            'id' => time() . '-' . bin2hex(random_bytes(3)),
            'teacher_code' => $user['code'],
            'teacher_name' => $user['name'],
            'title' => $title,
            'class' => $class,
            'keys' => array_map('intval', $keys),
            'pdf' => $pdf['path'],
            'pdf_name' => hn_clean(basename($_FILES['pdf']['name'] ?? '')),
            'answer_key' => $keyPath,
            'answer_key_name' => $keyPath ? hn_clean(basename($_FILES['answer_key']['name'] ?? '')) : null,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        hn_save('exams.json', $exams);
        send_response(['success' => true, 'message' => 'آزمون با موفقیت ساخته شد.']);
    }

    case 'set_exam_status': {
        $user = hn_require_role(['teacher', 'admin']);
        hn_csrf_check();
        $id = $_POST['exam_id'] ?? '';
        $status = ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive';
        $exams = hn_load('exams.json');
        $found = false;
        foreach ($exams as &$e) {
            if ($e['id'] === $id && ($user['role'] === 'admin' || $e['teacher_code'] === $user['code'])) {
                $e['status'] = $status;
                $found = true;
            }
        }
        unset($e);
        if (!$found) send_response(['success' => false, 'message' => 'آزمون یافت نشد.']);
        hn_save('exams.json', $exams);
        send_response(['success' => true, 'message' => 'وضعیت آزمون تغییر کرد.']);
    }

    case 'delete_exam': {
        $user = hn_require_role(['teacher', 'admin']);
        hn_csrf_check();
        $id = $_POST['exam_id'] ?? '';
        $exams = hn_load('exams.json');
        $target = null;
        foreach ($exams as $e) {
            if ($e['id'] === $id && ($user['role'] === 'admin' || $e['teacher_code'] === $user['code'])) {
                $target = $e;
            }
        }
        if (!$target) send_response(['success' => false, 'message' => 'آزمون یافت نشد.']);
        $exams = array_filter($exams, fn($e) => $e['id'] !== $id);
        // حذف نتایج مرتبط
        $results = hn_load('results.json');
        $results = array_filter($results, fn($r) => $r['exam_id'] !== $id);
        hn_save('results.json', $results);
        hn_save('exams.json', array_values($exams));
        send_response(['success' => true, 'message' => 'آزمون و نتایج آن حذف شد.']);
    }

    case 'get_results': {
        $user = hn_require_role(['teacher', 'admin']);
        $id = $_GET['exam_id'] ?? '';
        $exams = hn_load('exams.json');
        $exam = null;
        foreach ($exams as $e) {
            if ($e['id'] === $id && ($user['role'] === 'admin' || $e['teacher_code'] === $user['code'])) $exam = $e;
        }
        if (!$exam) send_response(['success' => false, 'message' => 'آزمون یافت نشد.']);
        $results = array_values(array_filter(hn_load('results.json'), fn($r) => $r['exam_id'] === $id));
        usort($results, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));
        send_response(['success' => true, 'data' => ['exam' => $exam, 'results' => $results]]);
    }

    case 'clear_results': {
        $user = hn_require_role(['teacher', 'admin']);
        hn_csrf_check();
        $id = $_POST['exam_id'] ?? '';
        $exams = hn_load('exams.json');
        $ok = false;
        foreach ($exams as $e) {
            if ($e['id'] === $id && ($user['role'] === 'admin' || $e['teacher_code'] === $user['code'])) $ok = true;
        }
        if (!$ok) send_response(['success' => false, 'message' => 'آزمون یافت نشد.']);
        $results = hn_load('results.json');
        hn_save('results.json', array_values(array_filter($results, fn($r) => $r['exam_id'] !== $id)));
        send_response(['success' => true, 'message' => 'نتایج این آزمون حذف شد.']);
    }

    // ---------- دانش‌آموز ----------
    case 'get_student_exams': {
        $user = hn_require_role('student');
        $class = $user['class'] ?? '';
        $exams = array_values(array_filter(hn_load('exams.json'), fn($e) =>
            ($e['status'] ?? 'active') === 'active' && (($e['class'] ?? 'all') === 'all' || $e['class'] === $class)
        ));
        usort($exams, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));
        $results = hn_load('results.json');
        $myDone = [];
        foreach ($results as $r) {
            if (($r['student_code'] ?? '') === $user['code']) $myDone[$r['exam_id']] = $r;
        }
        // حذف کلید از سمت دانش‌آموز (فقط تعداد سوالات ارسال می‌شود)
        foreach ($exams as &$e) {
            $e['key_count'] = count($e['keys'] ?? []);
            unset($e['keys']);
        }
        unset($e);
        send_response(['success' => true, 'data' => ['exams' => $exams, 'my_results' => $myDone]]);
    }

    case 'get_preview_exams': {
        // پیش‌نمایش مدیریتی: مدیر لیست آزمون‌های فعال را «به چشم دانش‌آموز» می‌بیند؛ ثبت‌نتیجه مخصوص دانش‌آموز است
        hn_require_role('admin');
        $exams = array_values(array_filter(hn_load('exams.json'), fn($e) => ($e['status'] ?? 'active') === 'active'));
        usort($exams, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));
        foreach ($exams as &$e) {
            $e['key_count'] = count($e['keys'] ?? []);
            unset($e['keys']);
        }
        unset($e);
        send_response(['success' => true, 'preview' => true, 'data' => ['exams' => $exams, 'my_results' => []]]);
    }

    case 'get_exam': {
        $user = hn_require_role(['student', 'teacher', 'admin']);
        $id = $_GET['exam_id'] ?? '';
        $exam = null;
        foreach (hn_load('exams.json') as $e) {
            if ($e['id'] === $id) $exam = $e;
        }
        if (!$exam) send_response(['success' => false, 'message' => 'آزمون یافت نشد.']);
        if ($user['role'] === 'student') {
            // فقط آزمون فعالِ کلاس خودش
            $class = $user['class'] ?? '';
            if (($exam['status'] ?? '') !== 'active' || (!in_array($exam['class'] ?? 'all', ['all', $class]))) {
                send_response(['success' => false, 'message' => 'این آزمون برای شما در دسترس نیست.']);
            }
            $exam['key_count'] = count($exam['keys'] ?? []);
            unset($exam['keys']); // کلید هرگز به دانش‌آموز ارسال نمی‌شود
        }
        send_response(['success' => true, 'data' => $exam]);
    }

    case 'submit_answers': {
        $user = hn_require_role('student');
        hn_csrf_check();
        $id = $_POST['exam_id'] ?? '';
        $class = $user['class'] ?? '';
        $exam = null;
        foreach (hn_load('exams.json') as $e) {
            // استخراج کلید از فایل (نه از پاسخ JSON ارسالی کاربر)
            if ($e['id'] === $id) $exam = $e;
        }
        if (!$exam || ($exam['status'] ?? '') !== 'active') {
            send_response(['success' => false, 'message' => 'آزمون یافت نشد یا فعال نیست.']);
        }
        if (!in_array($exam['class'] ?? 'all', ['all', $class])) {
            send_response(['success' => false, 'message' => 'این آزمون برای کلاس شما نیست.']);
        }
        // جلوگیری از شرکت مجدد
        foreach (hn_load('results.json') as $r) {
            if ($r['exam_id'] === $id && ($r['student_code'] ?? '') === $user['code']) {
                send_response(['success' => false, 'message' => 'شما قبلاً در این آزمون شرکت کرده‌اید.']);
            }
        }
        // تصحیح سمت سرور با کلید ذخیره‌شده (اعتماد نمی‌کنیم به امتیاز ارسالی)
        $answers = json_decode($_POST['answers'] ?? '', true);
        if (!is_array($answers)) send_response(['success' => false, 'message' => 'پاسخ‌ها نامعتبر است.']);
        $score = 0;
        $total = count($exam['keys']);
        $correct = []; $wrong = []; $blank = [];
        foreach ($exam['keys'] as $i => $key) {
            $q = $i + 1;
            $ans = $answers[$q] ?? null;
            if ($ans === null || $ans === '') $blank[] = $q;
            elseif ((int)$ans === (int)$key) { $score++; $correct[] = $q; }
            else $wrong[] = $q;
        }
        $results = hn_load('results.json');
        $results[] = [
            'id' => time() . '-' . bin2hex(random_bytes(3)),
            'exam_id' => $id,
            'exam_title' => $exam['title'],
            'student_code' => $user['code'],
            'student_name' => $user['name'],
            'student_class' => $class,
            'score' => $score,
            'total' => $total,
            'correct' => $correct,
            'wrong' => $wrong,
            'blank' => $blank,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        hn_save('results.json', $results);
        send_response([
            'success' => true,
            'message' => 'پاسخنامه شما با موفقیت ثبت شد.',
            'data' => ['score' => $score, 'total' => $total, 'correct' => $correct, 'wrong' => $wrong, 'blank' => $blank],
        ]);
    }

    default:
        send_response(['success' => false, 'message' => 'action نامعتبر است.']);
}
