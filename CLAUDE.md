# Familien-Rezeptbuch (Fork: Fitness- & Ernährungsplaner) – Hinweise für Claude

Laravel-12-App (Blade, Tailwind 4, Alpine.js) für eine Familie im Heimnetz. UI, Code-Kommentare,
Commit-Messages und Antworten an den Nutzer: **Deutsch**. Details zu Funktionen stehen in `README.md`,
der Ausbauplan in **`TODO.md`**.

## Dieser Fork

- Fork von https://github.com/tianvay/familyrecipes, Remote: **https://github.com/MarianRasinski/familyrecipes**
  (Branch `master`). Ziel: Ausbau zum **Fitness- und Ernährungs-Wochenplaner** (Nährwerte, Ziele,
  Training, Meal Prep) – Schritte und Stand in `TODO.md`.
- **Jedes Familienmitglied bekommt einen eigenen Plan mit eigenen Zielen** (Entscheidung 08.11.2026).
  Neue Tabellen für Planung/Ziele/Training daher mit `user_id`; der bisherige Familien-Essensplan
  (ein Gericht pro Tag) bleibt bis zur Ablösung bestehen.
- **Profile/Kalorienbudgets vorerst nur für Erwachsene** (Kinder: Plan ohne Kalorienziel; Erwachsenen-Formeln passen
  nicht). **Jeder sieht nur sein eigenes Profil und Budget** – auch der Admin nicht die der anderen (Entscheidung 08.11.2026).
- Datenbank des Forks: **lokale MariaDB (XAMPP) auf `127.0.0.1`** (eigene Instanz, getrennt von der Familien-DB
  des Originals auf 192.168.178.38 – diese nie verwenden).
- Der Abschnitt „Arbeitsweise“ (Accounts, Geräte) stammt aus dem Original und ist für diesen Fork noch
  nicht überprüft; „Umgebung“ beschreibt den Fork-PC.
- Pflicht nach jeder Änderung: **README.md, TODO.md und CLAUDE.md aktuell halten**; Laravel-Konventionen
  (Actions für schreibende Abläufe, Form Requests/Validator, Policies/Gates, Factories + Tests, Pint).

## Arbeitsweise mit dem Nutzer

- Nutzer ist das Familienoberhaupt. 6 echte Accounts (Admin, Papa, Mama = Erwachsene/Admin; Joshua,
  Michelle, Chantal = Kinder). Nur 2 nutzen den Desktop, alle anderen **iPhone/iPad** → mobil zuerst denken.
- **Committen und pushen ohne Nachfrage** (Freigabe des Nutzers 08.11.2026) – nach abgeschlossenen, getesteten
  Schritten. Vorher Diff auf Geheimnisse prüfen (`DB_PASSWORD`,
  `APP_KEY`, keine `storage/app`-/SQLite-Dateien). Themen in getrennte Commits. Branch: `master`,
  Remote: https://github.com/MarianRasinski/familyrecipes (Fork). Commit-Footer: `Co-Authored-By: Claude …`.
- Die Datenbank (lokal, 127.0.0.1) kann echte Daten enthalten – behandeln wie die echte Familien-Datenbank. Testdaten nur auf freien Tagen/klar markiert
  („Claude-Test“), IDs merken und danach gezielt wieder löschen; echte Einträge nie anfassen.
- System-/Sicherheitseinstellungen (Windows-Firewall, Router, Rechte auf dem DB-Server) nicht selbst
  ändern – Befehl/SQL vorbereiten, Nutzer führt aus.
- Nach Features: Tests, Pint, `npm run build`, im Browser (built-in, `http://localhost`) prüfen,
  README ergänzen, danach committen und pushen. Ideenlisten nach Umsetzbarkeit sortieren.

## Umgebung (Fork-PC, Stand 08.11.2026)

- Windows, Rechner `desktop-nl8voeq`, IP `192.168.2.101`, Heimnetz `192.168.2.0/24` (FRITZ!Box).
  Projekt unter `C:\Users\maria\Desktop\CProjects\familyrecipes`.
