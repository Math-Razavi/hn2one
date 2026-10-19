<?php
// teacher.php — ابزارهای هوشمند دبیر: طرح درس هوشمند، آزمون‌ساز هوشمند، بانک سؤالات اشتراکی، بانک فرمول‌ها
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// پوشه داده ابزارهای دبیر
if (!is_dir(HN_DATA . '/teacher')) @mkdir(HN_DATA . '/teacher', 0775, true);

// ---------- هوش مصنوعی ----------
function ai_settings(): array {
    $s = hn_load('settings.json');
    return [$s['ai_api_url'] ?? '', $s['ai_api_key'] ?? '', $s['ai_model'] ?? ''];
}
function ai_enabled(): bool {
    [$url, $key, $model] = ai_settings();
    return $url !== '' && $key !== '' && $model !== '';
}
function ai_call(string $prompt, string $system): string {
    [$url, $key, $model] = ai_settings();
    $ch = curl_init(rtrim($url, '/') . '/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_TIMEOUT        => 180,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . $key],
        CURLOPT_POSTFIELDS     => json_encode([
            'model'       => $model,
            'temperature' => 0.4,
            'max_tokens'  => 4000,
            'messages'    => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user',   'content' => $prompt],
            ],
        ], JSON_UNESCAPED_UNICODE),
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    if ($res === false) {
        send_response(['success' => false, 'message' => 'اتصال به سرویس هوش مصنوعی برقرار نشد. بعداً دوباره تلاش کنید.']);
    }
    $data = json_decode((string)$res, true);
    $text = trim((string)($data['choices'][0]['message']['content'] ?? ''));
    if ($text === '') {
        send_response(['success' => false, 'message' => 'پاسخ معتبری از سرویس هوش مصنوعی دریافت نشد.']);
    }
    return $text;
}

// ---------- کمکی ----------
function new_id(): string { return time() . '-' . bin2hex(random_bytes(3)); }
function clamp_text($str, int $max = 20000): string { return mb_substr(trim((string)$str), 0, $max); }

