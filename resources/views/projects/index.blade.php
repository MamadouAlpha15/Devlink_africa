{{--
    Liste des projets collaboratifs.

    Filtres GET : q, category, country, status, skill, sort.
    Variables : $projects (12/page), $filters, $hasFilters, $sorts,
    $categories, $statuses, $countries, $skillsByCategory.
    Voir docs/front-end/pages.md.
--}}
@php
    // Pastilles des filtres actifs.
    $activeFilters = [];

    if ($filters['q']) {
        $activeFilters[] = ['key' => 'q', 'label' => '« '.$filters['q'].' »'];
    }

    if ($filters['category']) {
        $category = collect($categories)->firstWhere('value', $filters['category']);
        $activeFilters[] = ['key' => 'category', 'label' => $category?->label() ?? $filters['category']];
    }

    if ($filters['country']) {
        $country = $countries->firstWhere('slug', $filters['country']);
        $activeFilters[] = ['key' => 'country', 'label' => $country ? $country->name : $filters['country']];
    }

    if ($filters['status']) {
        $status = collect($statuses)->firstWhere('value', $filters['status']);
        $activeFilters[] = ['key' => 'status', 'label' => $status?->label() ?? $filters['status']];
    }

    if ($filters['skill']) {
        $skill = $skillsByCategory->flatten()->firstWhere('slug', $filters['skill']);
        $activeFilters[] = ['key' => 'skill', 'label' => $skill?->name ?? $filters['skill']];
    }
@endphp

