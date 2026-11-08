{{-- Nährwerte aus den Zutaten (App\Support\Nutrition\RecipeNutrition) --}}
@if ($nutrition->hasData())
    @php($values = ($recipe->servings ? $nutrition->perServing() : $nutrition->total)->rounded())
    @php($approx = $nutrition->isEstimated() ? '≈ ' : '')
    <section class="card-section mb-10" aria-labelledby="nutrition-heading">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 id="nutrition-heading" class="font-display text-2xl font-semibold">
                Nährwerte {{ $recipe->servings ? 'pro Portion' : 'gesamt' }}
            </h2>
            @if ($nutrition->isEstimated())
                <span class="chip">geschätzt</span>
            @endif
        </div>

        <dl class="mb-4 flex flex-wrap gap-2">
            @foreach ([
                'Energie' => $approx.$values['kcal'].' kcal',
                'Eiweiß' => $approx.number_format($values['protein'], 1, ',', '').' g',
                'Kohlenhydrate' => $approx.number_format($values['carbs'], 1, ',', '').' g',
                'Fett' => $approx.number_format($values['fat'], 1, ',', '').' g',
                'Ballaststoffe' => $approx.number_format($values['fiber'], 1, ',', '').' g',
            ] as $label => $value)
                <div class="chip gap-1">
                    <dt class="text-ink-soft">{{ $label }}</dt>
                    <dd class="font-semibold">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        <p class="text-sm text-ink-soft">
            Berechnet aus {{ $nutrition->countedCount() }} von {{ $nutrition->relevantCount() }} Zutaten
            (Bundeslebensmittelschlüssel 4.0).
            @if ($nutrition->missingCount() > 0)
                <span class="text-accent-dark">{{ $nutrition->missingCount() }} {{ $nutrition->missingCount() === 1 ? 'Zutat fehlt' : 'Zutaten fehlen' }} noch.</span>
            @elseif ($nutrition->isEstimated())
                Einige Mengen sind geschätzt (z. B. EL oder Stück).
            @endif
            @can('update', $recipe)
                <a href="{{ route('recipes.nutrition.edit', $recipe) }}" class="font-semibold text-accent hover:text-ink">Zuordnung prüfen →</a>
            @endcan
        </p>
    </section>
@else
    @can('update', $recipe)
        <section class="card-section mb-10 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-display text-xl font-semibold">Noch keine Nährwerte berechnet</h2>
                <p class="text-sm text-ink-soft">Ordne die Zutaten Lebensmitteln zu – Vorschläge sind schon vorausgefüllt.</p>
            </div>
            <a href="{{ route('recipes.nutrition.edit', $recipe) }}" class="btn btn-primary">Nährwerte berechnen</a>
        </section>
    @endcan
@endif
