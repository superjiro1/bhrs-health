<?php
/** Shared by index.php and export.php: resolves ?period=daily|monthly|yearly&date=YYYY-MM-DD to a date range. */
declare(strict_types=1);
if (!function_exists('get')) { http_response_code(403); exit('Forbidden'); }

$period = get('period', 'monthly');
if (!in_array($period, ['daily', 'monthly', 'yearly'], true)) { $period = 'monthly'; }
$date = get('date', date('Y-m-d'));
if (!valid_date($date) || $date > date('Y-m-d')) { $date = date('Y-m-d'); }
$ts = strtotime($date);
if ($period === 'daily') {
    $from = $to = $date;
    $periodLabel = date('F d, Y', $ts);
} elseif ($period === 'monthly') {
    $from = date('Y-m-01', $ts);
    $to = date('Y-m-t', $ts);
    $periodLabel = date('F Y', $ts);
} else {
    $from = date('Y-01-01', $ts);
    $to = date('Y-12-31', $ts);
    $periodLabel = date('Y', $ts);
}
