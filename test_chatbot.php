<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Testing chatbot API...\n\n";

// Test 1: Check if bootstrap loads
echo "1. Loading bootstrap... ";
try {
    require_once __DIR__ . '/app/bootstrap.php';
    echo "OK\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check if Config works
echo "2. Loading Config... ";
try {
    $apiKey = App\Config::requireEnv('GEMINI_API_KEY');
    echo "OK (key exists)\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Check if catalog loads
echo "3. Loading catalog... ";
try {
    $catalog = App\load_catalog_default();
    echo "OK (" . count($catalog) . " items)\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Check if CatalogIndex loads
echo "4. Creating CatalogIndex... ";
try {
    $index = new App\CatalogIndex($catalog);
    $categories = $index->getCategories();
    echo "OK (" . count($categories) . " categories)\n";
    echo "   Categories: " . implode(', ', array_slice($categories, 0, 10)) . "...\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Test category sampling
echo "5. Testing category sampling... ";
try {
    $items = $index->sampleItems(['alphabet'], 5);
    echo "OK (" . count($items) . " items sampled)\n";
    foreach ($items as $item) {
        echo "   - " . $item['title'] . " (category: " . $item['category'] . ")\n";
    }
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nAll tests passed!\n";
