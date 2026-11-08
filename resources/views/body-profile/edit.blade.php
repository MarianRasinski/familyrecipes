<x-layouts.app title="Profil & Ziele">
    <div class="mx-auto max-w-3xl">
        <h1 class="mb-2 font-display text-4xl font-semibold tracking-tight">Profil &amp; Ziele</h1>
        <p class="mb-8 text-ink-soft">
            Aus deinen Angaben berechnen wir ein Tagesbudget für Kalorien und Nährstoffe.
            Nur du siehst dein Profil – auch das Familienoberhaupt nicht.
        </p>

        @if ($budget)
            @php
                $fmt = fn (float $value) => number_format($value, 0, ',', '.');
                $days = [
                    ['title' => 'Ruhetag', 'targets' => $budget->restDay()],
                ];
                if ($profile->training_days > 0) {
                    $days[] = ['title' => 'Trainingstag', 'targets' => $budget->trainingDay()];
                }
            @endphp

            <section class="card-section mb-8" aria-labelledby="budget-heading">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <h2 id="budget-heading" class="font-display text-2xl font-semibold">Dein Tagesbudget</h2>
                    <span class="chip">{{ $budget->isOverridden() ? 'selbst festgelegt' : 'berechnet' }}</span>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($days as $day)
                        <div class="rounded-2xl bg-paper p-5">
                            <p class="text-sm font-medium text-ink-soft">{{ $day['title'] }}</p>
                            <p class="mb-3 font-display text-4xl font-semibold">
                                {{ $fmt($day['targets']->kcal) }} <span class="text-base font-medium text-ink-soft">kcal</span>
                            </p>
                            <dl class="flex flex-wrap gap-2">
                                @foreach ([
                                    'Eiweiß' => $day['targets']->protein,
                                    'Kohlenhydrate' => $day['targets']->carbs,
                                    'Fett' => $day['targets']->fat,
                                    'Ballaststoffe' => $day['targets']->fiber,
                                ] as $label => $grams)
                                    <div class="chip gap-1">
                                        <dt class="text-ink-soft">{{ $label }}</dt>
                                        <dd class="font-semibold">{{ $fmt($grams) }} g</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endforeach
                </div>

                @if ($profile->training_days > 0)
                    <p class="mt-4 text-sm text-ink-soft">
                        Bei {{ $profile->training_days }} {{ $profile->training_days === 1 ? 'Trainingstag' : 'Trainingstagen' }} pro Woche
                        sind das im Schnitt <strong class="text-ink">{{ $fmt($budget->weeklyAverageKcal()) }} kcal</strong> am Tag.
                    </p>
                @endif

                @foreach ($budget->warnings() as $warning)
                    <p class="mt-4 rounded-2xl bg-accent/10 p-4 text-sm text-accent-dark">{{ $warning }}</p>
                @endforeach

                <details class="mt-6 text-sm">
                    <summary class="cursor-pointer font-semibold text-accent hover:text-ink">So wird gerechnet</summary>
                    <dl class="mt-4 divide-y divide-line">
                        @foreach ($budget->steps() as $step)
                            <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 py-3">
                                <div>
                                    <dt class="font-medium">{{ $step['label'] }}</dt>
                                    <p class="text-xs text-ink-soft">{{ $step['formula'] }}</p>
                                </div>
                                <dd class="font-semibold whitespace-nowrap">{{ $step['result'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    <p class="mt-3 text-xs leading-relaxed text-ink-soft">
                        Orientierungswerte für gesunde Erwachsene, keine medizinische Beratung. Der tatsächliche Bedarf
                        kann um 10–20 % abweichen – beobachte dein Gewicht über einige Wochen und passe das Budget an.
                    </p>
                </details>
            </section>
        @endif

        <form method="POST" action="{{ route('body-profile.update') }}" class="card-section space-y-5"
              x-data="{ trainingDays: {{ (int) old('training_days', $profile->training_days) }} }">
            @csrf
            @method('PUT')

            <h2 class="font-display text-xl font-semibold">{{ $profile->exists ? 'Angaben ändern' : 'Deine Angaben' }}</h2>

            <fieldset>
                <legend class="field-label">Geschlecht (für die Formel) *</legend>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($sexes as $sex)
                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-line p-4 text-sm transition hover:border-ink-soft has-[:checked]:border-accent has-[:checked]:bg-accent/5">
                            <input type="radio" name="sex" value="{{ $sex->value }}" required
                                   @checked(old('sex', $profile->sex?->value) === $sex->value) class="size-4 shrink-0 accent-accent">
                            <span class="font-semibold">{{ $sex->label() }}</span>
                        </label>
                    @endforeach
                </div>
                @error('sex') <p class="field-error">{{ $message }}</p> @enderror
            </fieldset>

            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <label for="birth_date" class="field-label">Geburtsdatum *</label>
                    <input id="birth_date" type="date" name="birth_date" required class="field"
                           value="{{ old('birth_date', $profile->birth_date?->toDateString()) }}">
                    @error('birth_date') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="height_cm" class="field-label">Größe in cm *</label>
                    <input id="height_cm" type="number" name="height_cm" required min="120" max="230" inputmode="numeric" class="field"
                           value="{{ old('height_cm', $profile->height_cm) }}" placeholder="z. B. 175">
                    @error('height_cm') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="weight_kg" class="field-label">Gewicht in kg *</label>
                    <input id="weight_kg" name="weight_kg" required inputmode="decimal" class="field"
                           value="{{ old('weight_kg', $profile->weight_kg !== null ? str_replace('.', ',', (string) $profile->weight_kg) : null) }}" placeholder="z. B. 72,5">
                    @error('weight_kg') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <fieldset>
                <legend class="field-label">Aktivität im Alltag (ohne Sport) *</legend>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($activityLevels as $level)
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-line p-4 text-sm transition hover:border-ink-soft has-[:checked]:border-accent has-[:checked]:bg-accent/5">
                            <input type="radio" name="activity_level" value="{{ $level->value }}" required
                                   @checked(old('activity_level', $profile->activity_level?->value) === $level->value) class="mt-0.5 size-4 shrink-0 accent-accent">
                            <span>
                                <span class="block font-semibold">{{ $level->label() }}</span>
                                <span class="block text-xs leading-relaxed text-ink-soft">{{ $level->description() }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('activity_level') <p class="field-error">{{ $message }}</p> @enderror
            </fieldset>

            <fieldset>
                <legend class="field-label">Ziel *</legend>
                <div class="grid gap-2 sm:grid-cols-3">
                    @foreach ($goals as $goal)
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-line p-4 text-sm transition hover:border-ink-soft has-[:checked]:border-accent has-[:checked]:bg-accent/5">
                            <input type="radio" name="goal" value="{{ $goal->value }}" required
                                   @checked(old('goal', $profile->goal?->value) === $goal->value) class="mt-0.5 size-4 shrink-0 accent-accent">
                            <span>
                                <span class="block font-semibold">{{ $goal->label() }}</span>
                                <span class="block text-xs leading-relaxed text-ink-soft">{{ $goal->description() }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('goal') <p class="field-error">{{ $message }}</p> @enderror
            </fieldset>

            <div class="space-y-5 border-t border-line pt-8">
                <h3 class="font-display text-lg font-semibold">Training</h3>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="training_days" class="field-label">Trainingstage pro Woche *</label>
                        <select id="training_days" name="training_days" class="field" x-model.number="trainingDays">
                            @foreach (range(0, 7) as $count)
                                <option value="{{ $count }}" @selected((int) old('training_days', $profile->training_days) === $count)>
                                    {{ $count === 0 ? 'Kein Training' : $count }}
                                </option>
                            @endforeach
                        </select>
                        @error('training_days') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div x-show="trainingDays > 0">
                        <label for="training_minutes" class="field-label">Dauer einer Einheit in Minuten</label>
                        <input id="training_minutes" type="number" name="training_minutes" min="10" max="300" inputmode="numeric" class="field"
                               value="{{ old('training_minutes', $profile->training_minutes) }}">
                        @error('training_minutes') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div x-show="trainingDays > 0">
                    <label for="training_type" class="field-label">Trainingsart</label>
                    <select id="training_type" name="training_type" class="field">
                        @foreach ($trainingTypes as $type)
                            <option value="{{ $type->value }}" @selected(old('training_type', $profile->training_type?->value) === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    @error('training_type') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="space-y-2 border-t border-line pt-8">
                <div class="grid gap-5 sm:grid-cols-2"><div>
                <label for="kcal_override" class="field-label">Eigenes Tagesbudget in kcal (optional)</label>
                <input id="kcal_override" type="number" name="kcal_override" min="1000" max="6000" step="10" inputmode="numeric" class="field"
                       value="{{ old('kcal_override', $profile->kcal_override) }}" placeholder="leer = berechnen">
                </div></div>
                <p class="text-xs text-ink-soft">
                    Z. B. eine Vorgabe aus der Ernährungsberatung. Gilt für Ruhetage; an Trainingstagen kommt der Trainingsverbrauch dazu.
                    Eiweiß, Fett und Kohlenhydrate werden daraus berechnet.
                </p>
                @error('kcal_override') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="btn btn-primary">{{ $profile->exists ? 'Profil speichern' : 'Budget berechnen' }}</button>
        </form>
    </div>
</x-layouts.app>
