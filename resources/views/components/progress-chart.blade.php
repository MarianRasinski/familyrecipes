{{--
    Liniendiagramm für einen Verlauf (eine Reihe, daher ohne Legende – die Überschrift benennt sie).
    $points: Liste ['label' => '14.10.', 'value' => float, 'text' => '10 × 20 kg'] in zeitlicher Reihenfolge.
    Reines SVG mit Theme-Farben (kein Build nötig); Tooltips über <title>, Tabellenansicht liefert die Seite.
--}}
@props(['points', 'unit' => '', 'label' => 'Verlauf'])

@php
    $width = 480;
    $height = 200;
    $left = 44;
    $right = 16;
    $top = 16;
    $bottom = 28;
    $values = collect($points)->pluck('value');
    $min = $values->min();
    $max = $values->max();
    // Etwas Luft über und unter der Linie; bei gleichen Werten eine feste Spanne
    $pad = $max > $min ? ($max - $min) * 0.15 : max(1, $max * 0.1);
    $low = max(0, $min - $pad);
    $high = $max + $pad;
    $count = count($points);
    $x = fn (int $i) => $left + ($count > 1 ? $i * ($width - $left - $right) / ($count - 1) : ($width - $left - $right) / 2);
    $y = fn (float $v) => $top + ($high - $v) / ($high - $low) * ($height - $top - $bottom);
    $fmt = fn (float $v) => rtrim(rtrim(number_format($v, 1, ',', '.'), '0'), ',');
    $path = collect($points)->values()->map(fn ($p, $i) => ($i === 0 ? 'M' : 'L').round($x($i), 1).' '.round($y($p['value']), 1))->implode(' ');
    $ticks = $max > $min ? [$min, ($min + $max) / 2, $max] : [$min];
@endphp

<svg viewBox="0 0 {{ $width }} {{ $height }}" class="w-full" role="img"
     aria-label="{{ $label }}: von {{ $fmt($points[0]['value']) }} auf {{ $fmt($points[$count - 1]['value']) }} {{ $unit }}">
    {{-- Raster und Achsenbeschriftung zurückhaltend --}}
    @foreach ($ticks as $tick)
        <line x1="{{ $left }}" x2="{{ $width - $right }}" y1="{{ round($y($tick), 1) }}" y2="{{ round($y($tick), 1) }}"
              stroke="var(--color-line)" stroke-width="1" />
        <text x="{{ $left - 8 }}" y="{{ round($y($tick), 1) }}" text-anchor="end" dominant-baseline="central"
              font-size="12" fill="var(--color-ink-soft)">{{ $fmt($tick) }}</text>
    @endforeach

    {{-- Datumsangaben: erstes und letztes Training --}}
    <text x="{{ round($x(0), 1) }}" y="{{ $height - 8 }}" text-anchor="{{ $count > 1 ? 'start' : 'middle' }}" font-size="12" fill="var(--color-ink-soft)">{{ $points[0]['label'] }}</text>
    @if ($count > 1)
        <text x="{{ round($x($count - 1), 1) }}" y="{{ $height - 8 }}" text-anchor="end" font-size="12" fill="var(--color-ink-soft)">{{ $points[$count - 1]['label'] }}</text>
    @endif

    {{-- Linie (2 px) --}}
    <path d="{{ $path }}" fill="none" stroke="var(--color-herb)" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />

    {{-- Punkte mit Ring zur Fläche, größere unsichtbare Trefferfläche mit Tooltip --}}
    @foreach ($points as $i => $point)
        <g>
            <title>{{ $point['label'] }}: {{ $point['text'] }}</title>
            <circle cx="{{ round($x($i), 1) }}" cy="{{ round($y($point['value']), 1) }}" r="5" fill="var(--color-herb)" stroke="#fff" stroke-width="2" />
            <circle cx="{{ round($x($i), 1) }}" cy="{{ round($y($point['value']), 1) }}" r="14" fill="transparent" />
        </g>
    @endforeach

    {{-- Direktes Label nur am letzten Punkt --}}
    <text x="{{ round($x($count - 1), 1) }}" y="{{ round($y($points[$count - 1]['value']) - 12, 1) }}" text-anchor="{{ $count > 1 ? 'end' : 'middle' }}"
          font-size="12" font-weight="600" fill="var(--color-ink)">{{ $fmt($points[$count - 1]['value']) }} {{ $unit }}</text>
</svg>
