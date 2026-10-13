{{--
    Trouver des développeurs.

    Tous les filtres partent en GET vers `devs.index` : les noms des
    paramètres (q, country, city, skill, level, availability, type, sort)
    sont imposés par le backend, voir docs/front-end/pages.md.

    La colonne de filtres est un seul formulaire : fixe à partir de `lg`,
    tiroir plein écran en dessous (composant Alpine `filterDrawer`).
--}}
@php
    // Libellés lisibles des filtres actifs, pour les pastilles « à retirer ».
    $activeFilters = [];

    if ($filters['q']) {
        $activeFilters[] = ['key' => 'q', 'label' => '« '.$filters['q'].' »'];
    }

    if ($filters['country']) {
        $country = $countries->firstWhere('slug', $filters['country']);
        $activeFilters[] = ['key' => 'country', 'label' => $country ? $country->name : $filters['country']];
    }

    if ($filters['city']) {
        $activeFilters[] = ['key' => 'city', 'label' => $filters['city']];
    }

    if ($filters['skill']) {
        $skill = $skillsByCategory->flatten()->firstWhere('slug', $filters['skill']);
        $activeFilters[] = ['key' => 'skill', 'label' => $skill?->name ?? $filters['skill']];
    }

    foreach ([
        'level' => $experienceLevels,
        'availability' => $availabilities,
        'type' => $profileTypes,
    ] as $key => $options) {
        if ($filters[$key]) {
            $option = collect($options)->firstWhere('value', $filters[$key]);
            $activeFilters[] = ['key' => $key, 'label' => $option?->label() ?? $filters[$key]];
        }
    }
@endphp

