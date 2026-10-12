{{--
    L'écosystème DevLink Africa (cahier, section 5, bonus) : statistiques par pays.

    Variables : $totals (developers, countries, projects, resources, collaborations),
    $countries (tous les pays actifs, avec developers_count, projects_count,
    resources_count, top_developer, main_skills), $chartCountries (les 10 premiers
    pour le graphique) et $topSkills (avec developers_count).

    Les graphiques sont des barres horizontales d'une seule série : une seule
    couleur, triées, la valeur écrite au bout. Le tableau « Tous les pays » sert
    de version lisible sans graphique.
--}}
@php
    $plural = fn (int $count, string $one, string $many): string => $count.' '.($count > 1 ? $many : $one);
    $maxDevelopers = max(1, (int) $chartCountries->max('developers_count'));
    $maxSkill = max(1, (int) $topSkills->max('developers_count'));

    // Une valeur non nulle garde toujours une barre visible.
    $barWidth = fn (int $value, int $max): string => $value > 0 ? max(2, round($value / $max * 100, 1)).'%' : '0%';
@endphp

<x-app-layout title="L'écosystème DevLink Africa">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-page-header
            title="L'écosystème DevLink Africa"
            subtitle="Les développeurs, projets et ressources de la communauté, pays par pays."
        />

        {{-- ==========  Chiffres clés  ========== --}}
        <section class="panel mt-8" aria-label="Chiffres clés">
            <dl class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ([
                    ['value' => $totals['developers'], 'label' => 'Développeurs'],
                    ['value' => $totals['countries'], 'label' => 'Pays représentés'],
                    ['value' => $totals['projects'], 'label' => 'Projets'],
                    ['value' => $totals['resources'], 'label' => 'Ressources partagées'],
                    ['value' => $totals['collaborations'], 'label' => 'Collaborations'],
                ] as $stat)
                    <div>
                        <dt class="stat-label">{{ $stat['label'] }}</dt>
                        <dd class="stat-value mt-1 text-2xl">{{ number_format($stat['value'], 0, ',', ' ') }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        @if ($countries->isEmpty())
            <x-empty-state class="mt-8" title="L'écosystème se construit">
                Aucun pays n'est encore représenté. Les statistiques apparaîtront avec les premiers profils complétés.
            </x-empty-state>
        @else
            <div class="mt-8 grid gap-6 lg:grid-cols-[3fr_2fr]">

                {{-- ==========  Développeurs par pays  ========== --}}
                <section class="panel">
                    <x-section-heading title="Développeurs par pays">
                        <x-slot name="action">
                            <span class="text-xs text-ink-500 dark:text-ink-400">
                                {{ $chartCountries->count() < $totals['countries'] ? 'Top '.$chartCountries->count() : 'Tous les pays' }}
                            </span>
                        </x-slot>
                    </x-section-heading>

                    @if ($chartCountries->isEmpty())
                        <p class="muted mt-4 text-sm">Aucun profil de développeur complété pour l'instant.</p>
                    @else
                        <ol class="mt-5 space-y-1">
                            @foreach ($chartCountries as $country)
                                @php
                                    $details = $plural($country->developers_count, 'développeur', 'développeurs').', '
                                        .$plural($country->projects_count, 'projet', 'projets').', '
                                        .$plural($country->resources_count, 'ressource', 'ressources');
                                @endphp
                                <li class="group relative">
                                    <a
                                        href="{{ route('devs.index', ['country' => $country->slug]) }}"
                                        class="grid grid-cols-[7.5rem_minmax(0,1fr)_2.5rem] items-center gap-3 rounded-lg px-2 py-2 transition-colors hover:bg-ink-50 focus-visible:bg-ink-50 sm:grid-cols-[10rem_minmax(0,1fr)_3rem] dark:hover:bg-ink-800/50 dark:focus-visible:bg-ink-800/50"
                                        aria-label="{{ $country->name }} : {{ $details }}. Voir les développeurs."
                                    >
                                        <span class="flex min-w-0 items-center gap-2 text-sm font-medium text-ink-700 dark:text-ink-200">
                                            <x-flag :country="$country" />
                                            <span class="truncate">{{ $country->name }}</span>
                                        </span>
                                        <span class="h-2.5 rounded-r-[4px] bg-ink-100 dark:bg-ink-800" aria-hidden="true">
                                            <span class="block h-full rounded-r-[4px] bg-brand-600 dark:bg-brand-400" style="width: {{ $barWidth($country->developers_count, $maxDevelopers) }}"></span>
                                        </span>
                                        <span class="tabular text-right text-sm font-semibold text-ink-900 dark:text-white">{{ $country->developers_count }}</span>
                                    </a>

                                    {{-- Détail au survol ou au focus clavier --}}
                                    <span
                                        role="tooltip"
                                        class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded-md bg-ink-900 px-2.5 py-1.5 text-xs text-white shadow-lg group-focus-within:block group-hover:block dark:bg-ink-700"
                                    >
                                        <span class="font-semibold">{{ $country->name }}</span> · {{ $details }}
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </section>

                {{-- ==========  Compétences les plus présentes  ========== --}}
                <section class="panel">
                    <x-section-heading title="Compétences les plus présentes" />

                    @if ($topSkills->isEmpty())
                        <p class="muted mt-4 text-sm">Aucune compétence déclarée pour l'instant.</p>
                    @else
                        <ol class="mt-5 space-y-1">
                            @foreach ($topSkills as $skill)
                                <li class="group relative">
                                    <a
                                        href="{{ route('devs.index', ['skill' => $skill->slug]) }}"
                                        class="grid grid-cols-[6.5rem_minmax(0,1fr)_2.5rem] items-center gap-3 rounded-lg px-2 py-2 transition-colors hover:bg-ink-50 focus-visible:bg-ink-50 dark:hover:bg-ink-800/50 dark:focus-visible:bg-ink-800/50"
                                        aria-label="{{ $skill->name }} : {{ $plural($skill->developers_count, 'développeur', 'développeurs') }}. Voir les développeurs."
                                    >
                                        <span class="truncate text-sm font-medium text-ink-700 dark:text-ink-200">{{ $skill->name }}</span>
                                        <span class="h-2.5 rounded-r-[4px] bg-ink-100 dark:bg-ink-800" aria-hidden="true">
                                            <span class="block h-full rounded-r-[4px] bg-brand-600 dark:bg-brand-400" style="width: {{ $barWidth($skill->developers_count, $maxSkill) }}"></span>
                                        </span>
                                        <span class="tabular text-right text-sm font-semibold text-ink-900 dark:text-white">{{ $skill->developers_count }}</span>
                                    </a>

                                    <span
                                        role="tooltip"
                                        class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded-md bg-ink-900 px-2.5 py-1.5 text-xs text-white shadow-lg group-focus-within:block group-hover:block dark:bg-ink-700"
                                    >
                                        <span class="font-semibold">{{ $skill->name }}</span> · {{ $plural($skill->developers_count, 'développeur', 'développeurs') }}
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </section>
            </div>

            {{-- ==========  Tous les pays  ========== --}}
            <section class="panel mt-6">
                <x-section-heading title="Tous les pays" :count="$countries->count()" />

                <div class="-mx-5 mt-5 overflow-x-auto sm:-mx-6">
                    <table class="w-full min-w-[52rem] whitespace-nowrap text-left text-sm">
                        <thead>
                            <tr class="border-b border-ink-200 text-xs uppercase tracking-wider text-ink-500 dark:border-ink-800 dark:text-ink-400">
                                <th scope="col" class="px-5 py-2.5 font-semibold sm:px-6">Pays</th>
                                <th scope="col" class="px-3 py-2.5 text-right font-semibold">Développeurs</th>
                                <th scope="col" class="px-3 py-2.5 text-right font-semibold">Projets</th>
                                <th scope="col" class="px-3 py-2.5 text-right font-semibold">Ressources</th>
                                <th scope="col" class="px-3 py-2.5 font-semibold">Meilleur DevScore</th>
                                <th scope="col" class="px-5 py-2.5 font-semibold sm:px-6">Compétences phares</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100 dark:divide-ink-800/70">
                            @foreach ($countries as $country)
                                <tr>
                                    <th scope="row" class="px-5 py-3 font-medium text-ink-900 sm:px-6 dark:text-white">
                                        <a href="{{ route('devs.index', ['country' => $country->slug]) }}" class="inline-flex items-center gap-2 hover:text-brand-600 dark:hover:text-brand-400">
                                            <x-flag :country="$country" />
                                            {{ $country->name }}
                                        </a>
                                    </th>
                                    <td class="tabular px-3 py-3 text-right text-ink-700 dark:text-ink-200">{{ $country->developers_count }}</td>
                                    <td class="tabular px-3 py-3 text-right text-ink-700 dark:text-ink-200">{{ $country->projects_count }}</td>
                                    <td class="tabular px-3 py-3 text-right text-ink-700 dark:text-ink-200">{{ $country->resources_count }}</td>
                                    <td class="px-3 py-3">
                                        @if ($country->top_developer)
                                            <a href="{{ route('devs.show', $country->top_developer) }}" class="inline-flex items-center gap-2 text-ink-700 hover:text-brand-600 dark:text-ink-200 dark:hover:text-brand-400">
                                                <x-avatar :user="$country->top_developer" size="xs" />
                                                <span>{{ $country->top_developer->name }}</span>
                                                <span class="tabular text-xs text-ink-500 dark:text-ink-400">{{ $country->top_developer->devscore }} pts</span>
                                            </a>
                                        @else
                                            <span class="text-ink-400 dark:text-ink-500">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 sm:px-6">
                                        @forelse ($country->main_skills as $skill)
                                            <span class="chip mb-1 mr-1">{{ $skill->name }}</span>
                                        @empty
                                            <span class="text-ink-400 dark:text-ink-500">—</span>
                                        @endforelse
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @guest
            <section class="panel mt-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <p class="font-semibold text-ink-900 dark:text-white">Représentez votre pays</p>
                    <p class="mt-1 text-sm text-ink-600 dark:text-ink-400">Créez votre profil pour apparaître dans la recherche, le classement et ces statistiques.</p>
                </div>
                <a href="{{ route('register') }}" class="btn-primary shrink-0">Créer un compte</a>
            </section>
        @endguest
    </div>
</x-app-layout>
