{{--
    Carte d'un développeur. Toute la carte est cliquable vers le profil.

    Utilisée par /devs et par AfriCommit AI. Quand `score` est fourni, un
    anneau de correspondance s'affiche : c'est le SEUL élément de couleur
    de marque de la carte, parce que c'est la seule information neuve.

    Usage : <x-dev-card :developer="$developer" />
            <x-dev-card :developer="$result['user']" :score="$result['score']" />
--}}
@props([
    'developer',
    'score' => null,
    'matchedSkills' => [],
])

@php
    // Les compétences qui correspondent passent devant les autres.
    $matched = (array) $matchedSkills;

    $skills = $matched
        ? $developer->skills->sortByDesc(fn ($skill) => in_array($skill->name, $matched, true))->values()
        : $developer->skills;
@endphp

<a
    href="{{ route('devs.show', $developer) }}"
    {{ $attributes->merge(['class' => 'card-interactive group flex flex-col p-4']) }}
>
    <div class="flex items-start gap-3">
        <x-avatar :user="$developer" size="md" />

        <div class="min-w-0 flex-1">
            <p class="truncate font-semibold text-ink-900 dark:text-white">
                {{ $developer->name }}
            </p>

            <p class="mt-0.5 truncate text-sm text-ink-600 dark:text-ink-400">
                {{ $developer->profile_type?->label() }}
                @if ($developer->experience_level)
                    <span class="text-ink-300 dark:text-ink-700">·</span> {{ $developer->experience_level->label() }}
                @endif
            </p>

            <x-country-label :country="$developer->country" :city="$developer->city" class="mt-1 !text-xs" />
        </div>

        {{-- Anneau de correspondance (AfriCommit AI), sinon le DevScore.

             Rayon 15,9155 dans un viewBox de 36 : la circonférence vaut
             exactement 100, donc le décalage est le complément du
             pourcentage — aucun nombre magique, et pas de débordement
             de l'arc à 100 %. --}}
        @if (! is_null($score))
            <div
                class="relative shrink-0"
                x-data="animatedScore({{ (int) $score }})"
                title="{{ $score }} % de correspondance"
            >
                <svg viewBox="0 0 36 36" class="h-12 w-12 -rotate-90">
                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke-width="2.5"
                            class="stroke-ink-200 dark:stroke-ink-800" />
                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke-width="2.5" stroke-linecap="butt"
                            class="stroke-brand-600 transition-[stroke-dashoffset] duration-700 ease-snap dark:stroke-brand-400"
                            stroke-dasharray="100"
                            :stroke-dashoffset="100 - value" />
                </svg>
                <span class="tabular absolute inset-0 flex items-center justify-center text-[0.9375rem] font-semibold text-ink-900 dark:text-white">
                    {{ $score }}
                </span>
            </div>
        @else
            <x-devscore :score="$developer->devscore" />
        @endif
    </div>

    @if ($developer->bio)
        <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-ink-600 dark:text-ink-400">
            {{ $developer->bio }}
        </p>
    @endif

    @if ($skills->isNotEmpty())
        <x-skill-chips :skills="$skills" :limit="4" :highlight="$matched" class="mt-3" />
    @endif

    {{-- Pied de carte poussé en bas, pour aligner les cartes d'une ligne. --}}
    <div class="mt-auto flex items-center justify-between gap-3 pt-4">
        <div class="flex min-w-0 items-center gap-2">
            <x-availability :user="$developer" />

            @if (! is_null($score))
                <x-devscore :score="$developer->devscore" />
            @endif
        </div>

        <span class="inline-flex shrink-0 items-center gap-1 text-xs font-medium text-ink-500 dark:text-ink-400">
            Voir le profil
            <x-icon name="arrow-right" class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5" />
        </span>
    </div>
</a>
