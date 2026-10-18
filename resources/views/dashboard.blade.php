{{--
    Tableau de bord — première page après la connexion.

    Variables : $user, $completion, $africaRank, $stats, $pending,
    $recommendedDevelopers, $interestingProjects, $feed, $feedScope,
    $feedScopes, $activityFeed. Voir docs/front-end/pages.md.

    Lecture en trois temps : ce qui demande une action (profil à compléter,
    demandes en attente), puis où j'en suis (DevScore et compteurs), puis
    quoi faire maintenant (développeurs et projets, fil d'activité).
--}}
@php
    $firstName = $user->first_name ?: $user->name;
    $pendingTotal = $pending['invitations'] + $pending['applications'];

    // Compteurs nus, côte à côte : une colonne de chiffres se lit d'un coup.
    $counters = [
        ['value' => $stats['projects'], 'label' => 'Projets'],
        ['value' => $stats['resources'], 'label' => 'Ressources'],
        ['value' => $stats['collaborations'], 'label' => 'Collaborations'],
        ['value' => $stats['followers'], 'label' => 'Abonnés'],
    ];
@endphp

<x-app-layout title="Tableau de bord">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-page-header
            :title="'Bonjour '.$firstName"
            subtitle="Voici où en sont vos projets, et qui pourrait vous rejoindre."
        >
            <x-slot name="actions">
                <a href="{{ route('ai.create') }}" class="btn-secondary btn-sm">
                    <x-icon name="sparkles" class="h-4 w-4" />
                    AfriCommit AI
                </a>
                <a href="{{ route('projects.create') }}" class="btn-primary btn-sm">
                    <x-icon name="plus" class="h-4 w-4" />
                    Créer un projet
                </a>
            </x-slot>
        </x-page-header>

        <x-flash class="mt-6" />

        {{--
            Profil incomplet : une carte avec la barre de progression plutôt
            qu'un bandeau d'alerte. Ce n'est pas une erreur, c'est une
            progression — et voir la barre donne envie de la terminer.
        --}}
        @if ($completion < 100)
            <div class="card mt-6 p-4 sm:p-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="card-title">Votre profil est complété à {{ $completion }} %</p>
                        <p class="muted mt-1">
                            Un profil complet remonte dans les recherches et vaut 20 points de DevScore.
                        </p>
                    </div>

                    <a href="{{ route('developer-profile.edit') }}" class="btn-secondary btn-sm shrink-0">
                        Compléter mon profil
                    </a>
                </div>

                <div class="progress-track mt-4" role="img" aria-label="Profil complété à {{ $completion }} %">
                    <div class="progress-bar" style="width: {{ $completion }}%"></div>
                </div>
            </div>
        @endif

        {{-- Demandes en attente : la seule chose qui réclame une réponse. --}}
        @if ($pendingTotal > 0)
            <div class="alert-info mt-4">
                <x-icon name="inbox" class="mt-0.5 h-4 w-4 shrink-0 text-ink-400" />

                <div class="min-w-0 flex-1">
                    <p class="font-medium text-ink-900 dark:text-white">
                        {{ $pendingTotal > 1 ? $pendingTotal.' demandes attendent votre réponse' : 'Une demande attend votre réponse' }}
                    </p>
                    <p class="mt-0.5">
                        @if ($pending['invitations'] > 0)
                            {{ $pending['invitations'] }} {{ $pending['invitations'] > 1 ? 'invitations à rejoindre un projet' : 'invitation à rejoindre un projet' }}@if ($pending['applications'] > 0), @endif
                        @endif
                        @if ($pending['applications'] > 0)
                            {{ $pending['applications'] }} {{ $pending['applications'] > 1 ? 'candidatures sur vos projets' : 'candidature sur vos projets' }}
                        @endif
                    </p>
                </div>

                <a href="{{ route('collaborations.index') }}" class="btn-secondary btn-sm shrink-0">Voir</a>
            </div>
        @endif

        {{--
            Où j'en suis. Le DevScore est détaché des quatre compteurs parce
            qu'il est le seul à être cliquable et le seul à résumer les autres.
        --}}
        <div class="mt-6 grid gap-4 lg:grid-cols-[17rem_minmax(0,1fr)]">
            <a href="{{ route('devscore.show') }}" class="card-interactive flex items-center justify-between gap-4 p-5">
                <div class="min-w-0">
                    <p class="caption">DevScore</p>
                    <p class="tabular mt-1 text-3xl font-semibold tracking-[-0.02em] text-ink-900 dark:text-white">
                        {{ $user->devscore }}
                    </p>
                    <p class="muted mt-1">
                        @if ($africaRank)
                            {{ $africaRank }}<sup>{{ $africaRank === 1 ? 'er' : 'e' }}</sup> en Afrique
                        @else
                            Pas encore classé
                        @endif
                    </p>
                </div>

                <x-icon name="chevron-right" class="h-4 w-4 shrink-0 text-ink-400" />
            </a>

            <div class="card grid grid-cols-2 gap-x-4 gap-y-5 p-5 sm:grid-cols-4">
                @foreach ($counters as $counter)
                    <x-stat :value="$counter['value']" :label="$counter['label']" />
                @endforeach
            </div>
        </div>

        {{-- Badges obtenus. Icônes du jeu de l'application, pas les emojis stockés. --}}
        @if ($user->badges->isNotEmpty())
            <div class="card mt-4 p-4 sm:p-5">
                <x-section-heading title="Mes badges" :count="$user->badges->count()">
                    <x-slot name="action">
                        <a href="{{ route('devscore.show') }}" class="link-muted text-sm">Tout voir</a>
                    </x-slot>
                </x-section-heading>

                <ul class="mt-4 flex flex-wrap gap-2">
                    @foreach ($user->badges as $badge)
                        <li class="chip" title="{{ $badge->description }}">
                            <x-badge-icon :badge="$badge" class="h-3.5 w-3.5 text-ink-500 dark:text-ink-400" />
                            {{ $badge->name }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{--
            Deux colonnes : à gauche ce sur quoi on agit (des gens, des
            projets), à droite ce qu'on consulte (le fil). La colonne de
            droite est plus étroite et ne descend pas sous le contenu.
        --}}
        <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_21rem]">
            {{--
                `min-w-0` sur les deux colonnes : un élément de grille a
                `min-width: auto` par défaut, donc il refuse de descendre sous
                la largeur minimale de son contenu. Or une ligne en `truncate`
                est en `white-space: nowrap` : son minimum, c'est son texte
                entier. Sans cela la colonne déborde de l'écran sur téléphone.
            --}}
            <div class="min-w-0 space-y-6">

                {{-- Développeurs recommandés --}}
                <section class="panel">
                    <x-section-heading title="Développeurs recommandés">
                        <x-slot name="action">
                            <a href="{{ route('ai.create') }}" class="link-muted text-sm">Demander à AfriCommit AI</a>
                        </x-slot>
                    </x-section-heading>

                    @forelse ($recommendedDevelopers as $recommendation)
                        @php($developer = $recommendation['user'])

                        <a
                            href="{{ route('devs.show', $developer) }}"
                            class="-mx-2 mt-1 flex items-center gap-3 rounded-lg px-2 py-2.5 transition-colors hover:bg-ink-50 dark:hover:bg-ink-800/60 {{ $loop->first ? 'mt-4' : '' }}"
                        >
                            <x-avatar :user="$developer" size="sm" />

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-ink-900 dark:text-white">
                                    {{ $developer->name }}
                                </p>
                                <p class="muted mt-0.5 truncate">{{ $recommendation['reason'] }}</p>
                            </div>

                            {{-- Le pourcentage de correspondance n'existe que
                                 pour les recommandations issues de mes postes. --}}
                            @if ($recommendation['score'])
                                <span class="badge-neutral tabular shrink-0">{{ $recommendation['score'] }} %</span>
                            @else
                                <x-country-label :country="$developer->country" class="hidden shrink-0 text-xs sm:inline-flex" />
                            @endif
                        </a>
                    @empty
                        <x-empty-state class="mt-4" title="Pas encore de recommandation">
                            Complétez votre profil et vos compétences pour que nous sachions qui vous proposer.

                            <x-slot name="action">
                                <a href="{{ route('devs.index') }}" class="btn-secondary btn-sm">Trouver des développeurs</a>
                            </x-slot>
                        </x-empty-state>
                    @endforelse
                </section>

                {{-- Projets intéressants --}}
                <section class="panel">
                    <x-section-heading title="Projets qui recrutent">
                        <x-slot name="action">
                            <a href="{{ route('projects.index') }}" class="link-muted text-sm">Tous les projets</a>
                        </x-slot>
                    </x-section-heading>

                    @forelse ($interestingProjects as $project)
                        @php($openRoles = $project->roles->reject->isFull())

                        <a
                            href="{{ route('projects.show', $project) }}"
                            class="-mx-2 flex items-start gap-3 rounded-lg px-2 py-2.5 transition-colors hover:bg-ink-50 dark:hover:bg-ink-800/60 {{ $loop->first ? 'mt-3' : '' }}"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-ink-900 dark:text-white">
                                    {{ $project->title }}
                                </p>

                                <p class="muted mt-0.5 truncate">
                                    {{ $project->category->label() }}
                                    <span class="text-ink-300 dark:text-ink-700" aria-hidden="true">·</span>
                                    par {{ $project->owner->name }}
                                    @if ($project->country)
                                        <span class="text-ink-300 dark:text-ink-700" aria-hidden="true">·</span>
                                        {{ $project->country->name }}
                                    @endif
                                </p>

                                {{-- Les postes ouverts : c'est ce qui donne envie de cliquer. --}}
                                @if ($openRoles->isNotEmpty())
                                    <p class="mt-1.5 truncate text-xs text-ink-600 dark:text-ink-400">
                                        Recherche&nbsp;: {{ $openRoles->pluck('title')->join(', ') }}
                                    </p>
                                @endif
                            </div>

                            <x-project-status :status="$project->status" class="shrink-0" />
                        </a>
                    @empty
                        <x-empty-state class="mt-4" title="Aucun projet ouvert pour le moment">
                            Lancez le vôtre : décrivez l'idée, listez les postes, et laissez les développeurs venir.

                            <x-slot name="action">
                                <a href="{{ route('projects.create') }}" class="btn-primary btn-sm">Créer un projet</a>
                            </x-slot>
                        </x-empty-state>
                    @endforelse
                </section>
            </div>

            {{-- Fil d'activité --}}
            <aside class="panel h-fit min-w-0 lg:sticky lg:top-20">
                <x-section-heading title="Fil d'activité" />

                {{-- Deux portées seulement : mon réseau, ou tout le monde. --}}
                <div class="mt-4 flex gap-1.5">
                    @foreach ($feedScopes as $value => $label)
                        <a
                            href="{{ route('dashboard', ['feed' => $value]) }}"
                            class="chip-button {{ $feedScope === $value ? 'chip-button-active' : '' }}"
                            @if ($feedScope === $value) aria-current="page" @endif
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                @forelse ($feed as $activity)
                    @if ($loop->first)
                        <ul class="mt-4 divide-y divide-ink-100 dark:divide-ink-800/70">
                    @endif

                    @php($subject = $activity->subject)

                    <li class="py-3 first:pt-0 last:pb-0">
                        <p class="text-sm leading-snug text-ink-600 dark:text-ink-400">
                            <a href="{{ route('devs.show', $activity->user) }}" class="font-medium text-ink-900 hover:underline dark:text-white">
                                {{ $activity->user->name }}
                            </a>

                            {{ $activity->type->label() }}

                            @if ($subject instanceof \App\Models\Badge)
                                {{-- Un badge n'a pas de page : on le montre tel quel,
                                     avec son icône du jeu plutôt que son emoji. --}}
                                <span class="inline-flex items-center gap-1 align-middle font-medium text-ink-900 dark:text-white">
                                    <x-badge-icon :badge="$subject" class="h-3.5 w-3.5 text-ink-500 dark:text-ink-400" />
                                    {{ $subject->name }}
                                </span>
                            @elseif ($link = $activityFeed->linkFor($activity))
                                <a href="{{ $link }}" class="font-medium text-ink-900 hover:underline dark:text-white">
                                    {{ $activityFeed->subjectTitle($activity) }}
                                </a>
                            @else
                                <span class="font-medium text-ink-900 dark:text-white">
                                    {{ $activityFeed->subjectTitle($activity) }}
                                </span>
                            @endif
                        </p>

                        <p class="mt-1 text-xs text-ink-500">{{ $activity->created_at->diffForHumans() }}</p>
                    </li>

                    @if ($loop->last)
                        </ul>
                    @endif
                @empty
                    <x-empty-state class="mt-4" title="Rien pour l'instant">
                        @if ($feedScope === 'network')
                            Suivez des développeurs pour voir leur activité ici.
                        @else
                            Les premières publications apparaîtront ici.
                        @endif

                        @if ($feedScope === 'network')
                            <x-slot name="action">
                                <a href="{{ route('dashboard', ['feed' => 'all']) }}" class="btn-secondary btn-sm">
                                    Voir toute la communauté
                                </a>
                            </x-slot>
                        @endif
                    </x-empty-state>
                @endforelse
            </aside>
        </div>
    </div>
</x-app-layout>
