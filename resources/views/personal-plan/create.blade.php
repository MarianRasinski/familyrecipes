<x-layouts.app title="Mahlzeit hinzufügen">
    <div class="mx-auto max-w-xl">
        <a href="{{ route('personal-plan.show', $week) }}#tag-{{ $date->toDateString() }}" class="mb-4 inline-flex text-sm font-semibold text-accent hover:text-ink">← Mein Plan</a>
        <h1 class="mb-2 font-display text-4xl font-semibold tracking-tight">Hinzufügen</h1>
        <p class="mb-8 text-ink-soft">{{ App\Support\Week::dayName($date->dayOfWeekIso) }}, {{ $date->format('d.m.Y') }}</p>

        <form method="POST" action="{{ route('planned-meals.store') }}" class="card-section space-y-5"
              x-data="{
                  type: @js(old('type', 'recipe')),
                  food: @js($oldFood),
                  grams: @js((string) old('grams', '')),
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
                  choose(food) { this.food = food; this.q = ''; this.results = []; },
              }">
            @csrf
            <input type="hidden" name="date" value="{{ $date->toDateString() }}">
            <input type="hidden" name="type" :value="type" value="recipe">

            <fieldset>
                <legend class="field-label">Mahlzeit</legend>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($slots as $option)
                        <label class="flex cursor-pointer items-center gap-2 rounded-2xl border border-line p-3 text-sm transition hover:border-ink-soft has-[:checked]:border-accent has-[:checked]:bg-accent/5">
                            <input type="radio" name="meal" value="{{ $option->value }}" class="size-4 accent-accent"
                                   @checked(old('meal', $slot->value) === $option->value)>
                            <span aria-hidden="true">{{ $option->emoji() }}</span> <span class="font-semibold">{{ $option->label() }}</span>
                        </label>
                    @endforeach
                </div>
                @error('meal') <p class="field-error">{{ $message }}</p> @enderror
            </fieldset>

            <div class="flex gap-1 rounded-full bg-paper-deep p-1 text-sm font-medium" role="tablist">
                <button type="button" role="tab" @click="type = 'recipe'" :aria-selected="type === 'recipe'"
                        :class="type === 'recipe' ? 'bg-white shadow-sm text-ink' : 'text-ink-soft'"
                        class="flex-1 rounded-full px-3 py-2 whitespace-nowrap transition">Rezept</button>
                <button type="button" role="tab" @click="type = 'food'" :aria-selected="type === 'food'"
                        :class="type === 'food' ? 'bg-white shadow-sm text-ink' : 'text-ink-soft'"
                        class="flex-1 rounded-full px-3 py-2 whitespace-nowrap transition">Lebensmittel</button>
            </div>

            {{-- Rezept --}}
            <div x-show="type === 'recipe'" class="space-y-5"
                 x-data="recipePicker({ recipes: @js($recipes), selected: @js(old('recipe_id') ? (int) old('recipe_id') : null) })">
                <input type="hidden" name="recipe_id" :value="selectedId ?? ''">

                <template x-if="selected">
                    <div class="flex items-center gap-3 rounded-2xl border-2 border-accent bg-accent/5 p-3">
                        <span class="text-2xl" aria-hidden="true" x-text="selected.emoji"></span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold" x-text="selected.title"></span>
                            <span class="block text-xs text-ink-soft" x-text="selected.kcal !== null ? selected.kcal + ' kcal pro Portion' : 'noch keine Nährwerte'"></span>
                        </span>
                        <button type="button" @click="clear()" class="btn btn-ghost py-1.5! text-xs">Ändern</button>
                    </div>
                </template>

                <div x-show="! selected" class="space-y-3">
                    <label for="recipe-search" class="field-label">Rezept suchen</label>
                    <input id="recipe-search" x-ref="search" type="search" x-model="query" class="field" placeholder="z. B. Lasagne, Porridge …" autocomplete="off">

                    <div class="flex flex-wrap gap-2" x-show="hasFavorites">
                        <button type="button" @click="onlyFavorites = true" :aria-pressed="onlyFavorites"
                                :class="onlyFavorites ? 'chip-active' : ''" class="chip">♥ Nur meine Favoriten</button>
                        <button type="button" @click="onlyFavorites = false" :aria-pressed="! onlyFavorites"
                                :class="! onlyFavorites ? 'chip-active' : ''" class="chip">Alle Rezepte</button>
                    </div>

                    <ul class="max-h-72 divide-y divide-line overflow-y-auto overscroll-contain rounded-2xl border border-line bg-white" role="listbox" aria-label="Rezepte">
                        <template x-for="recipe in filtered" :key="recipe.id">
                            <li>
                                <button type="button" @click="select(recipe)" role="option" :aria-selected="selectedId === recipe.id"
                                        class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-paper">
                                    <span class="text-xl" aria-hidden="true" x-text="recipe.emoji"></span>
                                    <span class="min-w-0 flex-1" x-text="recipe.title"></span>
                                    <span class="text-xs text-ink-soft" x-text="recipe.kcal !== null ? recipe.kcal + ' kcal' : ''"></span>
                                    <span x-show="recipe.favorite" class="text-accent" aria-label="Favorit">♥</span>
                                </button>
                            </li>
                        </template>
                        <li x-show="filtered.length === 0" class="px-4 py-6 text-center text-sm text-ink-soft">
                            <span x-show="onlyFavorites">Keine passenden Favoriten – <button type="button" class="underline" @click="onlyFavorites = false">alle Rezepte durchsuchen</button>.</span>
                            <span x-show="! onlyFavorites">Kein passendes Rezept – oder als Lebensmittel eintragen?</span>
                        </li>
                    </ul>
                </div>
                @error('recipe_id') <p class="field-error">{{ $message }}</p> @enderror

                <div>
                    <label for="servings" class="field-label">Portionen</label>
                    <input id="servings" name="servings" inputmode="decimal" class="field" value="{{ old('servings', '1') }}" :disabled="type !== 'recipe'">
                    <p class="mt-1 text-xs text-ink-soft">Auch halbe Portionen, z. B. 0,5 oder 1,5.</p>
                    @error('servings') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Lebensmittel --}}
            <div x-show="type === 'food'" x-cloak class="space-y-5">
                <input type="hidden" name="food_id" :value="food ? food.id : ''">

                <template x-if="food">
                    <div class="flex items-center gap-3 rounded-2xl border-2 border-accent bg-accent/5 p-3">
                        <span class="text-2xl" aria-hidden="true">🥕</span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold" x-text="food.name"></span>
                            <span class="block text-xs text-ink-soft" x-text="(food.kcal ?? '–') + ' kcal je 100 g'"></span>
                        </span>
                        <button type="button" @click="food = null; $nextTick(() => $refs.foodSearch.focus())" class="btn btn-ghost py-1.5! text-xs">Ändern</button>
                    </div>
                </template>

                <div x-show="! food" class="relative" @click.outside="results = []">
                    <label for="food-search" class="field-label">Lebensmittel suchen</label>
                    <input id="food-search" x-ref="foodSearch" type="search" class="field" placeholder="z. B. Haferflocken, Banane, Skyr …" autocomplete="off"
                           x-model="q" @input.debounce.300ms="search()" @keydown.escape="results = []">
                    <div x-cloak x-show="results.length > 0"
                         class="absolute left-0 z-10 mt-2 w-full overflow-hidden rounded-2xl border border-line bg-white py-2 text-sm shadow-[0_12px_32px_-12px_rgb(43_36_32/0.25)]">
                        <template x-for="result in results" :key="result.id">
                            <button type="button" class="block w-full px-4 py-2 text-left hover:bg-paper" @click="choose(result)">
                                <span x-text="result.name"></span>
                                <span class="text-xs text-ink-soft" x-text="(result.kcal ?? '–') + ' kcal/100 g · ' + (result.category ?? '')"></span>
                            </button>
                        </template>
                    </div>
                    <p x-cloak x-show="! loading && q.trim().length >= 2 && results.length === 0" class="mt-1 text-xs text-ink-soft">Nichts gefunden – kürzeres Wort versuchen.</p>
                </div>
                @error('food_id') <p class="field-error">{{ $message }}</p> @enderror

                <div>
                    <label for="grams" class="field-label">Menge in Gramm</label>
                    <input id="grams" name="grams" inputmode="decimal" class="field" x-model="grams" placeholder="z. B. 80" :disabled="type !== 'food'">
                    <p class="mt-1 text-xs text-ink-soft" x-cloak x-show="food && food.kcal !== null && parseFloat(grams.replace(',', '.')) > 0"
                       x-text="'≈ ' + Math.round(food.kcal * parseFloat(grams.replace(',', '.')) / 100) + ' kcal'"></p>
                    @error('grams') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-full">Einplanen</button>
        </form>
    </div>
</x-layouts.app>
