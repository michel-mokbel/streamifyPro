<?php
declare(strict_types=1);

namespace App;

/**
 * Lightweight catalog indexer for category-based content discovery
 */
class CatalogIndex {
    private array $catalog;
    private array $index = [];
    private array $categories = [];
    private array $categoryStats = [];
    
    public function __construct(array $catalog) {
        $this->catalog = $catalog;
        $this->buildIndex();
    }
    
    /**
     * Build the category index from catalog
     */
    private function buildIndex(): void {
        // Initialize structures
        $this->index = [];
        $this->categories = [];
        $this->categoryStats = [];
        
        // Process each item
        foreach ($this->catalog as $item) {
            $itemId = $item['id'] ?? '';
            if (!$itemId) continue;
            
            // Get item's category (normalized)
            $category = $this->normalizeCategory($item['category'] ?? '');
            if (!$category) $category = 'educational'; // default
            
            // Track unique categories
            if (!in_array($category, $this->categories)) {
                $this->categories[] = $category;
            }
            
            // Initialize category structure if needed
            if (!isset($this->index[$category])) {
                $this->index[$category] = [
                    'items' => [],
                    'subcategories' => [],
                    'channels' => [],
                    'playlists' => []
                ];
                $this->categoryStats[$category] = [
                    'total_items' => 0,
                    'by_source' => [],
                    'by_channel' => [],
                    'by_playlist' => []
                ];
            }
            
            // Add item to category
            $this->index[$category]['items'][] = $itemId;
            $this->categoryStats[$category]['total_items']++;
            
            // Track source
            $source = $item['source'] ?? 'unknown';
            if (!isset($this->categoryStats[$category]['by_source'][$source])) {
                $this->categoryStats[$category]['by_source'][$source] = 0;
            }
            $this->categoryStats[$category]['by_source'][$source]++;
            
            // Track subcategory (if any)
            $subcategory = $item['subcategory'] ?? '';
            if ($subcategory) {
                if (!in_array($subcategory, $this->index[$category]['subcategories'])) {
                    $this->index[$category]['subcategories'][] = $subcategory;
                }
            }
            
            // Track channel (for kids content)
            $channelId = $item['channel_id'] ?? '';
            if ($channelId) {
                if (!isset($this->index[$category]['channels'][$channelId])) {
                    $this->index[$category]['channels'][$channelId] = [
                        'name' => $item['channel_name'] ?? "Channel $channelId",
                        'items' => []
                    ];
                    $this->categoryStats[$category]['by_channel'][$channelId] = 0;
                }
                $this->index[$category]['channels'][$channelId]['items'][] = $itemId;
                $this->categoryStats[$category]['by_channel'][$channelId]++;
            }
            
            // Track playlist (for kids content)
            $playlistId = $item['playlist_id'] ?? '';
            if ($playlistId && $channelId) {
                $playlistKey = "$channelId:$playlistId";
                if (!isset($this->index[$category]['playlists'][$playlistKey])) {
                    $this->index[$category]['playlists'][$playlistKey] = [
                        'name' => $item['playlist_name'] ?? "Playlist $playlistId",
                        'channel_id' => $channelId,
                        'items' => []
                    ];
                    $this->categoryStats[$category]['by_playlist'][$playlistKey] = 0;
                }
                $this->index[$category]['playlists'][$playlistKey]['items'][] = $itemId;
                $this->categoryStats[$category]['by_playlist'][$playlistKey]++;
            }
        }
        
        // Sort categories
        sort($this->categories);
    }
    
    /**
     * Normalize category names
     */
    private function normalizeCategory(string $category): string {
        $category = strtolower(trim($category));
        
        // Map variations to canonical names
        $mappings = [
            'game' => 'games',
            'interactive games' => 'games',
            'video games' => 'games',
            'alphabet videos' => 'alphabet',
            'alphabet songs' => 'alphabet',
            'letters' => 'alphabet',
            'phonics' => 'alphabet',
            'number' => 'numbers',
            'counting' => 'numbers',
            'math' => 'numbers',
            'mathematics' => 'numbers',
            'animal' => 'animals',
            'wildlife' => 'animals',
            'nature' => 'animals',
            'story' => 'stories',
            'bedtime stories' => 'stories',
            'tales' => 'stories',
            'music' => 'music',
            'songs' => 'music',
            'rhymes' => 'music',
            'fitness' => 'fitness',
            'exercise' => 'fitness',
            'workout' => 'fitness',
            'sports' => 'fitness',
            'educational' => 'educational',
            'learning' => 'educational',
            'kids' => 'educational',
            'cartoon' => 'cartoons',
            'animation' => 'cartoons'
        ];
        
        return $mappings[$category] ?? $category;
    }
    
