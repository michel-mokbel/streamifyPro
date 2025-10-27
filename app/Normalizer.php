<?php
declare(strict_types=1);

namespace App;

final class Normalizer {
    public static function loadCatalog(array $paths): array {
        $items = [];
        foreach ($paths as $path) {
            if (!is_file($path)) continue;
            $json = json_decode(file_get_contents($path), true);
            if (!$json) continue;
            $name = strtolower(basename($path));
            if (strpos($name, 'kids') !== false) {
                $items = array_merge($items, self::fromKids($json, $name));
            } elseif (strpos($name, 'games') !== false) {
                $items = array_merge($items, self::fromGames($json, $name));
            } elseif (strpos($name, 'streaming') !== false) {
                $items = array_merge($items, self::fromStreaming($json, $name));
            } elseif (strpos($name, 'fitness') !== false) {
                $items = array_merge($items, self::fromFitness($json, $name));
            }
        }
        return $items;
    }

    private static function baseLang(string $filename): string {
        return (strpos($filename, '-ar') !== false) ? 'ar' : 'en';
    }

    private static function sanitizeTags(array $tags): array {
        $out = [];
        foreach ($tags as $t) {
            $tt = strtolower(trim((string)$t));
            if ($tt !== '' && !in_array($tt, $out, true)) $out[] = $tt;
        }
        return $out;
    }

    private static function absolutize(?string $url): ?string {
        if (!$url) return null;
        $u = trim((string)$url);
        if ($u === '') return null;
        if (preg_match('#^https?://#i', $u)) return $u;
        if ($u[0] !== '/') $u = '/' . $u;
        return $u;
    }

    private static function pickThumb(string $source, array $candidates, string $fallback): string {
        foreach ($candidates as $c) {
            $val = is_string($c) ? $c : (is_null($c) ? null : (string)$c);
            if ($val && trim($val) !== '') {
                return self::absolutize($val) ?? $fallback;
            }
        }
        return $fallback;
    }

    private static function mk(array $r): array {
        $tags = self::sanitizeTags($r['tags'] ?? []);
        $category = $r['category'] ?? self::deriveCategory($tags, $r['title'] ?? '', $r['source'] ?? '');
        
        return [
            'id' => (string)($r['id'] ?? uniqid('item_', true)),
            'type' => strtolower((string)($r['type'] ?? 'video')),
            'source' => (string)($r['source'] ?? 'unknown'),
            'title' => (string)($r['title'] ?? ''),
            'description' => (string)($r['description'] ?? ''),
            'tags' => $tags,
            'age_min' => (int)($r['age_min'] ?? 0),
            'age_max' => (int)($r['age_max'] ?? 99),
            'language' => strtolower((string)($r['language'] ?? 'en')),
            'duration_sec' => (int)($r['duration_sec'] ?? 0),
            'is_educational' => (bool)($r['is_educational'] ?? false),
            'thumbnail' => $r['thumbnail'] ?? null,
            'rating' => (float)($r['rating'] ?? 0.0),
            'popularity' => (int)($r['popularity'] ?? 0),
            'content_url' => $r['content_url'] ?? null,
            'channel_id' => $r['channel_id'] ?? null,
            'playlist_id' => $r['playlist_id'] ?? null,
            'subcategory' => $r['subcategory'] ?? null,
            'category' => $category,
        ];
    }
    
    private static function deriveCategory(array $tags, string $title, string $source): string {
        $titleLower = strtolower($title);
        $tagsLower = array_map('strtolower', $tags);
        
        // Check tags and title for category keywords
        $checks = array_merge($tagsLower, [$titleLower]);
        
        // Category detection rules
        if (self::containsAny($checks, ['alphabet', 'letter', 'abc', 'phonics', 'حروف', 'أبجدية'])) {
            return 'alphabet';
        }
        if (self::containsAny($checks, ['animal', 'zoo', 'wildlife', 'حيوان'])) {
            return 'animals';
        }
        if (self::containsAny($checks, ['number', 'count', 'math', 'أرقام', 'رياضيات'])) {
            return 'numbers';
        }
        if (self::containsAny($checks, ['story', 'stories', 'tale', 'bedtime', 'قصص', 'حكاية'])) {
            return 'stories';
        }
        if (self::containsAny($checks, ['science', 'experiment', 'discover', 'علوم', 'تجربة'])) {
            return 'science';
        }
        if (self::containsAny($checks, ['dance', 'dancing', 'movement', 'رقص'])) {
            return 'dance';
        }
        // Be strict for games to avoid misclassifying kids videos that mention "play"
        if ($source === 'games' || in_array('game', $tagsLower, true) || in_array('games', $tagsLower, true)) {
            return 'games';
        }
        if (self::containsAny($checks, ['fitness', 'exercise', 'workout', 'sport', 'تمرين', 'رياضة']) || $source === 'fitness') {
            return 'fitness';
        }
        if (self::containsAny($checks, ['music', 'song', 'sing', 'rhyme', 'أغنية', 'موسيقى'])) {
            return 'music';
        }
        if (self::containsAny($checks, ['cartoon', 'animation', 'animated', 'رسوم', 'كرتون'])) {
            return 'cartoons';
        }
        
        // Default category
        return 'educational';
    }
    
