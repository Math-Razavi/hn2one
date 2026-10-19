<?php
// jalali.php — تبدیل تاریخ میلادی به شمسی (الگوریتم استاندارد)
function gregorian_to_jalali($gy, $gm, $gd) {
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100))
          + ((int)(($gy2 + 399) / 400)) + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * ((int)($days / 12053)));
    $days %= 12053;
    $jy += 4 * ((int)($days / 1461));
    $days %= 1461;
    if ($days > 365) {
        $jy += (int)(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    if ($days < 186) {
        $jm = 1 + (int)($days / 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + (int)(($days - 186) / 30);
        $jd = 1 + (($days - 186) % 30);
    }
    return [$jy, $jm, $jd];
}

function jdate_from_ts($ts) {
    $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    list($jy, $jm, $jd) = gregorian_to_jalali((int)date('Y', $ts), (int)date('n', $ts), (int)date('j', $ts));
    $months = ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
    $dayNames = ['یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه','شنبه'];
    $dayName = $dayNames[(int)date('w', $ts)];
    $out = "$dayName $jd $months[$jm] $jy";
    return str_replace(range(0, 9), $fa, $out);
}

function jdate_short($ts) {
    $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    list($jy, $jm, $jd) = gregorian_to_jalali((int)date('Y', $ts), (int)date('n', $ts), (int)date('j', $ts));
    return str_replace(range(0, 9), $fa, sprintf('%04d/%02d/%02d', $jy, $jm, $jd));
}