switch ($action) {

    // ============ طرح درس هوشمند ============
    case 'ai_lesson_plan': {
        $user = hn_require_role(['teacher', 'admin']);
        hn_csrf_check();
        if (!ai_enabled()) {
            send_response(['success' => false, 'message' => 'هوش مصنوعی هنوز تنظیم نشده است؛ از مدیر بخواهید تنظیمات را در پنل مدیریت وارد کند.']);
        }
        $grade    = hn_clean($_POST['grade'] ?? 'هفتم');
        $topic    = hn_clean($_POST['topic'] ?? '');
        $duration = hn_clean($_POST['duration'] ?? '۴۵');
        $goals    = hn_clean($_POST['goals'] ?? '');
        if ($topic === '') send_response(['success' => false, 'message' => 'موضوع طرح درس الزامی است.']);
        if (!in_array($grade, ['هفتم', 'هشتم', 'نهم'], true)) $grade = 'هفتم';

        $prompt = "برای درس ریاضی پایه $grade در مدرسه راهنمایی (متوسطه اول) روی موضوع «{$topic}» یک طرح درس جامع به مدت $duration دقیقه بنویس.\n"
            . "اهداف ویژه‌ی معلم: " . ($goals !== '' ? $goals : 'تعیین‌شده توسط خودت') . "\n"
            . "ساختار دقیق را رعایت کن: عنوان؛ اهداف کلی؛ اهداف جزئی؛ پیش‌نیازها؛ وسایل کمک‌آموزشی؛ "
            . "مراحل تدریس (آمادگی و ایجاد انگیزه، تدریس و معلم‌راهنمایی، مشارکت فعال و تمرین، جمع‌بندی) با زمان‌بندی هر مرحله؛ "
            . "تکلیف شب؛ ارزشیابی (سؤالات و نمونه تمرین).\n"
            . "خروجی فقط متن طرح درس است، بدون مقدمه و خداحافظی. عناوین اصلی را با «## » شروع کن و نکات ریز را با «·» جدا کن.";
        $content = ai_call($prompt, 'تو یک دبیر باتجربه و خلاق ریاضی متوسطه اول در ایران هستی. طرح درس حرفه‌ای، دقیق و قابل استفاده در کلاس واقعی تولید می‌کنی. خروجی فارسی است.');

        $plans = hn_load('teacher/lesson_plans.json');
        $plan = [
            'id' => new_id(),
            'teacher_code' => $user['code'],
            'teacher_name' => $user['name'],
            'grade' => $grade,
            'topic' => $topic,
            'duration' => $duration,
            'goals' => $goals,
            'content' => clamp_text($content),
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $plans[] = $plan;
        hn_save('teacher/lesson_plans.json', $plans);
        send_response([
            'success' => true,
            'message' => 'طرح درس با موفقیت ساخته و در آرشیو ذخیره شد.',
            'data'    => $plan,
        ]);
    }

    case 'lesson_plans': {
        $user = hn_require_role(['teacher', 'admin']);
        $plans = hn_load('teacher/lesson_plans.json');
        if ($user['role'] === 'teacher') {
            $plans = array_values(array_filter($plans, fn($p) => ($p['teacher_code'] ?? '') === $user['code']));
        }
        usort($plans, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));
        send_response(['success' => true, 'data' => $plans]);
    }

    case 'lesson_plan_delete': {
        $user = hn_require_role(['teacher', 'admin']);
        hn_csrf_check();
        $id = $_POST['plan_id'] ?? '';
        $plans = hn_load('teacher/lesson_plans.json');
        $ok = false;
        foreach ($plans as $p) {
            if ($p['id'] === $id && ($user['role'] === 'admin' || $p['teacher_code'] === $user['code'])) $ok = true;
        }
        if (!$ok) send_response(['success' => false, 'message' => 'طرح درس یافت نشد.']);
        hn_save('teacher/lesson_plans.json', array_values(array_filter($plans, fn($p) => $p['id'] !== $id)));
        send_response(['success' => true, 'message' => 'طرح درس حذف شد.']);
    }

    // ============ آزمون‌ساز هوشمند ============
    case 'ai_quiz': {
        $user = hn_require_role(['teacher', 'admin']);
        hn_csrf_check();
        if (!ai_enabled()) {
            send_response(['success' => false, 'message' => 'هوش مصنوعی هنوز تنظیم نشده است؛ از مدیر بخواهید تنظیمات را در پنل مدیریت وارد کند.']);
        }
        $title = hn_clean($_POST['title'] ?? '');
        $grade = hn_clean($_POST['grade'] ?? 'هفتم');
        $note  = hn_clean($_POST['note'] ?? '');
        $sections = json_decode($_POST['sections'] ?? '', true);
        if ($title === '') send_response(['success' => false, 'message' => 'عنوان آزمون الزامی است.']);
        if (!is_array($sections) || count($sections) < 1) {
            send_response(['success' => false, 'message' => 'حداقل یک بخش برای آزمون تعریف کنید.']);
        }
        if (count($sections) > 6) send_response(['success' => false, 'message' => 'حداکثر ۶ بخش مجاز است.']);
        $types  = ['چهارگزینه‌ای', 'تشریحی', 'جای خالی'];
        $levels = ['آسان', 'متوسط', 'دشوار'];
        foreach ($sections as $i => &$sec) {
            $n = max(1, min(30, (int)($sec['count'] ?? 0)));
            $sec['count']      = $n;
            $sec['name']       = hn_clean($sec['name'] ?? 'بخش ' . ($i + 1)) ?: 'بخش ' . ($i + 1);
            $sec['topic']      = hn_clean($sec['topic'] ?? '');
            $sec['type']       = in_array($sec['type'] ?? '', $types, true) ? $sec['type'] : 'چهارگزینه‌ای';
            $sec['difficulty'] = in_array($sec['difficulty'] ?? '', $levels, true) ? $sec['difficulty'] : 'متوسط';
            if ($sec['topic'] === '') send_response(['success' => false, 'message' => 'موضوع تک‌تک بخش‌ها الزامی است.']);
        }
        unset($sec);
        if (!in_array($grade, ['هفتم', 'هشتم', 'نهم'], true)) $grade = 'هفتم';

        $lines = [];
        foreach ($sections as $i => $s) {
            $lines[] = "بخش " . ($i + 1) . ": «{$s['name']}» — موضوع {$s['topic']} — {$s['count']} سؤال از نوع {$s['type']} با دشواری {$s['difficulty']}";
        }
        $prompt = "آزمون ریاضی پایه $grade با عنوان «{$title}» طراحی کن.";
        if ($note !== '') $prompt .= "\nتوجه معلم: $note";
        $prompt .= "\nساختار آزمون دقیقاً طبق جدول بخش‌های زیر:\n" . implode("\n", $lines) . "\n";
        $prompt .= "قاعده‌ها:\n- فقط متن خود آزمون را بنویس، بدون مقدمه.\n- شماره سؤال‌ها به ترتیب کل آزمون ادامه دارد.\n"
            . "- برای سؤال‌های چهارگزینه‌ای چهار گزینه عدددار بنویس و تنها یک گزینه درست است.\n"
            . "- در انتهای آزمون یک «__. پاسخنامه» شامل جواب دقیق هر سؤال بنویس.\n"
            . "- اعداد و فرمول‌ها را حتماً دقیق و سازگار با کتاب ریاضی پایه $grade بنویس.";
        $content = ai_call($prompt, 'تو طراح آزمون حرفه‌ای ریاضی متوسطه اول ایران هستی. سؤال‌های دقیق، صحیح علمی، با اعداد حساب‌شده و منطقی طراحی می‌کنی. خروجی فارسی است.');

        $quizzes = hn_load('teacher/quiz_designs.json');
        $quiz = [
            'id' => new_id(),
            'teacher_code' => $user['code'],
            'teacher_name' => $user['name'],
            'title' => $title,
            'grade' => $grade,
            'sections' => $sections,
            'content' => clamp_text($content),
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $quizzes[] = $quiz;
        hn_save('teacher/quiz_designs.json', $quizzes);
        send_response([
            'success' => true,
            'message' => 'آزمون هوشمند ساخته و در آرشیو ذخیره شد.',
            'data'    => $quiz,
        ]);
    }

    case 'quiz_save': {
        // ذخیره دستی / ویرایش آزمون (بدون AI)
        $user = hn_require_role(['teacher', 'admin']);
        hn_csrf_check();
        $id      = hn_clean($_POST['quiz_id'] ?? '');
        $title   = hn_clean($_POST['title'] ?? '');
        $grade   = hn_clean($_POST['grade'] ?? 'هفتم');
        $content = clamp_text($_POST['content'] ?? '');
        if ($title === '' || $content === '') {
            send_response(['success' => false, 'message' => 'عنوان و متن آزمون الزامی است.']);
        }
        if (!in_array($grade, ['هفتم', 'هشتم', 'نهم'], true)) $grade = 'هفتم';
        $quizzes = hn_load('teacher/quiz_designs.json');
        if ($id !== '') {
            $found = false;
            foreach ($quizzes as &$q) {
                if ($q['id'] === $id && ($user['role'] === 'admin' || $q['teacher_code'] === $user['code'])) {
                    $q['title'] = $title;
                    $q['grade'] = $grade;
                    $q['content'] = $content;
                    $found = true;
                }
            }
            unset($q);
            if (!$found) send_response(['success' => false, 'message' => 'آزمون یافت نشد.']);
            $savedId = $id;
        } else {
            $id = new_id();
            $quizzes[] = [
                'id' => $id,
                'teacher_code' => $user['code'],
                'teacher_name' => $user['name'],
                'title' => $title,
                'grade' => $grade,
                'sections' => [],
                'content' => $content,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            $savedId = $id;
        }
        hn_save('teacher/quiz_designs.json', $quizzes);
        send_response(['success' => true, 'message' => 'آزمون ذخیره شد.', 'data' => ['id' => $savedId]]);
    }

    case 'quiz_designs': {
        $user = hn_require_role(['teacher', 'admin']);
        $quizzes = hn_load('teacher/quiz_designs.json');
        if ($user['role'] === 'teacher') {
            $quizzes = array_values(array_filter($quizzes, fn($q) => ($q['teacher_code'] ?? '') === $user['code']));
        }
        usort($quizzes, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));
        send_response(['success' => true, 'data' => $quizzes]);
    }

    case 'quiz_design_delete': {
        $user = hn_require_role(['teacher', 'admin']);
        hn_csrf_check();
        $id = $_POST['quiz_id'] ?? '';
        $quizzes = hn_load('teacher/quiz_designs.json');
        $ok = false;
        foreach ($quizzes as $q) {
            if ($q['id'] === $id && ($user['role'] === 'admin' || $q['teacher_code'] === $user['code'])) $ok = true;
        }
        if (!$ok) send_response(['success' => false, 'message' => 'آزمون یافت نشد.']);
        hn_save('teacher/quiz_designs.json', array_values(array_filter($quizzes, fn($q) => $q['id'] !== $id)));
        send_response(['success' => true, 'message' => 'آزمون بایگانی حذف شد.']);
    }

    case 'quiz_export_html': {
        // خروجی HTML آزمون (paper قابل چاپ)
        $user = hn_require_role(['teacher', 'admin']);
        $id = $_GET['quiz_id'] ?? $_POST['quiz_id'] ?? '';
        $quiz = null;
        foreach (hn_load('teacher/quiz_designs.json') as $q) {
            if ($q['id'] === $id && ($user['role'] === 'admin' || $q['teacher_code'] === $user['code'])) $quiz = $q;
        }
        if (!$quiz) send_response(['success' => false, 'message' => 'آزمون یافت نشد.']);
        ob_start(); ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($quiz['title'], ENT_QUOTES, 'UTF-8'); ?></title>
<link rel="stylesheet" href="/assets/css/fonts.css">
<style>
  body { font-family: 'Vazirmatn', sans-serif; background:#f3f6f9; margin:0; }
  .sheet { max-width:800px; margin:24px auto; background:#fff; padding:40px 48px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,.08); }
  .head { display:flex; justify-content:space-between; border-bottom:3px double #1e293b; padding-bottom:12px; margin-bottom:18px; }
  .head h1 { font-size:22px; margin:0; }
  .meta { color:#475569; font-size:14px; }
  pre.body { white-space:pre-wrap; font-family:'Vazirmatn',sans-serif; font-size:15px; line-height:2.1; margin:0; }
  .no-print { position:fixed; top:14px; left:14px; display:flex; gap:8px; }
  .no-print button,.no-print a { background:#16a34a; color:#fff; border:none; padding:9px 18px; border-radius:8px; font-family:inherit; font-weight:700; cursor:pointer; text-decoration:none; font-size:14px; }
  @media print { .no-print { display:none; } body { background:#fff; } .sheet { box-shadow:none; margin:0; border-radius:0; max-width:100%; } }
</style>
</head>
<body>
<div class="no-print"><button onclick="window.print()">🖨️ چاپ آزمون</button><a href="/azmoon/teacher.html">بازگشت به پنل</a></div>
<div class="sheet">
  <div class="head">
    <h1><?php echo htmlspecialchars($quiz['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="meta">پایه <?php echo htmlspecialchars($quiz['grade'], ENT_QUOTES, 'UTF-8'); ?> — دبیر: <?php echo htmlspecialchars($quiz['teacher_name'], ENT_QUOTES, 'UTF-8'); ?></div>
  </div>
  <pre class="body"><?php echo htmlspecialchars($quiz['content'], ENT_QUOTES, 'UTF-8'); ?></pre>
</div>
</body>
</html>
<?php echo ob_get_clean(); exit;
    }

    // ============ بانک سؤالات اشتراکی ============
    case 'questions': {
        $user = hn_require_role(['teacher', 'admin']);
        $grade = $_GET['grade'] ?? '';
        $difficulty = $_GET['difficulty'] ?? '';
        $q = mb_strtolower(trim((string)($_GET['q'] ?? '')));
        $questions = hn_load('teacher/questions.json');
        $out = [];
        foreach ($questions as $item) {
            if ($grade !== '' && ($item['grade'] ?? '') !== $grade) continue;
            if ($difficulty !== '' && ($item['difficulty'] ?? '') !== $difficulty) continue;
            if ($q !== '' && mb_strpos(mb_strtolower($item['question'] . ' ' . ($item['topic'] ?? '')), $q) === false) continue;
            $out[] = $item;
        }
        usort($out, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));
        send_response(['success' => true, 'data' => $out]);
    }

    case 'question_add': {
        $user = hn_require_role(['teacher', 'admin']);
        hn_csrf_check();
        $grade = hn_clean($_POST['grade'] ?? 'هفتم');
        $topic = hn_clean($_POST['topic'] ?? '');
        $difficulty = hn_clean($_POST['difficulty'] ?? 'متوسط');
        $question = clamp_text($_POST['question'] ?? '', 5000);
        $answer = clamp_text($_POST['answer'] ?? '', 5000);
        if ($question === '' || $answer === '') send_response(['success' => false, 'message' => 'متن سؤال و پاسخ الزامی است.']);
        if (!in_array($grade, ['هفتم', 'هشتم', 'نهم'], true)) $grade = 'هفتم';
        if (!in_array($difficulty, ['آسان', 'متوسط', 'دشوار'], true)) $difficulty = 'متوسط';
        $questions = hn_load('teacher/questions.json');
        $questions[] = [
            'id' => new_id(),
            'teacher_code' => $user['code'],
            'teacher_name' => $user['name'],
            'grade' => $grade,
            'topic' => $topic,
            'difficulty' => $difficulty,
            'question' => $question,
            'answer' => $answer,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        hn_save('teacher/questions.json', $questions);
        send_response(['success' => true, 'message' => 'سؤال به بانک اشتراکی اضافه شد.']);
    }

    case 'question_delete': {
        $user = hn_require_role(['teacher', 'admin']);
        hn_csrf_check();
        $id = $_POST['question_id'] ?? '';
        $questions = hn_load('teacher/questions.json');
        $ok = false;
        foreach ($questions as $item) {
            if ($item['id'] === $id && ($user['role'] === 'admin' || $item['teacher_code'] === $user['code'])) $ok = true;
        }
        if (!$ok) send_response(['success' => false, 'message' => 'سؤال یافت نشد یا دسترسی ندارید.']);
        hn_save('teacher/questions.json', array_values(array_filter($questions, fn($item) => $item['id'] !== $id)));
        send_response(['success' => true, 'message' => 'سؤال حذف شد.']);
    }

    // ============ بانک فرمول‌ها ============
    case 'formulas': {
        $user = hn_require_role(['teacher', 'admin']);
        $q = mb_strtolower(trim((string)($_GET['q'] ?? '')));
        $formulas = hn_load('teacher/formulas.json');
        $out = [];
        foreach ($formulas as $f) {
            if ($q !== '' && mb_strpos(mb_strtolower(($f['title'] ?? '') . ' ' . ($f['topic'] ?? '') . ' ' . ($f['latex'] ?? '') . ' ' . ($f['description'] ?? '')), $q) === false) continue;
            $out[] = $f;
        }
        usort($out, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));
        send_response(['success' => true, 'data' => $out]);
    }

    case 'formula_add': {
        $user = hn_require_role(['teacher', 'admin']);
        hn_csrf_check();
        $topic = hn_clean($_POST['topic'] ?? '');
        $title = hn_clean($_POST['title'] ?? '');
        // لاتک خام نگه‌داری می‌شود (رفتار نمایش را خراب نمی‌کند)
        $latex = clamp_text($_POST['latex'] ?? '', 2000);
        $description = hn_clean($_POST['description'] ?? '');
        if ($title === '' || $latex === '') send_response(['success' => false, 'message' => 'عنوان و فرمول لاتک الزامی است.']);
        $formulas = hn_load('teacher/formulas.json');
        $formulas[] = [
            'id' => new_id(),
            'teacher_code' => $user['code'],
            'teacher_name' => $user['name'],
            'topic' => $topic,
            'title' => $title,
            'latex' => $latex,
            'description' => $description,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        hn_save('teacher/formulas.json', $formulas);
        send_response(['success' => true, 'message' => 'فرمول به بانک اضافه شد.']);
    }

    case 'formula_delete': {
        $user = hn_require_role(['teacher', 'admin']);
        hn_csrf_check();
        $id = $_POST['formula_id'] ?? '';
        $formulas = hn_load('teacher/formulas.json');
        $ok = false;
        foreach ($formulas as $f) {
            if ($f['id'] === $id && ($user['role'] === 'admin' || $f['teacher_code'] === $user['code'])) $ok = true;
        }
        if (!$ok) send_response(['success' => false, 'message' => 'فرمول یافت نشد یا دسترسی ندارید.']);
        hn_save('teacher/formulas.json', array_values(array_filter($formulas, fn($f) => $f['id'] !== $id)));
        send_response(['success' => true, 'message' => 'فرمول حذف شد.']);
    }

    default:
        send_response(['success' => false, 'message' => 'action نامعتبر است.']);
}
