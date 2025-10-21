<?php
declare(strict_types=1);

namespace App;

final class Recommender {
    private array $catalog;
    public function __construct(array $catalog) { $this->catalog = $catalog; }

    /**
     * Get all available categories, channels, and subcategories for AI decision making
     */
    public function getCatalogMetadata(): array {
        $tags = [];
        $categories = [];
        $channels = [];
        $sources = [];
        
        foreach ($this->catalog as $item) {
            // Collect all tags
            foreach ($item['tags'] ?? [] as $tag) {
                $tags[strtolower($tag)] = ($tags[strtolower($tag)] ?? 0) + 1;
            }
            
            // Collect categories
            if (!empty($item['category'])) {
                $categories[strtolower($item['category'])] = ($categories[strtolower($item['category'])] ?? 0) + 1;
            }
            
            // Collect channel IDs
            if (!empty($item['channel_id'])) {
                $channels[(string)$item['channel_id']] = ($channels[(string)$item['channel_id']] ?? 0) + 1;
            }
            
            // Collect sources
            if (!empty($item['source'])) {
                $sources[$item['source']] = ($sources[$item['source']] ?? 0) + 1;
            }
        }
        
        // Get top tags and sort by frequency
        arsort($tags);
        $topTags = array_slice(array_keys($tags), 0, 20);
        
        arsort($categories);
        $topCategories = array_keys($categories);
        
        arsort($channels);
        $topChannels = array_keys($channels);
        
        return [
            'available_tags' => $topTags,
            'available_categories' => $topCategories,
            'available_channels' => $topChannels,
            'available_sources' => array_keys($sources),
            'total_items' => count($this->catalog)
        ];
    }

    /**
     * Normalize category names to match catalog tags
     */
    private function normalizeCategory(string $category): string {
        $category = strtolower(trim($category));
        
        // Map common phrases to actual tags
        $mappings = [
            'interactive games' => 'game',
            'video games' => 'game',
            'games' => 'game',
            'alphabet videos' => 'alphabet',
            'alphabet songs' => 'alphabet',
            'numbers' => 'number',
            'math' => 'math',
            'mathematics' => 'math',
            'animals' => 'animal',
            'music' => 'music',
            'songs' => 'music',
            'stories' => 'story',
            'science' => 'science',
            'fitness' => 'fitness',
            'exercise' => 'fitness',
            'workout' => 'fitness',
            'educational' => 'educational',
            'kids' => 'kids',
        ];
        
        // Check exact match first
        if (isset($mappings[$category])) {
            return $mappings[$category];
        }
        
        // Check if any mapping key is contained in the category
        foreach ($mappings as $key => $value) {
            if (strpos($category, $key) !== false) {
                return $value;
            }
        }
        
        // Return original if no mapping found
        return $category;
    }

    public function suggestForChild(array $prefs): array {
        $age = $prefs['age'] ?? null;
        $lang = strtolower($prefs['language'] ?? '');
        $wants = $prefs['wants'] ?? 'both';
        $mood = strtolower($prefs['mood'] ?? 'learning');
        $max = (int)($prefs['max_items'] ?? 8);

        $filtered = array_filter($this->catalog, function($it) use ($age, $lang, $wants) {
            if ($age !== null && !($it['age_min'] <= $age && $age <= $it['age_max'])) return false;
            if ($lang && $it['language'] !== $lang) return false;
            if ($wants !== 'both' && $it['type'] !== rtrim($wants, 's')) return false;
            // simple guardrail
            if (in_array('adult', $it['tags'] ?? [], true) || in_array('violence', $it['tags'] ?? [], true)) return false;
            return true;
        });

        foreach ($filtered as &$it) {
            $score = 0.0;
            if (!empty($it['is_educational'])) $score += 3.0;
            $tags = $it['tags'] ?? [];
            if ($mood === 'learning' && in_array('educational', $tags, true)) $score += 2.0;
            if ($mood === 'calm' && (in_array('music', $tags, true) || in_array('story', $tags, true))) $score += 1.0;
            if ($mood === 'active' && (in_array('dance', $tags, true) || in_array('exercise', $tags, true) || in_array('game', $tags, true))) $score += 1.5;
            $score += min(2.0, (($it['rating'] ?? 0.0) / 5.0) * 2.0);
            $score += min(2.0, log(1 + (int)($it['popularity'] ?? 0)) / 5.0);
            if ($age !== null && $age <= 6) {
                if (($it['duration_sec'] ?? 0) > 900) $score -= 1.0;
                if (($it['duration_sec'] ?? 0) <= 420) $score += 0.5;
            }
            $text = strtolower(($it['title'] ?? '').' '.($it['description'] ?? '').' '.implode(' ', $tags));
            foreach (['educational','learning','abc','math','shapes','phonics','science'] as $kw) {
                if (strpos($text, $kw) !== false) $score += 0.3;
            }
            $it['_score'] = $score;
        }
        unset($it);

        usort($filtered, function($a,$b){ return $b['_score'] <=> $a['_score']; });
        $slice = array_slice(array_values($filtered), 0, $max);
        foreach ($slice as &$it) { unset($it['_score']); }
        return $slice;
    }

