<?php declare(strict_types=1);

function escape(null|string|int|float $value): string
{
    return htmlspecialchars(string: (string) ($value ?? ''), flags: ENT_QUOTES | ENT_SUBSTITUTE);
}

function version(): string
{
    $files = [
        'app.js', 'styles.css'
    ];
    $times = 0;
    foreach ($files as $file) {
        $path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR. 'assets' . DIRECTORY_SEPARATOR . $file;
        if (file_exists($path)) {
            $times += filemtime($path);
        }
    }

    return substr(md5((string) $times),0, 8);
}

/** '2026-07' → 'Juli 2026' */
function formatMonthLabel(string $monthKey): string
{
    $monthNames = [
        1 => 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
        'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember',
    ];

    [$year, $month] = explode('-', $monthKey);

    return ($monthNames[intval($month)] ?? '?') . ' ' . $year;
}

/** '2026-07-05' → 'Sa 05.07.' */
function formatShortDate(string $date): string
{
    $weekdayNames = [1 => 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }

    return $weekdayNames[intval(date('N', $timestamp))] . ' ' . date('d.m.', $timestamp);
}
