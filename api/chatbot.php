<?php
declare(strict_types=1);

use App\Config;
use App\Recommender;
use App\CatalogIndex;
use function App\load_catalog_default;
use function App\json_error;

require_once __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

// -------------------- Configuration --------------------
$API_KEY = Config::requireEnv('GEMINI_API_KEY');
$MODEL = Config::model();
$ALLOWED_CATEGORIES = [
    'alphabet', 'animals', 'numbers', 'stories', 'science', 
    'dance', 'games', 'fitness', 'music', 'cartoons', 'educational'
];

// -------------------- Load catalog & index --------------------
$catalog = load_catalog_default();
$index = new CatalogIndex($catalog);

// -------------------- Handle caching --------------------
$jsonFiles = [
    __DIR__ . '/json/kids-ar.json',
    __DIR__ . '/json/games-ar.json',
    __DIR__ . '/json/streaming-ar.json',
    __DIR__ . '/json/fitness-ar.json'
];
$fileMTimes = [];
foreach ($jsonFiles as $file) {
    if (is_file($file)) $fileMTimes[] = filemtime($file);
}
$etag = md5(json_encode($fileMTimes));
header('ETag: "' . $etag . '"');
header('Cache-Control: public, max-age=3600');
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === '"' . $etag . '"') {
    http_response_code(304);
    exit;
}

// -------------------- Parse request --------------------
$raw = file_get_contents('php://input');
$body = json_decode($raw, true) ?: [];
$message = trim((string)($body['message'] ?? ''));
$maxItems = (int)($body['max_items'] ?? 8);
$seed = isset($body['seed']) ? (int)$body['seed'] : null;
$debug = (bool)($body['debug'] ?? false);

// Debug tracking
$debugInfo = [];
if ($debug) {
    $debugInfo['start_time'] = microtime(true);
    $debugInfo['input'] = [
        'message' => $message,
        'max_items' => $maxItems,
        'seed' => $seed
    ];
}

// -------------------- Category Router --------------------
function routeToCategories(string $message, array $allowedCategories, string $apiKey, string $model, bool $debug = false): array {
    $startTime = microtime(true);
    
    // Prepare system prompt
    $system = "You classify user requests into allowed kid categories.\n\n" .
              "Return JSON only:\n" .
              "{\"categories\":[\"<cat1>\",\"<cat2>\"], \"primary\":\"<one_of_categories>\"}\n\n" .
              "Rules:\n" .
              "- Pick 1 to 3 categories.\n" .
              "- Choose ONLY from this list (exact spelling):\n" .
              "- " . implode(" | ", $allowedCategories) . "\n" .
              "- No prose, no extra keys, no markdown.\n" .
              "- If unsure, pick the single best category.";
    
    // Call Gemini
    $req = [
        'contents' => [
            ['role' => 'user', 'parts' => [['text' => $system]]],
            ['role' => 'user', 'parts' => [['text' => $message]]]
        ],
        'generationConfig' => [
            'temperature' => 0,
            'maxOutputTokens' => 50,
            'response_mime_type' => 'application/json'
        ]
    ];
    
    try {
        $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode($req, JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => 5
        ]);
        
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($code >= 200 && $code < 300 && $resp) {
            $data = json_decode($resp, true);
            $text = '';
            foreach (($data['candidates'][0]['content']['parts'] ?? []) as $part) {
                if (isset($part['text'])) $text .= $part['text'];
            }
            
            $result = json_decode(trim($text), true);
            if (is_array($result) && isset($result['categories']) && is_array($result['categories'])) {
                // Validate categories
                $validCategories = [];
                foreach ($result['categories'] as $cat) {
                    if (is_string($cat) && in_array($cat, $allowedCategories)) {
                        $validCategories[] = $cat;
                    }
                }
                
                if (!empty($validCategories)) {
                    $primary = $result['primary'] ?? $validCategories[0];
                    if (!in_array($primary, $validCategories)) {
                        $primary = $validCategories[0];
                    }
                    
                    return [
                        'success' => true,
                        'categories' => array_slice($validCategories, 0, 3),
                        'primary' => $primary,
                        'method' => 'ai',
                        'time_ms' => round((microtime(true) - $startTime) * 1000)
                    ];
                }
            }
        }
    } catch (Exception $e) {
        // Fall through to keyword fallback
    }
    
    // AI failed - use keyword fallback
    return keywordFallback($message, $allowedCategories, $startTime);
}

