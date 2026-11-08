#!/usr/bin/env bash
# Screenshot einer Seite der laufenden App (http://localhost) in Handygröße – zur optischen Prüfung.
#
# Aufruf (Git Bash, im Projektordner):
#   scripts/dev/screenshot.sh /mein-plan ziel.png [breite] [höhe]
#
# - Meldet sich per curl an (SHOT_USER/SHOT_PASS, Standard: Admin/Test) und speichert das HTML,
#   dann rendert Headless-Edge es als PNG.
# - Edge rendert mindestens ca. 500 px breit; kleinere Breiten werden rechts abgeschnitten →
#   Standardbreite 520. Wartezeit für Webfonts per --virtual-time-budget (sonst fehlt die Schrift).
# - Bilder (Rezeptfotos) fehlen im Screenshot, weil die Datei ohne Anmeldung geladen wird.
set -euo pipefail

path="${1:?Pfad, z. B. /mein-plan}"
out="${2:?Zieldatei, z. B. shot.png}"
width="${3:-520}"
height="${4:-2000}"
user="${SHOT_USER:-Admin}"
pass="${SHOT_PASS:-Test}"
edge="/c/Program Files (x86)/Microsoft/Edge/Application/msedge.exe"

jar="$(mktemp)"
html="${out%.png}.html"
token() { grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//'; }

t="$(curl -s -c "$jar" -b "$jar" http://localhost/login | token)"
curl -s -c "$jar" -b "$jar" -o /dev/null --data-urlencode "_token=$t" \
    --data-urlencode "username=$user" --data-urlencode "password=$pass" http://localhost/login
curl -s -b "$jar" "http://localhost${path}" > "$html"
rm -f "$jar"

abs_html="$(cd "$(dirname "$html")" && pwd -W 2>/dev/null || pwd)/$(basename "$html")"
"$edge" --headless=new --disable-gpu --hide-scrollbars --virtual-time-budget=6000 \
    --window-size="${width},${height}" --screenshot="$out" "file:///${abs_html}" 2>/dev/null

echo "Gespeichert: $out (HTML: $html)"
