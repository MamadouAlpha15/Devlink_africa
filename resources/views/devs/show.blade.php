{{--
    Profil public d'un développeur — version fonctionnelle minimale (backend).
    Le front-end la restylera : voir docs/front-end/profils.md pour les variables disponibles.
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            @if ($user->avatar_url)
                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="h-20 w-20 rounded-full object-cover">
            @else
                <div class="h-20 w-20 rounded-full bg-gray-200 flex items-center justify-center text-2xl font-bold">{{ $user->initials }}</div>
            @endif

            <div>
                <h2 class="font-semibold text-xl text-gray-800">{{ $user->name }}</h2>
                <p class="text-gray-600">
                    {{ $user->profile_type?->label() }}
                    @if ($user->experience_level) · {{ $user->experience_level->label() }} @endif
                </p>
                <p class="text-gray-600">
                    @if ($user->country) {{ $user->country->flag }} {{ $user->country->name }} @endif
                    @if ($user->city) · {{ $user->city }} @endif
                </p>
                @if ($user->show_availability && $user->availability)
                    <p class="text-sm">{{ $user->availability->label() }}</p>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-800 rounded">{{ session('status') }}</div>
            @endif

            @if ($isOwner && ! $user->is_public)
                <div class="p-4 bg-yellow-50 text-yellow-800 rounded">Votre profil est privé : vous seul pouvez le voir.</div>
            @endif

            {{-- Actions --}}
            <div class="flex flex-wrap gap-3">
                @if ($isOwner)
                    <a href="{{ route('developer-profile.edit') }}" class="underline">Modifier mon profil</a>
                @elseif ($canFollow)
                    @if ($isFollowing)
                        <form method="POST" action="{{ route('devs.unfollow', $user) }}">
                            @csrf
                            @method('DELETE')
                            <x-secondary-button type="submit">Ne plus suivre</x-secondary-button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('devs.follow', $user) }}">
                            @csrf
                            <x-primary-button>Suivre</x-primary-button>
                        </form>
                    @endif
                @endif
                {{-- À venir : Contacter (messagerie), Proposer une collaboration, Signaler --}}

                @if ($user->github_url) <a href="{{ $user->github_url }}" target="_blank" rel="noopener" class="underline">GitHub</a> @endif
                @if ($user->linkedin_url) <a href="{{ $user->linkedin_url }}" target="_blank" rel="noopener" class="underline">LinkedIn</a> @endif
                @if ($user->portfolio_url) <a href="{{ $user->portfolio_url }}" target="_blank" rel="noopener" class="underline">Portfolio</a> @endif
                @if ($user->website_url) <a href="{{ $user->website_url }}" target="_blank" rel="noopener" class="underline">Site</a> @endif
                @if ($user->show_email) <a href="mailto:{{ $user->email }}" class="underline">{{ $user->email }}</a> @endif
            </div>

            {{-- DevScore et statistiques --}}
            <div class="p-6 bg-white shadow rounded">
                <p class="text-2xl font-bold">{{ $user->devscore }} points DevScore</p>
                <p class="text-gray-600">
                    {{ $user->projects_count }} projets ·
                    {{ $user->resources_count }} ressources ·
                    {{ $user->collaborations_count }} collaborations ·
                    @if ($ratingAvg) note {{ number_format($ratingAvg, 1, ',', ' ') }} · @endif
                    {{ $user->followers_count }} abonnés · {{ $user->following_count }} abonnements
                </p>
            </div>

            @if ($user->bio)
                <div class="p-6 bg-white shadow rounded">
                    <h3 class="font-semibold mb-2">Bio</h3>
                    <p class="whitespace-pre-line">{{ $user->bio }}</p>
                </div>
            @endif

            <div class="p-6 bg-white shadow rounded">
                <h3 class="font-semibold mb-2">Compétences</h3>
                <ul class="flex flex-wrap gap-2">
                    @forelse ($user->skills as $skill)
                        <li class="px-3 py-1 bg-gray-100 rounded-full text-sm">{{ $skill->name }} — {{ $skill->pivot->level->label() }}</li>
                    @empty
                        <li class="text-gray-600 text-sm">Aucune compétence renseignée.</li>
                    @endforelse
                </ul>
            </div>

            <div class="p-6 bg-white shadow rounded">
                <h3 class="font-semibold mb-2">Badges</h3>
                <ul class="flex flex-wrap gap-2">
                    @forelse ($user->badges as $badge)
                        <li class="px-3 py-1 bg-yellow-50 rounded-full text-sm" title="{{ $badge->description }}">{{ $badge->icon }} {{ $badge->name }}</li>
                    @empty
                        <li class="text-gray-600 text-sm">Pas encore de badge.</li>
                    @endforelse
                </ul>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <div class="p-6 bg-white shadow rounded">
                    <h3 class="font-semibold mb-2">Projets</h3>
                    <ul class="space-y-2">
                        @forelse ($projects as $project)
                            <li>
                                <span class="font-medium">{{ $project->title }}</span>
                                <span class="text-sm text-gray-600">· {{ $project->pivot->role->label() }} · {{ $project->status->label() }}</span>
                            </li>
                        @empty
                            <li class="text-gray-600 text-sm">Aucun projet.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="p-6 bg-white shadow rounded">
                    <h3 class="font-semibold mb-2">Ressources</h3>
                    <ul class="space-y-2">
                        @forelse ($resources as $resource)
                            <li>
                                <span class="font-medium">{{ $resource->title }}</span>
                                <span class="text-sm text-gray-600">· {{ $resource->type->label() }}
                                    @if ($resource->rating_avg) · ★ {{ number_format($resource->rating_avg, 1, ',', ' ') }} @endif
                                </span>
                            </li>
                        @empty
                            <li class="text-gray-600 text-sm">Aucune ressource.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
