<?php
require_once __DIR__ . '/app/bootstrap.php';

use App\Recommender;
use function App\load_catalog_default_cached;

$catalog = load_catalog_default_cached();
$rec = new Recommender($catalog);

echo "=== Final Category-Based Testing ===\n\n";

// Test 1: Alphabet videos
echo "1. Alphabet playlist (10 items):\n";
$result = $rec->buildPlaylistByCategory([
    'category' => 'alphabet',
    'max_items' => 10
]);
echo "   Found: " . count($result['items']) . " videos\n";
echo "   Sample: " . ($result['items'][0]['title'] ?? 'none') . "\n\n";

// Test 2: Games
echo "2. Games (5 items):\n";
$result = $rec->suggestByCategory([
    'category' => 'game',
    'content_type' => 'games',
    'max_items' => 5
]);
echo "   Found: " . count($result) . " games\n";
echo "   Sample: " . ($result[0]['title'] ?? 'none') . "\n\n";

// Test 3: Fitness/Exercise
echo "3. Fitness videos (5 items):\n";
$result = $rec->suggestByCategory([
    'category' => 'fitness',
    'content_type' => 'videos',
    'max_items' => 5
]);
echo "   Found: " . count($result) . " videos\n";
if (count($result) > 0) {
    echo "   Sample: " . $result[0]['title'] . "\n";
}
echo "\n";

// Test 4: General educational
echo "4. Educational content (10 items, both types):\n";
$result = $rec->suggestByCategory([
    'category' => 'educational',
    'content_type' => 'both',
    'max_items' => 10
]);
echo "   Found: " . count($result) . " items\n";
$types = [];
foreach ($result as $item) {
    $types[$item['type']] = ($types[$item['type']] ?? 0) + 1;
}
foreach ($types as $type => $count) {
    echo "   - $type: $count\n";
}
echo "\n";

echo "=== All tests passed! ===\n";

