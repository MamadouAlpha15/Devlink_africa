{{--
    DevLink AI — résultats d'une recherche.

    Variables : $search (->query), $criteria (skills, skill_categories,
    country, project_type, summary, engine), $results (score, matched_skills,
    user). Voir docs/front-end/devlink-ai.md.
--}}
@php
    $usedClaude = ($criteria['engine'] ?? 'local') === 'claude';

    $skills = collect($criteria['skills'] ?? []);
    $skillCategories = collect($criteria['skill_categories'] ?? []);

    $projectType = isset($criteria['project_type'])
        ? \App\Enums\ProjectCategory::from($criteria['project_type'])->label()
        : null;

    // Meilleur score, pour le résumé en haut de page.
    $bestScore = collect($results)->max('score');
@endphp

<x-app-layout title="Résultats DevLink AI">
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-page-header
            title="Résultats DevLink AI"
            :back-link="route('ai.create')"
            back-label="Nouvelle recherche"
        >
            <x-slot name="actions">
                <a href="{{ route('ai.create') }}" class="btn-primary btn-sm">
                    <x-icon name="sparkles" class="h-4 w-4" />
                    Nouvelle recherche
                </a>
            </x-slot>
        </x-page-header>

        {{-- ==========  La demande  ========== --}}
        <div class="panel mt-6">
            <div class="flex items-start gap-3.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-ink-100 text-ink-500 dark:bg-ink-800 dark:text-ink-400">
                    <x-icon name="message" class="h-4 w-4" />
                </span>

                <div class="min-w-0 flex-1">
                    <p class="caption">Votre demande</p>
                    <p class="mt-1.5 text-base italic leading-relaxed text-ink-800 dark:text-ink-200">
                        « {{ $search->query }} »
                    </p>
                </div>
            </div>
        </div>

        {{-- ==========  Ce que l'IA a compris  ========== --}}
        <div class="panel mt-4 border-brand-300 dark:border-brand-700">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="flex items-center gap-2.5 section-title">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-white">
                        <x-icon name="sparkles" class="h-4 w-4" />
                    </span>
                    L'IA a compris
                </h2>

                {{-- Moteur réellement utilisé : Claude ou l'analyseur local de secours. --}}
                <span class="{{ $usedClaude ? 'badge-neutral' : 'badge-neutral' }}">
                    {{ $usedClaude ? 'Moteur Claude' : 'Analyseur local' }}
                </span>
            </div>

            @if ($criteria['summary'] ?? null)
                <p class="mt-4 text-sm leading-relaxed text-ink-700 dark:text-ink-300">
                    {{ $criteria['summary'] }}
                </p>
            @endif

            <dl class="mt-5 grid gap-3 sm:grid-cols-3">
                {{-- Compétences --}}
                <div class="surface-muted p-3.5">
                    <dt class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-ink-500 dark:text-ink-400">
                        <x-icon name="code" class="h-3.5 w-3.5" />
                        Compétences
                    </dt>
                    <dd class="mt-2">
                        @if ($skills->isNotEmpty())
                            <ul class="flex flex-wrap gap-1.5">
                                @foreach ($skills as $skillSlug)
                                    <li class="chip">{{ $skillSlug }}</li>
                                @endforeach
                            </ul>
                        @elseif ($skillCategories->isNotEmpty())
                            <ul class="flex flex-wrap gap-1.5">
                                @foreach ($skillCategories as $category)
                                    <li class="chip">{{ \App\Enums\SkillCategory::from($category)->label() }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="muted">Aucune précisée</p>
                        @endif
                    </dd>
                </div>

                {{-- Pays --}}
                <div class="surface-muted p-3.5">
                    <dt class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-ink-500 dark:text-ink-400">
                        <x-icon name="map-pin" class="h-3.5 w-3.5" />
                        Pays
                    </dt>
                    <dd class="mt-2">
                        @if ($criteria['country'] ?? null)
                            <span class="chip">{{ $criteria['country'] }}</span>
                        @else
                            <p class="muted">Toute l'Afrique</p>
                        @endif
                    </dd>
                </div>

                {{-- Type de projet --}}
                <div class="surface-muted p-3.5">
                    <dt class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-ink-500 dark:text-ink-400">
                        <x-icon name="briefcase" class="h-3.5 w-3.5" />
                        Type de projet
                    </dt>
                    <dd class="mt-2">
                        @if ($projectType)
                            <span class="chip">{{ $projectType }}</span>
                        @else
                            <p class="muted">Non précisé</p>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        {{-- ==========  Profils recommandés  ========== --}}
        <div class="mt-8">
            @if (count($results) > 0)
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <h2 class="section-title">
                        {{ count($results) }} {{ count($results) > 1 ? 'profils recommandés' : 'profil recommandé' }}
                    </h2>

                    <p class="muted">
                        Meilleure correspondance&nbsp;: <span class="tabular font-semibold text-brand-600 dark:text-brand-400">{{ $bestScore }} %</span>
                    </p>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    @foreach ($results as $result)
                        <x-dev-card
                            :developer="$result['user']"
                            :score="$result['score']"
                            :matched-skills="$result['matched_skills']"
                        />
                    @endforeach
                </div>

                <p class="field-hint mt-6 text-center">
                    Le pourcentage combine les compétences (60 pts), le pays (20 pts),
                    l'expérience sur un projet du même type (10 pts) et la disponibilité (10 pts).
                </p>
            @else
                <x-empty-state icon="search" title="Aucun profil ne correspond">
                    Essayez une demande plus large : retirez le pays, ou citez une autre
                    compétence du même domaine.

                    <x-slot name="action">
                        <a href="{{ route('ai.create') }}" class="btn-primary btn-sm">
                            <x-icon name="sparkles" class="h-4 w-4" />
                            Reformuler ma recherche
                        </a>
                    </x-slot>
                </x-empty-state>
            @endif
        </div>
    </div>
</x-app-layout>