    private static function containsAny(array $haystack, array $needles): bool {
        foreach ($haystack as $str) {
            foreach ($needles as $needle) {
                if (strpos($str, $needle) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function fromKids(array $json, string $fname): array {
        $lang = self::baseLang($fname);
        $out = [];
        $channels = $json['channels'] ?? [];
        foreach ($channels as $ch) {
            $channelId = $ch['id'] ?? null;
            $playlists = $ch['playlists'] ?? [];
            foreach ($playlists as $pl) {
                $playlistId = $pl['id'] ?? null;
                $videos = $pl['content'] ?? $pl['videos'] ?? $pl['Videos'] ?? [];
                foreach ($videos as $v) {
                    $title = $v['title'] ?? $v['Title'] ?? '';
                    $desc = $v['description'] ?? $v['Description'] ?? '';
                    $duration = (int)($v['Duration'] ?? ($v['duration'] ?? 120)); // 2 minutes default for kids videos
                    $thumb = self::pickThumb('kids', [
                        $v['imageCropped'] ?? null,
                        $v['imageFile'] ?? null,
                        $v['Thumbnail'] ?? null,
                        $v['thumb'] ?? null,
                        $v['logo'] ?? null,
                    ], '/assets/img/placeholders/kids-480x270.png');
                    $src = $v['sourceFile'] ?? ($v['Url'] ?? ($v['URL'] ?? null));
                    // Extract tags from channel/playlist/title
                    $tags = ['kids','educational'];
                    $contextStr = strtolower($ch['name'] ?? '') . ' ' . strtolower($pl['name'] ?? '') . ' ' . strtolower($title);
                    
                    // Add specific tags based on context
                    if (strpos($contextStr, 'alphabet') !== false || strpos($contextStr, 'letter') !== false) {
                        $tags[] = 'alphabet';
                    }
                    if (strpos($contextStr, 'number') !== false || strpos($contextStr, 'count') !== false) {
                        $tags[] = 'numbers';
                    }
                    if (strpos($contextStr, 'animal') !== false) {
                        $tags[] = 'animals';
                    }
                    if (strpos($contextStr, 'story') !== false || strpos($contextStr, 'stories') !== false) {
                        $tags[] = 'stories';
                    }
                    
                    $out[] = self::mk([
                        'id' => (string)($v['id'] ?? $v['ID'] ?? uniqid('kids_', true)),
                        'type' => 'video',
                        'source' => 'kids',
                        'title' => $title,
                        'description' => $desc,
                        'tags' => $tags,
                        'age_min' => 3,
                        'age_max' => 8,
                        'language' => $lang,
                        'duration_sec' => $duration,
                        'is_educational' => true,
                        'thumbnail' => $thumb,
                        'rating' => (float)($v['avrate'] ?? 0),
                        'popularity' => (int)($v['ownrate'] ?? 0),
                        'content_url' => $src,
                        'channel_id' => $channelId,
                        'playlist_id' => $playlistId,
                        'channel_name' => $ch['name'] ?? null,
                        'playlist_name' => $pl['name'] ?? null,
                    ]);
                }
            }
        }
        return $out;
    }

    private static function fromGames(array $json, string $fname): array {
        $lang = self::baseLang($fname);
        $out = [];
        $groups = $json['Content'] ?? [];
        foreach ($groups as $g) {
            $html5 = $g['HTML5'] ?? [];
            foreach ($html5 as $cat) {
                $catName = strtolower((string)($cat['Name'] ?? 'games'));
                $items = $cat['Content'] ?? [];
                foreach ($items as $it) {
                    $title = $it['Title'] ?? ($it['Package_id'] ?? 'Game');
                    $desc = $it['Description'] ?? '';
                    $thumb = self::pickThumb('games', [
                        $it['Thumbnail_Large'] ?? null,
                        $it['Thumb'] ?? null,
                        $it['Logo'] ?? null,
                        $it['logo'] ?? null,
                        $it['Image'] ?? null,
                        $it['image'] ?? null,
                    ], 'https://via.placeholder.com/480x270?text=Game');
                    $src = $it['Url'] ?? ($it['URL'] ?? null);
                    $tags = ['game', $catName];
                    $out[] = self::mk([
                        'id' => (string)($it['ID'] ?? uniqid('game_', true)),
                        'type' => 'game',
                        'source' => 'games',
                        'title' => $title,
                        'description' => $desc,
                        'tags' => $tags,
                        'age_min' => 5,
                        'age_max' => 12,
                        'language' => $lang,
                        'duration_sec' => 0,
                        'is_educational' => false,
                        'thumbnail' => $thumb,
                        'rating' => (float)($it['avrate'] ?? 0),
                        'popularity' => (int)($it['PlayCount'] ?? 0),
                        'content_url' => $src,
                        'category' => 'games',
                        'subcategory' => $catName,
                    ]);
                }
            }
        }
        return $out;
    }

    private static function fromStreaming(array $json, string $fname): array {
        $lang = self::baseLang($fname);
        $out = [];
        $groups = $json['Content'] ?? [];
        foreach ($groups as $g) {
            $videosGroups = $g['Videos'] ?? [];
            foreach ($videosGroups as $vg) {
                $catName = $vg['Name'] ?? 'streaming';
                $items = $vg['Content'] ?? [];
                foreach ($items as $it) {
                    $title = $it['Title'] ?? 'Video';
                    $desc = $it['Description'] ?? '';
                    $thumb = self::pickThumb('streaming', [
                        $it['Thumbnail'] ?? null,
                        $it['Image'] ?? null,
                        $it['Poster'] ?? null,
                        $it['Thumb'] ?? null,
                    ], '/assets/img/placeholders/video-480x270.png');
                    $duration = (int)($it['Duration'] ?? 0);
                    $src = $it['Url'] ?? ($it['URL'] ?? null);
                    $tags = ['video', $catName];
                    $out[] = self::mk([
                        'id' => (string)($it['ID'] ?? uniqid('vid_', true)),
                        'type' => 'video',
                        'source' => 'streaming',
                        'title' => $title,
                        'description' => $desc,
                        'tags' => $tags,
                        'age_min' => 10,
                        'age_max' => 99,
                        'language' => $lang,
                        'duration_sec' => $duration,
                        'is_educational' => false,
                        'thumbnail' => $thumb,
                        'rating' => (float)($it['avrate'] ?? 0),
                        'popularity' => (int)($it['PlayCount'] ?? 0),
                        'content_url' => $src,
                        'category' => $catName,
                    ]);
                }
            }
        }
        return $out;
    }

    private static function fromFitness(array $json, string $fname): array {
        $lang = self::baseLang($fname);
        $out = [];
        $videos = $json['videos'] ?? [];
        foreach ($videos as $v) {
            $title = $v['name'] ?? 'Exercise';
            $desc = $v['description'] ?? '';
            $idForThumb = (string)($v['id'] ?? '');
            $thumb = self::pickThumb('fitness', [
                $v['thumbnail'] ?? null,
                $v['thumb'] ?? null,
                $v['image'] ?? null,
                $idForThumb ? '/streamifyPro/assets/thumbnails/' . $idForThumb . '.jpg' : null,
            ], '/assets/img/placeholders/fitness-480x270.png');
            $src = $v['url'] ?? null;
            $tags = ['fitness','exercise'];
            $out[] = self::mk([
                'id' => (string)($v['id'] ?? uniqid('fit_', true)),
                'type' => 'video',
                'source' => 'fitness',
                'title' => $title,
                'description' => $desc,
                'tags' => $tags,
                'age_min' => 8,
                'age_max' => 99,
                'language' => $lang,
                'duration_sec' => 0,
                'is_educational' => true,
                'thumbnail' => $thumb,
                'rating' => 0.0,
                'popularity' => 0,
                'content_url' => $src,
            ]);
        }
        return $out;
    }
}
