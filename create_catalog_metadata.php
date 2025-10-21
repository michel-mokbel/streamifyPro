<?php
declare(strict_types=1);

/**
 * Script to generate comprehensive catalog metadata for AI cross-referencing
 * This creates a JSON file with all source files, categories, subcategories,
 * channels, tags, and other metadata that the AI can use to better understand
 * the catalog structure and make informed decisions.
 */

require_once __DIR__ . '/app/bootstrap.php';

use App\Normalizer;

class CatalogMetadataGenerator {
    private array $jsonFiles;
    private array $metadata = [];
    private array $sources = [];
    private array $categories = [];
    private array $subcategories = [];
    private array $channels = [];
    private array $tags = [];
    private array $contentTypes = [];
    
    public function __construct() {
        $this->jsonFiles = [
            'kids' => __DIR__ . '/api/json/kids-ar.json',
            'games' => __DIR__ . '/api/json/games-ar.json',
            'streaming' => __DIR__ . '/api/json/streaming-ar.json',
            'fitness' => __DIR__ . '/api/json/fitness-ar.json',
        ];
    }
    
    public function generate(): array {
        echo "Analyzing catalog files...\n";
        
        // Analyze each source file
        foreach ($this->jsonFiles as $sourceName => $filePath) {
            if (!file_exists($filePath)) {
                echo "Warning: File not found: $filePath\n";
                continue;
            }
            
            echo "Processing $sourceName...\n";
            $this->analyzeSource($sourceName, $filePath);
        }
        
        // Generate comprehensive metadata
        $this->metadata = [
            'generated_at' => date('Y-m-d H:i:s'),
            'total_sources' => count($this->sources),
            'total_categories' => count($this->categories),
            'total_subcategories' => count($this->subcategories),
            'total_channels' => count($this->channels),
            'total_tags' => count($this->tags),
            'sources' => $this->sources,
            'categories_by_source' => $this->categories,
            'subcategories_by_source' => $this->subcategories,
            'channels_by_source' => $this->channels,
            'tags_frequency' => $this->tags,
            'content_types' => $this->contentTypes,
            'ai_mapping_rules' => $this->generateAIMappingRules(),
            'catalog_structure' => $this->getCatalogStructure(),
        ];
        
        return $this->metadata;
    }
    
    private function analyzeSource(string $sourceName, string $filePath): void {
        $json = json_decode(file_get_contents($filePath), true);
        if (!$json) return;
        
        $sourceInfo = [
            'name' => $sourceName,
            'file' => basename($filePath),
            'file_size' => filesize($filePath),
            'last_modified' => date('Y-m-d H:i:s', filemtime($filePath)),
            'content_type' => $this->determineContentType($json, $sourceName),
            'total_items' => 0,
            'channels' => [],
            'categories' => [],
            'subcategories' => [],
            'common_tags' => []
        ];
        
        // Analyze based on source type
        switch ($sourceName) {
            case 'kids':
                $this->analyzeKidsSource($json, $sourceInfo);
                break;
            case 'games':
                $this->analyzeGamesSource($json, $sourceInfo);
                break;
            case 'streaming':
                $this->analyzeStreamingSource($json, $sourceInfo);
                break;
            case 'fitness':
                $this->analyzeFitnessSource($json, $sourceInfo);
                break;
        }
        
        $this->sources[$sourceName] = $sourceInfo;
    }
    
    private function analyzeKidsSource(array $json, array &$sourceInfo): void {
        if (!isset($json['channels'])) return;
        
        $allTags = [];
        $channelCount = 0;
        $itemCount = 0;
        
        foreach ($json['channels'] as $channel) {
            $channelId = (string)$channel['id'];
            $channelName = $channel['name'] ?? 'Unknown';
            
            $sourceInfo['channels'][$channelId] = [
                'id' => $channelId,
                'name' => $channelName,
                'description' => $channel['description'] ?? '',
                'playlists_count' => count($channel['playlists'] ?? []),
                'total_items' => 0,
                'categories' => []
            ];
            
            $channelCount++;
            
            foreach ($channel['playlists'] ?? [] as $playlist) {
                $playlistName = $playlist['name'] ?? 'Unknown';
                $categoryName = $this->extractCategoryFromPlaylist($playlistName);
                
                if ($categoryName) {
                    $sourceInfo['categories'][$categoryName] = ($sourceInfo['categories'][$categoryName] ?? 0) + 1;
                    $sourceInfo['channels'][$channelId]['categories'][] = $categoryName;
                }
                
                foreach ($playlist['content'] ?? [] as $item) {
                    $itemCount++;
                    $sourceInfo['channels'][$channelId]['total_items']++;
                    
                    // Extract tags from title and description
                    $itemTags = $this->extractTagsFromText([
                        $item['title'] ?? '',
                        $item['description'] ?? '',
                        $playlistName
                    ]);
                    $allTags = array_merge($allTags, $itemTags);
                }
            }
        }
        
        $sourceInfo['total_items'] = $itemCount;
        $sourceInfo['channels_count'] = $channelCount;
        $sourceInfo['common_tags'] = $this->getTopTags($allTags, 20);
    }
    
