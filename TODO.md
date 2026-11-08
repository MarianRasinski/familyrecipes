# TODO – Ausbau zum Fitness- & Ernährungs-Wochenplaner

Stand: 08.11.2026 (zuletzt ergänzt: Wünsche zu Planer, Tagesbudget, Trainingsplan). Reihenfolge = geplante Umsetzung. Jeder Schritt ist für sich nutzbar und bekommt Tests.
Grundsatz: **Jedes Familienmitglied hat einen eigenen Plan und eigene Ziele** (neue Tabellen mit `user_id`).
Alle Berechnungen sind Orientierungswerte und zeigen ihren Rechenweg.

## Erledigt

- [x] **Fork-Grundlagen:** lokale Datenbank (127.0.0.1, getrennt von der Familien-DB des Originals),
      `.env.example`, README/CLAUDE.md an den Fork angepasst, dieses TODO angelegt.
- [x] **Übungskatalog:** Tabellen `exercises`, `equipment`, `muscle_groups` (+ Pivots), Import
      `php artisan exercises:import` aus `database/data/exercises/*.json` (Quelle: `TrainJ/Unbenannt.txt`),
      Seiten „Übungen“ mit Filtern (Muskelgruppe, Gerät, Stufe, nur vorhandene Geräte).
- [x] **Server-Autostart (Skript):** `scripts/windows/Install-Server.ps1` – Apache/MariaDB als Dienste, PHP im PATH,
      Firewall. ⚠️ Muss einmal als Administrator ausgeführt werden; danach `php artisan migrate` + `exercises:import`.

- [x] **Nährwertbasis Teil 1 – Lebensmittel:** BLS 4.0 (MRI, frei nutzbar) → `database/data/foods/bls_4_0.csv`
      (7.140 Lebensmittel, je 100 g: kcal, Eiweiß, Fett, KH, Ballaststoffe, Zucker, ges. Fettsäuren, Salz) per
      `scripts/bls/convert.py`; Tabelle `foods`, `php artisan foods:import`, Wertobjekt `Nutrients`
      (fehlende Werte → „geschätzt“), Seite „Lebensmittel & Nährwerte“ mit Suche und Gruppenfilter.
- [x] **Nährwertbasis Teil 2 – Rezepte berechnen:** Zutat ↔ Lebensmittel (`ingredients.food_id`, eigenes Gewicht
      `grams`), Gewichtsschätzung für EL/TL/Stück/Größen (`IngredientWeight`, Annahmen in `config/nutrition.php`),
      Vorschläge mit Synonymliste (`FoodMatcher`), `RecipeNutrition` (gesamt/pro Portion, „geschätzt“, unvollständig),
      Nährwerte pro Portion auf der Rezeptseite, Zuordnungsassistent `/rezepte/{rezept}/naehrwerte` mit
      Lebensmittelsuche, Übersicht „Nährwerte prüfen“ (`/naehrwerte`). Zuordnungen bleiben beim Bearbeiten erhalten.
- [x] **Profil & Ziele mit Tagesbudget:** Tabelle `body_profiles` (eine pro Person, nur Erwachsene, nur selbst sichtbar),
      Seite `/profil`; `EnergyBudget`: Mifflin-St Jeor × PAL ± Ziel = Ruhetag, + Trainingsverbrauch (MET) = Trainingstag,
      Wochenschnitt, Makros (Eiweiß je kg mit BMI-Bezugsgewicht, Fett 30 %, KH Rest, Ballaststoffe 30 g), eigenes Budget
      optional, Rechenweg und Warnung unter Grundumsatz. Werte in `config/nutrition.php` (`budget`).
- [x] **Persönlicher Wochenplaner (Grundversion):** `/mein-plan`, je Person privat; Frühstück/Mittag/Abend/Snacks mit
      mehreren Einträgen (Rezept × Portionen oder Lebensmittel × Gramm), Nährwerte je Eintrag/Mahlzeit/Tag, Abgleich mit
      dem Tagesbudget („noch … kcal“, Warnung ab 10 % drüber), Trainingstage pro Tag markieren, Menge ändern/entfernen,
      mobil per Antippen. Lebensmittelsuche berücksichtigt Synonyme.
- [x] **Dashboard „Mein Tag“ (Grundversion):** auf der Startseite – Fortschrittsringe (SVG) für kcal/Eiweiß/KH/Fett,
      verbleibende kcal, Trainings-/Ruhetag, heutige Mahlzeiten mit Schnell-Links, Wochenbalken.

## Grundversion (Dashboard, Wochenplaner, Rezeptdatenbank, Nährwertberechnung)

- [ ] **Vorhandene Rezepte zuordnen** (Nutzer, über „Nährwerte prüfen“ – Vorschläge sind vorausgefüllt)
- [ ] **Rezeptdatenbank ausbauen**
  - [x] Nährwerte auf den Rezeptkarten (kcal, Eiweiß, KH pro Portion)
  - [ ] Handangaben `calories`/`protein` später entfernen oder nur noch als Fallback nutzen
  - [ ] vegetarisch/vegan je Lebensmittel (aus Gruppe ableiten, Gerichte prüfen) → Filter
  - [ ] Synonymliste nach echten Zuordnungen erweitern (häufige Korrekturen im Assistenten übernehmen)
  - [x] Filter High Protein, Low Carb, Schnell (berechnet, `RecipeFilter`, Grenzen in `config/nutrition.php`)
  - [ ] Filter Vegetarisch/Vegan (aus Zutaten), Meal Prep geeignet (Feld)
  - [x] Portionen umrechnen auf der Rezeptseite (− / +, `?portionen=`); offen: Stückzahlen runden („1,25 Knoblauchzehe“),
        Mengen im Text der Schritte und im Kochmodus mit umrechnen
