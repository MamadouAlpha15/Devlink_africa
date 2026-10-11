{{--
    Pays (et ville) d'un développeur ou d'un projet.
    `country` peut être null.

    Usage : <x-country-label :country="$user->country" :city="$user->city" />
--}}
@props([
    'country' => null,
    'city' => null,
])

@if ($country || $city)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 text-sm text-ink-600 dark:text-ink-400']) }}>
        @if ($country)
            <x-flag :country="$country" />
            <span>{{ $country->name }}</span>
        @else
            <x-icon name="map-pin" class="h-4 w-4 text-ink-400" />
        @endif

        @if ($city)
            <span class="text-ink-300 dark:text-ink-700" aria-hidden="true">·</span>
            <span>{{ $city }}</span>
        @endif
    </span>
@endif