    /**
     * Get all available categories
     */
    public function getCategories(): array {
        return $this->categories;
    }
    
    /**
     * Get items for a category
     */
    public function getCategoryItems(string $category): array {
        $category = $this->normalizeCategory($category);
        return $this->index[$category]['items'] ?? [];
    }
    
    /**
     * Get category data including channels and playlists
     */
    public function getCategoryData(string $category): array {
        $category = $this->normalizeCategory($category);
        return $this->index[$category] ?? [
            'items' => [],
            'subcategories' => [],
            'channels' => [],
            'playlists' => []
        ];
    }
    
    /**
     * Get category statistics
     */
    public function getCategoryStats(string $category): array {
        $category = $this->normalizeCategory($category);
        return $this->categoryStats[$category] ?? [
            'total_items' => 0,
            'by_source' => [],
            'by_channel' => [],
            'by_playlist' => []
        ];
    }
    
    /**
     * Get all statistics
     */
    public function getAllStats(): array {
        return [
            'total_categories' => count($this->categories),
            'categories' => $this->categories,
            'category_stats' => $this->categoryStats
        ];
    }
    
    /**
     * Get items by ID
     */
    public function getItemsById(array $ids): array {
        $items = [];
        foreach ($this->catalog as $item) {
            if (in_array($item['id'] ?? '', $ids)) {
                $items[] = $item;
            }
        }
        return $items;
    }
    
    /**
     * Sample items from categories with channel/playlist coverage
     */
    public function sampleItems(array $categories, int $maxItems, ?int $seed = null): array {
        if ($seed !== null) {
            mt_srand($seed);
        }
        
        $selectedIds = [];
        $categoryPools = [];
        
        // Build pools for each category
        foreach ($categories as $category) {
            $data = $this->getCategoryData($category);
            if (empty($data['items'])) continue;
            
            $categoryPools[$category] = [
                'items' => $data['items'],
                'channels' => $data['channels'],
                'playlists' => $data['playlists'],
                'selected' => []
            ];
        }
        
        if (empty($categoryPools)) {
            return [];
        }
        
        // Calculate target items per category
        $numCategories = count($categoryPools);
        $itemsPerCategory = intval($maxItems / $numCategories);
        $remainder = $maxItems % $numCategories;
        
        // Sample from each category
        foreach ($categoryPools as $category => &$pool) {
            $targetCount = $itemsPerCategory + ($remainder-- > 0 ? 1 : 0);
            
            // Strategy: prefer sampling from channels/playlists for variety
            if (!empty($pool['channels'])) {
                // Build weighted channel list
                $channelList = [];
                foreach ($pool['channels'] as $channelId => $channelData) {
                    $weight = count($channelData['items']);
                    // Add channel ID multiple times based on weight
                    for ($i = 0; $i < $weight; $i++) {
                        $channelList[] = $channelId;
                    }
                }
                
                while (count($pool['selected']) < $targetCount && !empty($channelList)) {
                    // Pick a random channel from weighted list
                    $pick = $channelList[array_rand($channelList)];

                    // If channel no longer exists in pool, drop it and continue
                    if (!isset($pool['channels'][$pick]) || empty($pool['channels'][$pick]['items'])) {
                        $channelList = array_values(array_filter($channelList, fn($ch) => $ch !== $pick));
                        continue;
                    }

                    $channelItems = $pool['channels'][$pick]['items'];
                    
                    // Sample items from this channel
                    $available = array_diff($channelItems, $pool['selected']);
                    if (!empty($available)) {
                        $item = $available[array_rand($available)];
                        $pool['selected'][] = $item;
                    } else {
                        // Remove all instances of this exhausted channel
                        $channelList = array_values(array_filter($channelList, fn($ch) => $ch !== $pick));
                    }
                }
            }
            
            // Fill remaining from general pool
            while (count($pool['selected']) < $targetCount) {
                $available = array_diff($pool['items'], $pool['selected']);
                if (empty($available)) break;
                
                $pick = $available[array_rand($available)];
                $pool['selected'][] = $pick;
            }
            
            $selectedIds = array_merge($selectedIds, $pool['selected']);
        }
        
        // Backfill if needed
        while (count($selectedIds) < $maxItems) {
            $added = false;
            foreach ($categoryPools as &$pool) {
                $available = array_diff($pool['items'], $selectedIds);
                if (!empty($available)) {
                    $pick = $available[array_rand($available)];
                    $selectedIds[] = $pick;
                    $added = true;
                    break;
                }
            }
            if (!$added) break;
        }
        
        // Shuffle final list
        shuffle($selectedIds);
        
        // Return actual items
        return $this->getItemsById(array_slice($selectedIds, 0, $maxItems));
    }
    
}