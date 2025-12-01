<?php
class Logger {
    private static function ensureDir($path) {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
    }

    public static function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $file = __DIR__ . '/../logs/app.log';
        self::ensureDir($file);
        $log = sprintf("[%s] %s: %s %s\n", $timestamp, strtoupper($level), $message, json_encode($context));
        @file_put_contents($file, $log, FILE_APPEND);
    }

    public static function info($msg, $ctx = []) { self::log('info', $msg, $ctx); }
    public static function error($msg, $ctx = []) { self::log('error', $msg, $ctx); }
    public static function warning($msg, $ctx = []) { self::log('warning', $msg, $ctx); }
}
?>
