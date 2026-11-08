<x-layouts.app :title="'Mein Plan ' . $plan->week->label()">
    @php
        $week = $plan->week;
        $fmt = fn (float $value) => number_format($value, 0, ',', '.');
    @endphp

    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="mb-2 text-sm font-semibold tracking-widest text-accent uppercase">{{ $week->relativeName() ?? 'Mein Plan' }}</p>
            <h1 class="font-display text-4xl font-semibold tracking-tight sm:text-5xl">Mein Plan</h1>
            <p class="mt-2 text-ink-soft">{{ $week->label() }}</p>
        </div>

        <nav class="flex items-center gap-2" aria-label="Woche wechseln">
            <a href="{{ route('personal-plan.show', $week->previous()) }}" class="btn btn-ghost px-3!" title="Vorherige Woche" aria-label="Vorherige Woche">←</a>
            @unless ($week->isCurrent())
                <a href="{{ route('personal-plan.show') }}" class="btn btn-ghost">Diese Woche</a>
            @endunless
            <a href="{{ route('personal-plan.show', $week->next()) }}" class="btn btn-ghost px-3!" title="Nächste Woche" aria-label="Nächste Woche">→</a>
        </nav>
    </div>

    {{-- Wochenüberblick --}}
    @if ($plan->budget)
        @php $weekTarget = $plan->plannedDaysTargetKcal(); @endphp
        <section class="mb-8 rounded-3xl bg-paper-deep px-5 py-4 text-sm text-ink-soft">
            @if ($plan->plannedDays()->isEmpty())
                Noch nichts geplant. Dein Budget: <strong class="text-ink">{{ $fmt($plan->budget->restDayKcal()) }} kcal</strong> am Ruhetag
                @if ($plan->budget->trainingKcal() > 0)
                    · <strong class="text-ink">{{ $fmt($plan->budget->trainingDayKcal()) }} kcal</strong> am Trainingstag
                @endif
            @else
                Geplant: <strong class="text-ink">{{ $fmt($plan->total()->kcal) }} kcal</strong>
                an {{ $plan->plannedDays()->count() }} {{ $plan->plannedDays()->count() === 1 ? 'Tag' : 'Tagen' }}
                – Budget für diese Tage: <strong class="text-ink">{{ $fmt($weekTarget) }} kcal</strong>.
            @endif
            @if ($plan->budget->profile->training_days > 0)
                <br>Trainingstage markiert: {{ $plan->trainingDayCount() }} von {{ $plan->budget->profile->training_days }} geplanten.
            @endif
            <a href="{{ route('body-profile.edit') }}" class="font-semibold text-accent hover:text-ink">Profil &amp; Ziele →</a>
        </section>
    @elseif (auth()->user()->can('manage-body-profile'))
        <section class="mb-8 flex flex-wrap items-center justify-between gap-4 rounded-3xl bg-paper-deep px-5 py-4 text-sm">
            <p class="text-ink-soft">Mit einem Profil siehst du hier dein Tagesbudget und wie viel noch übrig ist.</p>
            <a href="{{ route('body-profile.edit') }}" class="btn btn-ghost">Profil anlegen</a>
        </section>
    @endif

    <ol class="space-y-4">
        @foreach ($plan->days as $dayNumber => $day)
            @php
                $total = $day->total();
                $isToday = $day->date->isToday();
                $dayKey = $day->date->toDateString();
            @endphp
            <li id="tag-{{ $dayKey }}" @class([
                    'scroll-mt-24 rounded-3xl border bg-white p-4 sm:p-5',
                    'border-accent ring-2 ring-accent/15' => $isToday,
                    'border-line' => ! $isToday,
                ])>
                {{-- Kopf: Tag und Trainingstag --}}
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-display text-xl font-semibold">
                        {{ App\Support\Week::dayName($dayNumber) }}
                        <span class="text-sm font-normal text-ink-soft">{{ $day->date->format('d.m.') }}</span>
                        @if ($isToday)
                            <span class="ml-1 rounded-full bg-accent px-1.5 py-0.5 text-xs font-semibold text-white">Heute</span>
                        @endif
                    </h2>

                    @if ($plan->budget)
                        <form method="POST" action="{{ route('personal-plan.training', $dayKey) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="training" value="{{ $day->isTrainingDay ? 0 : 1 }}">
                            <button type="submit" @class(['chip', 'chip-active' => $day->isTrainingDay]) aria-pressed="{{ $day->isTrainingDay ? 'true' : 'false' }}">
                                🏋️ {{ $day->isTrainingDay ? 'Trainingstag' : 'Ruhetag' }}
                            </button>
                        </form>
                    @endif
                </div>

                {{-- Summe und Budget --}}
                @if ($day->hasTarget())
                    @php $remaining = $day->remainingKcal(); @endphp
                    <div class="mb-5">
                        <div class="mb-2 flex flex-wrap items-baseline justify-between gap-x-4 text-sm">
                            <p>
                                <strong class="font-display text-2xl font-semibold">{{ $fmt($total->kcal) }}</strong>
                                <span class="text-ink-soft">von {{ $fmt($day->target->kcal) }} kcal</span>
                            </p>
                            <p @class(['font-semibold', 'text-accent-dark' => $day->isOverBudget(), 'text-herb' => ! $day->isOverBudget() && $remaining >= 0, 'text-ink-soft' => ! $day->isOverBudget() && $remaining < 0])>
                                {{ $remaining >= 0 ? 'noch '.$fmt($remaining).' kcal' : $fmt(-$remaining).' kcal über Budget' }}
                            </p>
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-paper-deep" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $day->progressPercent() }}" aria-label="Anteil am Tagesbudget">
                            <div @class(['h-full rounded-full', 'bg-accent' => $day->isOverBudget(), 'bg-herb' => ! $day->isOverBudget()]) style="width: {{ $day->progressPercent() }}%"></div>
                        </div>
                        <p class="mt-2 text-xs text-ink-soft">
                            Eiweiß {{ $fmt($total->protein) }}/{{ $fmt($day->target->protein) }} g
                            · Kohlenhydrate {{ $fmt($total->carbs) }}/{{ $fmt($day->target->carbs) }} g
                            · Fett {{ $fmt($total->fat) }}/{{ $fmt($day->target->fat) }} g
                        </p>
                    </div>
                @elseif (! $day->isEmpty())
                    <p class="mb-5 text-sm text-ink-soft">
                        <strong class="text-ink">{{ $fmt($total->kcal) }} kcal</strong>
                        · Eiweiß {{ $fmt($total->protein) }} g · Kohlenhydrate {{ $fmt($total->carbs) }} g · Fett {{ $fmt($total->fat) }} g
                    </p>
                @endif

                @if ($day->unknownCount() > 0)
                    <p class="mb-4 text-xs text-accent-dark">
                        {{ $day->unknownCount() }} {{ $day->unknownCount() === 1 ? 'Eintrag hat' : 'Einträge haben' }} noch keine Nährwerte und {{ $day->unknownCount() === 1 ? 'fehlt' : 'fehlen' }} in der Summe.
                    </p>
                @endif

                {{-- Mahlzeiten --}}
                <div class="divide-y divide-line">
                    @foreach ($slots as $slot)
                        @php $entries = $day->entriesFor($slot); @endphp
                        <section class="py-3">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold">
                                    <span aria-hidden="true">{{ $slot->emoji() }}</span> {{ $slot->label() }}
                                    @if ($entries->isNotEmpty())
                                        <span class="font-normal text-ink-soft">· {{ $fmt($day->slotTotal($slot)->kcal) }} kcal</span>
                                    @endif
                                </h3>
                                <a href="{{ route('personal-plan.create', ['day' => $dayKey, 'mahlzeit' => $slot->value]) }}"
                                   class="text-sm font-semibold text-accent hover:text-ink" aria-label="{{ $slot->label() }} am {{ $day->date->format('d.m.') }} hinzufügen">＋ Hinzufügen</a>
                            </div>

                            @if ($entries->isNotEmpty())
                                <ul class="mt-2 space-y-2">
                                    @foreach ($entries as $entry)
                                        @php $nutrients = $entry->nutrients(); @endphp
                                        <li class="flex items-start gap-3 rounded-2xl bg-paper px-3 py-2 text-sm" x-data="{ editing: false }">
                                            <span class="text-lg" aria-hidden="true">{{ $entry->recipe ? ($entry->recipe->emoji ?: '🍽️') : '🥕' }}</span>
                                            <div class="min-w-0 flex-1">
                                                @if ($entry->recipe)
                                                    <a href="{{ route('recipes.show', $entry->recipe) }}" class="font-medium hover:text-accent-dark">{{ $entry->title() }}</a>
                                                @else
                                                    <span class="font-medium">{{ $entry->title() }}</span>
                                                @endif
                                                <p class="text-xs text-ink-soft">
                                                    <button type="button" class="underline hover:text-ink" @click="editing = ! editing">{{ $entry->amountLabel() }}</button>
                                                    ·
                                                    @if ($nutrients)
                                                        {{ $fmt($nutrients->kcal) }} kcal · {{ $fmt($nutrients->protein) }} g Eiweiß
                                                    @elseif ($entry->recipe)
                                                        noch keine Nährwerte
                                                        @can('update', $entry->recipe)
                                                            – <a href="{{ route('recipes.nutrition.edit', $entry->recipe) }}" class="font-semibold text-accent hover:text-ink">berechnen</a>
                                                        @endcan
                                                    @endif
                                                </p>
                                                <form x-cloak x-show="editing" method="POST" action="{{ route('planned-meals.update', $entry) }}" class="mt-2 flex items-center gap-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <label for="amount-{{ $entry->id }}" class="sr-only">{{ $entry->recipe ? 'Portionen' : 'Gramm' }}</label>
                                                    <input id="amount-{{ $entry->id }}" name="amount" inputmode="decimal" class="field w-24 py-1.5!"
                                                           value="{{ str_replace('.', ',', (string) ($entry->recipe ? $entry->servings : $entry->grams)) }}">
                                                    <span class="text-xs text-ink-soft">{{ $entry->recipe ? 'Portionen' : 'g' }}</span>
                                                    <button type="submit" class="btn btn-primary py-1.5! text-xs">Speichern</button>
                                                </form>
                                            </div>
                                            <form method="POST" action="{{ route('planned-meals.destroy', $entry) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="grid size-8 place-items-center rounded-full text-ink-soft hover:bg-paper-deep hover:text-accent-dark"
                                                        aria-label="{{ $entry->title() }} entfernen" title="Entfernen">✕</button>
                                            </form>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </section>
                    @endforeach
                </div>
            </li>
        @endforeach
    </ol>
</x-layouts.app>
