<?php

namespace App\Support\Nutrition;

use App\Models\BodyProfile;
use Carbon\CarbonInterface;

/**
 * Tagesbudget aus einem Profil – Orientierungswerte mit nachvollziehbarem Rechenweg.
 *
 * Ruhetag:     Grundumsatz (Mifflin-St Jeor) × PAL ± Ziel-Zuschlag – oder das selbst festgelegte Budget.
 * Trainingstag: Ruhetag + Trainingsverbrauch ((MET − 1) × kg × Stunden).
 * Makros:      Eiweiß je kg (Bezugs-)Gewicht, Fett als Kalorienanteil, Kohlenhydrate = Rest.
 * Alle Annahmen stehen in `config/nutrition.php` (budget).
 */
final class EnergyBudget
{
    private readonly CarbonInterface $date;

    public function __construct(
        public readonly BodyProfile $profile,
        ?CarbonInterface $date = null,
    ) {
        $this->date = $date ?? now();
    }

    public function age(): int
    {
        return $this->profile->ageOn($this->date);
    }

    /**
     * Grundumsatz: 10 × kg + 6,25 × cm − 5 × Alter + 5 (Männer) bzw. − 161 (Frauen).
     */
    public function basalMetabolicRate(): float
    {
        $p = $this->profile;

        return 10 * $p->weight_kg + 6.25 * $p->height_cm - 5 * $this->age() + $p->sex->bmrOffset();
    }

    /**
     * Gesamtbedarf an einem Ruhetag (Alltag ohne Sport).
     */
    public function maintenance(): float
    {
        return $this->basalMetabolicRate() * $this->profile->activity_level->pal();
    }

    /**
     * Zielkalorien laut Formel (ohne selbst festgelegtes Budget).
     */
    public function calculatedTarget(): int
    {
        return $this->round($this->maintenance() + $this->profile->goal->kcalAdjustment());
    }

    public function isOverridden(): bool
    {
        return $this->profile->kcal_override !== null;
    }

    public function restDayKcal(): int
    {
        return $this->profile->kcal_override ?? $this->calculatedTarget();
    }

    /**
     * Zusätzlicher Verbrauch einer Trainingseinheit (netto, ohne den ohnehin enthaltenen Grundumsatz).
     */
    public function trainingKcal(): int
    {
        $p = $this->profile;

        if ($p->training_days === 0) {
            return 0;
        }

        return $this->round(($p->training_type->met() - 1) * $p->weight_kg * $p->training_minutes / 60);
    }

    public function trainingDayKcal(): int
    {
        return $this->restDayKcal() + $this->trainingKcal();
    }

    /**
     * Durchschnitt pro Tag über eine Woche mit den eingetragenen Trainingstagen.
     */
    public function weeklyAverageKcal(): int
    {
        $days = $this->profile->training_days;

        return $this->round(($this->restDayKcal() * (7 - $days) + $this->trainingDayKcal() * $days) / 7);
    }

    public function restDay(): Nutrients
    {
        return $this->targetsFor($this->restDayKcal());
    }

    public function trainingDay(): Nutrients
    {
        return $this->targetsFor($this->trainingDayKcal());
    }

    /**
     * Gewicht, auf das sich das Eiweißziel bezieht: bei hohem BMI das Gewicht beim Bezugs-BMI.
     */
    public function proteinReferenceWeight(): float
    {
        $heightM = $this->profile->height_cm / 100;
        $reference = (float) config('nutrition.budget.protein_reference_bmi') * $heightM ** 2;

        return round(min($this->profile->weight_kg, $reference), 1);
    }

    public function bmi(): float
    {
        return round($this->profile->weight_kg / ($this->profile->height_cm / 100) ** 2, 1);
    }

    /**
     * Makro-Ziele für eine Kalorienmenge.
     */
    public function targetsFor(int $kcal): Nutrients
    {
        $protein = round($this->proteinReferenceWeight() * $this->profile->goal->proteinPerKg());
        $fat = round($kcal * (float) config('nutrition.budget.fat_share') / 9);
        $carbs = max(0, round(($kcal - $protein * 4 - $fat * 9) / 4));

        return new Nutrients(
            kcal: $kcal,
            protein: $protein,
            fat: $fat,
            carbs: $carbs,
            fiber: (float) config('nutrition.budget.fiber'),
        );
    }

