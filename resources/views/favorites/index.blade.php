{{--
    Mes favoris.

    Variables : $developers, $resources, $projects. Voir docs/front-end/favoris.md.

    Des listes, pas des galeries : on vient ici pour retrouver un élément
    précis et parfois le retirer, pas pour parcourir. Le bouton de retrait est
    donc sur chaque ligne, et les trois sections restent visibles même vides —
    elles disent ce qu'on peut enregistrer.
--}}
@php
    $total = $developers->count() + $resources->count() + $projects->count();
@endphp

<x-app-layout title="Mes favoris">
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-page-header
            title="Mes favoris"
            :subtitle="$total > 0
                ? $total.' '.($total > 1 ? 'éléments enregistrés' : 'élément enregistré')
                : 'Rien d\'enregistré pour l\'instant'"
        />

        <x-flash class="mt-6" />

        {{-- ==========  Développeurs  ========== --}}
        <section class="panel mt-8">
            <x-section-heading title="Développeurs" :count="$developers->count()" />

            @forelse ($developers as $developer)
                @if ($loop->first)
                    <ul class="mt-4 divide-y divide-ink-100 dark:divide-ink-800/70">
                @endif

                <li class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                    <a href="{{ route('devs.show', $developer) }}" class="flex min-w-0 flex-1 items-center gap-3">
                        <x-avatar :user="$developer" size="sm" class="shrink-0" />

                        <span class="min-w-0">
                            <span class="block truncate text-sm font-medium text-ink-900 dark:text-white">
                                {{ $developer->name }}
                            </span>
                            <span class="mt-0.5 flex items-center gap-2">
                                <x-country-label :country="$developer->country" class="truncate text-xs" />
                                @if ($developer->skills->isNotEmpty())
                                    <span class="truncate text-xs text-ink-500">
                                        {{ $developer->skills->take(3)->pluck('name')->join(' · ') }}
                                    </span>
                                @endif
                            </span>
                        </span>
                    </a>

                    <div class="shrink-0">
                        @include('partials.favorite-button', ['type' => 'developer', 'model' => $developer])
                    </div>
                </li>

                @if ($loop->last)
                    </ul>
                @endif
            @empty
                <p class="muted mt-4">
                    Aucun développeur enregistré.
                    <a href="{{ route('devs.index') }}" class="link">Trouver des développeurs</a>
                </p>
            @endforelse
        </section>

        {{-- ==========  Ressources  ========== --}}
        <section class="panel mt-6">
            <x-section-heading title="Ressources" :count="$resources->count()" />

            @forelse ($resources as $resource)
                @if ($loop->first)
                    <ul class="mt-4 divide-y divide-ink-100 dark:divide-ink-800/70">
                @endif

                <li class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                    <a href="{{ route('resources.show', $resource) }}" class="flex min-w-0 flex-1 items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-ink-200 text-ink-400 dark:border-ink-800 dark:text-ink-500">
                            <x-icon name="package" class="h-4 w-4" />
                        </span>

                        <span class="min-w-0">
                            <span class="block truncate text-sm font-medium text-ink-900 dark:text-white">
                                {{ $resource->title }}
                            </span>
                            <span class="muted mt-0.5 block truncate">
                                {{ $resource->type->label() }}
                                <span class="text-ink-300 dark:text-ink-700" aria-hidden="true">·</span>
                                par {{ $resource->user->name }}
                            </span>
                        </span>
                    </a>

                    <div class="shrink-0">
                        @include('partials.favorite-button', ['type' => 'resource', 'model' => $resource])
                    </div>
                </li>

                @if ($loop->last)
                    </ul>
                @endif
            @empty
                <p class="muted mt-4">
                    Aucune ressource enregistrée.
                    <a href="{{ route('resources.index') }}" class="link">Parcourir les ressources</a>
                </p>
            @endforelse
        </section>

        {{-- ==========  Projets  ========== --}}
        <section class="panel mt-6">
            <x-section-heading title="Projets" :count="$projects->count()" />

            @forelse ($projects as $project)
                @if ($loop->first)
                    <ul class="mt-4 divide-y divide-ink-100 dark:divide-ink-800/70">
                @endif

                <li class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                    <a href="{{ route('projects.show', $project) }}" class="flex min-w-0 flex-1 items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-ink-200 text-ink-400 dark:border-ink-800 dark:text-ink-500">
                            <x-icon name="folder" class="h-4 w-4" />
                        </span>

                        <span class="min-w-0">
                            <span class="block truncate text-sm font-medium text-ink-900 dark:text-white">
                                {{ $project->title }}
                            </span>
                            <span class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1">
                                <x-project-status :status="$project->status" />
                                <span class="muted truncate">par {{ $project->owner->name }}</span>
                                <x-country-label :country="$project->country" class="truncate text-xs" />
                            </span>
                        </span>
                    </a>

                    <div class="shrink-0">
                        @include('partials.favorite-button', ['type' => 'project', 'model' => $project])
                    </div>
                </li>

                @if ($loop->last)
                    </ul>
                @endif
            @empty
                <p class="muted mt-4">
                    Aucun projet enregistré.
                    <a href="{{ route('projects.index') }}" class="link">Voir les projets</a>
                </p>
            @endforelse
        </section>
    </div>
</x-app-layout>
