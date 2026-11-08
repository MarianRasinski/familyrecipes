<x-layouts.app title="Trainingsprotokoll">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('training.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-ink-soft hover:text-ink">← Training</a>

        <h1 class="mb-2 font-display text-4xl font-semibold tracking-tight">Protokoll</h1>
        <p class="mb-8 text-ink-soft">Deine Trainings – starte eines über eine Einheit unter „Training“.</p>

        @if ($sessions->isEmpty())
            <div class="card-section flex flex-col items-center gap-4 py-12 text-center">
                <span class="text-5xl" aria-hidden="true">🏋️</span>
                <p class="text-ink-soft">Noch kein Training eingetragen.</p>
                <a href="{{ route('training.index') }}" class="btn btn-primary">Zu meinen Einheiten</a>
            </div>
        @else
            <ul class="space-y-3">
                @foreach ($sessions as $session)
                    <li>
                        <a href="{{ route('workout-sessions.show', $session) }}" class="flex items-center gap-4 rounded-3xl border border-line bg-white p-5 transition hover:border-ink-soft">
                            <span class="w-16 shrink-0 text-center">
                                <span class="block font-display text-2xl leading-tight font-semibold">{{ $session->date->format('d.') }}</span>
                                <span class="block text-xs text-ink-soft">{{ $session->date->format('m.Y') }}</span>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold">{{ $session->name }}</span>
                                <span class="block text-xs text-ink-soft">
                                    {{ $session->doneCount() }}/{{ $session->sets->count() }} Sätze
                                    @if ($session->volumeKg() > 0)
                                        · {{ number_format($session->volumeKg(), 0, ',', '.') }} kg bewegt
                                    @endif
                                </span>
                            </span>
                            @if ($session->isFinished())
                                <span class="chip">✓ fertig</span>
                            @else
                                <span class="chip chip-active">läuft</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-8">{{ $sessions->links() }}</div>
        @endif
    </div>
</x-layouts.app>
