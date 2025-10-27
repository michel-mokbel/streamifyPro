<?php
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/app/bootstrap.php';
    $catalog = App\load_catalog_default();
    $index = new App\CatalogIndex($catalog);
    
    echo "Categories found: " . count($index->getCategories()) . "\n";
    echo "First 10 categories: " . implode(', ', array_slice($index->getCategories(), 0, 10)) . "\n\n";
    
    // Test alphabet category
    $alphabetItems = $index->getCategoryItems('alphabet');
    echo "Alphabet category has " . count($alphabetItems) . " items\n";
    
    // Test sampling
    echo "\nTesting simple sampling...\n";
    $sample = $index->sampleItems(['alphabet'], 3);
    echo "Sampled " . count($sample) . " items\n";
    
    if (count($sample) > 0) {
        echo "First item:\n";
        echo "- Title: " . $sample[0]['title'] . "\n";
        echo "- Category: " . $sample[0]['category'] . "\n";
        echo "- Source: " . $sample[0]['source'] . "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}
