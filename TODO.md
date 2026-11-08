# TODO – Ausbau zum Fitness- & Ernährungs-Wochenplaner

Stand: 08.11.2026 (Ende der Session). Jeder Schritt ist für sich nutzbar und bekommt Tests.
Grundsatz: **Jedes Familienmitglied hat einen eigenen Plan und eigene Ziele** (neue Tabellen mit `user_id`, nur selbst
sichtbar – auch der Admin sieht fremde Pläne/Profile/Trainings nicht). Alle Berechnungen sind Orientierungswerte und
zeigen ihren Rechenweg.

## Übergabe (für die nächste Session)

**Stand:** Grundversion fertig (Nährwerte, Profil & Tagesbudget, „Mein Plan“, Dashboard „Mein Tag“, Rezeptfilter,
Portionen umrechnen) plus Training (Trainingsplan, Protokoll mit Verlauf, Übungsbilder, Muskelkarte).
Alles committet und nach `origin/master` gepusht, 303 Tests grün, Pint sauber, Klassen-Check ohne Funde.
Alle Migrationen bis `2026_11_08_000007` sind auf der lokalen DB ausgeführt. Testdaten von Claude sind gelöscht.

**Offen beim Nutzer:**
- Rezepte unter „Nährwerte prüfen“ (`/naehrwerte`) Lebensmitteln zuordnen – bisher haben nur wenige Rezepte
  Nährwerte, daher findet „High Protein“/„Low Carb“ kaum etwas und „Mein Plan“ zeigt oft „ohne Nährwerte“.
- Auf echten iPhones/iPads testen (bisher nur Headless-Edge-Screenshots bei 520 px): Mein Plan, Training,
  Protokoll (viel Tippen im Training), Profil, Startseite.
- In der DB liegt ein **nicht abgeschlossenes Training des Accounts „marian“** (08.11., 21:48) – echte Daten des
  Nutzers, nicht anfassen.

**Empfohlene nächste Schritte** (ohne Build machbar, nach Nutzen sortiert):
1. Trainingstage-Anzahl im Profil aus dem Wochenplan übernehmen (heute nur Hinweis bei Abweichung) – klein.
2. „Mein Plan“: Tag/Mahlzeit kopieren („wie gestern“) – spart am meisten Tipparbeit.
3. Gewicht mit Datum speichern → Fortschritt (Gewichtsverlauf als `<x-progress-chart>`, Ziel vs. geplant).
4. Belastungsübersicht der Woche (Muskelkarte über alle geplanten Einheiten bzw. das Protokoll).
5. Einkaufsliste 2.0 aus den persönlichen Plänen.
Größere Brocken mit Build (neues JS): Pausen-Timer im Protokoll, Drag & Drop im Planer.

**Werkzeuge:** `python scripts/dev/check-tailwind-classes.py` (Klassen im Build?), `scripts/dev/screenshot.sh`
(Seite als Handy-Screenshot) – Details in CLAUDE.md („Prüfen im Browser“, „Stolpersteine“).

## Erledigt

- [x] **Fork-Grundlagen:** lokale Datenbank (127.0.0.1, getrennt von der Familien-DB des Originals),
      `.env.example`, README/CLAUDE.md an den Fork angepasst, dieses TODO angelegt.
- [x] **Server-Autostart (Skript):** `scripts/windows/Install-Server.ps1` – Apache/MariaDB als Dienste, PHP im PATH,
      Firewall (einmal als Administrator ausführen).
- [x] **Übungskatalog:** Tabellen `exercises`, `equipment`, `muscle_groups` (+ Pivots), Import
      `php artisan exercises:import` aus `database/data/exercises/*.json` (Quelle: `TrainJ/Unbenannt.txt`),
      Seiten „Übungen“ mit Filtern (Muskelgruppe, Gerät, Stufe, nur vorhandene Geräte).
- [x] **Nährwertbasis Teil 1 – Lebensmittel:** BLS 4.0 (MRI, frei nutzbar) → `database/data/foods/bls_4_0.csv`
      (7.140 Lebensmittel, je 100 g) per `scripts/bls/convert.py`; Tabelle `foods`, `php artisan foods:import`,
      `Nutrients` (fehlende Werte → „geschätzt“), Seite „Lebensmittel & Nährwerte“. Suche berücksichtigt Synonyme.
- [x] **Nährwertbasis Teil 2 – Rezepte berechnen:** Zutat ↔ Lebensmittel, Gewichtsschätzung (`IngredientWeight`),
      Vorschläge (`FoodMatcher`), `RecipeNutrition`, Nährwerte pro Portion auf der Rezeptseite, Zuordnungsassistent
      `/rezepte/{rezept}/naehrwerte`, Übersicht „Nährwerte prüfen“ (`/naehrwerte`).
- [x] **Rezepte filtern & umrechnen:** Nährwerte auf den Rezeptkarten, Filter High Protein/Low Carb/Schnell
      (`RecipeFilter`), Portionen umrechnen auf der Rezeptseite (− / +, `?portionen=`).
- [x] **Profil & Ziele mit Tagesbudget:** `/profil`, `body_profiles`; `EnergyBudget` (Mifflin-St Jeor × PAL ± Ziel =
      Ruhetag, + Trainingsverbrauch = Trainingstag, Wochenschnitt, Makros), eigenes Budget optional, Rechenweg, Warnung.
