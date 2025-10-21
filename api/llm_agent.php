<?php
declare(strict_types=1);

use App\Config;
use App\Recommender;
use App\CatalogIndex;
use function App\load_catalog_default;
use function App\json_error;

require_once __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$API_KEY = Config::requireEnv('GEMINI_API_KEY');
$MODEL   = Config::model();

// Load catalog (client/browser caching via ETag below)
$catalog = load_catalog_default();
$rec     = new Recommender($catalog);
$index   = new CatalogIndex($catalog); // <-- make taxonomy available

// Generate ETag based on source files modification time for cache invalidation
$jsonFiles = [
    __DIR__ . '/json/kids-ar.json',
    __DIR__ . '/json/games-ar.json',
    __DIR__ . '/json/streaming-ar.json',
    __DIR__ . '/json/fitness-ar.json'
];
$fileMTimes = [];
foreach ($jsonFiles as $file) {
    if (is_file($file)) {
        $fileMTimes[] = filemtime($file);
    }
}
$etag = md5(json_encode($fileMTimes));
header('ETag: "' . $etag . '"');
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour in browser

// Handle If-None-Match header for cache validation
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === '"' . $etag . '"') {
    http_response_code(304);
    exit;
}

// Load comprehensive catalog metadata for AI decision making (if present)
$metadataFile = __DIR__ . '/json/catalog_metadata.json';
if (is_file($metadataFile)) {
    $fullMetadata = json_decode(file_get_contents($metadataFile), true) ?: [];
    // Keep existing structure for compatibility with your Recommender
    $metadata = $rec->getCatalogMetadata();
} else {
    $metadata = $rec->getCatalogMetadata();
    $fullMetadata = null;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true) ?: [];
$message = trim((string)($body['message'] ?? 'Suggest something educational for my child.'));
$age = (int)($body['age'] ?? Config::defaultAge());
$lang = strtolower((string)($body['language'] ?? Config::defaultLanguage()));
$minutes = isset($body['minutes']) ? (int)$body['minutes'] : null;

