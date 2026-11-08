<?php

namespace App\Enums;

/**
 * Geschlecht für die Grundumsatz-Formel (Mifflin-St Jeor kennt nur diese beiden Varianten).
 */
enum Sex: string
{
    case Female = 'female';
    case Male = 'male';

    public function label(): string
    {
        return match ($this) {
            self::Female => 'Weiblich',
            self::Male => 'Männlich',
        };
    }

    /**
     * Konstante am Ende der Mifflin-St-Jeor-Formel.
     */
    public function bmrOffset(): int
    {
        return match ($this) {
            self::Female => -161,
            self::Male => 5,
        };
    }
}
