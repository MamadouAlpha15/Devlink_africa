{{--
    Profil public d'un développeur.

    Variables : $user, $projects, $resources, $ratingAvg, $isOwner,
    $canFollow, $isFollowing, $invitableProjects.
    Voir docs/front-end/profils.md.

    Rappel : `email` ne s'affiche que si `show_email`, et la disponibilité
    que si `show_availability` (géré par <x-availability>).
--}}
@php
    // Liens externes du profil, dans l'ordre d'affichage.
    //
    // SÉCURITÉ — ces quatre URL sont saisies librement par l'utilisateur et
    // finissent dans un attribut `href`. Une valeur comme `javascript:…` ou
    // `data:text/html,…` s'exécuterait au clic, dans le navigateur d'un
    // visiteur, sur ce domaine. La validation du backend porte sur la forme
    // de l'URL, pas sur son schéma : on n'affiche donc que http et https,
    // et on ignore silencieusement le reste.
    $allowedSchemes = ['http', 'https'];

    $externalLinks = collect([
        ['url' => $user->github_url, 'label' => 'GitHub', 'icon' => 'github'],
        ['url' => $user->linkedin_url, 'label' => 'LinkedIn', 'icon' => 'linkedin'],
        ['url' => $user->portfolio_url, 'label' => 'Portfolio', 'icon' => 'briefcase'],
        ['url' => $user->website_url, 'label' => 'Site personnel', 'icon' => 'link'],
    ])->filter(function ($link) use ($allowedSchemes) {
        if (blank($link['url'])) {
            return false;
        }

        $scheme = parse_url($link['url'], PHP_URL_SCHEME);

        return is_string($scheme) && in_array(strtolower($scheme), $allowedSchemes, true);
    });

    // Compteurs affichés sous l'identité.
    $stats = [
        ['value' => $user->projects_count, 'label' => 'Projets', 'icon' => 'folder'],
        ['value' => $user->resources_count, 'label' => 'Ressources', 'icon' => 'book'],
        ['value' => $user->collaborations_count, 'label' => 'Collaborations', 'icon' => 'handshake'],
        ['value' => $user->followers_count, 'label' => 'Abonnés', 'icon' => 'users'],
    ];
@endphp

