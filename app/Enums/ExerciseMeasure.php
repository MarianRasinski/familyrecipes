<?php

namespace App\Enums;

/**
 * Worin das Ziel einer Übung gemessen wird.
 */
enum ExerciseMeasure: string
{
    case Reps = 'reps';          // Wiederholungen
    case Duration = 'duration';  // Zeit, gespeichert in Sekunden
    case Interval = 'interval';  // Intervalle, nur als Text beschrieben

    public function label(): string
    {
        return match ($this) {
            self::Reps => 'Wiederholungen',
            self::Duration => 'Dauer',
            self::Interval => 'Intervalle',
        };
    }
}
