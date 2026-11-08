{{--
    „Mein Tag“ (Dashboard): persönliche Tagesziele, heutige Mahlzeiten und Woche im Überblick.
    $myPlan: App\Support\Planning\WeekPlan der aktuellen Woche, $myDay: DayPlan von heute.
--}}
@php
    $fmt = fn (float $value) => number_format($value, 0, ',', '.');
    $total = $myDay->total();
    $target = $myDay->target;
    $remaining = $myDay->remainingKcal();
    $dayKey = $myDay->date->toDateString();
    $plannedSlots = collect($slots)->filter(fn ($slot) => $myDay->entriesFor($slot)->isNotEmpty());
    $emptySlots = collect($slots)->reject(fn ($slot) => $myDay->entriesFor($slot)->isNotEmpty());
@endphp

<section class="card-section" aria-labelledby="mein-tag">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 id="mein-tag" class="font-display text-2xl font-semibold">Mein Tag</h2>
            @if ($myPlan->budget)
                <p class="text-sm text-ink-soft">{{ $myDay->isTrainingDay ? '🏋️ Trainingstag' : 'Ruhetag' }} · Budget {{ $fmt($target->kcal) }} kcal</p>
            @endif
        </div>
        <a href="{{ route('personal-plan.show') }}#tag-{{ $dayKey }}" class="text-sm font-medium text-ink-soft hover:text-ink">Mein Plan →</a>
    </div>

    {{-- Heutiges Training laut Trainingsplan --}}
    @if ($myDay->workout)
        <div class="mb-5 flex flex-wrap items-center gap-3 rounded-2xl bg-paper px-4 py-3 text-sm">
            <span class="text-2xl" aria-hidden="true">🏋️</span>
            <a href="{{ route('workouts.edit', $myDay->workout) }}" class="min-w-0 flex-1 hover:text-accent-dark">
                <span class="block text-xs font-semibold tracking-widest text-ink-soft uppercase">Heute trainieren</span>
                <span class="block font-semibold">{{ $myDay->workout->name }}</span>
            </a>
            @if ($todaySession?->isFinished())
                <a href="{{ route('workout-sessions.show', $todaySession) }}" class="chip">✓ erledigt</a>
            @else
                <form method="POST" action="{{ route('workout-sessions.store', $myDay->workout) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">{{ $todaySession ? 'Weiter' : '▶ Starten' }}</button>
                </form>
            @endif
        </div>
    @endif

    {{-- Tagesziele --}}
    @if ($target)
        <div class="mb-6 flex flex-wrap items-center justify-center gap-6 sm:justify-start">
            <x-progress-ring :value="$total->kcal" :target="$target->kcal" label="Kalorien" unit="kcal" :size="128" :stroke="10" :over="$myDay->isOverBudget()" />
            <div class="flex flex-wrap justify-center gap-4">
                <x-progress-ring :value="$total->protein" :target="$target->protein" label="Eiweiß" :size="80" :stroke="7" />
                <x-progress-ring :value="$total->carbs" :target="$target->carbs" label="Kohlenhydrate" :size="80" :stroke="7" />
                <x-progress-ring :value="$total->fat" :target="$target->fat" label="Fett" :size="80" :stroke="7" />
            </div>
        </div>
        <p @class(['mb-5 text-sm font-semibold', 'text-accent-dark' => $myDay->isOverBudget(), 'text-herb' => ! $myDay->isOverBudget()])>
            @if ($remaining >= 0)
                Noch {{ $fmt($remaining) }} kcal übrig
            @else
                {{ $fmt(-$remaining) }} kcal über Budget
            @endif
            @if ($myDay->unknownCount() > 0)
                <span class="font-normal text-ink-soft">({{ $myDay->unknownCount() }} {{ $myDay->unknownCount() === 1 ? 'Eintrag' : 'Einträge' }} ohne Nährwerte)</span>
            @endif
        </p>
    @elseif (! $myDay->isEmpty())
        <p class="mb-5 text-sm text-ink-soft">
            Heute geplant: <strong class="text-ink">{{ $fmt($total->kcal) }} kcal</strong>
            · Eiweiß {{ $fmt($total->protein) }} g · Kohlenhydrate {{ $fmt($total->carbs) }} g · Fett {{ $fmt($total->fat) }} g
        </p>
    @endif

    @if (! $myPlan->budget && auth()->user()->can('manage-body-profile'))
        <p class="mb-5 rounded-2xl bg-paper px-4 py-3 text-sm text-ink-soft">
            Lege dein <a href="{{ route('body-profile.edit') }}" class="font-semibold text-accent hover:text-ink">Profil &amp; Ziele</a> an,
            dann siehst du hier dein Tagesbudget mit Fortschritt.
        </p>
    @endif

    {{-- Heutige Mahlzeiten --}}
    @if ($myDay->isEmpty())
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-dashed border-line p-4">
            <p class="text-sm text-ink-soft">Für heute hast du noch nichts geplant.</p>
            <a href="{{ route('personal-plan.create', ['day' => $dayKey, 'mahlzeit' => 'breakfast']) }}" class="btn btn-primary">＋ Mahlzeit planen</a>
        </div>
    @else
        <ul class="divide-y divide-line">
            @foreach ($plannedSlots as $slot)
                <li class="flex items-start gap-3 py-3 text-sm">
                    <span class="text-lg" aria-hidden="true">{{ $slot->emoji() }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold">{{ $slot->label() }}</p>
                        <p class="text-ink-soft">
                            {{ $myDay->entriesFor($slot)->map(fn ($entry) => $entry->title().' ('.$entry->amountLabel().')')->implode(', ') }}
                        </p>
                    </div>
                    <span class="shrink-0 font-semibold whitespace-nowrap">{{ $fmt($myDay->slotTotal($slot)->kcal) }} kcal</span>
                </li>
            @endforeach
        </ul>
        @if ($emptySlots->isNotEmpty())
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($emptySlots as $slot)
                    <a href="{{ route('personal-plan.create', ['day' => $dayKey, 'mahlzeit' => $slot->value]) }}" class="chip">＋ {{ $slot->label() }}</a>
                @endforeach
            </div>
        @endif
    @endif

    {{-- Woche im Überblick --}}
    @if ($myPlan->budget)
        <div class="mt-6 border-t border-line pt-8">
            <h3 class="mb-3 text-sm font-semibold tracking-widest text-ink-soft uppercase">Diese Woche</h3>
            <ol class="flex gap-2">
                @foreach ($myPlan->days as $dayNumber => $day)
                    <li class="flex flex-1 flex-col items-center gap-1 text-xs">
                        <a href="{{ route('personal-plan.show') }}#tag-{{ $day->date->toDateString() }}"
                           class="flex h-16 w-full flex-col justify-end overflow-hidden rounded-md bg-paper-deep"
                           title="{{ App\Support\Week::dayName($dayNumber) }}: {{ $fmt($day->total()->kcal) }} von {{ $fmt($day->target->kcal) }} kcal">
                            <span @class(['block w-full', 'bg-accent' => $day->isOverBudget(), 'bg-herb' => ! $day->isOverBudget()])
                                  style="height: {{ $day->progressPercent() }}%"></span>
                        </a>
                        <span @class(['font-semibold text-ink' => $day->date->isToday(), 'text-ink-soft' => ! $day->date->isToday()])>
                            {{ mb_substr(App\Support\Week::dayName($dayNumber), 0, 2) }}
                        </span>
                        <span class="text-ink-soft" aria-hidden="true">{!! $day->isTrainingDay ? '🏋️' : '&nbsp;' !!}</span>
                    </li>
                @endforeach
            </ol>
            <p class="mt-2 text-xs text-ink-soft">Balken = Anteil am Tagesbudget · 🏋️ = Trainingstag</p>
        </div>
    @endif
</section>
