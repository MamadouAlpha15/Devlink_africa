{{--
    Score de réputation (DevScore).

    Neutre et discret : c'est une métadonnée récurrente, pas l'information
    principale d'une carte. La couleur de marque est réservée à l'action.

    Usage : <x-devscore :score="$developer->devscore" />
--}}
@props([
    'score' => 0,
    'size' => 'sm',
])

@php
    $sizeClasses = $size === 'lg' ? 'gap-1.5 px-2.5 py-1 text-sm' : 'gap-1 px-2 py-0.5 text-xs';
    $iconClasses = $size === 'lg' ? 'h-4 w-4' : 'h-4 w-4';
@endphp

<span
    {{ $attributes->merge(['class' => "inline-flex shrink-0 items-center rounded-md bg-ink-100 font-medium text-ink-700 dark:bg-ink-800 dark:text-ink-300 $sizeClasses"]) }}
    title="{{ number_format($score, 0, ',', ' ') }} points DevScore"
>
    <x-icon name="zap" class="{{ $iconClasses }} text-ink-400 dark:text-ink-500" />
    <span class="tabular">{{ number_format($score, 0, ',', ' ') }}</span>
</span>
