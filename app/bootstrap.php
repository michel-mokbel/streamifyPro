<?php
declare(strict_types=1);

namespace App;

// Load .env file if it exists
$envFile = dirname(__DIR__) . '/api/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove quotes if present
            if (preg_match('/^(["\'])(.*)\1$/', $value, $m)) {
                $value = $m[2];
            }
            if (!empty($key) && getenv($key) === false) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Normalizer.php';
require_once __DIR__ . '/Recommender.php';
require_once __DIR__ . '/CatalogIndex.php';
require_once __DIR__ . '/Cache.php';

use App\Config;
use App\Normalizer;
use App\Recommender;

function json_error(int $code, string $msg): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['error'=>$msg, 'code'=>$code], JSON_UNESCAPED_SLASHES);
    exit;
}

function load_catalog_default(): array {
    $base = dirname(__DIR__) . '/api/json';
    $paths = [
        $base . '/kids-ar.json',
        $base . '/games-ar.json',
        $base . '/streaming-ar.json',
        $base . '/fitness-ar.json',
    ];
    return Normalizer::loadCatalog($paths);
}


use App\Cache;

/**
 * Load catalog directly without server-side caching.
 * Browser will cache the API responses automatically via HTTP headers.
 */
function load_catalog_default_cached(): array {
    $base = dirname(__DIR__) . '/api/json';
    $paths = [
        $base . '/kids-ar.json',
        $base . '/games-ar.json',
        $base . '/streaming-ar.json',
        $base . '/fitness-ar.json',
    ];
    return Normalizer::loadCatalog($paths);
}


use App\CatalogIndex;

function build_catalog_index(array $items): CatalogIndex {
    return new CatalogIndex($items);
}