- XAMPP 8.2.12 unter `C:\xampp`: PHP 8.2 (`C:\xampp\php`, Erweiterungen: pdo_mysql, mbstring, curl,
  fileinfo, zip, openssl …; **ohne GD und intl**), Apache 2.4, MariaDB 10.4. **Node/npm ist absichtlich nicht
  installiert** (Entscheidung des Nutzers) → der fertige Build `public/build` wird bei jeder Design-Änderung mit ins
  Git eingetragen; der PC holt ihn nur per `git pull`. **Das bleibt so** (Entscheidung 08.11.2026 – keinen CI-Build
  vorschlagen). Neue Tailwind-Klassen erst nach einem Build woanders nutzen,
  sonst nur Klassen, die schon in `public/build/assets/*.css` stehen (vorher prüfen).
- Apache: `Listen 80`, VirtualHost in `C:\xampp\apache\conf\extra\familyrecipes-vhost.conf` (eingebunden in
  `httpd-vhosts.conf`, angelegt 29.10.2026) → `public/`, Zugriff nur lokal + `192.168.2.0/24`.
- MariaDB auf `127.0.0.1:3306`, Datenbank `familyrecipes` (enthält echte Rezepte), Benutzer `familyrecipes`.
- Autostart: `scripts/windows/Install-Server.ps1` (als Administrator) richtet die Dienste `Apache2.4` und `mysql`
  (Automatisch, Neustart bei Absturz), PHP im System-PATH und die Firewall-Regel „Rezeptbuch (Apache, Heimnetz)“
  ein; `-Uninstall` macht Dienste/Regel rückgängig. Ob das Skript schon gelaufen ist: `Get-Service Apache2.4, mysql`.
- Kein Shell-Zugriff von Claude auf den PC (nur Dateien): Befehle als Skript/Anleitung vorbereiten, Nutzer führt aus.
- Standard-Admin per Migration: `Admin` / `Test`.
- Kein HTTPS → `navigator.share`, Clipboard-API, Wake Lock, Service Worker nicht verfügbar
  (Fallbacks sind eingebaut). Zeitzone `Europe/Berlin`.

## Befehle

```bash
php artisan test            # SQLite in-memory (phpunit.xml), echte DB bleibt unberührt
vendor/bin/pint             # Code-Stil
npm run build               # nach jeder Änderung an Views/CSS/JS nötig (kein Vite-Dev-Server im Betrieb);
                            # public/build wird mitcommittet (auf dem Fork-PC ist kein Node installiert)
php artisan view:clear
php artisan users:create    # Account per Konsole; recipes:import = alte recipes.json importieren
php artisan exercises:import # Übungskatalog aus database/data/exercises/*.json (wiederholbar)
php artisan foods:import     # 7.140 Lebensmittel aus database/data/foods/bls_4_0.csv (wiederholbar, Upsert per BLS-Code)
```

## Aufbau

- `app/Actions/` – schreibende Abläufe: `SaveRecipe` (inkl. Änderungsprotokoll), `DeleteRecipe`,
  `SaveMealPlan`, `ImportRecipeFromUrl`, `ImportExercises`, `ImportFoods`, `SaveIngredientNutrition`, `SaveBodyProfile`,
  `AddPlannedMeal`, `ToggleTrainingDay`.
- `app/Support/` – Domänenlogik: `Week` (ISO-Woche, Route-Binding `{week}` = `2026-W43`),
  `WishList` (Wunsch offen bis im Plan; „erfüllt“ wird berechnet), `ShoppingList`/`ShoppingListItem`/
  `Quantity` (Mengen addieren), `CookingSteps` (Zutaten/Timer je Schritt), `LastEaten`,
  `RecipeImport/` (`JsonLdRecipeParser`, `IngredientLineParser`, `UrlGuard` gegen Heimnetz-Abrufe),
  `Training/ExerciseTarget` (Zielvorgabe „10-12 je Seite“/„30-60 Sekunden“ → Messart, Spanne, pro Seite).
- Übungskatalog: `Exercise` ↔ `Equipment` (Gerätetyp mit `variants` + `is_available`, Pivot
  `EquipmentExercise.is_optional`) und `MuscleGroup` (Pivot `ExerciseMuscleGroup.is_primary`).
  Enums `ExerciseLevel`, `ExerciseMeasure`. Import-Schlüssel `source` (Dateiname) + `external_id`.
  Quelldatei des Nutzers: `TrainJ/Unbenannt.txt` → Kopie unter `database/data/exercises/ganzkoerper.json`.
