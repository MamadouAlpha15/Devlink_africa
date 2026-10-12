{{--
    Note sur cinq, en lecture seule.

    Cinq étoiles dessinées en SVG plutôt que le caractère typographique
    correspondant : la police ne le rend pas de la même façon d'un système
    à l'autre, et un caractère ne se colore pas à moitié.

    Usage : <x-star-rating :value="$review->rating" />
            <x-star-rating :value="4.2" show-value />
--}}
@props([
    'value' => 0,
    'showValue' => false,
    'size' => 'h-4 w-4',
])

@php
    $rounded = (int) round((float) $value);
    $formatted = number_format((float) $value, 1, ',', ' ');
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5']) }}>
    <span class="flex items-center gap-0.5" role="img" aria-label="Note : {{ $formatted }} sur 5">
        @foreach (range(1, 5) as $star)
            <x-icon
                :name="$star <= $rounded ? 'star-filled' : 'star'"
                class="{{ $size }} {{ $star <= $rounded ? 'text-amber-500' : 'text-ink-300 dark:text-ink-600' }}"
            />
        @endforeach
    </span>

    @if ($showValue)
        <span class="tabular text-sm font-medium text-ink-700 dark:text-ink-300">{{ $formatted }}</span>
    @endif
</span>
