<?php
// app/Intent.php
declare(strict_types=1);

namespace App;

final class Intent
{
    /** Expand/update anytime. Keys map to your real catalog categories. */
    private static array $synonyms = [
        'alphabet'   => ['alphabet','letters','phonics','abcs','abc','abcsong','phonetic','spelling','الحروف','حروف','ابجديه','أبجدية','تعلم الحروف'],
        'animals'    => ['animal','animals','zoo','pets','wildlife','حيوان','حيوانات','حيوانات للأطفال','حيوانات اليفه','حديقة الحيوان'],
        'numbers'    => ['numbers','counting','math','add','subtract','multiplication','الارقام','أرقام','عد','رياضيات','جمع','طرح','ضرب'],
        'stories'    => ['stories','story','bedtime','fairy','reading','قصة','قصص','حكايات','قبل النوم','قراءة'],
        'science'    => ['science','experiment','stem','physics','chemistry','biology','علوم','تجارب','علم','تجربة'],
        'dance'      => ['dance','dancing','movement','rhythm','رقص','حركات','رقصة'],
        'games'      => ['games','game','play','interactive','لعبة','العاب','ألعاب','العب'],
        'fitness'    => ['fitness','exercise','workout','yoga','حركة','تمارين','رياضة','يوغا'],
        'educational'=> ['educational','learning','teach','تعليمي','تعليم','تعلم','تعليميه'],
        'cartoons'   => ['cartoon','cartoons','animated','رسوم','كرتون','انيميشن','رسوم متحركة'],
        'music'      => ['music','song','songs','nursery','اغاني','أغنية','أناشيد','نشيد'],
    ];

    /**
     * Normalize text: lowercase, remove diacritics/tatweel/punct, collapse spaces.
     */
    public static function normalize(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        // Remove Arabic tatweel and diacritics
        $s = preg_replace('/[\x{0640}\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $s) ?? $s;
        // Remove punctuation
        $s = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $s) ?? $s;
        // Collapse whitespace
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
        return trim($s);
    }

    /**
     * Map a free-text message to a single catalog category (deterministic).
     * - Try synonym hits (both EN/AR).
     * - If tie/none: fuzzy-closest among available categories.
     * - Final fallback: 'educational' if available else the first category.
     */
    public static function pickCategory(string $message, array $availableCategories): string
    {
        $text = self::normalize($message);
        if (empty($availableCategories)) return 'educational';

        // 1) Direct/substring hits via synonym table
        $scores = array_fill_keys($availableCategories, 0);
        foreach (self::$synonyms as $category => $words) {
            if (!in_array($category, $availableCategories, true)) continue;
            foreach ($words as $w) {
                $w = self::normalize($w);
                if ($w !== '' && str_contains($text, $w)) {
                    $scores[$category] += mb_strlen($w, 'UTF-8'); // crude weight: longer phrase wins
                }
            }
        }
        arsort($scores);
        $topCat = array_key_first($scores);
        if ($topCat && $scores[$topCat] > 0) return $topCat;

        // 2) Substring against actual categories (e.g., "animal videos" → "animals")
        foreach ($availableCategories as $cat) {
            if (str_contains($text, self::normalize($cat))) return $cat;
        }

        // 3) Fuzzy: closest category name to text
        $best       = $availableCategories[0];
        $bestScore  = -1.0;
        foreach ($availableCategories as $cat) {
            similar_text($text, self::normalize($cat), $pct);
            if ($pct > $bestScore) { $bestScore = $pct; $best = $cat; }
        }

        if ($best) return $best;
        if (in_array('educational', $availableCategories, true)) return 'educational';
        return $availableCategories[0];
    }
}