- Nährwerte: `Food` (Tabelle `foods`, Werte je 100 g, **null = keine Daten ≠ 0**, `category` = `FoodCategory` aus dem
  ersten Buchstaben des BLS-Codes), `Support/Nutrition/Nutrients` (kcal/Eiweiß/Fett/KH/Ballaststoffe, `estimated`-Flag
  bleibt beim Addieren erhalten). BLS-Originaldateien des Nutzers: `TrainJ/BLS_4_0_2025_DE/` (nicht im Git);
  daraus erzeugt `scripts/bls/convert.py` die CSV. Quellenangabe „Max Rubner-Institut (2025): BLS 4.0“ anzeigen.
- Rezept-Nährwerte: `ingredients.food_id` + `ingredients.grams` (eigenes Gewicht der ganzen Menge, sonst Schätzung).
  `IngredientWeight` (Menge+Einheit → Gramm, `WeightEstimate`), `FoodMatcher` (Vorschlag: Synonyme → Suche; Gewürze nur
  Synonyme), `IngredientNutrition` (Status counted/optional/negligible/no_food/no_weight), `RecipeNutrition`
  (`$recipe->nutrition()`, braucht `ingredients.food` geladen). Alle Annahmen in `config/nutrition.php`.
  `SaveRecipe` übernimmt Zuordnungen gleichnamiger Zutaten. Zuordnungen stehen nicht im Änderungsprotokoll.
  Handangaben `recipes.calories/protein` werden nur angezeigt, solange nichts berechnet ist.
- Profil & Ziele: `BodyProfile` (Tabelle `body_profiles`, `user_id` unique, `$user->bodyProfile`), Enums `Sex`,
  `ActivityLevel` (PAL), `FitnessGoal`, `TrainingType` (MET) – Zahlen in `config/nutrition.php` (`budget`).
  `Support/Nutrition/EnergyBudget` (`$profile->budget($date)`): Ruhetag/Trainingstag/Wochenschnitt, Makros als
  `Nutrients`, `steps()` = Rechenweg, `warnings()`. Gate `manage-body-profile` (alle außer Kinder); Route `/profil`
  ohne Benutzer-Parameter → immer nur das eigene Profil. Action `SaveBodyProfile` (über die Beziehung, `user_id` nicht fillable).
- Mein Plan (persönlicher Wochenplaner, `/mein-plan/{week?}`): `PlannedMeal` (Rezept+`servings` oder Food+`grams`,
  Enum `MealSlot`, `position` je Tag+Mahlzeit, Scope `withDetails()` lädt `recipe.ingredients.food` + `food`,
  `nutrients()` = null wenn Rezept ohne Nährwerte), `TrainingDay` (Zeile = Trainingstag). `Support/Planning/WeekPlan::for($user, $week)`
  → `DayPlan` je Tag (Summe, Ziel aus `EnergyBudget`, `remainingKcal()`, `isOverBudget()` mit `budget.over_tolerance`).
  Routen `{day}` = `2026-11-09`; alles ohne Benutzer-Parameter, `PlannedMealPolicy` = nur Besitzer. Für alle Rollen.
  Startseite „Mein Tag“ (`home/partials/my-day.blade.php`): `HomeController` übergibt `myPlan`/`myDay`/`slots`;
  Ringe über `<x-progress-ring>` (SVG, Farben per `var(--color-…)` → keine neuen Tailwind-Klassen nötig).
  Lebensmittelsuche (`foods.search`) setzt Treffer aus `nutrition.synonyms` an den Anfang („Haferflocken“ → „Hafer Flocken“).
- Rechte: `App\Enums\Role` (admin/adult/child), Policies `RecipePolicy`, `UserPolicy`,
  `MealSuggestionPolicy`, Gates in `AppServiceProvider` (`make-wishes`, `plan-meals`, `view-shopping-list`,
  `view-family-favorites`, `view-revision-log`, `manage-body-profile`). Admin-Account = reine Verwaltung: wünscht nicht,
  fehlt in der Familien-Übersicht (Papa/Mama sind `adult`). Familien-Seite: alle Erwachsenen;
  Rezepte löschen, Benutzerverwaltung und Änderungsprotokoll: nur Admin.