- [ ] **Profil & Ziele ausbauen** (Grundversion erledigt, siehe oben)
  - [ ] Makro-Ziele selbst festlegen (heute nur das kcal-Budget; Eiweiß/Fett werden immer berechnet)
  - [ ] Gewicht mit Datum speichern statt überschreiben → Grundlage für „Fortschritt“
  - [ ] Budget auf der Startseite/im Dashboard anzeigen; konkrete Trainingstage aus dem Trainingsplan statt Anzahl
- [ ] **Persönlicher Wochenplaner ausbauen** (Grundversion erledigt, siehe oben)
  - [ ] Tag/Mahlzeit kopieren („wie gestern“), Einträge zwischen Mahlzeiten verschieben
  - [ ] Drag & Drop am Desktop (braucht neues JS → Build auf einem Rechner mit Node, `public/build` mitcommitten)
  - [ ] Verhältnis zum Familien-Essensplan klären (Gericht des Tages als Vorschlag fürs Abendessen übernehmen?)
  - [ ] Eigene Lebensmittel/Fertigprodukte anlegen (z. B. Proteinriegel mit Herstellerangaben)
- [ ] **Dashboard ausbauen** (Grundversion erledigt, siehe oben): anstehende Trainingseinheit, Gewichtsverlauf,
      Hinweis auf Rezepte ohne Nährwerte im eigenen Plan

## Danach

- [ ] **Übungskatalog ausbauen** (Wunsch des Nutzers)
  - [ ] **Grafik der beanspruchten Muskelgruppen:** eigene SVG-Körperansicht (vorne/hinten), Bereiche den
        Muskelgruppen zugeordnet; Hauptmuskulatur kräftig, Nebenmuskulatur hell eingefärbt. Auf der Übungsseite,
        später auch als Belastungsübersicht für eine Trainingseinheit/Woche. Zuordnung der 23 importierten
        Muskelgruppen (auch Sammelbegriffe wie „Beine“, „Rücken“) zu Körperbereichen festlegen.
  - [ ] **Grafik zur Ausführung jeder Übung:** Start- und Endposition (bzw. Bewegungsablauf) je Übung.
        Weg noch offen: eigene SVG-Illustrationen, hochgeladene Bilder/GIFs je Übung (in der DB wie Rezeptfotos)
        oder beides; optional Link zu einem Video.
- [ ] **Training**
  - [x] **Trainingsplan erstellen** (Wunsch des Nutzers): Einheiten mit Übungen aus dem Katalog (Reihenfolge, Sätze,
        Ziel, Pause, Notiz), Vorlage „Ganzkörper“ nach Stufe mit vorhandenen Geräten, Wochenplan je Wochentag,
        geplante Tage sind in „Mein Plan“ automatisch Trainingstage (Abweichung pro Tag möglich), „Heute trainieren“
        auf der Startseite
  - [ ] Trainingstage-Anzahl im Profil automatisch aus dem Wochenplan übernehmen (heute nur Hinweis bei Abweichung)
  - [ ] Weitere Vorlagen (Ober-/Unterkörper, Push/Pull), Einheit kopieren
  - [x] Trainingseinheiten eintragen/abhaken (tatsächliche Sätze, Wdh., Gewicht bzw. Dauer): Protokoll mit
        Vorbelegung vom letzten Mal, „Mein Verlauf“ je Übung, Start von der Startseite
  - [x] Protokoll: Sätze hinzufügen/entfernen, Verlaufsdiagramm je Übung (SVG, `<x-progress-chart>`)
  - [ ] Protokoll: Pausen-Timer zwischen Sätzen (braucht JS → Build)
  - [ ] Trainingstag ↔ Ruhetag steuert die Tagesziele; anstehende Einheiten auf dem Dashboard
  - [ ] Empfehlungen vor/nach dem Training
  - [ ] Übungen im Browser anlegen/bearbeiten (heute nur per Import)
- [ ] **Einkaufsliste 2.0:** Mengen nach Portionen, Kategorien (aus `foods`), Abhaken speichern,
      manuelle Einträge, Druckansicht/Export; Quelle = persönliche Pläne (eine Person oder ganze Familie)
- [ ] **Meal-Prep-Planer:** vorbereitbare Gerichte, gemeinsame Zutaten, Vorbereitungsplan,
      Lagerung/Haltbarkeit (neue Felder am Rezept)
- [ ] **Fortschritt:** Gewichtsverlauf, Ziel vs. geplant/gegessen, Diagramme

## Bekannte Grenzen / Notizen

- Auf dem PC ist absichtlich kein Node installiert → `public/build` wird bei jeder Design-Änderung mit ins Git
  eingetragen – **das bleibt so** (Entscheidung 08.11.2026, kein automatischer Build über GitHub Actions o. Ä.).
  Übungskatalog, Profil und „Mein Plan“ nutzen nur bestehende CSS-Klassen (geprüft); neue Klassen oder JS-Module
  erst nach einem Build auf einem Rechner mit Node, der `public/build` mitcommittet.
- Gerätevariante „Verstellbare Kurzhanteln“ erscheint als Ausführung „Verstellbare“.
