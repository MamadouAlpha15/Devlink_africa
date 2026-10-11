{{--
    DevLink AI — poser la question.

    Formulaire : POST /ai, champ `query` (10 à 500 caractères), 10 recherches
    par minute maximum. L'appel à l'IA prend quelques secondes : le composant
    Alpine `aiSearchForm` affiche un écran d'attente avec des étapes.

    Variables : $examples (4 phrases), $recentSearches (5 dernières).
    Voir docs/front-end/devlink-ai.md.
--}}
<x-app-layout title="DevLink AI">
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">

        {{-- ==========  Titre  ========== --}}
        <div>
            <h1 class="page-title">
                Qui cherchez-vous&nbsp;?
            </h1>

            <p class="lead mt-3 max-w-2xl">
                Décrivez le profil dont vous avez besoin, en une phrase et en français.
                DevLink AI identifie la compétence, le pays et le type de projet,
                puis vous propose jusqu'à 10 développeurs classés par correspondance.
            </p>
        </div>

        {{-- ==========  Formulaire  ========== --}}
        <form
            method="POST"
            action="{{ route('ai.store') }}"
            x-data="aiSearchForm(@js(old('query', '')))"
            @submit="start()"
            class="mt-10"
        >
            @csrf

            <div class="card p-2 shadow-card transition focus-within:border-brand-400 focus-within:ring-4 focus-within:ring-brand-500/10">
                <label for="query" class="sr-only">Votre recherche</label>

                <textarea
                    id="query"
                    name="query"
                    x-ref="queryField"
                    x-model="query"
                    rows="3"
                    maxlength="500"
                    required
                    :disabled="isSearching"
                    class="block w-full resize-none border-0 bg-transparent px-4 py-3.5 text-base leading-relaxed text-ink-900 placeholder:text-ink-400 focus:ring-0 dark:text-white dark:placeholder:text-ink-500"
                    placeholder="Je cherche un développeur Flutter en Guinée pour travailler sur une application de livraison."
                    @keydown.meta.enter="$el.form.requestSubmit()"
                    @keydown.ctrl.enter="$el.form.requestSubmit()"
                ></textarea>

                <div class="flex flex-wrap items-center justify-between gap-3 px-3 pb-1.5 pt-1">
                    <p class="text-xs text-ink-500 dark:text-ink-400">
                        <span class="tabular" :class="isTooShort && 'font-semibold text-amber-600 dark:text-amber-400'" x-text="length">0</span>
                        / 500 caractères
                        <span x-show="isTooShort" x-cloak class="text-amber-600 dark:text-amber-400">· 10 minimum</span>
                        <span class="ml-2 hidden sm:inline">
                            <span class="kbd">Ctrl</span> + <span class="kbd">Entrée</span> pour lancer
                        </span>
                    </p>

                    <button type="submit" class="btn-primary btn-sm" :disabled="! canSubmit">
                        <x-icon name="sparkles" class="h-4 w-4" />
                        Trouver les profils
                    </button>
                </div>
            </div>

            <x-input-error :messages="$errors->get('query')" class="mt-3" />

            {{-- Exemples cliquables --}}
            <div class="mt-6">
                <p class="text-center text-xs font-medium text-ink-500 dark:text-ink-400">
                    Pas d'idée&nbsp;? Essayez une de ces recherches
                </p>

                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    @foreach ($examples as $example)
                        <button
                            type="button"
                            @click="useExample(@js($example))"
                            class="group flex items-start gap-2.5 rounded-lg border border-ink-200 bg-white p-3 text-left text-sm text-ink-700 transition hover:border-brand-300 hover:bg-brand-50 dark:border-ink-800 dark:bg-ink-900 dark:text-ink-300 dark:hover:border-brand-700 dark:hover:bg-ink-800"
                        >
                            <x-icon name="search" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-ink-400 transition group-hover:text-brand-500" />
                            <span>{{ $example }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- ==========  Écran d'attente  ========== --}}
            <div
                x-show="isSearching"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-50 flex items-center justify-center bg-white/90 px-4 backdrop-blur-sm dark:bg-ink-950/90"
            >
                <div class="w-full max-w-md text-center">
                    {{-- Icône qui tourne --}}
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-xl bg-brand-600 text-white">
                        <x-icon name="sparkles" class="h-7 w-7 animate-pulse" />
                    </div>

                    <p class="mt-6 text-lg font-semibold text-ink-900 dark:text-white">
                        DevLink AI analyse votre demande
                    </p>

                    {{-- Étape en cours --}}
                    <p class="mt-2 h-5 text-sm text-ink-600 dark:text-ink-400" x-text="currentStep"></p>

                    {{-- Barre indéterminée --}}
                    <div class="progress-track mx-auto mt-6 max-w-xs">
                        <div class="h-full w-1/3 rounded-full bg-brand-600 animate-sweep dark:bg-brand-500"></div>
                    </div>

                    {{-- Liste des étapes, celles déjà passées sont cochées --}}
                    <ul class="mx-auto mt-8 max-w-xs space-y-2.5 text-left">
                        <template x-for="(step, index) in steps" :key="index">
                            <li class="flex items-center gap-2.5 text-xs transition"
                                :class="index <= stepIndex ? 'text-ink-700 dark:text-ink-200' : 'text-ink-400 dark:text-ink-600'">
                                <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full border transition"
                                      :class="index < stepIndex
                                        ? 'border-brand-600 bg-brand-600 text-white dark:border-brand-500 dark:bg-brand-500'
                                        : (index === stepIndex
                                            ? 'border-brand-600 dark:border-brand-400'
                                            : 'border-ink-300 dark:border-ink-700')">
                                    <svg x-show="index < stepIndex" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="4" stroke-linecap="round" stroke-linejoin="round" class="h-2.5 w-2.5">
                                        <path d="M20 6 9 17l-5-5"/>
                                    </svg>
                                </span>
                                <span x-text="step"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </form>

        {{-- ==========  Historique  ========== --}}
        @if ($recentSearches->isNotEmpty())
            <div class="panel mt-10">
                <x-section-heading title="Mes dernières recherches" />

                <ul class="mt-5 space-y-2">
                    @foreach ($recentSearches as $search)
                        @php($resultCount = count($search->results ?? []))

                        <li>
                            <a href="{{ route('ai.show', $search) }}"
                               class="group flex items-center justify-between gap-4 rounded-lg border border-ink-200 p-3.5 transition hover:border-brand-300 hover:bg-brand-50 dark:border-ink-800 dark:hover:border-brand-700 dark:hover:bg-ink-800">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-ink-900 group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-300">
                                        {{ Str::limit($search->query, 90) }}
                                    </p>
                                    <p class="mt-1 text-xs text-ink-500 dark:text-ink-400">
                                        <span class="tabular">{{ $resultCount }}</span>
                                        {{ $resultCount > 1 ? 'profils trouvés' : 'profil trouvé' }}
                                        <span class="text-ink-300 dark:text-ink-700">·</span>
                                        {{ $search->created_at->diffForHumans() }}
                                    </p>
                                </div>

                                <x-icon name="chevron-right" class="h-4 w-4 shrink-0 text-ink-400 transition group-hover:translate-x-0.5 group-hover:text-brand-500" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ==========  Explication du score  ========== --}}
        <div class="panel mt-6">
            <x-section-heading title="Comment le pourcentage est calculé" />

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                @foreach ([
                    ['label' => 'Compétences demandées', 'points' => 60, 'text' => 'Selon votre niveau. Une compétence voisine du même domaine compte à 70 %.'],
                    ['label' => 'Pays', 'points' => 20, 'text' => 'Le pays demandé correspond à votre pays.'],
                    ['label' => 'Projet du même type', 'points' => 10, 'text' => 'Vous avez déjà travaillé sur un projet de cette catégorie.'],
                    ['label' => 'Disponibilité', 'points' => 10, 'text' => 'Les profils disponibles passent devant.'],
                ] as $criterion)
                    <div class="rounded-lg border border-ink-200 p-4 dark:border-ink-800">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-ink-900 dark:text-white">{{ $criterion['label'] }}</p>
                            <span class="tabular shrink-0 text-sm font-bold text-brand-600 dark:text-brand-400">{{ $criterion['points'] }} pts</span>
                        </div>
                        <div class="progress-track mt-2.5 h-1.5">
                            <div class="progress-bar" style="width: {{ $criterion['points'] }}%"></div>
                        </div>
                        <p class="mt-2.5 text-xs leading-relaxed text-ink-600 dark:text-ink-400">{{ $criterion['text'] }}</p>
                    </div>
                @endforeach
            </div>

            <p class="field-hint mt-4">
                Un critère que vous ne demandez pas donne ses points à tout le monde.
                Seuls les profils publics, actifs et complétés sont recommandés.
            </p>
        </div>
    </div>
</x-app-layout>