    private function analyzeGamesSource(array $json, array &$sourceInfo): void {
        if (!isset($json['Content'])) return;
        
        $allTags = [];
        $itemCount = 0;
        
        foreach ($json['Content'] as $gameGroup) {
            $groupName = $gameGroup['Name'] ?? 'Unknown';
            $sourceInfo['categories'][$groupName] = 0;
            
            foreach (['HTML5', 'Flash'] as $type) {
                if (!isset($gameGroup[$type])) continue;
                
                $sourceInfo['content_type'] = $type;
                
                foreach ($gameGroup[$type] as $game) {
                    $games = $game['Content'] ?? [];
                    foreach ($games as $gameItem) {
                        $itemCount++;
                        $sourceInfo['categories'][$groupName]++;
                        
                        // Extract tags and categories
                        $categories = $gameItem['Category'] ?? [];
                        foreach ($categories as $cat) {
                            $sourceInfo['subcategories'][$cat] = ($sourceInfo['subcategories'][$cat] ?? 0) + 1;
                        }
                        
                        $itemTags = $this->extractTagsFromText([
                            $gameItem['Title'] ?? '',
                            $gameItem['Description'] ?? '',
                            $groupName,
                            $game['Name'] ?? ''
                        ]);
                        $allTags = array_merge($allTags, $itemTags);
                    }
                }
            }
        }
        
        $sourceInfo['total_items'] = $itemCount;
        $sourceInfo['common_tags'] = $this->getTopTags($allTags, 20);
    }
    
    private function analyzeStreamingSource(array $json, array &$sourceInfo): void {
        if (!isset($json['Content'])) return;
        
        $allTags = [];
        $itemCount = 0;
        
        foreach ($json['Content'] as $categoryGroup) {
            foreach (['Videos', 'Movies', 'Shows'] as $type) {
                if (!isset($categoryGroup[$type])) continue;
                
                $sourceInfo['content_type'] = $type;
                
                foreach ($categoryGroup[$type] as $category) {
                    $categoryName = $category['Name'] ?? 'Unknown';
                    $sourceInfo['categories'][$categoryName] = 0;
                    
                    $videos = $category['Content'] ?? [];
                    foreach ($videos as $video) {
                        $itemCount++;
                        $sourceInfo['categories'][$categoryName]++;
                        
                        $itemTags = $this->extractTagsFromText([
                            $video['Title'] ?? '',
                            $video['Description'] ?? '',
                            $categoryName
                        ]);
                        $allTags = array_merge($allTags, $itemTags);
                    }
                }
            }
        }
        
        $sourceInfo['total_items'] = $itemCount;
        $sourceInfo['common_tags'] = $this->getTopTags($allTags, 20);
    }
    
    private function analyzeFitnessSource(array $json, array &$sourceInfo): void {
        if (!isset($json['videos'])) return;
        
        $allTags = [];
        $itemCount = 0;
        $categories = [];
        
        foreach ($json['videos'] as $video) {
            $itemCount++;
            
            $categoryEn = $video['category_en'] ?? 'Unknown';
            $categoryAr = $video['category_ar'] ?? '';
            
            $sourceInfo['categories'][$categoryEn] = ($sourceInfo['categories'][$categoryEn] ?? 0) + 1;
            
            if ($categoryAr) {
                $sourceInfo['subcategories'][$categoryAr] = ($sourceInfo['subcategories'][$categoryAr] ?? 0) + 1;
            }
            
            $itemTags = $this->extractTagsFromText([
                $video['name'] ?? '',
                $video['description'] ?? '',
                $video['tips'] ?? '',
                $categoryEn
            ]);
            $allTags = array_merge($allTags, $itemTags);
        }
        
        $sourceInfo['total_items'] = $itemCount;
        $sourceInfo['common_tags'] = $this->getTopTags($allTags, 20);
    }
    
