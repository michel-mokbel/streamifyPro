<?php
declare(strict_types=1);

use App\Config;
use App\Recommender;
use function App\load_catalog_default;
use function App\json_error;

require_once __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

// -------------------- Load model config --------------------
$API_KEY = Config::requireEnv('GEMINI_API_KEY');
$MODEL   = Config::model();

// -------------------- Load catalog + recommender --------------------
$catalog = load_catalog_default();
$rec     = new Recommender($catalog);

// -------------------- Simple ETag for client caching --------------------
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
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true) ?: [];
$message = trim((string)($body['message'] ?? 'Suggest something educational.'));
$maxItems = (int)($body['max_items'] ?? 10);

// Debug switch
$DEBUG = (isset($_GET['debug']) && $_GET['debug'] == '1') || (!empty($body['debug']));
$_dbg  = [
    'server_time' => gmdate('c'),
    'etag'        => $etag,
    'model'       => $MODEL,
    'input'       => ['message'=>$message, 'max_items'=>$maxItems]
];

// -------------------- Catalog categories (ground truth) --------------------
$meta = $rec->getCatalogMetadata(); // must expose 'available_categories'
$allCategories = array_values(array_unique(array_map('strval', $meta['available_categories'] ?? [])));
sort($allCategories);

// If catalog returns nothing, fail fast with a safe message.
if (empty($allCategories)) {
    echo json_encode([
        'summary'     => 'No categories available in the catalog.',
        'result_type' => 'suggestions',
        'items'       => [],
        'debug'       => $DEBUG ? $_dbg : null
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($DEBUG) $_dbg['categories'] = $allCategories;

// -------------------- Helpers --------------------
function build_detail_url(array $item): string {
    $id = (string)($item['id'] ?? '');
    $source = (string)($item['source'] ?? '');
    if ($source === 'kids') {
        $channelId  = (string)($item['channel_id'] ?? '');
        $playlistId = (string)($item['playlist_id'] ?? '');
        if ($channelId && $playlistId && $id) {
            return 'kids-video.php?channel=' . urlencode($channelId) . '&playlist=' . urlencode($playlistId) . '&video=' . urlencode($id);
        }
        return 'kids.php';
    }
    if ($source === 'games') {
        $category = (string)($item['category'] ?? '');
        return 'game-detail.php?id=' . urlencode($id) . ($category ? '&category=' . urlencode($category) : '');
    }
    if ($source === 'fitness') return 'fitness-detail.php?id=' . urlencode($id);
    if ($source === 'streaming') {
        $category = (string)($item['category'] ?? '');
        return 'video-detail.php?id=' . urlencode($id) . ($category ? '&category=' . urlencode($category) : '');
    }
    return 'video-detail.php?id=' . urlencode($id);
}

function pack_items_as_cards(array $rows): array {
    $items = [];
    foreach ($rows as $it) {
        $item = [
            'id'           => $it['id'],
            'title'        => $it['title'],
            'type'         => $it['type'],
            'source'       => $it['source'] ?? '',
            'age_min'      => $it['age_min'],
            'age_max'      => $it['age_max'],
            'duration_sec' => $it['duration_sec'],
            'thumbnail'    => $it['thumbnail'],
            'channel_id'   => $it['channel_id'] ?? null,
            'playlist_id'  => $it['playlist_id'] ?? null,
            'category'     => $it['category'] ?? null,
        ];
        $item['detail_url'] = build_detail_url($item);
        $items[] = $item;
    }
    return $items;
}

// Tools (server-implemented)
function tool_search_by_category(Recommender $rec, string $category, int $maxItems): array {
    $out = $rec->suggestByCategory([
        'category'     => strtolower($category),
        'content_type' => 'both',
        'max_items'    => $maxItems
    ]);
    return $out;
}
function tool_build_playlist_by_category(Recommender $rec, string $category, int $maxItems): array {
    $pl = $rec->buildPlaylistByCategory([
        'category'  => strtolower($category),
        'max_items' => $maxItems
    ]);
    return $pl['items'] ?? [];
}

// Fuzzy fallback (no model) when needed
function fuzzy_pick_category(string $message, array $categories): string {
    $q = mb_strtolower($message);
    // 1) exact/substring match
    foreach ($categories as $c) {
        if ($c === '') continue;
        if (str_contains($q, mb_strtolower($c))) return $c;
    }
    // 2) simple keyword maps
    $maps = [
        'animal'   => 'animals',
        'alphabet' => 'alphabet',
        'letters'  => 'alphabet',
        'phonics'  => 'alphabet',
        'number'   => 'numbers',
        'math'     => 'numbers',
        'story'    => 'stories',
        'bedtime'  => 'stories',
        'dance'    => 'dance',
        'science'  => 'science',
        'game'     => 'games',
        'exercise' => 'fitness',
        'workout'  => 'fitness',
        'learn'    => 'educational',
        'education'=> 'educational'
    ];
    foreach ($maps as $k => $target) {
        if (str_contains($q, $k) && in_array($target, $categories, true)) return $target;
    }
    // 3) similarity
    $best = $categories[0]; $bestScore = -1;
    foreach ($categories as $c) {
        similar_text($q, $c, $pct);
        if ($pct > $bestScore) { $bestScore = $pct; $best = $c; }
    }
    return $best ?: ($categories[0] ?? 'educational');
}

// -------------------- Model wiring --------------------
function gemini_call(array $payload, string $apiKey, string $model): array {
    $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-goog-api-key: ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES)
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) json_error(502, 'Gemini request failed: ' . curl_error($ch));
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($code < 200 || $code >= 300) json_error(502, 'Gemini HTTP ' . $code . ': ' . $resp);
    $data = json_decode($resp, true);
    if (!$data) json_error(502, 'Gemini returned invalid JSON');
    return $data;
}

// -------------------- Tool schema (ONLY category-based) --------------------
$tools = [[
    'functionDeclarations' => [
        [
            'name' => 'search_by_category',
            'description' => 'Return content by category only (no filtering by age, language, duration, mood).',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'category'  => ['type'=>'string', 'description'=>'One category name from categories[]'],
                    'max_items' => ['type'=>'integer']
                ],
                'required' => ['category']
            ]
        ],
        [
            'name' => 'build_playlist_by_category',
            'description' => 'Return a playlist by category only (no other filters).',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'category'  => ['type'=>'string', 'description'=>'One category name from categories[]'],
                    'max_items' => ['type'=>'integer']
                ],
                'required' => ['category']
            ]
        ]
    ]
]];

