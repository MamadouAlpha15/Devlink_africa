{{--
    Administration — statistiques.

    Variables : $stats (users, users_this_month, suspended, projects,
    projects_recruiting, projects_completed, resources, resources_pending,
    countries, collaborations, reports_pending), $topCountries et
    $signupsPerMonth. Voir docs/front-end/pages.md.

    Ce qui demande une action passe avant les chiffres : un administrateur
    vient ici pour savoir ce qu'il doit traiter, pas pour contempler des totaux.
--}}
@php
    $pendingWork = $stats['reports_pending'] + $stats['resources_pending'];

    $tiles = [
        ['label' => 'Utilisateurs', 'value' => $stats['users'], 'detail' => '+'.$stats['users_this_month'].' ce mois · '.$stats['suspended'].' suspendu'.($stats['suspended'] > 1 ? 's' : '')],
        ['label' => 'Projets', 'value' => $stats['projects'], 'detail' => $stats['projects_recruiting'].' en recrutement · '.$stats['projects_completed'].' terminé'.($stats['projects_completed'] > 1 ? 's' : '')],
        ['label' => 'Ressources', 'value' => $stats['resources'], 'detail' => $stats['resources_pending'].' en attente de validation'],
        ['label' => 'Pays', 'value' => $stats['countries'], 'detail' => 'représentés sur 54'],
        ['label' => 'Collaborations', 'value' => $stats['collaborations'], 'detail' => 'membres ayant rejoint un projet'],
    ];

    // Une valeur non nulle garde toujours une barre visible, comme sur la page écosystème.
    $maxCountry = max(1, (int) $topCountries->max('users_count'));
    $maxSignups = max(1, (int) (collect($signupsPerMonth)->max() ?? 0));
    $barWidth = fn (int $value, int $max): string => $value > 0 ? max(2, round($value / $max * 100, 1)).'%' : '0%';
@endphp

<x-app-layout title="Administration">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-page-header
            title="Administration"
            subtitle="L'état de la plateforme, et ce qui attend d'être traité."
        />

        <div class="mt-8">
            @include('admin.partials.nav')
        </div>

        {{-- Ce qui attend : en tête, et seulement s'il y a quelque chose. --}}
        @if ($pendingWork > 0)
            <div class="alert-warning mt-6">
                <x-icon name="inbox" class="mt-0.5 h-4 w-4 shrink-0" />

                <div class="min-w-0 flex-1">
                    <p class="font-medium">
                        {{ $pendingWork > 1 ? $pendingWork.' éléments attendent votre décision' : 'Un élément attend votre décision' }}
                    </p>

                    <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                        @if ($stats['reports_pending'])
                            <a href="{{ route('admin.reports.index') }}" class="link">
                                {{ $stats['reports_pending'] }} signalement{{ $stats['reports_pending'] > 1 ? 's' : '' }} à traiter
                            </a>
                        @endif

                        @if ($stats['resources_pending'])
                            <a href="{{ route('admin.resources.index', ['status' => 'pending']) }}" class="link">
                                {{ $stats['resources_pending'] }} ressource{{ $stats['resources_pending'] > 1 ? 's' : '' }} à valider
                            </a>
                        @endif
                    </p>
                </div>
            </div>
        @endif

        {{-- Les totaux --}}
        <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-5">
            @foreach ($tiles as $tile)
                <div class="card p-4">
                    <p class="tabular text-2xl font-semibold tracking-[-0.02em] text-ink-900 dark:text-white">
                        {{ number_format($tile['value'], 0, ',', ' ') }}
                    </p>
                    <p class="mt-0.5 text-sm font-medium text-ink-700 dark:text-ink-300">{{ $tile['label'] }}</p>
                    <p class="mt-1 text-xs leading-relaxed text-ink-500 dark:text-ink-400">{{ $tile['detail'] }}</p>
                </div>
            @endforeach
        </div>

        {{--
            Deux graphiques, même grammaire que la page écosystème : barres
            horizontales d'une seule couleur, triées, la valeur écrite au bout.
            Une seule série, donc aucune légende à lire.
        --}}
        <div class="mt-6 grid gap-6 md:grid-cols-2">

            <section class="panel">
                <x-section-heading title="Membres par pays" :count="$topCountries->count()" />

                @if ($topCountries->isEmpty())
                    <p class="muted mt-4">Aucun pays représenté pour l'instant.</p>
                @else
                    <ol class="mt-5 space-y-1">
                        @foreach ($topCountries as $country)
                            <li>
                                <a
                                    href="{{ route('admin.users.index', ['q' => $country->name]) }}"
                                    class="grid grid-cols-[7.5rem_minmax(0,1fr)_2.5rem] items-center gap-3 rounded-lg px-2 py-2 transition-colors hover:bg-ink-50 sm:grid-cols-[9rem_minmax(0,1fr)_3rem] dark:hover:bg-ink-800/50"
                                    aria-label="{{ $country->name }} : {{ $country->users_count }} membre{{ $country->users_count > 1 ? 's' : '' }}"
                                >
                                    <span class="flex min-w-0 items-center gap-2 text-sm font-medium text-ink-700 dark:text-ink-200">
                                        <x-flag :country="$country" />
                                        <span class="truncate">{{ $country->name }}</span>
                                    </span>

                                    <span class="h-2.5 rounded-r-[4px] bg-ink-100 dark:bg-ink-800" aria-hidden="true">
                                        <span class="block h-full rounded-r-[4px] bg-brand-600 dark:bg-brand-400" style="width: {{ $barWidth($country->users_count, $maxCountry) }}"></span>
                                    </span>

                                    <span class="tabular text-right text-sm font-semibold text-ink-900 dark:text-white">{{ $country->users_count }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>

            <section class="panel">
                <x-section-heading title="Inscriptions" />
                <p class="muted mt-1">Sur les six derniers mois.</p>

                @if (empty($signupsPerMonth))
                    <p class="muted mt-4">Aucune inscription enregistrée.</p>
                @else
                    <ol class="mt-5 space-y-1">
                        @foreach ($signupsPerMonth as $month => $count)
                            <li
                                class="grid grid-cols-[7.5rem_minmax(0,1fr)_2.5rem] items-center gap-3 px-2 py-2 sm:grid-cols-[9rem_minmax(0,1fr)_3rem]"
                                aria-label="{{ $month }} : {{ $count }} inscription{{ $count > 1 ? 's' : '' }}"
                            >
                                <span class="truncate text-sm font-medium text-ink-700 dark:text-ink-200">{{ $month }}</span>

                                <span class="h-2.5 rounded-r-[4px] bg-ink-100 dark:bg-ink-800" aria-hidden="true">
                                    <span class="block h-full rounded-r-[4px] bg-brand-600 dark:bg-brand-400" style="width: {{ $barWidth((int) $count, $maxSignups) }}"></span>
                                </span>

                                <span class="tabular text-right text-sm font-semibold text-ink-900 dark:text-white">{{ $count }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