    private function extractCategoryFromPlaylist(string $playlistName): ?string {
        $categories = [
            'alphabet' => ['alphabet', 'letter', 'letters'],
            'numbers' => ['number', 'numbers', 'counting', 'math'],
            'colors' => ['color', 'colors', 'colour'],
            'shapes' => ['shape', 'shapes'],
            'animals' => ['animal', 'animals', 'pet', 'pets'],
            'science' => ['science', 'experiment', 'discovery'],
            'stories' => ['story', 'stories', 'tale', 'tales'],
            'songs' => ['song', 'songs', 'music', 'sing'],
            'dance' => ['dance', 'dancing', 'move'],
        ];
        
        $playlistLower = strtolower($playlistName);
        
        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($playlistLower, $keyword) !== false) {
                    return $category;
                }
            }
        }
        
        return null;
    }
    
    private function extractTagsFromText(array $texts): array {
        $tags = [];
        $commonWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'a', 'an'];
        
        foreach ($texts as $text) {
            $words = preg_split('/\s+/', strtolower(trim($text)));
            foreach ($words as $word) {
                $word = preg_replace('/[^a-z0-9]/', '', $word);
                if (strlen($word) > 2 && !in_array($word, $commonWords)) {
                    $tags[] = $word;
                }
            }
        }
        
        return $tags;
    }
    
    private function getTopTags(array $tags, int $limit): array {
        $counts = array_count_values($tags);
        arsort($counts);
        return array_slice($counts, 0, $limit, true);
    }
    
    private function determineContentType(array $json, string $sourceName): string {
        $typeMap = [
            'kids' => 'educational_videos',
            'games' => 'interactive_games',
            'streaming' => 'streaming_content',
            'fitness' => 'fitness_videos'
        ];
        
        return $typeMap[$sourceName] ?? 'unknown';
    }
    
    private function generateAIMappingRules(): array {
        return [
            'user_request_patterns' => [
                'games' => ['tags' => ['game'], 'sources' => ['games']],
                'interactive_games' => ['tags' => ['game'], 'sources' => ['games']],
                'alphabet' => ['tags' => ['alphabet', 'letter', 'educational'], 'sources' => ['kids']],
                'numbers' => ['tags' => ['number', 'counting', 'math'], 'sources' => ['kids']],
                'animals' => ['tags' => ['animal', 'nature'], 'sources' => ['kids']],
                'songs' => ['tags' => ['song', 'music'], 'sources' => ['kids']],
                'stories' => ['tags' => ['story', 'tale'], 'sources' => ['kids']],
                'fitness' => ['tags' => ['fitness', 'exercise'], 'sources' => ['fitness']],
            ],
            'recommended_search_strategy' => [
                'for_games' => 'Use tags=["game"] AND sources=["games"]',
                'for_educational' => 'Use sources=["kids"] with appropriate tags',
                'for_specific_channels' => 'Use channel_id array with specific IDs',
                'for_fitness' => 'Use sources=["fitness"]',
            ]
        ];
    }
    
    private function getCatalogStructure(): array {
        $structure = [];
        
        foreach ($this->sources as $sourceName => $sourceInfo) {
            $structure[$sourceName] = [
                'content_type' => $sourceInfo['content_type'],
                'total_items' => $sourceInfo['total_items'],
                'key_categories' => array_keys(array_slice($sourceInfo['categories'] ?? [], 0, 10, true)),
                'available_channels' => array_keys($sourceInfo['channels'] ?? []),
                'common_tags' => array_keys(array_slice($sourceInfo['common_tags'] ?? [], 0, 15, true))
            ];
        }
        
        return $structure;
    }
}

// Generate the metadata
echo "Creating catalog metadata...\n";
$generator = new CatalogMetadataGenerator();
$metadata = $generator->generate();

// Save to file
$outputFile = __DIR__ . '/api/json/catalog_metadata.json';
file_put_contents($outputFile, json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Metadata saved to: $outputFile\n";
echo "Total sources analyzed: " . count($metadata['sources']) . "\n";
echo "Total categories found: " . array_sum(array_map('count', $metadata['categories_by_source'])) . "\n";

// Display summary
foreach ($metadata['sources'] as $sourceName => $sourceInfo) {
    echo "\n$sourceName:\n";
    echo "  - Items: " . $sourceInfo['total_items'] . "\n";
    echo "  - Categories: " . count($sourceInfo['categories'] ?? []) . "\n";
    echo "  - Channels: " . count($sourceInfo['channels'] ?? []) . "\n";
}
