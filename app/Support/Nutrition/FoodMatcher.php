<?php

namespace App\Support\Nutrition;

use App\Models\Food;
use Illuminate\Support\Collection;

/**
 * Schlägt zu einem Zutatennamen ein Lebensmittel aus dem BLS vor.
 *
 * 1. Synonymliste (config/nutrition.php), auch für Singular/Plural-Varianten
 * 2. freie Suche (Food::search – Grundlebensmittel und rohe zuerst); nicht für Gewürze/Kräuter
 * 3. Suche nach dem längsten einzelnen Wort
 *
 * Das Ergebnis ist nur ein Vorschlag; gespeichert wird erst nach Bestätigung im Zuordnungsassistenten.
 */
final class FoodMatcher
{
    /** @var array<string, Food|null> */
    private array $cache = [];

    public function suggest(string $name): ?Food
    {
        $key = self::normalize($name);

        if ($key === '') {
            return null;
        }

        return $this->cache[$key] ??= $this->find($key);
    }

    /**
     * Ist die Zutat ein Gewürz o. Ä., das ohne Zuordnung vernachlässigt werden darf?
     */
    public static function isNegligible(string $name): bool
    {
        $normalized = self::normalize($name);

        foreach (config('nutrition.negligible') as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kleinschreibung, ohne Klammerzusätze und Alternativen („Hähnchenbrust oder Pute (optional)“ → „hähnchenbrust“).
     */
    public static function normalize(string $name): string
    {
        $name = mb_strtolower($name);
        $name = preg_replace('/\([^)]*\)/u', ' ', $name);
        $name = preg_split('/\s+(oder|bzw\.?|alternativ)\s+|,|\//u', $name)[0];

        return trim(preg_replace('/\s+/u', ' ', $name));
    }

    private function find(string $name): ?Food
    {
        $candidates = $this->candidates($name);
        $synonyms = config('nutrition.synonyms');

        foreach ($candidates as $candidate) {
            if (isset($synonyms[$candidate]) && $food = Food::firstWhere('bls_code', $synonyms[$candidate])) {
                return $food;
            }
        }

        // Gewürze & Kräuter nur über die Synonymliste – die freie Suche findet sonst Gerichte damit
        if (self::isNegligible($name)) {
            return null;
        }

        foreach ($candidates as $candidate) {
            if ($food = Food::query()->search($candidate)->first()) {
                return $food;
            }
        }

        $longest = collect(explode(' ', $candidates->last()))->sortByDesc(fn ($word) => mb_strlen($word))->first();

        return $longest && mb_strlen($longest) >= 4 ? Food::query()->search($longest)->first() : null;
    }

    /**
     * Name, Name ohne Füllwörter und Singularformen – in dieser Reihenfolge.
     *
     * @return Collection<int, string>
     */
    private function candidates(string $name): Collection
    {
        $ignore = config('nutrition.ignore_words');
        $stripped = trim(implode(' ', array_diff(explode(' ', $name), $ignore)));

        return collect([$name, $stripped])
            ->filter()
            ->flatMap(fn (string $term) => [$term, ...self::singulars($term)])
            ->unique()
            ->values();
    }

    /**
     * „tomaten“ → „tomate“, „zwiebeln“ → „zwiebel“, „wraps“ → „wrap“ (nur letztes Wort).
     *
     * @return list<string>
     */
    private static function singulars(string $term): array
    {
        $forms = [];

        foreach (['en' => '', 'n' => '', 's' => '', 'e' => ''] as $suffix => $replacement) {
            if (mb_strlen($term) > 4 && str_ends_with($term, $suffix)) {
                $forms[] = mb_substr($term, 0, -mb_strlen($suffix)).$replacement;
            }
        }

        return $forms;
    }
}