- Model `MealSuggestion` = ein **Wunsch** (UI sagt „Wunsch/wünschen“, Tabelle heißt `meal_suggestions`).
- Frontend: Alpine-Komponenten in `resources/js/*.js`, registriert in `app.js` (`shoppingList`,
  `cookingMode`, `recipePicker`, `recipeAppearance`). Layouts: `components/layouts/{app,guest,focus}`,
  gemeinsamer `<head>` in `partials/head.blade.php`.
- Tests: `travelTo('2026-10-14 18:00')` (Mittwoch, KW 42) für Wochenlogik; Factory-States
  `User::factory()->admin()/child()`, `Recipe::factory()->complete()`.

## Stolpersteine

- `Model::shouldBeStrict()` ist aktiv: kein Lazy Loading (immer `with()`), Mass-Assignment strikt
  (z. B. kein `firstOrCreate(['key' => …])` bei berechneten Spalten).
- Datumsspalten nur mit `whereDate`/`betweenDates` vergleichen (SQLite speichert `Y-m-d H:i:s`).
- Blade: nach einem einzeiligen `@php(...)` keinen `@php … @endphp`-Block mehr in derselben Datei.
- `@js(...)` kodiert Umlaute als `\u00fc` – in Tests View-Daten (`assertViewHas`) statt Text prüfen.
- Route `rezepte/importieren` muss vor `Route::resource('rezepte')` stehen.
- Slugs mit `language: 'de'`: „Gesäß“ → `gesaess` (in Tests beachten).
- Pivot-Flags (`is_optional`, `is_primary`) nur über die Pivot-Klassen lesen, sonst kommt `1` statt `true`.
- Laravel hält „food“ für unzählbar → `Food::$table = 'foods'` ist nötig.
- Zuordnungsassistent nutzt Inline-Alpine (`x-data` im Blade, kein neues JS-Modul) → kein Build nötig.
- `DatabaseSeeder` importiert alle 7.140 Lebensmittel (Tests mit Seeder dauern dadurch ~1 s länger).
- Bash-Heredocs mit Backslashes (PHP-Namespaces) werden zerschossen → Edit/Write-Tool oder
  Python-Skript als Datei im Scratchpad nutzen.
- Keine GD: Fotos werden im Browser verkleinert (`recipe-appearance.js`), Server-Limit 12 MB.
- Fotos liegen in der DB (`recipe_images`, nur über `$recipe->image()` laden, `storeImage()`/`removeImage()`);
  `recipes.image_hash` = vorhanden/Cache-Schlüssel. `image_path` ist ein Überbleibsel aus der Zeit vor der
  DB-Speicherung, nur noch für `recipes:move-images`. Seit 19.10.2026 sind alle Fotos übernommen (beide
  Installationen) → `image_path` kann per Migration entfernt werden. MariaDB `max_allowed_packet` ≥ 32M.

## Offene Ideen aus dem Original (nach Umsetzbarkeit)

Der Ausbauplan des Forks steht in `TODO.md`.

1. Portionen umrechnen auf der Rezeptseite (`Quantity` vorhanden)
2. Rezeptauswahl mit Suche/Favoriten auch im Planungsformular (heute `<select>`)
3. Bewertung nach dem Essen („Wie war’s?“, auch Kinder) → Grundlage für 4
4. Statistik: „oft gewünscht, selten gekocht“, Lieblingsgerichte je Person (Daten: `meal_suggestions`,
   `meal_plan_entries`, Erfüllung via `WishList`) – vom Nutzer gewünscht, Details noch besprechen
5. Essensplan als Kalender-Abo (iCal) für iPhone-Kalender
6. Abneigungen/Allergien pro Person mit Warnung beim Planen
7. Küchendienst/Tischdienst pro Tag
8. Größer/Infrastruktur: VPN über FRITZ!Box, gemeinsame Live-Einkaufsliste, HTTPS im Heimnetz
   (eigene CA auf 5 Geräten), Offline/Push

Noch nicht auf echtem iPhone/iPad verifiziert: Wunschliste in Handygröße, Timer-Signalton,
Kamera-Upload, Home-Bildschirm-App. Bekannte Grenze: „Ei“/„Eier“ werden auf der Einkaufsliste nicht
zusammengefasst.
