# TODO – Ausbau zum Fitness- & Ernährungs-Wochenplaner

Stand: 08.11.2026. Reihenfolge = geplante Umsetzung. Jeder Schritt ist für sich nutzbar und bekommt Tests.
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

## Grundversion (Dashboard, Wochenplaner, Rezeptdatenbank, Nährwertberechnung)

- [ ] **Nährwertbasis**
  - [ ] Nährwertquelle festlegen (Favorit: Bundeslebensmittelschlüssel BLS, lokal importiert; Lizenz/Verfügbarkeit prüfen)
  - [ ] Tabelle `foods` (kcal, Protein, Kohlenhydrate, Fett, Ballaststoffe pro 100 g, Kategorie, vegetarisch/vegan) + Import-Befehl
  - [ ] Standardgewichte für Einheiten (Stück, EL, TL, Prise …) je Lebensmittel bzw. allgemein
  - [ ] Zutaten: `food_id` + Menge in Gramm; `NutritionCalculator` in `app/Support` (pro Rezept/Portion)
  - [ ] Kennzeichnung „geschätzt“, wenn Zuordnung oder Umrechnung unsicher ist
- [ ] **Bestehende Rezepte zuordnen:** Assistent schlägt pro Zutat ein Lebensmittel vor, Nutzer bestätigt
- [ ] **Rezeptdatenbank ausbauen**
  - [ ] Nährwerte pro Portion anzeigen (kcal, Protein, KH, Fett, Ballaststoffe), manuell überschreibbar
  - [ ] Filter: High Protein, Low Carb, Schnell (berechnet), Vegetarisch/Vegan (aus Zutaten), Meal Prep geeignet (Feld)
  - [ ] Portionen umrechnen auf der Rezeptseite (`Quantity` vorhanden)
- [ ] **Profil & Ziele (pro Person)**
  - [ ] Gewicht, Größe, Alter, Geschlecht, Aktivitätslevel, Trainingshäufigkeit/-art, Ziel (Aufbau/Abbau/Halten)
  - [ ] Grundumsatz (Mifflin-St Jeor) → Gesamtbedarf (PAL) → Zielkalorien und Makros, Rechenweg sichtbar
  - [ ] Getrennte Zielwerte für Trainings- und Ruhetage
- [ ] **Persönlicher Wochenplaner**
  - [ ] Tabelle mit `user_id`, Datum, Mahlzeit (Frühstück/Mittag/Abend/Snack), Rezept, Portionen; mehrere Einträge je Mahlzeit
  - [ ] Tages- und Wochensummen (kcal, Makros) automatisch
  - [ ] Drag & Drop am Desktop, Antippen-zum-Einplanen am iPhone/iPad; Mahlzeiten tauschen/kopieren
  - [ ] Verhältnis zum bisherigen Familien-Essensplan klären (übernehmen, verknüpfen oder ablösen)
- [ ] **Dashboard:** Tagesziele mit Fortschrittsringen, verbleibende kcal/Makros, heutige Mahlzeiten, Wochenübersicht

## Danach

- [ ] **Training**
  - [ ] Trainingseinheiten pro Person planen/eintragen (aus dem Übungskatalog, Sätze/Wdh./Gewicht bzw. Dauer)
  - [ ] Trainingstag ↔ Ruhetag steuert die Tagesziele; anstehende Einheiten auf dem Dashboard
  - [ ] Empfehlungen vor/nach dem Training
  - [ ] Übungen im Browser anlegen/bearbeiten (heute nur per Import)
- [ ] **Einkaufsliste 2.0:** Mengen nach Portionen, Kategorien (aus `foods`), Abhaken speichern,
      manuelle Einträge, Druckansicht/Export; Quelle = persönliche Pläne (eine Person oder ganze Familie)
- [ ] **Meal-Prep-Planer:** vorbereitbare Gerichte, gemeinsame Zutaten, Vorbereitungsplan,
      Lagerung/Haltbarkeit (neue Felder am Rezept)
- [ ] **Fortschritt:** Gewichtsverlauf, Ziel vs. geplant/gegessen, Diagramme

## Bekannte Grenzen / Notizen

- Auf dem PC ist absichtlich kein Node installiert → `public/build` wird mit ins Git eingetragen. Der Übungskatalog
  nutzt nur bestehende CSS-Klassen (geprüft). Für neue Oberflächen (Dashboard, Planer) braucht es einen Build-Weg,
  z. B. GitHub Actions, der `public/build` baut und committet.
- Gerätevariante „Verstellbare Kurzhanteln“ erscheint als Ausführung „Verstellbare“.
