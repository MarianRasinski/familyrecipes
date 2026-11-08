{{--
    Fortschrittsring als SVG (ohne neue CSS-Klassen, Farben über die Theme-Variablen).
    value/target in derselben Einheit; über 100 % wird der Ring in Akzentfarbe gezeichnet.
--}}
@props(['value', 'target', 'label', 'unit' => 'g', 'size' => 88, 'stroke' => 8, 'over' => false])

@php
    $radius = ($size - $stroke) / 2;
    $circumference = 2 * M_PI * $radius;
    $ratio = $target > 0 ? $value / $target : 0;
    $filled = $circumference * min(1, max(0, $ratio));
    $color = ($over || $ratio > 1) ? 'var(--color-accent)' : 'var(--color-herb)';
    $center = $size / 2;
    $display = number_format($value, 0, ',', '.');
@endphp

<figure class="flex flex-col items-center gap-1 text-center">
    <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 {{ $size }} {{ $size }}" role="img"
         aria-label="{{ $label }}: {{ $display }} von {{ number_format($target, 0, ',', '.') }} {{ $unit }}">
        <circle cx="{{ $center }}" cy="{{ $center }}" r="{{ $radius }}" fill="none" stroke="var(--color-paper-deep)" stroke-width="{{ $stroke }}" />
        <circle cx="{{ $center }}" cy="{{ $center }}" r="{{ $radius }}" fill="none" stroke="{{ $color }}" stroke-width="{{ $stroke }}"
                stroke-linecap="round" stroke-dasharray="{{ round($filled, 2) }} {{ round($circumference, 2) }}"
                transform="rotate(-90 {{ $center }} {{ $center }})" />
        <text x="50%" y="50%" text-anchor="middle" dominant-baseline="central" fill="var(--color-ink)"
              font-size="{{ round($size / 4.6) }}" font-weight="600">{{ $display }}</text>
    </svg>
    <figcaption class="text-xs text-ink-soft">
        <span class="block font-semibold text-ink">{{ $label }}</span>
        von {{ number_format($target, 0, ',', '.') }} {{ $unit }}
    </figcaption>
</figure>
