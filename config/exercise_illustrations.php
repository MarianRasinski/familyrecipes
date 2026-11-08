<?php

/*
|--------------------------------------------------------------------------
| Übungsbilder (Strichfiguren, Start → Ende)
|--------------------------------------------------------------------------
|
| Je Übung (Slug) eine Liste von Bildern; jedes Bild ist eine Pose aus Gelenkpunkten in einem
| 120 × 112 großen Feld (Boden bei y = 94, Blick nach rechts). Gezeichnet von
| resources/views/components/exercise-illustration.blade.php.
|
|   head, s (Schulter), h (Hüfte)
|   e1/w1 = Ellbogen/Hand vorne, k1/a1/t1 = Knie/Knöchel/Zehen vorne
|   e2/w2, k2/a2/t2 = hinterer Arm/hinteres Bein (heller); fehlen sie, liegen sie knapp hinter den vorderen
|   front = true: Ansicht von vorne (Arme/Beine links und rechts statt vorne/hinten)
|   props: [['kb', x, y]] Kettlebell (Griff bei x/y), ['db', x, y] Kurzhantel, ['handles', x] Liegestützgriff
|   scene: 'mat' (Matte) oder 'rower' (Rudergerät)
|   label: Beschriftung unter dem Bild (Standard: Start/Ende)
|
| Neue Übung ohne Eintrag → kein Bild (die Seite zeigt dann nur Text und Muskelkarte).
|
*/

$standing = ['head' => [60, 14], 's' => [60, 26], 'h' => [60, 56], 'k1' => [61, 75], 'a1' => [60, 93], 't1' => [68, 93]];

$lying = ['head' => [20, 86], 's' => [30, 88], 'h' => [58, 88], 'k1' => [72, 72], 'a1' => [78, 92], 't1' => [85, 92]];

$rowerCatch = ['head' => [55, 45], 's' => [50, 56], 'h' => [40, 80], 'k1' => [56, 62], 'a1' => [70, 80], 't1' => [74, 74],
    'e1' => [62, 62], 'w1' => [74, 66], 'scene' => 'rower', 'label' => 'Auslage'];
$rowerFinish = ['head' => [17, 45], 's' => [20, 57], 'h' => [28, 80], 'k1' => [48, 77], 'a1' => [68, 80], 't1' => [72, 74],
    'e1' => [14, 70], 'w1' => [28, 66], 'scene' => 'rower', 'label' => 'Endzug'];

