<?php

namespace App\Enums;

enum ExerciseLevel: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';

    public function label(): string
    {
        return match ($this) {
            self::Beginner => 'Anfänger',
            self::Intermediate => 'Mittel',
            self::Advanced => 'Fortgeschritten',
        };
    }

    /**
     * Liest die deutsche Bezeichnung aus Importdateien („Anfänger“, „Mittel“, „Fortgeschritten“).
     */
    public static function fromLabel(?string $label): ?self
    {
        $label = mb_strtolower(trim((string) $label));

        foreach (self::cases() as $case) {
            if (mb_strtolower($case->label()) === $label || $case->value === $label) {
                return $case;
            }
        }

        return null;
    }
}