// -------- Helper: detail page URLs (kept from your logic) --------
function build_detail_url(array $item): string {
    $id = (string)($item['id'] ?? '');
    $source = (string)($item['source'] ?? '');
    if ($source === 'kids') {
        $channelId = (string)($item['channel_id'] ?? '');
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
    if ($source === 'fitness') {
        return 'fitness-detail.php?id=' . urlencode($id);
    }
    if ($source === 'streaming') {
        $category = (string)($item['category'] ?? '');
        return 'video-detail.php?id=' . urlencode($id) . ($category ? '&category=' . urlencode($category) : '');
    }
    return 'video-detail.php?id=' . urlencode($id);
}

// -------- Tool helpers (MUST be defined before use) --------
function tool_list_taxonomy(CatalogIndex $index): array {
    $summary = [
        'sources'       => $index->sources,
        'languages'     => $index->lang,
        'categories'    => $index->categories,
        'subcategories' => $index->subcategories,
        'channels'      => $index->channels,
        'playlists'     => $index->playlists
    ];
    return [
        'summary'     => 'Available taxonomy for structured search.',
        'result_type' => 'taxonomy',
        'items'       => [$summary]
    ];
}

/**
 * Expects Recommender::searchCatalogStructured(...) to exist.
 * If you used the earlier patch, it filters by taxonomy with fallback relaxation.
 */
function tool_search_catalog_structured(Recommender $rec, array $args): array {
    $max   = (int)($args['max_items'] ?? 12);
    $items = $rec->searchCatalogStructured($args, $max);
    $out = [];
    foreach ($items as $it) {
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
        $out[] = $item;
    }
    return [
        'summary'     => 'Results from structured search.',
        'result_type' => 'suggestions',
        'items'       => $out
    ];
}

/**
 * Your existing tools (kept) — search by category/metadata and playlist.
 * These call your Recommender methods and wrap items as cards.
 */
function tool_search_catalog(Recommender $rec, array $args, int $age, string $lang): array {
    $contentType = $args['content_type'] ?? $args['wants'] ?? 'both';
    $maxItems = (int)($args['max_items'] ?? 10);

    $hasTags = !empty($args['tags']) && is_array($args['tags']);
    $hasChannels = !empty($args['channels']) && is_array($args['channels']);
    $hasSources = !empty($args['sources']) && is_array($args['sources']);
    $hasCategories = !empty($args['categories']) && is_array($args['categories']);
    $hasCategory = !empty($args['category']) && is_string($args['category']);

    if ($hasCategory || (!$hasTags && !$hasChannels && !$hasSources && !$hasCategories)) {
        $category = strtolower((string)($args['category'] ?? 'educational'));
        $out = $rec->suggestByCategory([
            'category'     => $category,
            'content_type' => $contentType,
            'max_items'    => $maxItems
        ]);
        $summaryContext = $category;
    } else {
        $out = $rec->searchByMetadata([
            'tags'         => $args['tags'] ?? [],
            'channels'     => $args['channels'] ?? [],
            'sources'      => $args['sources'] ?? [],
            'categories'   => $args['categories'] ?? [],
            'content_type' => $contentType,
            'max_items'    => $maxItems
        ]);
        $filterDesc = [];
        if ($hasTags)       $filterDesc[] = 'tags: ' . implode(', ', $args['tags']);
        if ($hasSources)    $filterDesc[] = 'sources: ' . implode(', ', $args['sources']);
        if ($hasCategories) $filterDesc[] = 'categories: ' . implode(', ', $args['categories']);
        $summaryContext = empty($filterDesc) ? 'your request' : implode(', ', $filterDesc);
    }

    $items = [];
    foreach ($out as $it) {
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
    return [
        'summary'     => 'Top picks for ' . $summaryContext . '.',
        'result_type' => 'suggestions',
        'items'       => $items
    ];
}

function tool_build_playlist(Recommender $rec, array $args, int $age, string $lang): array {
    $maxItems = (int)($args['max_items'] ?? 10);

    if (!empty($args['tags']) || !empty($args['channels']) || !empty($args['sources']) || !empty($args['categories'])) {
        $out = $rec->searchByMetadata([
            'tags'         => $args['tags'] ?? [],
            'channels'     => $args['channels'] ?? [],
            'sources'      => $args['sources'] ?? [],
            'categories'   => $args['categories'] ?? [],
            'content_type' => 'both',
            'max_items'    => $maxItems
        ]);
        $pl = ['items' => $out];
        $summaryContext = 'based on your specific criteria';
    } else {
        $category = strtolower((string)($args['category'] ?? 'educational'));
        $pl = $rec->buildPlaylistByCategory([
            'category'  => $category,
            'max_items' => $maxItems
        ]);
        $summaryContext = $category;
    }

    $items = [];
    foreach ($pl['items'] as $it) {
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
    return [
        'summary'     => 'Playlist of ' . $summaryContext . ' content.',
        'result_type' => 'playlist',
        'items'       => $items
    ];
}

function tool_search_by_metadata(Recommender $rec, array $args): array {
    $contentType = $args['content_type'] ?? 'both';
    $maxItems = (int)($args['max_items'] ?? 10);

    $out = $rec->searchByMetadata([
        'tags'         => $args['tags'] ?? [],
        'channels'     => $args['channels'] ?? [],
        'sources'      => $args['sources'] ?? [],
        'categories'   => $args['categories'] ?? [],
        'content_type' => $contentType,
        'max_items'    => $maxItems
    ]);

    $items = [];
    foreach ($out as $it) {
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

    $filterDesc = [];
    if (!empty($args['tags']))     $filterDesc[] = 'tags: ' . implode(', ', $args['tags']);
    if (!empty($args['sources']))  $filterDesc[] = 'sources: ' . implode(', ', $args['sources']);
    if (!empty($args['channels'])) $filterDesc[] = 'channels: ' . implode(', ', $args['channels']);

    return [
        'summary'     => 'Results matching ' . (empty($filterDesc) ? 'your criteria' : implode(', ', $filterDesc)) . '.',
        'result_type' => 'suggestions',
        'items'       => $items
    ];
}
// -------- end helpers --------

// Tools (function declarations) - now with taxonomy + structured search
$tools = [[
    'functionDeclarations' => [
        [
            'name'=>'list_taxonomy',
            'description'=>'Return available sources, categories, subcategories, channels, playlists, and languages with counts.',
            'parameters'=>['type'=>'object','properties'=>[]]
        ],
        [
            'name' => 'search_catalog',
            'description' => 'Return content suggestions. USE CATEGORY parameter for animals, alphabet, numbers, stories. Use tags+sources ONLY for games.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'category'     => ['type'=>'string', 'description' => 'REQUIRED for categories: animals, alphabet, numbers, stories, science, dance.'],
                    'tags'         => ['type'=>'array', 'items'=>['type'=>'string'], 'description' => 'ONLY for games: ["game"].'],
                    'channels'     => ['type'=>'array', 'items'=>['type'=>'string'], 'description' => 'Channel IDs for specific shows/channels.'],
                    'sources'      => ['type'=>'array', 'items'=>['type'=>'string'], 'description' => 'ONLY for games or explicit source selection.'],
                    'categories'   => ['type'=>'array', 'items'=>['type'=>'string']],
                    'content_type' => ['type'=>'string', 'enum' => ['videos','games','both']],
                    'max_items'    => ['type'=>'integer']
                ]
            ]
        ],
        [
            'name' => 'search_catalog_structured',
            'description' => 'Structured search using taxonomy (source/category/subcategory/channel/playlist). Focus on categories mentioned by the user.',
            'parameters' => [
                'type'=>'object',
                'properties'=>[
                    'source'     => ['type'=>'string'],
                    'category'   => ['type'=>'string'],
                    'subcategory'=> ['type'=>'string'],
                    'channel'    => ['type'=>'string'],
                    'playlist'   => ['type'=>'string'],
                    'wants'      => ['type'=>'string','enum'=>['videos','games','both']],
                    'max_items'  => ['type'=>'integer']
                ]
            ]
        ],
        [
            'name' => 'build_playlist',
            'description' => 'Return a playlist. Prefer category for stable matching; tags+sources only for games.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'category'   => ['type'=>'string'],
                    'tags'       => ['type'=>'array', 'items'=>['type'=>'string']],
                    'channels'   => ['type'=>'array', 'items'=>['type'=>'string']],
                    'sources'    => ['type'=>'array', 'items'=>['type'=>'string']],
                    'categories' => ['type'=>'array', 'items'=>['type'=>'string']],
                    'max_items'  => ['type'=>'integer']
                ]
            ]
        ],
        [
            'name' => 'search_by_metadata',
            'description' => 'Search using specific catalog metadata (tags, channels, categories, sources).',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'tags'         => ['type'=>'array', 'items'=>['type'=>'string']],
                    'channels'     => ['type'=>'array', 'items'=>['type'=>'string']],
                    'sources'      => ['type'=>'array', 'items'=>['type'=>'string']],
                    'categories'   => ['type'=>'array', 'items'=>['type'=>'string']],
                    'content_type' => ['type'=>'string', 'enum' => ['videos','games','both']],
                    'max_items'    => ['type'=>'integer']
                ]
            ]
        ]
    ]
]];

