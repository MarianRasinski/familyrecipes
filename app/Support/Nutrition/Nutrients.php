<?php

namespace App\Support\Nutrition;

/**
 * Nährwerte einer Menge (z. B. 100 g Lebensmittel, eine Portion, ein Tag).
 *
 * `estimated` = mindestens ein Wert beruht auf einer Annahme (fehlender Datenwert, geschätzte
 * Menge o. Ä.) und ist deshalb nur ein Näherungswert. Das Flag bleibt beim Addieren erhalten.
 */
final class Nutrients
{
    public const FIELDS = ['kcal', 'protein', 'fat', 'carbs', 'fiber'];

    public function __construct(
        public readonly float $kcal = 0,
        public readonly float $protein = 0,
        public readonly float $fat = 0,
        public readonly float $carbs = 0,
        public readonly float $fiber = 0,
        public readonly bool $estimated = false,
    ) {}

    public static function zero(): self
    {
        return new self;
    }

    /**
     * Aus Werten je 100 g; fehlende Werte (null) zählen als 0 und machen das Ergebnis zur Schätzung.
     *
     * @param  array<string, float|int|string|null>  $per100g
     */
    public static function fromPer100g(array $per100g, float $grams = 100): self
    {
        $factor = $grams / 100;
        $missing = false;
        $values = [];

        foreach (self::FIELDS as $field) {
            $value = $per100g[$field] ?? null;
            $missing = $missing || $value === null;
            $values[$field] = (float) $value * $factor;
        }

        return new self(...$values, estimated: $missing);
    }

    public function add(self $other): self
    {
        return new self(
            $this->kcal + $other->kcal,
            $this->protein + $other->protein,
            $this->fat + $other->fat,
            $this->carbs + $other->carbs,
            $this->fiber + $other->fiber,
            $this->estimated || $other->estimated,
        );
    }

    public function scale(float $factor): self
    {
        return new self(
            $this->kcal * $factor,
            $this->protein * $factor,
            $this->fat * $factor,
            $this->carbs * $factor,
            $this->fiber * $factor,
            $this->estimated,
        );
    }

    public function markEstimated(): self
    {
        return new self($this->kcal, $this->protein, $this->fat, $this->carbs, $this->fiber, true);
    }

    /**
     * Gerundet zum Anzeigen: kcal ganzzahlig, Gramm auf eine Nachkommastelle.
     *
     * @return array{kcal: int, protein: float, fat: float, carbs: float, fiber: float}
     */
    public function rounded(): array
    {
        return [
            'kcal' => (int) round($this->kcal),
            'protein' => round($this->protein, 1),
            'fat' => round($this->fat, 1),
            'carbs' => round($this->carbs, 1),
            'fiber' => round($this->fiber, 1),
        ];
    }
}
