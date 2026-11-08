<?php

namespace Tests\Unit;

use App\Enums\ExerciseMeasure;
use App\Support\Training\ExerciseTarget;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ExerciseTargetTest extends TestCase
{
    /**
     * @return array<string, array{string, ?ExerciseMeasure, ?int, ?int, bool}>
     */
    public static function targets(): array
    {
        return [
            'Spanne' => ['10-15', ExerciseMeasure::Reps, 10, 15, false],
            'Gedankenstrich' => ['8–12', ExerciseMeasure::Reps, 8, 12, false],
            'je Bein' => ['10 je Bein', ExerciseMeasure::Reps, 10, 10, true],
            'je Seite mit Spanne' => ['10-12 je Seite', ExerciseMeasure::Reps, 10, 12, true],
            'insgesamt' => ['16-20 insgesamt', ExerciseMeasure::Reps, 16, 20, false],
            'Sekunden' => ['30-60 Sekunden', ExerciseMeasure::Duration, 30, 60, false],
            'Minuten in Sekunden' => ['15-30 Minuten', ExerciseMeasure::Duration, 900, 1800, false],
            'Intervall' => ['1 Minute intensiv, 1 Minute locker', ExerciseMeasure::Interval, null, null, false],
            'leer' => ['', null, null, null, false],
        ];
    }

    #[DataProvider('targets')]
    public function test_targets_are_parsed(string $text, ?ExerciseMeasure $measure, ?int $min, ?int $max, bool $perSide): void
    {
        $target = ExerciseTarget::parse($text);

        $this->assertSame($measure, $target->measure);
        $this->assertSame($min, $target->min);
        $this->assertSame($max, $target->max);
        $this->assertSame($perSide, $target->perSide);
        $this->assertSame($text, $target->text);
    }
}
