<?php
declare(strict_types=1);

namespace App;

final class CatalogIndex {
    public array $sources = [];
    public array $categories = [];
    public array $subcategories = [];
    public array $channels = [];
    public array $playlists = [];
    public array $lang = [];
    public array $byKey = [];

    public function __construct(private array $items) {
        $this->build();
    }
    private function addSet(array &$map, string $key, string $value): void {
        if ($key === '') return;
        if (!isset($map[$key])) { $map[$key] = []; }
        if ($value !== '' && !in_array($value, $map[$key], true)) $map[$key][] = $value;
    }
    private function k(string ...$parts): string {
        return strtolower(implode('|', array_map(fn($x)=>$x ?? '', $parts)));
    }
    private function build(): void {
        $srcs = []; $langs = [];
        foreach ($this->items as $idx => $it) {
            $source = strtolower($it['source'] ?? 'unknown');
            $category = strtolower($it['category'] ?? '');
            $subcategory = strtolower($it['subcategory'] ?? '');
            $channel = strtolower($it['channel'] ?? '');
            $playlist = strtolower($it['playlist'] ?? '');
            $language = strtolower($it['language'] ?? 'en');
            if ($source && !in_array($source, $srcs, true)) $srcs[] = $source;
            if ($language && !in_array($language, $langs, true)) $langs[] = $language;

            $this->addSet($this->categories, $source, $category);
            $this->addSet($this->subcategories, $source, $subcategory);
            $this->addSet($this->channels, $source, $channel);
            $this->addSet($this->playlists, $source, $playlist);

            $keys = [
                $this->k($source, $category, $subcategory, $channel, $playlist, $language, $it['type'] ?? ''),
                $this->k($source, $category, '', '', '', $language, $it['type'] ?? ''),
                $this->k($source, '', '', '', '', $language, $it['type'] ?? ''),
                $this->k('', '', '', '', '', $language, $it['type'] ?? ''),
            ];
            foreach ($keys as $key) {
                if (!isset($this->byKey[$key])) $this->byKey[$key] = [];
                $this->byKey[$key][] = $idx;
            }
        }
        sort($srcs); sort($langs);
        $this->sources = $srcs; $this->lang = $langs;

        foreach (['categories','subcategories','channels','playlists'] as $m) {
            foreach ($this->{$m} as $s => $arr) {
                $this->{$m}[$s] = array_values(array_unique(array_filter($arr)));
                sort($this->{$m}[$s]);
            }
        }
    }
}
