<?php
declare(strict_types=1);

use function App\load_catalog_default_cached;
use function App\json_error;

require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $data = load_catalog_default_cached();
    $summary = [
        'total_items' => count($data),
        'by_language' => [],
        'generated_at' => gmdate('c')
    ];

    // Helper for age buckets
    $buckets = [
        '2-4' => [2,4],
        '5-7' => [5,7],
        '8-10' => [8,10],
        '11-13' => [11,13],
        '14+' => [14,99]
    ];

    $langMap = [];
    foreach ($data as $it) {
        $lang = $it['language'] ?? 'unknown';
        if (!isset($langMap[$lang])) {
            $langMap[$lang] = [
                'total' => 0,
                'videos' => 0,
                'games' => 0,
                'educational_videos' => 0,
                'avg_duration_sec' => 0,
                'age_buckets' => array_fill_keys(array_keys($buckets), 0)
            ];
        }
        $langMap[$lang]['total']++;
        if (($it['type'] ?? '') === 'video') {
            $langMap[$lang]['videos']++;
            if (!empty($it['is_educational'])) $langMap[$lang]['educational_videos']++;
            $langMap[$lang]['avg_duration_sec'] += (int)($it['duration_sec'] ?? 0);
        } elseif (($it['type'] ?? '') === 'game') {
            $langMap[$lang]['games']++;
        }
        $ageMin = (int)($it['age_min'] ?? 0);
        $ageMax = (int)($it['age_max'] ?? 0);
        foreach ($buckets as $k => [$a,$b]) {
            // Count if item overlaps the bucket
            if ($ageMin <= $b && $ageMax >= $a) $langMap[$lang]['age_buckets'][$k]++;
        }
    }
    foreach ($langMap as $lang => $row) {
        if ($row['videos'] > 0) {
            $row['avg_duration_sec'] = (int) round($row['avg_duration_sec'] / $row['videos']);
        } else {
            $row['avg_duration_sec'] = 0;
        }
        $summary['by_language'][$lang] = $row;
    }

    echo json_encode($summary, JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    json_error(500, 'debug/catalog failed: ' . $e->getMessage());
}