<x-app-layout title="Trouver des développeurs">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-page-header
            title="Trouver des développeurs"
            :subtitle="$developers->total().' '.($developers->total() > 1 ? 'profils correspondent' : 'profil correspond').' à votre recherche'"
        />

        <div class="mt-8 grid gap-8 lg:grid-cols-[17rem_1fr] xl:grid-cols-[19rem_1fr]">

            {{-- ==========  Filtres  ========== --}}
            <div x-data="filterDrawer">

                {{-- Déclencheur mobile --}}
                <button type="button" @click="open()" class="btn-secondary btn-block lg:hidden">
                    <x-icon name="filter" class="h-4 w-4" />
                    Filtrer
                    @if ($hasFilters)
                        <span class="tabular rounded-full bg-brand-600 px-1.5 py-0.5 text-xs font-bold text-white">
                            {{ count($activeFilters) }}
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
                        action="{{ route('devs.index') }}"
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
                                       class="input" placeholder="Laravel, Conakry…" autocomplete="off">
                            </div>
                            <p class="field-hint">Nom, ville, bio, compétence ou pays.</p>
                        </div>

                        {{-- Pays --}}
                        <div>
                            <label for="country" class="label">Pays</label>
                            <select id="country" name="country" class="select">
                                <option value="">Toute l'Afrique</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->slug }}" @selected($filters['country'] === $country->slug)>
                                        {{ $country->name }} ({{ $country->developers_count }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Ville --}}
                        <div>
                            <label for="city" class="label">Ville</label>
                            <input id="city" type="text" name="city" value="{{ $filters['city'] }}"
                                   class="input" placeholder="Conakry, Dakar…">
                        </div>

                        {{-- Compétence --}}
                        <div>
                            <label for="skill" class="label">Compétence</label>
                            <select id="skill" name="skill" class="select">
                                <option value="">Toutes les compétences</option>
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

                        {{-- Listes d'énumérations --}}
                        @foreach ([
                            'level' => ['Niveau d\'expérience', $experienceLevels, 'Tous les niveaux'],
                            'availability' => ['Disponibilité', $availabilities, 'Peu importe'],
                            'type' => ['Type de profil', $profileTypes, 'Tous les profils'],
                        ] as $name => [$label, $options, $placeholder])
                            <div>
                                <label for="{{ $name }}" class="label">{{ $label }}</label>
                                <select id="{{ $name }}" name="{{ $name }}" class="select">
                                    <option value="">{{ $placeholder }}</option>
                                    @foreach ($options as $option)
                                        <option value="{{ $option->value }}" @selected($filters[$name] === $option->value)>
                                            {{ $option->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach

                        {{-- Le tri est aussi réglable depuis l'en-tête des résultats. --}}
                        <input type="hidden" name="sort" value="{{ $filters['sort'] }}">

                        <div class="divider space-y-2.5 pt-5">
                            <button type="submit" class="btn-primary btn-block">
                                <x-icon name="search" class="h-4 w-4" />
                                Appliquer les filtres
                            </button>

                            @if ($hasFilters)
                                <a href="{{ route('devs.index') }}" class="btn-ghost btn-block">
                                    Effacer les filtres
                                </a>
                            @endif
                        </div>
                    </form>
                </aside>
            </div>

            {{-- ==========  Résultats  ========== --}}
            <div class="min-w-0 space-y-6">

                {{-- Barre : filtres actifs + tri --}}
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                        @forelse ($activeFilters as $filter)
                            {{-- Chaque pastille renvoie vers la même recherche sans ce filtre. --}}
                            <a
                                href="{{ route('devs.index', collect($filters)->except($filter['key'])->filter()->all()) }}"
                                class="chip-button chip-button-active"
                                title="Retirer ce filtre"
                            >
                                {{ $filter['label'] }}
                                <x-icon name="close" class="h-3 w-3" />
                            </a>
                        @empty
                            <p class="muted">Tous les profils publics et complétés.</p>
                        @endforelse
                    </div>

                    {{-- Tri : formulaire autonome qui conserve les filtres courants. --}}
                    <form method="GET" action="{{ route('devs.index') }}" class="flex shrink-0 items-center gap-2">
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

                {{-- Grille des profils --}}
                @if ($developers->isNotEmpty())
                    <div class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-3">
                        @foreach ($developers as $developer)
                            <x-dev-card :developer="$developer" />
                        @endforeach
                    </div>

                    <div class="divider pt-6">
                        {{ $developers->links() }}
                    </div>
                @else
                    <x-empty-state icon="search" title="Aucun développeur ne correspond">
                        Essayez d'élargir votre recherche : retirez le pays, changez de compétence
                        ou utilisez un mot-clé plus court.

                        @if ($hasFilters)
                            <x-slot name="action">
                                <a href="{{ route('devs.index') }}" class="btn-primary btn-sm">Effacer les filtres</a>
                            </x-slot>
                        @endif
                    </x-empty-state>
                @endif

                {{-- Développeurs par pays (cahier, section 5) --}}
                @if ($topCountries->isNotEmpty())
                    <div class="panel">
                        <x-section-heading title="Développeurs par pays">
                            <x-slot name="action">
                                <a href="{{ route('ecosystem') }}" class="link text-sm">Voir l'écosystème</a>
                            </x-slot>
                        </x-section-heading>

                        <div class="mt-5 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($topCountries as $country)
                                <a
                                    href="{{ route('devs.index', ['country' => $country->slug]) }}"
                                    class="flex items-center justify-between gap-3 rounded-lg border border-ink-200 px-3.5 py-2.5 transition hover:border-brand-300 hover:bg-brand-50 dark:border-ink-800 dark:hover:border-brand-700 dark:hover:bg-ink-800"
                                >
                                    <span class="flex min-w-0 items-center gap-2 text-sm font-medium text-ink-700 dark:text-ink-200">
                                        <x-flag :country="$country" />
                                        <span class="truncate">{{ $country->name }}</span>
                                    </span>

                                    <span class="tabular shrink-0 rounded-md bg-ink-100 px-2 py-0.5 text-xs font-semibold text-ink-600 dark:bg-ink-800 dark:text-ink-300">
                                        {{ $country->developers_count }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
