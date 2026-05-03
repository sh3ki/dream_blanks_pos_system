<?php

namespace App\Helpers;

class DateHelper
{
    public static function format(string $date, string $format = 'M d, Y'): string
    {
        return date($format, strtotime($date));
    }

    public static function diffDays(string $from, string $to = 'now'): int
    {
        return (int)((strtotime($to) - strtotime($from)) / 86400);
    }

    public static function timeAgo(string $datetime): string
    {
        $diff = time() - strtotime($datetime);
        if ($diff < 60)     return 'just now';
        if ($diff < 3600)   return floor($diff / 60) . 'm ago';
        if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        return date('M d, Y', strtotime($datetime));
    }
}
