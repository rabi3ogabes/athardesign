<?php
declare(strict_types=1);

namespace App\Core;

final class Log
{
    public static function write(string $level, string $message): void
    {
        $file = STORAGE_PATH . '/logs/app-' . date('Y-m') . '.log';
        $line = '[' . date('Y-m-d H:i:s') . '] ' . strtoupper($level) . ': ' . $message . PHP_EOL;
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function error(string $message): void { self::write('error', $message); }
    public static function info(string $message): void { self::write('info', $message); }
}