    public function buildEducationalPlaylist(int $age, string $language='en', int $targetMinutes=30): array {
        $target = $targetMinutes * 60;
        $lang = strtolower($language);

        // 1) Primary pool: educational videos, exact language
        $pool = array_filter($this->catalog, function($it) use ($age, $lang) {
            return !empty($it['is_educational'])
                && ($it['type'] ?? '') === 'video'
                && ($it['language'] ?? '') === $lang
                && ($it['age_min'] <= $age && $age <= $it['age_max']);
        });

        // 2) Fallback A: educational videos any language
        if (empty($pool)) {
            $pool = array_filter($this->catalog, function($it) use ($age) {
                return !empty($it['is_educational'])
                    && ($it['type'] ?? '') === 'video'
                    && ($it['age_min'] <= $age && $age <= $it['age_max']);
            });
        }

        // 3) Fallback B: any kid-safe videos any language
        if (empty($pool)) {
            $pool = array_filter($this->catalog, function($it) use ($age) {
                if (($it['type'] ?? '') !== 'video') return false;
                if (in_array('adult', $it['tags'] ?? [], true) || in_array('violence', $it['tags'] ?? [], true)) return false;
                return ($it['age_min'] <= $age && $age <= $it['age_max']);
            });
        }

        // Sort by rating then popularity
        usort($pool, function($a,$b){
            $ra = $a['rating'] ?? 0; $rb = $b['rating'] ?? 0;
            if ($ra === $rb) return ($b['popularity'] ?? 0) <=> ($a['popularity'] ?? 0);
            return $rb <=> $ra;
        });

        // Pack items to target; treat 0 durations as 120s heuristic (to get ~10 items for typical requests)
        $playlist = []; $total = 0; $maxItems = 15;
        foreach ($pool as $it) {
            $dur = (int)($it['duration_sec'] ?? 0);
            if ($dur <= 0) $dur = 120; // 2 minutes default to aim for ~10 items
            if (($total + $dur <= $target || empty($playlist)) && count($playlist) < $maxItems) {
                $playlist[] = $it;
                $total += $dur;
                if ($total >= $target) break;
            }
        }
        return ['total_sec' => $total, 'items' => $playlist];
    }

    /**
     * Suggest content by category/topic
     */
    public function suggestByCategory(array $prefs): array {
        $category = $this->normalizeCategory($prefs['category'] ?? '');
        $lang = strtolower($prefs['language'] ?? '');
        $contentType = $prefs['content_type'] ?? 'both';
        $max = (int)($prefs['max_items'] ?? 10);

        // Filter by content type and category (language-agnostic since content is bilingual)
        $filtered = array_filter($this->catalog, function($it) use ($category, $contentType) {
            // Filter by content type
            if ($contentType !== 'both') {
                $wantType = rtrim($contentType, 's'); // 'videos' -> 'video', 'games' -> 'game'
                if ($it['type'] !== $wantType) return false;
            }
            
            // Safety filters
            if (in_array('adult', $it['tags'] ?? [], true) || in_array('violence', $it['tags'] ?? [], true)) {
                return false;
            }
            
            // Filter by category - search in tags, title, and description
            if ($category && $category !== 'educational' && $category !== 'all') {
                $searchText = strtolower(
                    ($it['title'] ?? '') . ' ' . 
                    ($it['description'] ?? '') . ' ' . 
                    implode(' ', $it['tags'] ?? [])
                );
                
                // Check if category keyword appears in content
                if (strpos($searchText, $category) === false) {
                    return false;
                }
            }
            
            return true;
        });

        // Score and sort results
        foreach ($filtered as &$it) {
            $score = 0.0;
            
            // Educational bonus
            if (!empty($it['is_educational'])) $score += 2.0;
            
            // Rating and popularity
            $score += min(3.0, (($it['rating'] ?? 0.0) / 5.0) * 3.0);
            $score += min(2.0, log(1 + (int)($it['popularity'] ?? 0)) / 5.0);
            
            // Boost if category matches exactly in tags
            if ($category) {
                foreach ($it['tags'] ?? [] as $tag) {
                    if (strpos(strtolower($tag), $category) !== false) {
                        $score += 5.0; // Big boost for exact tag match
                        break;
                    }
                }
                
                // Boost if category in title
                if (strpos(strtolower($it['title'] ?? ''), $category) !== false) {
                    $score += 3.0;
                }
            }
            
            $it['_score'] = $score;
        }
        unset($it);

        usort($filtered, function($a,$b){ return $b['_score'] <=> $a['_score']; });
        $slice = array_slice(array_values($filtered), 0, $max);
        foreach ($slice as &$it) { unset($it['_score']); }
        
        return $slice;
    }

