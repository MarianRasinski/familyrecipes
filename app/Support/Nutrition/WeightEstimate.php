<?php

namespace App\Support\Nutrition;

/**
 * Ergebnis der Umrechnung einer Rezeptangabe in Gramm.
 */
final class WeightEstimate
{
    private function __construct(
        public readonly ?float $grams,
        public readonly bool $estimated,
        public readonly bool $negligible,
        public readonly string $note,
    ) {}

    public static function exact(float $grams, string $note = ''): self
    {
        return new self($grams, false, false, $note);
    }

    public static function estimated(float $grams, string $note): self
    {
        return new self($grams, true, false, $note);
    }

    /**
     * Keine Menge angegeben („Salz, Pfeffer“). Ob das vernachlässigt werden darf, entscheidet
     * IngredientNutrition (nur bei Gewürzen).
     */
    public static function negligible(string $note = 'ohne Mengenangabe'): self
    {
        return new self(null, false, true, $note);
    }

    public static function unknown(string $note): self
    {
        return new self(null, true, false, $note);
    }

    public function isKnown(): bool
    {
        return $this->grams !== null;
    }
}
