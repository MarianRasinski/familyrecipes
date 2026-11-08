# Rezeptbuch

Rezeptsammlung für die Familie auf Basis von Laravel 12, Blade, Tailwind CSS 4 und Alpine.js.
Gedacht für den Betrieb im lokalen Netzwerk.

> **Fork:** Dieses Repository (https://github.com/MarianRasinski/familyrecipes) baut das Rezeptbuch zu einem
> **Fitness- und Ernährungs-Wochenplaner** aus – mit eigenem Plan und eigenen Zielen für jedes
> Familienmitglied. Ausbauplan und Stand: [`TODO.md`](TODO.md). Die Datenbank des Forks läuft lokal
> (`DB_HOST=127.0.0.1`), getrennt von der Familien-Datenbank des Originals.

## Einrichtung

Die Daten liegen in der MariaDB auf dem Heimserver **192.168.178.38**, Datenbank **`FamilyRecipes`**.
Einmalig direkt auf dem Server (z. B. dessen phpMyAdmin) Datenbank und Benutzer anlegen:

```sql
CREATE DATABASE `FamilyRecipes` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'familyrecipes'@'192.168.178.%' IDENTIFIED BY 'ein-passwort';
GRANT ALL PRIVILEGES ON `FamilyRecipes`.* TO 'familyrecipes'@'192.168.178.%';
```

Danach das Passwort als `DB_PASSWORD` in `.env` eintragen:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed       # Tabellen, Standard-Admin und 3 Beispielrezepte
php artisan recipes:import       # übrige Rezepte aus database/data/recipes.json
php artisan exercises:import     # Übungskatalog (läuft auch bei migrate --seed mit)
php artisan foods:import         # Lebensmittel/Nährwerte aus dem BLS (läuft auch bei migrate --seed mit)
php artisan storage:link         # für die Vorschau importierter Fotos
npm run build
```

Der fertige Build (`public/build`) liegt mit im Git. Eine weitere Installation (z. B. auf 192.168.178.38)
lässt sich daher ohne Node per `git pull` aktualisieren. Nach Änderungen an Views, CSS oder JS also
`npm run build` ausführen und `public/build` mitcommitten. `php artisan migrate` nur auf **einer**
Installation ausführen, weil beide dieselbe Datenbank nutzen.

**Standard-Zugang:** Benutzername `Admin`, Passwort `Test` (Familienoberhaupt, angelegt per Migration).
Das Passwort nach der ersten Anmeldung unter „Mein Konto“ ändern.

Entwicklung (Server, Queue, Logs und Vite gleichzeitig):

```bash
composer run dev
```

Danach unter http://localhost:8000 erreichbar.

### Fork: Server mit Autostart einrichten (Windows/XAMPP)

Einmalig PowerShell mit Rechtsklick → **„Als Administrator ausführen“** öffnen und im Projektordner starten:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\windows\Install-Server.ps1
```

Das Skript nimmt `C:\xampp\php` in den PATH auf (danach geht `php artisan …` in jeder **neuen** Konsole),
prüft die Apache-Konfiguration, installiert Apache und MariaDB als Windows-Dienste `Apache2.4` und `mysql`
mit automatischem Start und Neustart bei Absturz und gibt Port 80 in der Firewall fürs Heimnetz frei.
Es kann mehrfach laufen; `-Uninstall` entfernt Dienste und Firewall-Regel wieder.

### Betrieb über XAMPP (Original)

Apache liefert das Rezeptbuch über einen eigenen VirtualHost auf **Port 80** aus
(`C:\xampp\apache\conf\extra\httpd-vhosts.conf`, dazu `Listen 80` in `httpd.conf`).
Damit ist es im Heimnetz unter `http://192.168.178.64` bzw. `http://fredrgb.fritz.box` erreichbar.

Damit andere Geräte (z. B. iPad) zugreifen dürfen, braucht Windows eine Firewall-Freigabe für Port 80
im privaten Netzwerk (PowerShell als Administrator):

```powershell
New-NetFirewallRule -DisplayName "Rezeptbuch (Apache, Heimnetz)" -Direction Inbound -Action Allow `
  -Program "C:\xampp\apache\bin\httpd.exe" -Protocol TCP -LocalPort 80 -Profile Private -RemoteAddress LocalSubnet
```

In der FRITZ!Box sollte dem Rechner immer dieselbe IP-Adresse zugewiesen werden.
Die Tests laufen unabhängig davon gegen eine SQLite-In-Memory-Datenbank (`phpunit.xml`).

### Auf iPhone/iPad als App

In Safari `http://192.168.178.64` öffnen → Teilen-Symbol → **„Zum Home-Bildschirm“**. Das Rezeptbuch
erscheint dann mit eigenem Icon und startet im Vollbild ohne Safari-Leisten
(`public/manifest.json`, Icons in `public/`, neu erzeugen mit `python -I resources/icons/generate.py`).

## Startseite

Nach dem Anmelden zeigt die Startseite „Heute gibt's …“ (mit Rezept-Link), das Gericht von morgen,
die nächsten Tage, den Stand der nächsten Woche (geplante Tage, Vorschläge) und Schnellzugriffe.

## Fotos

Fotos werden vor dem Hochladen im Browser auf höchstens 1600 px verkleinert und als JPEG gespeichert
(iPhone-Fotos: typischerweise mehrere MB → unter 1 MB). Kann ein Gerät das nicht, nimmt der Server
Originale bis 12 MB an. Am iPhone/iPad kann man direkt mit der Kamera fotografieren.

Die Fotos liegen **in der Datenbank** (Tabelle `recipe_images`, `MEDIUMBLOB`), damit alle Installationen
dieselben Fotos sehen. Ausgeliefert werden sie über `/rezepte/{rezept}/bild?v=…`, und der Browser
speichert sie dauerhaft zwischen. Voraussetzung: In der MariaDB muss `max_allowed_packet` mindestens
`32M` betragen (Standard ist 1 MB).

- Ältere Fotos, die noch als Datei unter `storage/app/public/recipes` liegen, übernimmt
  `php artisan recipes:move-images` in die Datenbank. Der Befehl muss auf **jeder** Installation einmal
  laufen, weil jede nur ihre eigenen Dateien findet. Bis dahin werden diese Fotos weiter als Datei angezeigt.
- Die Dateien bleiben als Sicherung liegen und können danach gelöscht werden.

## Rezept per Link importieren

„🔗 Aus Link importieren“ (Rezeptübersicht oder „Neues Rezept“, für Erwachsene): Link z. B. von Chefkoch
einfügen → Titel, Beschreibung, Portionen, Zeiten, Kalorien, Zutaten (in Menge/Einheit/Name zerlegt),
Schritte, Tags und Foto landen im Rezeptformular und werden erst beim Speichern übernommen.

- Grundlage sind die strukturierten Rezeptdaten der Seite (schema.org/Recipe, JSON-LD); Seiten ohne
  diese Daten werden mit einem Hinweis abgelehnt.
- Werbetexte in Beschreibungen und von Chefkoch angehängte Autorennamen werden entfernt.
- Aus Sicherheitsgründen werden nur öffentliche Adressen abgerufen (auch nach Weiterleitungen),
  keine Geräte im Heimnetz. Nicht gespeicherte Import-Fotos werden nach einem Tag gelöscht.
- Code: `app/Actions/ImportRecipeFromUrl.php`, `app/Support/RecipeImport/`.

## Kochmodus

„👩‍🍳 Kochmodus“ auf der Rezeptseite: Vollbild, ein Schritt pro Bildschirm in großer Schrift, blättern per
Knopf, Wischen oder Pfeiltasten. Unter jedem Schritt stehen die Zutaten, die darin vorkommen; bei
Zeitangaben („12–15 Minuten“) gibt es einen Timer mit Signalton. Die Zutatenliste lässt sich jederzeit
einblenden und abhaken. Code: `app/Support/CookingSteps.php`, `resources/js/cooking-mode.js`.

## Benutzer & Rollen

Anmeldung mit **Benutzername** und Passwort, keine Selbstregistrierung.

| Rolle | Rezepte ansehen, Favoriten | Essen wünschen | Rezepte anlegen/ändern, Essensplan festlegen, Familie | Rezepte löschen, Accounts, Änderungsprotokoll |
| --- | :---: | :---: | :---: | :---: |
| Familienoberhaupt (Admin) | ✓ | – | ✓ | ✓ |
| Erwachsen | ✓ | ✓ | ✓ | – |
| Kind | ✓ | ✓ | – | – |

- Der Admin-Account dient nur der Verwaltung: Er wünscht sich nichts (die Wunschliste sieht er nur zum
  Planen) und erscheint nicht in der Übersicht „Wer mag was?“ – auch seine Favoriten zählen dort nicht.
- „Wer mag was?“ (Menü „Familie“, alle Erwachsenen) zeigt die Top 5 **am beliebtesten** (meiste
  Favoriten), die Top 5 **am häufigsten gewünscht** (alle Wünsche, auch eingeplante; gleiche Freitexte
  werden zusammengezählt) und die Favoriten jedes Familienmitglieds.

- Jedes Mitglied kann unter „Mein Konto“ das eigene Passwort ändern.
- Der Standard-Admin wird per Migration angelegt; weitere Accounts über „Benutzer verwalten“
  oder `php artisan users:create`.

## Änderungsprotokoll

Jedes Anlegen, Bearbeiten und Löschen eines Rezepts wird mit Person, Zeitpunkt und den
konkreten Änderungen (Felder alt → neu, hinzugefügte/entfernte Zutaten, Schritte, Tags) gespeichert.

- Pro Rezept: „Verlauf ansehen“ auf der Rezeptseite (für alle sichtbar).
- Gesamt: „Änderungsprotokoll“ im Benutzermenü (nur Familienoberhaupt), inkl. gelöschter Rezepte.
- Einträge bleiben lesbar, auch wenn Rezept oder Account später gelöscht werden.
- Seeder und Import erscheinen als „System“.

## Wunschliste

Unter „Wünsche“ (Menü, Startseite, „⭐ Wünschen“ auf jeder Rezeptseite) kann sich jedes Familienmitglied
(außer dem Admin-Account) ein Rezept oder eine eigene Idee wünschen – **ab dieser oder ab nächster Woche**, optional mit Wunschtag und Notiz.

- Ein Wunsch bleibt **offen, bis das Gericht im Essensplan steht** (an einem Tag ab der gewünschten Woche),
  egal wie viele Wochen das dauert. „Erfüllt“ wird aus dem Plan berechnet: Wird das Gericht wieder
  ausgeplant, ist der Wunsch wieder offen (`app/Support/WishList.php`).
- Eingeplante Wünsche erscheinen unter „Eingeplant 🎉“, solange der Tag heute oder später ist, und bleiben
  als Historie gespeichert. Ein Wunsch wird nur durch einen Plan-Tag ab dem Tag des Wunsches erfüllt –
  wer sich ein Gericht wünscht, das letzte Woche schon dran war, steht also wieder bei den offenen Wünschen.
- Jede Person hat ein Gericht nur einmal offen auf der Liste; eigene offene Wünsche lassen sich zurückziehen,
  Erwachsene können offene Wünsche entfernen.
- Rezeptauswahl für viele Rezepte: Suchfeld (findet „Käse“ auch mit „kase“) und „♥ Nur meine Favoriten“.

## Essensplan

Unter „Essensplan“ (Woche = Montag bis Sonntag, Adresse z. B. `/essensplan/2026-W43`):

- Jede Woche zeigt die Wünsche, die noch offen oder in dieser Woche eingeplant sind (✓).
- **Festlegen** (Erwachsene): „Plan festlegen“ zeigt alle offenen Wünsche mit Anzahl, Namen und Wunschtagen.
  Pro Tag ein Gericht, jedes Gericht höchstens zweimal pro Woche.
- Planen geht für diese und nächste Woche; ältere Wochen bleiben als Historie lesbar.
- Beim Planen steht an jedem Rezept, wann es es zuletzt gab; oben gibt es die Gruppe „Lange nicht gegessen“
  (seit mindestens drei Wochen oder noch nie). Auch die Rezeptseite zeigt „Zuletzt gegessen“.
- Wünsche und Pläne werden dauerhaft aufbewahrt (Grundlage für spätere Auswertungen) und bleiben
  auch nach dem Löschen eines Rezepts oder Accounts erhalten.

## Einkaufsliste

Unter „Einkaufsliste“ (nur Erwachsene) entsteht automatisch eine Liste aus dem Essensplan:
alle Zutaten der Gerichte von **heute bis Sonntag** der laufenden Woche; vergangene Tage zählen nicht.

- Gleiche Zutaten mit gleicher Einheit werden addiert (auch Bereiche wie „500–600 g“ und Kommazahlen),
  doppelt geplante Gerichte zählen doppelt. Bei jeder Zeile steht, für welche Gerichte und Tage sie gebraucht wird.
- Zutaten, die in allen Gerichten optional sind, stehen getrennt unter „Optional“.
- Freitext-Gerichte (z. B. „Pizza bestellen“) haben keine Zutaten und erscheinen nur als Hinweis.
- **Haben wir immer da:** Zutaten wie Salz oder Öl mit „immer da“ markieren – sie stehen dann eingeklappt
  unten, zählen nicht mit und werden nicht geteilt („doch einkaufen“ holt sie zurück). Abgleich über den
  ganzen Zutatennamen ohne Groß-/Kleinschreibung.
- Logik: `app/Support/ShoppingList.php`, Mengen: `app/Support/Quantity.php`.

**Teilen** (für den Supermarkt): Der Knopf „Teilen“ öffnet – wo der Browser es erlaubt – das
Teilen-Menü des Geräts (Notizen, WhatsApp, Erinnerungen …). Das geht nur über HTTPS; im Heimnetz
per `http://` erscheint stattdessen ein eigenes Menü: Text kopieren, per WhatsApp, per E-Mail oder als
Textdatei speichern. Abgehakte Zutaten (z. B. schon im Haus) werden beim Teilen weggelassen.
Frontend: `resources/js/shopping-list.js`.

## Übungen

Unter „Übungen“ (für alle Familienmitglieder lesbar) steht der Übungskatalog mit Vorgabe
(z. B. „3 × 10-12 je Seite“), Schwierigkeit, Geräten und Haupt-/Nebenmuskulatur.

- **Filter:** Suche, Muskelgruppe, Gerät, Stufe (Anfänger/Mittel/Fortgeschritten) und
  „Nur mit vorhandenen Geräten“ (optionale Geräte zählen dabei nicht).
- **Import:** `php artisan exercises:import` liest alle JSON-Dateien aus `database/data/exercises/`
  (oder eine einzelne Datei: `php artisan exercises:import pfad/zur/datei.json`). Ein erneuter Import
  aktualisiert die Übungen derselben Datei anhand ihrer `id`, statt sie doppelt anzulegen.
- **Dateiformat** (wie `database/data/exercises/ganzkoerper.json`): `kategorie`, `trainingsgeraete`
  (Gerätebestand), `uebungen` mit `id`, `name`, `geraet`, `muskelgruppen_haupt`, `muskelgruppen_neben`,
  `schwierigkeit`, `saetze`, `wiederholungen`, `beschreibung`.
- **Geräte** werden zu Gerätetypen zusammengefasst: „Kettlebell 12 kg“ und „Kettlebell 16 kg“ im Bestand
  ergeben den Typ „Kettlebell“ mit den Ausführungen 12 kg und 16 kg. „Kettlebell optional“ bei einer Übung
  heißt: geht auch ohne. Geräte, die nur bei Übungen vorkommen, gelten als nicht vorhanden.
- **Vorgaben** werden zusätzlich als Zahlen gespeichert (Wiederholungen bzw. Sekunden, „je Seite“),
  damit spätere Trainingsprotokolle darauf aufbauen können; der Originaltext bleibt erhalten.
- Code: `app/Actions/ImportExercises.php`, `app/Support/Training/ExerciseTarget.php`,
  `app/Http/Controllers/ExerciseController.php`.

## Trainingsplan

„Training“ (Navigation, `/training`) – jede Person plant ihr eigenes Training (privat, auch Kinder):

- **Einheiten** (z. B. „Ganzkörper A“) mit Übungen aus dem Katalog: Sätze, Ziel je Satz („10-12“, „45 Sekunden“,
  „8 je Seite“), Pause in Sekunden, Notiz (z. B. Gewicht). Neue Übungen übernehmen die Vorgabe aus dem Katalog.
  Reihenfolge mit ↑ ↓, Entfernen – Eingaben werden dabei mitgespeichert. Geschätzte Dauer je Einheit.
- **Vorlage „Ganzkörper“** (Anfänger/Mittel/Fortgeschritten): bis zu 8 Übungen bis zur gewählten Stufe, nur mit
  vorhandenen Geräten, möglichst viele verschiedene Hauptmuskelgruppen. Danach frei anpassbar.
- **Wochenplan:** je Wochentag eine Einheit oder Ruhetag, jede Woche gleich. Diese Tage sind in „Mein Plan“
  automatisch **Trainingstage** (Trainingstag-Budget, Einheit wird angezeigt) und erscheinen auf der Startseite
  als „Heute trainieren“. Einzelne Tage lassen sich in „Mein Plan“ abweichend umschalten (Training fällt aus /
  zusätzliches Training); das gilt nur für diesen Tag.
- Standardwerte (Pause, Sätze, Größe der Vorlage): `config/training.php`.

### Trainingsprotokoll

- **Training starten** („▶ Training starten“ in der Einheit oder „▶ Starten“ bei „Heute trainieren“ auf der
  Startseite): legt die geplanten Sätze an; das Gewicht vom letzten Mal ist vorausgefüllt, „Letztes Mal: …“ steht
  bei jeder Übung. Ein zweiter Start am selben Tag führt zurück ins laufende Training.
- Je Satz **Wiederholungen** (bzw. **Sekunden** bei Zeitübungen) und **Gewicht** eintragen, abhaken,
  „Zwischenspeichern“ oder „Training abschließen“. Abgeschlossene Tage zählen in „Mein Plan“ als Trainingstag.
- **Protokoll** (`/training/protokoll`): alle Trainings mit erledigten Sätzen und bewegtem Gewicht (Wdh. × kg).
- Während des Trainings: „＋ Satz“ (übernimmt Ziel und Gewicht des letzten Satzes) und ✕ zum Entfernen eines Satzes.
- **Mein Verlauf** auf jeder Übungsseite: Liniendiagramm (bestes Gewicht, sonst Wiederholungen/Sekunden; ab zwei
  Trainings, SVG ohne Build) und Liste des besten Satzes je Training. Alles nur für die Person selbst sichtbar.
- Name und Ziel werden beim Start kopiert – das Protokoll bleibt richtig, auch wenn die Einheit später geändert
  oder gelöscht wird.

## Lebensmittel & Nährwerte

„Lebensmittel & Nährwerte“ (Benutzermenü, für alle lesbar) durchsucht 7.140 Lebensmittel aus dem
**Bundeslebensmittelschlüssel (BLS) 4.0** des Max Rubner-Instituts mit Werten je 100 g: Energie, Eiweiß,
Kohlenhydrate, Fett, Ballaststoffe (zusätzlich gespeichert: Zucker, gesättigte Fettsäuren, Salz).

- **Suche:** Jedes Wort muss vorkommen („zwiebel roh“). Grundlebensmittel stehen vor Gerichten, rohe zuerst.
- **Fehlende Werte** („keine Daten“ im BLS) sind nicht 0: Sie werden als „unvollständige Daten“ markiert, und
  Berechnungen damit gelten als Schätzung (`app/Support/Nutrition/Nutrients.php`).
- **Daten aktualisieren:** Neue BLS-Version mit `python -I scripts/bls/convert.py <BLS-Excel> database/data/foods/bls_4_0.csv`
  umwandeln (braucht Python mit openpyxl), dann `php artisan foods:import`. Vorhandene Einträge werden anhand
  des BLS-Codes aktualisiert, eigene Lebensmittel ohne BLS-Code bleiben erhalten.
- **Quelle:** Max Rubner-Institut (2025): Bundeslebensmittelschlüssel (BLS), Version 4.0. Karlsruhe.
  Laut Dokumentation kostenfrei und ohne Lizenzbarrieren nutzbar, auch für App- und Softwareentwicklung.

## Nährwerte von Rezepten

Jede Zutat kann mit einem Lebensmittel aus dem BLS verknüpft werden. Daraus berechnet die Rezeptseite die
**Nährwerte pro Portion** (kcal, Eiweiß, Kohlenhydrate, Fett, Ballaststoffe).

- **Zuordnen** (Erwachsene): „Nährwerte berechnen“ bzw. „Zuordnung prüfen“ auf der Rezeptseite öffnet den
  Assistenten. Zu jeder Zutat steht ein **Vorschlag** bereit (Synonymliste + Suche), der erst beim Speichern
  übernommen wird. Über „ändern“ lässt sich jedes Lebensmittel suchen.
- **Gewicht:** g, kg, ml und l werden direkt übernommen. EL, TL, Prise, Scheibe, Stück, „klein/mittelgroß/groß“
  usw. werden über Durchschnittsgewichte geschätzt (z. B. 1 EL Öl ≈ 10 g, 1 Ei ≈ 60 g); Spannen wie „500–600 g“
  werden gemittelt. Wer es genauer weiß, trägt das Gewicht der ganzen Menge ein.
- **Was nicht mitzählt:** optionale Zutaten sowie Gewürze und Kräuter ohne Menge oder ohne Zuordnung.
  Andere Zutaten ohne Menge (z. B. „Tomaten“) gelten als fehlend, bis ein Gewicht eingetragen ist.
- **„geschätzt“** erscheint, wenn eine Menge geschätzt wurde, ein BLS-Wert fehlt oder Zutaten noch fehlen –
  dann steht dabei, wie viele Zutaten eingerechnet sind.
- **Übersicht:** „Nährwerte prüfen“ (Benutzermenü, Erwachsene) listet alle Rezepte, unvollständige zuerst.
- Beim Bearbeiten eines Rezepts bleiben Zuordnungen gleichnamiger Zutaten erhalten; ein eingetragenes Gewicht
  nur, solange Menge und Einheit gleich bleiben.
- Annahmen (Gewichte, Synonyme, Gewürze): `config/nutrition.php`. Code: `app/Support/Nutrition/`
  (`IngredientWeight`, `FoodMatcher`, `IngredientNutrition`, `RecipeNutrition`).

## Rezepte filtern & Portionen umrechnen

- **Rezeptkarten** zeigen kcal, Eiweiß und Kohlenhydrate pro Portion (sobald Zutaten zugeordnet sind; „≈“ = geschätzt).
- **Filter** über der Tag-Liste (kombinierbar mit Suche, Tag und Favoriten), berechnet aus den Zutaten:
  💪 **High Protein** (ab 20 g Eiweiß pro Portion und mind. 20 % der Kalorien aus Eiweiß),
  🥗 **Low Carb** (höchstens 25 g Kohlenhydrate pro Portion, nur vollständig berechnete Rezepte),
  ⚡ **Schnell** (Vorbereitung + Kochen höchstens 30 Minuten). Grenzen: `config/nutrition.php` (`recipe_filters`).
  Rezepte ohne Nährwerte erscheinen bei High Protein/Low Carb nicht.
- **Portionen umrechnen:** auf der Rezeptseite bei den Zutaten mit − / + (Link `?portionen=…`, 1–50).
  Zahlen, Brüche und Bereiche werden umgerechnet („1/2 TL“ → „0,75 TL“), Angaben wie „etwas“ bleiben.
  Die Mengen im Text der Zubereitungsschritte bleiben unverändert.

## Profil & Ziele (Tagesbudget)

„Profil & Ziele“ (Benutzermenü, `/profil`) berechnet aus Geschlecht, Geburtsdatum, Größe, Gewicht, Aktivität im
Alltag, Ziel und Training ein **Tagesbudget** für Kalorien, Eiweiß, Kohlenhydrate, Fett und Ballaststoffe.

- **Nur Erwachsene** (ab 18, Rolle nicht „Kind“) – die Formeln gelten nicht für Kinder.
- **Jeder sieht nur sein eigenes Profil**, auch das Familienoberhaupt nicht die der anderen (keine Benutzer-ID in der URL).
- **Rechnung** (aufklappbar unter „So wird gerechnet“): Grundumsatz nach Mifflin-St Jeor × PAL (Alltag ohne Sport)
  ± Ziel (Abnehmen −500, Halten ±0, Aufbau +300 kcal), auf 10 kcal gerundet = **Ruhetag**.
  **Trainingstag** = Ruhetag + (MET − 1) × kg × Stunden der Einheit; dazu der Wochendurchschnitt.
- **Makros:** Eiweiß 1,6–2,0 g je kg (bei BMI über 25 bezogen auf das Gewicht bei BMI 25), Fett 30 % der Kalorien,
  Kohlenhydrate = Rest, Ballaststoffe 30 g.
- **Eigenes Budget** (optional, z. B. aus der Ernährungsberatung) ersetzt das berechnete Ziel an Ruhetagen.
- Hinweis, wenn das Budget unter dem Grundumsatz liegt. Alle Werte: `config/nutrition.php` (`budget`),
  Code: `app/Support/Nutrition/EnergyBudget.php`, Tabelle `body_profiles`.

## Startseite: Mein Tag (Dashboard)

Unter „Heute gibt's“ zeigt die Startseite den eigenen Tag aus „Mein Plan“:

- **Fortschrittsringe** für Kalorien, Eiweiß, Kohlenhydrate und Fett gegen das Tagesbudget (nur mit Profil),
  dazu „Noch … kcal übrig“ bzw. „… kcal über Budget“ und ob heute Trainings- oder Ruhetag ist.
- **Heutige Mahlzeiten** mit kcal je Mahlzeit; für leere Mahlzeiten Schnell-Links („＋ Abendessen“).
- **Diese Woche:** sieben Balken (Anteil am Tagesbudget), 🏋️ = Trainingstag; antippen öffnet den Tag.
- Ohne Profil: Hinweis auf „Profil & Ziele“ (Erwachsene); Kinder sehen ihre Mahlzeiten mit kcal-Summe.
- Ringe sind SVG (`components/progress-ring.blade.php`) mit den Theme-Farben – kein neuer Build nötig.

## Mein Plan (persönlicher Wochenplaner)

„Mein Plan“ (Navigation, `/mein-plan`) ist der eigene Wochenplan jeder Person – unabhängig vom
Familien-Essensplan, der weiter bestehen bleibt.

- **Mehrere Mahlzeiten pro Tag:** Frühstück, Mittagessen, Abendessen, Snacks – je Mahlzeit beliebig viele
  Einträge: ein **Rezept** mit Portionen (auch 0,5 oder 1,5) oder ein **Lebensmittel** aus dem BLS mit Gramm.
- **Nährwerte beim Planen:** je Eintrag, Mahlzeit und Tag (kcal, Eiweiß, Kohlenhydrate, Fett). Rezepte ohne
  berechnete Nährwerte werden markiert und fehlen in der Summe (Link „berechnen“ für Erwachsene).
- **Abgleich mit dem Tagesbudget** (nur mit Profil, siehe „Profil & Ziele“): „noch … kcal“ bzw. „… kcal über
  Budget“ mit Balken; ab 10 % über dem Budget rot. Tage lassen sich als **Trainingstag** markieren, dann gilt
  das Trainingstag-Budget. Oben steht ein Wochenüberblick.
- Menge eines Eintrags antippen = ändern; ✕ = entfernen. Kinder planen ohne Kalorienziel.
- **Privat:** Jeder sieht und ändert nur seinen eigenen Plan, auch der Admin nicht den der anderen.
- Code: `app/Support/Planning/` (`WeekPlan`, `DayPlan`), Tabellen `planned_meals`, `training_days`.

## Aufbau

| Bereich | Ort |
| --- | --- |
| Models | `app/Models` – `Recipe`, `Ingredient`, `Step`, `Tag`, `User`, `RecipeRevision`, `MealSuggestion`, `MealPlanEntry`, `Exercise`, `Equipment`, `MuscleGroup`, `Food`, `BodyProfile`, `PlannedMeal`, `TrainingDay`, `Workout`, `WorkoutExercise`, `ScheduledWorkout`, `WorkoutSession`, `WorkoutSet` |
| Enums | `app/Enums` – `Difficulty`, `Role`, `RevisionAction`, `ExerciseLevel`, `ExerciseMeasure`, `FoodCategory`, `Sex`, `ActivityLevel`, `FitnessGoal`, `TrainingType`, `MealSlot` |
| Kalenderwoche | `app/Support/Week.php` (ISO-Woche, offen = diese und nächste Woche) |
| Speichern/Löschen inkl. Protokoll | `app/Actions/SaveRecipe.php`, `app/Actions/DeleteRecipe.php` |
| Validierung | `app/Http/Requests` |
| Berechtigungen | `app/Policies` (`RecipePolicy`, `UserPolicy`, `PlannedMealPolicy`, `WorkoutPolicy`, `WorkoutSessionPolicy`), Gates `view-family-favorites`, `view-revision-log` und `manage-body-profile` in `AppServiceProvider` |
| Controller | `app/Http/Controllers` – Rezepte, Verlauf, Favoriten, Familie, Änderungsprotokoll, Benutzer, Konto, Login |
| Views | `resources/views`, Layouts unter `resources/views/components/layouts` |
| Artisan-Befehle | `users:create`, `recipes:import`, `exercises:import`, `foods:import` (`app/Console/Commands`) |
| Beispielrezepte | `database/seeders/RecipeSeeder.php` |
| Ursprüngliche Rezeptdaten | `database/data/recipes.json` |
| Übungskatalog | `database/data/exercises/*.json` |
| Lebensmittel (BLS 4.0) | `database/data/foods/bls_4_0.csv`, Umwandlung: `scripts/bls/convert.py` |

## Tests

```bash
php artisan test
```
