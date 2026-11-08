<x-layouts.app title="Nährwerte prüfen">
    <section class="mb-10 max-w-2xl">
        <p class="mb-2 text-sm font-semibold tracking-widest text-accent uppercase">Nährwerte</p>
        <h1 class="font-display text-4xl leading-tight font-semibold tracking-tight sm:text-5xl">Nährwerte prüfen</h1>
        <p class="mt-3 text-lg text-ink-soft">
            @if ($incomplete > 0)
                Bei {{ $incomplete }} {{ $incomplete === 1 ? 'Rezept fehlt' : 'Rezepten fehlen' }} noch Zuordnungen. Unvollständige stehen oben.
            @else
                Alle Rezepte sind vollständig zugeordnet. 🎉
            @endif
        </p>
    </section>

    <div class="flex flex-col gap-3">
        @foreach ($rows as $row)
            @php($recipe = $row['recipe'])
            @php($nutrition = $row['nutrition'])
            <article class="card-section flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold"><a href="{{ route('recipes.show', $recipe) }}" class="hover:text-accent">{{ $recipe->title }}</a></h2>
                    <p class="text-xs text-ink-soft">
                        {{ $nutrition->countedCount() }} von {{ $nutrition->relevantCount() }} Zutaten berechnet
                        @if ($nutrition->hasData())
                            · {{ $nutrition->isEstimated() ? '≈ ' : '' }}{{ ($recipe->servings ? $nutrition->perServing() : $nutrition->total)->rounded()['kcal'] }} kcal {{ $recipe->servings ? 'pro Portion' : 'gesamt' }}
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if (! $nutrition->isComplete())
                        <span class="chip text-accent-dark">{{ $nutrition->missingCount() }} fehlen</span>
                    @elseif ($nutrition->isEstimated())
                        <span class="chip">geschätzt</span>
                    @else
                        <span class="chip chip-active">vollständig</span>
                    @endif
                    <a href="{{ route('recipes.nutrition.edit', $recipe) }}" class="btn btn-ghost">Zuordnen</a>
                </div>
            </article>
        @endforeach
    </div>
</x-layouts.app>