    /**
     * Build playlist by category
     */
    public function buildPlaylistByCategory(array $prefs): array {
        $category = $this->normalizeCategory($prefs['category'] ?? '');
        $lang = strtolower($prefs['language'] ?? '');
        $maxItems = (int)($prefs['max_items'] ?? 10);

        // Filter for videos matching category (language-agnostic since content is bilingual)
        $pool = array_filter($this->catalog, function($it) use ($category) {
            // Must be video
            if (($it['type'] ?? '') !== 'video') return false;
            
            // Safety filters
            if (in_array('adult', $it['tags'] ?? [], true) || in_array('violence', $it['tags'] ?? [], true)) {
                return false;
            }
            
            // Filter by category
            if ($category && $category !== 'educational' && $category !== 'all') {
                $searchText = strtolower(
                    ($it['title'] ?? '') . ' ' . 
                    ($it['description'] ?? '') . ' ' . 
                    implode(' ', $it['tags'] ?? [])
                );
                
                if (strpos($searchText, $category) === false) {
                    return false;
                }
            }
            
            return true;
        });

        // Score results
        foreach ($pool as &$it) {
            $score = 0.0;
            
            // Educational bonus
            if (!empty($it['is_educational'])) $score += 2.0;
            
            // Rating and popularity
            $score += min(3.0, (($it['rating'] ?? 0.0) / 5.0) * 3.0);
            $score += min(2.0, log(1 + (int)($it['popularity'] ?? 0)) / 5.0);
            
            // Category matching
            if ($category) {
                foreach ($it['tags'] ?? [] as $tag) {
                    if (strpos(strtolower($tag), $category) !== false) {
                        $score += 5.0;
                        break;
                    }
                }
                
                if (strpos(strtolower($it['title'] ?? ''), $category) !== false) {
                    $score += 3.0;
                }
            }
            
            $it['_score'] = $score;
        }
        unset($it);

        // Sort and limit
        usort($pool, function($a,$b){ return $b['_score'] <=> $a['_score']; });
        $playlist = array_slice(array_values($pool), 0, $maxItems);
        
        // Clean up scores
        foreach ($playlist as &$it) { unset($it['_score']); }
        
        return ['items' => $playlist, 'count' => count($playlist)];
    }

