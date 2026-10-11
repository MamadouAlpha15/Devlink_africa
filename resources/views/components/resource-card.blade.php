{{--
    Carte d'une ressource (API, template, package…). Toute la carte est cliquable.

    Le titre passe avant les étiquettes, comme pour les projets. Les trois
    chiffres du bas — j'aime, note, téléchargements — sont la seule mesure de
    qualité disponible avant d'ouvrir : ils restent visibles.

    Usage : <x-resource-card :resource="$resource" />

    Les compteurs viennent d'un `withCount` : sur une page qui ne les charge
    pas (les favoris), la ligne de chiffres disparaît d'elle-même.
--}}
@props(['resource'])

@php
    $rating = $resource->rating_avg;
    $hasCounters = ! is_null($resource->likes_count) || ! is_null($resource->downloads_count);
@endphp

<a
    href="{{ route('resources.show', $resource) }}"
    {{ $attributes->merge(['class' => 'card-interactive group flex flex-col overflow-hidden']) }}
>
    @if ($resource->imageUrl())
        <div class="aspect-[16/7] w-full overflow-hidden border-b border-ink-200 bg-ink-100 dark:border-ink-800 dark:bg-ink-800">
            <img src="{{ $resource->imageUrl() }}" alt="" loading="lazy" class="h-full w-full object-cover">
        </div>
    @endif

    <div class="flex flex-1 flex-col p-4">
        <h3 class="card-title">{{ $resource->title }}</h3>

        <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1">
            <span class="badge-neutral">{{ $resource->type->label() }}</span>

            @if ($resource->country)
                <span class="inline-flex items-center gap-1.5 text-xs text-ink-500 dark:text-ink-400">
                    <x-flag :country="$resource->country" />
                    {{ $resource->country->name }}
                </span>
            @endif

            @if ($resource->hasFile())
                <span class="inline-flex items-center gap-1 text-xs text-ink-500 dark:text-ink-400">
                    <x-icon name="download" class="h-3.5 w-3.5" />
                    Fichier
                </span>
            @endif
        </div>

        <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-ink-600 dark:text-ink-400">
            {{ $resource->description }}
        </p>

        @if ($resource->skills->isNotEmpty())
            <x-skill-chips :skills="$resource->skills" :limit="3" class="mt-3" />
        @endif

        {{--
            Pied de carte : les chiffres et l'auteur forment un seul bloc, la
            flèche s'aligne sur son centre. Séparés, la flèche se retrouvait
            au milieu de la carte au lieu de sa dernière ligne.
        --}}
        <div class="mt-auto flex items-center justify-between gap-3 pt-4">
            <span class="min-w-0 flex-1">
                @if ($hasCounters)
                    <span class="flex items-center gap-3 text-xs text-ink-500 dark:text-ink-400">
                        <span class="inline-flex items-center gap-1" title="J'aime">
                            <x-icon name="heart" class="h-3.5 w-3.5" />
                            <span class="tabular">{{ $resource->likes_count ?? 0 }}</span>
                        </span>

                        <span class="inline-flex items-center gap-1" title="Note moyenne">
                            <x-icon name="star" class="h-3.5 w-3.5" />
                            <span class="tabular">{{ $rating ? number_format($rating, 1, ',', ' ') : '—' }}</span>
                        </span>

                        <span class="inline-flex items-center gap-1" title="Téléchargements">
                            <x-icon name="download" class="h-3.5 w-3.5" />
                            <span class="tabular">{{ $resource->downloads_count ?? 0 }}</span>
                        </span>
                    </span>
                @endif

                <span class="{{ $hasCounters ? 'mt-1 ' : '' }}block truncate text-xs text-ink-500 dark:text-ink-400">
                    par {{ $resource->user->name }}
                </span>
            </span>

            <x-icon name="arrow-right" class="h-4 w-4 shrink-0 text-ink-400 transition-transform duration-150 group-hover:translate-x-0.5" />
        </div>
    </div>
</a>
