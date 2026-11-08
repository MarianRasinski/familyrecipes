"""
Wandelt die BLS-Hauptdatei (Excel) in die schlanke CSV um, die `php artisan foods:import` liest.

Aufruf (Python 3 mit openpyxl):
    python -I scripts/bls/convert.py TrainJ/BLS_4_0_2025_DE/BLS_4_0_Daten_2025_DE.xlsx database/data/foods/bls_4_0.csv

Übernommen werden die Werte je 100 g essbarem Anteil für Energie und die wichtigsten Nährstoffe.
"-" (fehlender Wert) bleibt leer – das ist nicht dasselbe wie 0. "TR" (Spuren) und "<LOQ"/"<LOD"
werden als 0 übernommen. Spalte `missing` listet Nährstoffe ohne Wert.
"""
import csv
import sys

import openpyxl

# BLS-Code → CSV-Spalte
NUTRIENTS = {
    'ENERCC': 'kcal',
    'PROT625': 'protein',
    'FAT': 'fat',
    'CHO': 'carbs',
    'FIBT': 'fiber',
    'SUGAR': 'sugar',
    'FASAT': 'saturated_fat',
    'NACL': 'salt',
}


def value(raw):
    if raw is None:
        return None
    if isinstance(raw, (int, float)):
        return round(float(raw), 2)
    text = str(raw).strip()
    if text in ('', '-'):
        return None
    if text.upper() == 'TR' or text.startswith('<'):
        return 0.0
    return round(float(text.replace(',', '.')), 2)


def main(source, target):
    sheet = openpyxl.load_workbook(source, read_only=True).worksheets[0]
    rows = sheet.iter_rows(values_only=True)
    header = next(rows)
    columns = {code: header.index(next(h for h in header if h and h.split(' ')[0] == code and '[' in h)) for code in NUTRIENTS}

    with open(target, 'w', newline='', encoding='utf-8') as out:
        writer = csv.writer(out)
        writer.writerow(['code', 'name', 'name_en', *NUTRIENTS.values(), 'missing'])
        count = 0
        for row in rows:
            if not row[0]:
                continue
            values = {code: value(row[i]) for code, i in columns.items()}
            missing = [NUTRIENTS[c] for c, v in values.items() if v is None]
            writer.writerow([
                row[0].strip(), row[1].strip(), (row[2] or '').strip(),
                *['' if v is None else ('%g' % v) for v in values.values()],
                ' '.join(missing),
            ])
            count += 1
    print(f'{count} Lebensmittel nach {target} geschrieben')


if __name__ == '__main__':
    main(sys.argv[1], sys.argv[2])