- [x] **Persönlicher Wochenplaner „Mein Plan“:** `/mein-plan`, Frühstück/Mittag/Abend/Snacks mit Rezepten oder
      Lebensmitteln, Nährwerte je Eintrag/Mahlzeit/Tag, Abgleich mit dem Tagesbudget, Trainingstage pro Tag.
- [x] **Dashboard „Mein Tag“** auf der Startseite: Fortschrittsringe, verbleibende kcal, Trainings-/Ruhetag,
      heutige Mahlzeiten, Wochenbalken, „Heute trainieren“ mit Start-Knopf.
- [x] **Trainingsplan:** Einheiten aus dem Katalog (Sätze, Ziel, Pause, Notiz, Reihenfolge), Vorlage „Ganzkörper“
      nach Stufe, Wochenplan je Wochentag; geplante Tage sind in „Mein Plan“ automatisch Trainingstage
      (Abweichung pro Tag möglich).
- [x] **Trainingsprotokoll:** Training starten (Gewicht vom letzten Mal vorausgefüllt), Sätze eintragen/abhaken,
      Sätze hinzufügen/entfernen, abschließen (→ Trainingstag), Protokoll-Übersicht, „Mein Verlauf“ mit Diagramm.
- [x] **Übungsbilder & Muskelkarte:** Strichfiguren Start → Ende für alle 20 Übungen
      (`config/exercise_illustrations.php`), Körper vorne/hinten mit Haupt-/Nebenmuskulatur
      (`config/muscle_map.php`), auf der Übungsseite und als Belastungsübersicht je Einheit.
- [x] **Prüfwerkzeuge:** `scripts/dev/check-tailwind-classes.py`, `scripts/dev/screenshot.sh`.

## Offen – Ernährung & Planung

- [ ] **Rezeptdatenbank**
  - [ ] Synonymliste nach echten Zuordnungen erweitern (häufige Korrekturen im Assistenten übernehmen)
  - [ ] vegetarisch/vegan je Lebensmittel (aus Gruppe ableiten, Gerichte prüfen) → Filter; Meal Prep geeignet (Feld)
  - [ ] Portionen: Stückzahlen runden („1,25 Knoblauchzehe“), Mengen im Text der Schritte und im Kochmodus umrechnen
  - [ ] Handangaben `calories`/`protein` entfernen oder nur noch als Fallback nutzen
- [ ] **Profil & Ziele**
  - [ ] Trainingstage-Anzahl aus dem Wochenplan übernehmen (statt eigener Zahl im Profil)
  - [ ] Makro-Ziele selbst festlegen (heute nur das kcal-Budget; Eiweiß/Fett werden immer berechnet)
  - [ ] Gewicht mit Datum speichern statt überschreiben → Grundlage für „Fortschritt“
- [ ] **Mein Plan**
  - [ ] Tag/Mahlzeit kopieren („wie gestern“), Einträge zwischen Mahlzeiten verschieben
  - [ ] Eigene Lebensmittel/Fertigprodukte anlegen (z. B. Proteinriegel mit Herstellerangaben)
  - [ ] Verhältnis zum Familien-Essensplan klären (Gericht des Tages als Vorschlag fürs Abendessen übernehmen?)
  - [ ] Drag & Drop am Desktop (braucht neues JS → Build auf einem Rechner mit Node)
- [ ] **Dashboard:** Hinweis auf Rezepte ohne Nährwerte im eigenen Plan, Gewichtsverlauf
- [ ] **Einkaufsliste 2.0:** Mengen nach Portionen, Kategorien (aus `foods`), Abhaken speichern,
      manuelle Einträge, Druckansicht/Export; Quelle = persönliche Pläne (eine Person oder ganze Familie)
- [ ] **Meal-Prep-Planer:** vorbereitbare Gerichte, gemeinsame Zutaten, Vorbereitungsplan,
      Lagerung/Haltbarkeit (neue Felder am Rezept)
- [ ] **Fortschritt:** Gewichtsverlauf, Ziel vs. geplant/gegessen, Diagramme

## Offen – Training

- [ ] Belastungsübersicht für die ganze Woche (alle geplanten Einheiten bzw. das Protokoll)
- [ ] Weitere Vorlagen (Ober-/Unterkörper, Push/Pull), Einheit kopieren
- [ ] Übungen im Browser anlegen/bearbeiten (heute nur per Import; dann auch Übungsbild und Muskelzuordnung pflegen)
- [ ] Optional: eigene Fotos/GIFs je Übung (in der DB wie Rezeptfotos) oder Video-Link; Übungsbild klein im Katalog
- [ ] Empfehlungen vor/nach dem Training
- [ ] Pausen-Timer zwischen Sätzen (braucht JS → Build)

## Bekannte Grenzen / Notizen

- Auf dem PC ist absichtlich kein Node installiert → `public/build` wird bei jeder Design-Änderung mit ins Git
  eingetragen – **das bleibt so** (Entscheidung 08.11.2026, kein automatischer Build über GitHub Actions o. Ä.).
  Alle Fork-Seiten nutzen nur Klassen aus dem bestehenden Build (`scripts/dev/check-tailwind-classes.py`);
  neue Klassen oder JS-Module erst nach einem Build auf einem Rechner mit Node, der `public/build` mitcommittet.
- Übungsbilder sind einfache Strichfiguren (Gedächtnisstütze, keine genaue Technikanleitung).
- Gerätevariante „Verstellbare Kurzhanteln“ erscheint als Ausführung „Verstellbare“.