    /**
     * Search by specific metadata - AI can specify exact tags, channels, or categories
     */
    public function searchByMetadata(array $prefs): array {
        $tags = $prefs['tags'] ?? [];
        $channels = $prefs['channels'] ?? [];
        $categories = $prefs['categories'] ?? [];
        $sources = $prefs['sources'] ?? [];
        $contentType = $prefs['content_type'] ?? 'both';
        $max = (int)($prefs['max_items'] ?? 10);

        // Filter by specified metadata
        $filtered = array_filter($this->catalog, function($item) use ($tags, $channels, $categories, $sources, $contentType) {
            // Filter by content type
            if ($contentType !== 'both') {
                $wantType = rtrim($contentType, 's');
                if ($item['type'] !== $wantType) return false;
            }
            
            // Safety filters
            if (in_array('adult', $item['tags'] ?? [], true) || in_array('violence', $item['tags'] ?? [], true)) {
                return false;
            }
            
            // Filter by tags (must match at least one if specified)
            if (!empty($tags)) {
                $itemTags = array_map('strtolower', $item['tags'] ?? []);
                $hasMatchingTag = false;
                foreach ($tags as $tag) {
                    if (in_array(strtolower($tag), $itemTags)) {
                        $hasMatchingTag = true;
                        break;
                    }
                }
                if (!$hasMatchingTag) return false;
            }
            
            // Filter by channels (must match if specified)
            if (!empty($channels) && !empty($item['channel_id'])) {
                if (!in_array((string)$item['channel_id'], $channels)) {
                    return false;
                }
            }
            
            // Filter by categories (must match if specified)
            if (!empty($categories) && !empty($item['category'])) {
                if (!in_array(strtolower($item['category']), array_map('strtolower', $categories))) {
                    return false;
                }
            }
            
            // Filter by sources (must match if specified)
            if (!empty($sources) && !empty($item['source'])) {
                if (!in_array($item['source'], $sources)) {
                    return false;
                }
            }
            
            return true;
        });

        // Score results
        foreach ($filtered as &$it) {
            $score = 0.0;
            
            // Educational bonus
            if (!empty($it['is_educational'])) $score += 2.0;
            
            // Rating and popularity
            $score += min(3.0, (($it['rating'] ?? 0.0) / 5.0) * 3.0);
            $score += min(2.0, log(1 + (int)($it['popularity'] ?? 0)) / 5.0);
            
            // Boost for exact metadata matches
            if (!empty($tags)) {
                foreach ($tags as $tag) {
                    foreach ($it['tags'] ?? [] as $itemTag) {
                        if (strtolower($itemTag) === strtolower($tag)) {
                            $score += 5.0;
                            break;
                        }
                    }
                }
            }
            
            $it['_score'] = $score;
        }
        unset($it);

        // Sort and limit
        usort($filtered, function($a,$b){ return $b['_score'] <=> $a['_score']; });
        $slice = array_slice(array_values($filtered), 0, $max);
        foreach ($slice as &$it) { unset($it['_score']); }
        
        return $slice;
    }

    public function searchCatalogStructured(array $filters, int $max=12): array {
        $age = $filters['age'] ?? null;
        $lang = strtolower($filters['language'] ?? '');
        $source = strtolower($filters['source'] ?? '');
        $category = strtolower($filters['category'] ?? '');
        $subcategory = strtolower($filters['subcategory'] ?? '');
        $channel = strtolower($filters['channel'] ?? '');
        $playlist = strtolower($filters['playlist'] ?? '');
        $type = $filters['wants'] ?? 'both';
        $type = $type !== 'both' ? rtrim($type, 's') : '';

        $steps = [
            [$source, $category, $subcategory, $channel, $playlist, $lang, $type],
            [$source, $category, '', '', '', $lang, $type],
            [$source, '', '', '', '', $lang, $type],
            ['', '', '', '', '', $lang, $type],
            ['', '', '', '', '', '', $type],
            ['', '', '', '', '', '', ''],
        ];

        $cands = $this->catalog;
        $best = [];
        foreach ($steps as $s) {
            [$S, $C, $SC, $CH, $PL, $L, $T] = $s;
            $filtered = array_filter($cands, function($it) use ($age,$S,$C,$SC,$CH,$PL,$L,$T) {
                if ($age !== null && !($it['age_min'] <= $age && $age <= $it['age_max'])) return false;
                if ($S !== '' && strtolower($it['source'] ?? '') !== $S) return false;
                if ($C !== '' && strtolower($it['category'] ?? '') !== $C) return false;
                if ($SC !== '' && strtolower($it['subcategory'] ?? '') !== $SC) return false;
                if ($CH !== '' && strtolower($it['channel'] ?? '') !== $CH) return false;
                if ($PL !== '' && strtolower($it['playlist'] ?? '') !== $PL) return false;
                if ($L !== '' && strtolower($it['language'] ?? '') !== $L) return false;
                if ($T !== '' && strtolower($it['type'] ?? '') !== $T) return false;
                if (in_array('adult', $it['tags'] ?? [], true) || in_array('violence', $it['tags'] ?? [], true)) return false;
                return true;
            });
            if (!empty($filtered)) { $best = $filtered; break; }
        }

        if (empty($best)) return [];

        foreach ($best as &$it) {
            $score = 0.0;
            if (!empty($it['is_educational'])) $score += 1.5;
            $score += min(2.0, (($it['rating'] ?? 0.0) / 5.0) * 2.0);
            $score += min(2.0, log(1 + (int)($it['popularity'] ?? 0)) / 5.0);
            $it['_score'] = $score;
        } unset($it);

        usort($best, function($a,$b){
            $cmp = ($b['_score'] <=> $a['_score']);
            if ($cmp !== 0) return $cmp;
            return strcmp($a['id'],$b['id']);
        });
        $slice = array_slice(array_values($best), 0, $max*2);
        shuffle($slice);
        $slice = array_slice($slice, 0, $max);
        foreach ($slice as &$it) { unset($it['_score']); }
        return $slice;
    }
}