$availableTags    = isset($metadata['available_tags']) ? implode(', ', $metadata['available_tags']) : '';
$availableSources = isset($metadata['available_sources']) ? implode(', ', $metadata['available_sources']) : '';

// Build system prompt (taxonomy-first planning)
$system = "You are a helpful family entertainment assistant for kids. You MUST call functions to provide content.\n\nCATALOG STRUCTURE AND MAPPING:";
if ($fullMetadata) {
    $system .= "\n\n=== AVAILABLE SOURCES ===";
    foreach ($fullMetadata['catalog_structure'] ?? [] as $sourceName => $sourceData) {
        $system .= "\n- $sourceName: " . ($sourceData['total_items'] ?? 0) . " items";
        if (!empty($sourceData['key_categories'])) {
            $system .= "\n  Categories: " . implode(', ', $sourceData['key_categories']);
        }
    }
    if (isset($fullMetadata['sources']['kids']['channels'])) {
        $system .= "\n\n=== AVAILABLE CHANNELS (kids source) ===";
        foreach ($fullMetadata['sources']['kids']['channels'] as $channelId => $channelData) {
            $system .= "\n- Channel $channelId: " . ($channelData['name'] ?? $channelId) . " (" . ($channelData['total_items'] ?? 0) . " items)";
            if (!empty($channelData['categories'])) {
                $system .= "\n  Channel categories: " . implode(', ', $channelData['categories']);
            }
        }
    }
    $system .= "\n\n=== COMMON REQUEST EXAMPLES ===";
    $system .= "\n- 'suggest games' → search_catalog(tags=['game'], sources=['games'])";
    $system .= "\n- 'interactive games' → search_catalog(tags=['game'], sources=['games'])";
    $system .= "\n- 'show me animals' → search_catalog(category='animals')";
    $system .= "\n- 'alphabet videos' → search_catalog(category='alphabet')";
    $system .= "\n- 'numbers and math' → search_catalog(category='numbers')";
    $system .= "\n- 'stories' → search_catalog(category='stories')";
    $system .= "\n- 'fitness' → search_catalog(sources=['fitness'])";
} else {
    $system .= "\n- Available tags: $availableTags";
    $system .= "\n- Available sources: $availableSources";
    $system .= "\n- Total items: " . ($metadata['total_items'] ?? 0);
}