<x-app-layout :title="$user->name">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-flash />

        @if ($isOwner && ! $user->is_public)
            <div class="alert-warning mb-6">
                <x-icon name="eye-off" class="mt-px h-4 w-4 shrink-0" />
                <div class="flex-1">
                    <p class="font-semibold">Votre profil est privé</p>
                    <p class="mt-0.5">Vous seul pouvez le voir. Rendez-le public depuis
                        <a href="{{ route('developer-profile.edit') }}" class="underline underline-offset-2">la modification du profil</a>
                        pour apparaître dans les recherches.</p>
                </div>
            </div>
        @endif

        {{-- ==========  En-tête du profil  ========== --}}
        <div class="panel">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
                <x-avatar :user="$user" size="2xl" />

                <div class="min-w-0 flex-1">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <h1 class="page-title">{{ $user->name }}</h1>

                            <p class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-ink-600 dark:text-ink-400">
                                <span class="font-medium text-ink-800 dark:text-ink-200">{{ $user->profile_type?->label() }}</span>
                                @if ($user->experience_level)
                                    <span class="text-ink-300 dark:text-ink-700">·</span>
                                    <span>{{ $user->experience_level->label() }}</span>
                                @endif
                                <span class="text-ink-300 dark:text-ink-700">·</span>
                                <span class="font-mono text-xs text-ink-500">{{ '@'.$user->username }}</span>
                            </p>

                            <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-2">
                                <x-country-label :country="$user->country" :city="$user->city" />
                                <x-availability :user="$user" />
                            </div>
                        </div>

                        {{-- DevScore mis en avant --}}
                        <div class="flex shrink-0 items-center gap-3 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 dark:border-brand-800 dark:bg-brand-950">
                            <x-icon name="zap" class="h-5 w-5 text-brand-600 dark:text-brand-400" />
                            <div>
                                <p class="tabular text-xl font-bold leading-none text-brand-700 dark:text-brand-300">
                                    {{ number_format($user->devscore, 0, ',', ' ') }}
                                </p>
                                <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-brand-600 dark:text-brand-400">
                                    points DevScore
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-5 flex flex-wrap items-center gap-2">
                        @if ($isOwner)
                            <a href="{{ route('developer-profile.edit') }}" class="btn-primary btn-sm">
                                <x-icon name="pencil" class="h-4 w-4" />
                                Modifier mon profil
                            </a>
                        @elseif ($canFollow)
                            @if ($isFollowing)
                                <form method="POST" action="{{ route('devs.unfollow', $user) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-secondary btn-sm">
                                        <x-icon name="user-check" class="h-4 w-4" />
                                        Ne plus suivre
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('devs.follow', $user) }}">
                                    @csrf
                                    <button type="submit" class="btn-primary btn-sm">
                                        <x-icon name="user-plus" class="h-4 w-4" />
                                        Suivre
                                    </button>
                                </form>
                            @endif
                        @endif

                        {{-- Enregistrer ce développeur. Le partial ne rend rien
                             pour un visiteur ni sur son propre profil. --}}
                        @include('partials.favorite-button', ['type' => 'developer', 'model' => $user])
                        @include('partials.report-button', ['type' => 'developer', 'model' => $user, 'authorId' => $user->id])

                        @if ($user->show_email)
                            <a href="mailto:{{ $user->email }}" class="btn-secondary btn-sm">
                                <x-icon name="mail" class="h-4 w-4" />
                                Contacter
                            </a>
                        @endif

                        {{-- Partage du lien du profil --}}
                        <button
                            type="button"
                            x-data="copyToClipboard(@js(route('devs.show', $user)))"
                            @click="copy()"
                            class="btn-ghost btn-sm"
                        >
                            <x-icon name="copy" class="h-4 w-4" x-show="! hasCopied" />
                            <x-icon name="check" class="h-4 w-4 text-emerald-600" x-show="hasCopied" x-cloak />
                            <span x-text="hasCopied ? 'Lien copié' : 'Partager'">Partager</span>
                        </button>

                        @foreach ($externalLinks as $link)
                            <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer"
                               class="btn-ghost btn-sm" title="{{ $link['label'] }}">
                                <x-icon :name="$link['icon']" class="h-4 w-4" />
                                <span class="hidden sm:inline">{{ $link['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Compteurs --}}
            <div class="divider mt-6 grid grid-cols-2 gap-3 pt-6 sm:grid-cols-4">
                @foreach ($stats as $stat)
                    <x-stat :value="$stat['value']" :label="$stat['label']" :icon="$stat['icon']" />
                @endforeach
            </div>
        </div>

        {{-- ==========  Contenu  ========== --}}
        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_20rem]">

            {{-- Colonne principale --}}
            <div class="min-w-0 space-y-6">

                @if ($user->bio)
                    <div class="panel">
                        <x-section-heading title="À propos" />
                        <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-ink-700 dark:text-ink-300">{{ $user->bio }}</p>
                    </div>
                @endif

                {{-- Compétences, groupées par niveau pour être lisibles d'un coup d'œil --}}
                <div class="panel">
                    <x-section-heading title="Compétences" :count="$user->skills->count()" />

                    @if ($user->skills->isNotEmpty())
                        @php($skillsByLevel = $user->skills->groupBy(fn ($skill) => $skill->pivot->level->value))

                        <div class="mt-5 space-y-4">
                            @foreach (['expert', 'advanced', 'intermediate', 'beginner'] as $level)
                                @if ($skillsByLevel->has($level))
                                    <div>
                                        <p class="caption">
                                            {{ \App\Enums\SkillLevel::from($level)->label() }}
                                        </p>
                                        <ul class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach ($skillsByLevel[$level] as $skill)
                                                <li class="{{ $level === 'expert' ? 'chip' : 'chip' }}">{{ $skill->name }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <p class="muted mt-4">Aucune compétence renseignée pour le moment.</p>
                    @endif
                </div>

                {{-- Projets --}}
                <div class="panel">
                    <x-section-heading title="Projets" :count="$projects->count()" />

                    @if ($projects->isNotEmpty())
                        <ul class="mt-5 space-y-2.5">
                            @foreach ($projects as $project)
                                <li>
                                    <a href="{{ route('projects.show', $project) }}"
                                       class="group flex items-center justify-between gap-4 rounded-lg border border-ink-200 p-3.5 transition hover:border-brand-300 hover:bg-brand-50 dark:border-ink-800 dark:hover:border-brand-700 dark:hover:bg-ink-800">
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-ink-900 group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-300">
                                                {{ $project->title }}
                                            </p>
                                            <p class="mt-1 flex flex-wrap items-center gap-x-2 text-xs text-ink-500 dark:text-ink-400">
                                                <span>{{ $project->pivot->role->label() }}</span>
                                                @if ($project->country)
                                                    <span class="text-ink-300 dark:text-ink-700">·</span>
                                                    <x-flag :country="$project->country" /><span>{{ $project->country->name }}</span>
                                                @endif
                                            </p>
                                        </div>

                                        <x-project-status :status="$project->status" class="shrink-0" />
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="muted mt-4">Aucun projet pour l'instant.</p>
                    @endif
                </div>

                {{-- Ressources --}}
                <div class="panel">
                    <x-section-heading title="Ressources publiées" :count="$resources->count()" />

                    @if ($resources->isNotEmpty())
                        <ul class="mt-5 space-y-2.5">
                            @foreach ($resources as $resource)
                                <li>
                                    {{-- Titre cliquable : depuis un profil, on veut
                                         ouvrir la ressource, pas seulement la lire. --}}
                                    <a
                                        href="{{ route('resources.show', $resource) }}"
                                        class="group flex items-center justify-between gap-4 rounded-lg border border-ink-200 p-3.5 transition hover:border-brand-300 hover:bg-brand-50 dark:border-ink-800 dark:hover:border-brand-700 dark:hover:bg-ink-800"
                                    >
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-ink-900 group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-300">
                                                {{ $resource->title }}
                                            </p>
                                            <p class="mt-1 text-xs text-ink-500 dark:text-ink-400">{{ $resource->type->label() }}</p>
                                        </div>

                                        <div class="flex shrink-0 items-center gap-3 text-xs text-ink-500 dark:text-ink-400">
                                            @if ($resource->rating_avg)
                                                <span class="inline-flex items-center gap-1">
                                                    <x-icon name="star" class="h-3.5 w-3.5 text-amber-500" />
                                                    <span class="tabular font-medium">{{ number_format($resource->rating_avg, 1, ',', ' ') }}</span>
                                                </span>
                                            @endif

                                            <span class="inline-flex items-center gap-1">
                                                <x-icon name="heart" class="h-3.5 w-3.5" />
                                                <span class="tabular">{{ $resource->likes_count }}</span>
                                            </span>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="muted mt-4">Aucune ressource publiée.</p>
                    @endif
                </div>
            </div>

            {{-- Colonne latérale --}}
            <aside class="space-y-6">

                {{-- Proposer une collaboration --}}
                @if ($invitableProjects->isNotEmpty())
                    {{-- L'URL d'envoi n'est plus fabriquée par concaténation dans
                         le navigateur : chaque option porte directement l'URL
                         complète, générée par `route()` côté serveur. Rien de ce
                         qui vient du DOM n'entre dans la construction du chemin. --}}
                    <form
                        method="POST"
                        x-data="{ action: @js(route('projects.invitations.store', $invitableProjects->first())) }"
                        :action="action"
                        class="panel border-brand-300 dark:border-brand-700"
                    >
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $user->id }}">

                        <x-section-heading title="Proposer une collaboration" />

                        <p class="muted mt-3">
                            Invitez {{ $user->first_name ?? $user->name }} à rejoindre un de vos projets.
                        </p>

                        <div class="mt-4 space-y-3">
                            <div>
                                <label for="invite-project" class="label">Projet</label>
                                <select id="invite-project" x-model="action" class="select">
                                    @foreach ($invitableProjects as $project)
                                        <option value="{{ route('projects.invitations.store', $project) }}">{{ $project->title }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div x-data="charCounter('', 1000)">
                                <label for="invite-message" class="label">
                                    Message <span class="label-hint">facultatif</span>
                                </label>
                                <textarea
                                    id="invite-message"
                                    name="message"
                                    x-model="value"
                                    rows="3"
                                    maxlength="1000"
                                    class="textarea"
                                    placeholder="Bonjour, ton profil correspond à notre projet…"
                                ></textarea>
                                <p class="field-hint text-right" :class="isNearLimit && 'text-amber-600'">
                                    <span class="tabular" x-text="length">0</span> / 1000
                                </p>
                            </div>

                            <x-input-error :messages="$errors->get('collaboration')" />

                            <button type="submit" class="btn-primary btn-block">
                                <x-icon name="send" class="h-4 w-4" />
                                Envoyer l'invitation
                            </button>
                        </div>
                    </form>
                @endif

                {{-- Badges --}}
                <div class="panel">
                    <x-section-heading title="Badges" :count="$user->badges->count()" />

                    {{--
                        Icône du jeu de l'application, pas l'emoji stocké en base :
                        un emoji ne se rend pas pareil d'un système à l'autre et
                        détonne au milieu d'une interface dessinée.
                    --}}
                    @if ($user->badges->isNotEmpty())
                        <ul class="mt-4 space-y-2.5">
                            @foreach ($user->badges as $badge)
                                <li class="flex items-start gap-3 rounded-lg border border-ink-200 p-3 dark:border-ink-800">
                                    <x-badge-icon :badge="$badge" class="mt-0.5 h-5 w-5 shrink-0 text-brand-600 dark:text-brand-400" />

                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-ink-900 dark:text-white">{{ $badge->name }}</p>
                                        <p class="mt-0.5 text-xs text-ink-600 dark:text-ink-400">{{ $badge->description }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="muted mt-4">Pas encore de badge.</p>
                    @endif
                </div>

                {{-- Chiffres complémentaires --}}
                <div class="panel">
                    <x-section-heading title="En résumé" />

                    <dl class="mt-4 space-y-3 text-sm">
                        @if ($ratingAvg)
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-ink-600 dark:text-ink-400">Note moyenne</dt>
                                <dd class="tabular inline-flex items-center gap-1 font-semibold text-ink-900 dark:text-white">
                                    <x-icon name="star" class="h-3.5 w-3.5 text-amber-500" />
                                    {{ number_format($ratingAvg, 1, ',', ' ') }}
                                </dd>
                            </div>
                        @endif

                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-ink-600 dark:text-ink-400">Abonnements</dt>
                            <dd class="tabular font-semibold text-ink-900 dark:text-white">{{ $user->following_count }}</dd>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-ink-600 dark:text-ink-400">Membre depuis</dt>
                            <dd class="font-semibold text-ink-900 dark:text-white">{{ $user->created_at->translatedFormat('F Y') }}</dd>
                        </div>
                    </dl>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
