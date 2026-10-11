{{--
    Carte d'un projet collaboratif. Toute la carte est cliquable.

    Le titre passe avant les étiquettes : on lit le nom du projet, puis sa
    catégorie, jamais l'inverse.

    Usage : <x-project-card :project="$project" />
--}}
@props(['project'])

@php
    // Postes encore ouverts : c'est l'information qui donne envie de cliquer.
    $openRoles = $project->roles->reject->isFull();
    $openSlots = $openRoles->sum(fn ($role) => $role->openSlots());
@endphp

<a
    href="{{ route('projects.show', $project) }}"
    {{ $attributes->merge(['class' => 'card-interactive group flex flex-col overflow-hidden']) }}
>
    @if ($project->coverUrl())
        <div class="aspect-[16/7] w-full overflow-hidden border-b border-ink-200 bg-ink-100 dark:border-ink-800 dark:bg-ink-800">
            <img src="{{ $project->coverUrl() }}" alt="" loading="lazy" class="h-full w-full object-cover">
        </div>
    @endif

    <div class="flex flex-1 flex-col p-4">
        <h3 class="card-title">{{ $project->title }}</h3>

        <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1">
            <x-project-status :status="$project->status" />

            <span class="text-xs text-ink-500 dark:text-ink-400">{{ $project->category->label() }}</span>

            @if ($project->country)
                <span class="inline-flex items-center gap-1.5 text-xs text-ink-500 dark:text-ink-400">
                    <x-flag :country="$project->country" />
                    {{ $project->country->name }}
                </span>
            @endif
        </div>

        <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-ink-600 dark:text-ink-400">
            {{ $project->description }}
        </p>

        @if ($project->skills->isNotEmpty())
            <x-skill-chips :skills="$project->skills" :limit="4" class="mt-3" />
        @endif

        @if ($openRoles->isNotEmpty())
            <p class="mt-3 flex items-start gap-2 text-xs leading-relaxed text-emerald-700 dark:text-emerald-400">
                <x-icon name="user-plus" class="mt-px h-4 w-4 shrink-0" />
                <span>
                    <span class="font-medium">Recherche {{ $openSlots }} {{ $openSlots > 1 ? 'profils' : 'profil' }}</span>
                    <span class="text-ink-500 dark:text-ink-400">· {{ $openRoles->pluck('title')->join(', ') }}</span>
                </span>
            </p>
        @endif

        <div class="mt-auto flex items-center justify-between gap-3 pt-4">
            <span class="inline-flex min-w-0 items-center gap-1.5 text-xs text-ink-500 dark:text-ink-400">
                <span class="tabular">{{ $project->memberships_count }}</span>
                {{ $project->memberships_count > 1 ? 'membres' : 'membre' }}
                <span class="text-ink-300 dark:text-ink-700">·</span>
                <span class="truncate">{{ $project->owner->name }}</span>
            </span>

            <x-icon name="arrow-right" class="h-4 w-4 shrink-0 text-ink-400 transition-transform duration-150 group-hover:translate-x-0.5" />
        </div>
    </div>
</a>
