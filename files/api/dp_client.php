<?php
// dp_client.php — کارخواه داناپلاس (سازگار با مدل BigBlueButton مستند نسخه ۱.۰)
// checksum = SHA1(methodName + exactQueryStringWithoutChecksum + sharedSecret) — رشته ارسالی و امضاشده یکسان است.
// کلید اشتراکی فقط سمت سرور استفاده و هرگز در پاسخ/لاگ/HTML قرار نمی‌گیرد.

require_once __DIR__ . '/config.php';

// ---------- پیکربندی (از settings.json، فیلدهای dp_api_address / dp_shared_secret) ----------
function dp_config(): array {
    $s = hn_load('settings.json');
    return [
        'address' => trim((string)($s['dp_api_address'] ?? '')),
        'secret'  => trim((string)($s['dp_shared_secret'] ?? '')),
    ];
}

function dp_available(): bool {
    $c = dp_config();
    return $c['address'] !== '' && $c['secret'] !== '';
}

// قاعده امنیتی: فقط HTTPS؛ استثنا فقط برای توسعه لوکال (127.0.0.1/localhost)
function dp_transport_error(): string {
    $c = dp_config();
    if ($c['address'] === '') return 'اتصال به کلاس‌های مجازی تنظیم نشده است. با مدیر مدرسه تماس بگیرید.';
    $host = (string)(parse_url($c['address'], PHP_URL_HOST) ?? '');
    $scheme = (string)(parse_url($c['address'], PHP_URL_SCHEME) ?? '');
    if ($scheme === 'https') return '';
    $isLocal = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    if ($scheme === 'http' && $isLocal) return '';
    return 'آدرس سرویس باید HTTPS باشد. با مدیر مدرسه تماس بگیرید.';
}

// ساخت query string با encoder استاندارد؛ همان رشته که امضا می‌شود، ارسال می‌شود
function dp_query_string(array $params): string {
    ksort($params);
    $parts = [];
    foreach ($params as $k => $v) {
        if ($v === null || $v === '') continue;
        $parts[] = rawurlencode((string)$k) . '=' . rawurlencode((string)$v);
    }
    return implode('&', $parts);
}

function dp_join_url(array $params): string {
    $c = dp_config();
    $qs = dp_query_string($params);
    $checksum = sha1('join' . $qs . $c['secret']);
    return rtrim($c['address'], '/') . '/api/join?' . $qs . '&checksum=' . $checksum;
}

// ---------- فراخوانی API (XML) ----------
function dp_call(string $method, array $params): array {
    $err = dp_transport_error();
    if ($err !== '') return ['success' => false, 'message' => $err];

    $c = dp_config();
    $params = array_filter($params, fn($v) => $v !== null && $v !== '');
    $qs = dp_query_string($params);
    $checksum = sha1($method . $qs . $c['secret']);
    $url = rtrim($c['address'], '/') . '/api/' . $method . '?' . $qs . '&checksum=' . $checksum;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $body = curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($body === false || $body === '') {
        dp_log('api_error', "[$method] " . ($curlErr ?: 'پاسخ خالی'));
        return ['success' => false, 'message' => 'اتصال به سرویس کلاس مجازی برقرار نشد. بعداً تلاش کنید.'];
    }

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string((string)$body);
    libxml_clear_errors();
    if ($xml === false) {
        dp_log('api_error', "[$method] XML نامعتبر");
        return ['success' => false, 'message' => 'پاسخ نامعتبر از سرویس کلاس مجازی.'];
    }

    $returncode = (string)($xml->returncode ?? '');
    if ($returncode !== 'SUCCESS') {
        $msg = trim((string)($xml->message ?? ''));
        dp_log('api_failed', "[$method] " . ((string)($xml->messageKey ?? '')) . ' ' . $msg);
        return ['success' => false, 'message' => $msg !== '' ? $msg : 'درخواست سرویس نافرجام بود.', 'messageKey' => (string)($xml->messageKey ?? '')];
    }

    // تبدیل XML به آرایه ساده (فقط متن‌ها؛ رنگ/ساختار پیچیده نگه نمی‌داریم)
    $out = ['returncode' => $returncode];
    if (isset($xml->attendees) && isset($xml->attendees->attendee)) {
        $out['attendees'] = [];
        foreach ($xml->attendees->attendee as $a) {
            $out['attendees'][] = [
                'userID'   => (string)($a->userID ?? ''),
                'fullName' => (string)($a->fullName ?? ''),
                'role'     => (string)($a->role ?? ''),
            ];
        }
    }
    foreach ($xml as $k => $v) {
        if ((string)$k === 'attendees') continue;
        $out[(string)$k] = trim((string)$v);
    }
    return ['success' => true, 'data' => $out];
}

// ---------- لاگ امن (بدون رمز، بدون URL امضاشده) ----------
function dp_log(string $event, string $detail = ''): void {
    $log = hn_load('dp_log.json');
    $log[] = [
        'time'   => date('Y-m-d H:i:s'),
        'event'  => $event,
        'detail' => mb_substr($detail, 0, 300),
    ];
    if (count($log) > 500) $log = array_slice($log, -500);
    hn_save('dp_log.json', $log);
}
