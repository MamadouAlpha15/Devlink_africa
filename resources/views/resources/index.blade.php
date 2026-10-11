{{--
    Dev Resources — liste et recherche.

    Variables : $resources (paginé, 12/page), $filters (q, type, skill,
    country, tag, sort), $hasFilters, $sorts, $types, $countries,
    $skillsByCategory, $popularTags. Voir docs/front-end/ressources.md.

    Même structure que « Trouver des développeurs » : colonne de filtres fixe
    sur grand écran, tiroir plein écran en dessous (composant `filterDrawer`).
--}}
@php
    $typeLabel = collect($types)->first(fn ($type) => $type->value === $filters['type'])?->label();
    $skillName = collect($skillsByCategory)->flatten()->firstWhere('slug', $filters['skill'])?->name;
    $countryName = $countries->firstWhere('slug', $filters['country'])?->name;

    // Filtres actifs, rappelés au-dessus des résultats : on doit pouvoir
    // retirer un critère sans rouvrir le panneau.
    $activeFilters = collect([
        $filters['q'] ? ['label' => '« '.$filters['q'].' »', 'without' => 'q'] : null,
        $typeLabel ? ['label' => $typeLabel, 'without' => 'type'] : null,
        $skillName ? ['label' => $skillName, 'without' => 'skill'] : null,
        $countryName ? ['label' => $countryName, 'without' => 'country'] : null,
        $filters['tag'] ? ['label' => '#'.$filters['tag'], 'without' => 'tag'] : null,
    ])->filter()->values();

    // Lien conservant les autres critères, sans celui qu'on retire.
    $linkWithout = fn (string $key) => route('resources.index', array_filter(
        array_merge($filters, [$key => null]),
        fn ($value) => filled($value) && $value !== 'recent',
    ));
@endphp

