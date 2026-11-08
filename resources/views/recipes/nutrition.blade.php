<x-layouts.app :title="'Nährwerte: '.$recipe->title">
    <a href="{{ route('recipes.show', $recipe) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-ink-soft hover:text-ink">
        ← {{ $recipe->title }}
    </a>

    <section class="mb-8 max-w-3xl">
        <p class="mb-2 text-sm font-semibold tracking-widest text-accent uppercase">Nährwerte berechnen</p>
        <h1 class="font-display text-4xl leading-tight font-semibold tracking-tight">{{ $recipe->title }}</h1>
        <p class="mt-3 text-ink-soft">
            Wähle zu jeder Zutat das passende Lebensmittel. <strong>Vorschläge</strong> sind vorausgefüllt und werden erst
            beim Speichern übernommen. Das Gewicht wird aus Menge und Einheit geschätzt – trag es ein, wenn du es genauer weißt.
            Gewürze und Kräuter ohne Menge sowie optionale Zutaten zählen nicht mit.
        </p>
    </section>

    @if ($errors->any())
        <div class="card-section mb-6 text-accent-dark" role="alert">
            <p class="font-semibold">Bitte prüfen:</p>
            <ul class="pl-5 text-sm">
                @foreach ($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('recipes.nutrition.update', $recipe) }}" class="flex flex-col gap-4">
        @csrf
        @method('PUT')

        @foreach ($rows as $i => $row)
            @php
                $line = $row['line'];
                $ingredient = $line->ingredient;
                $food = $row['food'];
                $estimate = $row['estimate'];
            @endphp
            <fieldset @class(['card-section flex flex-col gap-4', 'opacity-60' => $ingredient->is_optional])
                      x-data="{
                          selected: @js($food ? ['id' => $food->id, 'name' => $food->name] : null),
                          suggestion: @js($row['is_suggestion']),
                          editing: false,
                          q: '',
                          results: [],
                          loading: false,
                          async search() {
                              if (this.q.trim().length < 2) { this.results = []; return; }
                              this.loading = true;
                              const response = await fetch(@js(route('foods.search')) + '?q=' + encodeURIComponent(this.q), { headers: { Accept: 'application/json' } });
                              this.results = await response.json();
                              this.loading = false;
                          },
                          choose(food) { this.selected = food; this.suggestion = false; this.editing = false; this.q = ''; this.results = []; },
                      }">
                <input type="hidden" name="ingredients[{{ $i }}][id]" value="{{ $ingredient->id }}">
                <input type="hidden" name="ingredients[{{ $i }}][food_id]" :value="selected ? selected.id : ''" value="{{ $food?->id }}">

                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <legend class="font-semibold">
                        {{ trim("{$ingredient->amount} {$ingredient->unit}") }} {{ $ingredient->name }}
                        @if ($ingredient->is_optional)
                            <span class="text-xs text-ink-soft">(optional)</span>
                        @endif
                    </legend>
                    @if ($row['is_suggestion'])
                        <span class="text-xs text-accent">Vorschlag – noch nicht gespeichert</span>
                    @else
                        <span @class(['text-xs', 'text-accent-dark' => $line->isMissing(), 'text-ink-soft' => ! $line->isMissing()])>{{ $line->statusLabel() }}</span>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    {{-- Lebensmittel --}}
                    <div class="relative">
                        <p class="field-label">Lebensmittel</p>
                        <div class="flex flex-wrap items-center gap-2" x-show="selected && ! editing">
                            <span class="chip gap-1">
                                <span x-text="selected && selected.name">{{ $food?->name }}</span>
                                <span x-show="suggestion" class="text-xs text-accent">Vorschlag</span>
                            </span>
                            <button type="button" class="text-sm font-semibold text-accent hover:text-ink" @click="editing = true; $nextTick(() => $refs.search.focus())">ändern</button>
                            <button type="button" class="text-sm text-ink-soft hover:text-ink" @click="selected = null; suggestion = false">entfernen</button>
                        </div>
                        <div x-show="! selected || editing" @click.outside="results = []" @if ($food) x-cloak @endif>
                            <label for="food-search-{{ $i }}" class="sr-only">Lebensmittel suchen für {{ $ingredient->name }}</label>
                            <input id="food-search-{{ $i }}" x-ref="search" type="search" class="field" placeholder="Lebensmittel suchen …" autocomplete="off"
                                   x-model="q" @input.debounce.300ms="search()" @keydown.escape="results = []; editing = false">
                            <div x-cloak x-show="results.length > 0"
                                 class="absolute left-0 z-10 mt-2 w-full overflow-hidden rounded-2xl border border-line bg-white py-2 text-sm shadow-[0_12px_32px_-12px_rgb(43_36_32/0.25)]">
                                <template x-for="food in results" :key="food.id">
                                    <button type="button" class="block w-full px-4 py-2 text-left hover:bg-paper" @click="choose(food)">
                                        <span x-text="food.name"></span>
                                        <span class="text-xs text-ink-soft" x-text="(food.kcal ?? '–') + ' kcal/100 g · ' + (food.category ?? '')"></span>
                                    </button>
                                </template>
                            </div>
                            <p x-cloak x-show="! loading && q.trim().length >= 2 && results.length === 0" class="mt-1 text-xs text-ink-soft">Nichts gefunden – kürzeres Wort versuchen.</p>
                        </div>
                    </div>

                    {{-- Gewicht --}}
                    <div>
                        <label for="grams-{{ $i }}" class="field-label">Gewicht in g (ganze Menge)</label>
                        <input id="grams-{{ $i }}" type="text" inputmode="decimal" name="ingredients[{{ $i }}][grams]" class="field"
                               value="{{ old("ingredients.{$i}.grams", $ingredient->grams === null ? '' : rtrim(rtrim(number_format($ingredient->grams, 1, ',', ''), '0'), ',')) }}"
                               placeholder="{{ $estimate->isKnown() ? '≈ '.rtrim(rtrim(number_format($estimate->grams, 1, ',', ''), '0'), ',').' g' : 'bitte eintragen' }}">
                        <p class="mt-1 text-xs text-ink-soft">
                            @if ($estimate->negligible && $row['is_spice'])
                                Gewürz ohne Menge – wird vernachlässigt, außer du trägst ein Gewicht ein.
                            @elseif ($estimate->negligible)
                                Keine Menge im Rezept – bitte Gewicht eintragen.
                            @elseif ($estimate->isKnown())
                                {{ $estimate->estimated ? 'Geschätzt' : 'Aus der Mengenangabe' }}{{ $estimate->note ? ': '.$estimate->note : '' }}. Leer lassen, um das zu verwenden.
                            @else
                                {{ $estimate->note }} – bitte Gewicht eintragen.
                            @endif
                        </p>
                    </div>
                </div>
            </fieldset>
        @endforeach

        <div class="flex flex-wrap items-center gap-3 pt-2">
            <button type="submit" class="btn btn-primary">Zuordnung speichern</button>
            <a href="{{ route('recipes.show', $recipe) }}" class="btn btn-ghost">Abbrechen</a>
        </div>
    </form>
</x-layouts.app>
