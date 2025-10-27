<?php
declare(strict_types=1);

use App\CatalogIndex;
use function App\load_catalog_default;

require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

// Load catalog and build index
$catalog = load_catalog_default();
$index = new CatalogIndex($catalog);

// Get all categories and stats
$allStats = $index->getAllStats();
$categories = $index->getCategories();

// Build detailed breakdown
$breakdown = [];
foreach ($categories as $category) {
    $data = $index->getCategoryData($category);
    $stats = $index->getCategoryStats($category);
    
    // Get top channels
    $topChannels = [];
    arsort($stats['by_channel']);
    foreach (array_slice($stats['by_channel'], 0, 5, true) as $channelId => $count) {
        $channelInfo = $data['channels'][$channelId] ?? null;
        $topChannels[] = [
            'id' => $channelId,
            'name' => $channelInfo['name'] ?? "Channel $channelId",
            'item_count' => $count
        ];
    }
    
    // Get top playlists  
    $topPlaylists = [];
    arsort($stats['by_playlist']);
    foreach (array_slice($stats['by_playlist'], 0, 5, true) as $playlistKey => $count) {
        $playlistInfo = $data['playlists'][$playlistKey] ?? null;
        $topPlaylists[] = [
            'key' => $playlistKey,
            'name' => $playlistInfo['name'] ?? "Playlist",
            'item_count' => $count
        ];
    }
    
    $breakdown[$category] = [
        'total_items' => $stats['total_items'],
        'by_source' => $stats['by_source'],
        'unique_channels' => count($data['channels']),
        'unique_playlists' => count($data['playlists']),
        'unique_subcategories' => count($data['subcategories']),
        'top_channels' => $topChannels,
        'top_playlists' => $topPlaylists
    ];
}

// Build response
$response = [
    'index_status' => 'healthy',
    'total_categories' => count($categories),
    'categories' => $categories,
    'category_breakdown' => $breakdown,
    'generated_at' => gmdate('c')
];

echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
