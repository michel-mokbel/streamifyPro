<?php
declare(strict_types=1);

namespace App;

final class Config {
    public static function env(string $key, ?string $default=null): string {
        // Check multiple sources for env vars
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($val === false || $val === null || $val === '') return $default ?? '';
        return $val;
    }

    public static function requireEnv(string $key): string {
        $val = self::env($key, null);
        if ($val === null || $val === '') {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => "Missing required env: {$key}", 'code' => 401]);
            exit;
        }
        return $val;
    }

    public static function model(): string {
        $m = self::env('GEMINI_MODEL', 'gemini-2.5-flash');
        return $m;
    }

    public static function defaultLanguage(): string {
        return strtolower(self::env('DEFAULT_LANGUAGE', 'en'));
    }

    public static function defaultAge(): int {
        return (int) self::env('DEFAULT_AGE', '6');
    }

    public static function logLevel(): string {
        return strtolower(self::env('LOG_LEVEL', 'info'));
    }
}
