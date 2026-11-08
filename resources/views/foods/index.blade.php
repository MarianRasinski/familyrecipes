<x-layouts.app title="Lebensmittel">
    <section class="mb-10 max-w-2xl">
        <p class="mb-2 text-sm font-semibold tracking-widest text-accent uppercase">Nährwerte</p>
        <h1 class="font-display text-4xl leading-tight font-semibold tracking-tight sm:text-5xl">Lebensmittel</h1>
        <p class="mt-3 text-lg text-ink-soft">Nährwerte je 100 g aus dem Bundeslebensmittelschlüssel – Grundlage für die Berechnung deiner Rezepte.</p>
    </section>

    <form method="GET" action="{{ route('foods.index') }}" class="mb-10 grid gap-3 sm:grid-cols-2" role="search">
        <div>
            <label for="q" class="sr-only">Lebensmittel suchen</label>
            <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="z. B. zwiebel roh, haferflocken …" class="field" autofocus>
        </div>
        <div>
            <label for="gruppe" class="sr-only">Lebensmittelgruppe</label>
            <select id="gruppe" name="gruppe" class="field" onchange="this.form.submit()">
                <option value="">Alle Gruppen</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->value }}" @selected($activeCategory === $category)>{{ $category->label() }}</option>
                @endforeach
            </select>
        </div>
        <noscript><button type="submit" class="btn btn-primary">Suchen</button></noscript>
    </form>

    @if ($foods->isEmpty())
        <div class="card-section flex flex-col items-center gap-4 py-16 text-center">
            <span class="text-5xl" aria-hidden="true">🥕</span>
            <h2 class="font-display text-2xl font-semibold">Nichts gefunden</h2>
            <p class="max-w-sm text-ink-soft">
                @if (request()->hasAny(['q', 'gruppe']))
                    Versuch es mit einem kürzeren Suchwort, z. B. „zwiebel“ statt „zwiebeln“.
                @else
                    Noch keine Lebensmittel importiert. Importieren mit <code>php artisan foods:import</code>.
                @endif
            </p>
        </div>
    @else
        <p class="mb-4 text-sm text-ink-soft">{{ number_format($foods->total(), 0, ',', '.') }} Treffer · Werte je 100 g</p>

        <div class="flex flex-col gap-3">
            @foreach ($foods as $food)
                @php($values = $food->nutrientsFor(100)->rounded())
                <article class="card-section flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">{{ $food->name }}</h2>
                        <p class="text-xs text-ink-soft">
                            {{ $food->category?->label() }}
                            @if ($food->hasMissingValues())
                                · <span class="text-accent-dark">unvollständige Daten</span>
                            @endif
                        </p>
                    </div>
                    <dl class="flex flex-wrap gap-2 text-sm">
                        <div class="chip gap-1"><dt class="sr-only">Energie</dt><dd><strong>{{ $values['kcal'] }}</strong> kcal</dd></div>
                        <div class="chip gap-1"><dt class="text-ink-soft">Eiweiß</dt><dd>{{ number_format($values['protein'], 1, ',', '') }} g</dd></div>
                        <div class="chip gap-1"><dt class="text-ink-soft">KH</dt><dd>{{ number_format($values['carbs'], 1, ',', '') }} g</dd></div>
                        <div class="chip gap-1"><dt class="text-ink-soft">Fett</dt><dd>{{ number_format($values['fat'], 1, ',', '') }} g</dd></div>
                        <div class="chip gap-1"><dt class="text-ink-soft">Ballaststoffe</dt><dd>{{ $food->fiber === null ? '–' : number_format($values['fiber'], 1, ',', '').' g' }}</dd></div>
                    </dl>
                </article>
            @endforeach
        </div>

        <div class="mt-10">
            {{ $foods->links() }}
        </div>
    @endif

    <p class="mt-10 text-xs text-ink-soft">
        Quelle: Max Rubner-Institut (2025): Bundeslebensmittelschlüssel (BLS), Version 4.0. Karlsruhe.
        Fehlende Werte sind nicht 0, sondern „keine Daten“ – Berechnungen damit werden als Schätzung gekennzeichnet.
    </p>
</x-layouts.app>