<x-app-layout title="Dev Resources">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-page-header
            title="Dev Resources"
            :subtitle="$resources->total().' '.($resources->total() > 1 ? 'ressources partagées' : 'ressource partagée').' par la communauté'"
        >
            @auth
                <x-slot name="actions">
                    <a href="{{ route('resources.create') }}" class="btn-primary btn-sm">
                        <x-icon name="plus" class="h-4 w-4" />
                        Partager une ressource
                    </a>
                </x-slot>
            @endauth
        </x-page-header>

        <div class="mt-8 grid gap-8 lg:grid-cols-[17rem_1fr] xl:grid-cols-[19rem_1fr]">

            {{-- ==========  Filtres  ========== --}}
            <div x-data="filterDrawer">

                {{-- Déclencheur mobile --}}
                <button type="button" @click="open()" class="btn-secondary btn-block lg:hidden">
                    <x-icon name="filter" class="h-4 w-4" />
                    Filtrer
                    @if ($activeFilters->isNotEmpty())
                        <span class="tabular rounded-full bg-brand-600 px-1.5 py-0.5 text-xs font-bold text-white">
                            {{ $activeFilters->count() }}
                        </span>
                    @endif
                </button>

                {{-- Voile du tiroir --}}
                <div
                    x-show="isOpen"
                    x-cloak
                    x-transition.opacity
                    @click="close()"
                    class="fixed inset-0 z-40 bg-ink-950/60 backdrop-blur-sm lg:hidden"
                ></div>

                {{-- Panneau : tiroir sur mobile, colonne fixe sur grand écran --}}
                <aside
                    :class="isOpen ? 'fixed inset-y-0 left-0 z-50 w-[88%] max-w-sm overflow-y-auto' : 'hidden'"
                    class="lg:!static lg:!z-auto lg:!block lg:!w-auto lg:!max-w-none lg:!overflow-visible"
                    x-cloak
                >
                    <form
                        method="GET"
                        action="{{ route('resources.index') }}"
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

                        {{-- Mot-clé --}}
                        <div>
                            <label for="q" class="label">Mot-clé</label>
                            <div class="input-group">
                                <x-icon name="search" />
                                <input id="q" type="search" name="q" value="{{ $filters['q'] }}"
                                       class="input" placeholder="paiement, auth, ui kit…" autocomplete="off">
                            </div>
                            <p class="field-hint">Titre, description, technologie ou tag.</p>
                        </div>

                        {{-- Type --}}
                        <div>
                            <label for="type" class="label">Type</label>
                            <select id="type" name="type" class="select">
                                <option value="">Tous les types</option>
                                @foreach ($types as $type)
                                    <option value="{{ $type->value }}" @selected($filters['type'] === $type->value)>
                                        {{ $type->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Technologie --}}
                        <div>
                            <label for="skill" class="label">Technologie</label>
                            <select id="skill" name="skill" class="select">
                                <option value="">Toutes</option>
                                @foreach ($skillsByCategory as $category => $skills)
                                    <optgroup label="{{ $category }}">
                                        @foreach ($skills as $skill)
                                            <option value="{{ $skill->slug }}" @selected($filters['skill'] === $skill->slug)>
                                                {{ $skill->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        {{-- Pays --}}
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

                        {{-- Tri --}}
                        <div>
                            <label for="sort" class="label">Trier par</label>
                            <select id="sort" name="sort" class="select">
                                @foreach ($sorts as $value => $label)
                                    <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Le tag filtré n'a pas de champ : on le transporte. --}}
                        @if ($filters['tag'])
                            <input type="hidden" name="tag" value="{{ $filters['tag'] }}">
                        @endif

                        <div class="space-y-2 border-t border-ink-200 pt-5 dark:border-ink-800">
                            <button type="submit" class="btn-primary btn-block">Rechercher</button>

                            @if ($hasFilters)
                                <a href="{{ route('resources.index') }}" class="btn-ghost btn-block">Effacer les filtres</a>
                            @endif
                        </div>

                        {{-- Tags populaires : une entrée de recherche à un clic. --}}
                        @if ($popularTags->isNotEmpty())
                            <div class="border-t border-ink-200 pt-5 dark:border-ink-800">
                                <p class="label">Tags populaires</p>

                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach ($popularTags as $tag)
                                        <a
                                            href="{{ route('resources.index', ['tag' => $tag->slug]) }}"
                                            class="chip-button {{ $filters['tag'] === $tag->slug ? 'chip-button-active' : '' }}"
                                        >
                                            #{{ $tag->name }}
                                            <span class="tabular text-ink-400 dark:text-ink-500">{{ $tag->resources_count }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </form>
                </aside>
            </div>

            {{-- ==========  Résultats  ========== --}}
            <div class="min-w-0">

                {{-- Rappel des critères actifs, retirables un par un. --}}
                @if ($activeFilters->isNotEmpty())
                    <div class="mb-5 flex flex-wrap items-center gap-2">
                        <span class="caption">Filtres actifs</span>

                        @foreach ($activeFilters as $filter)
                            <a href="{{ $linkWithout($filter['without']) }}" class="chip-button chip-button-active">
                                {{ $filter['label'] }}
                                <x-icon name="close" class="h-3 w-3" />
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($resources->isNotEmpty())
                    <div class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-3">
                        @foreach ($resources as $resource)
                            <x-resource-card :resource="$resource" />
                        @endforeach
                    </div>

                    <div class="mt-8">{{ $resources->links() }}</div>
                @else
                    <x-empty-state icon="package" title="Aucune ressource ne correspond">
                        Élargissez la recherche, ou publiez la première ressource sur ce sujet.

                        <x-slot name="action">
                            <div class="flex flex-wrap justify-center gap-2">
                                @if ($hasFilters)
                                    <a href="{{ route('resources.index') }}" class="btn-secondary btn-sm">Effacer les filtres</a>
                                @endif
                                @auth
                                    <a href="{{ route('resources.create') }}" class="btn-primary btn-sm">Partager une ressource</a>
                                @endauth
                            </div>
                        </x-slot>
                    </x-empty-state>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
