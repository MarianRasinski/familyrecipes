<?php

namespace App\Support\Nutrition;

use App\Models\Food;
use App\Models\Ingredient;
use App\Support\Quantity;

/**
 * Rechnet eine Rezeptangabe („2 EL“, „500–600 g“, „1 mittelgroße“) in Gramm um.
 *
 * Reihenfolge: eingetragenes Gewicht der Zutat → Gewichts-/Volumeneinheit (exakt) →
 * Löffel & Co. → Stückgewicht nach Stichwort. Annahmen stehen in config/nutrition.php.
 * Bereiche werden gemittelt und gelten als Schätzung.
 */
final class IngredientWeight
{
    public static function for(Ingredient $ingredient, ?Food $food = null): WeightEstimate
    {
        if ($ingredient->grams !== null) {
            return WeightEstimate::exact($ingredient->grams, 'eingetragenes Gewicht');
        }

        return self::estimate($ingredient->amount, $ingredient->unit, $ingredient->name, $food);
    }

    public static function estimate(?string $amount, ?string $unit, string $name, ?Food $food = null): WeightEstimate
    {
        $unitKey = mb_strtolower(trim((string) $unit));
        $quantity = Quantity::parse($amount);

        if ($quantity === null) {
            // „Salz, Pfeffer“ ohne Menge: zählt nicht als fehlender Wert
            return filled(trim((string) $amount)) || ! in_array($unitKey, ['', 'prise', 'etwas'], true)
                ? WeightEstimate::unknown('Menge nicht lesbar')
                : WeightEstimate::negligible();
        }

        $count = ($quantity->min + $quantity->max) / 2;
        $isRange = $quantity->min !== $quantity->max;
        $units = config('nutrition.units');

        if (isset($units[$unitKey])) {
            $definition = $units[$unitKey];
            $perUnit = $definition['by_category'][$food?->category?->value] ?? $definition['grams'];
            $grams = $count * $perUnit;
            $note = $isRange ? 'Mittelwert der Spanne' : (($definition['exact'] ?? false) ? '' : "1 {$unit} ≈ ".self::format($perUnit).' g');

            return ($definition['exact'] ?? false) && ! $isRange
                ? WeightEstimate::exact($grams)
                : WeightEstimate::estimated($grams, $note);
        }

        if (in_array($unitKey, config('nutrition.piece_units'), true)) {
            $piece = self::pieceWeight($name, $food);

            if ($piece === null) {
                return WeightEstimate::unknown('Stückgewicht unbekannt');
            }

            $factor = self::sizeFactor($unitKey.' '.$name);

            return WeightEstimate::estimated($count * $piece * $factor, '1 Stück ≈ '.self::format($piece * $factor).' g');
        }

        return WeightEstimate::unknown("Einheit „{$unit}“ unbekannt");
    }

    /**
     * Stückgewicht nach dem längsten passenden Stichwort im Zutaten- oder Lebensmittelnamen.
     */
    private static function pieceWeight(string $name, ?Food $food): ?float
    {
        $weights = config('nutrition.piece_weights');
        uksort($weights, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ([FoodMatcher::normalize($name), mb_strtolower((string) $food?->name)] as $haystack) {
            if ($haystack === '') {
                continue;
            }

            foreach ($weights as $keyword => $grams) {
                if (preg_match('/(^|[^\p{L}])'.preg_quote($keyword, '/').'(n|en|e|s)?($|[^\p{L}])/u', $haystack)
                    || str_contains($haystack, $keyword) && mb_strlen($keyword) >= 5) {
                    return (float) $grams;
                }
            }
        }

        return null;
    }

    private static function sizeFactor(string $text): float
    {
        foreach (config('nutrition.size_factors') as $word => $factor) {
            if (preg_match('/(^|\s)'.preg_quote($word, '/').'($|\s)/u', mb_strtolower($text))) {
                return (float) $factor;
            }
        }

        return 1.0;
    }

    private static function format(float $grams): string
    {
        return rtrim(rtrim(number_format($grams, 1, ',', ''), '0'), ',');
    }
}
