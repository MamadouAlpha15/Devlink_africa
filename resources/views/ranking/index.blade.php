{{--
    Classement africain.

    Variables : $developers (paginé, 25/page, avec `points`), $firstRank,
    $filters, $periodLabel, $periods, $countries, $myRank.
    Voir docs/front-end/devscore-classement.md.

    Le podium se signale par une médaille, pas par une estrade : trois
    icônes suffisent, et la colonne de rangs reste lisible de haut en bas.
--}}
@php
    $zoneName = $filters['country']
        ? $countries->firstWhere('slug', $filters['country'])?->name
        : 'Afrique';

    // Médailles des trois premiers. Les teintes encodent le rang : c'est de
    // l'information, pas de la décoration.
    $medalColors = [
        1 => 'text-amber-500',
        2 => 'text-ink-400 dark:text-ink-300',
        3 => 'text-orange-700 dark:text-orange-500',
    ];
@endphp

<x-app-layout title="Classement africain">
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">

        <x-page-header
            title="Classement africain"
            :subtitle="$zoneName.' · '.$periodLabel"
        >
            @if ($myRank)
                <x-slot name="actions">
                    <span class="chip-strong">
                        <x-icon name="trending-up" class="h-3.5 w-3.5" />
                        Votre rang&nbsp;: {{ $myRank }}<sup>{{ $myRank === 1 ? 'er' : 'e' }}</sup>
                    </span>
                </x-slot>
            @endif
        </x-page-header>

        {{--
            Filtres du cahier : Afrique • Pays • Mois • Année. Les champs
            Mois et Année n'apparaissent que si la période les concerne —
            sinon on demande trois dates pour un seul classement.
        --}}
        <form
            method="GET"
            action="{{ route('ranking.index') }}"
            x-data="{ period: @js($filters['period']) }"
            class="card mt-6 flex flex-wrap items-end gap-3 p-4"
        >
            <div class="w-full sm:w-64">
                <label for="country" class="label">Zone</label>
                <select id="country" name="country" class="select mt-1.5">
                    <option value="">Toute l'Afrique</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country->slug }}" @selected($filters['country'] === $country->slug)>
                            {{ $country->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-44">
                <label for="period" class="label">Période</label>
                <select id="period" name="period" x-model="period" class="select mt-1.5">
                    @foreach ($periods as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div x-show="period === 'month'" x-cloak class="w-full sm:w-44">
                <label for="month" class="label">Mois</label>
                <input id="month" type="month" name="month" value="{{ $filters['month'] }}" max="{{ now()->format('Y-m') }}" class="input mt-1.5">
            </div>

            <div x-show="period === 'year'" x-cloak class="w-28">
                <label for="year" class="label">Année</label>
                <input id="year" type="number" name="year" value="{{ $filters['year'] }}" min="2020" max="{{ now()->year }}" class="input tabular mt-1.5">
            </div>

            <button type="submit" class="btn-primary">Afficher</button>
        </form>

        @if ($developers->isNotEmpty())
            <div class="card mt-6 divide-y divide-ink-100 dark:divide-ink-800/70">
                @foreach ($developers as $developer)
                    @php
                        $rank = $firstRank + $loop->index;
                        $isMe = auth()->check() && $developer->id === auth()->id();
                    @endphp

                    <a
                        href="{{ route('devs.show', $developer) }}"
                        class="flex items-center gap-3 px-4 py-3 transition-colors first:rounded-t-xl last:rounded-b-xl hover:bg-ink-50 sm:gap-4 dark:hover:bg-ink-800/60 {{ $isMe ? 'bg-brand-50/70 dark:bg-brand-950/40' : '' }}"
                    >
                        {{-- Rang : largeur fixe et chiffres tabulaires, pour que
                             la colonne reste alignée de 1 à 999. --}}
                        <span class="flex w-10 shrink-0 items-center justify-end gap-1.5">
                            @if ($rank <= 3)
                                <x-icon name="medal" class="h-4 w-4 {{ $medalColors[$rank] }}" />
                            @endif
                            <span class="tabular text-sm font-semibold text-ink-900 dark:text-white">{{ $rank }}</span>
                        </span>

                        <x-avatar :user="$developer" size="sm" class="shrink-0" />

                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-2">
                                <span class="truncate text-sm font-medium text-ink-900 dark:text-white">
                                    {{ $developer->name }}
                                </span>

                                @if ($isMe)
                                    <span class="badge-neutral shrink-0">Vous</span>
                                @endif
                            </span>

                            <span class="mt-0.5 flex items-center gap-2">
                                <x-country-label :country="$developer->country" class="truncate text-xs" />

                                {{-- Badges : les icônes du jeu, jamais les emojis stockés. --}}
                                @if ($developer->badges->isNotEmpty())
                                    <span class="flex shrink-0 items-center gap-1">
                                        @foreach ($developer->badges as $badge)
                                            <x-badge-icon
                                                :badge="$badge"
                                                class="h-3.5 w-3.5 text-ink-400 dark:text-ink-500"
                                                :title="$badge->name"
                                            />
                                        @endforeach
                                    </span>
                                @endif
                            </span>
                        </span>

                        <span class="shrink-0 text-right">
                            <span class="tabular block text-sm font-semibold text-ink-900 dark:text-white">
                                {{ (int) $developer->points }}
                            </span>
                            <span class="block text-xs text-ink-500">points</span>
                        </span>
                    </a>
                @endforeach
            </div>

            <div class="mt-6">{{ $developers->links() }}</div>
        @else
            <x-empty-state class="mt-6" icon="trophy" title="Aucun classement sur cette période">
                Personne n'a encore gagné de points pour {{ $zoneName }} · {{ mb_strtolower($periodLabel) }}.
                Élargissez la zone ou la période.

                <x-slot name="action">
                    <a href="{{ route('ranking.index') }}" class="btn-secondary btn-sm">Voir toute l'Afrique</a>
                </x-slot>
            </x-empty-state>
        @endif
    </div>
</x-app-layout>
