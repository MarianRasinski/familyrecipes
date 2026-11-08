<x-layouts.app :title="$session->name . ' · ' . $session->date->format('d.m.')">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('workout-sessions.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-ink-soft hover:text-ink">← Protokoll</a>

        <div class="mb-6">
            <p class="mb-2 text-sm font-semibold tracking-widest text-accent uppercase">
                {{ App\Support\Week::dayName($session->date->dayOfWeekIso) }}, {{ $session->date->format('d.m.Y') }}
                · {{ $session->isFinished() ? 'abgeschlossen' : 'läuft' }}
            </p>
            <h1 class="font-display text-4xl leading-tight font-semibold tracking-tight">{{ $session->name }}</h1>
            <p class="mt-2 text-ink-soft">
                {{ $session->doneCount() }} von {{ $session->sets->count() }} Sätzen erledigt
                @if ($session->volumeKg() > 0)
                    · {{ number_format($session->volumeKg(), 0, ',', '.') }} kg bewegt
                @endif
            </p>
        </div>

        <form method="POST" action="{{ route('workout-sessions.update', $session) }}" class="space-y-4">
            @csrf
            @method('PUT')
            {{-- Enter speichert zwischen (nicht abschließen) --}}
            <button type="submit" class="hidden" tabindex="-1" aria-hidden="true"></button>

            @foreach ($groups as $sets)
                @php
                    $exercise = $sets->first()->exercise;
                    $previous = $last->get($exercise->id);
                    $timed = $sets->first()->isTimed();
                @endphp
                <fieldset class="card-section space-y-3">
                    <legend class="sr-only">{{ $exercise->name }}</legend>
                    <div>
                        <p class="flex flex-wrap items-baseline justify-between gap-x-4">
                            <a href="{{ route('exercises.show', $exercise) }}" class="font-display text-xl font-semibold hover:text-accent-dark">{{ $exercise->name }}</a>
                            @if ($sets->first()->target)
                                <span class="text-sm text-ink-soft">Ziel: {{ $sets->count() }} × {{ $sets->first()->target }}</span>
                            @endif
                        </p>
                        @if ($previous?->isNotEmpty())
                            <p class="text-xs text-ink-soft">
                                Letztes Mal ({{ $previous->first()->session->date->format('d.m.') }}):
                                {{ $previous->map->resultLabel()->implode(' · ') }}
                            </p>
                        @endif
                    </div>

                    <ol class="divide-y divide-line">
                        @foreach ($sets as $set)
                            @php
                                $key = "sets.{$set->id}";
                                $name = "sets[{$set->id}]";
                            @endphp
                            <li class="flex flex-wrap items-center gap-3 py-2.5">
                                <label class="flex w-20 shrink-0 cursor-pointer items-center gap-2 text-sm font-semibold">
                                    <input type="hidden" name="{{ $name }}[done]" value="0">
                                    <input type="checkbox" name="{{ $name }}[done]" value="1" class="size-5 accent-accent"
                                           @checked(old("{$key}.done", $set->done))>
                                    Satz {{ $set->set_number }}
                                </label>

                                @if ($timed)
                                    <label class="flex items-center gap-2 text-sm text-ink-soft">
                                        <span class="sr-only">Sekunden in Satz {{ $set->set_number }}</span>
                                        <input type="number" name="{{ $name }}[seconds]" min="0" max="36000" inputmode="numeric" class="field w-20 py-1.5!"
                                               value="{{ old("{$key}.seconds", $set->seconds) }}" placeholder="{{ App\Support\Training\ExerciseTarget::parse($set->target)->max ?? '' }}">
                                        s
                                    </label>
                                @else
                                    <label class="flex items-center gap-2 text-sm text-ink-soft">
                                        <span class="sr-only">Wiederholungen in Satz {{ $set->set_number }}</span>
                                        <input type="number" name="{{ $name }}[reps]" min="0" max="1000" inputmode="numeric" class="field w-20 py-1.5!"
                                               value="{{ old("{$key}.reps", $set->reps) }}" placeholder="{{ App\Support\Training\ExerciseTarget::parse($set->target)->max ?? 'Wdh.' }}">
                                        Wdh.
                                    </label>
                                @endif

                                <label class="flex items-center gap-2 text-sm text-ink-soft">
                                    <span class="sr-only">Gewicht in Satz {{ $set->set_number }}</span>
                                    <input name="{{ $name }}[weight_kg]" inputmode="decimal" class="field w-20 py-1.5!"
                                           value="{{ old("{$key}.weight_kg", $set->weight_kg !== null ? str_replace('.', ',', (string) $set->weight_kg) : '') }}" placeholder="–">
                                    kg
                                </label>

                                @foreach (['reps', 'seconds', 'weight_kg'] as $field)
                                    @error("{$key}.{$field}") <p class="field-error w-full">{{ $message }}</p> @enderror
                                @endforeach
                            </li>
                        @endforeach
                    </ol>
                </fieldset>
            @endforeach

            <div class="card-section">
                <label for="notes" class="field-label">Notizen <span class="font-normal text-ink-soft">(optional)</span></label>
                <textarea id="notes" name="notes" rows="2" maxlength="2000" class="field" placeholder="z. B. Knie zwickt, nächstes Mal schwerer">{{ old('notes', $session->notes) }}</textarea>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn btn-ghost flex-1">Zwischenspeichern</button>
                <button type="submit" name="finish" value="1" class="btn btn-primary flex-1">
                    {{ $session->isFinished() ? 'Änderungen speichern' : 'Training abschließen' }}
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('workout-sessions.destroy', $session) }}" class="mt-8 text-center"
              onsubmit="return confirm('Dieses Training wirklich aus dem Protokoll löschen?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Training löschen</button>
        </form>
    </div>
</x-layouts.app>
