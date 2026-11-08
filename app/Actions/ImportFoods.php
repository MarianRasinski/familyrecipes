<?php

namespace App\Actions;

use App\Enums\FoodCategory;
use App\Models\Food;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use SplFileObject;

/**
 * Importiert Lebensmittel aus der CSV, die scripts/bls/convert.py aus dem BLS erzeugt.
 *
 * Wiederholbar: Vorhandene Einträge (gleicher BLS-Code) werden aktualisiert, eigene Lebensmittel
 * ohne BLS-Code bleiben unberührt. Leere Nährwertfelder = „keine Daten“ und werden als null gespeichert.
 */
class ImportFoods
{
    public const COLUMNS = ['code', 'name', 'name_en', 'kcal', 'protein', 'fat', 'carbs', 'fiber', 'sugar', 'saturated_fat', 'salt'];

    private const NUTRIENTS = ['kcal', 'protein', 'fat', 'carbs', 'fiber', 'sugar', 'saturated_fat', 'salt'];

    private const CHUNK = 500;

    /**
     * @return array{created: int, updated: int}
     *
     * @throws InvalidArgumentException bei unbekanntem Dateiformat
     */
    public function handle(string $path, string $source = 'BLS 4.0'): array
    {
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD | SplFileObject::DROP_NEW_LINE);

        $header = array_map(fn ($column) => trim((string) $column, "\u{FEFF} "), (array) $file->current());
        $file->next();

        if (array_diff(self::COLUMNS, $header) !== []) {
            throw new InvalidArgumentException('Unbekanntes Format – erwartet werden die Spalten: '.implode(', ', self::COLUMNS));
        }

        $before = Food::whereNotNull('bls_code')->count();
        $total = 0;
        $chunk = [];
        $now = now();

        DB::transaction(function () use ($file, $header, $source, $now, &$chunk, &$total) {
            for (; $file->valid(); $file->next()) {
                $row = (array) $file->current();

                if (count($row) !== count($header)) {
                    continue;
                }

                $chunk[] = $this->map(array_combine($header, $row), $source, $now);
                $total++;

                if (count($chunk) === self::CHUNK) {
                    $this->flush($chunk);
                }
            }

            $this->flush($chunk);
        });

        $created = Food::whereNotNull('bls_code')->count() - $before;

        return ['created' => $created, 'updated' => $total - $created];
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    private function map(array $row, string $source, $now): array
    {
        $code = trim($row['code']);

        $data = [
            'bls_code' => $code,
            'name' => trim($row['name']),
            'name_en' => trim($row['name_en']) ?: null,
            'category' => FoodCategory::fromBlsCode($code)?->value,
            'source' => $source,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        foreach (self::NUTRIENTS as $field) {
            $value = trim($row[$field]);
            $data[$field] = $value === '' ? null : (float) $value;
        }

        return $data;
    }

    /**
     * @param  list<array<string, mixed>>  $chunk
     */
    private function flush(array &$chunk): void
    {
        if ($chunk === []) {
            return;
        }

        Food::upsert($chunk, ['bls_code'], ['name', 'name_en', 'category', 'source', 'updated_at', ...self::NUTRIENTS]);
        $chunk = [];
    }
}
