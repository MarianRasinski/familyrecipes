<?php

namespace App\Support\Training;

use App\Enums\ExerciseMeasure;
use App\Support\Quantity;

/**
 * Zerlegt eine Zielvorgabe wie „10-12 je Seite“, „30-60 Sekunden“ oder „15-30 Minuten“
 * in Messart, Spanne und „pro Seite“. Zeiten werden in Sekunden umgerechnet.
 * Was sich nicht als Zahl lesen lässt (z. B. Intervalle), bleibt nur als Text erhalten.
 */
final class ExerciseTarget
{
    private const SIDE_PATTERN = '/\s*\bje\s+(seite|bein|arm)\b/iu';

    private const UNITS = [
        'sekunde' => 1, 'sekunden' => 1, 'sek' => 1, 's' => 1,
        'minute' => 60, 'minuten' => 60, 'min' => 60,
    ];

    private function __construct(
        public readonly string $text,
        public readonly ?ExerciseMeasure $measure,
        public readonly ?int $min,
        public readonly ?int $max,
        public readonly bool $perSide,
    ) {}

    public static function parse(?string $text): self
    {
        $text = trim((string) $text);

        if ($text === '') {
            return new self('', null, null, null, false);
        }

        $perSide = (bool) preg_match(self::SIDE_PATTERN, $text);
        $rest = trim(preg_replace(self::SIDE_PATTERN, '', $text));

        // „16-20 insgesamt“ = Wiederholungen über beide Seiten
        $rest = trim(preg_replace('/\s*\binsgesamt\b/iu', '', $rest));

        // Mehrere Angaben („1 Minute intensiv, 1 Minute locker“) → Intervall
        if (str_contains($rest, ',')) {
            return new self($text, ExerciseMeasure::Interval, null, null, $perSide);
        }

        $factor = null;

        if (preg_match('/^(.*?)\s*([a-zäöü]+)\.?$/iu', $rest, $m) && isset(self::UNITS[mb_strtolower($m[2])])) {
            $factor = self::UNITS[mb_strtolower($m[2])];
            $rest = $m[1];
        }

        $quantity = Quantity::parse($rest);

        if ($quantity === null) {
            return new self($text, ExerciseMeasure::Interval, null, null, $perSide);
        }

        return new self(
            $text,
            $factor === null ? ExerciseMeasure::Reps : ExerciseMeasure::Duration,
            (int) round($quantity->min * ($factor ?? 1)),
            (int) round($quantity->max * ($factor ?? 1)),
            $perSide,
        );
    }
}
