<x-layouts.app :title="$workout->name">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('training.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-ink-soft hover:text-ink">← Training</a>

        <div class="mb-8">
            <h1 class="font-display text-4xl leading-tight font-semibold tracking-tight">{{ $workout->name }}</h1>
            <p class="mt-2 text-ink-soft">
                {{ $workout->exercises->count() }} {{ $workout->exercises->count() === 1 ? 'Übung' : 'Übungen' }}
                @if ($workout->exercises->isNotEmpty())
                    · ca. {{ $workout->estimatedMinutes() }} Minuten
                @endif
                @if ($workout->schedule->isNotEmpty())
                    · {{ $workout->schedule->map(fn ($entry) => App\Support\Week::dayName($entry->weekday))->implode(', ') }}
                @endif
            </p>
        </div>

        @if ($workout->exercises->isNotEmpty())
            <form method="POST" action="{{ route('workout-sessions.store', $workout) }}" class="mb-8">
                @csrf
                <button type="submit" class="btn btn-primary w-full">▶ Training starten</button>
            </form>
        @endif

        {{-- Einheit mit allen Übungen (ein Formular; ↑ ↓ und Entfernen speichern die Eingaben mit) --}}
        <form method="POST" action="{{ route('workouts.update', $workout) }}" class="mb-8 space-y-4">
            @csrf
            @method('PUT')
            {{-- Enter im Eingabefeld soll speichern, nicht den ersten ↑-Knopf auslösen --}}
            <button type="submit" class="hidden" tabindex="-1" aria-hidden="true"></button>

            <div class="card-section space-y-4">
                <div>
                    <label for="name" class="field-label">Name</label>
                    <input id="name" name="name" required maxlength="100" class="field" value="{{ old('name', $workout->name) }}">
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="notes" class="field-label">Notizen <span class="font-normal text-ink-soft">(optional)</span></label>
                    <textarea id="notes" name="notes" rows="2" maxlength="2000" class="field" placeholder="z. B. Aufwärmen: 5 Minuten Seilspringen">{{ old('notes', $workout->notes) }}</textarea>
                </div>
            </div>

            @forelse ($workout->exercises as $item)
                @php
                    $key = "exercises.{$item->id}";
                    $name = "exercises[{$item->id}]";
                @endphp
                <fieldset id="uebung-{{ $item->id }}" class="card-section scroll-mt-24 space-y-4">
                    <legend class="sr-only">{{ $loop->iteration }}. {{ $item->exercise->name }}</legend>
                    <div class="flex items-start justify-between gap-3">
                        <p class="flex items-baseline gap-3">
                            <span class="font-display text-xl font-semibold text-accent">{{ $loop->iteration }}</span>
                            <a href="{{ route('exercises.show', $item->exercise) }}" class="font-display text-xl font-semibold hover:text-accent-dark">{{ $item->exercise->name }}</a>
                        </p>
                        <div class="flex shrink-0 items-center gap-1">
                            @unless ($loop->first)
                                <button type="submit" name="move" value="{{ $item->id }}:up" class="btn btn-ghost px-3! py-1.5!" aria-label="{{ $item->exercise->name }} nach oben" title="Nach oben">↑</button>
                            @endunless
                            @unless ($loop->last)
                                <button type="submit" name="move" value="{{ $item->id }}:down" class="btn btn-ghost px-3! py-1.5!" aria-label="{{ $item->exercise->name }} nach unten" title="Nach unten">↓</button>
                            @endunless
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                        <div>
                            <label for="sets-{{ $item->id }}" class="field-label">Sätze</label>
                            <input id="sets-{{ $item->id }}" type="number" name="{{ $name }}[sets]" min="1" max="20" inputmode="numeric" required class="field"
                                   value="{{ old("{$key}.sets", $item->sets) }}">
                            @error("{$key}.sets") <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="target-{{ $item->id }}" class="field-label">Ziel je Satz</label>
                            <input id="target-{{ $item->id }}" name="{{ $name }}[target]" maxlength="50" class="field" placeholder="z. B. 10-12"
                                   value="{{ old("{$key}.target", $item->target) }}">
                            @error("{$key}.target") <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="rest-{{ $item->id }}" class="field-label">Pause (Sek.)</label>
                            <input id="rest-{{ $item->id }}" type="number" name="{{ $name }}[rest_seconds]" min="0" max="900" step="5" inputmode="numeric" class="field"
                                   value="{{ old("{$key}.rest_seconds", $item->rest_seconds) }}">
                            @error("{$key}.rest_seconds") <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex flex-wrap items-end gap-3">
                        <div class="min-w-0 flex-1">
                            <label for="notes-{{ $item->id }}" class="field-label">Notiz <span class="font-normal text-ink-soft">(optional)</span></label>
                            <input id="notes-{{ $item->id }}" name="{{ $name }}[notes]" maxlength="200" class="field" placeholder="z. B. 12 kg Kettlebell"
                                   value="{{ old("{$key}.notes", $item->notes) }}">
                        </div>
                        <button type="submit" name="remove" value="{{ $item->id }}" class="btn btn-danger">Entfernen</button>
                    </div>
                    @if ($item->exercise->target && $item->exercise->target !== $item->target)
                        <p class="text-xs text-ink-soft">Vorgabe im Katalog: {{ $item->exercise->prescription() }}</p>
                    @endif
                </fieldset>
            @empty
                <p class="card-section text-sm text-ink-soft">Noch keine Übungen – füge unten die erste hinzu.</p>
            @endforelse

            <button type="submit" class="btn btn-primary w-full">Einheit speichern</button>
        </form>

        {{-- Übung hinzufügen --}}
        <form method="POST" action="{{ route('workouts.exercises.store', $workout) }}" class="card-section mb-8 space-y-4"
              x-data="recipePicker({ recipes: @js($catalog), selected: null })">
            @csrf
            <h2 class="font-display text-xl font-semibold">Übung hinzufügen</h2>
            <input type="hidden" name="exercise_id" :value="selectedId ?? ''">

            <template x-if="selected">
                <div class="flex items-center gap-3 rounded-2xl border-2 border-accent bg-accent/5 p-3">
                    <span class="text-2xl" aria-hidden="true" x-text="selected.emoji"></span>
                    <span class="min-w-0 flex-1 font-semibold" x-text="selected.title"></span>
                    <button type="button" @click="clear()" class="btn btn-ghost py-1.5! text-xs">Ändern</button>
                </div>
            </template>

            <div x-show="! selected" class="space-y-3">
                <label for="exercise-search" class="field-label">Übung suchen</label>
                <input id="exercise-search" x-ref="search" type="search" x-model="query" class="field" placeholder="z. B. Squat, Plank, Rudern …" autocomplete="off">
                <ul class="max-h-72 divide-y divide-line overflow-y-auto overscroll-contain rounded-2xl border border-line bg-white" role="listbox" aria-label="Übungen">
                    <template x-for="exercise in filtered" :key="exercise.id">
                        <li>
                            <button type="button" @click="select(exercise)" role="option" :aria-selected="selectedId === exercise.id"
                                    class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-paper">
                                <span class="text-xl" aria-hidden="true" x-text="exercise.emoji"></span>
                                <span class="min-w-0 flex-1" x-text="exercise.title"></span>
                            </button>
                        </li>
                    </template>
                    <li x-show="filtered.length === 0" class="px-4 py-6 text-center text-sm text-ink-soft">Keine passende Übung.</li>
                </ul>
                <p class="text-xs text-ink-soft">🏠 = alle nötigen Geräte vorhanden</p>
            </div>
            @error('exercise_id') <p class="field-error">{{ $message }}</p> @enderror

            <button type="submit" class="btn btn-primary w-full">Hinzufügen</button>
        </form>

        <form method="POST" action="{{ route('workouts.destroy', $workout) }}" class="text-center"
              onsubmit="return confirm(@js("„{$workout->name}“ wirklich löschen?"))">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Einheit löschen</button>
        </form>
    </div>
</x-layouts.app>
