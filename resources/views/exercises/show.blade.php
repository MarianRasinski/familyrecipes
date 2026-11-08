<x-layouts.app :title="$exercise->name">
    <article class="max-w-3xl">
        <a href="{{ route('exercises.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-ink-soft hover:text-ink">
            ← Alle Übungen
        </a>

        <header class="mb-8 flex flex-col gap-4">
            <div class="flex flex-wrap gap-2">
                @if ($exercise->category)
                    <span class="chip">{{ $exercise->category }}</span>
                @endif
                @if ($exercise->level)
                    <a href="{{ route('exercises.index', ['stufe' => $exercise->level->value]) }}" class="chip">{{ $exercise->level->label() }}</a>
                @endif
            </div>

            <h1 class="font-display text-4xl leading-tight font-semibold tracking-tight sm:text-5xl">{{ $exercise->name }}</h1>

            @if ($exercise->description)
                <p class="text-lg leading-relaxed text-ink/80">{{ $exercise->description }}</p>
            @endif
        </header>

        <div class="grid gap-6 sm:grid-cols-2">
            <section class="card-section">
                <h2 class="mb-3 font-display text-xl font-semibold">Vorgabe</h2>
                @if ($exercise->prescription())
                    <p class="text-2xl font-semibold text-accent">{{ $exercise->prescription() }}</p>
                    @if ($exercise->measure)
                        <p class="mt-1 text-sm text-ink-soft">
                            {{ $exercise->measure->label() }}{{ $exercise->per_side ? ', pro Seite' : '' }}
                        </p>
                    @endif
                @else
                    <p class="text-ink-soft">Keine Vorgabe hinterlegt.</p>
                @endif
            </section>

            <section class="card-section">
                <h2 class="mb-3 font-display text-xl font-semibold">Geräte</h2>
                @forelse ($exercise->equipment as $item)
                    <p>
                        <a href="{{ route('exercises.index', ['geraet' => $item->slug]) }}" class="hover:text-accent">{{ $item->name }}</a>
                        @if ($item->pivot->is_optional)
                            <span class="text-sm text-ink-soft">(optional)</span>
                        @endif
                        @if ($item->variants)
                            <span class="text-sm text-ink-soft">· vorhanden: {{ implode(', ', $item->variants) }}</span>
                        @elseif (! $item->is_available)
                            <span class="text-sm text-accent-dark">· nicht vorhanden</span>
                        @endif
                    </p>
                @empty
                    <p class="text-ink-soft">Ohne Gerät</p>
                @endforelse
            </section>
        </div>

        <section class="card-section mt-6">
            <h2 class="mb-3 font-display text-xl font-semibold">Muskelgruppen</h2>
            @foreach ([true => 'Hauptsächlich', false => 'Unterstützend'] as $primary => $heading)
                @php($groups = $exercise->muscleGroups->filter(fn ($group) => $group->pivot->is_primary == $primary))
                @if ($groups->isNotEmpty())
                    <p class="mb-2 text-sm font-semibold text-ink-soft">{{ $heading }}</p>
                    <div class="mb-4 flex flex-wrap gap-2">
                        @foreach ($groups as $group)
                            <a href="{{ route('exercises.index', ['gruppe' => $group->slug]) }}" @class(['chip', 'chip-active' => $primary])>{{ $group->name }}</a>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </section>
    </article>
</x-layouts.app>
