<?php

/*
|--------------------------------------------------------------------------
| Nährwertberechnung
|--------------------------------------------------------------------------
|
| Annahmen für die Umrechnung von Rezeptangaben in Gramm und für die automatische
| Zuordnung von Zutaten zu Lebensmitteln des Bundeslebensmittelschlüssels (BLS).
| Alle Gewichte außer g/kg/ml/l sind Durchschnittswerte → Ergebnis gilt als „geschätzt“.
| Schlüssel immer kleingeschrieben.
|
*/

return [

    /*
     * Gewicht je Einheit in Gramm. `by_category` überschreibt den Standardwert für eine
     * Lebensmittelgruppe (Buchstabe des BLS-Codes, z. B. Q = Fette & Öle, C = Getreide).
     * `exact` = keine Schätzung (Gewichts- und Volumenangaben).
     */
    'units' => [
        'g' => ['grams' => 1, 'exact' => true],
        'gramm' => ['grams' => 1, 'exact' => true],
        'gr' => ['grams' => 1, 'exact' => true],
        'kg' => ['grams' => 1000, 'exact' => true],
        'ml' => ['grams' => 1, 'exact' => true, 'by_category' => ['Q' => 0.92]],
        'l' => ['grams' => 1000, 'exact' => true, 'by_category' => ['Q' => 920]],
        'liter' => ['grams' => 1000, 'exact' => true, 'by_category' => ['Q' => 920]],
        'el' => ['grams' => 15, 'by_category' => ['Q' => 10, 'C' => 10, 'R' => 8]],
        'esslöffel' => ['grams' => 15, 'by_category' => ['Q' => 10, 'C' => 10, 'R' => 8]],
        'tl' => ['grams' => 5, 'by_category' => ['Q' => 4, 'C' => 3, 'R' => 3]],
        'teelöffel' => ['grams' => 5, 'by_category' => ['Q' => 4, 'C' => 3, 'R' => 3]],
        'prise' => ['grams' => 0.5],
        'msp' => ['grams' => 0.5],
        'messerspitze' => ['grams' => 0.5],
        'tasse' => ['grams' => 150],
        'becher' => ['grams' => 200],
        'dose' => ['grams' => 240], // Abtropfgewicht einer 400-g-Dose
        'scheibe' => ['grams' => 20],
        'scheiben' => ['grams' => 20],
        'bund' => ['grams' => 30],
        'handvoll' => ['grams' => 30],
        'blatt' => ['grams' => 10],
        'blätter' => ['grams' => 10],
    ],

    /*
     * Einheiten, die „ein Stück von etwas“ meinen – Gewicht kommt aus `piece_weights`.
     * Größenangaben verändern das Stückgewicht.
     */
    'piece_units' => ['', 'stück', 'stk', 'stk.', 'zehe', 'zehen', 'kopf', 'stange', 'stangen', 'rolle', 'block', 'päckchen', 'pck', 'pck.', 'packung', 'klein', 'kleine', 'mittelgroß', 'mittelgroße', 'groß', 'große'],

    'size_factors' => [
        'klein' => 0.7, 'kleine' => 0.7, 'kleiner' => 0.7,
        'groß' => 1.3, 'große' => 1.3, 'großer' => 1.3,
    ],

    /*
     * Durchschnittliches Stückgewicht (essbarer Anteil) nach Stichwort im Zutaten- oder Lebensmittelnamen.
     * Längere Stichwörter gewinnen („kirschtomate“ vor „tomate“).
     */
    'piece_weights' => [
        'ei' => 60, 'eier' => 60, 'hühnerei' => 60,
        'zwiebel' => 80, 'frühlingszwiebel' => 20, 'lauchzwiebel' => 20, 'schalotte' => 30,
        'knoblauch' => 4, 'knoblauchzehe' => 4,
        'zitrone' => 100, 'limette' => 60, 'orange' => 180,
        'tomate' => 100, 'kirschtomate' => 15, 'cherrytomate' => 15, 'cocktailtomate' => 15,
        'paprika' => 160, 'zucchini' => 250, 'aubergine' => 300, 'gurke' => 400, 'gewürzgurke' => 30,
        'brokkoli' => 400, 'broccoli' => 400, 'blumenkohl' => 600, 'salat' => 300, 'salatblatt' => 10, 'salatblätter' => 10,
        'kartoffel' => 120, 'süßkartoffel' => 250, 'batate' => 250,
        'karotte' => 80, 'möhre' => 80, 'sellerie' => 50, 'staudensellerie' => 50, 'bleichsellerie' => 50, 'lauch' => 200,
        'apfel' => 150, 'banane' => 120, 'birne' => 150, 'avocado' => 150, 'champignon' => 20,
        'wrap' => 60, 'tortilla' => 60, 'brötchen' => 60, 'toast' => 25, 'pizzateig' => 400,
        'tofu' => 200, 'mozzarella' => 125, 'feta' => 200,
        'hähnchenbrust' => 150, 'hähnchenbrustfilet' => 150, 'putenschnitzel' => 120, 'putenbrust' => 150,
        'puddingpulver' => 37, 'backpulver' => 15, 'vanillezucker' => 8, 'trockenhefe' => 7, 'hefe' => 42,
    ],

    /*
     * Zutatennamen (kleingeschrieben, ohne Klammern) → BLS-Code. Wird vor der freien Suche geprüft.
     */
    'synonyms' => [
        'ei' => 'E111100', 'eier' => 'E111100', 'hühnerei' => 'E111100',
        'haferflocken' => 'C133000', 'zarte haferflocken' => 'C133000', 'kernige haferflocken' => 'C133000',
        'hackfleisch' => 'U050100', 'hack' => 'U050100', 'gemischtes hackfleisch' => 'U050100',
        'rinderhack' => 'U010100', 'rinderhackfleisch' => 'U010100',
        'magerquark' => 'M713100', 'quark' => 'M713100', 'skyr' => 'M710100',
        'naturjoghurt' => 'M141300', 'joghurt' => 'M141300', 'griechischer joghurt' => 'M141500',
        'körniger frischkäse' => 'M711100', 'hüttenkäse' => 'M711100', 'frischkäse' => 'M710700',
        'feta' => 'M012200', 'schafskäse' => 'M012200', 'cheddar' => 'M303600', 'parmesan' => 'M306400',
        'mozzarella' => 'M032100', 'gouda' => 'M402600', 'reibekäse' => 'M402600', 'geriebener käse' => 'M402600',
        'butter' => 'Q630000', 'margarine' => 'Q400000',
        'olivenöl' => 'Q120000', 'öl' => 'Q180000', 'rapsöl' => 'Q180000', 'sonnenblumenöl' => 'Q320000',
        'tofu' => 'H861000', 'naturtofu' => 'H861000', 'natur-tofu' => 'H861000',
        'kartoffel' => 'K110100', 'kartoffeln' => 'K110100', 'süßkartoffel' => 'K420100', 'süßkartoffeln' => 'K420100',
        'brokkoli' => 'G312100', 'broccoli' => 'G312100', 'paprika' => 'G543100', 'zucchini' => 'G582100',
        'tomate' => 'G561100', 'tomaten' => 'G561100', 'kirschtomaten' => 'G561100', 'cherrytomaten' => 'G561100',
        'zwiebel' => 'G480100', 'zwiebeln' => 'G480100', 'rote zwiebel' => 'G480100',
        'frühlingszwiebel' => 'G482100', 'frühlingszwiebeln' => 'G482100',
        'knoblauch' => 'G490100', 'knoblauchzehe' => 'G490100', 'knoblauchzehen' => 'G490100', 'knoblauchpulver' => 'G490500',
        'zitrone' => 'F601100', 'zitronensaft' => 'F601600',
        'karotte' => 'G620100', 'karotten' => 'G620100', 'möhre' => 'G620100', 'möhren' => 'G620100',
        'gurke' => 'G520100', 'gewürzgurken' => 'G520802', 'gewürzgurke' => 'G520802',
        'staudensellerie' => 'G220100', 'stangensellerie' => 'G220100',
        'salat' => 'G105100', 'salatblätter' => 'G105100', 'kopfsalat' => 'G105100',
        'champignons' => 'K701100', 'spinat' => 'G211100', 'avocado' => 'F502100',
        'banane' => 'F503100', 'apfel' => 'F110100', 'oliven' => 'H510802',
        'wrap' => 'B783012', 'wraps' => 'B783012', 'vollkorn-wraps' => 'B783012', 'tortilla' => 'B783012', 'tortillas' => 'B783012',
        'pizzateig' => 'D079000', 'puddingpulver' => 'R481100',
        'hähnchenbrust' => 'V416100', 'hähnchenbrustfilet' => 'V416100', 'hähnchen' => 'V416100',
        'putenbrust' => 'V486100', 'putenschnitzel' => 'V486100',
        'thunfisch' => 'T121100', 'lachs' => 'T410100',
        'sojasauce' => 'R143000', 'sojasoße' => 'R143000', 'sesam' => 'H420100',
        'zucker' => 'S111000', 'mehl' => 'C214100', 'weizenmehl' => 'C214100',
        'milch' => 'M113300', 'sahne' => 'M173800', 'schlagsahne' => 'M173800', 'schmand' => 'M172700',
        'vegane sahne' => 'H845000', 'reis' => 'C352000', 'nudeln' => 'E401000', 'spaghetti' => 'E401000', 'pasta' => 'E401000',
        'honig' => 'S120000', 'erdnussbutter' => 'H880200', 'kokosmilch' => 'H154000', 'passierte tomaten' => 'R161200',
        'petersilie' => 'G250100', 'basilikum' => 'G061000', 'schnittlauch' => 'G081100',
        'salz' => 'R111000', 'pfeffer' => 'R258100', 'schwarzer pfeffer' => 'R258100',
    ],

    /*
     * Wörter, die für die Zuordnung keine Rolle spielen und vorab entfernt werden.
     */
    'ignore_words' => ['frisch', 'frische', 'frischer', 'frisches', 'tk', 'bio', 'natur', 'gehackt', 'gehackte', 'fein', 'grob',
        'klein', 'kleine', 'groß', 'große', 'mittelgroß', 'mittelgroße', 'rote', 'roter', 'gelbe', 'grüne', 'weiße', 'reife', 'reifer'],

    /*
     * Gewürze & Kräuter: ohne Lebensmittel-Zuordnung oder ohne Menge vernachlässigbar (kaum Kalorien).
     * Für sie gibt es nur Vorschläge aus `synonyms`, keine freie Suche („Dill“ ≠ „Krabben in Dillcreme“).
     */
    'negligible' => ['salz', 'pfeffer', 'gewürzmischung', 'gewürzsalz', 'muskat', 'chili', 'paprikapulver', 'oregano', 'thymian', 'rosmarin',
        'kräuter', 'dill', 'msg', 'glutamat', 'zimt', 'kreuzkümmel', 'curry', 'kurkuma', 'flavor', 'aroma', 'süßstoff', 'backpulver', 'natron'],

    /*
    |--------------------------------------------------------------------------
    | Tagesbudget (Profil & Ziele, App\Support\Nutrition\EnergyBudget)
    |--------------------------------------------------------------------------
    |
    | Grundumsatz nach Mifflin-St Jeor × PAL (Alltag ohne Sport) = Gesamtbedarf am Ruhetag,
    | ± Ziel-Zuschlag = Zielkalorien. An Trainingstagen kommt der Trainingsverbrauch dazu:
    | (MET − 1) × kg × Stunden (netto, der Grundumsatz ist schon enthalten).
    | Orientierungswerte für gesunde Erwachsene – keine medizinische Beratung.
    |
    */
    'budget' => [

        // PAL-Werte angelehnt an die DGE-Referenzwerte (Sport ist hier bewusst nicht enthalten)
        'pal' => [
            'sedentary' => 1.4,
            'light' => 1.6,
            'active' => 1.8,
            'heavy' => 2.0,
        ],

        // kcal-Zuschlag pro Tag und Eiweiß in g je kg (Bezugs-)Körpergewicht
        'goals' => [
            'lose' => ['kcal' => -500, 'protein_per_kg' => 2.0],     // ≈ 0,5 kg weniger pro Woche
            'maintain' => ['kcal' => 0, 'protein_per_kg' => 1.6],
            'gain' => ['kcal' => 300, 'protein_per_kg' => 1.8],
        ],

        // Eiweiß wird bei einem BMI darüber auf das Gewicht bei diesem BMI bezogen (sonst zu hoch)
        'protein_reference_bmi' => 25,

        // Anteil der Kalorien aus Fett; Kohlenhydrate bekommen den Rest
        'fat_share' => 0.30,

        // Ballaststoffe in g pro Tag (DGE-Richtwert)
        'fiber' => 30,

        // MET-Werte je Trainingsart (Compendium of Physical Activities, gerundet)
        'training_met' => [
            'strength' => 5.0,
            'mixed' => 6.0,
            'cardio' => 7.0,
        ],

        // Kalorienziele werden auf diese Schrittweite gerundet
        'round_to' => 10,

        // Wochenplaner: ab so viel über dem Tagesbudget gibt es einen Hinweis (0.10 = 10 %)
        'over_tolerance' => 0.10,
    ],

];
