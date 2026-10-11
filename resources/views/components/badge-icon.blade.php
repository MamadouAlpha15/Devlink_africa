{{--
    Icône d'un badge DevScore.

    Les badges sont stockés avec un emoji (`badges.icon`), mais ils ont aussi
    un `slug` stable : on mappe sur le slug, qui ne changera pas, plutôt que
    sur l'emoji. Un badge ajouté plus tard sans correspondance ici retombe
    sur la médaille — jamais de case vide, jamais d'emoji affiché.
--}}
@props([
    'badge',
])

@php
    $iconFor = [
        'premier-projet' => 'rocket',
        'collaborateur' => 'handshake',
        'builder' => 'hammer',
        'expert-laravel' => 'flame',
        'contributeur-actif' => 'star',
        'panafricain' => 'globe',
    ];
@endphp

<x-icon
    :name="$iconFor[$badge->slug] ?? 'medal'"
    {{ $attributes->merge(['class' => 'h-5 w-5']) }}
/>
