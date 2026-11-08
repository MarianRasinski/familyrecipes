<x-layouts.app title="Training">
    @php
        $workoutsById = $workouts->keyBy('id');
        $scheduledCount = $schedule->count();
    @endphp

    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="mb-2 text-sm font-semibold tracking-widest text-accent uppercase">Mein Trainingsplan</p>
            <h1 class="font-display text-4xl font-semibold tracking-tight sm:text-5xl">Training</h1>
            <p class="mt-2 text-ink-soft">Einheiten aus dem Übungskatalog zusammenstellen und auf die Woche verteilen.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('workout-sessions.index') }}" class="btn btn-ghost">Protokoll</a>
            <a href="{{ route('exercises.index') }}" class="btn btn-ghost">Übungskatalog</a>
        </div>
    </div>

    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.6fr)]">
        {{-- Wochenplan --}}
        <section class="card-section self-start" aria-labelledby="wochenplan">
            <h2 id="wochenplan" class="mb-1 font-display text-2xl font-semibold">Wochenplan</h2>
            <p class="mb-5 text-sm text-ink-soft">Jede Woche gleich. An diesen Tagen gilt in „Mein Plan“ automatisch das Trainingstag-Budget.</p>

            @if ($workouts->isEmpty())
                <p class="rounded-2xl bg-paper px-4 py-3 text-sm text-ink-soft">Lege zuerst eine Einheit an – rechts bzw. unten.</p>
            @else
                <form method="POST" action="{{ route('training.schedule') }}" class="space-y-3">
                    @csrf
                    @method('PUT')
                    @foreach (range(1, 7) as $weekday)
                        <div class="flex items-center gap-3">
                            <label for="day-{{ $weekday }}" class="w-24 shrink-0 text-sm font-semibold">{{ App\Support\Week::dayName($weekday) }}</label>
                            <select id="day-{{ $weekday }}" name="days[{{ $weekday }}]" class="field">
                                <option value="">Ruhetag</option>
                                @foreach ($workouts as $workout)
                                    <option value="{{ $workout->id }}" @selected((int) $schedule->get($weekday) === $workout->id)>{{ $workout->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                    @error('days.*') <p class="field-error">{{ $message }}</p> @enderror
                    <button type="submit" class="btn btn-primary w-full">Wochenplan speichern</button>
                </form>

                @if ($profile && $profile->training_days !== $scheduledCount)
                    <p class="mt-4 rounded-2xl bg-paper px-4 py-3 text-xs text-ink-soft">
                        In deinem Profil stehen {{ $profile->training_days }} Trainingstage pro Woche, hier sind es {{ $scheduledCount }}.
                        Der Wochendurchschnitt im Profil rechnet mit der Zahl aus dem Profil –
                        <a href="{{ route('body-profile.edit') }}" class="font-semibold text-accent hover:text-ink">Profil anpassen</a>.
                    </p>
                @endif
            @endif
        </section>

        <div class="flex flex-col gap-6">
            {{-- Einheiten --}}
            <section aria-labelledby="einheiten">
                <h2 id="einheiten" class="mb-4 font-display text-2xl font-semibold">Meine Einheiten</h2>

                @if ($workouts->isEmpty())
                    <p class="text-sm text-ink-soft">Noch keine Einheiten.</p>
                @else
                    <ul class="space-y-3">
                        @foreach ($workouts as $workout)
                            <li>
                                <a href="{{ route('workouts.edit', $workout) }}" class="flex items-center gap-4 rounded-3xl border border-line bg-white p-5 transition hover:border-ink-soft">
                                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-paper-deep text-2xl" aria-hidden="true">🏋️</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-semibold">{{ $workout->name }}</span>
                                        <span class="block text-xs text-ink-soft">
                                            {{ $workout->exercises->count() }} {{ $workout->exercises->count() === 1 ? 'Übung' : 'Übungen' }}
                                            @if ($workout->exercises->isNotEmpty())
                                                · ca. {{ $workout->estimatedMinutes() }} Min.
                                            @endif
                                            @if ($workout->schedule->isNotEmpty())
                                                · {{ $workout->schedule->map(fn ($entry) => mb_substr(App\Support\Week::dayName($entry->weekday), 0, 2))->implode(', ') }}
                                            @endif
                                        </span>
                                    </span>
                                    <span class="text-ink-soft" aria-hidden="true">→</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            @if ($recentSessions->isNotEmpty())
                <section aria-labelledby="zuletzt">
                    <div class="mb-3 flex items-baseline justify-between gap-3">
                        <h2 id="zuletzt" class="font-display text-xl font-semibold">Zuletzt trainiert</h2>
                        <a href="{{ route('workout-sessions.index') }}" class="text-sm font-medium text-ink-soft hover:text-ink">Protokoll →</a>
                    </div>
                    <ul class="divide-y divide-line overflow-hidden rounded-3xl border border-line bg-white">
                        @foreach ($recentSessions as $session)
                            <li>
                                <a href="{{ route('workout-sessions.show', $session) }}" class="flex items-center gap-3 px-5 py-3 text-sm hover:bg-paper">
                                    <span class="w-16 shrink-0 text-ink-soft">{{ $session->date->format('d.m.') }}</span>
                                    <span class="min-w-0 flex-1 font-semibold">{{ $session->name }}</span>
                                    <span class="text-xs text-ink-soft">{{ $session->isFinished() ? '✓' : 'läuft' }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Neue Einheit --}}
            <form method="POST" action="{{ route('workouts.store') }}" class="card-section space-y-3">
                @csrf
                <h2 class="font-display text-xl font-semibold">Neue Einheit</h2>
                <label for="name" class="field-label">Name</label>
                <div class="flex flex-wrap gap-2">
                    <input id="name" name="name" required maxlength="100" class="field flex-1" placeholder="z. B. Ganzkörper A, Oberkörper, Laufen" value="{{ old('name') }}">
                    <button type="submit" class="btn btn-primary">Anlegen</button>
                </div>
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </form>

            {{-- Vorlage --}}
            <form method="POST" action="{{ route('training.template') }}" class="card-section space-y-4">
                @csrf
                <div>
                    <h2 class="font-display text-xl font-semibold">Vorlage: Ganzkörper</h2>
                    <p class="text-sm text-ink-soft">
                        Stellt eine Einheit aus dem Katalog zusammen – nur Übungen mit vorhandenen Geräten, möglichst viele
                        Muskelgruppen. Danach kannst du alles anpassen. Üblich: 2–3 × pro Woche mit einem Ruhetag dazwischen.
                    </p>
                </div>
                <fieldset>
                    <legend class="field-label">Stufe</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($levels as $level)
                            <label class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-2xl whitespace-nowrap border border-line p-3 text-sm font-semibold transition hover:border-ink-soft has-[:checked]:border-accent has-[:checked]:bg-accent/5">
                                <input type="radio" name="level" value="{{ $level->value }}" class="size-4 accent-accent" @checked($loop->first) required>
                                {{ $level->label() }}
                            </label>
                        @endforeach
                    </div>
                    @error('level') <p class="field-error">{{ $message }}</p> @enderror
                </fieldset>
                <button type="submit" class="btn btn-ghost w-full">Einheit aus Vorlage erstellen</button>
            </form>
        </div>
    </div>
</x-layouts.app>
