{{--
    Übungsbild: Strichfiguren mit Start- und Endposition aus config/exercise_illustrations.php.
    Reines SVG mit Theme-Farben (kein Build nötig). Ohne Eintrag für die Übung wird nichts ausgegeben.
--}}
@props(['exercise'])

@php
    $frames = config('exercise_illustrations.'.$exercise->slug, []);

    $line = fn (array $a, array $b, string $color, float $width = 4, string $opacity = '1') => sprintf(
        '<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="%s" stroke-width="%s" stroke-opacity="%s" stroke-linecap="round" />',
        $a[0], $a[1], $b[0], $b[1], $color, $width, $opacity,
    );
    $offset = fn (?array $p, float $dx) => $p ? [$p[0] + $dx, $p[1]] : null;

    $render = function (array $f) use ($line, $offset): string {
        $ink = 'var(--color-ink)';
        $soft = 'var(--color-ink-soft)';
        $front = $f['front'] ?? false;
        $out = '';

        // Szene: Boden, Matte, Rudergerät
        $out .= $line([2, 95], [118, 95], 'var(--color-line)', 2);
        if (($f['scene'] ?? null) === 'mat') {
            $out .= '<rect x="4" y="93" width="112" height="2.5" rx="1.2" fill="var(--color-accent)" fill-opacity="0.35" />';
        }
        if (($f['scene'] ?? null) === 'rower') {
            $out .= $line([6, 89], [98, 89], 'var(--color-line)', 3);
            $out .= '<circle cx="104" cy="80" r="9" fill="var(--color-paper-deep)" stroke="var(--color-ink-soft)" stroke-width="1.5" />';
            $out .= $line([72, 72], [76, 88], 'var(--color-ink-soft)', 2.5);
            $out .= sprintf('<rect x="%s" y="82" width="14" height="4" rx="2" fill="var(--color-ink-soft)" />', $f['h'][0] - 7);
            $out .= $line($f['w1'], [100, 78], 'var(--color-ink-soft)', 1, '0.7');
        }

        // Gliedmaßen: hinten (hell) zuerst, dann Rumpf, dann vorne
        $limb = function (string $joint, string $end, ?string $tip, string $suffix, array $from, string $color, string $opacity) use ($f, $line, $offset) {
            $near = $suffix === '2' ? '1' : $suffix;
            $explicit = array_key_exists($joint.$suffix, $f);
            if ($explicit && $f[$joint.$suffix] === null) {
                return '';
            }
            $p1 = $f[$joint.$suffix] ?? $offset($f[$joint.$near] ?? null, -2);
            $p2 = $f[$end.$suffix] ?? $offset($f[$end.$near] ?? null, -2);
            if (! $p1 || ! $p2) {
                return '';
            }
            $svg = $line($from, $p1, $color, 4, $opacity).$line($p1, $p2, $color, 4, $opacity);
            $p3 = $tip ? ($f[$tip.$suffix] ?? $offset($f[$tip.$near] ?? null, -2)) : null;

            return $svg.($p3 ? $line($p2, $p3, $color, 3.5, $opacity) : '');
        };

        $shoulderNear = $front ? [$f['s'][0] + 8, $f['s'][1]] : $f['s'];
        $shoulderFar = $front ? [$f['s'][0] - 8, $f['s'][1]] : $f['s'];
        $hipNear = $front ? [$f['h'][0] + 5, $f['h'][1]] : $f['h'];
        $hipFar = $front ? [$f['h'][0] - 5, $f['h'][1]] : $f['h'];
        $farColor = $front ? $ink : $soft;
        $farOpacity = $front ? '1' : '0.55';

        $out .= $limb('k', 'a', 't', '2', $hipFar, $farColor, $farOpacity);
        $out .= $limb('e', 'w', null, '2', $shoulderFar, $farColor, $farOpacity);

        $out .= $line($f['s'], $f['h'], $ink, 5);
        if ($front) {
            $out .= $line($shoulderFar, $shoulderNear, $ink, 4).$line($hipFar, $hipNear, $ink, 4);
        }
        $out .= $line($f['head'], $f['s'], $ink, 3);
        $out .= sprintf('<circle cx="%s" cy="%s" r="6" fill="%s" />', $f['head'][0], $f['head'][1], $ink);

        $out .= $limb('k', 'a', 't', '1', $hipNear, $ink, '1');
        $out .= $limb('e', 'w', null, '1', $shoulderNear, $ink, '1');

        // Geräte
        foreach ($f['props'] ?? [] as $prop) {
            [$type, $x] = $prop;
            $y = $prop[2] ?? 0;
            $out .= match ($type) {
                'kb' => sprintf('<path d="M%s %s Q%s %s %s %s" fill="none" stroke="var(--color-accent-dark)" stroke-width="2" />', $x - 3, $y + 3, $x, $y - 3, $x + 3, $y + 3)
                    .sprintf('<circle cx="%s" cy="%s" r="5" fill="var(--color-accent)" />', $x, $y + 7),
                'db' => $line([$x - 5, $y], [$x + 5, $y], $soft, 2)
                    .sprintf('<rect x="%s" y="%s" width="3" height="8" rx="1" fill="var(--color-accent)" />', $x - 7, $y - 4)
                    .sprintf('<rect x="%s" y="%s" width="3" height="8" rx="1" fill="var(--color-accent)" />', $x + 4, $y - 4),
                'handles' => sprintf('<rect x="%s" y="91" width="10" height="4" rx="1.5" fill="var(--color-accent)" />', $x - 5),
                default => '',
            };
        }

        return $out;
    };

    $defaultLabels = count($frames) === 1 ? ['Position'] : ['Start', 'Ende'];
@endphp

@if ($frames)
    <figure {{ $attributes->merge(['class' => 'flex flex-wrap items-center justify-center gap-2']) }} aria-label="Ausführung: {{ $exercise->name }}">
        @foreach ($frames as $i => $frame)
            @if ($i > 0)
                <span class="text-2xl text-ink-soft" aria-hidden="true">→</span>
            @endif
            <div class="flex flex-1 flex-col items-center gap-1" style="min-width: 8rem; max-width: 14rem">
                <svg viewBox="0 -14 120 112" class="w-full" role="img"
                     aria-label="{{ $exercise->name }}, {{ $frame['label'] ?? ($defaultLabels[$i] ?? 'Bild '.($i + 1)) }}">
                    {!! $render($frame) !!}
                </svg>
                <span class="text-xs font-semibold text-ink-soft">{{ $frame['label'] ?? ($defaultLabels[$i] ?? '') }}</span>
            </div>
        @endforeach
    </figure>
@endif