return [

    'goblet-squat' => [
        [...$standing, 'e1' => [65, 38], 'w1' => [66, 30], 'props' => [['kb', 67, 30]]],
        ['head' => [63, 34], 's' => [60, 46], 'h' => [48, 72], 'k1' => [66, 72], 'a1' => [62, 93], 't1' => [70, 93],
            'e1' => [67, 58], 'w1' => [68, 50], 'props' => [['kb', 69, 50]]],
    ],

    'kettlebell-swing' => [
        ['head' => [79, 34], 's' => [70, 41], 'h' => [48, 56], 'k1' => [56, 74], 'a1' => [56, 93], 't1' => [64, 93],
            'e1' => [64, 55], 'w1' => [58, 68], 'props' => [['kb', 57, 68]]],
        [...$standing, 'k1' => [60, 75], 'e1' => [76, 27], 'w1' => [91, 28], 'props' => [['kb', 94, 28]]],
    ],

    'kettlebell-deadlift' => [
        ['head' => [74, 37], 's' => [66, 44], 'h' => [46, 62], 'k1' => [62, 70], 'a1' => [58, 93], 't1' => [66, 93],
            'e1' => [64, 61], 'w1' => [61, 78], 'props' => [['kb', 61, 78]]],
        [...$standing, 'e1' => [61, 42], 'w1' => [62, 58], 'props' => [['kb', 62, 58]]],
    ],

    'kettlebell-reverse-lunge' => [
        [...$standing, 'e1' => [65, 38], 'w1' => [66, 30], 'props' => [['kb', 67, 30]]],
        ['head' => [57, 28], 's' => [57, 40], 'h' => [56, 70], 'k1' => [72, 72], 'a1' => [71, 93], 't1' => [79, 93],
            'k2' => [46, 88], 'a2' => [29, 91], 't2' => [27, 94],
            'e1' => [62, 52], 'w1' => [63, 44], 'props' => [['kb', 64, 44]]],
    ],

    'kettlebell-clean-and-press' => [
        [...$standing, 'e1' => [63, 41], 'w1' => [65, 28], 'e2' => null, 'props' => [['kb', 67, 27]], 'label' => 'Rack-Position'],
        [...$standing, 'e1' => [63, 11], 'w1' => [64, -4], 'e2' => null, 'props' => [['kb', 65, -5]], 'label' => 'Über Kopf'],
    ],

    'kettlebell-floor-press' => [
        [...$lying, 'e1' => [38, 91], 'w1' => [38, 76], 'scene' => 'mat', 'props' => [['kb', 38, 76]]],
        [...$lying, 'e1' => [31, 73], 'w1' => [32, 58], 'scene' => 'mat', 'props' => [['kb', 32, 58]]],
    ],

    'kurzhantel-bizepscurls' => [
        [...$standing, 'e1' => [61, 42], 'w1' => [63, 58], 'props' => [['db', 63, 58]]],
        [...$standing, 'e1' => [61, 42], 'w1' => [70, 30], 'props' => [['db', 70, 30]]],
    ],

    'kurzhantel-schulterdruecken' => [
        [...$standing, 'e1' => [57, 40], 'w1' => [60, 26], 'props' => [['db', 60, 25]]],
        [...$standing, 'e1' => [61, 11], 'w1' => [62, -4], 'props' => [['db', 62, -5]]],
    ],

    'kurzhantel-rudern' => [
        ['head' => [78, 33], 's' => [68, 40], 'h' => [46, 56], 'k1' => [52, 74], 'a1' => [52, 93], 't1' => [60, 93],
            'e1' => [68, 56], 'w1' => [68, 71], 'e2' => [66, 54], 'w2' => [58, 64], 'props' => [['db', 68, 71]]],
        ['head' => [78, 33], 's' => [68, 40], 'h' => [46, 56], 'k1' => [52, 74], 'a1' => [52, 93], 't1' => [60, 93],
            'e1' => [55, 48], 'w1' => [57, 63], 'e2' => [66, 54], 'w2' => [58, 64], 'props' => [['db', 57, 63]]],
    ],

    'kurzhantel-romanian-deadlift' => [
        [...$standing, 'e1' => [61, 42], 'w1' => [62, 58], 'props' => [['db', 62, 58]]],
        ['head' => [78, 44], 's' => [68, 48], 'h' => [44, 58], 'k1' => [54, 75], 'a1' => [56, 93], 't1' => [64, 93],
            'e1' => [66, 64], 'w1' => [64, 79], 'props' => [['db', 64, 79]]],
    ],

    'kurzhantel-floor-press' => [
        [...$lying, 'e1' => [38, 91], 'w1' => [38, 76], 'scene' => 'mat', 'props' => [['db', 38, 76]]],
        [...$lying, 'e1' => [31, 73], 'w1' => [32, 58], 'scene' => 'mat', 'props' => [['db', 32, 58]]],
    ],

    'kurzhantel-seitheben' => [
        [...$standing, 'front' => true, 'k1' => [64, 75], 'a1' => [65, 93], 't1' => [69, 93], 'k2' => [56, 75], 'a2' => [55, 93], 't2' => [51, 93],
            'e1' => [70, 41], 'w1' => [71, 56], 'e2' => [50, 41], 'w2' => [49, 56], 'props' => [['db', 71, 57], ['db', 49, 57]]],
        [...$standing, 'front' => true, 'k1' => [64, 75], 'a1' => [65, 93], 't1' => [69, 93], 'k2' => [56, 75], 'a2' => [55, 93], 't2' => [51, 93],
            'e1' => [83, 27], 'w1' => [98, 28], 'e2' => [37, 27], 'w2' => [22, 28], 'props' => [['db', 99, 28], ['db', 21, 28]]],
    ],

    'liegestuetze' => [
        ['head' => [88, 62], 's' => [78, 67], 'h' => [50, 78], 'k1' => [36, 85], 'a1' => [22, 91], 't1' => [18, 94],
            'e1' => [78, 80], 'w1' => [78, 92], 'props' => [['handles', 78]], 'label' => 'Oben'],
        ['head' => [89, 79], 's' => [78, 83], 'h' => [50, 86], 'k1' => [36, 89], 'a1' => [22, 91], 't1' => [18, 94],
            'e1' => [64, 86], 'w1' => [78, 92], 'props' => [['handles', 78]], 'label' => 'Unten'],
    ],

    'plank' => [
        ['head' => [88, 72], 's' => [78, 77], 'h' => [50, 81], 'k1' => [35, 86], 'a1' => [20, 90], 't1' => [16, 94],
            'e1' => [78, 92], 'w1' => [92, 92], 'scene' => 'mat', 'label' => 'Position halten'],
    ],

    'russian-twist' => [
        ['head' => [33, 53], 's' => [38, 64], 'h' => [50, 88], 'k1' => [66, 72], 'a1' => [80, 82], 't1' => [86, 80],
            'e1' => [48, 74], 'w1' => [60, 70], 'scene' => 'mat', 'props' => [['kb', 61, 70]], 'label' => 'Zur einen Seite'],
        ['head' => [33, 53], 's' => [38, 64], 'h' => [50, 88], 'k1' => [66, 72], 'a1' => [80, 82], 't1' => [86, 80],
            'e1' => [40, 78], 'w1' => [52, 86], 'scene' => 'mat', 'props' => [['kb', 53, 86]], 'label' => 'Zur anderen Seite'],
    ],

    'glute-bridge' => [
        ['head' => [20, 86], 's' => [30, 88], 'h' => [56, 88], 'k1' => [70, 72], 'a1' => [76, 92], 't1' => [83, 92],
            'e1' => [44, 91], 'w1' => [58, 92], 'scene' => 'mat'],
        ['head' => [20, 85], 's' => [30, 87], 'h' => [54, 72], 'k1' => [70, 70], 'a1' => [76, 92], 't1' => [83, 92],
            'e1' => [44, 91], 'w1' => [58, 92], 'scene' => 'mat'],
    ],

    'bird-dog' => [
        ['head' => [87, 63], 's' => [76, 68], 'h' => [46, 70], 'k1' => [46, 89], 'a1' => [28, 91], 't1' => [25, 94],
            'e1' => [76, 80], 'w1' => [76, 92], 'scene' => 'mat'],
        ['head' => [87, 63], 's' => [76, 68], 'h' => [46, 70], 'k1' => [46, 89], 'a1' => [28, 91], 't1' => [25, 94],
            'k2' => [28, 70], 'a2' => [10, 70], 't2' => [7, 72],
            'e1' => [91, 66], 'w1' => [106, 64], 'e2' => [76, 80], 'w2' => [76, 92], 'scene' => 'mat'],
    ],

    'rudermaschine-intervalltraining' => [$rowerCatch, $rowerFinish],

    'rudermaschine-grundlagenausdauer' => [$rowerCatch, $rowerFinish],

    'dead-bug' => [
        ['head' => [20, 84], 's' => [30, 86], 'h' => [58, 86], 'k1' => [58, 68], 'a1' => [76, 68], 't1' => [79, 63],
            'e1' => [31, 72], 'w1' => [32, 57], 'scene' => 'mat'],
        ['head' => [20, 84], 's' => [30, 86], 'h' => [58, 86], 'k1' => [58, 68], 'a1' => [76, 68], 't1' => [79, 63],
            'k2' => [76, 82], 'a2' => [94, 87], 't2' => [97, 83],
            'e1' => [16, 87], 'w1' => [2, 88], 'e2' => [31, 72], 'w2' => [32, 57], 'scene' => 'mat'],
    ],

];