$system .= "\n\n=== CRITICAL INSTRUCTIONS ===
1. ALWAYS call list_taxonomy first to learn real sources/categories/subcategories/channels/playlists and languages.
2. ANALYZE the user request and plan with the taxonomy, focusing on the category terms the user mentions.
3. Prefer calling search_catalog_structured with concrete taxonomy keys to guarantee results. Do not filter by age, language, or duration. For time-boxed requests, call build_playlist.
4. For any mention of 'games' or 'interactive games': call search_catalog(tags=['game'], sources=['games']).
5. NEVER say 'I couldn't find'. Always call a function and return items.
6. Respond as compact JSON: {\"summary\",\"result_type\",\"items\"}.";

// Intelligent fallback (kept from your version; calls tool_search_catalog internally)
function intelligentFallback(Recommender $rec, string $message, int $age, string $lang, ?array $fullMetadata): array {
    $messageLower = strtolower(trim($message));

    if (!$fullMetadata || !isset($fullMetadata['ai_mapping_rules']['user_request_patterns'])) {
        return tool_search_catalog($rec, ['tags' => ['educational'], 'sources' => ['kids'], 'max_items' => 10], $age, $lang);
    }

    $mappingRules = $fullMetadata['ai_mapping_rules']['user_request_patterns'];

    if (isset($fullMetadata['catalog_structure']['kids']['key_categories'])) {
        $availableCategories = $fullMetadata['catalog_structure']['kids']['key_categories'];
        foreach ($availableCategories as $category) {
            if (strpos($messageLower, $category) !== false) {
                $result = tool_search_catalog($rec, ['category' => $category, 'max_items' => 10], $age, $lang);
                if (!empty($result['items'])) return $result;
            }
        }
    }

    foreach ($mappingRules as $pattern => $criteria) {
        if (strpos($messageLower, $pattern) !== false) {
            $args = ['max_items' => 10];
            if ($pattern === 'games' || $pattern === 'interactive_games') {
                $args['tags'] = ['game'];
                $args['sources'] = ['games'];
            } else {
                if (!empty($criteria['tags']))    $args['tags'] = $criteria['tags'];
                if (!empty($criteria['sources'])) $args['sources'] = $criteria['sources'];
                if (empty($criteria['tags']) && empty($criteria['sources'])) {
                    $args['category'] = $pattern;
                }
            }
            $result = tool_search_catalog($rec, $args, $age, $lang);
            if (!empty($result['items'])) return $result;
        }
    }

    $edgeCases = [
        'suggest games'     => ['tags' => ['game'], 'sources' => ['games']],
        'games'             => ['tags' => ['game'], 'sources' => ['games']],
        'interactive games' => ['tags' => ['game'], 'sources' => ['games']],
        'any'               => ['tags' => ['educational'], 'sources' => ['kids']],
        'yes'               => ['tags' => ['educational'], 'sources' => ['kids']],
        'more'              => ['tags' => ['educational'], 'sources' => ['kids']],
        'continue'          => ['tags' => ['educational'], 'sources' => ['kids']],
        'video'             => ['tags' => ['educational'], 'sources' => ['kids']],
        'show'              => ['tags' => ['educational'], 'sources' => ['kids']],
        'suggest'           => ['tags' => ['educational'], 'sources' => ['kids']],
        'recommend'         => ['tags' => ['educational'], 'sources' => ['kids']],
    ];
    foreach ($edgeCases as $edgePattern => $edgeArgs) {
        if (strpos($messageLower, $edgePattern) !== false) {
            $edgeArgs['max_items'] = 10;
            $result = tool_search_catalog($rec, $edgeArgs, $age, $lang);
            if (!empty($result['items'])) return $result;
        }
    }

    if (isset($fullMetadata['catalog_structure'])) {
        foreach ($fullMetadata['catalog_structure'] as $sourceName => $sourceData) {
            if (isset($sourceData['key_categories'])) {
                foreach ($sourceData['key_categories'] as $category) {
                    if (strpos($messageLower, $category) !== false) {
                        $args = ['category' => $category, 'max_items' => 10];
                        if ($sourceName !== 'kids') $args['sources'] = [$sourceName];
                        $result = tool_search_catalog($rec, $args, $age, $lang);
                        if (!empty($result['items'])) return $result;
                    }
                }
            }
        }
    }

    $finalResult = tool_search_catalog($rec, ['tags' => ['educational'], 'sources' => ['kids'], 'max_items' => 10], $age, $lang);
    if (empty($finalResult['items'])) {
        try {
            $finalResult = tool_search_catalog($rec, ['max_items' => 10], $age, $lang);
        } catch (Exception $e) {
            $finalResult = ['summary' => 'Content suggestions for you.','result_type' => 'suggestions','items' => []];
        }
    }
    return $finalResult;
}

// Gemini REST helper
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

function execute_tool_call(
    string $name,
    array $args,
    Recommender $rec,
    CatalogIndex $index,
    int $age,
    string $lang,
    string $message,
    ?array $fullMetadata
): array {
    switch ($name) {
        case 'list_taxonomy':
            return tool_list_taxonomy($index);

        case 'search_catalog':
            $result = tool_search_catalog($rec, $args, $age, $lang);
            if (empty($result['items'])) {
                $fallback = intelligentFallback($rec, $message, $age, $lang, $fullMetadata);
                if (!empty($fallback['items'])) {
                    $result = $fallback;
                }
            }
            return $result;

        case 'search_catalog_structured':
            $result = tool_search_catalog_structured($rec, $args);
            if (empty($result['items'])) {
                $result = tool_search_catalog_structured($rec, [
                    'max_items' => 12
                ]);
            }
            return $result;

        case 'build_playlist':
            $result = tool_build_playlist($rec, $args, $age, $lang);
            if (empty($result['items'])) {
                $fallback = intelligentFallback($rec, $message, $age, $lang, $fullMetadata);
                if (!empty($fallback['items'])) {
                    $result = $fallback;
                }
            }
            return $result;

        case 'search_by_metadata':
            $result = tool_search_by_metadata($rec, $args);
            if (empty($result['items'])) {
                $fallback = intelligentFallback($rec, $message, $age, $lang, $fullMetadata);
                if (!empty($fallback['items'])) {
                    $result = $fallback;
                }
            }
            return $result;

        default:
            json_error(400, 'Unknown tool: ' . $name);
    }
}

$messages = [
    ['role' => 'user', 'parts' => [['text' => $system]]],
    ['role' => 'user', 'parts' => [['text' => $message]]]
];

$lastToolResult = null;
$finalResponse = null;

for ($round = 0; $round < 6; $round++) {
    $req = [
        'contents' => $messages,
        'tools' => $tools,
        'generationConfig' => [
            'temperature' => 0,
            'maxOutputTokens' => 1000,
            'response_mime_type' => 'application/json'
        ],
        'toolConfig' => [
            'functionCallingConfig' => [
                'mode' => 'ANY'
            ]
        ]
    ];

    $response = gemini_call($req, $API_KEY, $MODEL);
    $candidate = $response['candidates'][0] ?? null;
    if (!$candidate) {
        break;
    }

    $parts = $candidate['content']['parts'] ?? [];
    $call = null;
    foreach ($parts as $part) {
        if (!empty($part['functionCall'])) {
            $call = $part['functionCall'];
            break;
        }
    }

    if ($call) {
        $name = $call['name'] ?? '';
        $args = $call['args'] ?? [];
        $result = execute_tool_call($name, $args, $rec, $index, $age, $lang, $message, $fullMetadata);
        $lastToolResult = $result;

        $messages[] = ['role' => 'model', 'parts' => [['functionCall' => $call]]];
        $messages[] = [
            'role' => 'tool',
            'parts' => [[
                'functionResponse' => [
                    'name' => $name,
                    'response' => $result
                ]
            ]]
        ];

        // Allow the model to make another decision unless it already produced suggestions
        if (($result['result_type'] ?? '') !== 'taxonomy' && !empty($result['items'])) {
            // Capture as potential final result but continue one more turn for summary if needed
            $finalResponse = $finalResponse ?? $result;
        }

        continue;
    }

    $text = '';
    foreach ($parts as $part) {
        if (isset($part['text'])) {
            $text .= (string)$part['text'];
        }
    }

    if ($text !== '') {
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            $finalResponse = $decoded;
        } elseif (!$finalResponse && $lastToolResult) {
            // If the model replied with plain text, fall back to the last tool output
            $finalResponse = $lastToolResult;
        }
    }

    break;
}

