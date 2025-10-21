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

    private static function mk(array $r): array {
        return [
            'id' => (string)($r['id'] ?? uniqid('item_', true)),
            'type' => strtolower((string)($r['type'] ?? 'video')),
            'source' => (string)($r['source'] ?? 'unknown'),
            'title' => (string)($r['title'] ?? ''),
            'description' => (string)($r['description'] ?? ''),
            'tags' => self::sanitizeTags($r['tags'] ?? []),
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
            'category' => $r['category'] ?? null,
        ];
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
                    $thumb = $v['imageCropped'] ?? ($v['imageFile'] ?? ($v['Thumbnail'] ?? null));
                    $src = $v['sourceFile'] ?? ($v['Url'] ?? ($v['URL'] ?? null));
                    $tags = ['kids','educational'];
                    $out[] = self::mk([
                'source' => 'fitness',
                'category' => strtolower((string)($v['category_en'] ?? 'fitness')),
                'subcategory' => '',
                'channel' => '',
                'playlist' => '',

                        'source' => 'streaming',
                        'category' => strtolower((string)($ch['category'] ?? '')),
                        'subcategory' => '',
                        'channel' => '',
                        'playlist' => '',

                        'source' => 'games',
                        'category' => strtolower((string)($ch['category'] ?? '')),
                        'subcategory' => '',
                        'channel' => '',
                        'playlist' => '',

                        'source' => 'kids',
                        'category' => strtolower((string)($ch['name'] ?? '')),
                        'subcategory' => strtolower((string)($pl['name'] ?? '')),
                        'channel' => strtolower((string)($ch['name'] ?? '')),
                        'playlist' => strtolower((string)($pl['name'] ?? '')),

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
                $catName = $cat['Name'] ?? 'games';
                $items = $cat['Content'] ?? [];
                foreach ($items as $it) {
                    $title = $it['Title'] ?? ($it['Package_id'] ?? 'Game');
                    $desc = $it['Description'] ?? '';
                    $thumb = $it['Thumbnail'] ?? null;
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
                        'category' => $catName,
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
                    $thumb = $it['Thumbnail'] ?? null;
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
            $thumb = null;
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
