<?php
/**
 * Streamify Pro API Proxy
 * Simple PHP proxy that serves local JSON files based on a route parameter.
 * Usage: /api/api.php?route=streaming|games|kids
 */

// Basic headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Get language parameter (default to 'en')
$lang = isset($_GET['lang']) ? strtolower(trim((string)$_GET['lang'])) : 'en';
$lang = ($lang === 'ar') ? 'ar' : 'en'; // Only support 'en' and 'ar'

// Map routes to local files (with language support)
$routes = [
    'streaming' => __DIR__ . '/json/streaming-ar.json', // Always use streaming-ar.json since it has both EN and AR fields
    'games' => __DIR__ . '/json/games-ar.json', // Always use games-ar.json since it has both EN and AR fields
    'kids' => __DIR__ . '/json/kids-ar.json', // Always use kids-ar.json since it has both EN and AR fields
    'fitness' => __DIR__ . '/json/fitness-ar.json', // Always use fitness-ar.json since it has both EN and AR fields
];

// Helper to return an error as JSON
function json_error(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(['error' => $message], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$route = isset($_GET['route']) ? strtolower(trim((string)$_GET['route'])) : '';
if ($route === '') {
    json_error(400, 'Missing route parameter. Use ?route=streaming|games|kids|fitness');
}

if (!array_key_exists($route, $routes)) {
    json_error(404, 'Unknown route: ' . $route);
}

$filePath = $routes[$route];
if (!is_readable($filePath)) {
    json_error(500, 'Data file is not readable for route: ' . $route);
}

$contents = file_get_contents($filePath);
if ($contents === false) {
    json_error(500, 'Failed to read data file for route: ' . $route);
}

// Validate JSON
$decoded = json_decode($contents, true);
if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
    json_error(500, 'Invalid JSON in ' . basename($filePath) . ': ' . json_last_error_msg());
}

// Send cache headers for a short time to improve responsiveness
header('Cache-Control: public, max-age=60');

echo $contents; // Return the raw JSON file verbatim as requested
exit;
?>