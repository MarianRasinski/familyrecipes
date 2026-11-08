<?php

namespace App\Enums;

/**
 * Aktivität im Alltag (Beruf, Wege, Haushalt) – ohne Sport, der wird je Trainingstag extra gerechnet.
 * Die PAL-Werte stehen in `config/nutrition.php` (budget.pal).
 */
enum ActivityLevel: string
{
    case Sedentary = 'sedentary';
    case Light = 'light';
    case Active = 'active';
    case Heavy = 'heavy';

    public function label(): string
    {
        return match ($this) {
            self::Sedentary => 'Überwiegend sitzend',
            self::Light => 'Sitzend mit Bewegung',
            self::Active => 'Überwiegend stehend oder gehend',
            self::Heavy => 'Körperlich schwer',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Sedentary => 'Büro oder Homeoffice, kaum Wege zu Fuß.',
            self::Light => 'Sitzende Arbeit, dazu Wege zu Fuß, Haushalt, Kinder.',
            self::Active => 'Z. B. Verkauf, Pflege, Handwerk, Kita.',
            self::Heavy => 'Z. B. Bau, Landwirtschaft, Lager.',
        };
    }

    /**
     * Physical Activity Level: Faktor, mit dem der Grundumsatz zum Gesamtbedarf wird.
     */
    public function pal(): float
    {
        return (float) config("nutrition.budget.pal.{$this->value}");
    }
}
