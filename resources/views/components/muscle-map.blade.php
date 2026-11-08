{{--
    Muskelkarte: Körper von vorne und hinten, Hauptmuskulatur kräftig, Nebenmuskulatur hell gefärbt.
    $map: App\Support\Training\MuscleMap, $label: Beschreibung für Screenreader.
    Eigenes SVG (linke Körperhälfte, gespiegelt) mit Theme-Farben – kein Build nötig.
--}}
@props(['map', 'label' => 'Beanspruchte Muskelgruppen', 'size' => 'normal'])

@php
    // Silhouette (linke Hälfte, wird gespiegelt)
    $silhouette = [
        ['path', 'M50 30 L37 30 Q30 31 29 38 L33 62 Q34 80 36 96 L38 106 L50 108 Z'],
        ['path', 'M29 34 Q23 36 22 46 L21 70 Q24 73 28 70 L31 46 Z'],
        ['path', 'M21 70 L18 104 Q21 107 24 104 L28 70 Z'],
        ['ellipse', 20, 110, 3.5, 5],
        ['path', 'M36 100 Q34 130 37 150 L48 150 Q50 128 50 108 Z'],
        ['path', 'M37 150 Q36 172 39 192 L46 192 Q48 172 48 150 Z'],
        ['ellipse', 42, 195, 5, 3],
    ];

    // Muskelbereiche je Ansicht (linke Hälfte, wird gespiegelt)
    $views = [
        'Vorne' => [
            'side_delts' => [['ellipse', 27.5, 39, 3, 6.5]],
            'front_delts' => [['ellipse', 33, 38, 5, 6.5]],
            'chest' => [['path', 'M49 37 L38 36 Q34 38 34 45 Q36 53 44 54 L49 52 Z']],
            'biceps' => [['ellipse', 26.5, 54, 3.8, 10]],
            'forearms' => [['ellipse', 23.5, 85, 3, 11]],
            'obliques' => [['path', 'M43 58 L37 60 Q36 76 39 90 L43 92 Z']],
            'abs' => [['rect', 45, 57, 4.5, 34]],
            'hip_flexors' => [['ellipse', 44, 100, 3.5, 5]],
            'quads' => [['ellipse', 43, 127, 5.8, 19]],
        ],
        'Hinten' => [
            'side_delts' => [['ellipse', 27.5, 39, 3, 6.5]],
            'rear_delts' => [['ellipse', 33, 38, 5, 6.5]],
            'upper_back' => [['path', 'M49 42 L42 40 L38 44 L40 56 L49 58 Z']],
            'traps' => [['path', 'M50 26 L50 50 L42 38 L37 32 L45 29 Z']],
            'lats' => [['path', 'M38 50 L35 56 Q36 72 42 84 L47 80 Q42 68 41 58 Z']],
            'triceps' => [['ellipse', 26.5, 54, 3.8, 10]],
            'forearms' => [['ellipse', 23.5, 85, 3, 11]],
            'lower_back' => [['rect', 45, 64, 4.5, 28]],
            'glutes' => [['ellipse', 43, 106, 7, 8]],
            'hamstrings' => [['ellipse', 43, 132, 5.5, 16]],
            'calves' => [['ellipse', 42.5, 166, 4.2, 12]],
        ],
    ];

    $names = [
        'chest' => 'Brust', 'front_delts' => 'Vordere Schultern', 'side_delts' => 'Seitliche Schultern',
        'rear_delts' => 'Hintere Schultern', 'biceps' => 'Bizeps', 'triceps' => 'Trizeps', 'forearms' => 'Unterarme',
        'abs' => 'Gerader Bauchmuskel', 'obliques' => 'Schräge Bauchmuskeln', 'hip_flexors' => 'Hüftbeuger',
        'quads' => 'Quadrizeps', 'traps' => 'Trapezmuskel', 'upper_back' => 'Oberer Rücken', 'lats' => 'Latissimus',
        'lower_back' => 'Unterer Rücken', 'glutes' => 'Gesäß', 'hamstrings' => 'Beinbeuger', 'calves' => 'Waden',
    ];

    $shape = function (array $s, string $attributes): string {
        return match ($s[0]) {
            'path' => "<path d=\"{$s[1]}\" {$attributes} />",
            'ellipse' => "<ellipse cx=\"{$s[1]}\" cy=\"{$s[2]}\" rx=\"{$s[3]}\" ry=\"{$s[4]}\" {$attributes} />",
            'rect' => "<rect x=\"{$s[1]}\" y=\"{$s[2]}\" width=\"{$s[3]}\" height=\"{$s[4]}\" rx=\"2\" {$attributes} />",
        };
    };
    $mirror = 'transform="translate(100 0) scale(-1 1)"';
    $width = $size === 'small' ? 'max-width: 14rem' : 'max-width: 22rem';
@endphp

<figure {{ $attributes->merge(['class' => 'flex flex-col items-center gap-3']) }}>
    <svg viewBox="0 0 220 214" class="w-full" style="{{ $width }}" role="img" aria-label="{{ $label }}">
        @foreach (array_keys($views) as $index => $viewName)
            <g transform="translate({{ $index * 115 + 5 }} 0)">
                {{-- Silhouette --}}
                <circle cx="50" cy="16" r="10" fill="var(--color-paper-deep)" stroke="var(--color-line)" stroke-width="1" />
                <rect x="45" y="24" width="10" height="8" fill="var(--color-paper-deep)" />
                @foreach (['', $mirror] as $transform)
                    <g {!! $transform !!}>
                        @foreach ($silhouette as $s)
                            {!! $shape($s, 'fill="var(--color-paper-deep)" stroke="var(--color-line)" stroke-width="1" stroke-linejoin="round"') !!}
                        @endforeach
                    </g>
                @endforeach

                {{-- Muskeln --}}
                @foreach ($views[$viewName] as $region => $shapes)
                    @php($style = $map->style($region))
                    <g>
                        <title>{{ $names[$region] }}</title>
                        @foreach (['', $mirror] as $transform)
                            <g {!! $transform !!}>
                                @foreach ($shapes as $s)
                                    {!! $shape($s, 'fill="'.$style['fill'].'" fill-opacity="'.$style['opacity'].'" stroke="#fff" stroke-width="0.8"') !!}
                                @endforeach
                            </g>
                        @endforeach
                    </g>
                @endforeach

                <text x="50" y="211" text-anchor="middle" font-size="10" fill="var(--color-ink-soft)">{{ $viewName }}</text>
            </g>
        @endforeach
    </svg>

    <figcaption class="flex flex-wrap justify-center gap-4 text-xs text-ink-soft">
        <span class="flex items-center gap-2">
            <svg width="12" height="12" aria-hidden="true"><rect width="12" height="12" rx="3" fill="var(--color-herb)" /></svg>
            Hauptmuskulatur
        </span>
        <span class="flex items-center gap-2">
            <svg width="12" height="12" aria-hidden="true"><rect width="12" height="12" rx="3" fill="var(--color-herb)" fill-opacity="0.4" /></svg>
            Nebenmuskulatur
        </span>
    </figcaption>
</figure>