function keywordFallback(string $message, array $allowedCategories, float $startTime): array {
    $messageLower = mb_strtolower($message);
    
    // Keyword mappings (English and Arabic)
    $mappings = [
        'alphabet' => ['alphabet', 'letters', 'phonics', 'abc', 'الحروف', 'أبجدية', 'حروف'],
        'animals' => ['animals', 'animal', 'zoo', 'wildlife', 'حيوانات', 'حيوان'],
        'numbers' => ['numbers', 'number', 'counting', 'math', 'أرقام', 'عد', 'رياضيات'],
        'stories' => ['stories', 'story', 'bedtime', 'tale', 'قصص', 'قصة', 'حكايات'],
        'science' => ['science', 'experiment', 'discovery', 'علوم', 'تجارب', 'اكتشاف'],
        'dance' => ['dance', 'dancing', 'رقص', 'رقصة'],
        'games' => ['games', 'game', 'play', 'interactive', 'ألعاب', 'لعبة'],
        'fitness' => ['fitness', 'exercise', 'workout', 'sports', 'تمارين', 'رياضة'],
        'music' => ['music', 'songs', 'song', 'sing', 'أغاني', 'أناشيد', 'موسيقى'],
        'cartoons' => ['cartoon', 'animation', 'animated', 'رسوم متحركة', 'كرتون'],
        'educational' => ['educational', 'learning', 'learn', 'تعليمي', 'تعلم']
    ];
    
    $matched = [];
    foreach ($mappings as $category => $keywords) {
        if (!in_array($category, $allowedCategories)) continue;
        
        foreach ($keywords as $keyword) {
            if (mb_strpos($messageLower, $keyword) !== false) {
                $matched[] = $category;
                break;
            }
        }
    }
    
    // Dedupe and limit
    $matched = array_unique($matched);
    if (empty($matched)) {
        $matched = ['educational'];
    }
    
    $categories = array_slice($matched, 0, 3);
    
    return [
        'success' => true,
        'categories' => $categories,
        'primary' => $categories[0],
        'method' => 'keyword',
        'time_ms' => round((microtime(true) - $startTime) * 1000)
    ];
}

// -------------------- Route to categories --------------------
$routeResult = routeToCategories($message, $ALLOWED_CATEGORIES, $API_KEY, $MODEL, $debug);

if ($debug) {
    $debugInfo['router'] = $routeResult;
}

// -------------------- Sample items from categories --------------------
$startSample = microtime(true);
$categories = $routeResult['categories'];
$primary = $routeResult['primary'];

// Sample items
$items = $index->sampleItems($categories, $maxItems, $seed);

// Build detail URLs for items
$cards = [];
foreach ($items as $item) {
    $card = [
        'id' => $item['id'],
        'title' => $item['title'],
        'type' => $item['type'],
        'source' => $item['source'] ?? '',
        'age_min' => $item['age_min'],
        'age_max' => $item['age_max'],
        'duration_sec' => $item['duration_sec'],
        'thumbnail' => $item['thumbnail'],
        'category' => $item['category'] ?? null,
    ];
    
    // Build detail URL
    $source = (string)($item['source'] ?? '');
    $id = (string)($item['id'] ?? '');
    
    if ($source === 'kids') {
        $channelId = (string)($item['channel_id'] ?? '');
        $playlistId = (string)($item['playlist_id'] ?? '');
        if ($channelId && $playlistId && $id) {
            $card['detail_url'] = 'kids-video.php?channel=' . urlencode($channelId) . 
                                  '&playlist=' . urlencode($playlistId) . '&video=' . urlencode($id);
        } else {
            $card['detail_url'] = 'kids.php';
        }
    } elseif ($source === 'games') {
        $category = (string)($item['category'] ?? '');
        $card['detail_url'] = 'game-detail.php?id=' . urlencode($id) . 
                              ($category ? '&category=' . urlencode($category) : '');
    } elseif ($source === 'fitness') {
        $card['detail_url'] = 'fitness-detail.php?id=' . urlencode($id);
    } elseif ($source === 'streaming') {
        $category = (string)($item['category'] ?? '');
        $card['detail_url'] = 'video-detail.php?id=' . urlencode($id) . 
                              ($category ? '&category=' . urlencode($category) : '');
    } else {
        $card['detail_url'] = 'video-detail.php?id=' . urlencode($id);
    }
    
    $cards[] = $card;
}

if ($debug) {
    $debugInfo['sampler'] = [
        'categories' => $categories,
        'primary' => $primary,
        'pool_sizes' => array_map(fn($cat) => [
            'category' => $cat,
            'total_items' => count($index->getCategoryItems($cat))
        ], $categories),
        'sampled_count' => count($cards),
        'seed' => $seed,
        'time_ms' => round((microtime(true) - $startSample) * 1000)
    ];
    
    $debugInfo['total_time_ms'] = round((microtime(true) - $debugInfo['start_time']) * 1000);
}

// -------------------- Build response --------------------
$response = [
    'summary' => 'Top picks for ' . implode(' and ', $categories) . '.',
    'result_type' => 'suggestions',
    'items' => $cards
];

if ($debug) {
    $response['debug'] = $debugInfo;
}

echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;