// -------------------- System prompt (explicit, strict) --------------------
$system =
"You are a kids entertainment assistant.\n".
"Rules:\n".
"- You MUST choose exactly ONE category from the provided list and call a function.\n".
"- DO NOT filter by age, mood, duration, or language. Ignore those concepts entirely.\n".
"- If the user mentions time (e.g., 20 minutes), you STILL only choose a category; the server will handle duration if needed.\n".
"- If the request fits more than one category, pick the strongest single match.\n".
"- Output must come from a function call. Never answer with plain text.\n\n".
"Available categories:\n- " . implode("\n- ", $allCategories) . "\n\n".
"Examples:\n".
"- \"alphabet songs\" -> search_by_category(category=\"alphabet\")\n".
"- \"animal videos\"  -> search_by_category(category=\"animals\")\n".
"- \"fun math\"       -> search_by_category(category=\"numbers\")\n".
"- \"bedtime story\"  -> search_by_category(category=\"stories\")\n".
"- \"dancing\"        -> search_by_category(category=\"dance\")\n".
"- \"science\"        -> search_by_category(category=\"science\")\n".
"- \"games\"          -> search_by_category(category=\"games\")\n";

// -------------------- First (and only) model call --------------------
$req = [
    'contents' => [
        ['role'=>'user','parts'=>[['text'=>$system]]],
        ['role'=>'user','parts'=>[['text'=>$message]]]
    ],
    'tools' => $tools,
    'generationConfig' => [
        'temperature' => 0,
        'maxOutputTokens' => 600,
        'response_mime_type' => 'application/json'
    ],
    'toolConfig' => [
        'functionCallingConfig' => [ 'mode' => 'ANY' ] // force tool usage
    ]
];
if ($DEBUG) { $_dbg['req_preview'] = ['system_len'=>strlen($system), 'message'=>$message]; }

$r1 = gemini_call($req, $API_KEY, $MODEL);
$parts = $r1['candidates'][0]['content']['parts'] ?? [];
$call  = null;
foreach ($parts as $p) {
    if (!empty($p['functionCall'])) { $call = $p['functionCall']; break; }
}
if ($DEBUG) {
    $_dbg['r1_candidates'] = count($r1['candidates'] ?? []);
    $_dbg['function_call'] = $call ?: '(none)';
}

// -------------------- Execute chosen tool or fallback --------------------
$chosenCategory = null;
$items = [];

if ($call && isset($call['name'])) {
    $name = $call['name'];
    $args = $call['args'] ?? [];
    if ($DEBUG) $_dbg['tool_name'] = $name;

    if ($name === 'search_by_category' || $name === 'build_playlist_by_category') {
        $cat = (string)($args['category'] ?? '');
        // enforce valid known category
        if (!in_array($cat, $allCategories, true)) {
            // try case-insensitive fix
            foreach ($allCategories as $c) {
                if (mb_strtolower($c) === mb_strtolower($cat)) { $cat = $c; break; }
            }
        }
        if (!in_array($cat, $allCategories, true)) {
            $cat = fuzzy_pick_category($message, $allCategories);
            if ($DEBUG) $_dbg['category_corrected'] = $cat;
        }
        $chosenCategory = $cat;

        if ($name === 'search_by_category') {
            $rows  = tool_search_by_category($rec, $cat, $maxItems);
        } else {
            $rows  = tool_build_playlist_by_category($rec, $cat, $maxItems);
        }
        $items = pack_items_as_cards($rows);
    }
}

// If model didn’t call a tool (or returned nothing), server picks best category
if (empty($items)) {
    $chosenCategory = $chosenCategory ?: fuzzy_pick_category($message, $allCategories);
    if ($DEBUG) $_dbg['fallback_category'] = $chosenCategory;
    $rows  = tool_search_by_category($rec, $chosenCategory, $maxItems);
    $items = pack_items_as_cards($rows);
}

// -------------------- Final response --------------------
$result = [
    'summary'     => 'Top picks for ' . ($chosenCategory ?? 'your request') . '.',
    'result_type' => 'suggestions',
    'items'       => $items
];

if ($DEBUG) {
    $_dbg['items_count'] = count($items);
    $_dbg['chosen_category'] = $chosenCategory;
    $result['debug'] = $_dbg;
}

echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;
