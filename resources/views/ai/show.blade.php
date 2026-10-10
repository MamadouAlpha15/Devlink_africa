{{--
    Résultats DevLink AI — version fonctionnelle minimale (backend).
    Le front-end la restylera : voir docs/front-end/devlink-ai.md.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">DevLink AI</h2>
        <p class="text-sm text-gray-600 italic">« {{ $search->query }} »</p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 space-y-6">

            {{-- Ce que l'IA a compris --}}
            <div class="p-4 bg-white shadow rounded text-sm space-y-1">
                <h3 class="font-semibold">L'IA a compris</h3>
                @if ($criteria['summary'] ?? null) <p>{{ $criteria['summary'] }}</p> @endif
                <p>
                    Compétences : {{ collect($criteria['skills'] ?? [])->join(', ') ?: '—' }}
                    @if (! empty($criteria['skill_categories'])) ({{ collect($criteria['skill_categories'])->map(fn ($c) => \App\Enums\SkillCategory::from($c)->label())->join(', ') }}) @endif
                    · Pays : {{ $criteria['country'] ?? '—' }}
                    · Type de projet : {{ isset($criteria['project_type']) ? \App\Enums\ProjectCategory::from($criteria['project_type'])->label() : '—' }}
                </p>
                <p class="text-gray-500">Moteur : {{ ($criteria['engine'] ?? 'local') === 'claude' ? 'Claude (IA)' : 'analyseur local' }}</p>
            </div>

            {{-- Profils recommandés --}}
            <div class="space-y-3">
                @forelse ($results as $result)
                    @php($developer = $result['user'])
                    <div class="p-4 bg-white shadow rounded flex items-center gap-4">
                        <div class="text-2xl font-bold w-20 text-center">{{ $result['score'] }} %</div>
                        @if ($developer->avatar_url)
                            <img src="{{ $developer->avatar_url }}" alt="" class="h-12 w-12 rounded-full object-cover">
                        @else
                            <div class="h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center font-bold">{{ $developer->initials }}</div>
                        @endif
                        <div class="flex-1">
                            <p class="font-semibold">{{ $developer->name }}</p>
                            <p class="text-sm text-gray-600">
                                {{ collect($result['matched_skills'])->join(' · ') ?: $developer->skills->take(3)->pluck('name')->join(' · ') }}
                                · {{ $developer->country?->flag }} {{ $developer->country?->name }}
                            </p>
                        </div>
                        <a href="{{ route('devs.show', $developer) }}" class="underline text-sm">Voir le profil</a>
                    </div>
                @empty
                    <p class="text-gray-600">Aucun profil ne correspond. Essayez une demande plus large (sans pays, ou une autre compétence).</p>
                @endforelse
            </div>

            <a href="{{ route('ai.create') }}" class="underline text-sm">Nouvelle recherche</a>
        </div>
    </div>
</x-app-layout>
