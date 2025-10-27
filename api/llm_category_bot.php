<?php
// api/llm_category_bot.php
declare(strict_types=1);

use App\Config;
use App\Recommender;
use function App\load_catalog_default;
use function App\json_error;

require_once __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

// -------------------- Config & helpers --------------------
$API_KEY = Config::requireEnv('GEMINI_API_KEY');
$MODEL   = Config::model();

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

function closest_category(string $guess, array $categories): string {
    $guessN = mb_strtolower($guess, 'UTF-8');
    $best = $categories[0]; $bestScore = -1.0;
    foreach ($categories as $c) {
        similar_text($guessN, mb_strtolower($c, 'UTF-8'), $pct);
        if ($pct > $bestScore) { $bestScore = $pct; $best = $c; }
    }
    return $best;
}

function build_detail_url(array $item): string {
    $id = (string)($item['id'] ?? '');
    $source = (string)($item['source'] ?? '');
    if ($source === 'kids') {
        $ch = (string)($item['channel_id'] ?? '');
        $pl = (string)($item['playlist_id'] ?? '');
        if ($ch && $pl && $id) return 'kids-video.php?channel='.urlencode($ch).'&playlist='.urlencode($pl).'&video='.urlencode($id);
        return 'kids.php';
    }
    if ($source === 'games') {
        $cat = (string)($item['category'] ?? '');
        return 'game-detail.php?id='.urlencode($id).($cat ? '&category='.urlencode($cat) : '');
    }
    if ($source === 'fitness') return 'fitness-detail.php?id='.urlencode($id);
    if ($source === 'streaming') {
        $cat = (string)($item['category'] ?? '');
        return 'video-detail.php?id='.urlencode($id).($cat ? '&category='.urlencode($cat) : '');
    }
    return 'video-detail.php?id='.urlencode($id);
}

// -------------------- Input --------------------
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true) ?: [];
$message  = trim((string)($body['message'] ?? ''));
$maxItems = (int)($body['max_items'] ?? 8);
$DEBUG    = (isset($_GET['debug']) && $_GET['debug'] == '1') || (!empty($body['debug']));

// -------------------- Catalog & categories --------------------
$catalog = load_catalog_default();
$rec     = new Recommender($catalog);
$meta    = $rec->getCatalogMetadata();
$categories = array_values(array_unique(array_map('strval', $meta['available_categories'] ?? [])));
sort($categories);
if (empty($categories)) {
    echo json_encode([
        'summary'     => 'No categories available.',
        'result_type' => 'suggestions',
        'items'       => [],
        'debug'       => $DEBUG ? ['reason'=>'no categories'] : null
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------- Build strict classifier prompt --------------------
$whitelist = "- " . implode("\n- ", $categories);

$system = <<<TXT
You are a strict classifier. Given a child's entertainment request in ANY language (e.g., English or Arabic), choose EXACTLY ONE category from this whitelist and return JSON only:

Return:
{"category":"<one_of_the_list>"}

Rules:
- The category MUST be one of the whitelist items below. Do not invent or normalize to a different word.
- If multiple could fit, pick the single best match.
- Do not include explanations, markdown, code fences, or extra keys.
- Output must be valid JSON with lowercase property name "category" only.

Whitelist:
$whitelist
TXT;

$req = [
    'contents' => [
        ['role'=>'user','parts'=>[['text'=>$system]]],
        ['role'=>'user','parts'=>[['text'=>$message]]]
    ],
    'generationConfig' => [
        'temperature' => 0,
        'maxOutputTokens' => 100,
        'response_mime_type' => 'application/json'
    ]
];

if ($DEBUG) $dbg = ['input'=>['message'=>$message,'max_items'=>$maxItems],'categories'=>$categories];

// -------------------- Call LLM (classification) --------------------
$r = gemini_call($req, $API_KEY, $MODEL);

// Try to parse text payload as JSON: {"category":"..."}
$parts = $r['candidates'][0]['content']['parts'] ?? [];
$text  = '';
foreach ($parts as $p) {
    if (!empty($p['text'])) { $text = (string)$p['text']; break; }
}
$parsed = null; $chosen = null; $reason = null;
if ($text) {
    $parsed = json_decode($text, true);
    if (is_array($parsed) && isset($parsed['category'])) {
        $chosen = (string)$parsed['category'];
    } else {
        $reason = 'llm_return_not_json';
    }
} else {
    $reason = 'llm_empty_text';
}

// -------------------- Validate & correct category --------------------
$originalChosen = $chosen;
if ($chosen !== null) {
    // exact allowlist
    if (!in_array($chosen, $categories, true)) {
        // case-insensitive correction
        foreach ($categories as $c) {
            if (mb_strtolower($c,'UTF-8') === mb_strtolower($chosen,'UTF-8')) { $chosen = $c; break; }
        }
        // if still invalid, fuzzy to closest
        if (!in_array($chosen, $categories, true)) {
            $reason = $reason ?: 'category_not_in_whitelist';
            $chosen = closest_category($chosen, $categories);
        }
    }
} else {
    // LLM failed; derive from the user's text using simple substring or fallback to closest category name
    $reason = $reason ?: 'llm_no_category';
    $msgN = mb_strtolower($message,'UTF-8');
    foreach ($categories as $c) {
        if (str_contains($msgN, mb_strtolower($c,'UTF-8'))) { $chosen = $c; break; }
    }
    if (!$chosen) $chosen = closest_category($message, $categories);
}

// -------------------- Fetch items by category (ONLY) --------------------
$rows = $rec->suggestByCategory([
    'category'     => strtolower($chosen),
    'content_type' => 'both',
    'max_items'    => $maxItems
]);

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

// If empty (rare category), fallback once to 'educational' or the first
if (empty($items)) {
    $fallbackCat = in_array('educational', $categories, true) ? 'educational' : $categories[0];
    $rows = $rec->suggestByCategory([
        'category'     => strtolower($fallbackCat),
        'content_type' => 'both',
        'max_items'    => $maxItems
    ]);
    foreach ($rows as $it) {
        $items[] = [
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
            'detail_url'   => 'video-detail.php?id=' . urlencode((string)($it['id'] ?? ''))
        ];
    }
    $reason = $reason ? $reason . '+category_empty_fallback' : 'category_empty_fallback';
}

// -------------------- Respond --------------------
$out = [
    'summary'     => 'Top picks for ' . ($chosen ?? 'your request') . '.',
    'result_type' => 'suggestions',
    'items'       => $items
];

if ($DEBUG) {
    $out['debug'] = [
        'message'          => $message,
        'llm_raw_text'     => $text,
        'llm_parsed'       => $parsed,
        'llm_original'     => $originalChosen,
        'chosen_category'  => $chosen,
        'reason'           => $reason,
        'items_count'      => count($items),
        'whitelist_size'   => count($categories)
    ];
}

echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;
