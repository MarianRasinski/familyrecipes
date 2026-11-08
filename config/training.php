<?php

/*
|--------------------------------------------------------------------------
| Trainingsplan
|--------------------------------------------------------------------------
|
| Standardwerte für neue Übungen in einer Einheit und für die Vorlage „Ganzkörper“.
|
*/

return [

    // Pause zwischen Sätzen, wenn nichts eingetragen ist (auch für die Dauer-Schätzung)
    'default_rest_seconds' => 60,

    // Sätze, wenn die Übung im Katalog keine Vorgabe hat
    'default_sets' => 3,

    // Vorlage „Ganzkörper“: höchstens so viele Übungen, möglichst verschiedene Hauptmuskelgruppen
    'template_max_exercises' => 8,

];
