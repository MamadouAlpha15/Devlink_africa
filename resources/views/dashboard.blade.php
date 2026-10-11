{{--
    Dashboard — version fonctionnelle minimale (backend).
    Première page vue par le jury après la connexion : le front-end la restylera en priorité.
    Voir docs/front-end/dashboard.md.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Bonjour {{ $user->first_name ?: $user->name }} 👋</h2>
        <p class="text-sm text-gray-600">Bienvenue sur DevLink Africa.</p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 space-y-6">

            @if ($completion < 100)
                <div class="p-4 bg-yellow-50 text-yellow-800 rounded flex justify-between items-center">
                    <span>Votre profil est complété à {{ $completion }} %.</span>
                    <a href="{{ route('developer-profile.edit') }}" class="underline">Compléter mon profil</a>
                </div>
            @endif

            @if ($pending['invitations'] || $pending['applications'])
                <div class="p-4 bg-blue-50 text-blue-800 rounded flex justify-between items-center">
                    <span>
                        @if ($pending['invitations']) {{ $pending['invitations'] }} invitation(s) à rejoindre un projet. @endif
                        @if ($pending['applications']) {{ $pending['applications'] }} demande(s) sur vos projets. @endif
                    </span>
                    <a href="{{ route('collaborations.index') }}" class="underline">Voir</a>
                </div>
            @endif

            {{-- DevScore et compteurs --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <a href="{{ route('devscore.show') }}" class="p-4 bg-white shadow rounded">
                    <p class="text-2xl font-bold">{{ $user->devscore }}</p>
                    <p class="text-sm text-gray-600">DevScore @if ($africaRank) · {{ $africaRank }}e en Afrique @endif</p>
                </a>
                <div class="p-4 bg-white shadow rounded"><p class="text-2xl font-bold">{{ $stats['projects'] }}</p><p class="text-sm text-gray-600">Projets</p></div>
                <div class="p-4 bg-white shadow rounded"><p class="text-2xl font-bold">{{ $stats['resources'] }}</p><p class="text-sm text-gray-600">Ressources</p></div>
                <div class="p-4 bg-white shadow rounded"><p class="text-2xl font-bold">{{ $stats['collaborations'] }}</p><p class="text-sm text-gray-600">Collaborations</p></div>
                <div class="p-4 bg-white shadow rounded"><p class="text-2xl font-bold">{{ $stats['followers'] }}</p><p class="text-sm text-gray-600">Abonnés</p></div>
            </div>

            @if ($user->badges->isNotEmpty())
                <p class="text-sm">Mes badges : {{ $user->badges->map(fn ($badge) => $badge->icon.' '.$badge->name)->join(' · ') }}</p>
            @endif

            <div class="grid lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">

                    {{-- Recommandations de développeurs --}}
                    <div class="p-6 bg-white shadow rounded space-y-2">
                        <div class="flex justify-between">
                            <h3 class="font-semibold">Développeurs recommandés</h3>
                            <a href="{{ route('ai.create') }}" class="text-sm underline">Demander à DevLink AI</a>
                        </div>
                        @forelse ($recommendedDevelopers as $recommendation)
                            <a href="{{ route('devs.show', $recommendation['user']) }}" class="flex items-center gap-3 hover:bg-gray-50 p-1 rounded">
                                @if ($recommendation['score']) <span class="font-bold w-12">{{ $recommendation['score'] }} %</span> @endif
                                <span class="flex-1">
                                    {{ $recommendation['user']->name }}
                                    <span class="text-sm text-gray-600">{{ $recommendation['user']->country?->flag }} {{ $recommendation['user']->skills->take(3)->pluck('name')->join(' · ') }}</span>
                                </span>
                                <span class="text-xs text-gray-500">{{ $recommendation['reason'] }}</span>
                            </a>
                        @empty
                            <p class="text-sm text-gray-600">Pas encore de recommandation. <a href="{{ route('devs.index') }}" class="underline">Trouver des développeurs</a></p>
                        @endforelse
                    </div>

                    {{-- Projets intéressants --}}
                    <div class="p-6 bg-white shadow rounded space-y-2">
                        <div class="flex justify-between">
                            <h3 class="font-semibold">Projets intéressants</h3>
                            <a href="{{ route('projects.create') }}" class="text-sm underline">+ Créer un projet</a>
                        </div>
                        @forelse ($interestingProjects as $project)
                            <a href="{{ route('projects.show', $project) }}" class="block hover:bg-gray-50 p-1 rounded">
                                <span class="font-medium">{{ $project->title }}</span>
                                <span class="text-sm text-gray-600">· {{ $project->category->label() }} @if ($project->country) · {{ $project->country->flag }} @endif · par {{ $project->owner->name }}</span>
                                @php($openRoles = $project->roles->reject->isFull())
                                @if ($openRoles->isNotEmpty())
                                    <span class="block text-sm text-green-700">Recherche : {{ $openRoles->pluck('title')->join(', ') }}</span>
                                @endif
                            </a>
                        @empty
                            <p class="text-sm text-gray-600">Aucun projet ouvert pour le moment.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Fil d'activité --}}
                <div class="p-6 bg-white shadow rounded space-y-3 h-fit">
                    <h3 class="font-semibold">Fil d'activité</h3>
                    <div class="flex gap-3 text-sm">
                        @foreach ($feedScopes as $value => $label)
                            <a href="{{ route('dashboard', ['feed' => $value]) }}" class="{{ $feedScope === $value ? 'font-bold underline' : 'text-gray-600' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                    @forelse ($feed as $activity)
                        <div class="text-sm">
                            <a href="{{ route('devs.show', $activity->user) }}" class="font-medium">{{ $activity->user->name }}</a>
                            {{ $activity->type->label() }}
                            @if ($link = $activityFeed->linkFor($activity))
                                <a href="{{ $link }}" class="underline">{{ $activityFeed->subjectTitle($activity) }}</a>
                            @else
                                <strong>{{ $activityFeed->subjectTitle($activity) }}</strong>
                            @endif
                            <span class="block text-xs text-gray-500">{{ $activity->created_at->diffForHumans() }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-600">
                            Rien pour l'instant.
                            @if ($feedScope === 'network') Suivez des développeurs pour voir leur activité, ou <a href="{{ route('dashboard', ['feed' => 'all']) }}" class="underline">voyez toute la communauté</a>. @endif
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
