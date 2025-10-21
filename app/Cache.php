<?php
declare(strict_types=1);

namespace App;

final class Cache {
    public static function path(string $name): string {
        $dir = dirname(__DIR__) . '/cache';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        return $dir . '/' . $name;
    }

    public static function get(string $name): mixed {
        $file = self::path($name);
        if (!is_file($file)) return null;
        $raw = file_get_contents($file);
        if ($raw === false) return null;
        $data = json_decode($raw, true);
        return $data;
    }

    public static function set(string $name, mixed $value): void {
        $file = self::path($name);
        file_put_contents($file, json_encode($value, JSON_UNESCAPED_SLASHES));
    }
}