<x-app-layout title="Projets collaboratifs">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-page-header
            title="Projets collaboratifs"
            :subtitle="$projects->total().' '.($projects->total() > 1 ? 'projets ouverts à la collaboration' : 'projet ouvert à la collaboration')"
        >
            @auth
                <x-slot name="actions">
                    <a href="{{ route('projects.create') }}" class="btn-primary btn-sm">
                        <x-icon name="plus" class="h-4 w-4" />
                        Créer un projet
                    </a>
                </x-slot>
            @endauth
        </x-page-header>

        <div class="mt-8 grid gap-8 lg:grid-cols-[17rem_1fr] xl:grid-cols-[19rem_1fr]">

            {{-- ==========  Filtres  ========== --}}
            <div x-data="filterDrawer">

                <button type="button" @click="open()" class="btn-secondary btn-block lg:hidden">
                    <x-icon name="filter" class="h-4 w-4" />
                    Filtrer
                    @if ($hasFilters)
                        <span class="tabular rounded-full bg-brand-600 px-1.5 py-0.5 text-xs font-bold text-white">
                            {{ count($activeFilters) }}
                        </span>
                    @endif
                </button>

                <div
                    x-show="isOpen"
                    x-cloak
                    x-transition.opacity
                    @click="close()"
                    class="fixed inset-0 z-40 bg-ink-950/60 backdrop-blur-sm lg:hidden"
                ></div>

                <aside
                    :class="isOpen ? 'fixed inset-y-0 left-0 z-50 w-[88%] max-w-sm overflow-y-auto' : 'hidden'"
                    class="lg:!static lg:!z-auto lg:!block lg:!w-auto lg:!max-w-none lg:!overflow-visible"
                    x-cloak
                >
                    <form
                        method="GET"
                        action="{{ route('projects.index') }}"
                        class="card h-full space-y-5 rounded-none p-5 lg:sticky lg:top-24 lg:rounded-xl"
                    >
                        <div class="flex items-center justify-between">
                            <h2 class="flex items-center gap-2 font-semibold text-ink-900 dark:text-white">
                                <x-icon name="sliders" class="h-4 w-4 text-ink-400" />
                                Filtres
                            </h2>

                            <button type="button" @click="close()" class="btn btn-ghost btn-square h-8 w-8 lg:hidden" aria-label="Fermer">
                                <x-icon name="close" class="h-4 w-4" />
                            </button>
                        </div>

                        <div>
                            <label for="q" class="label">Mot-clé</label>
                            <div class="input-group">
                                <x-icon name="search" />
                                <input id="q" type="search" name="q" value="{{ $filters['q'] }}"
                                       class="input" placeholder="livraison, fintech…" autocomplete="off">
                            </div>
                            <p class="field-hint">Titre, description, technologies, postes.</p>
                        </div>

                        <div>
                            <label for="category" class="label">Catégorie</label>
                            <select id="category" name="category" class="select">
                                <option value="">Toutes les catégories</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->value }}" @selected($filters['category'] === $category->value)>
                                        {{ $category->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="country" class="label">Pays</label>
                            <select id="country" name="country" class="select">
                                <option value="">Toute l'Afrique</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->slug }}" @selected($filters['country'] === $country->slug)>
                                        {{ $country->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="status" class="label">Statut</label>
                            <select id="status" name="status" class="select">
                                <option value="">Tous (hors archivés)</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>
                                        {{ $status->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="skill" class="label">Technologie ou poste</label>
                            <select id="skill" name="skill" class="select">
                                <option value="">Toutes</option>
                                @foreach ($skillsByCategory as $group => $skills)
                                    <optgroup label="{{ $group }}">
                                        @foreach ($skills as $skill)
                                            <option value="{{ $skill->slug }}" @selected($filters['skill'] === $skill->slug)>
                                                {{ $skill->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        <input type="hidden" name="sort" value="{{ $filters['sort'] }}">

                        <div class="divider space-y-2.5 pt-5">
                            <button type="submit" class="btn-primary btn-block">
                                <x-icon name="search" class="h-4 w-4" />
                                Appliquer les filtres
                            </button>

                            @if ($hasFilters)
                                <a href="{{ route('projects.index') }}" class="btn-ghost btn-block">Effacer les filtres</a>
                            @endif
                        </div>
                    </form>
                </aside>
            </div>

            {{-- ==========  Résultats  ========== --}}
            <div class="min-w-0 space-y-6">

                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                        @forelse ($activeFilters as $filter)
                            <a
                                href="{{ route('projects.index', collect($filters)->except($filter['key'])->filter()->all()) }}"
                                class="chip-button chip-button-active"
                                title="Retirer ce filtre"
                            >
                                {{ $filter['label'] }}
                                <x-icon name="close" class="h-3 w-3" />
                            </a>
                        @empty
                            <p class="muted">Tous les projets, les archivés exceptés.</p>
                        @endforelse
                    </div>

                    <form method="GET" action="{{ route('projects.index') }}" class="flex shrink-0 items-center gap-2">
                        @foreach (collect($filters)->except('sort')->filter() as $name => $value)
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endforeach

                        <label for="sort" class="shrink-0 text-sm text-ink-600 dark:text-ink-400">Trier&nbsp;:</label>
                        <select id="sort" name="sort" class="select h-10 w-auto pr-9 text-sm" onchange="this.form.submit()">
                            @foreach ($sorts as $value => $label)
                                <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>

                        <noscript><button type="submit" class="btn-secondary btn-sm">OK</button></noscript>
                    </form>
                </div>

                @if ($projects->isNotEmpty())
                    <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                        @foreach ($projects as $project)
                            <x-project-card :project="$project" />
                        @endforeach
                    </div>

                    <div class="divider pt-6">
                        {{ $projects->links() }}
                    </div>
                @else
                    <x-empty-state icon="folder" title="Aucun projet ne correspond">
                        Modifiez vos filtres, ou lancez le vôtre : publiez votre projet et
                        décrivez les profils que vous cherchez.

                        <x-slot name="action">
                            <div class="flex flex-wrap justify-center gap-2">
                                @if ($hasFilters)
                                    <a href="{{ route('projects.index') }}" class="btn-secondary btn-sm">Effacer les filtres</a>
                                @endif
                                @auth
                                    <a href="{{ route('projects.create') }}" class="btn-primary btn-sm">
                                        <x-icon name="plus" class="h-4 w-4" />
                                        Créer un projet
                                    </a>
                                @endauth
                            </div>
                        </x-slot>
                    </x-empty-state>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
