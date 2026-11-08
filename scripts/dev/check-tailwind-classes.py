"""
Prüft, ob die in Blade-Views verwendeten Tailwind-Klassen schon im fertigen Build stehen.

Hintergrund: Auf dem Fork-PC ist kein Node installiert, `public/build` wird mitcommittet.
Neue Klassen erscheinen erst nach einem Build auf einem Rechner mit Node – bis dahin nur
Klassen verwenden, die dieses Skript nicht meldet.

Aufruf (im Projektordner):
    python scripts/dev/check-tailwind-classes.py                      # alle Views
    python scripts/dev/check-tailwind-classes.py resources/views/x.blade.php ...

Geprüft werden statische `class="…"`-Attribute und die Zeichenketten in `@class([...])`.
Alpine-Ausdrücke (`:class="…"`) und Klassen mit Blade-Ausgaben werden übersprungen.
Ausgabe: je Datei die fehlenden Klassen; Exit-Code 1, wenn etwas fehlt.
"""

import glob
import re
import sys

css_files = glob.glob('public/build/assets/*.css')
if not css_files:
    sys.exit('Kein Build gefunden (public/build/assets/*.css) – im Projektordner aufrufen.')

css = open(css_files[0], encoding='utf8').read()
selectors = {m.replace('\\', '') for m in re.findall(r'\.((?:[A-Za-z0-9_-]|\\.)+)', css)}



def top_level_literals(code):
    """Zeichenketten in '…' außerhalb von Klammern – also Array-Schlüssel/-Werte, nicht Funktionsargumente
    wie request()->routeIs('home')."""
    literals, depth, i = [], 0, 0
    while i < len(code):
        char = code[i]
        if char == "'":
            end = code.find("'", i + 1)
            if end == -1:
                break
            if depth == 0:
                literals.append(code[i + 1:end])
            i = end + 1
            continue
        if char in '([':
            depth += 1
        elif char in ')]':
            depth -= 1
        i += 1
    return literals


paths = sys.argv[1:] or glob.glob('resources/views/**/*.blade.php', recursive=True)
missing_any = False

for path in sorted(paths):
    view = open(path, encoding='utf8').read()
    classes = set()

    # class="…" (nicht :class="…")
    for match in re.finditer(r'(?<![:\w-])class="([^"]+)"', view):
        classes.update(c for c in match.group(1).split() if not re.search(r'[{}$\'"?]', c))

    # @class([...]): Schlüssel/Werte in einfachen Anführungszeichen
    for block in re.finditer(r'@class\(\[(.*?)\]\)\s*(?:>|\n|\s\w)', view, re.S):
        for literal in top_level_literals(block.group(1)):
            classes.update(c for c in literal.split() if not re.search(r'[{}$]', c))

    missing = sorted(c for c in classes if c not in selectors)
    if missing:
        missing_any = True
        print(f'{path}: {" ".join(missing)}')

if not missing_any:
    print('Alle Klassen sind im Build vorhanden.')

sys.exit(1 if missing_any else 0)