    /**
     * Hinweise, wenn das Budget ungewöhnlich ist.
     *
     * @return list<string>
     */
    public function warnings(): array
    {
        $warnings = [];

        if ($this->restDayKcal() < $this->basalMetabolicRate()) {
            $warnings[] = 'Dein Budget liegt unter deinem Grundumsatz. Das ist auf Dauer nicht empfehlenswert – sprich das am besten mit deiner Ärztin oder deinem Arzt ab.';
        }

        $rest = $this->restDay();
        if ($rest->protein * 4 + $rest->fat * 9 > $rest->kcal) {
            $warnings[] = 'Eiweiß- und Fettziel brauchen schon mehr Kalorien als das Budget hergibt; für Kohlenhydrate bleibt nichts übrig.';
        }

        return $warnings;
    }

    /**
     * Rechenweg zum Anzeigen: Schritt, Rechnung, Ergebnis.
     *
     * @return list<array{label: string, formula: string, result: string}>
     */
    public function steps(): array
    {
        $p = $this->profile;
        $sign = $p->sex->bmrOffset() < 0 ? '− '.abs($p->sex->bmrOffset()) : '+ '.$p->sex->bmrOffset();
        $adjustment = $p->goal->kcalAdjustment();

        $steps = [
            [
                'label' => 'Grundumsatz (Mifflin-St Jeor)',
                'formula' => sprintf('10 × %s kg + 6,25 × %d cm − 5 × %d Jahre %s', self::number($p->weight_kg), $p->height_cm, $this->age(), $sign),
                'result' => self::kcal($this->basalMetabolicRate()),
            ],
            [
                'label' => 'Gesamtbedarf am Ruhetag',
                'formula' => sprintf('Grundumsatz × PAL %s (%s)', self::number($p->activity_level->pal()), $p->activity_level->label()),
                'result' => self::kcal($this->maintenance()),
            ],
            [
                'label' => 'Ziel: '.$p->goal->label(),
                'formula' => $adjustment === 0
                    ? 'kein Zu- oder Abschlag'
                    : sprintf('%s %d kcal, gerundet', $adjustment < 0 ? '−' : '+', abs($adjustment)),
                'result' => self::kcal($this->calculatedTarget()),
            ],
        ];

        if ($this->isOverridden()) {
            $steps[] = [
                'label' => 'Selbst festgelegtes Budget',
                'formula' => 'ersetzt das berechnete Ziel an Ruhetagen',
                'result' => self::kcal($this->restDayKcal()),
            ];
        }

        if ($p->training_days > 0) {
            $steps[] = [
                'label' => 'Training: '.$p->training_type->label(),
                'formula' => sprintf('(MET %s − 1) × %s kg × %d Min. ÷ 60', self::number($p->training_type->met()), self::number($p->weight_kg), $p->training_minutes),
                'result' => '+ '.self::kcal($this->trainingKcal()),
            ];
        }

        $reference = $this->proteinReferenceWeight();
        $steps[] = [
            'label' => 'Eiweiß',
            'formula' => sprintf('%s g × %s kg', self::number($p->goal->proteinPerKg()), self::number($reference))
                .($reference < $p->weight_kg ? sprintf(' (Gewicht bei BMI %d, dein BMI: %s)', config('nutrition.budget.protein_reference_bmi'), self::number($this->bmi())) : ''),
            'result' => self::number($this->restDay()->protein).' g',
        ];
        $steps[] = [
            'label' => 'Fett',
            'formula' => sprintf('%d %% der Kalorien ÷ 9 kcal/g', round((float) config('nutrition.budget.fat_share') * 100)),
            'result' => self::number($this->restDay()->fat).' g',
        ];
        $steps[] = [
            'label' => 'Kohlenhydrate',
            'formula' => 'restliche Kalorien ÷ 4 kcal/g',
            'result' => self::number($this->restDay()->carbs).' g',
        ];

        return $steps;
    }

    private function round(float $kcal): int
    {
        $step = max(1, (int) config('nutrition.budget.round_to'));

        return (int) (round($kcal / $step) * $step);
    }

    private static function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
    }

    private static function kcal(float $value): string
    {
        return number_format(round($value), 0, ',', '.').' kcal';
    }
}