if (!$finalResponse && $lastToolResult && ($lastToolResult['result_type'] ?? '') !== 'taxonomy') {
    $finalResponse = $lastToolResult;
}

if (!$finalResponse) {
    $finalResponse = intelligentFallback($rec, $message, $age, $lang, $fullMetadata);
}

// FINAL SAFETY CHECKS — never return empty items
if (empty($finalResponse['items'])) {
    // Try videos then games, then any
    $fallback = tool_search_catalog_structured($rec, [
        'wants' => 'videos',
        'max_items' => 8
    ]);
    if (empty($fallback['items'])) {
        $fallback = tool_search_catalog_structured($rec, [
            'wants' => 'games',
            'max_items' => 8
        ]);
    }
    if (empty($fallback['items'])) {
        $fallback = tool_search_catalog_structured($rec, [
            'max_items' => 8
        ]);
    }
    if (!empty($fallback['items'])) {
        $finalResponse = $fallback;
    } else {
        // Last resort attempts
        $emergency = tool_search_catalog($rec, ['tags' => ['educational'], 'sources' => ['kids'], 'max_items' => 10], $age, $lang);
        if (!empty($emergency['items'])) {
            $finalResponse = $emergency;
        } else {
            $any = tool_search_catalog($rec, ['max_items' => 10], $age, $lang);
            if (!empty($any['items'])) {
                $finalResponse = $any;
            }
        }
    }
}

// Normalize response structure
if (!isset($finalResponse['items']) || !is_array($finalResponse['items'])) {
    $finalResponse['items'] = [];
}
if (!isset($finalResponse['summary'])) {
    $finalResponse['summary'] = 'Content suggestions for you.';
}
if (!isset($finalResponse['result_type'])) {
    $finalResponse['result_type'] = 'suggestions';
}

// Return plain JSON (no 'text' wrapping)
echo json_encode($finalResponse, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;
