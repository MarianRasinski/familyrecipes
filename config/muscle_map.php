<?php

/*
|--------------------------------------------------------------------------
| Muskelkarte (Körper von vorne und hinten)
|--------------------------------------------------------------------------
|
| `groups`: Muskelgruppe (Slug aus dem Übungskatalog) → Körperbereiche der Grafik
| (resources/views/components/muscle-map.blade.php). Sammelbegriffe wie „Beine“ oder „Rücken“
| färben mehrere Bereiche. Tiefe Muskulatur ist nicht sichtbar und wird über den darüber
| liegenden Bereich dargestellt. Unbekannte Muskelgruppen werden ignoriert.
|
| Bereiche vorne:  chest, front_delts, side_delts, biceps, forearms, abs, obliques, hip_flexors, quads
| Bereiche hinten: traps, rear_delts, side_delts, upper_back, lats, triceps, forearms, lower_back,
|                  glutes, hamstrings, calves
|
*/

return [

    'groups' => [
        'bauch' => ['abs', 'obliques'],
        'gerader-bauchmuskel' => ['abs'],
        'schraege-bauchmuskeln' => ['obliques'],
        'tiefe-bauchmuskulatur' => ['abs'],
        'tiefe-rumpfmuskulatur' => ['abs', 'obliques', 'lower_back'],
        'hueftbeuger' => ['hip_flexors'],
        'brust' => ['chest'],
        'schultern' => ['front_delts', 'side_delts', 'rear_delts'],
        'vordere-schultern' => ['front_delts'],
        'seitliche-schultern' => ['side_delts'],
        'hintere-schultern' => ['rear_delts'],
        'bizeps' => ['biceps'],
        'trizeps' => ['triceps'],
        'unterarme' => ['forearms'],
        'ruecken' => ['traps', 'upper_back', 'lats', 'lower_back'],
        'oberer-ruecken' => ['upper_back', 'traps'],
        'oberer-trapezmuskel' => ['traps'],
        'latissimus' => ['lats'],
        'rueckenstrecker' => ['lower_back'],
        'gesaess' => ['glutes'],
        'beine' => ['quads', 'hamstrings', 'calves', 'glutes'],
        'quadrizeps' => ['quads'],
        'beinbeuger' => ['hamstrings'],
        'waden' => ['calves'],
    ],

];
