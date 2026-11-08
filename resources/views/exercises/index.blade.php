<x-layouts.app title="Übungen">
    <section class="mb-10 max-w-2xl">
        <p class="mb-2 text-sm font-semibold tracking-widest text-accent uppercase">Training</p>
        <h1 class="font-display text-4xl leading-tight font-semibold tracking-tight sm:text-5xl">Übungen</h1>
        <p class="mt-3 text-lg text-ink-soft">{{ $exercises->count() }} {{ $exercises->count() === 1 ? 'Übung' : 'Übungen' }} – nach Muskelgruppe, Gerät oder Schwierigkeit filtern.</p>
    </section>

    {{-- Filter: Auswahlfelder schicken das Formular sofort ab --}}
    <form method="GET" action="{{ route('exercises.index') }}" class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4" role="search">
        @if ($activeLevel)
            <input type="hidden" name="stufe" value="{{ $activeLevel->value }}">
        @endif

        <div>
            <label for="q" class="sr-only">Übungen durchsuchen</label>
            <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Übung suchen …" class="field">
        </div>

        <div>
            <label for="gruppe" class="sr-only">Muskelgruppe</label>
            <select id="gruppe" name="gruppe" class="field" onchange="this.form.submit()">
                <option value="">Alle Muskelgruppen</option>
                @foreach ($muscleGroups as $group)
                    <option value="{{ $group->slug }}" @selected(request('gruppe') === $group->slug)>{{ $group->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="geraet" class="sr-only">Gerät</label>
            <select id="geraet" name="geraet" class="field" onchange="this.form.submit()">
                <option value="">Alle Geräte</option>
                @foreach ($equipment as $item)
                    <option value="{{ $item->slug }}" @selected(request('geraet') === $item->slug)>{{ $item->name }}</option>
                @endforeach
            </select>
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-soft">
            <input type="checkbox" name="zuhause" value="1" @checked(request()->boolean('zuhause')) onchange="this.form.submit()">
            Nur mit vorhandenen Geräten
        </label>

        <noscript><button type="submit" class="btn btn-primary">Filtern</button></noscript>
    </form>

    <nav class="mb-10 flex flex-wrap gap-2" aria-label="Nach Schwierigkeit filtern">
        <a href="{{ route('exercises.index', request()->except('stufe')) }}" @class(['chip', 'chip-active' => ! $activeLevel])>Alle Stufen</a>
        @foreach ($levels as $level)
            <a href="{{ route('exercises.index', [...request()->except('stufe'), 'stufe' => $level->value]) }}"
               @class(['chip', 'chip-active' => $activeLevel === $level])>{{ $level->label() }}</a>
        @endforeach
    </nav>

    @if ($exercises->isEmpty())
        <div class="card-section flex flex-col items-center gap-4 py-16 text-center">
            <span class="text-5xl" aria-hidden="true">🏋️</span>
            <h2 class="font-display text-2xl font-semibold">Keine Übungen gefunden</h2>
            <p class="max-w-sm text-ink-soft">
                @if (request()->hasAny(['q', 'gruppe', 'geraet', 'stufe', 'zuhause']))
                    Setze die Filter zurück oder wähle eine andere Kombination.
                @else
                    Der Übungskatalog ist noch leer. Importieren mit <code>php artisan exercises:import</code>.
                @endif
            </p>
            @if (request()->hasAny(['q', 'gruppe', 'geraet', 'stufe', 'zuhause']))
                <a href="{{ route('exercises.index') }}" class="btn btn-ghost">Filter zurücksetzen</a>
            @endif
        </div>
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($exercises as $exercise)
                <a href="{{ route('exercises.show', $exercise) }}" class="card-section flex flex-col gap-3 hover:border-ink-soft">
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="font-display text-xl font-semibold">{{ $exercise->name }}</h2>
                        @if ($exercise->level)
                            <span class="chip">{{ $exercise->level->label() }}</span>
                        @endif
                    </div>
                    @if ($exercise->prescription())
                        <p class="text-sm font-semibold text-accent">{{ $exercise->prescription() }}</p>
                    @endif
                    <p class="text-sm text-ink-soft">
                        {{ $exercise->muscleGroups->filter(fn ($group) => $group->pivot->is_primary)->pluck('name')->join(', ') }}
                    </p>
                    <p class="mt-auto text-xs text-ink-soft">
                        {{ $exercise->equipment->map(fn ($item) => $item->name.($item->pivot->is_optional ? ' (optional)' : ''))->join(' · ') ?: 'Ohne Gerät' }}
                    </p>
                </a>
            @endforeach
        </div>
    @endif
</x-layouts.app>
