{{--
    Détail d'un projet.

    Variables : $project, $viewerMembership, $viewerPendingRequest,
    $canUpdate, $canManage, $canApply, $pendingApplications,
    $pendingInvitations, $suggestions, $skills.
    Voir docs/front-end/projets.md.

    Les erreurs métier arrivent sous les clés `collaboration`,
    `project_role_id` et `member`.
--}}
@php
    $openRoles = $project->roles->reject->isFull();
    $filledSlots = $project->memberships->count();
@endphp

<x-app-layout :title="$project->title">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <a href="{{ route('projects.index') }}" class="mb-5 inline-flex items-center gap-1.5 text-sm font-medium text-ink-500 transition hover:text-brand-600 dark:text-ink-400 dark:hover:text-brand-400">
            <x-icon name="arrow-left" class="h-4 w-4" />
            Tous les projets
        </a>

        <x-flash :error-keys="['collaboration', 'project_role_id', 'member']" />

        {{-- ==========  En-tête  ========== --}}
        <div class="card mt-4 overflow-hidden">
            @if ($project->coverUrl())
                <div class="aspect-[21/7] w-full border-b border-ink-200 bg-ink-100 dark:border-ink-800 dark:bg-ink-800">
                    <img src="{{ $project->coverUrl() }}" alt="" class="h-full w-full object-cover">
                </div>
            @endif

            <div class="p-5 sm:p-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="chip">{{ $project->category->label() }}</span>
                            <x-project-status :status="$project->status" />
                        </div>

                        <h1 class="page-title mt-3">{{ $project->title }}</h1>

                        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-ink-600 dark:text-ink-400">
                            <x-country-label :country="$project->country" />

                            <span class="inline-flex items-center gap-1.5">
                                <x-icon name="users" class="h-3.5 w-3.5 text-ink-400" />
                                <span class="tabular">{{ $filledSlots }}</span>
                                {{ $filledSlots > 1 ? 'membres' : 'membre' }}
                            </span>

                            <span class="inline-flex items-center gap-1.5">
                                <x-icon name="calendar" class="h-3.5 w-3.5 text-ink-400" />
                                {{ $project->created_at->translatedFormat('d F Y') }}
                            </span>
                        </div>
                    </div>

                    {{-- Actions du visiteur --}}
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        @if ($canUpdate)
                            <a href="{{ route('projects.edit', $project) }}" class="btn-secondary btn-sm">
                                <x-icon name="pencil" class="h-4 w-4" />
                                Modifier
                            </a>
                        @endif

                        {{-- Enregistrer ce projet pour le retrouver dans mes favoris. --}}
                        @include('partials.favorite-button', ['type' => 'project', 'model' => $project])
                        @include('partials.report-button', ['type' => 'project', 'model' => $project, 'authorId' => $project->owner_id])

                        <button
                            type="button"
                            x-data="copyToClipboard(@js(route('projects.show', $project)))"
                            @click="copy()"
                            class="btn-ghost btn-sm"
                        >
                            <x-icon name="copy" class="h-4 w-4" x-show="! hasCopied" />
                            <x-icon name="check" class="h-4 w-4 text-emerald-600" x-show="hasCopied" x-cloak />
                            <span x-text="hasCopied ? 'Lien copié' : 'Partager'">Partager</span>
                        </button>

                        @if ($viewerMembership && $viewerMembership->role !== \App\Enums\ProjectMemberRole::Owner)
                            <form method="POST" action="{{ route('projects.leave', $project) }}" x-data="confirmAction">
                                @csrf
                                <button type="button" x-show="! isConfirming" @click="ask()" class="btn-ghost btn-sm">
                                    <x-icon name="logout" class="h-4 w-4" />
                                    Quitter
                                </button>

                                <span x-show="isConfirming" x-cloak class="inline-flex items-center gap-1.5">
                                    <button type="submit" class="btn-danger btn-sm">Confirmer</button>
                                    <button type="button" @click="cancel()" class="btn-ghost btn-sm">Annuler</button>
                                </span>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Bandeau d'état de ma demande / invitation --}}
                @if ($viewerPendingRequest)
                    <div class="mt-5 {{ $viewerPendingRequest->direction === \App\Enums\RequestDirection::Invitation ? 'alert-info' : 'alert-warning' }}">
                        @if ($viewerPendingRequest->direction === \App\Enums\RequestDirection::Invitation)
                            <x-icon name="mail" class="mt-px h-4 w-4 shrink-0" />
                            <div class="flex-1">
                                <p class="font-semibold">Vous êtes invité à rejoindre ce projet</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('collaborations.accept', $viewerPendingRequest) }}">
                                        @csrf
                                        <button type="submit" class="btn-primary btn-sm">
                                            <x-icon name="check" class="h-4 w-4" />
                                            Accepter
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('collaborations.reject', $viewerPendingRequest) }}">
                                        @csrf
                                        <button type="submit" class="btn-secondary btn-sm">Refuser</button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <x-icon name="clock" class="mt-px h-4 w-4 shrink-0" />
                            <div class="flex-1">
                                <p class="font-semibold">Votre demande est en attente de réponse</p>
                                <form method="POST" action="{{ route('collaborations.destroy', $viewerPendingRequest) }}" class="mt-3">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-secondary btn-sm">Annuler ma demande</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @elseif (! auth()->check() && $project->acceptsCollaborators())
                    <div class="alert-info mt-5">
                        <x-icon name="lock" class="mt-px h-4 w-4 shrink-0" />
                        <p class="flex-1">
                            <a href="{{ route('login') }}" class="font-semibold underline underline-offset-2">Connectez-vous</a>
                            pour demander à rejoindre ce projet.
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_20rem]">

            {{-- ==========  Colonne principale  ========== --}}
            <div class="min-w-0 space-y-6">

                {{-- Rejoindre --}}
                @if ($canApply)
                    <form method="POST" action="{{ route('projects.requests.store', $project) }}" class="panel border-brand-300 dark:border-brand-700">
                        @csrf

                        <x-section-heading title="Rejoindre ce projet" />

                        <div class="mt-4 space-y-4">
                            <div>
                                <label for="project_role_id" class="label">
                                    Poste visé <span class="label-hint">facultatif</span>
                                </label>
                                <select id="project_role_id" name="project_role_id" class="select">
                                    <option value="">Peu importe le poste</option>
                                    @foreach ($openRoles as $role)
                                        <option value="{{ $role->id }}" @selected(old('project_role_id') == $role->id)>
                                            {{ $role->title }} — {{ $role->openSlots() }} {{ $role->openSlots() > 1 ? 'places' : 'place' }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('project_role_id')" />
                            </div>

                            <div x-data="charCounter(@js(old('message', '')), 1000)">
                                <label for="message" class="label">Message de motivation</label>
                                <textarea
                                    id="message"
                                    name="message"
                                    x-model="value"
                                    rows="4"
                                    maxlength="1000"
                                    class="textarea"
                                    placeholder="Bonjour, j'aimerais rejoindre ton projet en tant que…"
                                >{{ old('message') }}</textarea>
                                <p class="field-hint text-right" :class="isNearLimit && 'text-amber-600'">
                                    <span class="tabular" x-text="length">0</span> / 1000
                                </p>
                                <x-input-error :messages="$errors->get('message')" />
                            </div>

                            <button type="submit" class="btn-primary">
                                <x-icon name="send" class="h-4 w-4" />
                                Envoyer ma demande
                            </button>
                        </div>
                    </form>
                @endif

                {{-- Description --}}
                <div class="panel">
                    <x-section-heading title="À propos du projet" />

                    <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-ink-700 dark:text-ink-300">
                        {{ $project->description }}
                    </p>

                    @if ($project->skills->isNotEmpty())
                        <div class="divider mt-5 pt-5">
                            <p class="caption">Technologies</p>
                            <x-skill-chips :skills="$project->skills" :limit="null" class="mt-2.5" />
                        </div>
                    @endif
                </div>

                {{-- Postes recherchés --}}
                <div class="panel">
                    <x-section-heading title="Profils recherchés" :count="$project->roles->count()" />

                    @if ($project->roles->isNotEmpty())
                        <ul class="mt-5 space-y-2.5">
                            @foreach ($project->roles as $role)
                                <li x-data="{ isEditing: false }" class="rounded-lg border border-ink-200 p-3.5 dark:border-ink-800">

                                    {{-- Affichage --}}
                                    <div x-show="! isEditing" class="flex flex-wrap items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="font-medium text-ink-900 dark:text-white">{{ $role->title }}</p>
                                            <p class="mt-1 flex flex-wrap items-center gap-x-2 text-xs text-ink-500 dark:text-ink-400">
                                                @if ($role->skill)
                                                    <span>{{ $role->skill->name }}</span>
                                                    <span class="text-ink-300 dark:text-ink-700">·</span>
                                                @endif
                                                <span class="tabular">{{ $role->members_count }} / {{ $role->slots }}</span>
                                                <span>{{ $role->members_count > 1 ? 'pourvus' : 'pourvu' }}</span>
                                            </p>
                                        </div>

                                        <div class="flex shrink-0 items-center gap-2">
                                            @if ($role->isFull())
                                                <span class="badge-neutral">Pourvu</span>
                                            @else
                                                <span class="badge-success">{{ $role->openSlots() }} {{ $role->openSlots() > 1 ? 'places' : 'place' }}</span>
                                            @endif

                                            @if ($canManage)
                                                <button type="button" @click="isEditing = true" class="btn-ghost btn-xs" aria-label="Modifier le poste">
                                                    <x-icon name="pencil" class="h-3.5 w-3.5" />
                                                </button>

                                                <form method="POST" action="{{ route('projects.roles.destroy', [$project, $role]) }}" x-data="confirmAction">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" x-show="! isConfirming" @click="ask()" class="btn-danger-ghost btn-xs" aria-label="Supprimer le poste">
                                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                                    </button>
                                                    <span x-show="isConfirming" x-cloak class="inline-flex items-center gap-1">
                                                        <button type="submit" class="btn-danger btn-xs">Supprimer</button>
                                                        <button type="button" @click="cancel()" class="btn-ghost btn-xs">Non</button>
                                                    </span>
                                                </form>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Modification en ligne (responsables) --}}
                                    @if ($canManage)
                                        <form
                                            x-show="isEditing"
                                            x-cloak
                                            method="POST"
                                            action="{{ route('projects.roles.update', [$project, $role]) }}"
                                            class="grid gap-2 sm:grid-cols-[1fr_1fr_5rem_auto]"
                                        >
                                            @csrf
                                            @method('PUT')

                                            <input type="text" name="title" value="{{ $role->title }}" required
                                                   class="input h-10 text-sm" aria-label="Intitulé du poste">

                                            <select name="skill_id" class="select h-10 text-sm" aria-label="Compétence">
                                                <option value="">Compétence</option>
                                                @foreach ($skills as $skill)
                                                    <option value="{{ $skill->id }}" @selected($role->skill_id === $skill->id)>{{ $skill->name }}</option>
                                                @endforeach
                                            </select>

                                            <input type="number" name="slots" value="{{ $role->slots }}" min="1" max="10"
                                                   class="input h-10 text-sm" aria-label="Nombre de places">

                                            <div class="flex items-center gap-1.5">
                                                <button type="submit" class="btn-primary btn-sm">Enregistrer</button>
                                                <button type="button" @click="isEditing = false" class="btn-ghost btn-sm">Annuler</button>
                                            </div>
                                        </form>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="muted mt-4">Aucun poste défini pour le moment.</p>
                    @endif

                    {{-- Ajouter un poste --}}
                    @if ($canManage)
                        <form method="POST" action="{{ route('projects.roles.store', $project) }}" class="divider mt-5 pt-5">
                            @csrf

                            <p class="caption mb-2">Ajouter un poste</p>

                            <div class="grid gap-2 sm:grid-cols-[1fr_1fr_5rem_auto]">
                                <input type="text" name="title" required placeholder="ex. UI/UX Designer"
                                       class="input h-10 text-sm" aria-label="Intitulé du poste">

                                <select name="skill_id" class="select h-10 text-sm" aria-label="Compétence">
                                    <option value="">Compétence</option>
                                    @foreach ($skills as $skill)
                                        <option value="{{ $skill->id }}">{{ $skill->name }}</option>
                                    @endforeach
                                </select>

                                <input type="number" name="slots" value="1" min="1" max="10"
                                       class="input h-10 text-sm" aria-label="Nombre de places">

                                <button type="submit" class="btn-primary btn-sm">
                                    <x-icon name="plus" class="h-4 w-4" />
                                    Ajouter
                                </button>
                            </div>

                            <x-input-error :messages="$errors->get('title')" />
                            <x-input-error :messages="$errors->get('slots')" />
                        </form>
                    @endif
                </div>

                {{-- Équipe --}}
                <div class="panel">
                    <x-section-heading title="Équipe" :count="$project->memberships->count()" />

                    <ul class="mt-5 space-y-2.5">
                        @foreach ($project->memberships as $membership)
                            <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-ink-200 p-3 dark:border-ink-800">
                                <a href="{{ route('devs.show', $membership->user) }}" class="group flex min-w-0 items-center gap-3">
                                    <x-avatar :user="$membership->user" size="sm" />

                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-ink-900 group-hover:text-brand-600 dark:text-white dark:group-hover:text-brand-400">
                                            {{ $membership->user->name }}
                                        </p>
                                        <p class="mt-0.5 flex flex-wrap items-center gap-x-1.5 text-xs text-ink-500 dark:text-ink-400">
                                            <span>{{ $membership->role->label() }}</span>
                                            @if ($membership->projectRole)
                                                <span class="text-ink-300 dark:text-ink-700">·</span>
                                                <span>{{ $membership->projectRole->title }}</span>
                                            @endif
                                            @if ($membership->user->country)
                                                <span class="text-ink-300 dark:text-ink-700">·</span>
                                                <x-flag :country="$membership->user->country" />
                                            @endif
                                        </p>
                                    </div>
                                </a>

                                <div class="flex shrink-0 items-center gap-1.5">
                                    @if ($membership->role === \App\Enums\ProjectMemberRole::Owner)
                                        <span class="badge-neutral">
                                            <x-icon name="crown" class="h-3 w-3" />
                                            Propriétaire
                                        </span>
                                    @elseif ($membership->role === \App\Enums\ProjectMemberRole::CoOwner)
                                        <span class="badge-neutral">Co-responsable</span>
                                    @endif

                                    @if ($canManage && $membership->role !== \App\Enums\ProjectMemberRole::Owner)
                                        @if ($canUpdate)
                                            <form method="POST" action="{{ route('projects.members.update', [$project, $membership->user]) }}">
                                                @csrf
                                                @method('PATCH')
                                                @if ($membership->role === \App\Enums\ProjectMemberRole::CoOwner)
                                                    <input type="hidden" name="role" value="member">
                                                    <button type="submit" class="btn-ghost btn-xs">Rétrograder</button>
                                                @else
                                                    <input type="hidden" name="role" value="co_owner">
                                                    <button type="submit" class="btn-ghost btn-xs">Promouvoir</button>
                                                @endif
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('projects.members.destroy', [$project, $membership->user]) }}" x-data="confirmAction">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" x-show="! isConfirming" @click="ask()" class="btn-danger-ghost btn-xs" aria-label="Retirer le membre">
                                                <x-icon name="close" class="h-3.5 w-3.5" />
                                            </button>
                                            <span x-show="isConfirming" x-cloak class="inline-flex items-center gap-1">
                                                <button type="submit" class="btn-danger btn-xs">Retirer</button>
                                                <button type="button" @click="cancel()" class="btn-ghost btn-xs">Non</button>
                                            </span>
                                        </form>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- ==========  Espace des responsables  ========== --}}
                @if ($canManage)

                    {{-- Demandes reçues --}}
                    <div class="panel">
                        <x-section-heading title="Demandes pour rejoindre" :count="$pendingApplications->count()" />

                        @if ($pendingApplications->isNotEmpty())
                            <ul class="mt-5 space-y-3">
                                @foreach ($pendingApplications as $request)
                                    <li class="rounded-lg border border-ink-200 p-4 dark:border-ink-800">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <a href="{{ route('devs.show', $request->user) }}" class="group flex min-w-0 items-center gap-3">
                                                <x-avatar :user="$request->user" size="sm" />
                                                <div class="min-w-0">
                                                    <p class="truncate text-sm font-medium text-ink-900 group-hover:text-brand-600 dark:text-white dark:group-hover:text-brand-400">
                                                        {{ $request->user->name }}
                                                    </p>
                                                    <p class="mt-0.5 truncate text-xs text-ink-500 dark:text-ink-400">
                                                        <x-flag :country="$request->user->country" />
                                                        {{ $request->user->skills->take(3)->pluck('name')->join(', ') }}
                                                    </p>
                                                </div>
                                            </a>

                                            @if ($request->projectRole)
                                                <span class="chip shrink-0">{{ $request->projectRole->title }}</span>
                                            @endif
                                        </div>

                                        @if ($request->message)
                                            <p class="surface-muted mt-3 p-3 text-sm italic leading-relaxed text-ink-700 dark:text-ink-300">
                                                « {{ $request->message }} »
                                            </p>
                                        @endif

                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <form method="POST" action="{{ route('collaborations.accept', $request) }}">
                                                @csrf
                                                <button type="submit" class="btn-primary btn-sm">
                                                    <x-icon name="check" class="h-4 w-4" />
                                                    Accepter
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('collaborations.reject', $request) }}">
                                                @csrf
                                                <button type="submit" class="btn-secondary btn-sm">Refuser</button>
                                            </form>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="muted mt-4">Aucune demande en attente.</p>
                        @endif

                        @if ($pendingInvitations->isNotEmpty())
                            <div class="divider mt-5 pt-5">
                                <p class="caption">Invitations envoyées</p>

                                <ul class="mt-3 space-y-2">
                                    @foreach ($pendingInvitations as $invitation)
                                        <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-ink-200 px-3 py-2.5 dark:border-ink-800">
                                            <span class="min-w-0 truncate text-sm text-ink-700 dark:text-ink-300">
                                                {{ $invitation->user->name }}
                                                @if ($invitation->projectRole)
                                                    <span class="text-ink-500 dark:text-ink-400">· {{ $invitation->projectRole->title }}</span>
                                                @endif
                                            </span>

                                            <form method="POST" action="{{ route('collaborations.destroy', $invitation) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-ghost btn-xs">Annuler</button>
                                            </form>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    {{-- Suggestions --}}
                    <div class="panel">
                        <x-section-heading title="Développeurs suggérés">
                            <x-slot name="action">
                                <a href="{{ route('devs.index') }}" class="link text-sm">Rechercher</a>
                            </x-slot>
                        </x-section-heading>

                        @if (count($suggestions) > 0)
                            <div class="mt-5 space-y-5">
                                @foreach ($suggestions as $suggestion)
                                    <div>
                                        <p class="caption">
                                            Pour « {{ $suggestion['role']->title }} »
                                        </p>

                                        @if (count($suggestion['developers']) > 0)
                                            <ul class="mt-2.5 space-y-2">
                                                @foreach ($suggestion['developers'] as $developer)
                                                    <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-ink-200 p-3 dark:border-ink-800">
                                                        <a href="{{ route('devs.show', $developer) }}" class="group flex min-w-0 items-center gap-3">
                                                            <x-avatar :user="$developer" size="sm" />
                                                            <div class="min-w-0">
                                                                <p class="truncate text-sm font-medium text-ink-900 group-hover:text-brand-600 dark:text-white dark:group-hover:text-brand-400">
                                                                    {{ $developer->name }}
                                                                </p>
                                                                <p class="mt-0.5 truncate text-xs text-ink-500 dark:text-ink-400">
                                                                    <x-flag :country="$developer->country" /> {{ $developer->city }}
                                                                </p>
                                                            </div>
                                                        </a>

                                                        <div class="flex shrink-0 items-center gap-2">
                                                            <x-devscore :score="$developer->devscore" />

                                                            <form method="POST" action="{{ route('projects.invitations.store', $project) }}">
                                                                @csrf
                                                                <input type="hidden" name="user_id" value="{{ $developer->id }}">
                                                                <input type="hidden" name="project_role_id" value="{{ $suggestion['role']->id }}">
                                                                <button type="submit" class="btn-secondary btn-xs">
                                                                    <x-icon name="user-plus" class="h-3.5 w-3.5" />
                                                                    Inviter
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="muted mt-2">Aucun développeur disponible pour ce poste.</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="muted mt-4">
                                Ajoutez un poste avec une compétence pour recevoir des suggestions de profils.
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- ==========  Colonne latérale  ========== --}}
            <aside class="space-y-6">

                {{-- Porteur du projet --}}
                <div class="panel">
                    <p class="caption">Porteur du projet</p>

                    <a href="{{ route('devs.show', $project->owner) }}" class="group mt-3 flex items-center gap-3">
                        <x-avatar :user="$project->owner" size="md" />

                        <div class="min-w-0">
                            <p class="truncate font-semibold text-ink-900 group-hover:text-brand-600 dark:text-white dark:group-hover:text-brand-400">
                                {{ $project->owner->name }}
                            </p>
                            <p class="mt-0.5 truncate text-xs text-ink-500 dark:text-ink-400">
                                {{ $project->owner->profile_type?->label() }}
                            </p>
                            <x-devscore :score="$project->owner->devscore" class="mt-1.5" />
                        </div>
                    </a>
                </div>

                {{-- Informations clés --}}
                <div class="panel">
                    <p class="caption">Informations</p>

                    <dl class="mt-3 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-ink-600 dark:text-ink-400">Statut</dt>
                            <dd><x-project-status :status="$project->status" /></dd>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-ink-600 dark:text-ink-400">Catégorie</dt>
                            <dd class="font-medium text-ink-900 dark:text-white">{{ $project->category->label() }}</dd>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-ink-600 dark:text-ink-400">Membres recherchés</dt>
                            <dd class="tabular font-medium text-ink-900 dark:text-white">{{ $project->members_wanted }}</dd>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-ink-600 dark:text-ink-400">Places ouvertes</dt>
                            <dd class="tabular font-medium text-ink-900 dark:text-white">
                                {{ $openRoles->sum(fn ($role) => $role->openSlots()) }}
                            </dd>
                        </div>

                        @if ($project->country)
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-ink-600 dark:text-ink-400">Pays</dt>
                                <dd class="font-medium text-ink-900 dark:text-white">
                                    <x-flag :country="$project->country" /> {{ $project->country->name }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>

                {{-- Postes ouverts, en raccourci --}}
                @if ($openRoles->isNotEmpty())
                    <div class="panel border-emerald-300 dark:border-emerald-800">
                        <p class="flex items-center gap-2 text-sm font-semibold text-emerald-700 dark:text-emerald-400">
                            <x-icon name="user-plus" class="h-4 w-4" />
                            Postes à pourvoir
                        </p>

                        <ul class="mt-3 space-y-2">
                            @foreach ($openRoles as $role)
                                <li class="flex items-center justify-between gap-3 text-sm">
                                    <span class="min-w-0 truncate text-ink-700 dark:text-ink-300">{{ $role->title }}</span>
                                    <span class="tabular shrink-0 rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                        ×{{ $role->openSlots() }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </aside>
        </div>
    </div>
</x-app-layout>
